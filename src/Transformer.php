<?php

namespace jfadich\JsonResponder;

use jfadich\JsonResponder\Exceptions\InvalidModelRelation;
use jfadich\JsonResponder\Contracts\Transformable;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Collection;
use League\Fractal\ParamBag;
use BadMethodCallException;

/**
 * Transformers take a Model object and prepare it for output to JSON. It can include nested relations dynamically
 * This is an extension of League\Fractal library
 */
abstract class Transformer extends TransformerAbstract
{
    /**
     * Columns available for sorting result.
     *
     * @var array ['api_name' => 'sql_field']
     */
    protected $orderColumns = [
        'created' => 'created_at',
        'updated' => 'updated_at'
    ];

    /**
     * Available includes that should not be passed to Eloquent for SQL eager loading.
     *
     * @var array
     */
    protected $lazyIncludes = [

    ];

    /**
     * Max number of resources that can be requested
     *
     * @var int
     */
    protected $requestLimit = 1000;

    /**
     * Parse the limit and order parameters
     *
     * @param ParamBag $params
     * @return array
     */
    protected function parseParams(ParamBag $params = null)
    {
        $result = ['limit' => null, 'order' => null];

        if ($params === null) {
            return $result;
        }

        $order = $params->get('order');
        $limit = $params->get('limit');

        if (is_numeric($limit[0])) {
            $result['limit'] = min($limit[0], $this->requestLimit);
        }

        if (is_array($order) && count($order) === 2) {
            if (in_array($order[0], array_keys($this->orderColumns)) && in_array($order[1],
                    ['desc', 'asc'])
            ) {
                $order[0] = $this->orderColumns[$order[0]];
                $result['order'] = $order;
            }
        }

        return $result;
    }

    /**
     * Prevent includes from being processed
     *
     * @return $this
     */
    public function disableIncludes()
    {
        $this->availableIncludes = [];

        return $this;
    }

    /**
     * Get the include data from the model.
     *
     * @param $model
     * @param $relation
     * @return Collection|Transformable|null
     */
    protected function resolveInclude($model, $relation)
    {
        $include = $model->$relation;

        if (!$include) {
            return null;
        }

        return $include;
    }

    /**
     * Get the model relation from the method name
     *
     * @param $method
     * @return bool|string
     */
    protected function resolveRelation($method)
    {
        $relation = camel_case(str_replace('include', '', $method));

        if (!in_array($relation, $this->getAvailableIncludes())) {
            return false;
        }

        return $relation;
    }

    /**
     * Get the transformer for the requested model
     *
     * @param $model
     * @param $relation
     * @return mixed
     * @throws InvalidModelRelation
     */
    protected function getRelatedTransformer($model, $relation)
    {
        if (!method_exists($model, $relation) || !($relation = $model->$relation())) {
            $class = get_class($model);
            throw new InvalidModelRelation("'$relation' is invalid relation for '$class'");
        }

        if (!($relation = $relation->getRelated()) instanceof Transformable) {
            throw new InvalidModelRelation('Model must be a Transformable instance');
        }

        return $relation->getTransformer();
    }

    /**
     * Get an array of includes that are available, but should not be eager loaded with Eloquent.
     *
     * @return array
     */
    public function getLazyIncludes()
    {
        return $this->lazyIncludes;
    }

    /**
     * Magic method to catch include* method calls.
     * Validate the include exists, then return item or collection.
     *
     * @param $method
     * @param $arguments
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\Item|null
     * @throws InvalidModelRelation
     */
    public function __call($method, $arguments)
    {
        $relation = $this->resolveRelation($method);

        if (!$relation || !($model = $arguments[0]) instanceof Transformable) {
            throw new BadMethodCallException('Invalid include requested on transformer');
        }

        $data = $this->resolveInclude($model, $relation);
        $transformer = $this->getRelatedTransformer($model, $relation);

        if ($data === null) {
            return null;
        }

        if ($data instanceof Collection) {
            return $this->collection($data, $transformer);
        }

        return $this->item($data, $transformer);
    }
}
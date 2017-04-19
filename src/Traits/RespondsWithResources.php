<?php

namespace jfadich\EloquentResources\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use jfadich\EloquentResources\Contracts\Transformable;
use jfadich\EloquentResources\Exceptions\MissingTransformerException;
use jfadich\EloquentResources\Transformer;
use League\Fractal\Manager as Fractal;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

trait RespondsWithResources
{
    use RespondsWithJson;

    /**
     * Fractal Manager to do the transformations
     *
     * @var Fractal
     */
    protected $fractal;

    /**
     * @var Request
     */
    protected $request;

    /**
     * Array of related objects that should be included in the request
     *
     * @var array
     */
    protected $includes = [];

    /**
     * @param Fractal $fractal
     * @param Request $request
     */
    public function bootRespondsWithResources(Fractal $fractal, Request $request)
    {
        $this->request = $request;
        $this->fractal = $fractal;
        $this->fractal->setRecursionLimit(config('transformers.parameters.includes.max'));
        $includesName = config('transformers.parameters.includes.name');

        if($request->has($includesName))
            $this->setIncludes($request->get($includesName));
    }

    /**
     * Take a model, transformer and generate a API response
     *
     * @param Transformable|Builder|Relation $item
     * @param Callable|array $callback
     * @param array $meta
     * @return Response
     * @throws MissingTransformerException
     */
    public function respondWithItem($item, $callback = null, $meta = [])
    {
        list($item, $callback, $meta) = $this->resolveQuery($item, $callback, $meta);

        // If a query builder instance is given set the eager loads and paginate the data.
        if ($item instanceof Builder || $item instanceof Relation) {
            $item = $item->firstOrFail();
        }

        if($callback === null) {
            throw new MissingTransformerException('Collection callback not provided.');
        }

        $resource = new Item($item, $callback);

        if (!empty($meta)) {
            foreach ($meta as $k => $v) {
                $resource->setMetaValue($k, $v);
            }
        }

        $data = $this->fractal->createData($resource);

        return $this->respondWithArray($data->toArray());
    }

    /**
     * Generates an API response from the given laravel collection and model transformer
     * If given a query, apply default constrains and execute it
     *
     * @param $collection
     * @param $callback
     * @param array $meta
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function respondWithCollection($collection, $callback = null, $meta = [])
    {
        list($collection, $callback, $meta) = $this->resolveQuery($collection, $callback, $meta);

        // If a query builder instance is given set the eager loads and paginate the data.
        if ($collection instanceof Builder || $collection instanceof Relation) {
            $collection = $collection->paginate($this->getCount());
        }

        $resource = new Collection($collection->all(), $callback);

        if (!empty($meta)) {
            foreach ($meta as $k => $v) {
                $resource->setMetaValue($k, $v);
            }
        }

        // Set the pagination details
        if ($collection instanceof LengthAwarePaginator) {
            $collection->appends($this->request->except('page'));

            $resource->setPaginator(new IlluminatePaginatorAdapter($collection));
        }

        $data = $this->fractal->createData($resource);

        return $this->respondWithArray($data->toArray());
    }

    protected function resolveQuery($resource, $callback = null, $meta = [])
    {
        // If a query builder instance is given set the eager loads and paginate the data.
        if ($resource instanceof Builder || $resource instanceof Relation) {
            // If the given $callback is not callable check for a meta array
            if(is_array($callback) && empty($meta)) {
                $meta = $callback;
            }

            $model = $resource->getModel();
        } else {
            $model = $resource;
        }

        if(!is_callable($callback)) {
            if(!$model instanceof Transformable)
                throw new MissingTransformerException('Provided model must be an instance of Transformable');

            $callback = $model->getTransformer();
        }

        if($callback instanceof Transformer) {
            $includes = $this->getIncludes($callback->getLazyIncludes());
        } else {
            $includes = $this->getIncludes();
        }

        $resource = $resource->with($this->getEagerLoad($includes));

        if($callback === null || (!is_callable($callback) && !$callback instanceof Transformer)) {
            throw new MissingTransformerException('Resource callback not provided.');
        }

        return [$resource, $callback, $meta];
    }

    /**
     * @param $item
     * @param array $meta
     * @return \Illuminate\Http\Response
     */
    public function respondCreated($item, $meta = [])
    {
        return $this->setStatusCode(Response::HTTP_CREATED)->respondWithItem($item, $meta);
    }

    /**
     * Get the per page count from the request, or the default
     *
     * @return int
     */
    public function getCount()
    {
        $config = config('transformers.parameters.count');

        return $this->request->get($config['name'], $config['default']);
    }

    /**
     * Parse the include string and save the desired includes.
     *
     * @param string $includes
     */
    public function setIncludes($includes = '')
    {
        $this->fractal->parseIncludes($includes);

        $this->includes = $this->fractal->getRequestedIncludes();
    }

    /**
     * Get the eagerload array. If sort/order params are provided apply them to the eagerload constraint
     *
     * @param array $includes
     * @return array
     */
    protected function getEagerLoad($includes = [])
    {
        $eager = [];

        foreach($includes as $include) {
            $order = $this->fractal->getIncludeParams($include);
            $order = $order['order'] ?? null;

            if (is_array($order) && count($order) === 2 && in_array($order[1], ['desc', 'asc'])) {
                $eager[$include] = function($query) use($order) {
                    return $query->orderBy($order[0], $order[1]);
                };
            } else {
                $eager[] = $include;
            }
        }

        return $eager;
    }

    /**
     * Get the array of requested includes
     *
     * @param array $except
     * @return array
     */
    public function getIncludes($except = [])
    {
        if(empty($except))
            return $this->includes;

        return array_except($this->includes, $except);
    }
}

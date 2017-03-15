<?php

namespace jfadich\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use jfadich\JsonResponder\Contracts\Transformable;
use jfadich\JsonResponder\Exceptions\MissingTransformerException;
use jfadich\JsonResponder\Traits\RespondsWithJson;
use jfadich\JsonResponder\Transformer;
use League\Fractal\Manager as Fractal;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

trait RespondsWithResources
{
    use RespondsWithJson;

    /**
     * @var Fractal
     */
    protected $fractal;

    protected $includes = [];

    /**
     * @param Fractal $fractal
     * @param Request $request
     */
    public function bootRespondsWithResources(Fractal $fractal, Request $request = null)
    {
        if($request !== null) {
            $this->request = $request;
            if($request->has('with'))
                $this->setIncludes($request->get('with'));
        }

        $this->fractal = $fractal;
    }

    /**
     * Take a model, transformer and generate a API response
     *
     * @param Transformable $item
     * @param array $meta
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithItem(Transformable $item, $meta = [])
    {
        $resource = new Item($item, $item->getTransformer());

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
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    protected function respondWithCollection($collection, $callback = null, $meta = [])
    {
        // If a query builder instance is given set the eager loads and paginate the data.
        if ($collection instanceof Builder || $collection instanceof Relation) {
            // If the given $callback is not callable check for a meta array
            if(!is_callable($callback)) {
                $meta = $callback === null ? [] : $callback;
            }

            $model = $collection->getModel();

            if(!$model instanceof Transformable)
                throw new MissingTransformerException('Provided model must be an instance of Transformable');

            /** @var Transformer $callback */
            $callback = $model->getTransformer();
            $collection = $collection->with($this->getIncludes($callback->getLazyIncludes()))->paginate($this->getCount());
        }

        if($callback === null) {
            throw new \Exception('Collection callback not provided.');
        }

        $resource = new Collection($collection->all(), $callback);

        if (!empty($meta)) {
            foreach ($meta as $k => $v) {
                $resource->setMetaValue($k, $v);
            }
        }

        // Set the pagination details
        if ($collection instanceof LengthAwarePaginator) {
            if(isset($this->request) && $this->request instanceof Request) {
                $collection->appends($this->request->except('page'));
            }

            $resource->setPaginator(new IlluminatePaginatorAdapter($collection));
        }

        $data = $this->fractal->createData($resource);

        return $this->respondWithArray($data->toArray());
    }

    /**
     * @param $item
     * @param array $meta
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondCreated($item, $meta = [])
    {
        return $this->setStatusCode(Response::HTTP_CREATED)->respondWithItem($item, $meta);
    }

    /**
     * Create the response for when a request fails validation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function buildFailedValidationResponse(Request $request, array $errors)
    {
        return $this->respondUnprocessableEntity('There was an error validating the request.', $errors);
    }

    /**
     * Get the per page count from the request, or the default
     *
     * @return int
     */
    protected function getCount()
    {
        $count = $this->defaultCount ?? 25;

        if(isset($this->request) && $this->request instanceof Request)
            return $this->request->get('limit', $count);

        return $count;
    }

    protected function setIncludes(array $includes = [])
    {
        $this->fractal->parseIncludes($includes);

        $this->includes = $this->fractal->getRequestedIncludes();;
    }

    protected function getIncludes($except = [])
    {
        if(empty($except))
            return $this->includes;

        return array_except($this->includes, $except);
    }
}

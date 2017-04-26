<?php

namespace jfadich\EloquentResources\Traits;

use jfadich\EloquentResources\Exceptions\MissingTransformerException;
use jfadich\EloquentResources\Contracts\Transformable;
use Illuminate\Database\Eloquent\Relations\Relation;
use jfadich\EloquentResources\ResourceManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

trait RespondsWithResources
{
    use RespondsWithJson;

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
        $data = app(ResourceManager::class)->resolveItemResource($item, $callback, $meta);

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
        $data = app(ResourceManager::class)->resolveCollectionResource($collection, $callback, $meta);

        return $this->respondWithArray($data->toArray());
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
}

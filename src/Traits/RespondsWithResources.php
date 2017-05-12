<?php

namespace jfadich\EloquentResources\Traits;

use jfadich\EloquentResources\Exceptions\MissingTransformerException;
use jfadich\EloquentResources\Contracts\Transformable;
use Illuminate\Database\Eloquent\Relations\Relation;
use jfadich\EloquentResources\ResourceManager;
use jfadich\EloquentResources\Transformer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

trait RespondsWithResources
{
    use RespondsWithJson;

    /**
     * Take a model, transformer and generate a API response
     *
     * @param Transformable|Builder|Relation $item
     * @param array $meta
     * @param Callable|Transformer|null $callback
     * @return Response
     * @throws MissingTransformerException
     */
    public function respondWithItem($item, $meta = [], $callback = null)
    {
        $data = app(ResourceManager::class)->buildItemResource($item, $meta, $callback);

        return $this->respondWithArray($data->toArray());
    }

    /**
     * Generates an API response from the given laravel collection and model transformer
     * If given a query, apply default constrains and execute it
     *
     * @param $collection
     * @param array $meta
     * @param Callable|Transformer|null $callback
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function respondWithCollection($collection, $meta = [], $callback = null)
    {
        $data = app(ResourceManager::class)->buildCollectionResource($collection, $meta, $callback);

        return $this->respondWithArray($data->toArray());
    }

    /**
     * @param $item
     * @param array $meta
     * @param Callable|Transformer|null $callback
     * @return \Illuminate\Http\Response
     */
    public function respondCreated($item,  $meta = [], $callback = null)
    {
        return $this->setStatusCode(Response::HTTP_CREATED)->respondWithItem($item, $meta, $callback);
    }
}

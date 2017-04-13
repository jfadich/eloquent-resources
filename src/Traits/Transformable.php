<?php

namespace jfadich\EloquentResources\Traits;

use jfadich\EloquentResources\TransformationManager;

/**
 * Trait to facilitate model transformations.
 */
trait Transformable
{
    /**
     * Generate a url friendly name for the model type.
     *
     * @return string
     */
    public function getResourceType()
    {
        return app(TransformationManager::class)->getResourceTypeFromClass(get_class($this));
    }

    /**
     * Get an instance of the transformer for this model
     *
     * @return mixed
     */
    public function getTransformer()
    {
        return app(TransformationManager::class)->getTransformer($this);
    }

    /**
     * Get a model record by it's type and id
     *
     * @param $type
     * @param $id
     * @return mixed
     */
    public function getByResourceType($type, $id)
    {
        $model = app(TransformationManager::class)->getClassFromResourceType($type);
        $model = new $model();

        return $model::where($model->getKeyName(), $id)->firstOrFail();
    }
}
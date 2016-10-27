<?php

namespace jfadich\JsonResponder\Traits;

use jfadich\JsonResponder\TransformationManager;

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

    public function getTransformer()
    {
        return app(TransformationManager::class)->getTransformer($this);
    }
}
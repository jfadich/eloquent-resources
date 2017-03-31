<?php

namespace jfadich\JsonResponder\Contracts;

interface Transformable
{
    /**
     * Generate a url friendly name for the model type
     *
     * @return string
     */
    public function getResourceType();

    /**
     * Get an instance of the transformer for this model
     *
     * @return mixed
     */
    public function getTransformer();

    /**
     * Get a model record by it's type and id
     *
     * @param $type
     * @param $id
     * @return mixed
     */
    public function getByResourceType($type, $id);
}
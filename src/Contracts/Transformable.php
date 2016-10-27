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

    public function getByResourceType($type, $id);
}
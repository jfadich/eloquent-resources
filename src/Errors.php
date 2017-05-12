<?php

namespace jfadich\EloquentResources;

use ReflectionClass;

class Errors
{
    // Authentication Errors
    const UNAUTHORIZED = 100;
    const NO_TOKEN_PRESENT = 101;
    const TOKEN_EXPIRED = 102;
    const INVALID_TOKEN = 103;
    const FORBIDDEN = 104;

    // Resource Errors
    const INVALID_ENTITY = 201;
    const ENTITY_NOT_FOUND = 202;
    const DUPLICATE_ENTITY = 203;
    const INVALID_RELATIONSHIP = 204;
    const INVALID_RESOURCE_TYPE = 205;

    /**
     * Get an associative array containing all defined error codes.
     *
     * @return array
     */
    public static function all()
    {
        $reflection = new ReflectionClass(Errors::class);
        return $reflection->getConstants();
    }
}
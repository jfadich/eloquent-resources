<?php

namespace jfadich\EloquentResources;

use jfadich\EloquentResources\Contracts\Transformable as TransformableContract;
use jfadich\EloquentResources\Contracts\Presentable as PresentableContract;
use jfadich\EloquentResources\Traits\Transformable;
use jfadich\EloquentResources\Traits\Presentable;
use Illuminate\Database\Eloquent\Model;

/**
 * Base transformable model. Can be used to replace the Eloquent base.
 * Serves as an example of implementing this packages traits.
 *
 * @package jfadich\EloquentResources
 */
class TransformableModel extends Model implements TransformableContract, PresentableContract
{
    use Transformable, Presentable;
}
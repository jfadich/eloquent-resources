<?php

namespace jfadich\EloquentResources\Http;

use Illuminate\Routing\Controller as BaseController;
use jfadich\EloquentResources\Traits\RespondsWithResources;
use League\Fractal\Manager as Fractal;
use Illuminate\Http\Request;


class ResourceController extends BaseController
{
    use RespondsWithResources;

    /**
     * Boot the RespondsWithResources trait
     *
     * @param Fractal $fractal
     * @param Request $request
     */
    public function __construct(Fractal $fractal, Request $request)
    {
        $this->bootRespondsWithResources($fractal, $request);
    }
}
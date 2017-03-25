<?php

namespace jfadich\JsonResponder\Http;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use jfadich\JsonResponder\Traits\RespondsWithResources;
use League\Fractal\Manager as Fractal;
use Illuminate\Http\Request;


class ResourceController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, RespondsWithResources;

    public function __construct(Fractal $fractal, Request $request)
    {
        $this->bootRespondsWithResources($fractal, $request);
    }
}
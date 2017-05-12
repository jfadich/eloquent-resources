<?php

namespace jfadich\EloquentResources\Http;

use jfadich\EloquentResources\Traits\RespondsWithResources;
use Illuminate\Routing\Controller as BaseController;

class ResourceController extends BaseController
{
    use RespondsWithResources;
}
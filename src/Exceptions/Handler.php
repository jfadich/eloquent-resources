<?php

namespace jfadich\EloquentResources\Exceptions;

use jfadich\EloquentResources\Traits\HandlesExceptions;
use Symfony\Component\Debug\ExceptionHandler;
use Exception;

class Handler extends ExceptionHandler
{
    use HandlesExceptions;

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        return $this->renderJsonExceptions($e);
    }

}
<?php

namespace jfadich\JsonResponder\Exceptions;

use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use jfadich\JsonResponder\Traits\RespondsWithJson;
use Symfony\Component\Debug\ExceptionHandler;
use Illuminate\Http\Response;
use Exception;

class Handler extends ExceptionHandler
{
    use RespondsWithJson;

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof ModelNotFoundException)
            return $this->respondNotFound('Resource not found');

        if($e instanceof NotFoundHttpException)
            return $this->respondNotFound('Page not found');

        if($e instanceof InvalidModelRelation)
            return $this->respondBadRequest($e->getMessage());

        if ( $e instanceof MethodNotAllowedHttpException ) {
            return $this->setStatusCode( Response::HTTP_METHOD_NOT_ALLOWED )->respondWithError( 'Method not Allowed' );
        }

        return parent::render($request, $e);
    }

}
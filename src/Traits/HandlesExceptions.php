<?php

namespace jfadich\EloquentResources\Traits;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException as SymfonyUnauthorized;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use jfadich\EloquentResources\Exceptions\InvalidModelRelationException;
use jfadich\EloquentResources\Exceptions\InvalidResourceTypeException;
use jfadich\EloquentResources\Exceptions\EloquentResourcesException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use jfadich\EloquentResources\ResourceManager;
use Illuminate\Auth\AuthenticationException;
use jfadich\EloquentResources\Errors;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Response;

trait HandlesExceptions
{
    use RespondsWithJson;

    /**
     * Generate the appropriate JSON response for the given exception
     *
     * @param \Exception $e
     * @return Response
     */
    public function renderJsonExceptions(\Exception $e)
    {
        if($e instanceof ValidationException) {
            return $this->respondUnprocessableEntity($e->getMessage(), $e->errors());
        }
        
        if($e instanceof AuthorizationException) {
            return $this->respondForbidden($e->getMessage());
        }
        
        if ($e instanceof ModelNotFoundException) {
            $this->setErrorCode(Errors::ENTITY_NOT_FOUND);

            if(!($model = $e->getModel()))
                return $this->respondNotFound('Resource not found');

            $resource = app(ResourceManager::class)->getResourceTypeFromClass($model) ?? $model;

            return $this->respondNotFound("No {$resource}s found");
        }

        if($e instanceof EloquentResourcesException) {
            if($e instanceof InvalidModelRelationException)
                return $this->setErrorCode( Errors::INVALID_RELATIONSHIP )->respondBadRequest($e->getMessage());
            if($e instanceof InvalidResourceTypeException)
                $this->setErrorCode( Errors::INVALID_RESOURCE_TYPE )->respondBadRequest($e->getMessage());

            return $this->respondInternalError($e->getMessage());
        }

        if ( $e instanceof MethodNotAllowedHttpException ) {
            return $this->setStatusCode( Response::HTTP_METHOD_NOT_ALLOWED )->respondWithError( 'Method not Allowed' );
        }

        if($e instanceof NotFoundHttpException)
            return $this->respondNotFound('Page not found');

        if($e instanceof UnauthorizedHttpException || $e instanceof SymfonyUnauthorized)
            return $this->setErrorCode( Errors::FORBIDDEN )->respondForbidden($e->getMessage());

        if($e instanceof AuthenticationException)
            return $this->setErrorCode( Errors::UNAUTHORIZED )->respondUnauthorized($e->getMessage());

        return $this->respondInternalError(App::environment('production') ? 'Something unexpected went wrong' :  $e->getMessage() );
    }
}

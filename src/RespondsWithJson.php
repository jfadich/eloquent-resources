<?php

namespace Jfadich\JsonResponder;

use Illuminate\Http\Response;

/**
 * Trait RespondsWithJson
 *
 * This trait adds the ability to easily return JSON responses from Controllers, Middleware or Exception Handlers
 */
trait RespondsWithJson
{
    /**
     * HTTP Status code
     *
     * @var int
     */
    protected $statusCode = 200;

    /**
     * Application Error Code
     *
     * @var int
     */
    protected $errorCode = -1;

    /**
     * @param array $array
     * @param array $headers
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondWithArray(array $array, array $headers = [])
    {
        return $this->makeResponse($array, $headers);
    }

    /**
     * @param $message
     * @return Response
     */
    public function respondWithError($message)
    {
        if ($this->statusCode === 200) {
            $this->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        return $this->makeResponse([
            'error' => [
                'message' => $message,
                'error_code' => $this->getErrorCode(),
                'http_status' => $this->getStatusCode()
            ]
        ]);
    }

    /**
     * @param string $message
     * @return Response
     */
    public function respondNotFound($message = 'Resource not found')
    {
        return $this->setStatusCode(Response::HTTP_NOT_FOUND)->respondWithError($message);
    }

    /**
     * @param string $message
     * @return Response
     */
    public function respondUnauthorized($message = 'You are not authorized')
    {
        return $this->setStatusCode(Response::HTTP_UNAUTHORIZED)->respondWithError($message);
    }

    /**
     * @param string $message
     * @return Response
     */
    public function respondForbidden($message = 'You are forbidden')
    {
        return $this->setStatusCode(Response::HTTP_FORBIDDEN)->respondWithError($message);
    }

    /**
     * @param string $message
     * @return Response
     */
    public function respondInternalError($message = 'Internal Error')
    {
        return $this->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)->respondWithError($message);
    }

    /**
     * @param string $message
     * @return Response
     */
    public function respondBadRequest($message = 'Bad Request')
    {
        return $this->setStatusCode(Response::HTTP_BAD_REQUEST)->respondWithError($message);
    }

    /**
     * @param string $message
     * @return Response
     */
    public function respondUnprocessableEntity($message = 'Incomplete or invalid entity')
    {
        return $this->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)->respondWithError($message);
    }

    /**
     * Get HTTP Status Code
     *
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Set HTTP StatusCode
     *
     * @param mixed $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Get current application error code
     *
     * @return mixed
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Set application error code
     *
     * @param int $code
     * @param bool $override
     * @return $this
     */
    public function setErrorCode($code, $override = true)
    {
        // If the error code has been set and override is disabled return now.
        if (!$override && $this->errorCode !== -1) {
            return $this;
        }

        $this->errorCode = $code;

        return $this;
    }

    /**
     * Generate a response object with the given data and set the content-type to json
     *
     * @param $data
     * @param array $headers
     * @return Response
     */
    protected function makeResponse($data, $headers = [])
    {
        return response($data, $this->getStatusCode(), $headers)->header('Content-Type', 'application/json');
    }
}
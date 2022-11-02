<?php

namespace App\Exceptions;

use Throwable;
use App\Traits\ApiResponse;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->renderable(function (NotFoundHttpException $exception, $request) {
            if ($request->is('api/*')) {
                return $this->notFoundHttpException();
            }
        });

        $this->renderable(function (AuthorizationException $exception, $request) {
            if ($request->is('api/*')) {
                return $this->authorizationException();
            }
        });

        $this->renderable(function (AuthenticationException $exception, $request) {
            if ($request->is('api/*')) {
                return $this->authenticationException($exception);
            }
        });

        $this->renderable(function (MethodNotAllowedHttpException $exception, $request) {
            if ($request->is('api/*')) {
                return $this->methodNotAllowedHttpException();
            }
        });

        $this->renderable(function (ModelNotFoundException $exception, $request) {
            if ($request->is('api/*')) {
                return $this->modelNotFoundException();
            }
        });

        $this->renderable(function (\Exception $exception, $request) {
            if ($request->is('api/*')) {
                return $this->validationException($exception, $request);
            }
        });
    }

    /**
     * @OA\Schema(
     *     schema="ValidationException",
     *     @OA\Property(property="error", type="object"),
     *     @OA\Property(property="code", type="number", example=422),
     * )
     */
    protected function validationException($exception, $request)
    {
        return $this->convertValidationExceptionToResponse($exception, $request);
    }

    /**
     * @OA\Schema(
     *     schema="AuthorizationException",
     *     @OA\Property(property="error", type="string", example="Unauthorized"),
     *     @OA\Property(property="code", type="number", example=403),
     * )
     */
    protected function authorizationException()
    {
        return $this->errorResponse(__("Unauthorized"), Response::HTTP_FORBIDDEN);
    }

    /**
     * @OA\Schema(
     *     schema="AuthenticationException",
     *     @OA\Property(property="error", type="string", example="Unauthenticated"),
     *     @OA\Property(property="code", type="number", example=401),
     * )
     */
    protected function authenticationException($exception)
    {
        return $this->errorResponse($exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @OA\Schema(
     *     schema="ModelNotFoundException",
     *     @OA\Property(property="error", type="string", example="Model not found"),
     *     @OA\Property(property="code", type="number", example=404),
     * )
     */
    protected function modelNotFoundException()
    {
        return $this->errorResponse(__("Model not found"), Response::HTTP_NOT_FOUND);
    }

    /**
     * @OA\Schema(
     *     schema="NotFoundHttpException",
     *     @OA\Property(property="error", type="string", example="Not found"),
     *     @OA\Property(property="code", type="number", example=404),
     * )
     */
    protected function notFoundHttpException()
    {
        return $this->errorResponse(__("Not found"), Response::HTTP_NOT_FOUND);
    }

    /**
     * @OA\Schema(
     *     schema="MethodNotAllowedHttpException",
     *     @OA\Property(property="error", type="string", example="Method not allowed"),
     *     @OA\Property(property="code", type="number", example=405),
     * )
     */
    protected function methodNotAllowedHttpException()
    {
        return $this->errorResponse(__("Method not allowed"), Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * @OA\Schema(
     *     schema="BadRequestException",
     *     @OA\Property(property="error", type="string", example="Some error description"),
     *     @OA\Property(property="code", type="number", example=400),
     * )
     */
    protected function badRequestException($exception)
    {
        $code = ($exception->getCode() !== 0) ? $exception->getCode() : Response::HTTP_BAD_REQUEST;

        return $this->errorResponse($exception->getMessage(), $code);
    }

    protected function convertValidationExceptionToResponse(ValidationException $exception, $request)
    {
        $errors = $exception->validator->errors()->getMessages();

        return $this->errorResponse($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}

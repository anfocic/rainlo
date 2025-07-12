<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class Controller
{
    /**
     * Return a successful response
     */
    protected function success(
        mixed $data = null,
        string $message = 'Operation completed successfully',
        int $statusCode = Response::HTTP_OK,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        $response['timestamp'] = now()->toISOString();

        return response()->json($response, $statusCode);
    }

    protected function successWithData(
        mixed $data,
        string $message = 'Data retrieved successfully',
        int $statusCode = Response::HTTP_OK,
        array $meta = []
    ): JsonResponse {
        return $this->success($data, $message, $statusCode, $meta);
    }

    protected function created(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return $this->success($data, $message, Response::HTTP_CREATED);
    }

    protected function updated(
        mixed $data = null,
        string $message = 'Resource updated successfully'
    ): JsonResponse {
        return $this->success($data, $message, Response::HTTP_OK);
    }

    protected function deleted(
        string $message = 'Resource deleted successfully'
    ): JsonResponse {
        return $this->success(null, $message, Response::HTTP_OK);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    protected function error(
        string $message = 'An error occurred',
        int $statusCode = ResponseAlias::HTTP_INTERNAL_SERVER_ERROR,
        mixed $errors = null,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        $response['timestamp'] = now()->toISOString();

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error response
     */
    protected function validationError(
        mixed $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->error($message, ResponseAlias::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * Return a not found error response
     */
    protected function notFound(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->error($message, ResponseAlias::HTTP_NOT_FOUND);
    }

    /**
     * Return an unauthorized error response
     */
    protected function unauthorized(
        string $message = 'Unauthorized access'
    ): JsonResponse {
        return $this->error($message, ResponseAlias::HTTP_UNAUTHORIZED);
    }

    /**
     * Return a forbidden error response
     */
    protected function forbidden(
        string $message = 'Access forbidden'
    ): JsonResponse {
        return $this->error($message, ResponseAlias::HTTP_FORBIDDEN);
    }

    /**
     * Return a bad request error response
     */
    protected function badRequest(
        string $message = 'Bad request',
        mixed $errors = null
    ): JsonResponse {
        return $this->error($message, ResponseAlias::HTTP_BAD_REQUEST, $errors);
    }

    protected function paginated(
        LengthAwarePaginator $paginator,
        string $message = 'Data retrieved successfully',
        array $meta = []
    ): JsonResponse {
        $paginationMeta = [
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
                'path' => $paginator->path(),
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
            ],
        ];

        $combinedMeta = array_merge($meta, $paginationMeta);

        return $this->successWithData($paginator->items(), $message, Response::HTTP_OK, $combinedMeta);
    }

    protected function collection(
        mixed $collection,
        string $message = 'Collection retrieved successfully',
        array $meta = []
    ): JsonResponse {
        $collectionMeta = [
            'count' => is_countable($collection) ? count($collection) : 0,
        ];

        $combinedMeta = array_merge($meta, $collectionMeta);

        return $this->successWithData($collection, $message, Response::HTTP_OK, $combinedMeta);
    }

    protected function customResponse(
        mixed $data = null,
        string $message = 'Operation completed',
        int $statusCode = Response::HTTP_OK,
        array $headers = [],
        array $meta = []
    ): JsonResponse {
        $response = $this->success($data, $message, $statusCode, $meta);

        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }

    protected function handleException(
        Throwable $exception,
        string $defaultMessage = 'An unexpected error occurred'
    ): JsonResponse {
        // Log the exception
        Log::error('Controller Exception: ' . $exception->getMessage(), [
            'exception' => $exception,
            'trace' => $exception->getTraceAsString(),
        ]);

        // Return appropriate response based on exception type
        if ($exception instanceof ModelNotFoundException) {
            return $this->notFound('Resource not found');
        }

        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return $this->validationError($exception->errors(), 'Validation failed');
        }

        if ($exception instanceof AuthenticationException) {
            return $this->unauthorized('Authentication required');
        }

        if ($exception instanceof AuthorizationException) {
            return $this->forbidden('Access denied');
        }

        // For production, don't expose internal error details
        $message = config('app.debug') ? $exception->getMessage() : $defaultMessage;

        return $this->error($message);
    }

    protected function executeWithErrorHandling(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    protected function getFilteredInput(array $allowedFields): array
    {
        return request()->only($allowedFields);
    }

    protected function withDebugInfo(JsonResponse $response, array $debugData = []): JsonResponse
    {
        if (config('app.debug') && !empty($debugData)) {
            $data = $response->getData(true);
            $data['debug'] = array_merge([
                'query_count' => DB::getQueryLog() ? count(DB::getQueryLog()) : 0,
                'memory_usage' => memory_get_usage(true),
                'execution_time' => microtime(true) - LARAVEL_START,
            ], $debugData);

            $response->setData($data);
        }

        return $response;
    }
}

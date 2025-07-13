<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

abstract class Controller
{

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

    protected function error(
        string $message = 'An error occurred',
        int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
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

    protected function validationError(
        mixed $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    protected function notFound(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->error($message, Response::HTTP_NOT_FOUND);
    }

    protected function unauthorized(
        string $message = 'Unauthorized access'
    ): JsonResponse {
        return $this->error($message, Response::HTTP_UNAUTHORIZED);
    }

    protected function forbidden(
        string $message = 'Access forbidden'
    ): JsonResponse {
        return $this->error($message, Response::HTTP_FORBIDDEN);
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
            return $this->notFound();
        }

        if ($exception instanceof ValidationException) {
            return $this->validationError($exception->errors());
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
}

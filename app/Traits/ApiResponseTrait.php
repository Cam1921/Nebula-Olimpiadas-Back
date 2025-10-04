<?php

namespace App\Traits;

trait ApiResponseTrait
{
    protected function successResponse(
        string $message,
        array $data = [],
        array $meta = [],
        int $code = 200,
        array $warnings = []
    ): array {
        return [
            'status' => 'success',
            'code' => $code,
            'message' => $message,
            'meta' => $meta,
            'data' => $data,
            'errors' => [],
            'warnings' => $warnings
        ];
    }

    protected function errorResponse(
        string $message,
        array $errors = [],
        int $code = 400,
        array $meta = []
    ): array {
        return [
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'meta' => $meta,
            'data' => [],
            'errors' => $errors,
            'warnings' => []
        ];
    }
}

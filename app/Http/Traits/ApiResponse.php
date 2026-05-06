<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Return a success response.
     */
    protected function success(mixed $data = null, ?string $message = null, int $code = 200, array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $data,
            'message' => $message,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a paginated success response.
     */
    protected function paginated($paginator, $resource, ?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $resource::collection($paginator->items()),
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'lastPage' => $paginator->lastPage(),
            ],
            'message' => $message,
        ]);
    }

    /**
     * Return an error response.
     */
    protected function error(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a not found response.
     */
    protected function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorized(string $message = 'Unauthorized.'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Get the currency code from request or default.
     */
    protected function getRequestCurrency(): string
    {
        $currency = request()->query('currency');
        if ($currency) {
            $exists = \App\Models\Currency::where('code', $currency)->where('is_active', true)->exists();
            if ($exists) {
                return $currency;
            }
        }
        return currency_code();
    }

    /**
     * Convert price from default currency to requested currency.
     */
    protected function convertPrice(float $amount, ?string $toCurrency = null): float
    {
        $toCurrency = $toCurrency ?? $this->getRequestCurrency();
        $defaultCode = currency_code();

        if ($toCurrency === $defaultCode) {
            return $amount;
        }

        try {
            return \App\Models\Currency::convert($amount, $defaultCode, $toCurrency);
        } catch (\Throwable) {
            return $amount;
        }
    }
}

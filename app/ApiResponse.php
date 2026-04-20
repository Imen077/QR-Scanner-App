<?php

namespace App;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    // succes response
    protected function succesResponse($data, $message = null, $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'Success',
            'message' => $message, 
            'data' => $data,
        ], $code);
    }

    // error response 
    protected function errorResponse($data, $message = null, $code = 400): JsonResponse
    {
        return response()->json([
            'status' => 'Error',
            'message' => $message, 
            'data' => null
        ], $code);
    }
}

<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success($message = 'Succès', $data = null, $status = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public static function error($message = 'Une erreur est survenue', $status = 500, $errors = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], $status);
    }

    public static function validation($errors)
    {
        return self::error('Données invalides', 422, $errors);
    }
}

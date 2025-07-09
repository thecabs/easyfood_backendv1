<?php
namespace App\Traits;

trait ApiResponseTrait
{
    protected function successResponse($data = null, $message = 'Succès', $status = 200)
    {
        if(is_array($data) && isset($data['data']) && is_array($data['data'])){
            return response()->json(array_merge([
                'status' => 'success',
                'message' => $message,
            ],$data),$status);
        }
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $status);
    }

    protected function errorResponse($message = 'Une erreur est survenue', $status = 500, $errors = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], $status);
    }

    protected function validationError($errors)
    {
        return $this->errorResponse('Données invalides', 422, $errors);
    }
}

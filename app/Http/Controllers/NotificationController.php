<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    use ApiResponseTrait;
    // toutes les notifications
    public function index(Request $request) {
        $user = $request->user();
        return response()->json([
            'status' => 'success',
            'data' => $user->notifications,
            'message' => 'Notifications récupérées avec succès!'
        ]);
    }

    // toutes les notifications lues
    public function getRead(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'status' => 'success',
            'data' => $user->readNotifications,

            'message' => 'Notifications récupérées avec succès!'
        ]);
    }

    // toutes les notifications non lues
    public function getUnRead(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'status' => 'success',
            'data' => $user->unreadNotifications,
            'message' => 'Notifications récupérées avec succès!'
        ]);
    }

    // marquer comme lue
    public function showNotification(Request $request, $id)
    {
        $user = $request->user();
        // Récupère la notification
        $notification = $user->notifications()->findOrFail($id);

        // Si elle n’est pas encore lue
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $notification->id,
                'read_at' => $notification->read_at
            ]
        ], 200);
    }

    // supprimer
    public function delete(Request $request, $id)
    {
        $user = $request->user();

        // Récupère la notification
        $notification = $user->notifications()->findOrFail($id);
        // Supprime la notification
        $notification->delete();
        echo json_encode($notification);

        return  $this->successResponse([
            'id' => $notification->id
        ],'Notification supprimée avec succès',200);
    }
}

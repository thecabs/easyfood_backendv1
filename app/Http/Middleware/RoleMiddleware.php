<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  array|string  $roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user(); // Récupère l'utilisateur authentifié

        // Vérifie si l'utilisateur est authentifié et a un rôle autorisé
        if (!$user || !in_array($user->role, $roles)) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        return $next($request); // Autorise la requête si la condition est remplie
    }
}

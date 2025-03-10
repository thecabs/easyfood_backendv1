<?php

use BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware\Authorize;

return [

    /*
     * Configuration du tableau de bord WebSockets
     */
    'dashboard' => [
        'port' => env('LARAVEL_WEBSOCKETS_PORT', 6001),
    ],

    /*
     * Configuration des applications WebSockets
     */
    'apps' => [
        [
            'id' => env('PUSHER_APP_ID', 'local'),
            'name' => env('APP_NAME', 'LaravelWebSockets'),
            'key' => env('PUSHER_APP_KEY', 'local'),
            'secret' => env('PUSHER_APP_SECRET', 'local'),
            'path' => env('PUSHER_APP_PATH', '/app'),
            'capacity' => null,
            'enable_client_messages' => true, // ✅ Permet aux clients d'envoyer des messages
            'enable_statistics' => true,
        ],
    ],

    /*
     * Gestion des applications WebSockets
     */
    'app_provider' => BeyondCode\LaravelWebSockets\Apps\ConfigAppProvider::class,

    /*
     * Liste des origines autorisées pour éviter les erreurs CORS
     */
    'allowed_origins' => [
        'http://localhost:8000',  // ✅ Ajout d'une URL locale
        'http://127.0.0.1:8000',
        env('APP_URL', 'http://localhost'),
    ],

    /*
     * Taille maximale des requêtes WebSockets
     */
    'max_request_size_in_kb' => 250,

    /*
     * Chemin pour le WebSockets Dashboard
     */
    'path' => env('LARAVEL_WEBSOCKETS_PATH', 'websockets'),

    /*
     * Middleware des routes du Dashboard WebSockets
     */
    'middleware' => [
        'web',
        Authorize::class,
    ],

    /*
     * Configuration des statistiques WebSockets
     */
    'statistics' => [
        'model' => \BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry::class,
        'logger' => BeyondCode\LaravelWebSockets\Statistics\Logger\HttpStatisticsLogger::class,
        'interval_in_seconds' => 60,
        'delete_statistics_older_than_days' => 60,
        'perform_dns_lookup' => false,
    ],

    /*
     * Configuration SSL pour WebSockets
     */
    'ssl' => [
        'local_cert' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_CERT', null),
        'local_pk' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_PK', null),
        'passphrase' => env('LARAVEL_WEBSOCKETS_SSL_PASSPHRASE', null),
        'verify_peer' => false, // ✅ Ajouté pour éviter des erreurs SSL en local
    ],

    /*
     * Gestion des canaux WebSockets
     */
    'channel_manager' => \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManagers\ArrayChannelManager::class,
];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Ici vous pouvez configurer les paramètres CORS pour les requêtes HTTP
    | qui viennent d'origines différentes (frontend Nuxt sur le port 3000).
    |
    */

    'paths' => ['api/*', 'broadcasting/auth'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://localhost:3000', 'http://127.0.0.1:3000'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];

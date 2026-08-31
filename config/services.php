<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'telegram' => [
        // Token statis untuk mengamankan endpoint webhook (routes/api.php) --
        // dibagikan ke tim eksternal yang akan mengambil event dari sini.
        'webhook_api_token' => env('TELEGRAM_WEBHOOK_API_TOKEN'),
    ],

    'gis_cad' => [
        // Fitur "GIS to CAD Generator" (KML/KMZ -> DXF AutoCAD).
        // Butuh python3 + `pip3 install -r python-worker/requirements.txt`
        // (ezdxf untuk tulis DXF, pyproj untuk transformasi WGS84 -> UTM)
        // sudah terpasang di server. Lihat python-worker/README.md.
        'python_bin' => env('GIS_CAD_PYTHON_BIN', 'python3'),
        'worker_script' => base_path('python-worker/gis_to_dxf.py'),
        'timeout' => (int) env('GIS_CAD_TIMEOUT', 300),
    ],

];

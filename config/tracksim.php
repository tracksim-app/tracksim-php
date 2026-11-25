<?php

return [

    // API key from the API tab of https://tracksim.app/integrations.
    'api_key' => env('TRACKSIM_API_KEY'),

    // Signing secret shown when you create a webhook at https://tracksim.app/settings.
    'webhook_secret' => env('TRACKSIM_WEBHOOK_SECRET'),

    'base_url' => 'https://api.tracksim.app',

    // Seconds to wait for an API response.
    'timeout' => 30,

];

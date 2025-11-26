<?php

return [
    App\Providers\AppServiceProvider::class,
    // Only load Telescope in local environment when explicitly enabled
    ...( (env('APP_ENV') === 'local' && env('TELESCOPE_ENABLED', false))
        ? [App\Providers\TelescopeServiceProvider::class]
        : []
    ),
];

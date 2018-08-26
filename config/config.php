<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Starting Version
    |--------------------------------------------------------------------------
    |
    | This value determines the starting version of the application. If the
    | project is already version 3.0.0, set that here so we can continue
    | from there. This will only be used when doing the first deploy.
    |
    */

    'starting_version' => '0.0.0-dev',

    /*
    |--------------------------------------------------------------------------
    | Deployment Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the deployments that have already run for
    | your application. Using this information, we can determine which of
    | the deployments on disk haven't been run in the application yet.
    |
    */

    'table' => 'deployments',

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode
    |--------------------------------------------------------------------------
    |
    | This value determines whether your deployments should employ the artisan
    | down command (`php artisan down`). This value can either be a boolean
    | or a string. String values are used as the message for the command.
    |
    */

    'maintenance_mode' => 'We are currently down for maintenance and should be back shortly. We apologise for any inconvenience.',

    /*
    |--------------------------------------------------------------------------
    | Deployment Commands
    |--------------------------------------------------------------------------
    |
    | This array determines commands that should be performed during each
    | deployment cycle; after all deployment files have been executed.
    | Feature-specific commands should be added to that deployment.
    |
    */

    'commands' => [
        'git reset --hard',
        'git pull',
        'composer install',
        'yarn',
        'npm run ' . (config('env') === 'production' ? 'production' : 'development'),
    ],

];

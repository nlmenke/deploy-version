## Deploy Version
Automatically update application versions by deploying.

[![Total Downloads](https://poser.pugx.org/nlmenke/deploy-version/downloads)](https://packagist.org/packages/nlmenke/deploy-version)
[![Latest Stable Version](https://poser.pugx.org/nlmenke/deploy-version/v/stable)](https://packagist.org/packages/nlmenke/deploy-version)
[![Latest Unstable Version](https://poser.pugx.org/nlmenke/deploy-version/v/unstable)](https://packagist.org/packages/nlmenke/deploy-version)
[![License](https://poser.pugx.org/nlmenke/deploy-version/license)](https://github.com/nlmenke/deploy-version/blob/master/LICENSE.md)

This package works similar to migrations in that you will need to generate a deployment file for
each feature. Then, you can deploy all new features at once.

### Installation
This package can be installed through Composer:
```bash
composer require nlmenke/deploy-version
```

In Laravel 5.5 and above, the package will auto-register the service provider. In Laravel 5.4, you
must install the service provider manually:
```php
// config/app.php
'providers' => [
    ...
    NLMenke\DeployVersion\DeployVersionServiceProvider::class,
    ...
];
```

In Laravel 5.5 and above, the package will auto-register the facade. In Laravel 5.4, you must
install the facade manually:
```php
// config/app.php
'aliases' => [
    ...
    NLMenke\DeployVersion\DeployVersionFacade::class
    ...
];
```

Optionally, you can publish the config file of the package:
```bash
php artisan vendor:publish --provider="NLMenke\DeployVersion\DeployVersionServiceProvider"
```

The following config file will be published in `config/deploy-version.php`:
```php
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
        'git reset --hard HEAD',
        'git pull',
        'composer install',
        'yarn',
        'npm run ' . (config('env') === 'production' ? 'production' : 'development'),
    ],

];
```

### Generating Deployments
To create a deployment, use the `make:deployment` command:
```bash
php artisan make:deployment initial_deployment
```

The new deployment will be placed in a new directory (`deployments`) in the project's base folder.
Each deployment contains a timestamp which allows Laravel to determine the order of the
deployments.

The `--major`, `--minor`, and `--patch` options may also be used to indicate what the release type
will be. If no option is passed, it is assumed the deployment will be a patch. Only one release
type may be used per deployment: a major release will override the minor and patch options, while
a minor release will override a patch.

The `--pre` option will determine if the deployment is a pre-release (alpha, beta, rc, etc.):
```bash
php artisan make:deployment initial_deployment --major --pre=alpha.1
```

If the deployment should include migrations, add the `--migration` option. If multiple deployments
include the migration option when deploying, the migration command will only be run once at the end
of the deployment cycle.

#### Deployment Structure
A deployment class will contain a few variables and an optional `deploy` function. If no options
are given for the `make:deployment` command, a basic deployment class will be created:
```php
<?php

use NLMenke\DeployVersion\Deployments\Deployment;

class SomePatch extends Deployment
{
    /**
     * Patch versions include backwards-compatible bug fixes.
     *
     * Patch version Z (x.y.Z | x > 0) MUST be incremented if only backwards compatible bug fixes
     * are introduced. A bug fix is defined as an internal change that fixes incorrect behavior.
     *
     * A true value will result in the patch version being increased while major and minor versions
     * will remain unchanged (e.g.: 1.2.3 -> 1.2.4). We'll assume all deployments are a patch
     * unless stated otherwise.
     *
     * @var bool
     */
    protected $patch = true;

    /**
     * Release notes for the deployment.
     *
     * @var array
     */
    protected $releaseNotes = [
        // changelog
    ];

    /**
     * Additional deployment functionality.
     *
     * @return void
     */
    public function deploy()
    {
        // do other deployment stuff
    }
}
```

> **Notes**
>
> The patch var will always be included in the new deployment class and will default to true if
> removed. If your deployment will only be a pre-release update, set the value to false and include
> the `$preRelease` variable.

If any additional functionality is required during a single deployment, such as running a seeder or
sending an email to your users letting them know about the update changes, add it to the `deploy`
method; any commands that should be run at the end of _**all**_ deployments should be added to the
`commands` option in your config file.

> **Notes**
>
> Migrations will only run once during a deployment cycle and are run before the `deploy` method of
> the first pending deployment class, so if your deployment requires migrations to be run first,
> they should work as intended.
>
> Config commands will be run after all deployments have completed.

For the most part, the release notes variable can be structured in any way you desire, so long as
it remains, at it's core, an array. This variable will be JSON encoded before being inserted into
your database. Data will be returned as a multi-dimensional array - any formatting thereafter will
need to be handled by your application.

### Running Deployments
To run all of your outstanding deployments, execute the `deploy` Artisan command:
```bash
php artisan deploy
```

> **Notes**
> 
> `composer install` must be run prior to your first deployment due to this being a Composer
> package.

If you are using the `maintenance_mode` functionality (which calls the `down` Artisan command),
you may include the `--message` option to change the default maintenance message:
```bash
php artisan deploy --message="Patience! For the Jedi it is time to eat as well."
```

> **Notes**
>
> In order to display this message, you will need to update (or create, if it does not already
> exist) `resources/views/errors/503.blade.php` and include `$exception->getMessage()` wherever you
> want the message to be displayed.

#### Forcing Deployments to Run in Production
Deployments only go one-way - they do not have a rollback option. In order to protect you from
running a deployment against your production application, you will be prompted for confirmation
before the command is executed. To force the command to run without a prompt, use the `--force`
(`-f`) flag.
```bash
php artisan deploy --force
```

### Retrieving Current Version
To get the latest version, you will need to use the DeployVersion facade:
```php
DeployVersion::version();

// 2.1.0-dev
```

They can be returned as different lengths by either passing the length parameter or by calling the
methods manually (`version` calls the `release` method by default):
```php
DeployVersion::release();

// 2.1.0-dev

DeployVersion::version('short');
DeployVersion::short();

// v2.1.0-alpha+8752f75

DeployVersion::version('long');
DeployVersion::long();

// Version 2.1.0-dev <span>(build 8752f75)</span>
```

### Release Notes
Pulling release notes will also use the DeployVersion facade. This method will return an array with
the release's version as the key:
```php
DeployVersion::releaseNotes();

// [ '1.0.0 <span>(August 25, 2018)</span>' => [ /* notes for this release */ ], '1.0.0-beta <span>(August 23, 2018)</span>' => [ /* notes for this beta release */ ] ]
```

You can also pass `major` as the first parameter to only return notes for the latest major release;
`minor` will return notes for the latest minor release; `single` will only return notes for the
latest release. Any parameter passed will result in a similar array returned.

### Release Date
The latest release's date/time can be accessed by calling the date function. This will return a
Carbon object, so you may manipulate it how you wish (See
[Carbon](https://github.com/briannesbitt/carbon) for details):
```php
DeployVersion::date()->format('Ymd');

// 20180821
```

### Todo
* [ ] testing suite

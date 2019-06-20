<?php namespace NLMenke\DeployVersion;

use Illuminate\Support\ServiceProvider;

/**
 * Class DeployVersionServiceProvider
 *
 * @package NLMenke\DeployVersion
 * @author  Nick Menke <nick@nlmenke.net>
 */
class DeployVersionServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            //
        ];
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}

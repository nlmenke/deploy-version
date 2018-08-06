<?php namespace NLMenke\DeployVersion;

use Illuminate\Support\ServiceProvider;

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
        $this->bootConfig();
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            //
        ];
    }

    /**
     * Bootstrap config.
     *
     * @return void
     */
    protected function bootConfig(): void
    {
        $configPath = __DIR__ . '/../config/config.php';

        $this->publishes([
            $configPath => config_path('config.php')
        ], 'config');
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig(): void
    {
        $configPath = __DIR__ . '/../config/config.php';

        $this->mergeConfigFrom($configPath, 'deploy-version');
    }
}

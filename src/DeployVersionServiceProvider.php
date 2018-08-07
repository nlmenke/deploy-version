<?php namespace NLMenke\DeployVersion;

use Illuminate\Support\ServiceProvider;
use NLMenke\DeployVersion\Console\DeployMakeCommand;
use NLMenke\DeployVersion\Deployments\DeploymentCreator;

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
        $this->registerCreator();
        $this->registerCommands();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            DeploymentCreator::class,
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
     * Register the deployment commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        $this->commands([
            DeployMakeCommand::class,
        ]);
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

    /**
     * Register the deployment creator.
     *
     * @return void
     */
    protected function registerCreator(): void
    {
        $this->app->singleton(DeploymentCreator::class, function ($app) {
            return new DeploymentCreator($app['files']);
        });
    }
}

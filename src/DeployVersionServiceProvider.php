<?php

namespace NLMenke\DeployVersion;

use Illuminate\Support\ServiceProvider;
use NLMenke\DeployVersion\Console\DeployCommand;
use NLMenke\DeployVersion\Console\DeployMakeCommand;
use NLMenke\DeployVersion\Deployments\Deployer;
use NLMenke\DeployVersion\Deployments\DeploymentCreator;
use NLMenke\DeployVersion\Deployments\DeploymentRepository;

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
        $this->registerRepository();
        $this->registerDeployer();
        $this->registerCreator();
        $this->registerCommands();
        $this->registerFacade();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            Deployer::class,
            DeploymentCreator::class,
            DeploymentRepository::class,
            DeployVersionService::class,
        ];
    }

    /**
     * Bootstrap config.
     *
     * @return void
     */
    protected function bootConfig()
    {
        $configPath = __DIR__ . '/../config/config.php';

        $this->publishes([
            $configPath => config_path('deploy-version.php')
        ], 'config');
    }

    /**
     * Register the deployment commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands([
            DeployMakeCommand::class,
            DeployCommand::class,
        ]);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $configPath = __DIR__ . '/../config/config.php';

        $this->mergeConfigFrom($configPath, 'deploy-version');
    }

    /**
     * Register the deployment creator.
     *
     * @return void
     */
    protected function registerCreator()
    {
        $this->app->singleton(DeploymentCreator::class, function ($app) {
            return new DeploymentCreator($app['files']);
        });
    }

    /**
     * Register the deployer service.
     *
     * @return void
     */
    protected function registerDeployer()
    {
        $this->app->singleton(Deployer::class, function ($app) {
            $repository = $app[DeploymentRepository::class];

            return new Deployer($repository, $app['db'], $app['files']);
        });
    }

    /**
     * Register the facade.
     *
     * @return void
     */
    protected function registerFacade()
    {
        $this->app->singleton(DeployVersionService::class, function ($app) {
            $repository = $app[DeploymentRepository::class];

            return new DeployVersionService($repository);
        });

        $this->app->alias(DeployVersionService::class, 'deploy-version');
    }

    /**
     * Register the deployment repository service.
     *
     * #return void
     */
    protected function registerRepository()
    {
        $this->app->singleton(DeploymentRepository::class, function ($app) {
            $table = $app['config']['deploy-version.table'];

            return new DeploymentRepository($app['db'], $table);
        });
    }
}

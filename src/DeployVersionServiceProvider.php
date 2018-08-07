<?php namespace NLMenke\DeployVersion;

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
    public function boot(): void
    {
        $this->bootConfig();
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerConfig();
        $this->registerRepository();
        $this->registerDeployer();
        $this->registerCreator();
        $this->registerCommands();
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
            DeployCommand::class,
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

    /**
     * Register the deployer service.
     *
     * @return void
     */
    protected function registerDeployer(): void
    {
        $this->app->singleton(Deployer::class, function ($app) {
            $repository = $app[DeploymentRepository::class];

            return new Deployer($repository, $app['db'], $app['files']);
        });
    }

    /**
     * Register the deployment repository service.
     *
     * #return void
     */
    protected function registerRepository(): void
    {
        $this->app->singleton(DeploymentRepository::class, function ($app) {
            $table = $app['config']['deploy-version.table'];

            return new DeploymentRepository($app['db'], $table);
        });
    }
}

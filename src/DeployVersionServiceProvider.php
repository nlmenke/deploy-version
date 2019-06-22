<?php namespace NLMenke\DeployVersion;

use Illuminate\Support\ServiceProvider;
use NLMenke\DeployVersion\Console\DeployMakeCommand;

/**
 * Class DeployVersionServiceProvider
 *
 * @package NLMenke\DeployVersion
 * @author  Nick Menke <nick@nlmenke.net>
 */
class DeployVersionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
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
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
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
        ]);
    }
}

<?php

namespace NLMenke\DeployVersion\Console;

use Illuminate\Console\ConfirmableTrait;
use NLMenke\DeployVersion\Deployments\Deployer;
use NLMenke\DeployVersion\Deployments\DeploymentRepository;
use Symfony\Component\Console\Input\InputOption;

class DeployCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'deploy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the deployments';

    /**
     * The deployer instance.
     *
     * @var Deployer
     */
    protected $deployer;

    /**
     * The repository instance.
     *
     * @var DeploymentRepository
     */
    protected $repository;

    /**
     * DeployCommand constructor.
     *
     * @param Deployer             $deployer
     * @param DeploymentRepository $repository
     * @return void
     */
    public function __construct(Deployer $deployer, DeploymentRepository $repository)
    {
        parent::__construct();

        $this->deployer = $deployer;
        $this->repository = $repository;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['message', 'm', Inputoption::VALUE_OPTIONAL, 'The message for maintenance mode'],
            ['force', 'f', InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws \Throwable
     */
    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $this->startMaintenance();

        // get the version before deployment
        $preDeployVersion = \DeployVersion::release();

        if (!$this->deployer->repositoryExists()) {
            $this->repository->createRepository();
        }

        $this->deployer->run($this->getDeploymentPath());

        foreach ($this->deployer->getNotes() as $note) {
            $this->output->writeln($note);
        }

        // get the version after deployment
        $postDeployVersion = \DeployVersion::fresh()->release();

        $deploymentsRun = $this->deployer->getDeploymentsRun();

        if ($deploymentsRun > 0) {
            // display upgrade information
            $this->line('Deployments successful: <info>' . $deploymentsRun . '</info>');
            $this->line('Project updated from <comment>' . $preDeployVersion . '</comment> to <info>' . $postDeployVersion . '</info>');
        }

        $this->endMaintenance();
    }

    /**
     * Set the application to maintenance mode.
     *
     * @return void
     */
    protected function startMaintenance()
    {
        if (config('deploy-version.maintenance_mode')) {
            // bring the application down
            $message = $this->option('message');
            if ($message === null) {
                $message = config('deploy-version.maintenance_mode');
            }

            if ($message === true) {
                $artisanParams = [];
            } else {
                $artisanParams = [
                    '--message' => $message
                ];
            }

            \Artisan::call('down', $artisanParams, $this->output);
        }
    }

    /**
     * Resume application functionality.
     *
     * @return void
     */
    protected function endMaintenance()
    {
        if (config('deploy-version.maintenance_mode')) {
            // bring the application back up
            \Artisan::call('up', [], $this->output);
        }
    }
}

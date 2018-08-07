<?php namespace NLMenke\DeployVersion\Console;

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
    protected function getOptions()
    {
        return [
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

        if (!$this->deployer->repositoryExists()) {
            $this->repository->createRepository();
        }

        $this->deployer->run($this->getDeploymentPath());

        foreach ($this->deployer->getNotes() as $note) {
            $this->output->writeln($note);
        }
    }
}

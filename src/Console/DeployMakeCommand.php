<?php namespace NLMenke\DeployVersion\Console;

use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use NLMenke\DeployVersion\Deployments\DeploymentCreator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DeployMakeCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:deployment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new deployment file';

    /**
     * The deployment creator instance.
     */
    protected $creator;

    /**
     * The Composer instance.
     *
     * @var Composer
     */
    protected $composer;

    /**
     * Create a new deployment install command instance.
     *
     * @param DeploymentCreator $creator
     * @param Composer          $composer
     * @return void
     */
    public function __construct(DeploymentCreator $creator, Composer $composer)
    {
        parent::__construct();

        $this->creator = $creator;
        $this->composer = $composer;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::OPTIONAL, 'The name of the deployment/feature'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['major', '', InputOption::VALUE_NONE, 'Create a major release deployment'],
            ['minor', '', InputOption::VALUE_NONE, 'Create a minor release deployment'],
            ['patch', '', InputOption::VALUE_NONE, 'Create a patch release deployment'],
            ['pre', '', InputOption::VALUE_OPTIONAL, 'Deployment is a pre-release'],
            ['migrate', '', InputOption::VALUE_NONE, 'Deployment should migrate'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $name = Str::snake(trim($this->input->getArgument('name')));

        $this->writeDeployment($name);

        $this->composer->dumpAutoloads();
    }

    /**
     * Write the deployment file to disk.
     *
     * @param string $name
     * @return void
     * @throws \Exception
     */
    protected function writeDeployment(string $name)
    {
        $file = pathinfo($this->creator->create($name, $this->getDeploymentPath(), $this->input->getOptions()), PATHINFO_FILENAME);

        $this->line("<info>Created Deployments:</info> {$file}");
    }
}

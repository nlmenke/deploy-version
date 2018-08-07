<?php namespace NLMenke\DeployVersion\Console;

use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use NLMenke\DeployVersion\Deployments\DeploymentCreator;
use Symfony\Component\Console\Input\InputArgument;

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
    protected function getArguments()
    {
        return [
            ['name', InputArgument::OPTIONAL, 'The name of the deployment/feature']
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
    protected function writeDeployment($name)
    {
        $file = pathinfo($this->creator->create($name, $this->getDeploymentPath()), PATHINFO_FILENAME);

        $this->line("<info>Created Deployments:</info> {$file}");
    }
}
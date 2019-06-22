<?php namespace NLMenke\DeployVersion\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class DeployMakeCommand
 *
 * @package NLMenke\DeployVersion\Console
 * @author  Nick Menke <nick@nlmenke.net>
 */
class DeployMakeCommand extends Command
{
    /**
     * The console command name.
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
     * The composer instance.
     *
     * @var Composer
     */
    protected $composer;

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new console command instance.
     *
     * @param Composer   $composer
     * @param Filesystem $files
     * @return void
     */
    public function __construct(Composer $composer, Filesystem $files)
    {
        parent::__construct();

        $this->composer = $composer;
        $this->files = $files;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the deployment/feature'],
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
            ['major', '', InputOption::VALUE_NONE, 'The deployment is a major release'],
            ['minor', '', InputOption::VALUE_NONE, 'The deployment is a minor release'],
            ['patch', '', InputOption::VALUE_NONE, 'The deployment is a patch release'],
            ['pre', '', InputOption::VALUE_REQUIRED, 'The deployment is a pre-release'],
            ['migrate', '', InputOption::VALUE_NONE, 'The deployment requires migrations to be run'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function handle()
    {
        $name = Str::snake(trim($this->input->getArgument('name')));

        $this->ensureDeploymentDoesntAlreadyExist($name);

        $file = $this->buildFile($name);

        $this->saveFile($name, $file);

        $this->composer->dumpAutoloads();
    }

    /**
     * Build a new deployment file.
     *
     * @param string $name
     * @return string
     * @throws FileNotFoundException
     */
    private function buildFile(string $name): string
    {
        $stub = $this->getStub();
        $stub = $this->populateStub($name, $stub);

        return $stub;
    }

    /**
     * Ensure that a deployment with the given class name doesn't already exist.
     *
     * @param string $name
     * @return void
     */
    private function ensureDeploymentDoesntAlreadyExist(string $name)
    {
        $className = Str::studly($name);

        if (class_exists($className)) {
            throw new InvalidArgumentException("A {$className} class already exists.");
        }
    }

    /**
     * Get the path to the deployment directory.
     *
     * @return string
     */
    private function getDeploymentPath(): string
    {
        return $this->laravel->basePath() . DIRECTORY_SEPARATOR . 'deployments';
    }

    /**
     * Get the filename for the deployment.
     *
     * @param string $name
     * @return string
     */
    private function getFilename(string $name): string
    {
        return date('Y_m_d_His') . '_' . $name;
    }

    /**
     * Get the deployment stub file.
     *
     * @param string $stub
     * @return string
     * @throws FileNotFoundException
     */
    private function getStub(string $stub = 'deployment'): string
    {
        return $this->files->get(__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . "{$stub}.stub");
    }

    /**
     * Populate the place-holders in the deployment stub.
     *
     * @param string $name
     * @param string $stub
     * @return string
     * @throws FileNotFoundException
     */
    private function populateStub(string $name, string $stub): string
    {
        if ($this->input->getOption('major')) {
            // insert major release variable
            $stub = str_replace('{MAJOR_VARIABLE}', $this->getStub('major'), $stub);
        } elseif ($this->input->getOption('minor')) {
            // insert minor release variable
            $stub = str_replace('{MINOR_VARIABLE}', $this->getStub('minor'), $stub);
        } elseif ($this->input->getOption('patch')) {
            // insert patch release variable
            $stub = str_replace('{PATCH_VARIABLE}', $this->getStub('patch'), $stub);
        }

        if ($preReleaseValue = $this->input->getOption('pre')) {
            // insert pre-release variable, set to option value
            $stub = str_replace('{PRE_RELEASE_VARIABLE}', $this->getStub('pre'), $stub);
            $stub = str_replace('{PRE_RELEASE_VALUE}', $preReleaseValue, $stub);
        }

        if ($this->input->getOption('migrate')) {
            // insert migration variable
            $stub = str_replace('{MIGRATION_VARIABLE}', $this->getStub('migrate'), $stub);
        }

        // insert release notes variable, set to an empty array
        $stub = str_replace('{RELEASE_NOTES_VARIABLE}', $this->getStub('notes'), $stub);

        if (!$this->files->isDirectory($this->getDeploymentPath()) && $this->input->getOption('major')) {
            // set an initial release note
            $stub = str_replace('{RELEASE_NOTES_VALUE}', "'Initial Release',", $stub);
        } else {
            $stub = str_replace('{RELEASE_NOTES_VALUE}', '// changelog', $stub);
        }

        // remove remaining class variable placeholders
        $stub = str_replace([
            '{MAJOR_VARIABLE}',
            '{MINOR_VARIABLE}',
            '{PATCH_VARIABLE}',
            '{PRE_RELEASE_VARIABLE}',
            '{MIGRATE_VARIABLE}',
        ], '', $stub);

        // remove extra newlines
        $stub = preg_replace('/(\R){3,}/', PHP_EOL . PHP_EOL, $stub);
        $stub = preg_replace('/\{(\R){2,}/', '{' . PHP_EOL, $stub);
        $stub = preg_replace('/(\R){2,}\}/', PHP_EOL . '}', $stub);

        // set class name
        $stub = str_replace('DummyClass', Str::studly($name), $stub);

        return $stub;
    }

    /**
     * Write the deployment file to disk.
     *
     * @param string $name
     * @param string $file
     * @return void
     */
    private function saveFile(string $name, string $file)
    {
        $path = $this->getDeploymentPath();

        // create the deployments directory if it doesn't already exist
        if (!$this->files->isDirectory($path)) {
            $this->files->makeDirectory($path);
        }

        $filename = $this->getFilename($name);

        $this->files->put($path . DIRECTORY_SEPARATOR . $filename . '.php', $file);

        $this->line("<info>Created Deployment:</info> {$filename}");
    }
}

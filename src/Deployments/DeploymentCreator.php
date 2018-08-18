<?php namespace NLMenke\DeployVersion\Deployments;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class DeploymentCreator
{
    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new deployment creator instance.
     *
     * @param Filesystem $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Create a new deployment at the given path.
     *
     * @param string $name
     * @param string $path
     * @param array  $options
     * @return string
     * @throws \Exception
     */
    public function create(string $name, string $path, array $options): string
    {
        $this->ensureDeploymentDoesntAlreadyExist($name);

        $stub = $this->getStub();

        if (!$this->files->exists($path)) {
            $this->files->makeDirectory($path);
        }

        $this->files->put($path = $this->getPath($name, $path), $this->populateStub($name, $stub, $options));

        return $path;
    }

    /**
     * Ensure that a deployment with the given name doesn't already exist.
     *
     * @param string $name
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function ensureDeploymentDoesntAlreadyExist(string $name)
    {
        $className = $this->getClassName($name);

        if (class_exists($className)) {
            throw new \InvalidArgumentException("A {$className} class already exists.");
        }
    }

    /**
     * Get the deployment stub file.
     *
     * @param string $stub
     * @return string
     * @throws FileNotFoundException
     */
    protected function getStub(string $stub = 'deploy'): string
    {
        return $this->files->get($this->stubPath() . DIRECTORY_SEPARATOR . "{$stub}.stub");
    }

    /**
     * Populate the place-holders in the deployment stub.
     *
     * @param string $name
     * @param string $stub
     * @param array  $options
     * @return string
     * @throws FileNotFoundException
     */
    protected function populateStub(string $name, string $stub, array $options): string
    {
        $isPatch = "true";
        if ($options['major']) {
            // insert major release variable, set to true
            $stub = str_replace('{MAJOR_VAR}', $this->getStub('major'), $stub);
            $isPatch = "false";
        } elseif ($options['minor']) {
            // insert minor release variable, set to true
            $stub = str_replace('{MINOR_VAR}', $this->getStub('minor'), $stub);
            $isPatch = "false";
        }

        // patch is assumed true, set to false if not patch release
        $stub = str_replace('{PATCH_VALUE}', $isPatch, $stub);

        if ($options['pre']) {
            // insert pre-release variable, set to option value
            $stub = str_replace('{PRE_RELEASE_VAR}', $this->getStub('pre'), $stub);
            $stub = str_replace('{PRE_RELEASE_VALUE}', $options['pre'], $stub);
        }

        if ($options['migrate']) {
            // insert migration variable, set to true
            $stub = str_replace('{MIGRATE_VAR}', $this->getStub('migrate'), $stub);
        }

        // insert notes variable, set to empty array
        $stub = str_replace('{RELEASE_NOTES_VAR}', $this->getStub('notes'), $stub);

        // remove remaining variable placeholders
        $stub = str_replace([
            '{MAJOR_VAR}',
            '{MINOR_VAR}',
            '{PRE_RELEASE_VAR}',
            '{MIGRATE_VAR}',
        ], '', $stub);

        // remove extra newlines
        $stub = preg_replace('/(\R){3,}/', PHP_EOL . PHP_EOL, $stub);
        $stub = preg_replace('/\{(\R){2,}/', '{' . PHP_EOL, $stub);
        $stub = preg_replace('/(\R){2,}\}/', PHP_EOL . '}', $stub);

        // set class name
        $stub = str_replace('DummyClass', $this->getClassName($name), $stub);

        return $stub;
    }

    /**
     * Get the class name of a deployment name.
     *
     * @param string $name
     * @return string
     */
    protected function getClassName(string $name): string
    {
        return Str::studly($name);
    }

    /**
     * Get the full path to the deployment.
     *
     * @param string $name
     * @param string $path
     * @return string
     */
    protected function getPath(string $name, string $path): string
    {
        $filename = $this->getDatePrefix();
        if ($name !== '') {
            $filename .= '_' . $name;
        }

        return $path . DIRECTORY_SEPARATOR . $filename . '.php';
    }

    /**
     * Get the date prefix for the deployment.
     *
     * @return string
     */
    protected function getDatePrefix(): string
    {
        return date('Y_m_d_His');
    }

    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function stubPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'stubs';
    }

    /**
     * Get the filesystem instance.
     *
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        return $this->files;
    }
}

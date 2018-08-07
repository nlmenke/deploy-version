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
     * @return string
     * @throws \Exception
     */
    public function create($name, $path)
    {
        $this->ensureDeploymentDoesntAlreadyExist($name);

        $stub = $this->getStub();

        if (!$this->files->exists($path)) {
            $this->files->makeDirectory($path);
        }

        $this->files->put($path = $this->getPath($name, $path), $this->populateStub($name, $stub));

        return $path;
    }

    /**
     * Ensure that a deployment with the given name doesn't already exist.
     *
     * @param string $name
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function ensureDeploymentDoesntAlreadyExist($name)
    {
        $className = $this->getClassName($name);

        if (class_exists($className)) {
            throw new \InvalidArgumentException("A {$className} class already exists.");
        }
    }

    /**
     * Get the deployment stub file.
     *
     * @return string
     * @throws FileNotFoundException
     */
    protected function getStub()
    {
        return $this->files->get($this->stubPath() . '/blank.stub');
    }

    /**
     * Populate the place-holders in the deployment stub.
     *
     * @param string $name
     * @param string $stub
     * @return string
     */
    protected function populateStub($name, $stub)
    {
        $stub = str_replace('DummyClass', $this->getClassName($name), $stub);

        return $stub;
    }

    /**
     * Get the class name of a deployment name.
     *
     * @param string $name
     * @return string
     */
    protected function getClassName($name)
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
    protected function getPath($name, $path)
    {
        $filename = $this->getDatePrefix();
        if ($name !== '') {
            $filename .= '_' . $name;
        }

        return $path . '/' . $filename . '.php';
    }

    /**
     * Get the date prefix for the deployment.
     *
     * @return string
     */
    protected function getDatePrefix()
    {
        return date('Y_m_d_His');
    }

    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function stubPath()
    {
        return __DIR__ . '/stubs';
    }

    /**
     * Get the filesystem instance.
     *
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }
}

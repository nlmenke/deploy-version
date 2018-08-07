<?php namespace NLMenke\DeployVersion\Deployments;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Deployer
{
    /**
     * The deployment repository implementation.
     *
     * @var DeploymentRepository
     */
    protected $repository;

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * The connection resolver instance.
     *
     * @var ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The name of the default connection.
     *
     * @var string
     */
    protected $connection;

    /**
     * The notes for the current operation.
     *
     * @var array
     */
    protected $notes = [];

    /**
     * Create a new deployer instance.
     *
     * @param DeploymentRepository        $repository
     * @param ConnectionResolverInterface $resolver
     * @param Filesystem                  $files
     * @return void
     */
    public function __construct(DeploymentRepository $repository, ConnectionResolverInterface $resolver, Filesystem $files)
    {
        $this->repository = $repository;
        $this->resolver = $resolver;
        $this->files = $files;
    }

    /**
     * Run the pending deployments at a given path.
     *
     * @param string $path
     * @return array
     * @throws \Throwable
     */
    public function run($path = '')
    {
        $this->notes = [];

        $files = $this->getDeploymentFiles($path);

        $deployments = $this->pendingDeployments($files, $this->repository->getRan());

        $this->requireFiles($deployments);

        $this->runPending($deployments);

        return $deployments;
    }

    /**
     * Get the deployment files that have not yet run.
     *
     * @param array $files
     * @param array $ran
     * @return array
     */
    protected function pendingDeployments($files, $ran)
    {
        return Collection::make($files)
            ->reject(function ($file) use ($ran) {
                return in_array($this->getDeploymentName($file), $ran);
            })
            ->values()
            ->all();
    }

    /**
     * Run an array of deployments.
     *
     * @param array $deployments
     * @return void
     * @throws \Throwable
     */
    public function runPending(array $deployments)
    {
        if (count($deployments) === 0) {
            $this->note('<info>Nothing to deploy.</info>');

            return;
        }

        $batch = $this->repository->getNextBatchNumber();

        foreach ($deployments as $file) {
            $this->runDeploy($file, $batch);
        }
    }

    /**
     * Run "up" a migration instance.
     *
     * @param string $file
     * @param int    $batch
     * @return void
     * @throws \Throwable
     */
    protected function runDeploy($file, $batch)
    {
        $name = $this->getDeploymentName($file);

        $deployment = $this->resolve($name);

        $this->note("<comment>Deploying:</comment> {$name}");

        $this->runDeployment($deployment, 'deploy');

        $this->repository->log($name, $batch);

        $this->note("<info>Deployed:</info> {$name}");
    }

    /**
     * Run a deployment inside a transaction if the database supports it.
     *
     * @param object $deployment
     * @param string $method
     * @return void
     * @throws \Throwable
     */
    protected function runDeployment($deployment, $method)
    {
        $connection = $this->resolveConnection($deployment->getConnection());

        $callback = function () use ($deployment, $method) {
            if (method_exists($deployment, $method)) {
                $deployment->{$method}();
            }
        };

        if ($this->getSchemaGrammar($connection)->supportsSchemaTransactions() && $deployment->withinTransaction) {
            $connection->transaction($callback);
        } else {
            $callback();
        }
    }

    /**
     * Resolve a deployment instance from a file.
     *
     * @param string $file
     * @return object
     */
    public function resolve($file)
    {
        $class = Str::studly(implode('_', array_slice(explode('_', $file), 4)));

        return new $class;
    }

    /**
     * Get all of the deployment files in a given path.
     *
     * @param string|array $paths
     * @return array
     */
    public function getDeploymentFiles($paths)
    {
        return Collection::make($paths)->flatMap(function ($path) {
            return $this->files->glob($path . '/*_*.php');
        })->filter()->sortBy(function ($file) {
            return $this->getDeploymentName($file);
        })->values()->keyBy(function ($file) {
            return $this->getDeploymentName($file);
        })->all();
    }

    /**
     * Require in all the deployment files in a given path.
     *
     * @param array $files
     * @return void
     */
    public function requireFiles(array $files)
    {
        foreach ($files as $file) {
            $this->files->requireOnce($file);
        }
    }

    /**
     * Get the name of the deployment.
     *
     * @param string $path
     * @return string
     */
    public function getDeploymentName($path)
    {
        return str_replace('.php', '', basename($path));
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Set the default connection name.
     *
     * @param string $name
     * @return void
     */
    public function setConnection($name)
    {
        if (!is_null($name)) {
            $this->resolver->setDefaultConnection($name);
        }

        $this->repository->setSource($name);

        $this->connection = $name;
    }

    /**
     * Resolve the database connection instance.
     *
     * @param string $connection
     * @return Connection
     */
    public function resolveConnection($connection)
    {
        return $this->resolver->connection($connection ?: $this->connection);
    }

    /**
     * Get the schema grammar out of a deployment connection.
     *
     * @param Connection $connection
     * @return Grammar
     */
    protected function getSchemaGrammar($connection)
    {
        if (is_null($grammar = $connection->getSchemaGrammar())) {
            $connection->useDefaultSchemaGrammar();

            $grammar = $connection->getSchemaGrammar();
        }

        return $grammar;
    }

    /**
     * Get the deployment repository instance.
     *
     * @return DeploymentRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Determine if the deployment repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        return $this->repository->repositoryExists();
    }

    /**
     * Get the file system instance.
     *
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }

    /**
     * Raise a note event for the deployer.
     *
     * @param string $message
     * @return void
     */
    protected function note($message)
    {
        $this->notes[] = $message;
    }

    /**
     * Get the notes for the last operation.
     *
     * @return array
     */
    public function getNotes()
    {
        return $this->notes;
    }
}

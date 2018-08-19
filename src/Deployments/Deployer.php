<?php namespace NLMenke\DeployVersion\Deployments;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\ConsoleOutput;

class Deployer
{
    /**
     * The name of the default connection.
     *
     * @var string
     */
    protected $connection;

    /**
     * Number of deployments run.
     *
     * @var int
     */
    protected $deploymentsRun = 0;

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Determine if the deployment cycle has migrated.
     *
     * @var bool
     */
    protected $migrated = false;

    /**
     * The notes for the current operation.
     *
     * @var array
     */
    protected $notes = [];

    /**
     * The deployment repository implementation.
     *
     * @var DeploymentRepository
     */
    protected $repository;

    /**
     * The connection resolver instance.
     *
     * @var ConnectionResolverInterface
     */
    protected $resolver;

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
     * @param Deployment $deployment
     * @return string
     */
    protected function calculateNewVersion(Deployment $deployment): string
    {
        // use the starting_version config item as a default
        $oldVersion = config('deploy-version.starting_version');

        // pull the latest version from the deployments table
        $latestDeployment = $this->repository->getLatest();
        if ($latestDeployment !== null) {
            $oldVersion = $latestDeployment->version;
        }

        $versionArr = explode('.', $oldVersion);

        // ensure latestVersion was set properly
        $versionArr[0] = isset($versionArr[0]) ? (int)$versionArr[0] : 0;
        $versionArr[1] = isset($versionArr[1]) ? (int)$versionArr[1] : 0;
        $versionArr[2] = isset($versionArr[2]) ? (int)$versionArr[2] : 0;

        // each deployment may only have a major, minor, OR patch and increment by 1
        if ($deployment->isMajor()) {
            $versionArr[0]++;

            // major release resets minor and patch
            $versionArr[1] = 0;
            $versionArr[2] = 0;
        } elseif ($deployment->isMinor()) {
            $versionArr[1]++;

            // minor release resets patch
            $versionArr[2] = 0;
        } elseif ($deployment->isPatch()) {
            $versionArr[2]++;
        }

        $newVersion = implode('.', $versionArr);

        return $newVersion;
    }

    /**
     * Get the version build.
     *
     * @return string
     */
    protected function getBuild(): string
    {
        return exec('git rev-parse --short HEAD');
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * Get all of the deployment files in a given path.
     *
     * @param string|array $paths
     * @return array
     */
    public function getDeploymentFiles($paths): array
    {
        return Collection::make($paths)->flatMap(function ($path) {
            return $this->files->glob($path . DIRECTORY_SEPARATOR . '*_*.php');
        })->filter()->sortBy(function ($file) {
            return $this->getDeploymentName($file);
        })->values()->keyBy(function ($file) {
            return $this->getDeploymentName($file);
        })->all();
    }

    /**
     * Get the name of the deployment.
     *
     * @param string $path
     * @return string
     */
    public function getDeploymentName(string $path): string
    {
        return str_replace('.php', '', basename($path));
    }

    /**
     * Get the number of deployments run.
     *
     * @return int
     */
    public function getDeploymentsRun(): int
    {
        return $this->deploymentsRun;
    }

    /**
     * Get the file system instance.
     *
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        return $this->files;
    }

    /**
     * Get the notes for the last operation.
     *
     * @return array
     */
    public function getNotes(): array
    {
        return $this->notes;
    }

    /**
     * Get the deployment repository instance.
     *
     * @return DeploymentRepository
     */
    public function getRepository(): DeploymentRepository
    {
        return $this->repository;
    }

    /**
     * Get the schema grammar out of a deployment connection.
     *
     * @param Connection $connection
     * @return Grammar
     */
    protected function getSchemaGrammar(Connection $connection): Grammar
    {
        if (is_null($grammar = $connection->getSchemaGrammar())) {
            $connection->useDefaultSchemaGrammar();

            $grammar = $connection->getSchemaGrammar();
        }

        return $grammar;
    }

    /**
     * Raise a note event for the deployer.
     *
     * @param string $message
     * @return void
     */
    protected function note(string $message)
    {
        $this->notes[] = $message;
    }

    /**
     * Get the deployment files that have not yet run.
     *
     * @param array $files
     * @param array $ran
     * @return array
     */
    protected function pendingDeployments(array $files, array $ran): array
    {
        return Collection::make($files)
            ->reject(function ($file) use ($ran) {
                return in_array($this->getDeploymentName($file), $ran);
            })
            ->values()
            ->all();
    }

    /**
     * Determine if the deployment repository exists.
     *
     * @return bool
     */
    public function repositoryExists(): bool
    {
        return $this->repository->repositoryExists();
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
     * Resolve a deployment instance from a file.
     *
     * @param string $file
     * @return Deployment
     */
    public function resolve(string $file): Deployment
    {
        $class = Str::studly(implode('_', array_slice(explode('_', $file), 4)));

        return new $class;
    }

    /**
     * Resolve the database connection instance.
     *
     * @param string $connection
     * @return Connection
     */
    public function resolveConnection(string $connection): Connection
    {
        return $this->resolver->connection($connection ?: $this->connection);
    }

    /**
     * Run the pending deployments at a given path.
     *
     * @param string $path
     * @return array
     * @throws \Throwable
     */
    public function run(string $path = ''): array
    {
        $this->notes = [];

        $files = $this->getDeploymentFiles($path);

        $deployments = $this->pendingDeployments($files, $this->repository->getRan());

        $this->requireFiles($deployments);

        $this->runPending($deployments);

        return $deployments;
    }

    /**
     * Run the deployment.
     *
     * @param string $file
     * @return void
     * @throws \Throwable
     */
    protected function runDeployment(string $file)
    {
        $name = $this->getDeploymentName($file);

        $deployment = $this->resolve($name);

        $this->note("<comment>Deploying:</comment> {$name}");

        if ($deployment->hasMigrations() && !$this->migrated) {
            $output = new ConsoleOutput;

            \Artisan::call('migrate', [], $output);

            $this->migrated = true;
        }

        if (method_exists($deployment, 'deploy')) {
            $deployment->deploy();
        }

        $version = $this->calculateNewVersion($deployment);
        $preRelease = $deployment->isPreRelease() !== false ? $deployment->isPreRelease() : null;
        $build = $this->getBuild();
        $releaseNotes = $deployment->getReleaseNotes();

        $this->repository->log($name, $version, $preRelease, $build, $releaseNotes);

        $this->deploymentsRun++;

        $this->note("<info>Deployed:</info> {$name}");
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
            // deployments have already been run
            $this->note('<info>Nothing to deploy.</info>');

            return;
        }

        // run each deployment
        foreach ($deployments as $file) {
            $this->runDeployment($file);
        }

        $output = new ConsoleOutput;

        // clear cache and views
        \Artisan::call('cache:clear', [], $output);
        \Artisan::call('view:clear', [], $output);

        // execute commands from config
        foreach (config('deploy-version.commands') as $command) {
            // prevent git commands from resetting development
            if (!\App::isLocal() || strpos($command, 'git') === false) {
                exec($command, $output);

                (new ConsoleOutput)->write($output, true);
            }
        }
    }

    /**
     * Set the default connection name.
     *
     * @param string $name
     * @return void
     */
    public function setConnection(string $name)
    {
        if (!is_null($name)) {
            $this->resolver->setDefaultConnection($name);
        }

        $this->repository->setSource($name);

        $this->connection = $name;
    }
}

<?php namespace NLMenke\DeployVersion\Deployments;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Output\ConsoleOutput;

class DeploymentRepository
{
    /**
     * The database connection resolver instance.
     *
     * @var ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The name of the migration table.
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the database connection to use.
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new database deployment repository instance.
     *
     * @param ConnectionResolverInterface $resolver
     * @param string                      $table
     * @return void
     */
    public function __construct(ConnectionResolverInterface $resolver, $table)
    {
        $this->resolver = $resolver;
        $this->table = $table;
    }

    /**
     * Get the last deployment.
     *
     * @return Collection|null
     */
    public function getLatest()
    {
        return $this->table()
            ->orderBy('version', 'desc')
            ->orderBy('deployment', 'desc')
            ->get()
            ->first();
    }

    /**
     * Get the completed deployments.
     *
     * @return array
     */
    public function getRan(): array
    {
        return $this->table()
            ->orderBy('version', 'desc')
            ->orderBy('deployment', 'asc')
            ->pluck('deployment')
            ->all();
    }

    /**
     * Log that a deployment was run.
     *
     * @param string      $file
     * @param string      $version
     * @param string|null $preRelease
     * @param string|null $build
     * @param array       $releaseNotes
     * @return void
     */
    public function log(string $file, string $version = '', string $preRelease = null, string $build = null, array $releaseNotes = [])
    {
        $record = [
            'deployment' => $file,
            'version' => $version,
            'pre_release' => $preRelease,
            'build' => $build,
            'release_notes' => json_encode($releaseNotes),
        ];

        $this->table()
            ->insert($record);
    }

    /**
     * Create the deployment repository data store.
     *
     * @return void
     */
    public function createRepository()
    {
        $schema = $this->getConnection()
            ->getSchemaBuilder();

        $schema->create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->string('deployment');
            $table->string('version');
            $table->string('pre_release')->nullable();
            $table->string('build')->nullable();
            $table->text('release_notes');
        });

        (new ConsoleOutput)->write('<info>Deployment table created successfully.</info>', true);
    }

    /**
     * Determine if the deployment repository exists.
     *
     * @return bool
     */
    public function repositoryExists(): bool
    {
        $schema = $this->getConnection()
            ->getSchemaBuilder();

        return $schema->hasTable($this->table);
    }

    /**
     * Get a query builder for the deployment table.
     *
     * @return Builder
     */
    protected function table(): Builder
    {
        return $this->getConnection()
            ->table($this->table)
            ->useWritePdo();
    }

    /**
     * Get the connection resolver instance.
     *
     * @return ConnectionResolverInterface
     */
    public function getConnectionResolver(): ConnectionResolverInterface
    {
        return $this->resolver;
    }

    /**
     * Resolve the database connection instance.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->resolver->connection($this->connection);
    }

    /**
     * Set the information source to gather data.
     *
     * @param string $name
     * @return void
     */
    public function setSource($name)
    {
        $this->connection = $name;
    }
}

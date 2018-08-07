<?php namespace NLMenke\DeployVersion\Deployments;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;

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
     * Get teh completed deployments.
     *
     * @return array
     */
    public function getRan()
    {
        return $this->table()
            ->orderBy('batch', 'asc')
            ->orderBy('deployment', 'asc')
            ->pluck('deployment')
            ->all();
    }

    /**
     * Get list of deployments.
     *
     * @param int $steps
     * @return array
     */
    public function getDeployments($steps)
    {
        $query = $this->table()
            ->where('batch', '>=', '1');

        return $query->orderBy('batch', 'desc')
            ->orderBy('deployment', 'desc')
            ->take($steps)
            ->get()
            ->all();
    }

    /**
     * Get the last deployment batch.
     *
     * @return array
     */
    public function getLast()
    {
        $query = $this->table()
            ->where('batch', $this->getLastBatchNumber());

        return $query->orderBy('deployment', 'desc')
            ->get()
            ->all();
    }

    /**
     * Get the completed deployments with their batch numbers.
     *
     * @return array
     */
    public function getDeploymentBatches()
    {
        return $this->table()
            ->orderBy('batch', 'asc')
            ->orderBy('deployment', 'asc')
            ->pluck('batch', 'deployment')
            ->all();
    }

    /**
     * Log that a deployment was run.
     *
     * @param string $file
     * @param int    $batch
     * @return void
     */
    public function log($file, $batch)
    {
        $record = [
            'deployment' => $file,
            'batch' => $batch,
        ];

        $this->table()
            ->insert($record);
    }

    /**
     * Get the next deployment batch number.
     *
     * @return int
     */
    public function getNextBatchNumber()
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Get the last deployment batch number.
     *
     * return int
     */
    public function getLastBatchNumber()
    {
        return $this->table()
            ->max('batch');
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
            $table->integer('batch');
        });
    }

    /**
     * Determine if the deployment repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
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
    protected function table()
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
    public function getConnectionResolver()
    {
        return $this->resolver;
    }

    /**
     * Resolve the database connection instance.
     *
     * @return Connection
     */
    public function getConnection()
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

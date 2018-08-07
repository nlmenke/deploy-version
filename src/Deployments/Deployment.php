<?php namespace NLMenke\DeployVersion\Deployments;

abstract class Deployment
{
    /**
     * The name of the database connection to use.
     *
     * @var string
     */
    protected $connection;

    /**
     * Enables, if supported, wrapping the deployment within a transaction.
     *
     * @var bool
     */
    protected $withinTransaction = true;

    /**
     * Run the deployments.
     *
     * @return void
     */
    public function deploy()
    {
        //
    }

    /**
     * Get the deployment connection name.
     *
     * @return string
     */
    public function getConnection()
    {
        return $this->connection;
    }
}

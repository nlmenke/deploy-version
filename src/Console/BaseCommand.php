<?php

namespace NLMenke\DeployVersion\Console;

use Illuminate\Console\Command;

class BaseCommand extends Command
{
    /**
     * Get the path to the deployment directory.
     *
     * @return string
     */
    protected function getDeploymentPath(): string
    {
        return $this->laravel->basePath() . DIRECTORY_SEPARATOR . 'deployments';
    }
}

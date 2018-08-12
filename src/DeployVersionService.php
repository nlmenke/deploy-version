<?php namespace NLMenke\DeployVersion;

use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use NLMenke\DeployVersion\Deployments\DeploymentRepository;

class DeployVersionService
{
    /**
     * The Laravel application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * The deployment repository instance.
     *
     * @var DeploymentRepository
     */
    protected $repository;

    /**
     * Create a new instance.
     *
     * @param DeploymentRepository $repository
     */
    public function __construct(DeploymentRepository $repository)
    {
        $this->app = app();
        $this->repository = $repository;
    }

    protected function getLatestDeployment()
    {
        if (!$this->repository->repositoryExists() || ($latestDeployment = $this->repository->getLatest()) === null) {
            $latestDeployment = [
                'version' => config('deploy-version.starting_version'),
                'pre_release' => '',
                'build' => substr(sha1(get_class($this)), 0, 7),
                'release_notes' => json_encode([]),
            ];
        }

        return (new Collection($latestDeployment))
            ->except('id', 'deployment');
    }

    public function version($method = 'full')
    {
        if ($method == 'short') {
            return $this->short();
        } elseif ($method == 'long') {
            return $this->long();
        }

        return $this->full();
    }

    public function full()
    {
        $latestDeployment = $this->getLatestDeployment();
        return 'v' . $latestDeployment['version']
            . ($latestDeployment['pre_release'] ? '-' . $latestDeployment['pre_release'] : '')
            . '+' . $latestDeployment['build'];
    }

    public function long()
    {
        $latestDeployment = $this->getLatestDeployment();
        return 'Version ' . $latestDeployment['version']
            . ($latestDeployment['pre_release'] ? '-' . $latestDeployment['pre_release'] : '')
            . ' <span>(build ' . $latestDeployment['build'] . ')</span>';
    }

    public function short()
    {
        $latestDeployment = $this->getLatestDeployment();
        return 'v' . $latestDeployment['version']
            . ($latestDeployment['pre_release'] ? '-' . $latestDeployment['pre_release'] : '');
    }
}

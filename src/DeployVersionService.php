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
     * @return void
     */
    public function __construct(DeploymentRepository $repository)
    {
        $this->app = app();
        $this->repository = $repository;
    }

    /**
     * Pull the latest deployment information.
     *
     * @return Collection
     */
    private function getLatestDeployment(): Collection
    {
        if (!$this->repository->repositoryExists() || ($latestDeployment = $this->repository->getLatest()) === null) {
            // default to staring version information
            $default = explode('-', config('deploy-version.starting_version'));

            $version = reset($default);
            $preRelease = (end($default) != $version) ? end($default) : null;

            $latestDeployment = [
                'version' => $version,
                'pre_release' => $preRelease,
                'build' => substr(sha1(get_class($this)), 0, 7),
                'release_notes' => json_encode([]),
            ];
        }

        return (new Collection($latestDeployment))
            ->except('id', 'deployment');
    }

    /**
     * Get the full version string.
     *
     * @example v2.1.0-alpha+8752f75
     * @return string
     */
    public function full(): string
    {
        $latestDeployment = $this->getLatestDeployment();

        return 'v' . $latestDeployment['version']
            . ($latestDeployment['pre_release'] ? '-' . $latestDeployment['pre_release'] : '')
            . '+' . $latestDeployment['build'];
    }

    /**
     * Get the long version string.
     *
     * @example Version 2.1.0-alpha (build 8752f75)
     * @return string
     */
    public function long(): string
    {
        $latestDeployment = $this->getLatestDeployment();

        return 'Version ' . $latestDeployment['version']
            . ($latestDeployment['pre_release'] ? '-' . $latestDeployment['pre_release'] : '')
            . ' <span>(build ' . $latestDeployment['build'] . ')</span>';
    }

    /**
     * Get the short version string.
     *
     * @example v2.1.0-alpha
     * @return string
     */
    public function short(): string
    {
        $latestDeployment = $this->getLatestDeployment();

        return 'v' . $latestDeployment['version']
            . ($latestDeployment['pre_release'] ? '-' . $latestDeployment['pre_release'] : '');
    }

    /**
     * Get the latest version.
     *
     * @param string|null $method
     * @return string
     */
    public function version(string $method = null): string
    {
        if ($method == 'long') {
            return $this->long();
        } elseif ($method == 'short') {
            return $this->short();
        }

        return $this->full();
    }
}

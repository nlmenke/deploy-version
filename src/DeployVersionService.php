<?php namespace NLMenke\DeployVersion;

use Carbon\Carbon;
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
     * Get the latest version's release date.
     *
     * @return Carbon
     */
    public function date(): Carbon
    {
        $latestDeployment = $this->getLatestDeployment();

        return Carbon::parse($latestDeployment['deployed_at']);
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
                'deployed_at' => Carbon::now(),
            ];
        }

        return (new Collection($latestDeployment))
            ->except('id', 'deployment');
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
     * Get the version's release string.
     *
     * @example 2.1.0-alpha
     * @return string
     */
    public function release(): string
    {
        $latestDeployment = $this->getLatestDeployment();

        return $latestDeployment['version']
            . ($latestDeployment['pre_release'] ? '-' . $latestDeployment['pre_release'] : '');
    }

    /**
     * Get release notes for deployments.
     *
     * @param string $level
     * @return array
     */
    public function releaseNotes($level = 'all'): array
    {
        if (!$this->repository->repositoryExists() || ($deployments = $this->repository->getAll()) === null) {
            // default to an empty array
            $deployments = [];
        }

        $deployments = (new Collection($deployments))->map(function ($deployment) {
            return (new Collection($deployment))->except('id', 'deployment');
        });

        if ($level == 'major' || $level == 'minor') {
            // all notes from latest major release
            $current = $deployments->first();
            $currentVersionArr = explode('.', $current['version']);

            $deployments = $deployments->filter(function (Collection $deployment) use ($currentVersionArr) {
                $versionArr = explode('.', $deployment['version']);
                $major = reset($versionArr);

                $currentMajor = reset($currentVersionArr);

                return $major === $currentMajor;
            });

            if ($level == 'minor') {
                // all notes from latest minor release
                $deployments = $deployments->filter(function (Collection $deployment) use ($currentVersionArr) {
                    $versionArr = explode('.', $deployment['version']);
                    $minor = $versionArr[1];

                    $currentMinor = $currentVersionArr[1];

                    return $minor === $currentMinor;
                });
            }
        } elseif ($level == 'single') {
            // only the latest release
            $deployments = [
                $deployments->first()
            ];
        }

        $notes = (new Collection($deployments))->reduce(function ($notes, Collection $deployment) {
            $notes[$deployment['version'] . ($deployment['pre_release'] ? '-' . $deployment['pre_release'] : '')] = json_decode($deployment['release_notes']);

            return $notes;
        });

        return $notes;
    }

    /**
     * Get the short version string.
     *
     * @example v2.1.0-alpha+8752f75
     * @return string
     */
    public function short(): string
    {
        $latestDeployment = $this->getLatestDeployment();

        return 'v' . $latestDeployment['version']
            . ($latestDeployment['pre_release'] ? '-' . $latestDeployment['pre_release'] : '')
            . '+' . $latestDeployment['build'];
    }

    /**
     * Get the latest version.
     *
     * @param string $length
     * @return string
     */
    public function version(string $length = 'full'): string
    {
        if ($length == 'long') {
            return $this->long();
        } elseif ($length == 'short') {
            return $this->short();
        }

        return $this->release();
    }
}

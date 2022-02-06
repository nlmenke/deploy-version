<?php

namespace NLMenke\DeployVersion;

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
     * List of deployments.
     *
     * @var Collection
     */
    protected $deployments;

    /**
     * The latest deployment.
     *
     * @var Collection
     */
    protected $latest;

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

        $this->deployments = $this->getDeployments();
        $this->latest = $this->deployments->first();
    }

    /**
     * Get the latest version's release date.
     *
     * @return Carbon
     */
    public function date(): Carbon
    {
        return $this->latest['deployed_at'];
    }

    /**
     * Get deployment information.
     *
     * @return Collection
     */
    private function getDeployments(): Collection
    {
        if (!$this->repository->repositoryExists() || empty($deployments = $this->repository->getAll())) {
            // default to single release with starting version information
            $default = explode('-', config('deploy-version.starting_version'));

            $version = reset($default);
            $preRelease = (end($default) != $version) ? end($default) : null;

            $deployments[] = (object)[
                'version' => $version,
                'pre_release' => $preRelease,
                'build' => substr(sha1(get_class($this)), 0, 7),
                'release_notes' => json_encode([]),
                'deployed_at' => Carbon::now(),
            ];
        }

        $deployments = (new Collection($deployments))->map(function ($deployment) {
            $deployment->deployed_at = Carbon::parse($deployment->deployed_at);

            return (new Collection($deployment))
                ->except('id', 'deployment');
        });

        return $deployments;
    }

    /**
     * Reset latest deployment when multiple calls occur in the same deployment cycle.
     *
     * @return $this
     */
    public function fresh()
    {
        $this->deployments = $this->getDeployments();
        $this->latest = $this->deployments->first();

        return $this;
    }

    /**
     * Get the long version string.
     *
     * @example Version 2.1.0-alpha (build 8752f75)
     * @return string
     */
    public function long(): string
    {
        return 'Version ' . $this->release()
            . ' <span>(build ' . $this->latest['build'] . ')</span>';
    }

    /**
     * Get the version's release string.
     *
     * @example 2.1.0-alpha
     * @return string
     */
    public function release(): string
    {
        return $this->latest['version']
            . ($this->latest['pre_release'] ? '-' . $this->latest['pre_release'] : '');
    }

    /**
     * Get release notes for deployments.
     *
     * @param string $level
     * @return array
     */
    public function releaseNotes($level = 'all'): array
    {
        $deployments = $this->deployments;

        if ($level == 'major' || $level == 'minor') {
            // only latest major release
            $currentVersionArr = explode('.', $this->latest['version']);

            $deployments = $deployments->filter(function (Collection $deployment) use ($currentVersionArr) {
                $versionArr = explode('.', $deployment['version']);
                $major = reset($versionArr);

                $currentMajor = reset($currentVersionArr);

                return $major === $currentMajor;
            });

            if ($level == 'minor') {
                // only latest minor release
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
                $this->latest
            ];
        }

        $notes = (new Collection($deployments))->reduce(function ($notes, Collection $deployment) {
            $release = $this->release() . ' <span>(' . $this->date()->format('F d, Y ') . ')</span>';

            $notes[$release] = json_decode($deployment['release_notes']);

            return $notes;
        });

        return $notes ?? [];
    }

    /**
     * Get the short version string.
     *
     * @example v2.1.0-alpha+8752f75
     * @return string
     */
    public function short(): string
    {
        return 'v' . $this->release()
            . '+' . $this->latest['build'];
    }

    /**
     * Get the latest version.
     *
     * @param string $length
     * @return string
     */
    public function version(string $length = 'release'): string
    {
        if ($length == 'long') {
            return $this->long();
        } elseif ($length == 'short') {
            return $this->short();
        }

        return $this->release();
    }
}

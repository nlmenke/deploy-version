<?php namespace NLMenke\DeployVersion\Deployments;

use Symfony\Component\Console\Output\ConsoleOutput;

abstract class Deployment
{
    /**
     * Major versions include incompatible API changes.
     *
     * Major version X (X.y.z | X > 0) MUST be incremented if any backwards incompatible changes
     * are introduced to the public API. It MAY include minor and patch level changes. Patch and
     * minor version MUST be reset to 0 when major version is incremented.
     *
     * The first version you deploy on your production server should have this set to true in at
     * least 1 deployment. If this is true, minor and patch versions will be reset to 0 and the
     * major version will be increased (e.g.: 1.2.3 -> 2.0.0).
     *
     * @var bool
     */
    protected $major = false;

    /**
     * Minor versions add functionality in a backwards-compatible manner.
     *
     * Minor version Y (x.Y.z | x > 0) MUST be incremented if new, backwards compatible
     * functionality is introduced to the public API. It MUST be incremented if any public API
     * functionality is marked as deprecated. It MAY be incremented if substantial new
     * functionality or improvements are introduced within the private code. It MAY include patch
     * level changes. Patch version MUST be reset to 0 when minor version is incremented.
     *
     * If this is set to true, the patch version will be reset to 0 and the minor version will be
     * increased while the major version remains unchanged (e.g.: 1.2.3 -> 1.3.0).
     *
     * @var bool
     */
    protected $minor = false;

    /**
     * Patch versions include backwards-compatible bug fixes.
     *
     * Patch version Z (x.y.Z | x > 0) MUST be incremented if only backwards compatible bug fixes
     * are introduced. A bug fix is defined as an internal change that fixes incorrect behavior.
     *
     * A true value will result in the patch version being increased while major and minor versions
     * will remain unchanged (e.g.: 1.2.3 -> 1.2.4). We'll assume all deployments are a patch
     * unless stated otherwise.
     *
     * @var bool
     */
    protected $patch = true;

    /**
     * Pre-releases are not yet ready for a full release.
     *
     * A pre-release version MAY be denoted by appending a hyphen and a series of dot separated
     * identifiers immediately following the patch version. Identifiers MUST comprise only ASCII
     * alphanumerics and hyphen [0-9A-Za-z-]. Identifiers MUST NOT be empty. Numeric identifiers
     * MUST NOT include leading zeroes. Pre-release versions have a lower precedence than the
     * associated normal version. A pre-release version indicates that the version is unstable and
     * might not satisfy the intended compatibility requirements as denoted by its associated
     * normal version. (examples: 1.0.0-alpha, 1.0.0-alpha.1, 1.0.0-0.3.7, 1.0.0-x.7.z.92)
     *
     * If you set a value in your deployment file, it will only used for that specific deployment.
     * Please note the hyphen (-) should not be applied to the beginning of this string.
     *
     * @var string|false
     */
    protected $preRelease = false;

    /**
     * Build hash.
     *
     * Build metadata MAY be denoted by appending a plus sign and a series of dot separated
     * identifiers immediately following the patch or pre-release version. Identifiers MUST
     * comprise only ASCII alphanumerics and hyphen [0-9A-Za-z-]. Identifiers MUST NOT be empty.
     * Build metadata SHOULD be ignored when determining version precedence. Thus two versions that
     * differ only in the build metadata, have the same precedence. (examples: 1.0.0-alpha+001,
     * 1.0.0+20130313144700, 1.0.0-beta+exp.sha.5114f85)
     *
     * For this package, we'll be pulling the abbreviated hash from the latest commit to your git
     * repository. Any value set will be overwritten.
     *
     * @var string
     */
    protected $build;

    /**
     * Deployment includes migrations.
     *
     * If the feature requires migrations to be run, this will ensure they are run during the
     * deployment process.
     *
     * @var bool
     */
    protected $migrate = false;

    /**
     * Release notes for the deployment.
     *
     * Any release notes that should be added for the new feature. Arrays will be JSON encoded
     * while strings will be input as written to the deployment database table.
     *
     * @var array
     */
    protected $releaseNotes = [];

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * Create a new deployment instance.
     */
    public function __construct()
    {
        $this->output = new ConsoleOutput;
    }

    /**
     * Deployment is a major release.
     *
     * @return bool
     */
    public function isMajor()
    {
        return $this->major;
    }

    /**
     * Deployment is a minor release.
     *
     * @return bool
     */
    public function isMinor()
    {
        return $this->minor;
    }

    /**
     * Deployment is a patch release.
     *
     * @return bool
     */
    public function isPatch()
    {
        return $this->patch;
    }

    /**
     * Deployment is a pre-release.
     *
     * @return false|string
     */
    public function isPreRelease()
    {
        return $this->preRelease;
    }

    /**
     * Deployment contains migrations.
     *
     * @return bool
     */
    public function hasMigrations()
    {
        return $this->migrate;
    }

    /**
     * Deployment release notes.
     *
     * @return array
     */
    public function getReleaseNotes()
    {
        return $this->releaseNotes;
    }
}

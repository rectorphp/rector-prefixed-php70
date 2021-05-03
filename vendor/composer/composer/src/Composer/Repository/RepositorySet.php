<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace RectorPrefix20210503\Composer\Repository;

use RectorPrefix20210503\Composer\DependencyResolver\Pool;
use RectorPrefix20210503\Composer\DependencyResolver\PoolBuilder;
use RectorPrefix20210503\Composer\DependencyResolver\Request;
use RectorPrefix20210503\Composer\EventDispatcher\EventDispatcher;
use RectorPrefix20210503\Composer\IO\IOInterface;
use RectorPrefix20210503\Composer\IO\NullIO;
use RectorPrefix20210503\Composer\Package\BasePackage;
use RectorPrefix20210503\Composer\Package\AliasPackage;
use RectorPrefix20210503\Composer\Semver\Constraint\ConstraintInterface;
use RectorPrefix20210503\Composer\Package\Version\StabilityFilter;
/**
 * @author Nils Adermann <naderman@naderman.de>
 */
class RepositorySet
{
    /**
     * Packages are returned even though their stability does not match the required stability
     */
    const ALLOW_UNACCEPTABLE_STABILITIES = 1;
    /**
     * Packages will be looked up in all repositories, even after they have been found in a higher prio one
     */
    const ALLOW_SHADOWED_REPOSITORIES = 2;
    /**
     * @var array[]
     * @psalm-var array<string, array<string, array{alias: string, alias_normalized: string}>>
     */
    private $rootAliases;
    /**
     * @var string[]
     * @psalm-var array<string, string>
     */
    private $rootReferences;
    /** @var RepositoryInterface[] */
    private $repositories = array();
    /**
     * @var int[] array of stability => BasePackage::STABILITY_* value
     * @psalm-var array<string, int>
     */
    private $acceptableStabilities;
    /**
     * @var int[] array of package name => BasePackage::STABILITY_* value
     * @psalm-var array<string, int>
     */
    private $stabilityFlags;
    private $rootRequires;
    /** @var bool */
    private $locked = \false;
    /** @var bool */
    private $allowInstalledRepositories = \false;
    /**
     * In most cases if you are looking to use this class as a way to find packages from repositories
     * passing minimumStability is all you need to worry about. The rest is for advanced pool creation including
     * aliases, pinned references and other special cases.
     *
     * @param string $minimumStability
     * @param int[]  $stabilityFlags   an array of package name => BasePackage::STABILITY_* value
     * @psalm-param array<string, int> $stabilityFlags
     * @param array[] $rootAliases
     * @psalm-param list<array{package: string, version: string, alias: string, alias_normalized: string}> $rootAliases
     * @param string[] $rootReferences an array of package name => source reference
     * @psalm-param array<string, string> $rootReferences
     */
    public function __construct($minimumStability = 'stable', array $stabilityFlags = array(), array $rootAliases = array(), array $rootReferences = array(), array $rootRequires = array())
    {
        $this->rootAliases = self::getRootAliasesPerPackage($rootAliases);
        $this->rootReferences = $rootReferences;
        $this->acceptableStabilities = array();
        foreach (\RectorPrefix20210503\Composer\Package\BasePackage::$stabilities as $stability => $value) {
            if ($value <= \RectorPrefix20210503\Composer\Package\BasePackage::$stabilities[$minimumStability]) {
                $this->acceptableStabilities[$stability] = $value;
            }
        }
        $this->stabilityFlags = $stabilityFlags;
        $this->rootRequires = $rootRequires;
        foreach ($rootRequires as $name => $constraint) {
            if (\RectorPrefix20210503\Composer\Repository\PlatformRepository::isPlatformPackage($name)) {
                unset($this->rootRequires[$name]);
            }
        }
    }
    public function allowInstalledRepositories($allow = \true)
    {
        $this->allowInstalledRepositories = $allow;
    }
    public function getRootRequires()
    {
        return $this->rootRequires;
    }
    /**
     * Adds a repository to this repository set
     *
     * The first repos added have a higher priority. As soon as a package is found in any
     * repository the search for that package ends, and following repos will not be consulted.
     *
     * @param RepositoryInterface $repo A package repository
     */
    public function addRepository(\RectorPrefix20210503\Composer\Repository\RepositoryInterface $repo)
    {
        if ($this->locked) {
            throw new \RuntimeException("Pool has already been created from this repository set, it cannot be modified anymore.");
        }
        if ($repo instanceof \RectorPrefix20210503\Composer\Repository\CompositeRepository) {
            $repos = $repo->getRepositories();
        } else {
            $repos = array($repo);
        }
        foreach ($repos as $repo) {
            $this->repositories[] = $repo;
        }
    }
    /**
     * Find packages providing or matching a name and optionally meeting a constraint in all repositories
     *
     * Returned in the order of repositories, matching priority
     *
     * @param  string                   $name
     * @param  ConstraintInterface|null $constraint
     * @param  int                      $flags      any of the ALLOW_* constants from this class to tweak what is returned
     * @return array
     */
    public function findPackages($name, \RectorPrefix20210503\Composer\Semver\Constraint\ConstraintInterface $constraint = null, $flags = 0)
    {
        $ignoreStability = ($flags & self::ALLOW_UNACCEPTABLE_STABILITIES) !== 0;
        $loadFromAllRepos = ($flags & self::ALLOW_SHADOWED_REPOSITORIES) !== 0;
        $packages = array();
        if ($loadFromAllRepos) {
            foreach ($this->repositories as $repository) {
                $packages[] = $repository->findPackages($name, $constraint) ?: array();
            }
        } else {
            foreach ($this->repositories as $repository) {
                $result = $repository->loadPackages(array($name => $constraint), $ignoreStability ? \RectorPrefix20210503\Composer\Package\BasePackage::$stabilities : $this->acceptableStabilities, $ignoreStability ? array() : $this->stabilityFlags);
                $packages[] = $result['packages'];
                foreach ($result['namesFound'] as $nameFound) {
                    // avoid loading the same package again from other repositories once it has been found
                    if ($name === $nameFound) {
                        break 2;
                    }
                }
            }
        }
        $candidates = $packages ? \call_user_func_array('array_merge', $packages) : array();
        // when using loadPackages above (!$loadFromAllRepos) the repos already filter for stability so no need to do it again
        if ($ignoreStability || !$loadFromAllRepos) {
            return $candidates;
        }
        $result = array();
        foreach ($candidates as $candidate) {
            if ($this->isPackageAcceptable($candidate->getNames(), $candidate->getStability())) {
                $result[] = $candidate;
            }
        }
        return $result;
    }
    public function getProviders($packageName)
    {
        $providers = array();
        foreach ($this->repositories as $repository) {
            if ($repoProviders = $repository->getProviders($packageName)) {
                $providers = \array_merge($providers, $repoProviders);
            }
        }
        return $providers;
    }
    public function isPackageAcceptable($names, $stability)
    {
        return \RectorPrefix20210503\Composer\Package\Version\StabilityFilter::isPackageAcceptable($this->acceptableStabilities, $this->stabilityFlags, $names, $stability);
    }
    /**
     * Create a pool for dependency resolution from the packages in this repository set.
     *
     * @return Pool
     */
    public function createPool(\RectorPrefix20210503\Composer\DependencyResolver\Request $request, \RectorPrefix20210503\Composer\IO\IOInterface $io, \RectorPrefix20210503\Composer\EventDispatcher\EventDispatcher $eventDispatcher = null)
    {
        $poolBuilder = new \RectorPrefix20210503\Composer\DependencyResolver\PoolBuilder($this->acceptableStabilities, $this->stabilityFlags, $this->rootAliases, $this->rootReferences, $io, $eventDispatcher);
        foreach ($this->repositories as $repo) {
            if (($repo instanceof \RectorPrefix20210503\Composer\Repository\InstalledRepositoryInterface || $repo instanceof \RectorPrefix20210503\Composer\Repository\InstalledRepository) && !$this->allowInstalledRepositories) {
                throw new \LogicException('The pool can not accept packages from an installed repository');
            }
        }
        $this->locked = \true;
        return $poolBuilder->buildPool($this->repositories, $request);
    }
    /**
     * Create a pool for dependency resolution from the packages in this repository set.
     *
     * @return Pool
     */
    public function createPoolWithAllPackages()
    {
        foreach ($this->repositories as $repo) {
            if (($repo instanceof \RectorPrefix20210503\Composer\Repository\InstalledRepositoryInterface || $repo instanceof \RectorPrefix20210503\Composer\Repository\InstalledRepository) && !$this->allowInstalledRepositories) {
                throw new \LogicException('The pool can not accept packages from an installed repository');
            }
        }
        $this->locked = \true;
        $packages = array();
        foreach ($this->repositories as $repository) {
            foreach ($repository->getPackages() as $package) {
                $packages[] = $package;
                if (isset($this->rootAliases[$package->getName()][$package->getVersion()])) {
                    $alias = $this->rootAliases[$package->getName()][$package->getVersion()];
                    while ($package instanceof \RectorPrefix20210503\Composer\Package\AliasPackage) {
                        $package = $package->getAliasOf();
                    }
                    $aliasPackage = new \RectorPrefix20210503\Composer\Package\AliasPackage($package, $alias['alias_normalized'], $alias['alias']);
                    $aliasPackage->setRootPackageAlias(\true);
                    $packages[] = $aliasPackage;
                }
            }
        }
        return new \RectorPrefix20210503\Composer\DependencyResolver\Pool($packages);
    }
    // TODO unify this with above in some simpler version without "request"?
    public function createPoolForPackage($packageName, \RectorPrefix20210503\Composer\Repository\LockArrayRepository $lockedRepo = null)
    {
        return $this->createPoolForPackages(array($packageName), $lockedRepo);
    }
    public function createPoolForPackages($packageNames, \RectorPrefix20210503\Composer\Repository\LockArrayRepository $lockedRepo = null)
    {
        $request = new \RectorPrefix20210503\Composer\DependencyResolver\Request($lockedRepo);
        foreach ($packageNames as $packageName) {
            if (\RectorPrefix20210503\Composer\Repository\PlatformRepository::isPlatformPackage($packageName)) {
                throw new \LogicException('createPoolForPackage(s) can not be used for platform packages, as they are never loaded by the PoolBuilder which expects them to be fixed. Use createPoolWithAllPackages or pass in a proper request with the platform packages you need fixed in it.');
            }
            $request->requireName($packageName);
        }
        return $this->createPool($request, new \RectorPrefix20210503\Composer\IO\NullIO());
    }
    private static function getRootAliasesPerPackage(array $aliases)
    {
        $normalizedAliases = array();
        foreach ($aliases as $alias) {
            $normalizedAliases[$alias['package']][$alias['version']] = array('alias' => $alias['alias'], 'alias_normalized' => $alias['alias_normalized']);
        }
        return $normalizedAliases;
    }
}
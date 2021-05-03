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
namespace RectorPrefix20210503\Composer\Package\Loader;

use RectorPrefix20210503\Composer\Package\BasePackage;
use RectorPrefix20210503\Composer\Package\AliasPackage;
use RectorPrefix20210503\Composer\Config;
use RectorPrefix20210503\Composer\IO\IOInterface;
use RectorPrefix20210503\Composer\Package\RootPackageInterface;
use RectorPrefix20210503\Composer\Repository\RepositoryFactory;
use RectorPrefix20210503\Composer\Package\Version\VersionGuesser;
use RectorPrefix20210503\Composer\Package\Version\VersionParser;
use RectorPrefix20210503\Composer\Package\RootPackage;
use RectorPrefix20210503\Composer\Repository\RepositoryManager;
use RectorPrefix20210503\Composer\Util\ProcessExecutor;
/**
 * ArrayLoader built for the sole purpose of loading the root package
 *
 * Sets additional defaults and loads repositories
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class RootPackageLoader extends \RectorPrefix20210503\Composer\Package\Loader\ArrayLoader
{
    /**
     * @var RepositoryManager
     */
    private $manager;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var VersionGuesser
     */
    private $versionGuesser;
    public function __construct(\RectorPrefix20210503\Composer\Repository\RepositoryManager $manager, \RectorPrefix20210503\Composer\Config $config, \RectorPrefix20210503\Composer\Package\Version\VersionParser $parser = null, \RectorPrefix20210503\Composer\Package\Version\VersionGuesser $versionGuesser = null, \RectorPrefix20210503\Composer\IO\IOInterface $io = null)
    {
        parent::__construct($parser);
        $this->manager = $manager;
        $this->config = $config;
        $this->versionGuesser = $versionGuesser ?: new \RectorPrefix20210503\Composer\Package\Version\VersionGuesser($config, new \RectorPrefix20210503\Composer\Util\ProcessExecutor($io), $this->versionParser);
    }
    /**
     * @param  array                $config package data
     * @param  string               $class  FQCN to be instantiated
     * @param  string               $cwd    cwd of the root package to be used to guess the version if it is not provided
     * @return RootPackageInterface
     */
    public function load(array $config, $class = 'RectorPrefix20210503\\Composer\\Package\\RootPackage', $cwd = null)
    {
        if (!isset($config['name'])) {
            $config['name'] = '__root__';
        } elseif ($err = \RectorPrefix20210503\Composer\Package\Loader\ValidatingArrayLoader::hasPackageNamingError($config['name'])) {
            throw new \RuntimeException('Your package name ' . $err);
        }
        $autoVersioned = \false;
        if (!isset($config['version'])) {
            $commit = null;
            // override with env var if available
            if (\getenv('COMPOSER_ROOT_VERSION')) {
                $config['version'] = \getenv('COMPOSER_ROOT_VERSION');
            } else {
                $versionData = $this->versionGuesser->guessVersion($config, $cwd ?: \getcwd());
                if ($versionData) {
                    $config['version'] = $versionData['pretty_version'];
                    $config['version_normalized'] = $versionData['version'];
                    $commit = $versionData['commit'];
                }
            }
            if (!isset($config['version'])) {
                $config['version'] = '1.0.0';
                $autoVersioned = \true;
            }
            if ($commit) {
                $config['source'] = array('type' => '', 'url' => '', 'reference' => $commit);
                $config['dist'] = array('type' => '', 'url' => '', 'reference' => $commit);
            }
        }
        $realPackage = $package = parent::load($config, $class);
        if ($realPackage instanceof \RectorPrefix20210503\Composer\Package\AliasPackage) {
            $realPackage = $package->getAliasOf();
        }
        if ($autoVersioned) {
            $realPackage->replaceVersion($realPackage->getVersion(), \RectorPrefix20210503\Composer\Package\RootPackage::DEFAULT_PRETTY_VERSION);
        }
        if (isset($config['minimum-stability'])) {
            $realPackage->setMinimumStability(\RectorPrefix20210503\Composer\Package\Version\VersionParser::normalizeStability($config['minimum-stability']));
        }
        $aliases = array();
        $stabilityFlags = array();
        $references = array();
        foreach (array('require', 'require-dev') as $linkType) {
            if (isset($config[$linkType])) {
                $linkInfo = \RectorPrefix20210503\Composer\Package\BasePackage::$supportedLinkTypes[$linkType];
                $method = 'get' . \ucfirst($linkInfo['method']);
                $links = array();
                foreach ($realPackage->{$method}() as $link) {
                    $links[$link->getTarget()] = $link->getConstraint()->getPrettyString();
                }
                $aliases = $this->extractAliases($links, $aliases);
                $stabilityFlags = $this->extractStabilityFlags($links, $stabilityFlags, $realPackage->getMinimumStability());
                $references = $this->extractReferences($links, $references);
                if (isset($links[$config['name']])) {
                    throw new \RuntimeException(\sprintf('Root package \'%s\' cannot require itself in its composer.json' . \PHP_EOL . 'Did you accidentally name your root package after an external package?', $config['name']));
                }
            }
        }
        foreach (\array_keys(\RectorPrefix20210503\Composer\Package\BasePackage::$supportedLinkTypes) as $linkType) {
            if (isset($config[$linkType])) {
                foreach ($config[$linkType] as $linkName => $constraint) {
                    if ($err = \RectorPrefix20210503\Composer\Package\Loader\ValidatingArrayLoader::hasPackageNamingError($linkName, \true)) {
                        throw new \RuntimeException($linkType . '.' . $err);
                    }
                }
            }
        }
        $realPackage->setAliases($aliases);
        $realPackage->setStabilityFlags($stabilityFlags);
        $realPackage->setReferences($references);
        if (isset($config['prefer-stable'])) {
            $realPackage->setPreferStable((bool) $config['prefer-stable']);
        }
        if (isset($config['config'])) {
            $realPackage->setConfig($config['config']);
        }
        $repos = \RectorPrefix20210503\Composer\Repository\RepositoryFactory::defaultRepos(null, $this->config, $this->manager);
        foreach ($repos as $repo) {
            $this->manager->addRepository($repo);
        }
        $realPackage->setRepositories($this->config->getRepositories());
        return $package;
    }
    private function extractAliases(array $requires, array $aliases)
    {
        foreach ($requires as $reqName => $reqVersion) {
            if (\preg_match('{^([^,\\s#]+)(?:#[^ ]+)? +as +([^,\\s]+)$}', $reqVersion, $match)) {
                $aliases[] = array('package' => \strtolower($reqName), 'version' => $this->versionParser->normalize($match[1], $reqVersion), 'alias' => $match[2], 'alias_normalized' => $this->versionParser->normalize($match[2], $reqVersion));
            } elseif (\strpos($reqVersion, ' as ') !== \false) {
                throw new \UnexpectedValueException('Invalid alias definition in "' . $reqName . '": "' . $reqVersion . '". Aliases should be in the form "exact-version as other-exact-version".');
            }
        }
        return $aliases;
    }
    private function extractStabilityFlags(array $requires, array $stabilityFlags, $minimumStability)
    {
        $stabilities = \RectorPrefix20210503\Composer\Package\BasePackage::$stabilities;
        $minimumStability = $stabilities[$minimumStability];
        foreach ($requires as $reqName => $reqVersion) {
            $constraints = array();
            // extract all sub-constraints in case it is an OR/AND multi-constraint
            $orSplit = \preg_split('{\\s*\\|\\|?\\s*}', \trim($reqVersion));
            foreach ($orSplit as $orConstraint) {
                $andSplit = \preg_split('{(?<!^|as|[=>< ,]) *(?<!-)[, ](?!-) *(?!,|as|$)}', $orConstraint);
                foreach ($andSplit as $andConstraint) {
                    $constraints[] = $andConstraint;
                }
            }
            // parse explicit stability flags to the most unstable
            $match = \false;
            foreach ($constraints as $constraint) {
                if (\preg_match('{^[^@]*?@(' . \implode('|', \array_keys($stabilities)) . ')$}i', $constraint, $match)) {
                    $name = \strtolower($reqName);
                    $stability = $stabilities[\RectorPrefix20210503\Composer\Package\Version\VersionParser::normalizeStability($match[1])];
                    if (isset($stabilityFlags[$name]) && $stabilityFlags[$name] > $stability) {
                        continue;
                    }
                    $stabilityFlags[$name] = $stability;
                    $match = \true;
                }
            }
            if ($match) {
                continue;
            }
            foreach ($constraints as $constraint) {
                // infer flags for requirements that have an explicit -dev or -beta version specified but only
                // for those that are more unstable than the minimumStability or existing flags
                $reqVersion = \preg_replace('{^([^,\\s@]+) as .+$}', '$1', $constraint);
                if (\preg_match('{^[^,\\s@]+$}', $reqVersion) && 'stable' !== ($stabilityName = \RectorPrefix20210503\Composer\Package\Version\VersionParser::parseStability($reqVersion))) {
                    $name = \strtolower($reqName);
                    $stability = $stabilities[$stabilityName];
                    if (isset($stabilityFlags[$name]) && $stabilityFlags[$name] > $stability || $minimumStability > $stability) {
                        continue;
                    }
                    $stabilityFlags[$name] = $stability;
                }
            }
        }
        return $stabilityFlags;
    }
    private function extractReferences(array $requires, array $references)
    {
        foreach ($requires as $reqName => $reqVersion) {
            $reqVersion = \preg_replace('{^([^,\\s@]+) as .+$}', '$1', $reqVersion);
            if (\preg_match('{^[^,\\s@]+?#([a-f0-9]+)$}', $reqVersion, $match) && 'dev' === \RectorPrefix20210503\Composer\Package\Version\VersionParser::parseStability($reqVersion)) {
                $name = \strtolower($reqName);
                $references[$name] = $match[1];
            }
        }
        return $references;
    }
}
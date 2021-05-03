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
namespace RectorPrefix20210503\Composer;

use RectorPrefix20210503\Composer\Package\RootPackageInterface;
use RectorPrefix20210503\Composer\Package\Locker;
use RectorPrefix20210503\Composer\Util\Loop;
use RectorPrefix20210503\Composer\Repository\RepositoryManager;
use RectorPrefix20210503\Composer\Installer\InstallationManager;
use RectorPrefix20210503\Composer\Plugin\PluginManager;
use RectorPrefix20210503\Composer\Downloader\DownloadManager;
use RectorPrefix20210503\Composer\EventDispatcher\EventDispatcher;
use RectorPrefix20210503\Composer\Autoload\AutoloadGenerator;
use RectorPrefix20210503\Composer\Package\Archiver\ArchiveManager;
/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Konstantin Kudryashiv <ever.zet@gmail.com>
 * @author Nils Adermann <naderman@naderman.de>
 */
class Composer
{
    /*
     * Examples of the following constants in the various configurations they can be in
     *
     * releases (phar):
     * const VERSION = '1.8.2';
     * const BRANCH_ALIAS_VERSION = '';
     * const RELEASE_DATE = '2019-01-29 15:00:53';
     * const SOURCE_VERSION = '';
     *
     * snapshot builds (phar):
     * const VERSION = 'd3873a05650e168251067d9648845c220c50e2d7';
     * const BRANCH_ALIAS_VERSION = '1.9-dev';
     * const RELEASE_DATE = '2019-02-20 07:43:56';
     * const SOURCE_VERSION = '';
     *
     * source (git clone):
     * const VERSION = '@package_version@';
     * const BRANCH_ALIAS_VERSION = '@package_branch_alias_version@';
     * const RELEASE_DATE = '@release_date@';
     * const SOURCE_VERSION = '1.8-dev+source';
     */
    const VERSION = '2.0.13';
    const BRANCH_ALIAS_VERSION = '';
    const RELEASE_DATE = '2021-04-27 13:11:08';
    const SOURCE_VERSION = '';
    /**
     * Version number of the internal composer-runtime-api package
     *
     * This is used to version features available to projects at runtime
     * like the platform-check file, the Composer\InstalledVersions class
     * and possibly others in the future.
     *
     * @var string
     */
    const RUNTIME_API_VERSION = '2.0.0';
    public static function getVersion()
    {
        // no replacement done, this must be a source checkout
        if (self::VERSION === '@package_version' . '@') {
            return self::SOURCE_VERSION;
        }
        // we have a branch alias and version is a commit id, this must be a snapshot build
        if (self::BRANCH_ALIAS_VERSION !== '' && \preg_match('{^[a-f0-9]{40}$}', self::VERSION)) {
            return self::BRANCH_ALIAS_VERSION . '+' . self::VERSION;
        }
        return self::VERSION;
    }
    /**
     * @var RootPackageInterface
     */
    private $package;
    /**
     * @var Locker
     */
    private $locker;
    /**
     * @var Loop
     */
    private $loop;
    /**
     * @var Repository\RepositoryManager
     */
    private $repositoryManager;
    /**
     * @var Downloader\DownloadManager
     */
    private $downloadManager;
    /**
     * @var Installer\InstallationManager
     */
    private $installationManager;
    /**
     * @var Plugin\PluginManager
     */
    private $pluginManager;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;
    /**
     * @var Autoload\AutoloadGenerator
     */
    private $autoloadGenerator;
    /**
     * @var ArchiveManager
     */
    private $archiveManager;
    /**
     * @param  RootPackageInterface $package
     * @return void
     */
    public function setPackage(\RectorPrefix20210503\Composer\Package\RootPackageInterface $package)
    {
        $this->package = $package;
    }
    /**
     * @return RootPackageInterface
     */
    public function getPackage()
    {
        return $this->package;
    }
    /**
     * @param Config $config
     */
    public function setConfig(\RectorPrefix20210503\Composer\Config $config)
    {
        $this->config = $config;
    }
    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }
    /**
     * @param Locker $locker
     */
    public function setLocker(\RectorPrefix20210503\Composer\Package\Locker $locker)
    {
        $this->locker = $locker;
    }
    /**
     * @return Locker
     */
    public function getLocker()
    {
        return $this->locker;
    }
    /**
     * @param Loop $loop
     */
    public function setLoop(\RectorPrefix20210503\Composer\Util\Loop $loop)
    {
        $this->loop = $loop;
    }
    /**
     * @return Loop
     */
    public function getLoop()
    {
        return $this->loop;
    }
    /**
     * @param RepositoryManager $manager
     */
    public function setRepositoryManager(\RectorPrefix20210503\Composer\Repository\RepositoryManager $manager)
    {
        $this->repositoryManager = $manager;
    }
    /**
     * @return RepositoryManager
     */
    public function getRepositoryManager()
    {
        return $this->repositoryManager;
    }
    /**
     * @param DownloadManager $manager
     */
    public function setDownloadManager(\RectorPrefix20210503\Composer\Downloader\DownloadManager $manager)
    {
        $this->downloadManager = $manager;
    }
    /**
     * @return DownloadManager
     */
    public function getDownloadManager()
    {
        return $this->downloadManager;
    }
    /**
     * @param ArchiveManager $manager
     */
    public function setArchiveManager(\RectorPrefix20210503\Composer\Package\Archiver\ArchiveManager $manager)
    {
        $this->archiveManager = $manager;
    }
    /**
     * @return ArchiveManager
     */
    public function getArchiveManager()
    {
        return $this->archiveManager;
    }
    /**
     * @param InstallationManager $manager
     */
    public function setInstallationManager(\RectorPrefix20210503\Composer\Installer\InstallationManager $manager)
    {
        $this->installationManager = $manager;
    }
    /**
     * @return InstallationManager
     */
    public function getInstallationManager()
    {
        return $this->installationManager;
    }
    /**
     * @param PluginManager $manager
     */
    public function setPluginManager(\RectorPrefix20210503\Composer\Plugin\PluginManager $manager)
    {
        $this->pluginManager = $manager;
    }
    /**
     * @return PluginManager
     */
    public function getPluginManager()
    {
        return $this->pluginManager;
    }
    /**
     * @param EventDispatcher $eventDispatcher
     */
    public function setEventDispatcher(\RectorPrefix20210503\Composer\EventDispatcher\EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }
    /**
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }
    /**
     * @param AutoloadGenerator $autoloadGenerator
     */
    public function setAutoloadGenerator(\RectorPrefix20210503\Composer\Autoload\AutoloadGenerator $autoloadGenerator)
    {
        $this->autoloadGenerator = $autoloadGenerator;
    }
    /**
     * @return AutoloadGenerator
     */
    public function getAutoloadGenerator()
    {
        return $this->autoloadGenerator;
    }
}
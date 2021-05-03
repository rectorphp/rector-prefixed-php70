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
namespace RectorPrefix20210503\Composer\Downloader;

use RectorPrefix20210503\Composer\Config;
use RectorPrefix20210503\Composer\Package\Dumper\ArrayDumper;
use RectorPrefix20210503\Composer\Package\PackageInterface;
use RectorPrefix20210503\Composer\Package\Version\VersionGuesser;
use RectorPrefix20210503\Composer\Package\Version\VersionParser;
use RectorPrefix20210503\Composer\Util\ProcessExecutor;
use RectorPrefix20210503\Composer\IO\IOInterface;
use RectorPrefix20210503\Composer\Util\Filesystem;
use RectorPrefix20210503\React\Promise\PromiseInterface;
use RectorPrefix20210503\Composer\DependencyResolver\Operation\UpdateOperation;
use RectorPrefix20210503\Composer\DependencyResolver\Operation\InstallOperation;
use RectorPrefix20210503\Composer\DependencyResolver\Operation\UninstallOperation;
/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
abstract class VcsDownloader implements \RectorPrefix20210503\Composer\Downloader\DownloaderInterface, \RectorPrefix20210503\Composer\Downloader\ChangeReportInterface, \RectorPrefix20210503\Composer\Downloader\VcsCapableDownloaderInterface
{
    /** @var IOInterface */
    protected $io;
    /** @var Config */
    protected $config;
    /** @var ProcessExecutor */
    protected $process;
    /** @var Filesystem */
    protected $filesystem;
    /** @var array */
    protected $hasCleanedChanges = array();
    public function __construct(\RectorPrefix20210503\Composer\IO\IOInterface $io, \RectorPrefix20210503\Composer\Config $config, \RectorPrefix20210503\Composer\Util\ProcessExecutor $process = null, \RectorPrefix20210503\Composer\Util\Filesystem $fs = null)
    {
        $this->io = $io;
        $this->config = $config;
        $this->process = $process ?: new \RectorPrefix20210503\Composer\Util\ProcessExecutor($io);
        $this->filesystem = $fs ?: new \RectorPrefix20210503\Composer\Util\Filesystem($this->process);
    }
    /**
     * {@inheritDoc}
     */
    public function getInstallationSource()
    {
        return 'source';
    }
    /**
     * {@inheritDoc}
     */
    public function download(\RectorPrefix20210503\Composer\Package\PackageInterface $package, $path, \RectorPrefix20210503\Composer\Package\PackageInterface $prevPackage = null)
    {
        if (!$package->getSourceReference()) {
            throw new \InvalidArgumentException('Package ' . $package->getPrettyName() . ' is missing reference information');
        }
        $urls = $this->prepareUrls($package->getSourceUrls());
        while ($url = \array_shift($urls)) {
            try {
                return $this->doDownload($package, $path, $url, $prevPackage);
            } catch (\Exception $e) {
                // rethrow phpunit exceptions to avoid hard to debug bug failures
                if ($e instanceof \RectorPrefix20210503\PHPUnit\Framework\Exception) {
                    throw $e;
                }
                if ($this->io->isDebug()) {
                    $this->io->writeError('Failed: [' . \get_class($e) . '] ' . $e->getMessage());
                } elseif (\count($urls)) {
                    $this->io->writeError('    Failed, trying the next URL');
                }
                if (!\count($urls)) {
                    throw $e;
                }
            }
        }
    }
    /**
     * {@inheritDoc}
     */
    public function prepare($type, \RectorPrefix20210503\Composer\Package\PackageInterface $package, $path, \RectorPrefix20210503\Composer\Package\PackageInterface $prevPackage = null)
    {
        if ($type === 'update') {
            $this->cleanChanges($prevPackage, $path, \true);
            $this->hasCleanedChanges[$prevPackage->getUniqueName()] = \true;
        } elseif ($type === 'install') {
            $this->filesystem->emptyDirectory($path);
        } elseif ($type === 'uninstall') {
            $this->cleanChanges($package, $path, \false);
        }
    }
    /**
     * {@inheritDoc}
     */
    public function cleanup($type, \RectorPrefix20210503\Composer\Package\PackageInterface $package, $path, \RectorPrefix20210503\Composer\Package\PackageInterface $prevPackage = null)
    {
        if ($type === 'update' && isset($this->hasCleanedChanges[$prevPackage->getUniqueName()])) {
            $this->reapplyChanges($path);
            unset($this->hasCleanedChanges[$prevPackage->getUniqueName()]);
        }
    }
    /**
     * {@inheritDoc}
     */
    public function install(\RectorPrefix20210503\Composer\Package\PackageInterface $package, $path)
    {
        if (!$package->getSourceReference()) {
            throw new \InvalidArgumentException('Package ' . $package->getPrettyName() . ' is missing reference information');
        }
        $this->io->writeError("  - " . \RectorPrefix20210503\Composer\DependencyResolver\Operation\InstallOperation::format($package) . ': ', \false);
        $urls = $this->prepareUrls($package->getSourceUrls());
        while ($url = \array_shift($urls)) {
            try {
                $this->doInstall($package, $path, $url);
                break;
            } catch (\Exception $e) {
                // rethrow phpunit exceptions to avoid hard to debug bug failures
                if ($e instanceof \RectorPrefix20210503\PHPUnit\Framework\Exception) {
                    throw $e;
                }
                if ($this->io->isDebug()) {
                    $this->io->writeError('Failed: [' . \get_class($e) . '] ' . $e->getMessage());
                } elseif (\count($urls)) {
                    $this->io->writeError('    Failed, trying the next URL');
                }
                if (!\count($urls)) {
                    throw $e;
                }
            }
        }
    }
    /**
     * {@inheritDoc}
     */
    public function update(\RectorPrefix20210503\Composer\Package\PackageInterface $initial, \RectorPrefix20210503\Composer\Package\PackageInterface $target, $path)
    {
        if (!$target->getSourceReference()) {
            throw new \InvalidArgumentException('Package ' . $target->getPrettyName() . ' is missing reference information');
        }
        $this->io->writeError("  - " . \RectorPrefix20210503\Composer\DependencyResolver\Operation\UpdateOperation::format($initial, $target) . ': ', \false);
        $urls = $this->prepareUrls($target->getSourceUrls());
        $exception = null;
        while ($url = \array_shift($urls)) {
            try {
                $this->doUpdate($initial, $target, $path, $url);
                $exception = null;
                break;
            } catch (\Exception $exception) {
                // rethrow phpunit exceptions to avoid hard to debug bug failures
                if ($exception instanceof \RectorPrefix20210503\PHPUnit\Framework\Exception) {
                    throw $exception;
                }
                if ($this->io->isDebug()) {
                    $this->io->writeError('Failed: [' . \get_class($exception) . '] ' . $exception->getMessage());
                } elseif (\count($urls)) {
                    $this->io->writeError('    Failed, trying the next URL');
                }
            }
        }
        // print the commit logs if in verbose mode and VCS metadata is present
        // because in case of missing metadata code would trigger another exception
        if (!$exception && $this->io->isVerbose() && $this->hasMetadataRepository($path)) {
            $message = 'Pulling in changes:';
            $logs = $this->getCommitLogs($initial->getSourceReference(), $target->getSourceReference(), $path);
            if (!\trim($logs)) {
                $message = 'Rolling back changes:';
                $logs = $this->getCommitLogs($target->getSourceReference(), $initial->getSourceReference(), $path);
            }
            if (\trim($logs)) {
                $logs = \implode("\n", \array_map(function ($line) {
                    return '      ' . $line;
                }, \explode("\n", $logs)));
                // escape angle brackets for proper output in the console
                $logs = \str_replace('<', '\\<', $logs);
                $this->io->writeError('    ' . $message);
                $this->io->writeError($logs);
            }
        }
        if (!$urls && $exception) {
            throw $exception;
        }
    }
    /**
     * {@inheritDoc}
     */
    public function remove(\RectorPrefix20210503\Composer\Package\PackageInterface $package, $path)
    {
        $this->io->writeError("  - " . \RectorPrefix20210503\Composer\DependencyResolver\Operation\UninstallOperation::format($package));
        if (!$this->filesystem->removeDirectory($path)) {
            throw new \RuntimeException('Could not completely delete ' . $path . ', aborting.');
        }
    }
    /**
     * {@inheritDoc}
     */
    public function getVcsReference(\RectorPrefix20210503\Composer\Package\PackageInterface $package, $path)
    {
        $parser = new \RectorPrefix20210503\Composer\Package\Version\VersionParser();
        $guesser = new \RectorPrefix20210503\Composer\Package\Version\VersionGuesser($this->config, $this->process, $parser);
        $dumper = new \RectorPrefix20210503\Composer\Package\Dumper\ArrayDumper();
        $packageConfig = $dumper->dump($package);
        if ($packageVersion = $guesser->guessVersion($packageConfig, $path)) {
            return $packageVersion['commit'];
        }
    }
    /**
     * Prompt the user to check if changes should be stashed/removed or the operation aborted
     *
     * @param  PackageInterface  $package
     * @param  string            $path
     * @param  bool              $update  if true (update) the changes can be stashed and reapplied after an update,
     *                                    if false (remove) the changes should be assumed to be lost if the operation is not aborted
     * @throws \RuntimeException in case the operation must be aborted
     */
    protected function cleanChanges(\RectorPrefix20210503\Composer\Package\PackageInterface $package, $path, $update)
    {
        // the default implementation just fails if there are any changes, override in child classes to provide stash-ability
        if (null !== $this->getLocalChanges($package, $path)) {
            throw new \RuntimeException('Source directory ' . $path . ' has uncommitted changes.');
        }
    }
    /**
     * Reapply previously stashes changes if applicable, only called after an update (regardless if successful or not)
     *
     * @param  string            $path
     * @throws \RuntimeException in case the operation must be aborted or the patch does not apply cleanly
     */
    protected function reapplyChanges($path)
    {
    }
    /**
     * Downloads data needed to run an install/update later
     *
     * @param PackageInterface      $package     package instance
     * @param string                $path        download path
     * @param string                $url         package url
     * @param PackageInterface|null $prevPackage previous package (in case of an update)
     *
     * @return PromiseInterface|null
     */
    protected abstract function doDownload(\RectorPrefix20210503\Composer\Package\PackageInterface $package, $path, $url, \RectorPrefix20210503\Composer\Package\PackageInterface $prevPackage = null);
    /**
     * Downloads specific package into specific folder.
     *
     * @param PackageInterface $package package instance
     * @param string           $path    download path
     * @param string           $url     package url
     *
     * @return PromiseInterface|null
     */
    protected abstract function doInstall(\RectorPrefix20210503\Composer\Package\PackageInterface $package, $path, $url);
    /**
     * Updates specific package in specific folder from initial to target version.
     *
     * @param PackageInterface $initial initial package
     * @param PackageInterface $target  updated package
     * @param string           $path    download path
     * @param string           $url     package url
     *
     * @return PromiseInterface|null
     */
    protected abstract function doUpdate(\RectorPrefix20210503\Composer\Package\PackageInterface $initial, \RectorPrefix20210503\Composer\Package\PackageInterface $target, $path, $url);
    /**
     * Fetches the commit logs between two commits
     *
     * @param  string $fromReference the source reference
     * @param  string $toReference   the target reference
     * @param  string $path          the package path
     * @return string
     */
    protected abstract function getCommitLogs($fromReference, $toReference, $path);
    /**
     * Checks if VCS metadata repository has been initialized
     * repository example: .git|.svn|.hg
     *
     * @param  string $path
     * @return bool
     */
    protected abstract function hasMetadataRepository($path);
    private function prepareUrls(array $urls)
    {
        foreach ($urls as $index => $url) {
            if (\RectorPrefix20210503\Composer\Util\Filesystem::isLocalPath($url)) {
                // realpath() below will not understand
                // url that starts with "file://"
                $fileProtocol = 'file://';
                $isFileProtocol = \false;
                if (0 === \strpos($url, $fileProtocol)) {
                    $url = \substr($url, \strlen($fileProtocol));
                    $isFileProtocol = \true;
                }
                // realpath() below will not understand %20 spaces etc.
                if (\false !== \strpos($url, '%')) {
                    $url = \rawurldecode($url);
                }
                $urls[$index] = \realpath($url);
                if ($isFileProtocol) {
                    $urls[$index] = $fileProtocol . $urls[$index];
                }
            }
        }
        return $urls;
    }
}
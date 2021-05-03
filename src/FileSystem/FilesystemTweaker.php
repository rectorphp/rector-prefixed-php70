<?php

declare (strict_types=1);
namespace Rector\Core\FileSystem;

use RectorPrefix20210503\Nette\Utils\Strings;
use RectorPrefix20210503\Symplify\SmartFileSystem\FileSystemGuard;
final class FilesystemTweaker
{
    /**
     * @var FileSystemGuard
     */
    private $fileSystemGuard;
    public function __construct(\RectorPrefix20210503\Symplify\SmartFileSystem\FileSystemGuard $fileSystemGuard)
    {
        $this->fileSystemGuard = $fileSystemGuard;
    }
    /**
     * This will turn paths like "src/Symfony/Component/*\/Tests" to existing directory paths
     *
     * @param string[] $directories
     * @return string[]
     */
    public function resolveDirectoriesWithFnmatch(array $directories) : array
    {
        $absoluteDirectories = [];
        foreach ($directories as $directory) {
            // is fnmatch for directories
            if (\RectorPrefix20210503\Nette\Utils\Strings::contains($directory, '*')) {
                $foundDirectories = $this->foundDirectoriesInGlob($directory);
                $absoluteDirectories = \array_merge($absoluteDirectories, $foundDirectories);
            } else {
                // is classic directory
                $this->fileSystemGuard->ensureDirectoryExists($directory);
                $absoluteDirectories[] = $directory;
            }
        }
        return $absoluteDirectories;
    }
    /**
     * @return string[]
     */
    private function foundDirectoriesInGlob(string $directory) : array
    {
        $foundDirectories = [];
        foreach ((array) \glob($directory, \GLOB_ONLYDIR) as $foundDirectory) {
            if (!\is_string($foundDirectory)) {
                continue;
            }
            $foundDirectories[] = $foundDirectory;
        }
        return $foundDirectories;
    }
}
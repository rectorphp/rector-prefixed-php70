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

use RectorPrefix20210503\Composer\Package\PackageInterface;
/**
 * ChangeReport interface.
 *
 * @author Sascha Egerer <sascha.egerer@dkd.de>
 */
interface ChangeReportInterface
{
    /**
     * Checks for changes to the local copy
     *
     * @param  PackageInterface $package package instance
     * @param  string           $path    package directory
     * @return string|null      changes or null
     */
    public function getLocalChanges(\RectorPrefix20210503\Composer\Package\PackageInterface $package, $path);
}
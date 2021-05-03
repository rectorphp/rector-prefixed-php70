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
namespace RectorPrefix20210503\Composer\Command;

use RectorPrefix20210503\Composer\Factory;
use RectorPrefix20210503\Composer\Package\Loader\ValidatingArrayLoader;
use RectorPrefix20210503\Composer\Plugin\CommandEvent;
use RectorPrefix20210503\Composer\Plugin\PluginEvents;
use RectorPrefix20210503\Composer\Util\ConfigValidator;
use RectorPrefix20210503\Symfony\Component\Console\Input\InputArgument;
use RectorPrefix20210503\Symfony\Component\Console\Input\InputInterface;
use RectorPrefix20210503\Symfony\Component\Console\Input\InputOption;
use RectorPrefix20210503\Symfony\Component\Console\Output\OutputInterface;
/**
 * ValidateCommand
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class ValidateCommand extends \RectorPrefix20210503\Composer\Command\BaseCommand
{
    /**
     * configure
     */
    protected function configure()
    {
        $this->setName('validate')->setDescription('Validates a composer.json and composer.lock.')->setDefinition(array(new \RectorPrefix20210503\Symfony\Component\Console\Input\InputOption('no-check-all', null, \RectorPrefix20210503\Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Do not validate requires for overly strict/loose constraints'), new \RectorPrefix20210503\Symfony\Component\Console\Input\InputOption('no-check-lock', null, \RectorPrefix20210503\Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Do not check if lock file is up to date'), new \RectorPrefix20210503\Symfony\Component\Console\Input\InputOption('no-check-publish', null, \RectorPrefix20210503\Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Do not check for publish errors'), new \RectorPrefix20210503\Symfony\Component\Console\Input\InputOption('no-check-version', null, \RectorPrefix20210503\Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Do not report a warning if the version field is present'), new \RectorPrefix20210503\Symfony\Component\Console\Input\InputOption('with-dependencies', 'A', \RectorPrefix20210503\Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Also validate the composer.json of all installed dependencies'), new \RectorPrefix20210503\Symfony\Component\Console\Input\InputOption('strict', null, \RectorPrefix20210503\Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Return a non-zero exit code for warnings as well as errors'), new \RectorPrefix20210503\Symfony\Component\Console\Input\InputArgument('file', \RectorPrefix20210503\Symfony\Component\Console\Input\InputArgument::OPTIONAL, 'path to composer.json file')))->setHelp(<<<EOT
The validate command validates a given composer.json and composer.lock

Exit codes in case of errors are:
1 validation warning(s), only when --strict is given
2 validation error(s)
3 file unreadable or missing

Read more at https://getcomposer.org/doc/03-cli.md#validate
EOT
);
    }
    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(\RectorPrefix20210503\Symfony\Component\Console\Input\InputInterface $input, \RectorPrefix20210503\Symfony\Component\Console\Output\OutputInterface $output)
    {
        $file = $input->getArgument('file') ?: \RectorPrefix20210503\Composer\Factory::getComposerFile();
        $io = $this->getIO();
        if (!\file_exists($file)) {
            $io->writeError('<error>' . $file . ' not found.</error>');
            return 3;
        }
        if (!\is_readable($file)) {
            $io->writeError('<error>' . $file . ' is not readable.</error>');
            return 3;
        }
        $validator = new \RectorPrefix20210503\Composer\Util\ConfigValidator($io);
        $checkAll = $input->getOption('no-check-all') ? 0 : \RectorPrefix20210503\Composer\Package\Loader\ValidatingArrayLoader::CHECK_ALL;
        $checkPublish = !$input->getOption('no-check-publish');
        $checkLock = !$input->getOption('no-check-lock');
        $checkVersion = $input->getOption('no-check-version') ? 0 : \RectorPrefix20210503\Composer\Util\ConfigValidator::CHECK_VERSION;
        $isStrict = $input->getOption('strict');
        list($errors, $publishErrors, $warnings) = $validator->validate($file, $checkAll, $checkVersion);
        $lockErrors = array();
        $composer = \RectorPrefix20210503\Composer\Factory::create($io, $file, $input->hasParameterOption('--no-plugins'));
        $locker = $composer->getLocker();
        if ($locker->isLocked() && !$locker->isFresh()) {
            $lockErrors[] = 'The lock file is not up to date with the latest changes in composer.json, it is recommended that you run `composer update` or `composer update <package name>`.';
        }
        $this->outputResult($io, $file, $errors, $warnings, $checkPublish, $publishErrors, $checkLock, $lockErrors, \true);
        // $errors include publish and lock errors when exists
        $exitCode = $errors ? 2 : ($isStrict && $warnings ? 1 : 0);
        if ($input->getOption('with-dependencies')) {
            $localRepo = $composer->getRepositoryManager()->getLocalRepository();
            foreach ($localRepo->getPackages() as $package) {
                $path = $composer->getInstallationManager()->getInstallPath($package);
                $file = $path . '/composer.json';
                if (\is_dir($path) && \file_exists($file)) {
                    list($errors, $publishErrors, $warnings) = $validator->validate($file, $checkAll, $checkVersion);
                    $this->outputResult($io, $package->getPrettyName(), $errors, $warnings, $checkPublish, $publishErrors);
                    // $errors include publish errors when exists
                    $depCode = $errors ? 2 : ($isStrict && $warnings ? 1 : 0);
                    $exitCode = \max($depCode, $exitCode);
                }
            }
        }
        $commandEvent = new \RectorPrefix20210503\Composer\Plugin\CommandEvent(\RectorPrefix20210503\Composer\Plugin\PluginEvents::COMMAND, 'validate', $input, $output);
        $eventCode = $composer->getEventDispatcher()->dispatch($commandEvent->getName(), $commandEvent);
        return \max($eventCode, $exitCode);
    }
    private function outputResult($io, $name, &$errors, &$warnings, $checkPublish = \false, $publishErrors = array(), $checkLock = \false, $lockErrors = array(), $printSchemaUrl = \false)
    {
        $doPrintSchemaUrl = \false;
        if ($errors) {
            $io->writeError('<error>' . $name . ' is invalid, the following errors/warnings were found:</error>');
        } elseif ($publishErrors) {
            $io->writeError('<info>' . $name . ' is valid for simple usage with composer but has</info>');
            $io->writeError('<info>strict errors that make it unable to be published as a package:</info>');
            $doPrintSchemaUrl = $printSchemaUrl;
        } elseif ($warnings) {
            $io->writeError('<info>' . $name . ' is valid, but with a few warnings</info>');
            $doPrintSchemaUrl = $printSchemaUrl;
        } else {
            $io->write('<info>' . $name . ' is valid</info>');
            // if ($lockErrors) then they will be displayed below
        }
        if ($doPrintSchemaUrl) {
            $io->writeError('<warning>See https://getcomposer.org/doc/04-schema.md for details on the schema</warning>');
        }
        // Avoid setting the exit code to 1 in case --strict and --no-check-publish/--no-check-lock are combined
        $extraWarnings = array();
        // If checking publish errors, display them as errors, otherwise just show them as warnings
        if ($checkPublish) {
            $errors = \array_merge($errors, $publishErrors);
        } else {
            $extraWarnings = \array_merge($extraWarnings, $publishErrors);
        }
        // If checking lock errors, display them as errors, otherwise just show them as warnings
        if ($checkLock) {
            $errors = \array_merge($errors, $lockErrors);
        } else {
            $extraWarnings = \array_merge($extraWarnings, $lockErrors);
        }
        $messages = array('error' => $errors, 'warning' => \array_merge($warnings, $extraWarnings));
        foreach ($messages as $style => $msgs) {
            foreach ($msgs as $msg) {
                $io->writeError('<' . $style . '>' . $msg . '</' . $style . '>');
            }
        }
    }
}
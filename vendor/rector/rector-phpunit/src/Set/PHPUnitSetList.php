<?php

declare (strict_types=1);
namespace Rector\PHPUnit\Set;

use Rector\Set\Contract\SetListInterface;
final class PHPUnitSetList implements \Rector\Set\Contract\SetListInterface
{
    /**
     * @var string
     */
    const PHPUNIT80_DMS = __DIR__ . '/../../config/sets/phpunit80-dms.php';
    /**
     * @var string
     */
    const PHPUNIT_40 = __DIR__ . '/../../config/sets/phpunit40.php';
    /**
     * @var string
     */
    const PHPUNIT_50 = __DIR__ . '/../../config/sets/phpunit50.php';
    /**
     * @var string
     */
    const PHPUNIT_60 = __DIR__ . '/../../config/sets/phpunit60.php';
    /**
     * @var string
     */
    const PHPUNIT_70 = __DIR__ . '/../../config/sets/phpunit70.php';
    /**
     * @var string
     */
    const PHPUNIT_75 = __DIR__ . '/../../config/sets/phpunit75.php';
    /**
     * @var string
     */
    const PHPUNIT_80 = __DIR__ . '/../../config/sets/phpunit80.php';
    /**
     * @var string
     */
    const PHPUNIT_90 = __DIR__ . '/../../config/sets/phpunit90.php';
    /**
     * @var string
     */
    const PHPUNIT_91 = __DIR__ . '/../../config/sets/phpunit91.php';
    /**
     * @var string
     */
    const PHPUNIT_CODE_QUALITY = __DIR__ . '/../../config/sets/phpunit-code-quality.php';
    /**
     * @var string
     */
    const PHPUNIT_EXCEPTION = __DIR__ . '/../../config/sets/phpunit-exception.php';
    /**
     * @var string
     */
    const PHPUNIT_MOCK = __DIR__ . '/../../config/sets/phpunit-mock.php';
    /**
     * @var string
     */
    const PHPUNIT_SPECIFIC_METHOD = __DIR__ . '/../../config/sets/phpunit-specific-method.php';
    /**
     * @var string
     */
    const PHPUNIT_YIELD_DATA_PROVIDER = __DIR__ . '/../../config/sets/phpunit-yield-data-provider.php';
}
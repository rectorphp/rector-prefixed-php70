<?php

declare (strict_types=1);
namespace Ssch\TYPO3Rector\TypoScript\Conditions;

final class PIDinRootlineConditionMatcher extends \Ssch\TYPO3Rector\TypoScript\Conditions\AbstractRootlineConditionMatcher
{
    /**
     * @var string
     */
    const TYPE = 'PIDinRootline';
    protected function getType() : string
    {
        return self::TYPE;
    }
    protected function getExpression() : string
    {
        return 'tree.rootLineIds';
    }
}

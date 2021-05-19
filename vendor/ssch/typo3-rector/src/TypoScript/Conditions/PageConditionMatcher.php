<?php

declare (strict_types=1);
namespace Ssch\TYPO3Rector\TypoScript\Conditions;

use Ssch\TYPO3Rector\Contract\TypoScript\Conditions\TyposcriptConditionMatcher;
final class PageConditionMatcher implements \Ssch\TYPO3Rector\Contract\TypoScript\Conditions\TyposcriptConditionMatcher
{
    /**
     * @var string
     */
    const TYPE = 'page';
    /**
     * @return string|null
     */
    public function change(string $condition)
    {
        \preg_match('#' . self::TYPE . '\\s*\\|(.*)\\s*=\\s*(.*)#', $condition, $matches);
        if (!\is_array($matches)) {
            return $condition;
        }
        if (!isset($matches[1])) {
            return $condition;
        }
        if (!isset($matches[2])) {
            return $condition;
        }
        return \sprintf('page["%s"] == "%s"', \trim($matches[1]), \trim($matches[2]));
    }
    public function shouldApply(string $condition) : bool
    {
        return 1 === \preg_match('#^' . self::TYPE . self::ZERO_ONE_OR_MORE_WHITESPACES . '\\|#', $condition);
    }
}

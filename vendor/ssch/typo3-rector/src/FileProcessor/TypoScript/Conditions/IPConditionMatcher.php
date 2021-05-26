<?php

declare (strict_types=1);
namespace Ssch\TYPO3Rector\FileProcessor\TypoScript\Conditions;

use RectorPrefix20210526\Nette\Utils\Strings;
use Ssch\TYPO3Rector\Contract\FileProcessor\TypoScript\Conditions\TyposcriptConditionMatcher;
use Ssch\TYPO3Rector\Helper\ArrayUtility;
final class IPConditionMatcher implements \Ssch\TYPO3Rector\Contract\FileProcessor\TypoScript\Conditions\TyposcriptConditionMatcher
{
    /**
     * @var string
     */
    const TYPE = 'IP';
    /**
     * @return string|null
     */
    public function change(string $condition)
    {
        \preg_match('#' . self::TYPE . '\\s*=\\s*(.*)#', $condition, $matches);
        if (!\is_array($matches)) {
            return $condition;
        }
        $values = \Ssch\TYPO3Rector\Helper\ArrayUtility::trimExplode(',', $matches[1], \true);
        return \sprintf('ip("%s")', \implode(',', $values));
    }
    public function shouldApply(string $condition) : bool
    {
        if (\RectorPrefix20210526\Nette\Utils\Strings::contains($condition, self::CONTAINS_CONSTANT)) {
            return \false;
        }
        return \RectorPrefix20210526\Nette\Utils\Strings::startsWith($condition, self::TYPE);
    }
}
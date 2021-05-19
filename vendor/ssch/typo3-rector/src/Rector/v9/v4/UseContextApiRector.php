<?php

declare (strict_types=1);
namespace Ssch\TYPO3Rector\Rector\v9\v4;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Scalar\String_;
use PHPStan\Type\ObjectType;
use Rector\Core\Rector\AbstractRector;
use Ssch\TYPO3Rector\Helper\Typo3NodeResolver;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/9.4/Deprecation-85389-VariousPublicPropertiesInFavorOfContextAPI.html
 * @see \Ssch\TYPO3Rector\Tests\Rector\v9\v4\UseContextApiRector\UseContextApiRectorTest
 */
final class UseContextApiRector extends \Rector\Core\Rector\AbstractRector
{
    /**
     * @var string[]
     */
    const REFACTOR_PROPERTIES = ['loginUser', 'gr_list', 'beUserLogin', 'showHiddenPage', 'showHiddenRecords'];
    /**
     * @var \Ssch\TYPO3Rector\Helper\Typo3NodeResolver
     */
    private $typo3NodeResolver;
    public function __construct(\Ssch\TYPO3Rector\Helper\Typo3NodeResolver $typo3NodeResolver)
    {
        $this->typo3NodeResolver = $typo3NodeResolver;
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [\PhpParser\Node\Expr\PropertyFetch::class];
    }
    /**
     * @param \PhpParser\Node $node
     * @return \PhpParser\Node|null
     */
    public function refactor($node)
    {
        if (!$this->isObjectType($node->var, new \PHPStan\Type\ObjectType('RectorPrefix20210519\\TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController')) && !$this->typo3NodeResolver->isPropertyFetchOnAnyPropertyOfGlobals($node, \Ssch\TYPO3Rector\Helper\Typo3NodeResolver::TYPO_SCRIPT_FRONTEND_CONTROLLER)) {
            return null;
        }
        if (!$this->isNames($node->name, self::REFACTOR_PROPERTIES)) {
            return null;
        }
        $propertyName = $this->getName($node->name);
        $staticCall = $this->nodeFactory->createStaticCall('RectorPrefix20210519\\TYPO3\\CMS\\Core\\Utility\\GeneralUtility', 'makeInstance', [$this->nodeFactory->createClassConstReference('RectorPrefix20210519\\TYPO3\\CMS\\Core\\Context\\Context')]);
        $contextCall = $this->nodeFactory->createMethodCall($staticCall, 'getPropertyFromAspect');
        if ('loginUser' === $propertyName) {
            $contextCall->args = $this->nodeFactory->createArgs(['frontend.user', 'isLoggedIn']);
            return $contextCall;
        }
        if ('gr_list' === $propertyName) {
            $contextCall->args = $this->nodeFactory->createArgs(['frontend.user', 'groupIds']);
            return $this->nodeFactory->createFuncCall('implode', [new \PhpParser\Node\Scalar\String_(','), $contextCall]);
        }
        if ('beUserLogin' === $propertyName) {
            $contextCall->args = $this->nodeFactory->createArgs(['backend.user', 'isLoggedIn']);
            return $contextCall;
        }
        if ('showHiddenPage' === $propertyName) {
            $contextCall->args = $this->nodeFactory->createArgs(['visibility', 'includeHiddenPages']);
            return $contextCall;
        }
        if ('showHiddenRecords' === $propertyName) {
            $contextCall->args = $this->nodeFactory->createArgs(['visibility', 'includeHiddenContent']);
            return $contextCall;
        }
        return null;
    }
    /**
     * @codeCoverageIgnore
     */
    public function getRuleDefinition() : \Symplify\RuleDocGenerator\ValueObject\RuleDefinition
    {
        return new \Symplify\RuleDocGenerator\ValueObject\RuleDefinition('Various public properties in favor of Context API', [new \Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample(<<<'CODE_SAMPLE'
$frontendUserIsLoggedIn = $GLOBALS['TSFE']->loginUser;
$groupList = $GLOBALS['TSFE']->gr_list;
$backendUserIsLoggedIn = $GLOBALS['TSFE']->beUserLogin;
$showHiddenPage = $GLOBALS['TSFE']->showHiddenPage;
$showHiddenRecords = $GLOBALS['TSFE']->showHiddenRecords;
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$frontendUserIsLoggedIn = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class)->getPropertyFromAspect('frontend.user', 'isLoggedIn');
$groupList = implode(',', \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class)->getPropertyFromAspect('frontend.user', 'groupIds'));
$backendUserIsLoggedIn = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class)->getPropertyFromAspect('backend.user', 'isLoggedIn');
$showHiddenPage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class)->getPropertyFromAspect('visibility', 'includeHiddenPages');
$showHiddenRecords = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class)->getPropertyFromAspect('visibility', 'includeHiddenContent');
CODE_SAMPLE
)]);
    }
}

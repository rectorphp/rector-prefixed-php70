<?php

declare (strict_types=1);
namespace Rector\DowngradePhp71\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Name\FullyQualified;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\DowngradePhp71\Rector\FuncCall\DowngradeIsIterableRector\DowngradeIsIterableRectorTest
 */
final class DowngradeIsIterableRector extends \Rector\Core\Rector\AbstractRector
{
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [\PhpParser\Node\Expr\FuncCall::class];
    }
    public function getRuleDefinition() : \Symplify\RuleDocGenerator\ValueObject\RuleDefinition
    {
        return new \Symplify\RuleDocGenerator\ValueObject\RuleDefinition('Change is_iterable with array and Traversable object type check', [new \Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run($obj)
    {
        is_iterable($obj);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($obj)
    {
        is_array($obj) || $obj instanceof \Traversable;
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @param FuncCall $node
     * @return \PhpParser\Node|null
     */
    public function refactor(\PhpParser\Node $node)
    {
        if (!$this->isName($node, 'is_iterable')) {
            return null;
        }
        /** @var mixed $arg */
        $arg = $node->args[0]->value;
        $funcCall = $this->nodeFactory->createFuncCall('is_array', [$arg]);
        $instanceOf = new \PhpParser\Node\Expr\Instanceof_($arg, new \PhpParser\Node\Name\FullyQualified('Traversable'));
        return new \PhpParser\Node\Expr\BinaryOp\BooleanOr($funcCall, $instanceOf);
    }
}
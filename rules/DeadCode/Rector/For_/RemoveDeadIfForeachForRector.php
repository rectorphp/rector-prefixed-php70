<?php

declare (strict_types=1);
namespace Rector\DeadCode\Rector\For_;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\DeadCode\Rector\For_\RemoveDeadIfForeachForRector\RemoveDeadIfForeachForRectorTest
 */
final class RemoveDeadIfForeachForRector extends \Rector\Core\Rector\AbstractRector
{
    public function getRuleDefinition() : \Symplify\RuleDocGenerator\ValueObject\RuleDefinition
    {
        return new \Symplify\RuleDocGenerator\ValueObject\RuleDefinition('Remove if, foreach and for that does not do anything', [new \Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run($someObject)
    {
        $value = 5;
        if ($value) {
        }

        if ($someObject->run()) {
        }

        foreach ($values as $value) {
        }

        return $value;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($someObject)
    {
        $value = 5;
        if ($someObject->run()) {
        }

        return $value;
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [\PhpParser\Node\Stmt\For_::class, \PhpParser\Node\Stmt\If_::class, \PhpParser\Node\Stmt\Foreach_::class];
    }
    /**
     * @param For_|If_|Foreach_ $node
     * @return \PhpParser\Node|null
     */
    public function refactor(\PhpParser\Node $node)
    {
        if ($node instanceof \PhpParser\Node\Stmt\If_) {
            $this->processIf($node);
            return null;
        }
        if ($node instanceof \PhpParser\Node\Stmt\Foreach_) {
            $this->processForeach($node);
            return null;
        }
        // For
        if ($node->stmts !== []) {
            return null;
        }
        $this->removeNode($node);
        return null;
    }
    /**
     * @return void
     */
    private function processIf(\PhpParser\Node\Stmt\If_ $if)
    {
        if ($if->stmts !== []) {
            return;
        }
        if ($if->else !== null) {
            return;
        }
        if ($if->elseifs !== []) {
            return;
        }
        if ($this->isNodeWithSideEffect($if->cond)) {
            return;
        }
        $this->removeNode($if);
    }
    /**
     * @return void
     */
    private function processForeach(\PhpParser\Node\Stmt\Foreach_ $foreach)
    {
        if ($foreach->stmts !== []) {
            return;
        }
        if ($this->isNodeWithSideEffect($foreach->expr)) {
            return;
        }
        $this->removeNode($foreach);
    }
    private function isNodeWithSideEffect(\PhpParser\Node\Expr $expr) : bool
    {
        if ($expr instanceof \PhpParser\Node\Expr\Variable) {
            return \false;
        }
        if ($expr instanceof \PhpParser\Node\Scalar) {
            return \false;
        }
        return !$this->valueResolver->isTrueOrFalse($expr);
    }
}
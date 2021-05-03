<?php

declare (strict_types=1);
namespace Rector\DeadCode\NodeFinder;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use Rector\Core\PhpParser\Comparing\NodeComparator;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
use Rector\NodeNameResolver\NodeNameResolver;
final class PreviousVariableAssignNodeFinder
{
    /**
     * @var BetterNodeFinder
     */
    private $betterNodeFinder;
    /**
     * @var NodeNameResolver
     */
    private $nodeNameResolver;
    /**
     * @var NodeComparator
     */
    private $nodeComparator;
    public function __construct(\Rector\Core\PhpParser\Node\BetterNodeFinder $betterNodeFinder, \Rector\NodeNameResolver\NodeNameResolver $nodeNameResolver, \Rector\Core\PhpParser\Comparing\NodeComparator $nodeComparator)
    {
        $this->betterNodeFinder = $betterNodeFinder;
        $this->nodeNameResolver = $nodeNameResolver;
        $this->nodeComparator = $nodeComparator;
    }
    /**
     * @return \PhpParser\Node|null
     */
    public function find(\PhpParser\Node\Expr\Assign $assign)
    {
        $currentAssign = $assign;
        $variableName = $this->nodeNameResolver->getName($assign->var);
        if ($variableName === null) {
            return null;
        }
        return $this->betterNodeFinder->findFirstPrevious($assign, function (\PhpParser\Node $node) use($variableName, $currentAssign) : bool {
            if (!$node instanceof \PhpParser\Node\Expr\Assign) {
                return \false;
            }
            // skip self
            if ($this->nodeComparator->areSameNode($node, $currentAssign)) {
                return \false;
            }
            return $this->nodeNameResolver->isName($node->var, $variableName);
        });
    }
}
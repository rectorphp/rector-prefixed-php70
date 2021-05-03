<?php

declare (strict_types=1);
namespace Rector\NodeNameResolver\NodeNameResolver;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use Rector\NodeNameResolver\Contract\NodeNameResolverInterface;
use Rector\NodeNameResolver\NodeNameResolver;
final class UseNameResolver implements \Rector\NodeNameResolver\Contract\NodeNameResolverInterface
{
    /**
     * @var NodeNameResolver
     */
    private $nodeNameResolver;
    /**
     * @required
     * @return void
     */
    public function autowireUseNameResolver(\Rector\NodeNameResolver\NodeNameResolver $nodeNameResolver)
    {
        $this->nodeNameResolver = $nodeNameResolver;
    }
    /**
     * @return class-string<Node>
     */
    public function getNode() : string
    {
        return \PhpParser\Node\Stmt\Use_::class;
    }
    /**
     * @param Use_ $node
     * @return string|null
     */
    public function resolve(\PhpParser\Node $node)
    {
        if ($node->uses === []) {
            return null;
        }
        $onlyUse = $node->uses[0];
        return $this->nodeNameResolver->getName($onlyUse);
    }
}
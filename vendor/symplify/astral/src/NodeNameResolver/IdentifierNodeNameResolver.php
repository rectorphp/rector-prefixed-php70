<?php

declare (strict_types=1);
namespace RectorPrefix20210520\Symplify\Astral\NodeNameResolver;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use RectorPrefix20210520\Symplify\Astral\Contract\NodeNameResolverInterface;
final class IdentifierNodeNameResolver implements \RectorPrefix20210520\Symplify\Astral\Contract\NodeNameResolverInterface
{
    public function match(\PhpParser\Node $node) : bool
    {
        if ($node instanceof \PhpParser\Node\Identifier) {
            return \true;
        }
        return $node instanceof \PhpParser\Node\Name;
    }
    /**
     * @param Identifier|Name $node
     * @return string|null
     */
    public function resolve(\PhpParser\Node $node)
    {
        return (string) $node;
    }
}

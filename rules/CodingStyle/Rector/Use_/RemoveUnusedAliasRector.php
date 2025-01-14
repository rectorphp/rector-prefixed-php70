<?php

declare (strict_types=1);
namespace Rector\CodingStyle\Rector\Use_;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use Rector\CodingStyle\Naming\NameRenamer;
use Rector\CodingStyle\Node\DocAliasResolver;
use Rector\CodingStyle\Node\UseManipulator;
use Rector\CodingStyle\Node\UseNameAliasToNameResolver;
use Rector\CodingStyle\ValueObject\NameAndParent;
use Rector\Core\Rector\AbstractRector;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\CodingStyle\Rector\Use_\RemoveUnusedAliasRector\RemoveUnusedAliasRectorTest
 */
final class RemoveUnusedAliasRector extends \Rector\Core\Rector\AbstractRector
{
    /**
     * @var NameAndParent[][]
     */
    private $resolvedNodeNames = [];
    /**
     * @var array<string, string[]>
     */
    private $useNamesAliasToName = [];
    /**
     * @var string[]
     */
    private $resolvedDocPossibleAliases = [];
    /**
     * @var \Rector\CodingStyle\Node\DocAliasResolver
     */
    private $docAliasResolver;
    /**
     * @var \Rector\CodingStyle\Node\UseManipulator
     */
    private $useManipulator;
    /**
     * @var \Rector\CodingStyle\Node\UseNameAliasToNameResolver
     */
    private $useNameAliasToNameResolver;
    /**
     * @var \Rector\CodingStyle\Naming\NameRenamer
     */
    private $nameRenamer;
    public function __construct(\Rector\CodingStyle\Node\DocAliasResolver $docAliasResolver, \Rector\CodingStyle\Node\UseManipulator $useManipulator, \Rector\CodingStyle\Node\UseNameAliasToNameResolver $useNameAliasToNameResolver, \Rector\CodingStyle\Naming\NameRenamer $nameRenamer)
    {
        $this->docAliasResolver = $docAliasResolver;
        $this->useManipulator = $useManipulator;
        $this->useNameAliasToNameResolver = $useNameAliasToNameResolver;
        $this->nameRenamer = $nameRenamer;
    }
    public function getRuleDefinition() : \Symplify\RuleDocGenerator\ValueObject\RuleDefinition
    {
        return new \Symplify\RuleDocGenerator\ValueObject\RuleDefinition('Removes unused use aliases. Keep annotation aliases like "Doctrine\\ORM\\Mapping as ORM" to keep convention format', [new \Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample(<<<'CODE_SAMPLE'
use Symfony\Kernel as BaseKernel;

class SomeClass extends BaseKernel
{
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Kernel;

class SomeClass extends Kernel
{
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [\PhpParser\Node\Stmt\Use_::class];
    }
    /**
     * @param Use_ $node
     * @return \PhpParser\Node|null
     */
    public function refactor(\PhpParser\Node $node)
    {
        if ($this->shouldSkipUse($node)) {
            return null;
        }
        $searchNode = $this->resolveSearchNode($node);
        if (!$searchNode instanceof \PhpParser\Node) {
            return null;
        }
        $this->resolvedNodeNames = $this->useManipulator->resolveUsedNameNodes($searchNode);
        $this->resolvedDocPossibleAliases = $this->docAliasResolver->resolve($searchNode);
        $this->useNamesAliasToName = $this->useNameAliasToNameResolver->resolve($this->file, $node);
        // lowercase
        $this->resolvedDocPossibleAliases = $this->lowercaseArray($this->resolvedDocPossibleAliases);
        $this->resolvedNodeNames = \array_change_key_case($this->resolvedNodeNames, \CASE_LOWER);
        $this->useNamesAliasToName = \array_change_key_case($this->useNamesAliasToName, \CASE_LOWER);
        foreach ($node->uses as $use) {
            if ($use->alias === null) {
                continue;
            }
            $lastName = $use->name->getLast();
            $lowercasedLastName = \strtolower($lastName);
            /** @var string $aliasName */
            $aliasName = $this->getName($use->alias);
            if ($this->shouldSkip($node, $use->name, $lastName, $aliasName)) {
                continue;
            }
            // only last name is used → no need for alias
            if (isset($this->resolvedNodeNames[$lowercasedLastName])) {
                $use->alias = null;
                continue;
            }
            $this->refactorAliasName($aliasName, $lastName, $use);
        }
        return $node;
    }
    private function shouldSkipUse(\PhpParser\Node\Stmt\Use_ $use) : bool
    {
        // skip cases without namespace, problematic to analyse
        $namespace = $this->betterNodeFinder->findParentType($use, \PhpParser\Node\Stmt\Namespace_::class);
        if (!$namespace instanceof \PhpParser\Node) {
            return \true;
        }
        return !$this->hasUseAlias($use);
    }
    /**
     * @return \PhpParser\Node|null
     */
    private function resolveSearchNode(\PhpParser\Node\Stmt\Use_ $use)
    {
        $searchNode = $use->getAttribute(\Rector\NodeTypeResolver\Node\AttributeKey::PARENT_NODE);
        if ($searchNode !== null) {
            return $searchNode;
        }
        return $use->getAttribute(\Rector\NodeTypeResolver\Node\AttributeKey::NEXT_NODE);
    }
    /**
     * @param string[] $values
     * @return string[]
     */
    private function lowercaseArray(array $values) : array
    {
        return \array_map('strtolower', $values);
    }
    private function shouldSkip(\PhpParser\Node\Stmt\Use_ $use, \PhpParser\Node\Name $name, string $lastName, string $aliasName) : bool
    {
        // PHP is case insensitive
        $loweredLastName = \strtolower($lastName);
        $loweredAliasName = \strtolower($aliasName);
        // both are used → nothing to remove
        if (isset($this->resolvedNodeNames[$loweredLastName], $this->resolvedNodeNames[$loweredAliasName])) {
            return \true;
        }
        // part of some @Doc annotation
        if (\in_array($loweredAliasName, $this->resolvedDocPossibleAliases, \true)) {
            return \true;
        }
        return (bool) $this->betterNodeFinder->findFirstNext($use, function (\PhpParser\Node $node) use($name) : bool {
            if (!$node instanceof \PhpParser\Node\Expr\ClassConstFetch) {
                return \false;
            }
            if (!$node->class instanceof \PhpParser\Node\Name) {
                return \false;
            }
            return $node->class->toString() === $name->toString();
        });
    }
    /**
     * @return void
     */
    private function refactorAliasName(string $aliasName, string $lastName, \PhpParser\Node\Stmt\UseUse $useUse)
    {
        // only alias name is used → use last name directly
        $lowerAliasName = \strtolower($aliasName);
        if (!isset($this->resolvedNodeNames[$lowerAliasName])) {
            return;
        }
        // keep to differentiate 2 aliases classes
        $lowerLastName = \strtolower($lastName);
        if (\count($this->useNamesAliasToName[$lowerLastName] ?? []) > 1) {
            return;
        }
        $this->nameRenamer->renameNameNode($this->resolvedNodeNames[$lowerAliasName], $lastName);
        $useUse->alias = null;
    }
    private function hasUseAlias(\PhpParser\Node\Stmt\Use_ $use) : bool
    {
        foreach ($use->uses as $useUse) {
            if ($useUse->alias !== null) {
                return \true;
            }
        }
        return \false;
    }
}

<?php

declare (strict_types=1);
namespace Rector\Restoration\Rector\Namespace_;

use RectorPrefix20210503\Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use Rector\Core\Contract\Rector\ConfigurableRectorInterface;
use Rector\Core\Rector\AbstractRector;
use Rector\Restoration\ValueObject\CompleteImportForPartialAnnotation;
use RectorPrefix20210503\Symplify\Astral\ValueObject\NodeBuilder\UseBuilder;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\Restoration\Rector\Namespace_\CompleteImportForPartialAnnotationRector\CompleteImportForPartialAnnotationRectorTest
 */
final class CompleteImportForPartialAnnotationRector extends \Rector\Core\Rector\AbstractRector implements \Rector\Core\Contract\Rector\ConfigurableRectorInterface
{
    /**
     * @api
     * @var string
     */
    const USE_IMPORTS_TO_RESTORE = '$useImportsToRestore';
    /**
     * @var CompleteImportForPartialAnnotation[]
     */
    private $useImportsToRestore = [];
    public function getRuleDefinition() : \Symplify\RuleDocGenerator\ValueObject\RuleDefinition
    {
        return new \Symplify\RuleDocGenerator\ValueObject\RuleDefinition('In case you have accidentally removed use imports but code still contains partial use statements, this will save you', [new \Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @ORM\Id
     */
    public $id;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Doctrine\ORM\Mapping as ORM;

class SomeClass
{
    /**
     * @ORM\Id
     */
    public $id;
}
CODE_SAMPLE
, [self::USE_IMPORTS_TO_RESTORE => [new \Rector\Restoration\ValueObject\CompleteImportForPartialAnnotation('Doctrine\\ORM\\Mapping', 'ORM')]])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [\PhpParser\Node\Stmt\Namespace_::class];
    }
    /**
     * @param Namespace_ $node
     * @return \PhpParser\Node|null
     */
    public function refactor(\PhpParser\Node $node)
    {
        $class = $this->betterNodeFinder->findFirstInstanceOf($node->stmts, \PhpParser\Node\Stmt\Class_::class);
        if (!$class instanceof \PhpParser\Node\Stmt\Class_) {
            return null;
        }
        foreach ($this->useImportsToRestore as $useImportToRestore) {
            $annotationToSeek = '#\\*\\s+\\@' . $useImportToRestore->getAlias() . '#';
            if (!\RectorPrefix20210503\Nette\Utils\Strings::match($this->print($class), $annotationToSeek)) {
                continue;
            }
            $node = $this->addImportToNamespaceIfMissing($node, $useImportToRestore);
        }
        return $node;
    }
    /**
     * @param CompleteImportForPartialAnnotation[][] $configuration
     * @return void
     */
    public function configure(array $configuration)
    {
        $default = [new \Rector\Restoration\ValueObject\CompleteImportForPartialAnnotation('Doctrine\\ORM\\Mapping', 'ORM'), new \Rector\Restoration\ValueObject\CompleteImportForPartialAnnotation('Symfony\\Component\\Validator\\Constraints', 'Assert'), new \Rector\Restoration\ValueObject\CompleteImportForPartialAnnotation('JMS\\Serializer\\Annotation', 'Serializer')];
        $this->useImportsToRestore = \array_merge($configuration[self::USE_IMPORTS_TO_RESTORE] ?? [], $default);
    }
    private function addImportToNamespaceIfMissing(\PhpParser\Node\Stmt\Namespace_ $namespace, \Rector\Restoration\ValueObject\CompleteImportForPartialAnnotation $completeImportForPartialAnnotation) : \PhpParser\Node\Stmt\Namespace_
    {
        foreach ($namespace->stmts as $stmt) {
            if (!$stmt instanceof \PhpParser\Node\Stmt\Use_) {
                continue;
            }
            $useUse = $stmt->uses[0];
            // already there
            if (!$this->isName($useUse->name, $completeImportForPartialAnnotation->getUse())) {
                continue;
            }
            if ((string) $useUse->alias !== $completeImportForPartialAnnotation->getAlias()) {
                continue;
            }
            return $namespace;
        }
        return $this->addImportToNamespace($namespace, $completeImportForPartialAnnotation);
    }
    private function addImportToNamespace(\PhpParser\Node\Stmt\Namespace_ $namespace, \Rector\Restoration\ValueObject\CompleteImportForPartialAnnotation $completeImportForPartialAnnotation) : \PhpParser\Node\Stmt\Namespace_
    {
        $useBuilder = new \RectorPrefix20210503\Symplify\Astral\ValueObject\NodeBuilder\UseBuilder($completeImportForPartialAnnotation->getUse());
        if ($completeImportForPartialAnnotation->getAlias() !== '') {
            $useBuilder->as($completeImportForPartialAnnotation->getAlias());
        }
        /** @var Stmt $use */
        $use = $useBuilder->getNode();
        $namespace->stmts = \array_merge([$use], $namespace->stmts);
        return $namespace;
    }
}
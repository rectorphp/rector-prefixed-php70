<?php

declare (strict_types=1);
namespace Rector\TypeDeclaration\Rector\FunctionLike;

use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Type\MixedType;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\ValueObject\PhpVersionFeature;
use Rector\DeadCode\PhpDoc\TagRemover\ParamTagRemover;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\PHPStanStaticTypeMapper\ValueObject\TypeKind;
use Rector\TypeDeclaration\ChildPopulator\ChildParamPopulator;
use Rector\TypeDeclaration\NodeTypeAnalyzer\TraitTypeAnalyzer;
use Rector\TypeDeclaration\TypeInferer\ParamTypeInferer;
use Rector\TypeDeclaration\ValueObject\NewType;
use Rector\VendorLocker\VendorLockResolver;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/scalar_type_hints_v5
 * @changelog https://github.com/nikic/TypeUtil
 * @changelog https://github.com/nette/type-fixer
 * @changelog https://github.com/FriendsOfPHP/PHP-CS-Fixer/issues/3258
 *
 * @see \Rector\Tests\TypeDeclaration\Rector\FunctionLike\ParamTypeDeclarationRector\ParamTypeDeclarationRectorTest
 */
final class ParamTypeDeclarationRector extends \Rector\Core\Rector\AbstractRector
{
    /**
     * @var \Rector\VendorLocker\VendorLockResolver
     */
    private $vendorLockResolver;
    /**
     * @var \Rector\TypeDeclaration\ChildPopulator\ChildParamPopulator
     */
    private $childParamPopulator;
    /**
     * @var \Rector\TypeDeclaration\TypeInferer\ParamTypeInferer
     */
    private $paramTypeInferer;
    /**
     * @var \Rector\TypeDeclaration\NodeTypeAnalyzer\TraitTypeAnalyzer
     */
    private $traitTypeAnalyzer;
    /**
     * @var \Rector\DeadCode\PhpDoc\TagRemover\ParamTagRemover
     */
    private $paramTagRemover;
    public function __construct(\Rector\VendorLocker\VendorLockResolver $vendorLockResolver, \Rector\TypeDeclaration\ChildPopulator\ChildParamPopulator $childParamPopulator, \Rector\TypeDeclaration\TypeInferer\ParamTypeInferer $paramTypeInferer, \Rector\TypeDeclaration\NodeTypeAnalyzer\TraitTypeAnalyzer $traitTypeAnalyzer, \Rector\DeadCode\PhpDoc\TagRemover\ParamTagRemover $paramTagRemover)
    {
        $this->vendorLockResolver = $vendorLockResolver;
        $this->childParamPopulator = $childParamPopulator;
        $this->paramTypeInferer = $paramTypeInferer;
        $this->traitTypeAnalyzer = $traitTypeAnalyzer;
        $this->paramTagRemover = $paramTagRemover;
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [\PhpParser\Node\Stmt\Function_::class, \PhpParser\Node\Stmt\ClassMethod::class];
    }
    public function getRuleDefinition() : \Symplify\RuleDocGenerator\ValueObject\RuleDefinition
    {
        return new \Symplify\RuleDocGenerator\ValueObject\RuleDefinition('Change @param types to type declarations if not a BC-break', [new \Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample(<<<'CODE_SAMPLE'
class ParentClass
{
    /**
     * @param int $number
     */
    public function keep($number)
    {
    }
}

final class ChildClass extends ParentClass
{
    /**
     * @param int $number
     */
    public function keep($number)
    {
    }

    /**
     * @param int $number
     */
    public function change($number)
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class ParentClass
{
    /**
     * @param int $number
     */
    public function keep($number)
    {
    }
}

final class ChildClass extends ParentClass
{
    /**
     * @param int $number
     */
    public function keep($number)
    {
    }

    public function change(int $number)
    {
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @param ClassMethod|Function_ $node
     * @return \PhpParser\Node|null
     */
    public function refactor(\PhpParser\Node $node)
    {
        if (!$this->isAtLeastPhpVersion(\Rector\Core\ValueObject\PhpVersionFeature::SCALAR_TYPES)) {
            return null;
        }
        if ($node->params === []) {
            return null;
        }
        foreach ($node->params as $position => $param) {
            $this->refactorParam($param, $node, (int) $position);
        }
        return null;
    }
    /**
     * @param ClassMethod|Function_ $functionLike
     * @return void
     */
    private function refactorParam(\PhpParser\Node\Param $param, \PhpParser\Node\FunctionLike $functionLike, int $position)
    {
        if ($this->shouldSkipParam($param, $functionLike)) {
            return;
        }
        $inferedType = $this->paramTypeInferer->inferParam($param);
        if ($inferedType instanceof \PHPStan\Type\MixedType) {
            return;
        }
        if ($this->traitTypeAnalyzer->isTraitType($inferedType)) {
            return;
        }
        $paramTypeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($inferedType, \Rector\PHPStanStaticTypeMapper\ValueObject\TypeKind::KIND_PARAM);
        if (!$paramTypeNode instanceof \PhpParser\Node) {
            return;
        }
        $parentNode = $functionLike->getAttribute(\Rector\NodeTypeResolver\Node\AttributeKey::PARENT_NODE);
        if ($parentNode instanceof \PhpParser\Node\Stmt\Interface_ && $parentNode->extends !== []) {
            return;
        }
        $param->type = $paramTypeNode;
        $functionLikePhpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($functionLike);
        $this->paramTagRemover->removeParamTagsIfUseless($functionLikePhpDocInfo, $functionLike);
        $this->childParamPopulator->populateChildClassMethod($functionLike, $position, $inferedType);
    }
    private function shouldSkipParam(\PhpParser\Node\Param $param, \PhpParser\Node\FunctionLike $functionLike) : bool
    {
        if ($param->variadic) {
            return \true;
        }
        if ($this->vendorLockResolver->isClassMethodParamLockedIn($functionLike)) {
            return \true;
        }
        // no type → check it
        if ($param->type === null) {
            return \false;
        }
        // already set → skip
        return !$param->type->getAttribute(\Rector\TypeDeclaration\ValueObject\NewType::HAS_NEW_INHERITED_TYPE, \false);
    }
}

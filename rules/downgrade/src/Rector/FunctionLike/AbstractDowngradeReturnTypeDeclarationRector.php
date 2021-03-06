<?php

declare(strict_types=1);

namespace Rector\Downgrade\Rector\FunctionLike;

use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\Core\Contract\Rector\ConfigurableRectorInterface;
use Rector\Downgrade\Contract\Rector\DowngradeRectorInterface;
use Rector\Downgrade\Contract\Rector\DowngradeTypeRectorInterface;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\TypeDeclaration\Rector\FunctionLike\AbstractTypeDeclarationRector;

abstract class AbstractDowngradeReturnTypeDeclarationRector extends AbstractTypeDeclarationRector implements ConfigurableRectorInterface, DowngradeRectorInterface, DowngradeTypeRectorInterface
{
    /**
     * @var string
     */
    public const ADD_DOC_BLOCK = '$addDocBlock';

    /**
     * @var bool
     */
    private $addDocBlock = true;

    /**
     * @param ClassMethod|Function_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->isAtLeastPhpVersion($this->getPhpVersionFeature())) {
            return $node;
        }

        if ($this->shouldSkip($node)) {
            return null;
        }

        if ($this->addDocBlock) {
            /** @var PhpDocInfo|null $phpDocInfo */
            $phpDocInfo = $node->getAttribute(AttributeKey::PHP_DOC_INFO);
            if ($phpDocInfo === null) {
                $phpDocInfo = $this->phpDocInfoFactory->createEmpty($node);
            }

            if ($node->returnType !== null) {
                $type = $this->staticTypeMapper->mapPhpParserNodePHPStanType($node->returnType);
                $phpDocInfo->changeReturnType($type);
            }
        }

        $node->returnType = null;

        return $node;
    }

    public function configure(array $configuration): void
    {
        $this->addDocBlock = $configuration[self::ADD_DOC_BLOCK] ?? true;
    }

    protected function getRectorDefinitionDescription(): string
    {
        return sprintf("Remove the '%s' function type, add a @return tag instead", $this->getTypeNameToRemove());
    }

    /**
     * @param ClassMethod|Function_ $functionLike
     */
    private function shouldSkip(FunctionLike $functionLike): bool
    {
        if ($functionLike->returnType === null) {
            return true;
        }

        // It can either be the type, or the nullable type (eg: ?object)
        $isNullableType = $functionLike->returnType instanceof NullableType;
        if ($isNullableType) {
            /** @var NullableType */
            $nullableType = $functionLike->returnType;
            $typeName = $this->getName($nullableType->type);
        } else {
            $typeName = $this->getName($functionLike->returnType);
        }

        // Check it is the type to be removed
        return $typeName !== $this->getTypeNameToRemove();
    }
}

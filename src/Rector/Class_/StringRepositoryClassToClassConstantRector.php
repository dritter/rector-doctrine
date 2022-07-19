<?php

declare(strict_types=1);

namespace Rector\Doctrine\Rector\Class_;

use Doctrine\ORM\Mapping\Entity;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\BetterPhpDocParser\PhpDocParser\ClassAnnotationMatcher;
use Rector\Core\Rector\AbstractRector;
use Rector\Doctrine\NodeAnalyzer\AttributeFinder;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\Doctrine\Tests\Rector\Class_\StringRepositoryClassToClassConstantRectorTest\StringRepositoryClassToClassConstantRectorTest
 */
final class StringRepositoryClassToClassConstantRector extends AbstractRector
{
    private const ATTRIBUTE_NAME__REPOSITORY_CLASS = 'repositoryClass';

    /**
     * @var class-string<Entity>
     */
    private const VALID_DOCTRINE_CLASS = 'Doctrine\ORM\Mapping\Entity';

    public function __construct(
        private readonly ClassAnnotationMatcher $classAnnotationMatcher,
        private readonly AttributeFinder $attributeFinder
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert repositoryClass defined as String to <class>::class Constants in Doctrine Entities.',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
/**
 * @ORM\Entity(repositoryClass="App\Repository\SomeRepository")
 */
final class SomeClass
{
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
/**
 * @ORM\Entity(repositoryClass=SomeRepository::class)
 */
final class SomeClass
{
}
CODE_SAMPLE
                ),

            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = false;
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if ($phpDocInfo !== null) {
            $class = $this->changeTypeInAnnotationTypes($node, $phpDocInfo);
            $hasChanged = $class !== null || $phpDocInfo->hasChanged();
        }

        return $this->changeTypeInAttributeTypes($node, $hasChanged);
    }

    private function changeTypeInAttributeTypes(Class_ $class, bool $hasChanged): ?Class_
    {
        $attribute = $this->attributeFinder->findAttributeByClasses($class, [self::VALID_DOCTRINE_CLASS]);

        if (! $attribute instanceof Attribute) {
            return $hasChanged ? $class : null;
        }

        $attributeName = self::ATTRIBUTE_NAME__REPOSITORY_CLASS;
        foreach ($attribute->args as $arg) {
            $argName = $arg->name;
            if (! $argName instanceof Identifier) {
                continue;
            }

            if (! $this->isName($argName, $attributeName)) {
                continue;
            }

            /** @var string $value - Should always be string at this point */
            $value = $this->valueResolver->getValue($arg->value);
            $fullyQualified = $this->classAnnotationMatcher->resolveTagToKnownFullyQualifiedName($value, $class);

            if ($fullyQualified === $value) {
                // Doctrine FQCNs are strange: In their examples
                // they omit the leading slash. This leads to
                // ClassAnnotationMatcher searching in the wrong
                // namespace. Therefor we try to add the leading
                // slash manually here.
                $fullyQualified = $this->classAnnotationMatcher->resolveTagToKnownFullyQualifiedName(
                    "/${value}",
                    $class
                );
            }

            if ($fullyQualified === $value) {
                continue;
            }

            // Skip unknown classes
            if ($fullyQualified === null) {
                continue;
            }

            $arg->value = $this->nodeFactory->createClassConstFetch($fullyQualified, 'class');

            return $class;
        }

        return $hasChanged ? $class : null;
    }

    private function changeTypeInAnnotationTypes(Class_ $class, PhpDocInfo $phpDocInfo): ?Class_
    {
        $doctrineAnnotationTagValueNode = $phpDocInfo->getByAnnotationClasses([self::VALID_DOCTRINE_CLASS]);

        if (! $doctrineAnnotationTagValueNode instanceof DoctrineAnnotationTagValueNode) {
            return null;
        }

        return $this->processDoctrineAnnotation($doctrineAnnotationTagValueNode, $class);
    }

    private function processDoctrineAnnotation(
        DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode,
        Class_ $class
    ): ?Class_ {
        $key = self::ATTRIBUTE_NAME__REPOSITORY_CLASS;

        /** @var ?string $repositoryClass */
        $repositoryClass = $doctrineAnnotationTagValueNode->getValueWithoutQuotes($key);
        if ($repositoryClass === null) {
            return null;
        }

        // resolve to FQN
        $tagFullyQualifiedName = $this->classAnnotationMatcher->resolveTagToKnownFullyQualifiedName(
            $repositoryClass,
            $class
        );

        // Detect unknown classes
        if ($tagFullyQualifiedName === $repositoryClass) {
            return null;
        }

        $doctrineAnnotationTagValueNode->removeValue($key);
        $doctrineAnnotationTagValueNode->values[$key] = '\\' . $tagFullyQualifiedName . '::class';

        return $class;
    }
}

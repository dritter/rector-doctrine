<?php

declare(strict_types=1);

namespace Rector\Doctrine\NodeAnalyzer;

use PhpParser\Node\Stmt\Class_;
use Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Rector\Doctrine\ValueObject\PropertyNameAndPhpDocInfo;
use Rector\Doctrine\ValueObject\PropertyNamesAndPhpDocInfos;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeRemoval\NodeRemover;

final class TranslatablePropertyCollectorAndRemover
{
    public function __construct(
        private readonly PhpDocTagRemover $phpDocTagRemover,
        private readonly PhpDocInfoFactory $phpDocInfoFactory,
        private readonly NodeRemover $nodeRemover,
        private readonly NodeNameResolver $nodeNameResolver
    ) {
    }

    public function processClass(Class_ $class): PropertyNamesAndPhpDocInfos
    {
        $propertyNameAndPhpDocInfos = [];

        foreach ($class->getProperties() as $property) {
            $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);

            if ($phpDocInfo->hasByAnnotationClass('Gedmo\Mapping\Annotation\Locale')) {
                $this->nodeRemover->removeNode($property);
                continue;
            }

            $doctrineAnnotationTagValueNode = $phpDocInfo->getByAnnotationClass(
                'Gedmo\Mapping\Annotation\Translatable'
            );
            if (! $doctrineAnnotationTagValueNode instanceof DoctrineAnnotationTagValueNode) {
                continue;
            }

            $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $doctrineAnnotationTagValueNode);

            $propertyName = $this->nodeNameResolver->getName($property);
            $propertyNameAndPhpDocInfos[] = new PropertyNameAndPhpDocInfo($propertyName, $phpDocInfo);

            $this->nodeRemover->removeNode($property);
        }

        return new PropertyNamesAndPhpDocInfos($propertyNameAndPhpDocInfos);
    }
}

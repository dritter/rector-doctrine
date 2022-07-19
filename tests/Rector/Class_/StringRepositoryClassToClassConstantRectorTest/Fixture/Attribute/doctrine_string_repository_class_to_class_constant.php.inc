<?php

namespace Rector\Doctrine\Tests\Rector\Class_\StringRepositoryClassToClassConstantRectorTest\Fixture\Attribute;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "Rector\Doctrine\Tests\Rector\Class_\StringRepositoryClassToClassConstantRectorTest\Source\SomeRepository")]
final class MyEntity
{
}

?>
-----
<?php

namespace Rector\Doctrine\Tests\Rector\Class_\StringRepositoryClassToClassConstantRectorTest\Fixture\Attribute;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \Rector\Doctrine\Tests\Rector\Class_\StringRepositoryClassToClassConstantRectorTest\Source\SomeRepository::class)]
final class MyEntity
{
}

?>

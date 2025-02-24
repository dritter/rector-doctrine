<?php

namespace Rector\Doctrine\Tests\Rector\Property\TypedPropertyFromColumnTypeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;

class ColumnNotNullable
{
    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private $surname;
}

?>
-----
<?php

namespace Rector\Doctrine\Tests\Rector\Property\TypedPropertyFromColumnTypeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;

class ColumnNotNullable
{
    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $surname;
}

?>

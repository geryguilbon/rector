<?php

namespace Rector\Downgrade\Tests\Rector\Property\NoDocBlockDowngradeTypedPropertyRector\Fixture;

class DocBlockExists {
    /**
     * This property is the best one
     */
    private ?string $property;
}

?>
-----
<?php

namespace Rector\Downgrade\Tests\Rector\Property\NoDocBlockDowngradeTypedPropertyRector\Fixture;

class DocBlockExists {
    /**
     * This property is the best one
     */
    private $property;
}

?>

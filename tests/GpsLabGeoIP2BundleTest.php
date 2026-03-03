<?php
declare(strict_types=1);

namespace Huluti\GeoIP2Bundle\Tests;

use Huluti\GeoIP2Bundle\DependencyInjection\HulutiGeoIP2Extension;
use Huluti\GeoIP2Bundle\HulutiGeoIP2Bundle;
use PHPUnit\Framework\TestCase;

class HulutiGeoIP2BundleTest extends TestCase
{
    public function testGetContainerExtension(): void
    {
        $bundle = new HulutiGeoIP2Bundle();
        $extension = $bundle->getContainerExtension();

        $this->assertInstanceOf(HulutiGeoIP2Extension::class, $extension);

        // test laze-load
        $extension2 = $bundle->getContainerExtension();
        $this->assertSame($extension, $extension2);
    }
}

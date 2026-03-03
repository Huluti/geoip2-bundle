<?php
declare(strict_types=1);

namespace Huluti\GeoIP2Bundle;

use Huluti\GeoIP2Bundle\DependencyInjection\HulutiGeoIP2Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HulutiGeoIP2Bundle extends Bundle
{
    /**
     * @return ExtensionInterface|null
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new HulutiGeoIP2Extension();
        }

        return $this->extension ?: null;
    }
}

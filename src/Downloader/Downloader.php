<?php
declare(strict_types=1);

namespace Huluti\GeoIP2Bundle\Downloader;

interface Downloader
{
    /**
     * @param string $url
     * @param string $target
     */
    public function download(string $url, string $target): void;
}

<?php
declare(strict_types=1);

namespace Huluti\GeoIP2Bundle\Tests\Reader;

use GeoIp2\Database\Reader;
use MaxMind\Db\Reader\InvalidDatabaseException;

/**
 * Test class for test the initialization of a Reader object without reading the database file.
 */
class TestReader extends Reader
{
    public function __construct(public string $filename, array $locales = ['en'])
    {
        try {
            // Attempt to call the real constructor
            parent::__construct($this->filename, $locales);
        } catch (InvalidDatabaseException) {
            // Ignore DB errors — test just wants to instantiate
        }
    }
}

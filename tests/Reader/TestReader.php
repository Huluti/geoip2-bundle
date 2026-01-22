<?php
declare(strict_types=1);

/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\Tests\Reader;

use GeoIp2\Database\Reader;
use MaxMind\Db\Reader\InvalidDatabaseException;

/**
 * Test class for test the initialization of a Reader object without reading the database file.
 */
class TestReader extends Reader
{
    public string $filename;

    public function __construct(string $filename, array $locales = ['en'])
    {
        $this->filename = $filename;

        try {
            // Attempt to call the real constructor
            parent::__construct($filename, $locales);
        } catch (InvalidDatabaseException $e) {
            // Ignore DB errors — test just wants to instantiate
        }
    }
}

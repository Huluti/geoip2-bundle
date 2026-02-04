<?php
declare(strict_types=1);

/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private const URL = 'https://download.maxmind.com/app/geoip_download?edition_id=%s&license_key=%s&suffix=tar.gz';

    private const PATH = '%s/%s.mmdb';

    private const LICENSE_DIRTY_HACK = 'YOUR-LICENSE-KEY';

    private const DATABASE_EDITION_IDS = [
        'GeoLite2-ASN',
        'GeoLite2-City',
        'GeoLite2-Country',
        'GeoIP2-City',
        'GeoIP2-Country',
        'GeoIP2-Anonymous-IP',
        'GeoIP2-Domain',
        'GeoIP2-ISP',
    ];

    /**
     * @var string
     */
    private $cache_dir;

    /**
     * @param string|null $cache_dir
     */
    public function __construct(?string $cache_dir)
    {
        $this->cache_dir = $cache_dir ?: sys_get_temp_dir();
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree_builder = new TreeBuilder('gpslab_geoip');
        $root_node = $tree_builder->getRootNode();

        $this->normalizeDefaultDatabase($root_node);
        $this->normalizeRootConfigurationToDefaultDatabase($root_node);
        $this->normalizeLicenseDirtyHack($root_node);
        $this->validateAvailableDefaultDatabase($root_node);
        $this->allowGlobalLicense($root_node);
        $this->allowGlobalLocales($root_node);
        $this->validateDatabases($root_node);

        $root_node->fixXmlConfig('locale');
        $locales = $root_node->children()->arrayNode('locales');
        $locales->prototype('scalar');
        $locales->treatNullLike([]);
        $locales->defaultValue(['en']);

        $root_node->children()->scalarNode('license');

        $default_database = $root_node->children()->scalarNode('default_database');
        $default_database->defaultValue('default');

        $root_node->fixXmlConfig('database');
        $root_node->append($this->getDatabaseNode());

        return $tree_builder;
    }

    private function getDatabaseNode(): ArrayNodeDefinition
    {
        $tree_builder = new TreeBuilder('databases');
        $root_node = $tree_builder->getRootNode();
        $root_node->useAttributeAsKey('name');

        $database_node = $root_node->arrayPrototype();

        $this->normalizeUrl($database_node);
        $this->normalizePath($database_node);

        $url = $database_node->children()->scalarNode('url');
        $url->isRequired();

        $this->validateURL($url);

        $path = $database_node->children()->scalarNode('path');
        $path->isRequired();

        $database_node->fixXmlConfig('locale');
        $locales = $database_node->children()->arrayNode('locales');
        $locales->prototype('scalar');
        $locales->treatNullLike([]);
        $locales->defaultValue(['en']);

        $database_node->children()->scalarNode('license');

        $database_node->children()->enumNode('edition')->values(self::DATABASE_EDITION_IDS);

        return $root_node;
    }

    /**
     * Normalize default_database from databases.
     */
    private function normalizeDefaultDatabase(NodeDefinition $root_node): void
    {
        $root_node
            ->beforeNormalization()
            ->ifTrue(static fn ($v): bool => is_array($v)
            && !array_key_exists('default_database', $v)
            && !empty($v['databases'])
            && is_array($v['databases']))
            ->then(static function (array $v): array {
                $keys = array_keys($v['databases']);
                $v['default_database'] = reset($keys);

                return $v;
            });
    }

    private function normalizeRootConfigurationToDefaultDatabase(NodeDefinition $root_node): void
    {
        $root_node
            ->beforeNormalization()
            ->ifTrue(static fn ($v): bool => $v && is_array($v) && !array_key_exists('databases', $v) && !array_key_exists('database', $v))
            ->then(static function (array $v): array {
                $database = $v;
                unset($database['default_database']);
                $default_database = isset($v['default_database']) ? (string) $v['default_database'] : 'default';

                return [
                    'default_database' => $default_database,
                    'databases' => [
                        $default_database => $database,
                    ],
                ];
            });
    }

    /**
     * Dirty hack for Symfony Flex.
     *
     * @see https://github.com/symfony/recipes-contrib/pull/837
     */
    private function normalizeLicenseDirtyHack(NodeDefinition $root_node): void
    {
        $root_node
            ->beforeNormalization()
            ->ifTrue(static fn ($v): bool => $v && is_array($v) && array_key_exists('databases', $v) && is_array($v['databases']))
            ->then(static function (array $v): array {
                foreach ($v['databases'] as $name => $database) {
                    if (isset($database['license']) && $database['license'] === self::LICENSE_DIRTY_HACK) {
                        unset($v['databases'][$name]);
                        @trigger_error(sprintf('License for downloaded database "%s" is not specified.', $name), E_USER_WARNING);
                    }
                }

                return $v;
            });
    }

    /**
     * Validate that the default_database exists in the list of databases.
     */
    private function validateAvailableDefaultDatabase(NodeDefinition $root_node): void
    {
        $root_node
            ->validate()
            ->ifTrue(static fn ($v): bool => is_array($v)
            && array_key_exists('default_database', $v)
            && !empty($v['databases'])
            && !array_key_exists($v['default_database'], $v['databases']))
            ->then(static function (array $v): array {
                $databases = implode('", "', array_keys($v['databases']));

                throw new \InvalidArgumentException(sprintf('Undefined default database "%s". Available "%s" databases.', $v['default_database'], $databases));
            });
    }

    /**
     * Add a license option to the databases configuration if it does not exist.
     * Allow use a global license for all databases.
     */
    private function allowGlobalLicense(NodeDefinition $root_node): void
    {
        $root_node
            ->beforeNormalization()
            ->ifTrue(static fn ($v): bool => is_array($v)
            && array_key_exists('license', $v)
            && array_key_exists('databases', $v)
            && is_array($v['databases']))
            ->then(static function (array $v): array {
                foreach ($v['databases'] as $name => $database) {
                    if (!array_key_exists('license', $database)) {
                        $v['databases'][$name]['license'] = $v['license'];
                    }
                }

                return $v;
            });
    }

    /**
     * Add a locales option to the databases configuration if it does not exist.
     * Allow use a global locales for all databases.
     */
    private function allowGlobalLocales(NodeDefinition $root_node): void
    {
        $root_node
            ->beforeNormalization()
            ->ifTrue(static fn ($v): bool => is_array($v)
            && array_key_exists('locales', $v)
            && array_key_exists('databases', $v)
            && is_array($v['databases']))
            ->then(static function (array $v): array {
                foreach ($v['databases'] as $name => $database) {
                    if (!array_key_exists('locales', $database)) {
                        $v['databases'][$name]['locales'] = $v['locales'];
                    }
                }

                return $v;
            });
    }

    /**
     * Validate database options.
     */
    private function validateDatabases(NodeDefinition $root_node): void
    {
        $root_node
            ->validate()
            ->ifTrue(static fn ($v): bool => is_array($v) && array_key_exists('databases', $v) && is_array($v['databases']))
            ->then(static function (array $v): array {
                foreach ($v['databases'] as $name => $database) {
                    if (empty($database['license'])) {
                        throw new \InvalidArgumentException(sprintf('License for downloaded database "%s" is not specified.', $name));
                    }

                    if (empty($database['edition'])) {
                        throw new \InvalidArgumentException(sprintf('Edition of downloaded database "%s" is not selected.', $name));
                    }

                    if (empty($database['url'])) {
                        throw new \InvalidArgumentException(sprintf('URL for download database "%s" is not specified.', $name));
                    }

                    if (empty($database['path'])) {
                        throw new \InvalidArgumentException(sprintf('The destination path to download database "%s" is not specified.', $name));
                    }

                    if (empty($database['locales'])) {
                        throw new \InvalidArgumentException(sprintf('The list of locales for database "%s" should not be empty.', $name));
                    }
                }

                return $v;
            });
    }

    /**
     * Normalize url option from license key and edition id.
     */
    private function normalizeUrl(NodeDefinition $database_node): void
    {
        $database_node
            ->beforeNormalization()
            ->ifTrue(static fn ($v): bool => is_array($v)
            && !array_key_exists('url', $v)
            && array_key_exists('license', $v)
            && array_key_exists('edition', $v))
            ->then(static function (array $v): array {
                $v['url'] = sprintf(self::URL, urlencode((string) $v['edition']), urlencode((string) $v['license']));

                return $v;
            });
    }

    /**
     * Normalize path option from edition id.
     */
    private function normalizePath(NodeDefinition $database_node): void
    {
        $database_node
            ->beforeNormalization()
            ->ifTrue(static fn ($v): bool => is_array($v) && !array_key_exists('path', $v) && array_key_exists('edition', $v))
            ->then(function (array $v): array {
                $v['path'] = sprintf(self::PATH, $this->cache_dir, $v['edition']);

                return $v;
            });
    }

    /**
     * The url option must be a valid URL.
     */
    private function validateURL(NodeDefinition $url): void
    {
        $url
            ->validate()
            ->ifTrue(static fn ($v): bool => is_string($v) && $v && !filter_var($v, FILTER_VALIDATE_URL))
            ->then(static function (string $v): array {
                throw new \InvalidArgumentException(sprintf('URL "%s" must be valid.', $v));
            });
    }
}

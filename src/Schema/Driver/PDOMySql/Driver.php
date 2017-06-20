<?php
/**
 * This file is part of Schema.
 *
 * (c) Axel Etcheverry <axel@etcheverry.biz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @namespace
 */
namespace Schema\Driver\PDOMySql;

use Doctrine\DBAL\Driver\PDOMySql\Driver as BaseDriver;

class Driver extends BaseDriver
{
    public function getDatabasePlatform()
    {
        return new \Schema\Platform\MySqlPlatform();
    }

    public function createDatabasePlatformForVersion($version)
    {
        if (false !== stripos($version, 'mariadb')) {
            return $this->getDatabasePlatform();
        }

        $majorVersion = $versionParts['major'];
        $minorVersion = isset($versionParts['minor']) ? $versionParts['minor'] : 0;
        $patchVersion = isset($versionParts['patch']) ? $versionParts['patch'] : 0;
        $version      = $majorVersion . '.' . $minorVersion . '.' . $patchVersion;

        if (version_compare($version, '5.7', '>=')) {
            return new \Schema\Platform\MySQL57Platform();
        }

        return $this->getDatabasePlatform();
    }
}

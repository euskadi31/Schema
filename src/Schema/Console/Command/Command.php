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
namespace Schema\Console\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;

use Schema\Config;
use Schema\DriverManager;
use Doctrine;
use Doctrine\DBAL\Types\Type;

class Command extends BaseCommand
{
    public function getDb(Config $config)
    {
        Type::addType('enum', '\Schema\Type\EnumType');
        Type::addType('varchar', '\Schema\Type\VarcharType');
        Type::addType('char', '\Schema\Type\CharType');
        Type::addType('timestamp', '\Schema\Type\TimestampType');
        Type::addType('tinyint', '\Schema\Type\TinyintType');
        Type::addType('mediumint', '\Schema\Type\MediumIntType');
        Type::addType('tinytext', '\Schema\Type\TinyTextType');
        Type::addType('mediumtext', '\Schema\Type\MediumTextType');

        $connection = DriverManager::getConnection(
            $config['config'], 
            new Doctrine\DBAL\Configuration()
        );

        $config->setConnection($connection);

        return $connection;
    }
}
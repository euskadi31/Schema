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
namespace Schema\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\SmallIntType;

class TinyintType extends SmallIntType
{
    public function getName()
    {
        return 'tinyint';
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        if(method_exists($platform,'getTinyIntTypeDeclarationSQL')) {
            return $platform->getTinyIntTypeDeclarationSQL($fieldDeclaration);
        } else {
            return $platform->getSmallIntTypeDeclarationSQL($fieldDeclaration);
        }
    }
}

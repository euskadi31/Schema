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
use Doctrine\DBAL\Types\Type;

class EnumType extends Type
{
    /** @override */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'ENUM(' . $this->getSQLEnumDeclarationValues($fieldDeclaration['value']) . ')';
    }

    /** @override */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    /** @override */
    public function getBindingType()
    {
        return \PDO::PARAM_STR;
    }
    
    /** @override */
    protected function getSQLEnumDeclarationValues(array $values)
    {
        return "'" . implode("','", $values) . "'";
    }

    /** @override */
    public function getName()
    {
        return "enum";
    }
}
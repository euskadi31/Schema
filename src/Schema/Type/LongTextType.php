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
use Doctrine\DBAL\Types\StringType;

class MediumTextType extends StringType
{
    /** @override */
    public function getName()
    {
        return 'longtext';
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getLongTextTypeDeclarationSQL($fieldDeclaration);
    }
}

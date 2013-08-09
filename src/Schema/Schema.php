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
namespace Schema;

use SplFileInfo;

/**
 * 
 * @author Axel Etcheverry <axel@etcheverry.biz>
 */
class Schema
{
    const VERSION = '@package_version@';

    /**
     * Get schema file
     *
     * @param  string $file
     * @return SplFileInfo|null
     */
    public static function getFile($file = null)
    {
        if (empty($file)) {
            foreach (["json", "yml"] as $format) {
                if (file_exists("./schema." . $format)) {
                    return new SplFileInfo("./schema." . $format);
                }
            }
        } else {
            if (file_exists($file)) {
                return new SplFileInfo($file);
            }
        }

        return null;
    }
}

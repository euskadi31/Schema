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
namespace Schema\Console;


use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatter;

use Schema\Schema;

/**
 * 
 * @author Axel Etcheverry <axel@etcheverry.biz>
 */
class Application extends BaseApplication
{
    private static $logo = '   _____      __                        
  / ___/_____/ /_  ___  ____ ___  ____ _
  \__ \/ ___/ __ \/ _ \/ __ `__ \/ __ `/
 ___/ / /__/ / / /  __/ / / / / / /_/ / 
/____/\___/_/ /_/\___/_/ /_/ /_/\__,_/

';

    public function __construct()
    {
        if (function_exists('ini_set')) {
            ini_set('xdebug.show_exception_trace', false);
            ini_set('xdebug.scream', false);
        }

        if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
            date_default_timezone_set(@date_default_timezone_get());
        }

        //ErrorHandler::register();
        parent::__construct('Schema', Schema::VERSION);
    }


    public function getHelp()
    {
        return self::$logo . parent::getHelp();
    }

    /**
     * Initializes all the composer commands
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        $commands[] = new Command\CreateCommand();
        $commands[] = new Command\UpdateCommand();
        $commands[] = new Command\DumpCommand();
        
        /*if ('phar:' === substr(__FILE__, 0, 5)) {
            $commands[] = new Command\SelfUpdateCommand();
        }*/

        return $commands;
    }
}
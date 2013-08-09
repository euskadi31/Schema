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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Schema\Schema;
use Schema\Config;
use RuntimeException;

use Doctrine;

/**
 * 
 * @author Axel Etcheverry <axel@etcheverry.biz>
 */
class DumpCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('dump')
            ->setDescription('Create the sql schema to match the current mapping metadata.')
            ->addArgument(
                'schema',
                InputArgument::OPTIONAL,
                'Schema file.'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Format for dump file. (json, yml, sql)',
                'json'
            )
            ->setHelp(<<<EOT
The <info>%command.name%</info> command reads the schema.json file from
the current directory, processes it, and generate all files.

<info>php schema.phar %command.name%</info>

EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $schema_file = Schema::getFile($input->getArgument('schema'));
        $format = $input->getOption('format');

        if (empty($schema_file)) {
            throw new RuntimeException("Schema file not found.");
        }

        $config = new Config($schema_file);

        $db = $this->getDb($config);

        $config->loadSchema();

        $output->write('Dumping database schema...' . PHP_EOL);
        $config->save($format);
        $output->write('<info>Database schema dumped successfully!</info>' . PHP_EOL);
    }
}

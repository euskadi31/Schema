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
class CreateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('create')
            ->setDescription('Create the sql schema to match the current mapping metadata.')
            ->addArgument(
                'schema',
                InputArgument::OPTIONAL,
                'Schema file.'
            )
            ->addOption(
                'dump-sql',
                null,
                InputOption::VALUE_NONE,
                'Dumps the generated SQL statements to the screen (does not execute them).'
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
        
        if (empty($schema_file)) {
            throw new RuntimeException("Schema file not found.");
        }

        $config = new Config($schema_file);

        $db = $this->getDb($config);

        $schema = $config->getSchema();

        $sqls = $schema->toSql($db->getDatabasePlatform());

        if ($input->getOption('dump-sql') === true) {
            $output->write(implode(';' . PHP_EOL, $sqls) . ';' . PHP_EOL);
        } else {
            $output->write('ATTENTION: This operation should not be executed in a production environment.' . PHP_EOL . PHP_EOL);

            $output->write('Creating database schema...' . PHP_EOL);
            foreach ($sqls as $sql) {
                $db->exec($sql);
            }
            $output->write('<info>Database schema created successfully!</info>' . PHP_EOL);
        }
    }
}

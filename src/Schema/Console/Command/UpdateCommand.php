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
class UpdateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Executes (or dumps) the SQL needed to update the database schema to match the current mapping metadata.')
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
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Causes the generated SQL statements to be physically executed against your database.'
            )
            ->setHelp(<<<EOT
The <info>update</info> command reads the schema.json file from
the current directory, processes it, and generate all files.

<info>%command.name% --dump-sql</info>

<info>%command.name% --force</info>

EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $schema_file = Schema::getFile($input->getArgument('schema'));
        $dumpSql = (true === $input->getOption('dump-sql'));
        $force = (true === $input->getOption('force'));

        if (empty($schema_file)) {
            throw new RuntimeException("Schema file not found.");
        }

        $config = new Config($schema_file);

        $db = $this->getDb($config);
        
        $toSchema = $config->getSchema($db);

        $comparator = new \Doctrine\DBAL\Schema\Comparator();
        $schemaDiff = $comparator->compare($db->getSchemaManager($db)->createSchema(), $toSchema);

        $sqls = $schemaDiff->toSql($db->getDatabasePlatform());

        if (0 == count($sqls)) {
            $output->writeln('Nothing to update - your database is already in sync with the current entity metadata.');

            return;
        }

        if ($dumpSql && $force) {
            throw new \InvalidArgumentException('You can pass either the --dump-sql or the --force option (but not both simultaneously).');
        }

        if ($dumpSql) {
            $output->writeln(implode(';' . PHP_EOL, $sqls));
        } elseif ($force) {
            $output->writeln('Updating database schema...');

            foreach ($schemaDiff->toSql($db->getDatabasePlatform()) as $sql) {
                $db->exec($sql);
            }

            $output->writeln(sprintf('Database schema updated successfully! "<info>%s</info>" queries were executed', count($sqls)));

        } else {
            $output->writeln('<comment>ATTENTION</comment>: This operation should not be executed in a production environment.');
            $output->writeln('           Use the incremental update to detect changes during development and use');
            $output->writeln('           the SQL provided to manually update your database in production.');
            $output->writeln('');

            $output->writeln(sprintf('The Schema-Tool would execute <info>"%s"</info> queries to update the database.', count($sqls)));
            $output->writeln('Please run the operation by passing one of the following options:');

            $output->writeln(sprintf('    <info>%s --force</info> to execute the command', $this->getName()));
            $output->writeln(sprintf('    <info>%s --dump-sql</info> to dump the SQL statements to the screen', $this->getName()));
        }
    }
}
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

use ArrayObject;
use RuntimeException;
use SplFileInfo;
use DirectoryIterator;

use Symfony\Component\Yaml\Yaml;

use Doctrine\DBAL\Schema\Schema as DoctrineSchema;
use Doctrine\DBAL\Connection;

/**
 *
 * @author Axel Etcheverry <axel@etcheverry.biz>
 */
class Config extends ArrayObject
{
    /**
     *
     * @var SplFileInfo
     */
    protected $file;

    /**
     *
     * @var string
     */
    protected $path;

    /**
     *
     * @var Doctrine\DBAL\Connection;
     */
    protected $connection;

    /**
     *
     * @var array
     */
    protected $extensions = array(
        'json',
        'yml',
        'yaml'
    );

    /**
     *
     * @param SplFileInfo $file
     * @throws RuntimeException
     */
    public function __construct(SplFileInfo $file = null)
    {
        if (!empty($file)) {
            if (!$file->isFile()) {
                throw new RuntimeException(sprintf("File %s does not exists.", $file));
            }

            if (!$file->isReadable()) {
                throw new RuntimeException(sprintf("Unable to parse \"%s\" as the file is not readable.", $file));
            }

            $schema = $this->_loadFile($file);

            $this->file = $file;

            if (isset($schema["schemas"]) && is_string($schema["schemas"])) {
                $this->path = $schema["schemas"];
                $schema["schemas"] = array();
            }

            parent::__construct($this->_process($schema));
        }
    }

    public function getPath()
    {
        if ($this->path[0] != '/') {
            return $this->file->getPathInfo()->getRealPath() . "/" . $this->path;
        }

        return $this->path;
    }

    protected function _loadFile(SplFileInfo $file)
    {
        $content = file_get_contents($file->getPathname());

        if (empty($content)) {
            throw new RuntimeException(sprintf("File %s is empty", $file));
        }

        switch ($file->getExtension()) {
            case 'json':
                $data = json_decode(
                    $content,
                    true
                );

                if (version_compare(PHP_VERSION, '5.5.0', '>=')) {
                     if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new RuntimeException(json_last_error_msg());
                    }
                } else {
                    switch (json_last_error()) {
                        case JSON_ERROR_NONE:
                            break;
                        case JSON_ERROR_DEPTH:
                            throw new RuntimeException('Maximum stack depth exceeded');
                        case JSON_ERROR_STATE_MISMATCH:
                            throw new RuntimeException('Underflow or the modes mismatch');
                        case JSON_ERROR_CTRL_CHAR:
                            throw new RuntimeException('Unexpected control character found');
                        case JSON_ERROR_SYNTAX:
                            throw new RuntimeException('Syntax error');
                        case JSON_ERROR_UTF8:
                            throw new RuntimeException('Malformed UTF-8 characters, possibly incorrectly encoded');
                        default:
                            throw new RuntimeException('Decoding failed');
                    }
                }

                return $data;
                break;
            case 'yml':
            case 'yaml':
                return Yaml::parse($content);
                break;
            default:
                throw new RuntimeException("File format is not supported.");
                break;
        }
    }

    protected function _process(array $schema)
    {
        if (!empty($this->path)) {

            if (!is_dir($this->getPath())) {
                mkdir($this->getPath(), 0777, true);
            }

            foreach (new DirectoryIterator($this->getPath()) as $file) {
                if ($file->isFile() && in_array($file->getExtension(), $this->extensions)) {
                    $schema["schemas"][] = $this->_loadFile($file);
                }
            }
        }

        return $schema;
    }

    /**
     *
     * @param Connection $connection
     * @return  Schema\Config
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    public function loadSchema()
    {
        $data = array(
            "config" => $this["config"],
            "schemas" => array()
        );

        foreach ($this->connection->getSchemaManager()->listTables() as $table) {

            $removeIndexes = array();

            $tableDef = array(
                "name"      => $table->getName(),
                "columns"   => array()
            );

            $foreign_keys = $table->getForeignKeys();

            foreach ($table->getColumns() as $column) {
                $columnDef = array(
                    "type" => strtolower($column->getType())
                );

                $length = $column->getLength();

                if (!is_null($length)) {
                    $columnDef["length"] = (int)$length;
                }

                if ($column->getUnsigned() && $columnDef["type"] != "boolean") {
                    $columnDef["unsigned"] = true;
                }

                if ($column->getAutoincrement()) {
                    $columnDef["autoincrement"] = "auto";
                }

                if (!$column->getNotnull()) {
                    $columnDef["nullable"] = true;
                }

                $default = $column->getDefault();

                if ($column->getNotnull() && !is_null($default)) {
                    $columnDef["default"] = $default;
                }

                foreach ($foreign_keys as $key => $foreign_key) {

                    if ($foreign_key->getLocalTableName() == $table->getName()) {

                        if (in_array($column->getName(), $foreign_key->getLocalColumns())) {

                            if (!isset($removeIndexes[$table->getName()])) {
                                $removeIndexes[$table->getName()] = array();
                            }

                            $removeIndexes[$table->getName()][] = current($foreign_key->getLocalColumns());

                            $columnDef["reference"] = array(
                                "table"     => $foreign_key->getForeignTableName(),
                                "column"    => current($foreign_key->getForeignColumns())
                            );
                        }

                    }
                }

                $comment = $column->getComment();

                if (!empty($comment)) {
                    $columnDef["comment"] = $comment;
                }

                $tableDef["columns"][$column->getName()] = $columnDef;
            }

            $primaryKey = $table->getPrimaryKey();

            if (!empty($primaryKey)) {
                $primary = $primaryKey->getColumns();

                if (!empty($primary)) {
                    $tableDef["primary"] = (array)$primary;
                }
            }

            $indexes = $table->getIndexes();

            $tableDef["unique"] = array();
            $tableDef["index"] = array();

            foreach ($indexes as $id => $index) {
                if ($index->isUnique() && !$index->isPrimary()) {
                    $tableDef["unique"][] = $index->getColumns();
                } elseif (!$index->isUnique() && !$index->isPrimary()) {

                    $index_columns = $index->getColumns();

                    foreach ($index_columns as $i => $name) {

                        if (isset($removeIndexes[$table->getName()]) && in_array($name, $removeIndexes[$table->getName()])) {
                            unset($index_columns[$i]);
                        }
                    }

                    if (!empty($index_columns)) {
                        $tableDef["index"][] = $index_columns;
                    }
                }
            }

            if (empty($tableDef["unique"])) {
                unset($tableDef["unique"]);
            }

            if (empty($tableDef["index"])) {
                unset($tableDef["index"]);
            }

            $data["schemas"][] = $tableDef;
        }

        $this->exchangeArray($data);
    }

    protected function _save($path, $name, $format, array $data)
    {
        switch ($format) {
            case 'sql':
                $schema = $this->connection->getSchemaManager($this->connection)->createSchema();
                $sqls = $schema->toSql($this->connection->getDatabasePlatform());
                $content = implode(';' . PHP_EOL . PHP_EOL, $sqls) . ';' . PHP_EOL;
                $ext = 'sql';
                break;
            case 'json':
                $content = json_encode($data, JSON_PRETTY_PRINT);
                $ext = 'json';
                break;
            case 'yaml':
            case 'yml':
                $content = Yaml::dump($data, 6);
                $ext = 'yml';
                break;
            default:
                throw new RuntimeException(sprintf("Format %s unsupported.", $format));
        }

        return (bool)file_put_contents(
            $path . '/' . $name . '.' . $ext,
            $content
        );
    }

    public function save($format)
    {
        $name = $this->file->getBasename('.' . $this->file->getExtension());
        $path = $this->file->getPathInfo()->getRealPath();

        if (!empty($this->path) && in_array($format, $this->extensions)) {
            $schemas = $this->getArrayCopy();

            foreach ($schemas["schemas"] as $schema) {
                $this->_save(
                    $this->getPath(),
                    $schema["name"],
                    $format,
                    $schema
                );
            }

            $schemas["schemas"] = $this->path;

            return $this->_save(
                $this->file->getPathInfo()->getRealPath(),
                $this->file->getBasename('.' . $this->file->getExtension()),
                $format,
                $schemas
            );
        } else {
            return $this->_save(
                $this->file->getPathInfo()->getRealPath(),
                $this->file->getBasename('.' . $this->file->getExtension()),
                $format,
                $this->getArrayCopy()
            );
        }

        switch ($format) {
            case 'sql':
                $schema = $this->connection->getSchemaManager($this->connection)->createSchema();
                $sqls = $schema->toSql($this->connection->getDatabasePlatform());
                $content = implode(';' . PHP_EOL . PHP_EOL, $sqls) . ';' . PHP_EOL;
                $ext = 'sql';
                break;
            case 'json':
                $content = json_encode($this->getArrayCopy(), JSON_PRETTY_PRINT);
                $ext = 'json';
                break;
            case 'yaml':
            case 'yml':
                $content = Yaml::dump($this->getArrayCopy(), 6);
                $ext = 'yml';
                break;
            default:
                throw new RuntimeException(sprintf("Format %s unsupported.", $format));
        }

        return (bool)file_put_contents(
            $path . '/' . $name . '.' . $ext,
            $content
        );
    }

    /**
     *
     * @return Doctrine\DBAL\Schema\Schema
     */
    public function getSchema()
    {
        $references = array();

        $schema = new DoctrineSchema(
            array(),
            array(),
            $this->connection->getSchemaManager()->createSchemaConfig()
        );

        foreach ($this["schemas"] as $table) {

            $tableDef = $schema->createTable($table["name"]);

            // add columns to table schema
            foreach ($table["columns"] as $column_name => $options) {
                $type = $options["type"];
                unset($options["type"]);

                if (isset($options["reference"])) {
                    if (!isset($references[$table["name"]])) {
                        $references[$table["name"]] = array();
                    }

                    $references[$table["name"]][$column_name] = $options["reference"];

                    unset($options["reference"]);
                }

                if (isset($options["nullable"])) {
                    $options["notnull"] = !(bool)$options["nullable"];
                    unset($options["nullable"]);
                } else {
                    $options["notnull"] = true;
                }

                $tableDef->addColumn($column_name, $type, $options);
            }

            // set the primary key to table schema
            if (isset($table["primary"])) {
                $tableDef->setPrimaryKey((array)$table["primary"]);
            }

            // add unique index to table schema
            if (isset($table["unique"])) {
                foreach ($table["unique"] as $unique) {
                    $tableDef->addUniqueIndex((array)$unique);
                }
            }

            // add index to table schema
            if (isset($table["index"])) {
                foreach ($table["index"] as $index) {
                    $tableDef->addIndex((array)$index);
                }
            }

        }

        // add foreign key constraint to table schema
        foreach ($references as $table_name => $columns) {
            $tableDef = $schema->getTable($table_name);

            foreach ($columns as $column_name => $options) {

                $extra = array();

                if (isset($options["options"])) {
                    $extra = $options["options"];
                }

                if ($options["table"] === $table_name) {
                    $tableDef->addForeignKeyConstraint(
                        $tableDef,
                        (array)$column_name,
                        (array)$options["column"],
                        $extra
                    );
                } else {
                    $tableDef->addForeignKeyConstraint(
                        $schema->getTable($options["table"]),
                        (array)$column_name,
                        (array)$options["column"],
                        $extra
                    );
                }
            }

        }

        return $schema;

    }
}

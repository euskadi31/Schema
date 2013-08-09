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
namespace Schema\Platform;

use Doctrine\DBAL\Platforms\MySqlPlatform as BaseMySqlPlatform;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;

class MySqlPlatform extends BaseMySqlPlatform
{
    public function getTinyIntTypeDeclarationSQL(array $field)
    {
        return 'TINYINT' . $this->_getCommonIntegerTypeDeclarationSQL($field);
    }

    protected function _getCommonTextTypeDeclarationSQL(array $columnDef)
    {
        if (isset($columnDef['length']) && is_numeric($columnDef['length'])) {
            return '(' . (int)$columnDef['length'] . ')';
        }

        return '';
    }

    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef)
    {
        $length = '';
        if (isset($columnDef['length']) && is_numeric($columnDef['length'])) {
            $length = '(' . (int)$columnDef['length'] . ')';
        }

        $autoinc = '';
        if ( ! empty($columnDef['autoincrement'])) {
            $autoinc = ' AUTO_INCREMENT';
        }
        $unsigned = (isset($columnDef['unsigned']) && $columnDef['unsigned']) ? ' UNSIGNED' : '';

        return $length . $unsigned . $autoinc;
    }

    public function getBooleanTypeDeclarationSQL(array $field)
    {
        return 'TINYINT(1) UNSIGNED';
    }

    public function getDoubleDeclarationSQL(array $field)
    {
        return 'DOUBLE' . $this->_getCommonDoubleTypeDeclarationSQL($field);
    }

    public function getMediumIntTypeDeclarationSQL(array $field)
    {
        return 'MEDIUMINT' . $this->_getCommonIntegerTypeDeclarationSQL($field);
    }

    public function getTinyTextTypeDeclarationSQL(array $field)
    {
        return 'TINYTEXT' . $this->_getCommonTextTypeDeclarationSQL($field);
    }

    public function getMediumTextTypeDeclarationSQL(array $field)
    {
        return 'MEDIUMTEXT' . $this->_getCommonTextTypeDeclarationSQL($field);
    }

    public function getLongTextTypeDeclarationSQL(array $field)
    {
        return 'LONGTEXT' . $this->_getCommonTextTypeDeclarationSQL($field);
    }

    public function _getCommonDoubleTypeDeclarationSQL(array $columnDef)
    {
        if (!isset($columnDef['precision'], $columnDef['scale'])) {
            return '';
        }


        $columnDef['precision'] = (!isset($columnDef['precision']) || empty($columnDef['precision']))
            ? 10 : $columnDef['precision'];
        $columnDef['scale'] = ( ! isset($columnDef['scale']) || empty($columnDef['scale']))
            ? 0 : $columnDef['scale'];

        return '(' . $columnDef['precision'] . ', ' . $columnDef['scale'] . ')';
    }

    public function getDefaultValueDeclarationSQL($field)
    {
        $default = empty($field['notnull']) ? ' DEFAULT NULL' : '';

        if (isset($field['default'])) {

            if (isset($field['type'])) {
                //var_dump((string)$field['type']);
                if (in_array((string)$field['type'], array("Integer", "BigInteger", "SmallInteger", "Tinyint"))) {
                    $default = " DEFAULT '" . $field['default'] . "'";
                } else if ((string)$field['type'] == 'DateTime' && $field['default'] == $this->getCurrentTimestampSQL()) {
                    $default = " DEFAULT " . $this->getCurrentTimestampSQL();
                } else if ((string)$field['type'] == 'Timestamp' && $field['default'] == $this->getCurrentTimestampSQL()) {
                    $default = " DEFAULT " . $this->getCurrentTimestampSQL();
                } else if ((string) $field['type'] == 'Boolean') {
                    $default = " DEFAULT '" . $this->convertBooleans($field['default']) . "'";
                }
            }
        }
        return $default;
    }

    public function getColumnDeclarationSQL($name, array $field)
    {
        if (isset($field['columnDefinition'])) {
            $columnDef = $this->getCustomTypeDeclarationSQL($field);
        } else {
            $default = $this->getDefaultValueDeclarationSQL($field);

            $charset = (isset($field['charset']) && $field['charset']) ?
                    ' ' . $this->getColumnCharsetDeclarationSQL($field['charset']) : '';

            $collation = (isset($field['collation']) && $field['collation']) ?
                    ' ' . $this->getColumnCollationDeclarationSQL($field['collation']) : '';

            $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';

            $unique = (isset($field['unique']) && $field['unique']) ?
                    ' ' . $this->getUniqueFieldDeclarationSQL() : '';

            $check = (isset($field['check']) && $field['check']) ?
                    ' ' . $field['check'] : '';

            $typeDecl = $field['type']->getSqlDeclaration($field, $this);
            $columnDef = $typeDecl . $charset . $notnull . $default . $unique . $check . $collation;
        }

        if ($this->supportsInlineColumnComments() && isset($field['comment']) && $field['comment']) {
            $columnDef .= " COMMENT '" . $field['comment'] . "'";
        }

        return '`' . $name . '` ' . $columnDef;
    }

    public function getColumnDeclarationListSQL(array $fields)
    {
        $queryFields = array();

        foreach ($fields as $fieldName => $field) {
            $queryFields[] = "    " . $this->getColumnDeclarationSQL($fieldName, $field);
        }

        return PHP_EOL . implode(',' . PHP_EOL, $queryFields);

        /*foreach ($queryFields as $queryField) {
            $content .= "    " . $queryField . PHP_EOL;
        }*/

        //return $content;

        //return implode(', ' . PHP_EOL, $queryFields);
    }

    public function getUniqueConstraintDeclarationSQL($name, Index $index)
    {
        if (count($index->getColumns()) === 0) {
            throw new \InvalidArgumentException("Incomplete definition. 'columns' required.");
        }

        return '    CONSTRAINT `' . $name . '` UNIQUE ('
             . $this->getIndexFieldDeclarationListSQL($index->getColumns())
             . ')';
    }

    public function getIndexDeclarationSQL($name, Index $index)
    {
        $type = '';

        if ($index->isUnique()) {
            $type = 'UNIQUE ';
        }

        if (count($index->getColumns()) === 0) {
            throw new \InvalidArgumentException("Incomplete definition. 'columns' required.");
        }

        return '    ' . $type . 'INDEX `' . $name . '` ('
             . $this->getIndexFieldDeclarationListSQL($index->getColumns())
             . ')';
    }

    public function getIndexFieldDeclarationListSQL(array $fields)
    {
        $ret = array();

        foreach ($fields as $field => $definition) {
            if (is_array($definition)) {
                $ret[] = '`' . $field . '`';
            } else {
                $ret[] = '`' . $definition . '`';
            }
        }

        return implode(', ', $ret);
    }

    public function getCreateForeignKeySQL(ForeignKeyConstraint $foreignKey, $table)
    {
        if ($table instanceof Table) {
            $table = $table->getQuotedName($this);
        }

        $query = 'ALTER TABLE `' . $table . '` ADD ' . $this->getForeignKeyDeclarationSQL($foreignKey);

        return $query;
    }


    public function getForeignKeyBaseDeclarationSQL(ForeignKeyConstraint $foreignKey)
    {
        $sql = '';
        if (strlen($foreignKey->getName())) {
            $sql .= 'CONSTRAINT `' . $foreignKey->getQuotedName($this) . '` ';
        }
        $sql .= 'FOREIGN KEY (`';

        if (count($foreignKey->getLocalColumns()) === 0) {
            throw new \InvalidArgumentException("Incomplete definition. 'local' required.");
        }
        if (count($foreignKey->getForeignColumns()) === 0) {
            throw new \InvalidArgumentException("Incomplete definition. 'foreign' required.");
        }
        if (strlen($foreignKey->getForeignTableName()) === 0) {
            throw new \InvalidArgumentException("Incomplete definition. 'foreignTable' required.");
        }

        $sql .= implode('`, `', $foreignKey->getLocalColumns())
              . '`) REFERENCES `'
              . $foreignKey->getQuotedForeignTableName($this) . '` (`'
              . implode('`, `', $foreignKey->getForeignColumns()) . '`)';

        return $sql;
    }

    protected function _getCreateTableSQL($tableName, array $columns, array $options = array())
    {
        $queryFields = $this->getColumnDeclarationListSQL($columns);

        if (isset($options['uniqueConstraints']) && ! empty($options['uniqueConstraints'])) {
            foreach ($options['uniqueConstraints'] as $index => $definition) {
                $queryFields .= ', ' . PHP_EOL . $this->getUniqueConstraintDeclarationSQL($index, $definition);
            }
        }

        // add all indexes
        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach($options['indexes'] as $index => $definition) {
                $queryFields .= ', ' . PHP_EOL . $this->getIndexDeclarationSQL($index, $definition);
            }
        }

        // attach all primary keys
        if (isset($options['primary']) && ! empty($options['primary'])) {
            $keyColumns = array_unique(array_values($options['primary']));
            $queryFields .= ', ' . PHP_EOL . '    PRIMARY KEY(' . $this->getIndexFieldDeclarationListSQL($keyColumns) . ')' . PHP_EOL;
        }

        $query = 'CREATE ';
        if (!empty($options['temporary'])) {
            $query .= 'TEMPORARY ';
        }
        $query .= 'TABLE `' . $tableName . '` (' . $queryFields . ') ';

        if (isset($options['comment'])) {
            $comment = trim($options['comment'], " '");

            $query .= sprintf("COMMENT = '%s' ", str_replace("'", "''", $comment));
        }

        if ( ! isset($options['charset'])) {
            $options['charset'] = 'utf8';
        }

        if ( ! isset($options['collate'])) {
            $options['collate'] = 'utf8_unicode_ci';
        }

        $query .= 'DEFAULT CHARACTER SET ' . $options['charset'];
        $query .= ' COLLATE ' . $options['collate'];

        if ( ! isset($options['engine'])) {
            $options['engine'] = 'InnoDB';
        }
        $query .= ' ENGINE = ' . $options['engine'];

        $sql[] = $query;

        if (isset($options['foreignKeys'])) {
            foreach ((array) $options['foreignKeys'] as $definition) {
                $sql[] = $this->getCreateForeignKeySQL($definition, $tableName);
            }
        }

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeDoctrineTypeMappings()
    {
        $this->doctrineTypeMapping = array(
            'tinyint'       => 'tinyint',
            'smallint'      => 'smallint',
            'mediumint'     => 'mediumint',
            'int'           => 'integer',
            'integer'       => 'integer',
            'bigint'        => 'bigint',
            'tinytext'      => 'tinytext',
            'mediumtext'    => 'mediumtext',
            'longtext'      => 'longtext',
            'text'          => 'text',
            'varchar'       => 'varchar',
            'string'        => 'varchar',
            'char'          => 'char',
            'date'          => 'date',
            'datetime'      => 'datetime',
            'timestamp'     => 'timestamp',
            'time'          => 'time',
            'float'         => 'float',
            'double'        => 'double',
            'real'          => 'double',
            'decimal'       => 'decimal',
            'numeric'       => 'decimal',
            'year'          => 'year',
            'longblob'      => 'blob',
            'blob'          => 'blob',
            'mediumblob'    => 'blob',
            'tinyblob'      => 'blob',
            'binary'        => 'blob',
            'varbinary'     => 'blob',
            'set'           => 'simple_array',
        );
    }
}

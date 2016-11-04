<?php

namespace ddanielroche\migration;

use yii\db\ColumnSchema;
use yii\db\Connection;
use yii;
use yii\db\Schema;
use yii\gii\CodeFile;
use yii\db\TableSchema;
use yii\helpers\Inflector;

class Generator extends \yii\gii\Generator
{
    public $db = 'db';
    public $migrationNamespace = 'app\migrations';
    public $tableName;
    public $tableIgnore;
    public $genmode = 'single';
    public $usePrefix = true;
    public $tableOptions = 'ENGINE=InnoDB';
    public $gmdate;
    public $structure;
    public $data;

    private $_ignoredTables = [];
    private $_tables = [];

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Migration Generator';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'This generator generates migration file for the specified database table.';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['db', 'migrationNamespace'], 'filter', 'filter' => 'trim'],
            [['db', 'tableName', 'migrationNamespace'], 'required'],
            [['db'], 'match', 'pattern' => '/^\w+$/', 'message' => 'Only word characters are allowed.'],
            [['tableIgnore', 'gmdate'], 'safe'],
            [['db'], 'validateDb'],
            [['tableName'], 'validateTableName'],
            ['tableOptions', 'safe'],
            [['usePrefix', 'structure', 'data'], 'boolean'],
            [['genmode'], 'in', 'range' => ['single', 'mass']],

            [['migrationNamespace'], 'filter', 'filter' => function ($value) {
                return trim($value, '\\');
            }],
            [['migrationNamespace'], 'match', 'pattern' => '/^[\w\\\\]+$/', 'message' => 'Only word characters and backslashes are allowed.'],
            [['migrationNamespace'], 'validateNamespace'],
        ]);
    }

    /**
     * Validates the namespace.
     *
     * @param string $attribute Namespace variable.
     */
    public function validateNamespace($attribute)
    {
        $value = $this->$attribute;
        $value = ltrim($value, '\\');
        $path = Yii::getAlias('@' . str_replace('\\', '/', $value), false);
        if ($path === false || !file_exists($path)) {
            $this->addError($attribute, 'Namespace must be associated with an existing directory.');
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'db' => 'Database Connection ID',
            'tableName' => 'Table Name',
            'tableIgnore' => 'Ignored tables',
            'migrationNamespace' => 'Migration Namespace',
            'usePrefix' => 'Replace table prefix',
            'structure' => 'Generate Structure',
            'data' => 'Generate Data',
            'genmode' => 'Generation Mode',
            'tableOptions' => 'Table Options'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'db' => 'This is the ID of the DB application component.',
            'tableName' => 'Use "*" for all table, mask support - as "tablepart*", or you can separate table names by comma ',
            'tableIgnore' => 'You can separate some table names by comma, for ignor ',
            'migrationNamespace' => 'Namespace for save migration files, e.g., <code>app\migrations</code>',
            'usePrefix' => 'Use Table Prefix Replacer eg.{{%tablename}} instead of prefix_tablename',
            'genmode' => 'All tables in separated files, or all in one file',
            'tableOptions' => 'Table Options'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function autoCompleteData()
    {
        $db = $this->getDbConnection();
        if ($db !== null) {
            return [
                'tableName' => function () use ($db) {
                    return $db->getSchema()->getTableNames();
                },
            ];
        } else {
            return [];
        }
    }

    /**
     * @inheritdoc
     */
    public function requiredTemplates()
    {
        return ['migration.php', 'relation.php', 'mass.php'];
    }

    /**
     * @inheritdoc
     */
    public function stickyAttributes()
    {
        return array_merge(
            parent::stickyAttributes(),
            ['db', 'migrationNamespace', 'usePrefix', 'structure', 'data', 'tableOptions']
        );
    }

    public function getIgnoredTables()
    {
        return $this->_ignoredTables;
    }

    public function getTables()
    {
        return $this->_tables;
    }

    /**
     * @inheritdoc
     */
    public function generate()
    {
        $files = $tableRelations = $tableList = [];
        $db = $this->getDbConnection();
        if (!$this->gmdate) {
            $this->gmdate = gmdate('ymdHms');
        }
        if ($this->genmode == 'single') {
            foreach ($this->getTables() as $tableName) {
                $tableSchema = $db->getTableSchema($tableName);
                $tableCaption = Inflector::id2camel($this->getTableCaption($tableName), '_');
                $tableAlias = $this->getTableAlias($tableCaption);
                $tableUniqueIndexes = $this->generateUniqueIndexes($tableSchema);
                $tableIndexes = $this->generateIndexes($tableSchema);
                $tableColumns = $this->columnsBySchema($tableSchema);
                $tableRelations[] = [
                    'fKeys' => $this->generateRelations($tableSchema),
                    'tableAlias' => $tableAlias,
                    'tableName' => $tableName
                ];
                $tablePrimaryKey = $this->generatePrimaryKey($tableSchema);
                $migrationName = "M{$this->gmdate}{$tableCaption}";
                $params = compact(
                    'tableName',
                    'tableSchema',
                    'tableCaption',
                    'tableAlias',
                    'migrationName',
                    'tableColumns',
                    'tableUniqueIndexes',
                    'tableIndexes',
                    'tablePrimaryKey'
                );
                $files[] = new CodeFile(
                    Yii::getAlias('@' . str_replace('\\', '/', $this->migrationNamespace)) . "/$migrationName.php",
                    $this->render('migration.php', $params)
                );
            }
            $this->gmdate = $this->gmdate + 1;
            $migrationName = "M{$this->gmdate}Relations";
            $params = ['tableRelations' => $tableRelations, 'migrationName' => $migrationName];
            $files[] = new CodeFile(
                Yii::getAlias('@' . str_replace('\\', '/', $this->migrationNamespace)) . "/$migrationName.php",
                $this->render('relation.php', $params)
            );
        } else {
            foreach ($this->getTables() as $tableName) {
                $tableSchema = $db->getTableSchema($tableName);
                $tableCaption = $this->getTableCaption($tableName);
                $tableAlias = $this->getTableAlias($tableCaption);
                $tableUniqueIndexes = $this->generateUniqueIndexes($tableSchema);
                $tableIndexes = $this->generateIndexes($tableSchema);
                $tableColumns = $this->columnsBySchema($tableSchema);
                $tableRelations[] = [
                    'fKeys' => $this->generateRelations($tableSchema),
                    'tableAlias' => $tableAlias,
                    'tableName' => $tableName
                ];
                $tablePrimaryKey = $this->generatePrimaryKey($tableSchema);
                $tableList[] = [
                    'alias' => $tableAlias,
                    'uniqueIndexes' => $tableUniqueIndexes,
                    'indexes' => $tableIndexes,
                    'columns' => $tableColumns,
                    'name' => $tableName,
                    'tablePrimaryKey' => $tablePrimaryKey
                ];
            }
            $migrationName = "M{$this->gmdate}Mass";
            $params = [
                'tableList' => $tableList,
                'tableRelations' => $tableRelations,
                'migrationName' => $migrationName
            ];
            $files[] = new CodeFile(
                Yii::getAlias('@' . str_replace('\\', '/', $this->migrationNamespace)) . "/$migrationName.php",
                $this->render('mass.php', $params)
            );
        }


        return $files;
    }

    /**
     * @param TableSchema $tableSchema
     * @return string
     */
    public function generatePrimaryKey($tableSchema)
    {
        $pk = [];
        foreach ($tableSchema->columns as $column) {
            if ($column->isPrimaryKey && !$column->autoIncrement) {
                $pk[] = $column->name;
            }
        }

        return $pk;
    }


    public function columnsBySchema($schema)
    {
        $cols = [];
        /**@var TableSchema $schema * */
        foreach ($schema->columns as $column) {
            $type = $this->getColumnType($column);
            $cols[$column->name] = $type;
        }
        return $cols;
    }

    /**
     * @param \yii\db\ColumnSchema $col
     * @return string
     */
    public function getColumnType($col)
    {
        switch ($col->type) {
            case Schema::TYPE_PK:
                $coldata = '$this->primaryKey(' . $col->precision . ')';
                break;
            case Schema::TYPE_BIGPK:
                $coldata = '$this->bigPrimaryKey(' . $col->precision . ')';
                break;
            case Schema::TYPE_CHAR:
                $coldata = '$this->char(' . $col->size . ')';
                break;
            case Schema::TYPE_STRING:
                $coldata = '$this->string(' . $col->size . ')';
                break;
            case Schema::TYPE_TEXT:
                $coldata = '$this->text()';
                break;
            case Schema::TYPE_SMALLINT:
                $coldata = '$this->smallInteger(' . $col->precision . ')';
                break;
            case Schema::TYPE_INTEGER: // TODO agregar clave primaria cuando no es autoIncrement.
                $length = $col->precision ? $col->precision : $col->size;
                $coldata = ($col->isPrimaryKey && $col->autoIncrement) ? '$this->primaryKey(' . $length . ')' : '$this->integer(' . $length . ')';
                break;
            case Schema::TYPE_BIGINT:
                $length = $col->precision ? $col->precision : $col->size;
                $coldata = ($col->isPrimaryKey && $col->autoIncrement) ? '$this->bigPrimaryKey(' . $length . ')' : '$this->bigInteger(' . $length . ')';
                break;
            case Schema::TYPE_FLOAT:
                $coldata = '$this->float(' . $col->precision . ')';
                break;
            case Schema::TYPE_DOUBLE:
                $coldata = '$this->double(' . $col->precision . ')';
                break;
            case Schema::TYPE_DECIMAL:
                $coldata = '$this->decimal(' . $col->precision . ', ' . $col->scale . ')';
                break;
            case Schema::TYPE_DATETIME:
                $coldata = '$this->dateTime(' . $col->precision . ')';
                break;
            case Schema::TYPE_TIMESTAMP:
                $coldata = '$this->timestamp(' . $col->precision . ')';
                break;
            case Schema::TYPE_TIME:
                $coldata = '$this->time(' . $col->precision . ')';
                break;
            case Schema::TYPE_DATE:
                $coldata = '$this->date()';
                break;
            case Schema::TYPE_BINARY:
                $coldata = '$this->binary(' . $col->precision . ')';
                break;
            case Schema::TYPE_BOOLEAN:
                $coldata = '$this->boolean()';
                break;
            case Schema::TYPE_MONEY:
                $coldata = '$this->money()';
                break;
            default:
                $coldata = '';
        }

        if ($col->unsigned && !$col->autoIncrement) {
            $coldata .= '->unsigned()';
        }
        if (!$col->allowNull && !$col->autoIncrement) {
            $coldata .= '->notNull()';
        }
        $coldata .= $this->buildDefaultValue($col);
        if (!empty($col->comment)) {
            $coldata .= "->comment('$col->comment')";
        }

        return $coldata;
    }

    /**
     * Builds the default value specification for the column.
     *
     * @param ColumnSchema $column
     *
     * @return string string with default value of column.
     */
    protected function buildDefaultValue(ColumnSchema $column)
    {
        if ($column->defaultValue === null) {
            return '';
        }

        switch (gettype($column->defaultValue)) {
            case 'integer':
                $string = 'defaultValue(' . $column->defaultValue . ')';
                break;
            case 'double':
                // ensure type cast always has . as decimal separator in all locales
                $string = 'defaultValue("' . str_replace(',', '.', (string)$column->defaultValue) . '")';
                break;
            case 'boolean':
                $string = $column->defaultValue ? 'defaultValue(true)' : 'defaultValue(false)';
                break;
            case 'object':
                $string = 'defaultExpression("' . (string)$column->defaultValue . '")';
                break;
            default:
                $string = "defaultValue('{$column->defaultValue}')";
        }

        return '->'. $string;
    }

    /**
     * @param \yii\db\ColumnSchema $col
     * @return string
     */
    public function renderDefaultValue($col)
    {
        if ($col->defaultValue) {
            $col->defaultValue = trim($col->defaultValue, "()");
            /*switch ($col->type) {
                case Schema::TYPE_DECIMAL:
                    return "->defaultValue(" . (float)$col->defaultValue . ")";
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                    return "->defaultValue(" . (integer)$col->defaultValue . ")";
                default:
                    return "->defaultValue('$col->defaultValue')";
            }*/
            return "->defaultValue('$col->defaultValue')";
        }
        return "";
    }

    public function generateRelations($schema)
    {
        /**@var TableSchema $schema * */
        $rels = [];
        if (!empty($schema->foreignKeys)) {
            foreach ($schema->foreignKeys as $i => $constraint) {
                foreach ($constraint as $pk => $fk) {
                    if (!$pk) {
                        $rels[$i]['ftable'] = $fk;
                    } else {
                        $rels[$i]['pk'] = $pk;
                        $rels[$i]['fk'] = $fk;
                    }
                }
            }
        }
        //return [VarDumper::dumpAsString($schema->foreignKeys)];
        return $rels;
    }

    public function generateUniqueIndexes($tableName)
    {
        $indexes = $this->getDbConnection()->getSchema()->findUniqueIndexes($tableName);

        return $indexes;
    }

    public function generateIndexes($tableName)
    {
        $indexes = $this->getDbConnection()->getSchema()->findIndexes($tableName);

        return $indexes;
    }

    public function generatePure($tableName)
    {
        $query = $this->getDbConnection()->createCommand('SHOW CREATE TABLE ' . $tableName)->queryOne();
        return isset($query['Create Table']) ?: '';
    }

    public function getTableCaption($tableName)
    {
        $db = $this->getDbConnection();
        return str_replace($db->tablePrefix, '', strtolower($tableName));
    }

    public function getTableAlias($tableCaption)
    {
        return '{{%' . $tableCaption . '}}';
    }

    /**
     * Validates the [[db]] attribute.
     */
    public function validateDb()
    {
        if (!Yii::$app->has($this->db)) {
            $this->addError('db', 'There is no application component named "db".');
        } elseif (!Yii::$app->get($this->db) instanceof Connection) {
            $this->addError('db', 'The "db" application component must be a DB connection instance.');
        }
    }

    /**
     * Validates the [[tableName]] attribute.
     */
    public function validateTableName()
    {
        $tables = $this->prepareTables();

        if (empty($tables)) {
            $this->addError('tableName', "Table '{$this->tableName}' does not exist, or all tables was ignored");
            return false;
        }
        return true;
    }

    /**
     * @return array the table names that match the pattern specified by [[tableName]].
     */
    public function prepareIgnored()
    {
        $ignors = [];
        if (is_string($this->tableIgnore)) {
            if (strpos($this->tableIgnore, ',') !== false) {
                $ignors = explode(',', $this->tableIgnore);
            } else {
                $ignors[] = $this->tableIgnore;
            }
        } elseif (is_array($this->tableIgnore)) {
            $ignors = $this->tableIgnore;
        }
        $ignors = array_filter($ignors, 'trim');
        if (!empty($ignors)) {
            foreach ($ignors as $ignoredTable) {
                $prepared = $this->prepareTableName($ignoredTable);
                if (!empty($prepared)) {
                    $this->_ignoredTables = array_merge($this->_ignoredTables, $prepared);
                }
            }
        }
        return $this->_ignoredTables;
    }

    public function prepareTableName($tableName)
    {
        $prepared = [];
        $tableName = trim($tableName);
        $db = $this->getDbConnection();
        if ($db === null) {
            return $prepared;
        }
        if ($tableName == '*') {
            foreach ($db->schema->getTableNames() as $table) {
                $prepared[] = $table;
            }
        } elseif (strpos($tableName, '*') !== false) {
            $schema = '';
            $pattern = '/^' . str_replace('*', '\w+', $tableName) . '$/';

            foreach ($db->schema->getTableNames($schema) as $table) {
                if (preg_match($pattern, $table)) {
                    $prepared[] = $table;
                }
            }
        } elseif (($table = $db->getTableSchema($tableName, true)) !== null) {
            $prepared[] = $tableName;
        }
        return $prepared;
    }


    /**
     * @return array the table names that match the pattern specified by [[tableName]].
     */
    public function prepareTables()
    {
        $tables = [];
        $this->prepareIgnored();
        if (is_string($this->tableName)) {
            if (strpos($this->tableName, ',') !== false) {
                $tables = explode(',', $this->tableName);
            } else {
                $tables[] = $this->tableName;
            }
        } elseif (is_array($this->tableName)) {
            $tables = $this->tableName;
        }
        if (!empty($tables)) {
            foreach ($tables as $goodTable) {
                $prepared = $this->prepareTableName($goodTable);
                if (!empty($prepared)) {
                    $this->_tables = array_merge($this->_tables, $prepared);
                }
            }
            foreach ($this->_tables as $i => $t) {
                if (in_array($t, $this->_ignoredTables)) {
                    unset($this->_tables[$i]);
                }
            }
        }

        return $this->_tables;
    }


    /**
     * @return Connection the DB connection as specified by [[db]].
     */
    protected function getDbConnection()
    {
        return Yii::$app->{$this->db};
    }

    /**
     * Checks if any of the specified columns is auto incremental.
     * @param  \yii\db\TableSchema $table the table schema
     * @param  array $columns columns to check for autoIncrement property
     * @return boolean             whether any of the specified columns is auto incremental.
     */
    protected function isColumnAutoIncremental($table, $columns)
    {
        foreach ($columns as $column) {
            if (isset($table->columns[$column]) && $table->columns[$column]->autoIncrement) {
                return true;
            }
        }

        return false;
    }

    public function getLabelDefaults($labelname, $default)
    {
        $defaults = [
            'active' => 'Активно?',
            'name' => 'Название',
            'title' => 'Заголовок',
            'created' => 'Создано',
            'updated' => 'Обновлено'
        ];
        return isset($defaults[$labelname]) ? $defaults[$labelname] : $default;
    }

    /**
     * @inheritdoc
     */
    public function successMessage()
    {
        $output = <<<EOD
<p>The migration has been generated successfully.</p>
<p>To apply migration, execute this code un comman line:</p>
EOD;
        $path = \Yii::$app->basePath;
        $code = <<<EOD
$ cd $path
$ yii migrate
EOD;
        return $output . '<pre>' . highlight_string($code, true) . '</pre>';
    }
}

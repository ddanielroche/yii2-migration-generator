<?php
/**
 * This view is used by console/controllers/MigrateController.php
 * The following variables are available in this view:
 */
/**
 * @var $migrationName string the new migration class name
 * @var $tableAlias string table_name
 * @var $tableName string table_name
 * @var $tableSchema yii\db\TableSchema
 * @var array $tableColumns
 * @var array $tableIndexes
 * @var array $tablePk
 * @var ddanielroche\migration\Generator $generator
 */

$tableAlias = $generator->usePrefix ? $tableAlias : $tableName;

echo "<?php\n";
?>

use yii\db\Migration;

class <?= $migrationName ?> extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            /** @link http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci */
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('<?= $tableAlias ?>', [
<?php foreach ($tableColumns as $name => $data) : ?>
            '<?= $name ?>' => <?= $data; ?>,
<?php endforeach; ?>
        ], $tableOptions);
<?php if (!empty($tablePrimaryKey) && is_array($tablePrimaryKey)) : ?>

        $this->addPrimaryKey('<?= $tableName ?>_pk', '<?= $tableAlias ?>', '<?= implode(", ", $tablePrimaryKey) ?>');
<?php endif; ?>
<?php if (!empty($tableIndexes) && is_array($tableIndexes)) : ?>

<?php foreach ($tableIndexes as $name => $columns) : ?>
<?php if ($name != 'PRIMARY') : ?>
        $this->createIndex('<?= $name ?>', '<?= $tableAlias ?>', '<?= implode(", ", $columns) ?>', true);
<?php endif; ?>
<?php endforeach; ?>
<?php endif ?>
    }

    public function safeDown()
    {
<?php if (!empty($tableIndexes) && is_array($tableIndexes)) : ?>
<?php foreach ($tableIndexes as $name => $data) : ?>
<?php if ($name != 'PRIMARY') : ?>
        $this->dropIndex('<?= $name ?>', '<?= $tableAlias ?>');
<?php endif; ?>
<?php endforeach; ?>

<?php endif ?>
        $this->dropTable('<?= $tableAlias ?>');
    }
}

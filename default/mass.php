<?php
/**
 * This view is used by console/controllers/MigrateController.php
 * The following variables are available in this view:
 */
/**
 * @var $migrationName string the new migration class name
 * @var array $tableList
 * @var array $tableRelations
 * @var ddanielroche\migration\Generator $generator
 *
 */

echo "<?php\n";
?>

namespace <?= $generator->migrationNamespace ?>;

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
<?php foreach ($tableList as $tableData) : ?>

        $this->createTable('<?= ($generator->usePrefix) ? $tableData['alias'] : $tableData['name'] ?>', [
<?php foreach ($tableData['columns'] as $name => $data) : ?>
            '<?= $name ?>' => <?= $data; ?>,
<?php endforeach; ?>
        ], $tableOptions);
<?php if (!empty($tableData['tablePrimaryKey']) && is_array($tableData['tablePrimaryKey'])) : ?>
        $this->addPrimaryKey('<?= $tableData['name'] ?>_pk', '<?= ($generator->usePrefix) ? $tableData['alias'] : $tableData['name'] ?>', '<?= implode(", ", $tableData['tablePrimaryKey']) ?>');
<?php endif; ?>
<?php if (!empty($tableData['uniqueIndexes']) && is_array($tableData['uniqueIndexes'])) : ?>
<?php foreach ($tableData['uniqueIndexes'] as $name => $columns) : ?>
<?php if ($name != 'PRIMARY') : ?>
        $this->createIndex('<?= $name ?>', '<?= ($generator->usePrefix) ? $tableData['alias'] : $tableData['name'] ?>', '<?= implode(", ", $columns) ?>', true);
<?php endif; ?>
<?php endforeach; ?>
<?php endif ?>
<?php if (!empty($tableData['indexes']) && is_array($tableData['indexes'])) : ?>
<?php foreach ($tableData['indexes'] as $name => $columns) : ?>
<?php if ($name != 'PRIMARY') : ?>
        $this->createIndex('<?= $name ?>', '<?= ($generator->usePrefix) ? $tableData['alias'] : $tableData['name'] ?>', '<?= implode(", ", $columns) ?>', false);
<?php endif; ?>
<?php endforeach; ?>
<?php endif ?>
<?php endforeach; ?>
    }

    public function safeDown()
    {
<?php foreach ($tableList as $tableData) : ?>
        $this->dropTable('<?= ($generator->usePrefix) ? $tableData['alias'] : $tableData['name'] ?>');
<?php endforeach; ?>
    }
}

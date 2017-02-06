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
<?php foreach ($tableList as $tableData) : ?>
<?php if (!empty($tableData['data']['data']) && is_array($tableData['data']['data'])) : ?>
        $tableName = '<?= ($generator->usePrefix) ? $tableData['alias'] : $tableData['name'] ?>';
        $this->batchInsert(
            $tableName,
            ['<?= implode("', '", $tableData['data']['columns']) ?>'],
            [
<?php foreach ($tableData['data']['data'] as $data) : ?>
                [
<?php foreach ($data as $key => $value) : ?>
                    '<?= $key ?>' => <?= \yii\helpers\VarDumper::export($value) ?>,
<?php endforeach; ?>
                ],
<?php endforeach; ?>
            ]
        );
        if ($this->db->getTableSchema($tableName)->sequenceName !== null) {
            $this->db->createCommand()->resetSequence($tableName)->execute();
        }
<?php endif ?>
<?php endforeach; ?>
    }

    public function safeDown()
    {
<?php foreach ($tableList as $tableData) : ?>
<?php if (!empty($tableData['data']['data']) && is_array($tableData['data']['data'])) : ?>
        $this->truncateTable('<?= ($generator->usePrefix) ? $tableData['alias'] : $tableData['name'] ?>');
<?php endif ?>
<?php endforeach; ?>
    }
}

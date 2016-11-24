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
        $this->batchInsert(
            '<?= ($generator->usePrefix) ? $tableData['alias'] : $tableData['name'] ?>',
            ['<?= implode("', '", $tableData['data']['columns']) ?>'],
<?= \yii\helpers\VarDumper::export($tableData['data']['data']) ?>

        );
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

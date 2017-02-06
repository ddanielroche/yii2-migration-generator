<?php
/**
 * This view is used by console/controllers/MigrateController.php
 * The following variables are available in this view:
 */
/**
 * @var $migrationName string the new migration class name
 * @var array $tableRelations
 * @var ddanielroche\migration\Generator $generator
 */

echo "<?php\n";
?>

namespace <?= $generator->migrationNamespace ?>;

use yii\db\Migration;

class <?= $migrationName ?> extends Migration
{
    public function safeUp()
    {
<?php if (!empty($tableRelations) && is_array($tableRelations)) : ?>
<?php foreach ($tableRelations as $table) : ?>
<?php foreach ($table['fKeys'] as $i => $rel) : ?>
        $this->addForeignKey('<?= $i ?>', '<?= ($generator->usePrefix) ? $table['tableAlias'] : $table['tableName'] ?>', '<?= $rel['pk'] ?>', '<?= $rel['ftable'] ?>', '<?= $rel['fk'] ?>');
<?php endforeach; ?>
<?php endforeach; ?>
<?php endif ?>
    }

    public function safeDown()
    {
<?php if (!empty($tableRelations) && is_array($tableRelations)) : ?>
<?php foreach ($tableRelations as $table) : ?>
<?php foreach ($table['fKeys'] as $i => $rel) : ?>
        $this->dropForeignKey('<?= $i ?>', '<?= ($generator->usePrefix) ? $table['tableAlias'] : $table['tableName'] ?>');
<?php endforeach; ?>
<?php endforeach; ?>
<?php endif ?>
    }
}

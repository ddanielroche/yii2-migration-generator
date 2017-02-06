<?php
/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var yii\gii\generators\form\Generator $generator
 */
$data = call_user_func($generator->autoCompleteData()['tableName']);
$data = array_combine($data, $data);

echo $form->field($generator, 'tableName')->widget(\kartik\select2\Select2::className(), [
    'data' => $data,
    'options' => [
        'placeholder' => 'Seleccione una o varias tablas para crear la migración...',
        'multiple' => true
    ],
    'pluginOptions' => [
        'allowClear' => true
    ]
]);
echo $form->field($generator, 'tableIgnore')->widget(\kartik\select2\Select2::className(), [
    'data' => $data,
    'options' => [
        'placeholder' => 'Seleccione una o varias tablas para crear la migración...',
        'multiple' => true
    ],
    'pluginOptions' => [
        'allowClear' => true
    ]
]);
echo $form->field($generator, 'db');
echo $form->field($generator, 'migrationNamespace');
echo $form->field($generator, 'usePrefix')->checkbox();
echo $form->field($generator, 'tableOptions');
echo $form->field($generator, 'genmode')->dropDownList([
    'single' => 'One file per table',
    'mass' => 'All in one file'
]);
echo $form->field($generator, 'structure')->checkbox();
echo $form->field($generator, 'data')->checkbox();
echo $form->field($generator, 'relations')->checkbox();
echo $form->field($generator, 'comments')->checkbox();
echo $form->field($generator, 'gmdate');
//echo \yii\helpers\Html::activeHiddenInput($generator, 'gmdate');

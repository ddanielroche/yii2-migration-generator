Migration Generator for Yii2 databases supports.
================================================
Generate migration files for one table, comma separated list of tables, part of table name or all tables.

[![build status](https://git.versat.azcuba.cu/backend/yii2-migration-generator/badges/master/build.svg)](https://git.versat.azcuba.cu/backend/yii2-migration-generator/commits/master)

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist ddanielroche/yii2-migration-generator "*"
```

or add

```
"ddanielroche/yii2-migration-generator": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
<?= \ddanielroche\migration\AutoloadExample::widget(); ?>```
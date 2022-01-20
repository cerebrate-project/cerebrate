<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;


class UserSettings extends AbstractMigration
{

    public $autoId = false; // turn off automatic `id` column create. We want it to be `int(10) unsigned`


    public function change()
    {
        $table = $this->table('user_settings', [
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
            ]);
        $table
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'limit' => 10,
                'signed' => false,
            ])
            ->addPrimaryKey('id')
            ->addColumn('name', 'string', [
                'default' => null,
                'null' => false,
                'limit' => 255,
                'comment' => 'The name of the user setting',
            ])
            ->addColumn('value', 'text', [
                'default' => null,
                'null' => true,
                'limit' => MysqlAdapter::TEXT_LONG,
                'comment' => 'The value of the user setting',
            ])
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'null' => true,
                'signed' => false,
                'length' => 10,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ]);

        $table->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE']);

        $table->addIndex('name')
            ->addIndex('user_id')
            ->addIndex('created')
            ->addIndex('modified');

        $table->create();
    }
}

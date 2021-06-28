<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class RemoteToolConnections extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */

    public $autoId = false; // turn off automatic `id` column create. We want it to be `int(10) unsigned`


    public function change()
    {
        $table = $this->table('remote_tool_connections', [
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
            ->addColumn('local_tool_id', 'integer', [
                'null' => false,
                'signed' => false,
                'length' => 10,
            ])
            ->addColumn('remote_tool_id', 'integer', [
                'null' => false,
                'signed' => false,
                'length' => 10,
            ])
            ->addColumn('remote_tool_name', 'string', [
                'null' => false,
                'limit' => 191,
            ])
            ->addColumn('brood_id', 'integer', [
                'null' => false,
                'signed' => false,
                'length' => 10,
            ])
            ->addColumn('name', 'string', [
                'null' => true,
                'limit' => 191,
            ])
            ->addColumn('settings', 'text', [
                'null' => true,
                'limit' => MysqlAdapter::TEXT_LONG
            ])
            ->addColumn('status', 'string', [
                'null' => true,
                'limit' => 32,
                'encoding' => 'ascii',
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ]);

        $table->addForeignKey('local_tool_id', 'local_tools', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE']);

        $table->addIndex('remote_tool_id')
              ->addIndex('remote_tool_name')
              ->addIndex('status')
              ->addIndex('name');

        $table->create();
    }
}


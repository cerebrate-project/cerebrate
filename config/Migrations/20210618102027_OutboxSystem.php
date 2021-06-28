<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class OutboxSystem extends AbstractMigration
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
        $table = $this->table('outbox', [
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
            ->addColumn('uuid', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('scope', 'string', [
                'default' => null,
                'null' => false,
                'limit' => 191,
                'comment' => 'The to model linked with the message',
            ])
            ->addColumn('action', 'string', [
                'default' => null,
                'null' => false,
                'limit' => 191,
                'comment' => 'The action linked with the message',
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'null' => false,
                'limit' => 191,
            ])
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'null' => true,
                'signed' => false,
                'length' => 10,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('data', 'text', [
                'default' => null,
                'null' => true,
                'limit' => MysqlAdapter::TEXT_LONG
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ]);

        $table->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE']);

        $table->addIndex(['uuid'], ['unique' => true])
              ->addIndex('scope')
              ->addIndex('action')
              ->addIndex('title')
              ->addIndex('created')
              ->addIndex('user_id');

        $table->create();
    }
}


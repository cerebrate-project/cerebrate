<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class LocalTools extends AbstractMigration
{

    public $autoId = false; // turn off automatic `id` column create. We want it to be `int(10) unsigned`

    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $exists = $this->hasTable('local_tools');
        if (!$exists) {
            $table = $this->table('local_tools', [
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
                    'limit' => 191,
                    'comment' => 'The name of the individual connection',
                ])
                ->addColumn('connector', 'string', [
                    'default' => null,
                    'null' => false,
                    'limit' => 191,
                    'comment' => 'The library name used for the connection',
                ])
                ->addColumn('settings', 'text', [
                    'default' => null,
                    'null' => true,
                ])
                ->addColumn('exposed', 'boolean', [
                    'default' => 0,
                    'null' => false,
                ])
                ->addColumn('description', 'text', [
                    'default' => null,
                    'null' => true,
                ]);

            $table->addIndex('name')
                ->addIndex('connector');

            $table->create();
        }
    }
}


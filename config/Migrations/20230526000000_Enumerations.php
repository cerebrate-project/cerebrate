<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class Enumerations extends AbstractMigration
{
    public $autoId = false; // turn off automatic `id` column create. We want it to be `int(10) unsigned`

    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $exists = $this->hasTable('enumeration_collections');
        if (!$exists) {
            $enumerationCollectionsTable = $this->table('enumeration_collections', [
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci'
            ]);
            $enumerationCollectionsTable
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'limit' => 10,
                    'signed' => false,
                ])
                ->addPrimaryKey('id')
                ->addColumn('uuid', 'string', [
                    'null' => false,
                    'limit' => 40,
                    'collation' => 'ascii_general_ci',
                    'encoding' => 'ascii',
                ])
                ->addColumn('name', 'string', [
                    'null' => false,
                    'limit' => 191,
                    'collation' => 'utf8mb4_unicode_ci',
                    'encoding' => 'utf8mb4',
                ])
                ->addColumn('description', 'text', [
                    'default' => null,
                    'null' => true
                ])
                ->addColumn('target_model', 'string', [
                    'null' => false,
                    'limit' => 255,
                    'collation' => 'ascii_general_ci',
                    'encoding' => 'ascii',
                ])
                ->addColumn('target_field', 'string', [
                    'null' => false,
                    'limit' => 255,
                    'collation' => 'ascii_general_ci',
                    'encoding' => 'ascii',
                ])
                ->addColumn('enabled', 'boolean', [
                    'default' => 0,
                ])
                ->addColumn('deleted', 'boolean', [
                    'default' => 0,
                ])
                ->addColumn('created', 'datetime', [
                    'null' => false
                ])
                ->addColumn('modified', 'datetime', [
                    'null' => false
                ])
                ->addIndex('name')
                ->addIndex('target_model')
                ->addIndex('target_field')
                ->addIndex('uuid', ['unique' => true]);
            
            $enumerationCollectionsTable->create();
        }



        $exists = $this->hasTable('enumerations');
        if (!$exists) {
            $enumerationsTable = $this->table('enumerations', [
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci'
            ]);
            $enumerationsTable
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'limit' => 10,
                    'signed' => false,
                ])
                ->addPrimaryKey('id')
                ->addColumn('value', 'string', [
                    'null' => false,
                    'limit' => 191,
                    'collation' => 'utf8mb4_unicode_ci',
                    'encoding' => 'utf8mb4',
                ])
                ->addColumn('enumeration_collection_id', 'integer', [
                    'limit' => 10,
                    'signed' => false,
                    'null' => false
                ])
                ->addIndex('value')
                ->addIndex('enumeration_collection_id');
            $enumerationsTable->create();
        }
    }
}

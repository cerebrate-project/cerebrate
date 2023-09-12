<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class OrgGrouping extends AbstractMigration
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
        $exists = $this->hasTable('org_groups');
        if (!$exists) {
            $orgGroupsTable = $this->table('org_groups', [
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci'
            ]);
            $orgGroupsTable
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
                ->addColumn('created', 'datetime', [
                    'null' => false
                ])
                ->addColumn('modified', 'datetime', [
                    'null' => false
                ])
                ->addIndex('name')
                ->addIndex('uuid', ['unique' => true]);
            
            $orgGroupsTable->create();
        }


        $exists = $this->hasTable('org_groups_organisations');
        if (!$exists) {
            $orgGroupsTable = $this->table('org_groups_organisations', [
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci'
            ]);
            $orgGroupsTable
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'limit' => 10,
                    'signed' => false,
                ])
                ->addPrimaryKey('id')
                ->addColumn('org_group_id', 'integer', [
                    'limit' => 10,
                    'signed' => false,
                ])
                ->addIndex('org_group_id')
                ->addColumn('organisation_id', 'integer', [
                    'limit' => 10,
                    'signed' => false,
                ])
                ->addIndex('organisation_id')
                ->addIndex(['org_group_id', 'organisation_id'], ['unique' => true]);
            
            $orgGroupsTable->create();
        }


        $exists = $this->hasTable('org_groups_admins');
        if (!$exists) {
            $orgGroupsTable = $this->table('org_groups_admins', [
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci'
            ]);
            $orgGroupsTable
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'limit' => 10,
                    'signed' => false,
                ])
                ->addPrimaryKey('id')
                ->addColumn('org_group_id', 'integer', [
                    'limit' => 10,
                    'signed' => false,
                ])
                ->addIndex('org_group_id')
                ->addColumn('user_id', 'integer', [
                    'limit' => 10,
                    'signed' => false,
                ])
                ->addIndex('user_id')
                ->addIndex(['org_group_id', 'user_id'], ['unique' => true]);
            
            $orgGroupsTable->create();
        }
    }
}

<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

final class AuditLogs extends AbstractMigration
{
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

    public $autoId = false; // turn off automatic `id` column create. We want it to be `int(10) unsigned`

    public function change(): void
    {
        $exists = $this->hasTable('audit_logs');
        if (!$exists) {
            $table = $this->table('audit_logs', [
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci'
            ]);
            $table
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'limit' => 10,
                    'signed' => false,
                ])
                ->addPrimaryKey('id')
                ->addColumn('user_id', 'integer', [
                    'default' => null,
                    'null' => true,
                    'signed' => false,
                    'length' => 10
                ])
                ->addColumn('authkey_id', 'integer', [
                    'default' => null,
                    'null' => true,
                    'signed' => false,
                    'length' => 10
                ])
                ->addColumn('request_ip', 'varbinary', [
                    'default' => null,
                    'null' => true,
                    'length' => 16
                ])
                ->addColumn('request_type', 'boolean', [
                    'null' => false
                ])
                ->addColumn('request_id', 'integer', [
                    'default' => null,
                    'null' => true,
                    'signed' => false,
                    'length' => 10
                ])
                ->addColumn('request_action', 'string', [
                    'null' => false,
                    'length' => 20
                ])
                ->addColumn('model', 'string', [
                    'null' => false,
                    'length' => 80
                ])
                ->addColumn('model_id', 'integer', [
                    'default' => null,
                    'null' => true,
                    'signed' => false,
                    'length' => 10
                ])
                ->addColumn('model_title', 'text', [
                    'default' => null,
                    'null' => true
                ])
                ->addColumn('change', 'blob', [
                ])
                ->addColumn('created', 'datetime', [
                    'default' => null,
                    'null' => false,
                ])
                ->addIndex('user_id')
                ->addIndex('request_ip')
                ->addIndex('model')
                ->addIndex('model_id')
                ->addIndex('request_action')
                ->addIndex('created');
            $table->create();
        }
    }
}

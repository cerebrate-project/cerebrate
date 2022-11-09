<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

final class PermissionRestrictions extends AbstractMigration
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
    public function change(): void
    {
        $exists = $this->hasTable('permission_limitations');
        if (!$exists) {
            $table = $this->table('permission_limitations', [
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
            ]);
            $table
                ->addColumn('scope', 'string', [
                    'null' => false,
                    'length' => 20,
                    'collation' => 'ascii_general_ci'
                ])
                ->addColumn('permission', 'string', [
                    'null' => false,
                    'length' => 40,
                    'collation' => 'utf8mb4_unicode_ci'
                ])
                ->addColumn('max_occurrence', 'integer', [
                    'null' => false,
                    'signed' => false
                ])
                ->addColumn('comment', 'blob', [])
                ->addIndex('scope')
                ->addIndex('permission');
            $table->create();
        }
    }
}

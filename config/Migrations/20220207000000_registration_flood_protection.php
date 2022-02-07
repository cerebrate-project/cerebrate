<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

final class RegistrationFloodProtection extends AbstractMigration
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
        $exists = $this->hasTable('flood_protections');
        if (!$exists) {
            $table = $this->table('flood_protections', [
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
            ]);
            $table
                ->addColumn('remote_ip', 'string', [
                    'null' => false,
                    'length' => 45,
                ])
                ->addColumn('request_action', 'string', [
                    'null' => false,
                    'length' => 191,
                ])
                ->addColumn('expiration', 'integer', [
                    'null' => false,
                    'signed' => false,
                    'length' => 10,
                ])
                ->addIndex('remote_ip')
                ->addIndex('request_action')
                ->addIndex('expiration');
            $table->create();
        }
    }
}

<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

final class Bruteforces extends AbstractMigration
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
        $exists = $this->hasTable('bruteforces');
        if (!$exists) {
            $table = $this->table('bruteforces', [
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
            ]);
            $table
                ->addColumn('user_ip', 'string', [
                    'null' => false,
                    'length' => 45,
                ])
                ->addColumn('username', 'string', [
                    'null' => false,
                    'length' => 191,
                    'collation' => 'utf8mb4_unicode_ci'
                ])
                ->addColumn('expiration', 'datetime', [
                    'null' => false
                ])
                ->addIndex('user_ip')
                ->addIndex('username')
                ->addIndex('expiration');
            $table->create();
        }
    }
}

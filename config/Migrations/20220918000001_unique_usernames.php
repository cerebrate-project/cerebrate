<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

final class UniqueUserNames extends AbstractMigration
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
        $table = $this->table('users');
        $exists = $table->hasIndexByName('users', 'username');
        $this->execute('DELETE FROM users WHERE id NOT IN (SELECT MIN(id) FROM users GROUP BY LOWER(username));');
        if (!$exists) {
            $table->addIndex(
                [
                    'username'
                ],
                [
                    'unique' => true
                ]
            )->save();
        }
    }
}

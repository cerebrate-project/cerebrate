<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class SupportForMegalomaniacPgpKeysRound2 extends AbstractMigration
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
        $encryption_keys = $this->table('audit_logs');
        $encryption_keys->changeColumn('changed', 'blob', ['limit' => MysqlAdapter::BLOB_MEDIUM])->save();
    }
}

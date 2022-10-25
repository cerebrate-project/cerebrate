<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

final class InboxSeverity extends AbstractMigration
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
        $exists = $this->table('inbox')->hasColumn('severity');
        if (!$exists) {
            $this->table('inbox')
                ->addColumn('severity', 'integer', [
                    'null' => false,
                    'default' => 0,
                    'signed' => false,
                    'length' => 10,
                ])
                ->renameColumn('comment', 'message')
                ->removeColumn('description')
                ->update();
            $this->table('outbox')
                ->addColumn('severity', 'integer', [
                    'null' => false,
                    'default' => 0,
                    'signed' => false,
                    'length' => 10,
                ])
                ->renameColumn('comment', 'message')
                ->removeColumn('description')
                ->update();
        }
    }
}

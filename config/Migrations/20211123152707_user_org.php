<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

final class UserOrg extends AbstractMigration
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
        $alignments = $this->table('users')
            ->addColumn('organisation_id', 'integer', [
                'default' => null,
                'null' => true,
                'signed' => false,
                'length' => 10
            ])
            ->addIndex('organisation_id')
            ->update();
        $q1 = $this->getQueryBuilder();
        $org_id = $q1->select(['min(id)'])->from('organisations')->execute()->fetchAll()[0][0];
        if (!empty($org_id)) {
            $q2 = $this->getQueryBuilder();
            $q2->update('users')
                ->set('organisation_id', $org_id)
                ->where(['organisation_id IS NULL'])
                ->execute();
        }
    }
}

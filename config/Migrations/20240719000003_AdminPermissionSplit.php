<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class AdminPermissionSplit extends AbstractMigration
{
    public $autoId = false; // turn off automatic `id` column create. We want it to be `int(10) unsigned`

    public function change(): void
    {
        $exists = $this->table('roles')->hasColumn('perm_community_admin');
        if (!$exists) {
            $this->table('roles')
                ->addColumn('perm_community_admin', 'boolean', [
                    'default' => 0,
                    'null' => false,
                ])
                ->addIndex('perm_community_admin')
                ->update();
        }
        $builder = $this->getQueryBuilder();
        $builder
            ->update('roles')
            ->set('perm_community_admin', true)
            ->where(['perm_admin' => true])
            ->execute();
    }
}

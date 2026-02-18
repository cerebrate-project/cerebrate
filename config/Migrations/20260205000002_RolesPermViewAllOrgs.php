<?php
declare(strict_types=1);

use Migrations\AbstractMigration;


final class RolesPermViewAllOrgs extends AbstractMigration
{
    public $autoId = false; // turn off automatic `id` column create. We want it to be `int(10) unsigned`

    public function change(): void
    {
        $exists = $this->table('roles')->hasColumn('perm_view_all_orgs');
        if (!$exists) {
            $this->table('roles')
                ->addColumn('perm_view_all_orgs', 'boolean', [
                    'default' => 0,
                    'null' => false,
                ])
                ->addIndex('perm_view_all_orgs')
                ->update();
        }
        $builder = $this->getQueryBuilder();
        $builder
            ->update('roles')
            ->set('perm_view_all_orgs', true)
            ->execute();
    }
}

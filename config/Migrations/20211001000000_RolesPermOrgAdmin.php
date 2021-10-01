<?php
declare(strict_types=1);

use Migrations\AbstractMigration;


class RolesPermOrgAdmin extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('roles')
            ->addColumn('perm_org_admin', 'boolean', [
                'default' => 0,
                'null' => false,
            ])
            ->update();
    }
}

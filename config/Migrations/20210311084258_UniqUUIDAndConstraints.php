<?php
declare(strict_types=1);

use Migrations\AbstractMigration;


class UniqUUIDAndConstraints extends AbstractMigration
{
    private $tablesRequiringUUIDMigration = [
        'auth_keys',
        'broods',
        'encryption_keys',
        'individuals',
        'meta_fields',
        'meta_templates',
        'organisations',
        'roles',
        'sharing_groups',
        'users',
    ];

    public function up()
    {
        foreach ($this->tablesRequiringUUIDMigration as $table) {
            $table = $this->table($table);
            $this->migrateUUID($table)->update();
        }

        // We don't need these table as we'll move to a more generic approach
        $this->table('alignment_tags')->drop()->save();
        $this->table('individual_encryption_keys')->drop()->save();
        $this->table('organisation_encryption_keys')->drop()->save();

        // If a user is deleted, so you its auth_keys
        $this->table('auth_keys')
            ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
            ->update();

        // Should an encryption_keys be tied to a user? In the UI it says to individuals/organisations but you can only set it from the user profile.
        $this->table('encryption_keys')
            ->renameColumn('owner_type', 'owner_model') // less confusing name & make it its length future-proof
            ->changeColumn('owner_model', 'string', [
                'length' => 40,
                'default' => null,
                'null' => false,
            ])
            ->update();

        // A meta_field belongs to both a template & a template field. If one of them is removed, so should the meta field
        // (We don't want floating meta_fields as there is no way to link it back to its corresponding template)
        $this->table('meta_fields')
            ->addForeignKey('meta_template_id', 'meta_templates', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
            ->addForeignKey('meta_template_field_id', 'meta_template_fields', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
            ->update();

        // sgo being an mapping table, we don't need an ID. We can use the two other FK as a composed PK
        $sgo = $this->table('sgo');
        if ($sgo->hasColumn('id')) {
            $sgo
                ->changePrimaryKey(['sharing_group_id', 'organisation_id'])
                ->removeColumn('id')
                ->update();
        }

        // A sharing group belongs to both a user & an organisation. If one of them is removed, so should the sharing group
        // (A sharing group without owner should not exists)
        $this->table('sharing_groups')
            ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
            ->addForeignKey('organisation_id', 'organisations', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
            ->update();

    }

    public function down()
    {
        /**
         * Operations not recoverable during a rollback:
         *  - Dropped tables (alignment_tags, individual_encryption_keys, organisation_encryption_keys)
         *  - sgo's `ID` primary key
         */
        foreach ($this->tablesRequiringUUIDMigration as $table) {
            $table = $this->table($table);
            $this->roolbackUUID($table)->update();
        }

        $this->table('auth_keys')
            ->dropForeignKey('user_id')
            ->update();

        $this->table('encryption_keys')
            ->renameColumn('owner_model', 'owner_type')
            ->changeColumn('owner_type', 'string', [
                'length' => 20,
                'default' => null,
                'null' => false,
            ])
            ->update();

        $this->table('meta_fields')
            ->dropForeignKey('meta_template_id')
            ->dropForeignKey('meta_template_field_id')
            ->update();

        $this->table('sharing_groups')
            ->dropForeignKey('user_id')
            ->dropForeignKey('organisation_id')
            ->update();
    }

    // public function change()
    // {
    //     if ($this->isMigratingUp()) {
    //         foreach ($this->tablesRequiringUUIDMigration as $table) {
    //             $table = $this->table($table);
    //             $this->migrateUUID($table)->update();
    //         }
    //     } else {
    //         foreach ($this->tablesRequiringUUIDMigration as $table) {
    //             $table = $this->table($table);
    //             $this->roolbackUUID($table)->update();
    //         }
    //     }

    //     if ($this->isMigratingUp()) {
    //         // We don't need these table as we'll move to a more generic approach
    //         $this->table('alignment_tags')->drop()->save();
    //         $this->table('individual_encryption_keys')->drop()->save();
    //         $this->table('organisation_encryption_keys')->drop()->save();
    //     }

    //     // If a user is deleted, so you its auth_keys
    //     $this->table('auth_keys')
    //         ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
    //         ->update();


    //     // Should an encryption_keys be tied to a user? In the UI it says to individuals/organisations but you can only set it from the user profile.
    //     $encryption_keys = $this->table('encryption_keys');
    //     $encryption_keys->renameColumn('owner_type', 'owner_model');
    //     if ($this->isMigratingUp()) {
    //         $encryption_keys->changeColumn('owner_model', 'string', [
    //             'length' => 40,
    //             'default' => null,
    //             'null' => false,
    //         ])->update();
    //     } else {
    //         $encryption_keys->changeColumn('owner_type', 'string', [
    //             'length' => 20,
    //             'default' => null,
    //             'null' => false,
    //         ])->update();
    //     }

    //     // A meta_field belongs to both a template & a template field. If one of them is removed, so should the meta field
    //     // (We don't want floating meta_fields as there is no way to link it back to its corresponding template)
    //     $this->table('meta_fields')
    //         ->addForeignKey('meta_template_id', 'meta_templates', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
    //         ->addForeignKey('meta_template_field_id', 'meta_template_fields', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
    //         ->update();

    //     // sgo being an mapping table, we don't need an ID. We can use the two other FK as a composed PK
    //     $sgo = $this->table('sgo');
    //     if ($this->isMigratingUp()) {
    //         $sgo->changePrimaryKey(['sharing_group_id', 'organisation_id'])
    //             ->removeColumn('id')
    //             ->update();
    //     } else {
    //         $sgo->addColumn('id', 'integer', [
    //             'autoIncrement' => true,
    //             'limit' => 10,
    //             'signed' => false,
    //         ])
    //             ->changePrimaryKey('id')
    //             ->update();
    //     }

    //     // A sharing group belongs to both a user & an organisation. If one of them is removed, so should the sharing group
    //     // (A sharing group without owner should not exists)
    //     $this->table('sharing_groups')
    //         ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
    //         ->addForeignKey('organisation_id', 'organisations', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
    //         ->update();
    // }

    // Use cake's built-in uuid type and ensure unicity
    private function migrateUUID($table)
    {
        $table->changeColumn('uuid', 'uuid', [
            'default' => null,
            'null' => false,
        ]);
        if ($table->hasIndex('uuid')) {
            $table->removeIndex(['uuid']); // remove existing non-unique index
        }
        $table->addIndex(['uuid'], ['unique' => true]);
        return $table;
    }

    private function roolbackUUID($table)
    {
        $table->changeColumn('uuid', 'string', [
            'limit' => 40,
            'null' => true,
        ])->removeIndex(['uuid']);
        return $table;
    }
}


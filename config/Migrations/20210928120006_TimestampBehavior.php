<?php
declare(strict_types=1);

use Migrations\AbstractMigration;


class TimestampBehavior extends AbstractMigration
{
    public function change()
    {
        $alignments = $this->table('alignments')
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->update();

        $broods = $this->table('broods')
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->update();

        $encryption_keys = $this->table('encryption_keys')
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->update();

        $inbox = $this->table('inbox')
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->update();

        $outbox = $this->table('outbox')
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->update();

        $individuals = $this->table('individuals')
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->update();

        $local_tools = $this->table('local_tools')
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->update();

        $meta_templates = $this->table('meta_templates')
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->update();

        $organisations = $this->table('organisations')
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->update();

        $sharing_groups = $this->table('sharing_groups')
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->update();

        $users = $this->table('users')
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->update();
    }
}
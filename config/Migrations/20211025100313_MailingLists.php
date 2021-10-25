<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class MailingLists extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */

    public $autoId = false; // turn off automatic `id` column create. We want it to be `int(10) unsigned`


    public function change()
    {
        $mailinglists = $this->table('mailing_lists', [
            'signed' => false,
            'collation' => 'utf8mb4_unicode_ci',
        ]);
        $mailinglists
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'limit' => 10,
                'signed' => false,
            ])
            ->addPrimaryKey('id')
            ->addColumn('uuid', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'null' => false,
                'limit' => 191,
                'comment' => 'The name of the mailing list',
            ])
            ->addColumn('recipients', 'string', [
                'default' => null,
                'null' => true,
                'limit' => 191,
                'comment' => 'Human-readable description of who the intended recipients.',
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => true,
                'comment' => 'Additional description of the mailing list'
            ])
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'null' => true,
                'signed' => false,
                'length' => 10,
            ])
            ->addColumn('active', 'boolean', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('deleted', 'boolean', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ]);


        $mailinglists->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);

        $mailinglists->addIndex(['uuid'], ['unique' => true])
              ->addIndex('name')
              ->addIndex('recipients')
              ->addIndex('user_id')
              ->addIndex('active')
              ->addIndex('deleted')
              ->addIndex('created')
              ->addIndex('modified');

        $mailinglists->create();


        $mailinglists_individuals = $this->table('mailing_lists_individuals', [
            'signed' => false,
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        $mailinglists_individuals
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'limit' => 10,
                'signed' => false,
            ])
            ->addPrimaryKey('id')
            ->addColumn('mailing_list_id', 'integer', [
                'default' => null,
                'null' => true,
                'signed' => false,
                'length' => 10,
            ])
            ->addColumn('individual_id', 'integer', [
                'default' => null,
                'null' => true,
                'signed' => false,
                'length' => 10,
            ]);

        $mailinglists_individuals->addIndex(['mailing_list_id', 'individual_id'], ['unique' => true]);

        $mailinglists_individuals->create();
    }
}


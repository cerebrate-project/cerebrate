<?php

declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class InitialSchema extends AbstractMigration
{
    public function change()
    {
        $this->execute('SET unique_checks=0; SET foreign_key_checks=0;');
        $this->execute("ALTER DATABASE CHARACTER SET 'utf8mb4';");
        $this->execute("ALTER DATABASE COLLATE='utf8mb4_general_ci';");
        $this->table('broods', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('uuid', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 40,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'id',
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'uuid',
            ])
            ->addColumn('url', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'name',
            ])
            ->addColumn('description', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'url',
            ])
            ->addColumn('organisation_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'description',
            ])
            ->addColumn('trusted', 'boolean', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'organisation_id',
            ])
            ->addColumn('pull', 'boolean', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'trusted',
            ])
            ->addColumn('skip_proxy', 'boolean', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'pull',
            ])
            ->addColumn('authkey', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 40,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'skip_proxy',
            ])
            ->addIndex(['uuid'], [
                'name' => 'uuid',
                'unique' => false,
            ])
            ->addIndex(['name'], [
                'name' => 'name',
                'unique' => false,
            ])
            ->addIndex(['url'], [
                'name' => 'url',
                'unique' => false,
            ])
            ->addIndex(['authkey'], [
                'name' => 'authkey',
                'unique' => false,
            ])
            ->addIndex(['organisation_id'], [
                'name' => 'organisation_id',
                'unique' => false,
            ])
            ->addForeignKey('organisation_id', 'organisations', 'id', [
                'constraint' => 'broods_ibfk_1',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->create();
        $this->table('sharing_groups', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('uuid', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 40,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'id',
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'uuid',
            ])
            ->addColumn('releasability', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'name',
            ])
            ->addColumn('description', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'releasability',
            ])
            ->addColumn('organisation_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'description',
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'organisation_id',
            ])
            ->addColumn('active', 'boolean', [
                'null' => true,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'user_id',
            ])
            ->addColumn('local', 'boolean', [
                'null' => true,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'active',
            ])
            ->addIndex(['uuid'], [
                'name' => 'uuid',
                'unique' => false,
            ])
            ->addIndex(['user_id'], [
                'name' => 'user_id',
                'unique' => false,
            ])
            ->addIndex(['organisation_id'], [
                'name' => 'organisation_id',
                'unique' => false,
            ])
            ->addIndex(['name'], [
                'name' => 'name',
                'unique' => false,
            ])
            ->create();
        $this->table('alignment_tags', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('alignment_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'id',
            ])
            ->addColumn('tag_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'alignment_id',
            ])
            ->addIndex(['alignment_id'], [
                'name' => 'alignment_id',
                'unique' => false,
            ])
            ->addIndex(['tag_id'], [
                'name' => 'tag_id',
                'unique' => false,
            ])
            ->addForeignKey('alignment_id', 'alignments', 'id', [
                'constraint' => 'alignment_tags_ibfk_1',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('tag_id', 'tags', 'id', [
                'constraint' => 'alignment_tags_ibfk_10',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('alignment_id', 'alignments', 'id', [
                'constraint' => 'alignment_tags_ibfk_11',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('tag_id', 'tags', 'id', [
                'constraint' => 'alignment_tags_ibfk_12',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('tag_id', 'tags', 'id', [
                'constraint' => 'alignment_tags_ibfk_2',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('alignment_id', 'alignments', 'id', [
                'constraint' => 'alignment_tags_ibfk_3',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('tag_id', 'tags', 'id', [
                'constraint' => 'alignment_tags_ibfk_4',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('alignment_id', 'alignments', 'id', [
                'constraint' => 'alignment_tags_ibfk_5',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('tag_id', 'tags', 'id', [
                'constraint' => 'alignment_tags_ibfk_6',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('alignment_id', 'alignments', 'id', [
                'constraint' => 'alignment_tags_ibfk_7',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('tag_id', 'tags', 'id', [
                'constraint' => 'alignment_tags_ibfk_8',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('alignment_id', 'alignments', 'id', [
                'constraint' => 'alignment_tags_ibfk_9',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->create();
        $this->table('meta_templates', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('scope', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'scope',
            ])
            ->addColumn('namespace', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'name',
            ])
            ->addColumn('description', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'namespace',
            ])
            ->addColumn('version', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'description',
            ])
            ->addColumn('uuid', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 40,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'version',
            ])
            ->addColumn('source', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'uuid',
            ])
            ->addColumn('enabled', 'boolean', [
                'null' => true,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'source',
            ])
            ->addColumn('is_default', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'enabled',
            ])
            ->addIndex(['scope'], [
                'name' => 'scope',
                'unique' => false,
            ])
            ->addIndex(['source'], [
                'name' => 'source',
                'unique' => false,
            ])
            ->addIndex(['name'], [
                'name' => 'name',
                'unique' => false,
            ])
            ->addIndex(['namespace'], [
                'name' => 'namespace',
                'unique' => false,
            ])
            ->addIndex(['version'], [
                'name' => 'version',
                'unique' => false,
            ])
            ->addIndex(['uuid'], [
                'name' => 'uuid',
                'unique' => false,
            ])
            ->create();
        $this->table('individuals', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('uuid', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 40,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'id',
            ])
            ->addColumn('email', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'uuid',
            ])
            ->addColumn('first_name', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'email',
            ])
            ->addColumn('last_name', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'first_name',
            ])
            ->addColumn('position', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'last_name',
            ])
            ->addIndex(['uuid'], [
                'name' => 'uuid',
                'unique' => false,
            ])
            ->addIndex(['email'], [
                'name' => 'email',
                'unique' => false,
            ])
            ->addIndex(['first_name'], [
                'name' => 'first_name',
                'unique' => false,
            ])
            ->addIndex(['last_name'], [
                'name' => 'last_name',
                'unique' => false,
            ])
            ->create();
        $this->table('organisations', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('uuid', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 40,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'id',
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'uuid',
            ])
            ->addColumn('url', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'name',
            ])
            ->addColumn('nationality', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'url',
            ])
            ->addColumn('sector', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'nationality',
            ])
            ->addColumn('type', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'sector',
            ])
            ->addColumn('contacts', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'type',
            ])
            ->addIndex(['uuid'], [
                'name' => 'uuid',
                'unique' => false,
            ])
            ->addIndex(['name'], [
                'name' => 'name',
                'unique' => false,
            ])
            ->addIndex(['url'], [
                'name' => 'url',
                'unique' => false,
            ])
            ->addIndex(['nationality'], [
                'name' => 'nationality',
                'unique' => false,
            ])
            ->addIndex(['sector'], [
                'name' => 'sector',
                'unique' => false,
            ])
            ->addIndex(['type'], [
                'name' => 'type',
                'unique' => false,
            ])
            ->create();
        $this->table('encryption_keys', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('uuid', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 40,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'id',
            ])
            ->addColumn('type', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'uuid',
            ])
            ->addColumn('encryption_key', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'type',
            ])
            ->addColumn('revoked', 'boolean', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'encryption_key',
            ])
            ->addColumn('expires', 'integer', [
                'null' => true,
                'default' => null,
                'limit' => '10',
                'signed' => false,
                'after' => 'revoked',
            ])
            ->addColumn('owner_id', 'integer', [
                'null' => true,
                'default' => null,
                'limit' => '10',
                'signed' => false,
                'after' => 'expires',
            ])
            ->addColumn('owner_type', 'string', [
                'null' => false,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'owner_id',
            ])
            ->addIndex(['uuid'], [
                'name' => 'uuid',
                'unique' => false,
            ])
            ->addIndex(['type'], [
                'name' => 'type',
                'unique' => false,
            ])
            ->addIndex(['expires'], [
                'name' => 'expires',
                'unique' => false,
            ])
            ->create();
        $this->table('meta_fields', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('scope', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('parent_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'scope',
            ])
            ->addColumn('field', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'parent_id',
            ])
            ->addColumn('value', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'field',
            ])
            ->addColumn('uuid', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 40,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'value',
            ])
            ->addColumn('meta_template_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'uuid',
            ])
            ->addColumn('meta_template_field_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'meta_template_id',
            ])
            ->addColumn('is_default', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'meta_template_field_id',
            ])
            ->addIndex(['scope'], [
                'name' => 'scope',
                'unique' => false,
            ])
            ->addIndex(['uuid'], [
                'name' => 'uuid',
                'unique' => false,
            ])
            ->addIndex(['parent_id'], [
                'name' => 'parent_id',
                'unique' => false,
            ])
            ->addIndex(['field'], [
                'name' => 'field',
                'unique' => false,
            ])
            ->addIndex(['value'], [
                'name' => 'value',
                'unique' => false,
            ])
            ->addIndex(['meta_template_id'], [
                'name' => 'meta_template_id',
                'unique' => false,
            ])
            ->addIndex(['meta_template_field_id'], [
                'name' => 'meta_template_field_id',
                'unique' => false,
            ])
            ->create();
        $this->table('audit_logs', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'after' => 'id',
            ])
            ->addColumn('user_id', 'integer', [
                'null' => true,
                'default' => null,
                'limit' => '10',
                'signed' => false,
                'after' => 'created',
            ])
            ->addColumn('authkey_id', 'integer', [
                'null' => true,
                'default' => null,
                'limit' => '10',
                'signed' => false,
                'after' => 'user_id',
            ])
            ->addColumn('request_ip', 'varbinary', [
                'null' => true,
                'default' => null,
                'limit' => 16,
                'after' => 'authkey_id',
            ])
            ->addColumn('request_type', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'request_ip',
            ])
            ->addColumn('request_id', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'request_type',
            ])
            ->addColumn('request_action', 'string', [
                'null' => false,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'request_id',
            ])
            ->addColumn('model', 'string', [
                'null' => false,
                'limit' => 80,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'request_action',
            ])
            ->addColumn('model_id', 'integer', [
                'null' => true,
                'default' => null,
                'limit' => '10',
                'signed' => false,
                'after' => 'model',
            ])
            ->addColumn('model_title', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'model_id',
            ])
            ->addColumn('change', 'blob', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::BLOB_REGULAR,
                'after' => 'model_title',
            ])
            ->addIndex(['user_id'], [
                'name' => 'user_id',
                'unique' => false,
            ])
            ->addIndex(['request_ip'], [
                'name' => 'request_ip',
                'unique' => false,
            ])
            ->addIndex(['model'], [
                'name' => 'model',
                'unique' => false,
            ])
            ->addIndex(['request_action'], [
                'name' => 'request_action',
                'unique' => false,
            ])
            ->addIndex(['model_id'], [
                'name' => 'model_id',
                'unique' => false,
            ])
            ->addIndex(['created'], [
                'name' => 'created',
                'unique' => false,
            ])
            ->create();
        $this->table('organisation_encryption_keys', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('organisation_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'id',
            ])
            ->addColumn('encryption_key_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'organisation_id',
            ])
            ->addIndex(['organisation_id'], [
                'name' => 'organisation_id',
                'unique' => false,
            ])
            ->addIndex(['encryption_key_id'], [
                'name' => 'encryption_key_id',
                'unique' => false,
            ])
            ->addForeignKey('organisation_id', 'organisations', 'id', [
                'constraint' => 'organisation_encryption_keys_ibfk_1',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('encryption_key_id', 'encryption_keys', 'id', [
                'constraint' => 'organisation_encryption_keys_ibfk_2',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->create();
        $this->table('users', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('uuid', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 40,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'id',
            ])
            ->addColumn('username', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'uuid',
            ])
            ->addColumn('password', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'username',
            ])
            ->addColumn('role_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'password',
            ])
            ->addColumn('individual_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'role_id',
            ])
            ->addColumn('disabled', 'boolean', [
                'null' => true,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'individual_id',
            ])
            ->addIndex(['uuid'], [
                'name' => 'uuid',
                'unique' => false,
            ])
            ->addIndex(['role_id'], [
                'name' => 'role_id',
                'unique' => false,
            ])
            ->addIndex(['individual_id'], [
                'name' => 'individual_id',
                'unique' => false,
            ])
            ->addForeignKey('role_id', 'roles', 'id', [
                'constraint' => 'users_ibfk_1',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('individual_id', 'individuals', 'id', [
                'constraint' => 'users_ibfk_2',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->create();
        $this->table('roles', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('uuid', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 40,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'id',
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'uuid',
            ])
            ->addColumn('is_default', 'boolean', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'name',
            ])
            ->addColumn('perm_admin', 'boolean', [
                'null' => true,
                'default' => null,
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'is_default',
            ])
            ->addIndex(['name'], [
                'name' => 'name',
                'unique' => false,
            ])
            ->addIndex(['uuid'], [
                'name' => 'uuid',
                'unique' => false,
            ])
            ->create();
        $this->table('tags', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('description', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'name',
            ])
            ->addColumn('colour', 'string', [
                'null' => false,
                'limit' => 6,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'description',
            ])
            ->addIndex(['name'], [
                'name' => 'name',
                'unique' => false,
            ])
            ->create();
        $this->table('individual_encryption_keys', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('individual_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'id',
            ])
            ->addColumn('encryption_key_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'individual_id',
            ])
            ->addIndex(['individual_id'], [
                'name' => 'individual_id',
                'unique' => false,
            ])
            ->addIndex(['encryption_key_id'], [
                'name' => 'encryption_key_id',
                'unique' => false,
            ])
            ->addForeignKey('individual_id', 'individuals', 'id', [
                'constraint' => 'individual_encryption_keys_ibfk_1',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('encryption_key_id', 'encryption_keys', 'id', [
                'constraint' => 'individual_encryption_keys_ibfk_2',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('individual_id', 'individuals', 'id', [
                'constraint' => 'individual_encryption_keys_ibfk_3',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('encryption_key_id', 'encryption_keys', 'id', [
                'constraint' => 'individual_encryption_keys_ibfk_4',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('individual_id', 'individuals', 'id', [
                'constraint' => 'individual_encryption_keys_ibfk_5',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('encryption_key_id', 'encryption_keys', 'id', [
                'constraint' => 'individual_encryption_keys_ibfk_6',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->create();
        $this->table('meta_template_fields', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('field', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('type', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'field',
            ])
            ->addColumn('meta_template_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'type',
            ])
            ->addColumn('regex', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'meta_template_id',
            ])
            ->addColumn('multiple', 'boolean', [
                'null' => true,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'regex',
            ])
            ->addColumn('enabled', 'boolean', [
                'null' => true,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'multiple',
            ])
            ->addIndex(['meta_template_id'], [
                'name' => 'meta_template_id',
                'unique' => false,
            ])
            ->addIndex(['field'], [
                'name' => 'field',
                'unique' => false,
            ])
            ->addIndex(['type'], [
                'name' => 'type',
                'unique' => false,
            ])
            ->addForeignKey('meta_template_id', 'meta_templates', 'id', [
                'constraint' => 'meta_template_id',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->create();
        $this->table('auth_keys', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('uuid', 'string', [
                'null' => false,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('authkey', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 72,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'uuid',
            ])
            ->addColumn('authkey_start', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 4,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'authkey',
            ])
            ->addColumn('authkey_end', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 4,
                'collation' => 'ascii_general_ci',
                'encoding' => 'ascii',
                'after' => 'authkey_start',
            ])
            ->addColumn('created', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'authkey_end',
            ])
            ->addColumn('expiration', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'created',
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'expiration',
            ])
            ->addColumn('comment', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'user_id',
            ])
            ->addIndex(['authkey_start'], [
                'name' => 'authkey_start',
                'unique' => false,
            ])
            ->addIndex(['authkey_end'], [
                'name' => 'authkey_end',
                'unique' => false,
            ])
            ->addIndex(['created'], [
                'name' => 'created',
                'unique' => false,
            ])
            ->addIndex(['expiration'], [
                'name' => 'expiration',
                'unique' => false,
            ])
            ->addIndex(['user_id'], [
                'name' => 'user_id',
                'unique' => false,
            ])
            ->create();
        $this->table('local_tools', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'id',
            ])
            ->addColumn('connector', 'string', [
                'null' => false,
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'name',
            ])
            ->addColumn('settings', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'connector',
            ])
            ->addColumn('exposed', 'boolean', [
                'null' => false,
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'settings',
            ])
            ->addColumn('description', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'exposed',
            ])
            ->addIndex(['name'], [
                'name' => 'name',
                'unique' => false,
            ])
            ->addIndex(['connector'], [
                'name' => 'connector',
                'unique' => false,
            ])
            ->create();
        $this->table('alignments', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('individual_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'id',
            ])
            ->addColumn('organisation_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'individual_id',
            ])
            ->addColumn('type', 'string', [
                'null' => true,
                'default' => 'member',
                'limit' => 191,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'organisation_id',
            ])
            ->addIndex(['individual_id'], [
                'name' => 'individual_id',
                'unique' => false,
            ])
            ->addIndex(['organisation_id'], [
                'name' => 'organisation_id',
                'unique' => false,
            ])
            ->addForeignKey('individual_id', 'individuals', 'id', [
                'constraint' => 'alignments_ibfk_1',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey('organisation_id', 'organisations', 'id', [
                'constraint' => 'alignments_ibfk_2',
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->create();
        $this->table('sgo', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'row_format' => 'DYNAMIC',
        ])
            ->addColumn('id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('sharing_group_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'id',
            ])
            ->addColumn('organisation_id', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
                'after' => 'sharing_group_id',
            ])
            ->addColumn('deleted', 'boolean', [
                'null' => true,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'organisation_id',
            ])
            ->addIndex(['sharing_group_id'], [
                'name' => 'sharing_group_id',
                'unique' => false,
            ])
            ->addIndex(['organisation_id'], [
                'name' => 'organisation_id',
                'unique' => false,
            ])
            ->create();
        $this->execute('SET unique_checks=1; SET foreign_key_checks=1;');
    }
}

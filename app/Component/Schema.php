<?php

namespace App\Component;

use InvalidArgumentException;
use Viloveul\Database\Contracts\Schema as ISchema;
use Viloveul\Database\Contracts\Manager as Database;

class Schema
{
    /**
     * @var mixed
     */
    protected $connection;

    /**
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->connection = $db->getConnection();
    }

    /**
     * @param $table
     */
    public function install(string $table): void
    {
        switch ($table) {
            case 'user':
                $schema = $this->connection->newSchema($table);
                $schema->set('id', ISchema::TYPE_CHAR, 50)->primary();
                $schema->set('name', ISchema::TYPE_VARCHAR)->index();
                $schema->set('picture', ISchema::TYPE_VARCHAR)->nullable()->index();
                $schema->set('email', ISchema::TYPE_VARCHAR)->unique();
                $schema->set('username', ISchema::TYPE_VARCHAR)->unique();
                $schema->set('password', ISchema::TYPE_VARCHAR)->index();
                $schema->set('status', ISchema::TYPE_INT, 1)->withDefault(0)->index();
                $schema->set('created_at', ISchema::TYPE_TIMESTAMP)->withDefault('CURRENT_TIMESTAMP')->index();
                $schema->set('updated_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->set('deleted_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->run();
                break;

            case 'user_password':
                $schema = $this->connection->newSchema($table);
                $schema->set('id', ISchema::TYPE_CHAR, 50)->primary();
                $schema->set('user_id', ISchema::TYPE_CHAR, 50)->index();
                $schema->set('password', ISchema::TYPE_VARCHAR)->index();
                $schema->set('expired', ISchema::TYPE_VARCHAR)->index();
                $schema->set('status', ISchema::TYPE_INT)->withDefault(0)->index();
                $schema->set('created_at', ISchema::TYPE_TIMESTAMP)->withDefault('CURRENT_TIMESTAMP')->index();
                $schema->run();
                break;

            case 'user_profile':
                $schema = $this->connection->newSchema($table);
                $schema->set('id', ISchema::TYPE_CHAR, 50)->primary();
                $schema->set('user_id', ISchema::TYPE_CHAR, 50)->index();
                $schema->set('name', ISchema::TYPE_VARCHAR)->index();
                $schema->set('value', ISchema::TYPE_TEXT)->nullable();
                $schema->set('last_modified', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->unique('user_id', 'name');
                $schema->run();
                break;

            case 'user_role':
                $schema = $this->connection->newSchema($table);
                $schema->set('user_id', ISchema::TYPE_CHAR, 50)->index();
                $schema->set('role_id', ISchema::TYPE_CHAR, 50)->index();
                $schema->unique('user_id', 'role_id');
                $schema->primary('user_id', 'role_id');
                $schema->run();
                break;

            case 'role':
                $schema = $this->connection->newSchema($table);
                $schema->set('id', ISchema::TYPE_CHAR, 50)->primary();
                $schema->set('name', ISchema::TYPE_VARCHAR)->unique();
                $schema->set('type', ISchema::TYPE_VARCHAR)->withDefault('access')->index();
                $schema->set('description', ISchema::TYPE_TEXT)->nullable();
                $schema->set('status', ISchema::TYPE_INT)->withDefault(1)->index();
                $schema->set('created_at', ISchema::TYPE_TIMESTAMP)->withDefault('CURRENT_TIMESTAMP')->index();
                $schema->set('updated_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->set('deleted_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->run();
                break;

            case 'role_child':
                $schema = $this->connection->newSchema($table);
                $schema->set('role_id', ISchema::TYPE_CHAR, 50)->index();
                $schema->set('child_id', ISchema::TYPE_CHAR, 50)->index();
                $schema->unique('role_id', 'child_id');
                $schema->primary('role_id', 'child_id');
                $schema->run();
                break;

            case 'setting':
                $schema = $this->connection->newSchema($table);
                $schema->set('id', ISchema::TYPE_CHAR, 50)->primary();
                $schema->set('name', ISchema::TYPE_VARCHAR)->unique();
                $schema->set('option', ISchema::TYPE_TEXT)->nullable();
                $schema->set('created_at', ISchema::TYPE_TIMESTAMP)->withDefault('CURRENT_TIMESTAMP')->index();
                $schema->set('updated_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->run();
                break;

            case 'tag':
                $schema = $this->connection->newSchema($table);
                $schema->set('id', ISchema::TYPE_CHAR, 50)->primary();
                $schema->set('parent_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
                $schema->set('author_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
                $schema->set('title', ISchema::TYPE_VARCHAR)->index();
                $schema->set('slug', ISchema::TYPE_VARCHAR)->unique();
                $schema->set('type', ISchema::TYPE_VARCHAR)->withDefault('tag')->index();
                $schema->set('status', ISchema::TYPE_INT)->withDefault(1)->index();
                $schema->set('created_at', ISchema::TYPE_TIMESTAMP)->withDefault('CURRENT_TIMESTAMP')->index();
                $schema->set('updated_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->set('deleted_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->run();
                break;

            case 'post':
                $schema = $this->connection->newSchema($table);
                $schema->set('id', ISchema::TYPE_CHAR, 50)->primary();
                $schema->set('parent_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
                $schema->set('author_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
                $schema->set('slug', ISchema::TYPE_VARCHAR)->unique();
                $schema->set('title', ISchema::TYPE_VARCHAR)->index();
                $schema->set('cover', ISchema::TYPE_VARCHAR)->nullable();
                $schema->set('type', ISchema::TYPE_VARCHAR)->withDefault('post')->index();
                $schema->set('description', ISchema::TYPE_TEXT)->nullable();
                $schema->set('content', ISchema::TYPE_TEXT)->nullable();
                $schema->set('comment_enabled', ISchema::TYPE_INT)->withDefault(1)->index();
                $schema->set('status', ISchema::TYPE_INT)->withDefault(0)->index();
                $schema->set('created_at', ISchema::TYPE_TIMESTAMP)->withDefault('CURRENT_TIMESTAMP')->index();
                $schema->set('updated_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->set('deleted_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->run();
                break;

            case 'menu':
                $schema = $this->connection->newSchema($table);
                $schema->set('id', ISchema::TYPE_CHAR, 50)->primary();
                $schema->set('author_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
                $schema->set('label', ISchema::TYPE_VARCHAR)->index();
                $schema->set('description', ISchema::TYPE_TEXT)->nullable();
                $schema->set('content', ISchema::TYPE_TEXT)->nullable();
                $schema->set('status', ISchema::TYPE_INT)->withDefault(0)->index();
                $schema->set('created_at', ISchema::TYPE_TIMESTAMP)->withDefault('CURRENT_TIMESTAMP')->index();
                $schema->set('updated_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->set('deleted_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->run();
                break;

            case 'link':
                $schema = $this->connection->newSchema($table);
                $schema->set('id', ISchema::TYPE_CHAR, 50)->primary();
                $schema->set('author_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
                $schema->set('role_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
                $schema->set('label', ISchema::TYPE_VARCHAR)->index();
                $schema->set('icon', ISchema::TYPE_VARCHAR)->nullable();
                $schema->set('description', ISchema::TYPE_TEXT)->nullable();
                $schema->set('url', ISchema::TYPE_TEXT)->nullable();
                $schema->set('status', ISchema::TYPE_INT)->withDefault(0)->index();
                $schema->set('created_at', ISchema::TYPE_TIMESTAMP)->withDefault('CURRENT_TIMESTAMP')->index();
                $schema->set('updated_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->set('deleted_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->run();
                break;

            // case 'menu_item':
            //     $schema = $this->connection->newSchema($table);
            //     $schema->set('id', ISchema::TYPE_CHAR, 50)->primary();
            //     $schema->set('parent_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
            //     $schema->set('menu_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
            //     $schema->set('link_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
            //     $schema->set('order', ISchema::TYPE_INT)->withDefault(0)->index();
            //     $schema->run();
            //     break;

            case 'post_tag':
                $schema = $this->connection->newSchema($table);
                $schema->set('post_id', ISchema::TYPE_CHAR, 50)->index();
                $schema->set('tag_id', ISchema::TYPE_CHAR, 50)->index();
                $schema->unique('post_id', 'tag_id');
                $schema->primary('post_id', 'tag_id');
                $schema->run();
                break;

            case 'comment':
                $schema = $this->connection->newSchema($table);
                $schema->set('id', ISchema::TYPE_CHAR, 50)->primary();
                $schema->set('post_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
                $schema->set('parent_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
                $schema->set('author_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
                $schema->set('name', ISchema::TYPE_VARCHAR)->index();
                $schema->set('email', ISchema::TYPE_VARCHAR)->index();
                $schema->set('website', ISchema::TYPE_VARCHAR)->nullable()->index();
                $schema->set('content', ISchema::TYPE_TEXT)->nullable();
                $schema->set('status', ISchema::TYPE_INT)->withDefault(0)->index();
                $schema->set('created_at', ISchema::TYPE_TIMESTAMP)->withDefault('CURRENT_TIMESTAMP')->index();
                $schema->set('updated_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->set('deleted_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->run();
                break;

            case 'notification':
                $schema = $this->connection->newSchema($table);
                $schema->set('id', ISchema::TYPE_CHAR, 50)->primary();
                $schema->set('author_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
                $schema->set('receiver_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
                $schema->set('subject', ISchema::TYPE_VARCHAR)->index();
                $schema->set('content', ISchema::TYPE_TEXT)->nullable();
                $schema->set('status', ISchema::TYPE_INT)->withDefault(0)->index();
                $schema->set('created_at', ISchema::TYPE_TIMESTAMP)->withDefault('CURRENT_TIMESTAMP')->index();
                $schema->set('updated_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->run();
                break;

            case 'media':
                $schema = $this->connection->newSchema($table);
                $schema->set('id', ISchema::TYPE_CHAR, 50)->primary();
                $schema->set('author_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
                $schema->set('name', ISchema::TYPE_VARCHAR)->index();
                $schema->set('filename', ISchema::TYPE_VARCHAR)->unique();
                $schema->set('type', ISchema::TYPE_VARCHAR)->index();
                $schema->set('ref', ISchema::TYPE_VARCHAR)->index();
                $schema->set('size', ISchema::TYPE_VARCHAR)->index();
                $schema->set('year', ISchema::TYPE_VARCHAR)->index();
                $schema->set('month', ISchema::TYPE_VARCHAR)->index();
                $schema->set('day', ISchema::TYPE_VARCHAR)->index();
                $schema->set('status', ISchema::TYPE_INT)->withDefault(0)->index();
                $schema->set('created_at', ISchema::TYPE_TIMESTAMP)->withDefault('CURRENT_TIMESTAMP')->index();
                $schema->set('updated_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->set('deleted_at', ISchema::TYPE_TIMESTAMP)->nullable()->index();
                $schema->run();
                break;

            case 'audit':
                $schema = $this->connection->newSchema($table);
                $schema->set('id', ISchema::TYPE_CHAR, 50)->primary();
                $schema->set('author_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
                $schema->set('object_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
                $schema->set('entity', ISchema::TYPE_VARCHAR)->index();
                $schema->set('ip', ISchema::TYPE_VARCHAR)->nullable()->index();
                $schema->set('type', ISchema::TYPE_VARCHAR)->index();
                $schema->set('agent', ISchema::TYPE_TEXT)->nullable();
                $schema->set('created_at', ISchema::TYPE_TIMESTAMP)->withDefault('CURRENT_TIMESTAMP')->index();
                $schema->run();
                break;

            case 'audit_detail':
                $schema = $this->connection->newSchema($table);
                $schema->set('id', ISchema::TYPE_CHAR, 50)->primary();
                $schema->set('audit_id', ISchema::TYPE_CHAR, 50)->withDefault(0)->index();
                $schema->set('resource', ISchema::TYPE_VARCHAR)->index();
                $schema->set('previous', ISchema::TYPE_TEXT)->nullable();
                $schema->run();
                break;

            default:
                throw new InvalidArgumentException("Definition table {$table} not exists.");
                break;
        }
    }
}
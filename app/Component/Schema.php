<?php

namespace App\Component;

use App\Database;
use Illuminate\Database\Schema\Blueprint;

class Schema
{
    /**
     * @var mixed
     */
    protected $builder;

    /**
     * @var string
     */
    protected $connectionName = 'viloveul';

    /**
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $connection = $db->getConnection($this->connectionName);
        $this->builder = $connection->getSchemaBuilder();
    }

    /**
     * @param $name
     */
    public function alter($name)
    {
        $builder = $this->builder;

        if ($name == 'user') {
            $builder->table($name, function (Blueprint $table) use ($builder, $name) {
                $builder->hasColumn($name, 'name') or $table->string('name')->index();
                $builder->hasColumn($name, 'picture') or $table->string('picture')->nullable()->index();
                $builder->hasColumn($name, 'email') or $table->string('email')->unique();
                $builder->hasColumn($name, 'username') or $table->string('username')->unique();
                $builder->hasColumn($name, 'password') or $table->string('password')->index();
                $builder->hasColumn($name, 'status') or $table->integer('status')->default(0)->index();
                $builder->hasColumn($name, 'created_at') or $table->timestamp('created_at')->useCurrent()->index();
                $builder->hasColumn($name, 'updated_at') or $table->timestamp('updated_at')->nullable()->index();
                $builder->hasColumn($name, 'deleted_at') or $table->timestamp('deleted_at')->nullable()->index();
            });
        }

        if ($name == 'user_password') {
            $builder->table($name, function (Blueprint $table) use ($builder, $name) {
                $builder->hasColumn($name, 'user_id') or $table->uuid('user_id')->index();
                $builder->hasColumn($name, 'password') or $table->string('password')->index();
                $builder->hasColumn($name, 'expired') or $table->string('expired')->index();
                $builder->hasColumn($name, 'status') or $table->integer('status')->default(0)->index();
                $builder->hasColumn($name, 'created_at') or $table->timestamp('created_at')->useCurrent()->index();
            });
        }

        if ($name == 'user_profile') {
            $builder->table($name, function (Blueprint $table) use ($builder, $name) {
                $builder->hasColumn($name, 'user_id') or $table->uuid('user_id')->index();
                $builder->hasColumn($name, 'name') or $table->string('name')->index();
                $builder->hasColumn($name, 'value') or $table->text('name')->nullable();
                $builder->hasColumn($name, 'last_modified') or $table->timestamp('last_modified')->nullable()->index();
            });
        }

        if ($name == 'user_role') {
            $builder->table($name, function (Blueprint $table) use ($builder, $name) {
                $builder->hasColumn($name, 'user_id') or $table->uuid('user_id')->index();
                $builder->hasColumn($name, 'role_id') or $table->uuid('role_id')->index();
            });
        }

        if ($name == 'role') {
            $builder->table($name, function (Blueprint $table) use ($builder, $name) {
                $builder->hasColumn($name, 'name') or $table->string('name')->unique();
                $builder->hasColumn($name, 'type') or $table->string('type')->default('access')->index();
                $builder->hasColumn($name, 'description') or $table->text('description')->nullable();
                $builder->hasColumn($name, 'status') or $table->integer('status')->default(1)->index();
                $builder->hasColumn($name, 'created_at') or $table->timestamp('created_at')->useCurrent()->index();
                $builder->hasColumn($name, 'updated_at') or $table->timestamp('updated_at')->nullable()->index();
                $builder->hasColumn($name, 'deleted_at') or $table->timestamp('deleted_at')->nullable()->index();
            });
        }

        if ($name == 'role_child') {
            $builder->table($name, function (Blueprint $table) use ($builder, $name) {
                $builder->hasColumn($name, 'role_id') or $table->uuid('role_id')->index();
                $builder->hasColumn($name, 'child_id') or $table->uuid('child_id')->index();
            });
        }

        if ($name == 'setting') {
            $builder->table($name, function (Blueprint $table) use ($builder, $name) {
                $builder->hasColumn($name, 'name') or $table->string('name')->unique();
                $builder->hasColumn($name, 'option') or $table->text('option')->nullable();
                $builder->hasColumn($name, 'created_at') or $table->timestamp('created_at')->useCurrent()->index();
                $builder->hasColumn($name, 'updated_at') or $table->timestamp('updated_at')->nullable()->index();
            });
        }

        if ($name == 'tag') {
            $builder->table($name, function (Blueprint $table) use ($builder, $name) {
                $builder->hasColumn($name, 'parent_id') or $table->uuid('parent_id')->default(0)->index();
                $builder->hasColumn($name, 'author_id') or $table->uuid('author_id')->default(0)->index();
                $builder->hasColumn($name, 'title') or $table->string('title')->index();
                $builder->hasColumn($name, 'slug') or $table->string('slug')->unique();
                $builder->hasColumn($name, 'type') or $table->string('type')->default('tag')->index();
                $builder->hasColumn($name, 'status') or $table->integer('status')->default(1)->index();
                $builder->hasColumn($name, 'created_at') or $table->timestamp('created_at')->useCurrent()->index();
                $builder->hasColumn($name, 'updated_at') or $table->timestamp('updated_at')->nullable()->index();
                $builder->hasColumn($name, 'deleted_at') or $table->timestamp('deleted_at')->nullable()->index();
            });
        }

        if ($name == 'post') {
            $builder->table($name, function (Blueprint $table) use ($builder, $name) {
                $builder->hasColumn($name, 'parent_id') or $table->uuid('parent_id')->default(0)->index();
                $builder->hasColumn($name, 'author_id') or $table->uuid('author_id')->default(0)->index();
                $builder->hasColumn($name, 'slug') or $table->string('slug')->unique();
                $builder->hasColumn($name, 'title') or $table->string('title')->index();
                $builder->hasColumn($name, 'cover') or $table->string('cover')->nullable();
                $builder->hasColumn($name, 'type') or $table->string('type')->index();
                $builder->hasColumn($name, 'description') or $table->text('description')->nullable();
                $builder->hasColumn($name, 'content') or $table->text('content')->nullable();
                $builder->hasColumn($name, 'comment_enabled') or $table->integer('comment_enabled')->default(0)->index();
                $builder->hasColumn($name, 'status') or $table->integer('status')->default(0)->index();
                $builder->hasColumn($name, 'created_at') or $table->timestamp('created_at')->useCurrent()->index();
                $builder->hasColumn($name, 'updated_at') or $table->timestamp('updated_at')->nullable()->index();
                $builder->hasColumn($name, 'deleted_at') or $table->timestamp('deleted_at')->nullable()->index();
            });
        }

        if ($name == 'menu') {
            $builder->table($name, function (Blueprint $table) use ($builder, $name) {
                $builder->hasColumn($name, 'author_id') or $table->uuid('author_id')->default(0)->index();
                $builder->hasColumn($name, 'label') or $table->string('label')->index();
                $builder->hasColumn($name, 'icon') or $table->string('icon')->nullable();
                $builder->hasColumn($name, 'type') or $table->string('type')->index();
                $builder->hasColumn($name, 'description') or $table->text('description')->nullable();
                $builder->hasColumn($name, 'url') or $table->text('url')->nullable();
                $builder->hasColumn($name, 'status') or $table->integer('status')->default(0)->index();
                $builder->hasColumn($name, 'created_at') or $table->timestamp('created_at')->useCurrent()->index();
                $builder->hasColumn($name, 'updated_at') or $table->timestamp('updated_at')->nullable()->index();
                $builder->hasColumn($name, 'deleted_at') or $table->timestamp('deleted_at')->nullable()->index();
            });
        }

        if ($name == 'post_tag') {
            $builder->table($name, function (Blueprint $table) use ($builder, $name) {
                $builder->hasColumn($name, 'post_id') or $table->uuid('post_id')->index();
                $builder->hasColumn($name, 'tag_id') or $table->uuid('tag_id')->index();
            });
        }

        if ($name == 'comment') {
            $builder->table($name, function (Blueprint $table) use ($builder, $name) {
                $builder->hasColumn($name, 'post_id') or $table->uuid('post_id')->index();
                $builder->hasColumn($name, 'parent_id') or $table->uuid('parent_id')->default(0)->index();
                $builder->hasColumn($name, 'author_id') or $table->uuid('author_id')->default(0)->index();
                $builder->hasColumn($name, 'name') or $table->string('name')->index();
                $builder->hasColumn($name, 'email') or $table->string('email')->index();
                $builder->hasColumn($name, 'website') or $table->string('website')->nullable()->index();
                $builder->hasColumn($name, 'content') or $table->text('content')->nullable();
                $builder->hasColumn($name, 'status') or $table->integer('status')->default(0)->index();
                $builder->hasColumn($name, 'created_at') or $table->timestamp('created_at')->useCurrent()->index();
                $builder->hasColumn($name, 'updated_at') or $table->timestamp('updated_at')->nullable()->index();
                $builder->hasColumn($name, 'deleted_at') or $table->timestamp('deleted_at')->nullable()->index();
            });
        }

        if ($name == 'notification') {
            $builder->table($name, function (Blueprint $table) use ($builder, $name) {
                $builder->hasColumn($name, 'author_id') or $table->uuid('author_id')->default(0)->index();
                $builder->hasColumn($name, 'receiver_id') or $table->uuid('receiver_id')->default(0)->index();
                $builder->hasColumn($name, 'subject') or $table->string('subject')->index();
                $builder->hasColumn($name, 'content') or $table->text('content')->nullable();
                $builder->hasColumn($name, 'status') or $table->integer('status')->default(0)->index();
                $builder->hasColumn($name, 'created_at') or $table->timestamp('created_at')->useCurrent()->index();
                $builder->hasColumn($name, 'updated_at') or $table->timestamp('updated_at')->nullable()->index();
            });
        }

        if ($name == 'media') {
            $builder->table($name, function (Blueprint $table) use ($builder, $name) {
                $builder->hasColumn($name, 'author_id') or $table->uuid('author_id')->default(0)->index();
                $builder->hasColumn($name, 'name') or $table->string('name')->index();
                $builder->hasColumn($name, 'filename') or $table->string('filename')->unique();
                $builder->hasColumn($name, 'ref') or $table->string('ref')->index();
                $builder->hasColumn($name, 'type') or $table->string('type')->index();
                $builder->hasColumn($name, 'size') or $table->string('size')->index();
                $builder->hasColumn($name, 'year') or $table->string('year')->index();
                $builder->hasColumn($name, 'month') or $table->string('month')->index();
                $builder->hasColumn($name, 'day') or $table->string('day')->index();
                $builder->hasColumn($name, 'status') or $table->integer('status')->default(0)->index();
                $builder->hasColumn($name, 'created_at') or $table->timestamp('created_at')->useCurrent()->index();
                $builder->hasColumn($name, 'updated_at') or $table->timestamp('updated_at')->nullable()->index();
                $builder->hasColumn($name, 'deleted_at') or $table->timestamp('deleted_at')->nullable()->index();
            });
        }

        if ($name == 'audit') {
            $builder->table($name, function (Blueprint $table) use ($builder, $name) {
                $builder->hasColumn($name, 'author_id') or $table->uuid('author_id')->default(0)->index();
                $builder->hasColumn($name, 'object_id') or $table->uuid('object_id')->default(0)->index();
                $builder->hasColumn($name, 'entity') or $table->string('entity')->index();
                $builder->hasColumn($name, 'ip') or $table->string('ip')->nullable()->index();
                $builder->hasColumn($name, 'agent') or $table->text('agent')->nullable();
                $builder->hasColumn($name, 'type') or $table->string('type')->index();
                $builder->hasColumn($name, 'created_at') or $table->timestamp('created_at')->useCurrent()->index();
            });
        }

        if ($name == 'audit_detail') {
            $builder->table($name, function (Blueprint $table) use ($builder, $name) {
                $builder->hasColumn($name, 'audit_id') or $table->uuid('audit_id')->default(0)->index();
                $builder->hasColumn($name, 'resource') or $table->string('resource')->index();
                $builder->hasColumn($name, 'previous') or $table->text('previous')->nullable();
            });
        }
    }

    /**
     * @param  $name
     * @return mixed
     */
    public function check($name)
    {
        return $this->builder->hasTable($name);
    }

    /**
     * @param $name
     */
    public function install($name)
    {
        $builder = $this->builder;

        if ($name == 'user') {
            $builder->create($name, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name')->index();
                $table->string('picture')->nullable()->index();
                $table->string('email')->unique();
                $table->string('username')->unique();
                $table->string('password')->index();
                $table->integer('status')->default(0)->index();
                $table->timestamp('created_at')->useCurrent()->index();
                $table->timestamp('updated_at')->nullable()->index();
                $table->timestamp('deleted_at')->nullable()->index();
            });
        }

        if ($name == 'user_password') {
            $builder->create($name, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id')->index();
                $table->string('password')->index();
                $table->string('expired')->index();
                $table->integer('status')->default(0)->index();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }

        if ($name == 'user_profile') {
            $builder->create($name, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id')->index();
                $table->string('name')->index();
                $table->text('value')->nullable();
                $table->timestamp('last_modified')->nullable()->index();
                $table->unique(['user_id', 'name']);
            });
        }

        if ($name == 'user_role') {
            $builder->create($name, function (Blueprint $table) {
                $table->uuid('user_id')->index();
                $table->uuid('role_id')->index();
                $table->unique(['user_id', 'role_id']);
                $table->primary(['user_id', 'role_id']);
            });
        }

        if ($name == 'role') {
            $builder->create($name, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name')->unique();
                $table->string('type')->default('access')->index();
                $table->text('description')->nullable();
                $table->integer('status')->default(1)->index();
                $table->timestamp('created_at')->useCurrent()->index();
                $table->timestamp('updated_at')->nullable()->index();
                $table->timestamp('deleted_at')->nullable()->index();
            });
        }

        if ($name == 'role_child') {
            $builder->create($name, function (Blueprint $table) {
                $table->uuid('role_id')->index();
                $table->uuid('child_id')->index();
                $table->unique(['role_id', 'child_id']);
                $table->primary(['role_id', 'child_id']);
            });
        }

        if ($name == 'setting') {
            $builder->create($name, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name')->unique();
                $table->text('option')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
                $table->timestamp('updated_at')->nullable()->index();
            });
        }

        if ($name == 'tag') {
            $builder->create($name, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('parent_id')->default(0)->index();
                $table->uuid('author_id')->default(0)->index();
                $table->string('title')->index();
                $table->string('slug')->unique();
                $table->string('type')->default('tag')->index();
                $table->integer('status')->default(1)->index();
                $table->timestamp('created_at')->useCurrent()->index();
                $table->timestamp('updated_at')->nullable()->index();
                $table->timestamp('deleted_at')->nullable()->index();
            });
        }

        if ($name == 'post') {
            $builder->create($name, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('parent_id')->default(0)->index();
                $table->uuid('author_id')->default(0)->index();
                $table->string('slug')->unique();
                $table->string('title')->index();
                $table->string('cover')->nullable();
                $table->string('type')->index();
                $table->text('description')->nullable();
                $table->text('content')->nullable();
                $table->integer('comment_enabled')->default(1)->index();
                $table->integer('status')->default(0)->index();
                $table->timestamp('created_at')->useCurrent()->index();
                $table->timestamp('updated_at')->nullable()->index();
                $table->timestamp('deleted_at')->nullable()->index();
            });
        }

        if ($name == 'menu') {
            $builder->create($name, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('author_id')->default(0)->index();
                $table->string('label')->index();
                $table->string('icon')->nullable();
                $table->string('type')->index();
                $table->text('description')->nullable();
                $table->text('url')->nullable();
                $table->integer('status')->default(0)->index();
                $table->timestamp('created_at')->useCurrent()->index();
                $table->timestamp('updated_at')->nullable()->index();
                $table->timestamp('deleted_at')->nullable()->index();
            });
        }

        if ($name == 'post_tag') {
            $builder->create($name, function (Blueprint $table) {
                $table->uuid('post_id')->index();
                $table->uuid('tag_id')->index();
                $table->unique(['post_id', 'tag_id']);
                $table->primary(['post_id', 'tag_id']);
            });
        }

        if ($name == 'comment') {
            $builder->create($name, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('post_id')->index();
                $table->uuid('parent_id')->default(0)->index();
                $table->uuid('author_id')->default(0)->index();
                $table->string('name')->index();
                $table->string('email')->index();
                $table->string('website')->nullable()->index();
                $table->text('content')->nullable();
                $table->integer('status')->default(0)->index();
                $table->timestamp('created_at')->useCurrent()->index();
                $table->timestamp('updated_at')->nullable()->index();
                $table->timestamp('deleted_at')->nullable()->index();
            });
        }

        if ($name == 'notification') {
            $builder->create($name, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('author_id')->default(0)->index();
                $table->uuid('receiver_id')->default(0)->index();
                $table->string('subject')->index();
                $table->text('content')->nullable();
                $table->integer('status')->default(0)->index();
                $table->timestamp('created_at')->useCurrent()->index();
                $table->timestamp('updated_at')->nullable()->index();
            });
        }

        if ($name == 'media') {
            $builder->create($name, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('author_id')->default(0)->index();
                $table->string('name')->index();
                $table->string('filename')->unique();
                $table->string('type')->index();
                $table->string('ref')->index();
                $table->string('size')->index();
                $table->string('year')->index();
                $table->string('month')->index();
                $table->string('day')->index();
                $table->integer('status')->default(0)->index();
                $table->timestamp('created_at')->useCurrent()->index();
                $table->timestamp('updated_at')->nullable()->index();
                $table->timestamp('deleted_at')->nullable()->index();
            });
        }

        if ($name == 'audit') {
            $builder->create($name, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('author_id')->default(0)->index();
                $table->uuid('object_id')->default(0)->index();
                $table->string('entity')->index();
                $table->string('ip')->nullable()->index();
                $table->text('agent')->nullable();
                $table->string('type')->index();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }

        if ($name == 'audit_detail') {
            $builder->create($name, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('audit_id')->default(0)->index();
                $table->string('resource')->index();
                $table->text('previous')->nullable();
            });
        }
    }
}
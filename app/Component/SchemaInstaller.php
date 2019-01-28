<?php

namespace App\Component;

use Illuminate\Database\Schema\Blueprint;
use Viloveul\Kernel\Contracts\Database;

class SchemaInstaller
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
    public function install($name)
    {
        if ($name == 'user' && !$this->builder->hasTable('user')) {
            $this->builder->create('user', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->index();
                $table->string('nickname')->unique();
                $table->string('email')->unique();
                $table->string('password')->index();
                $table->integer('status')->default(0)->index();
                $table->integer('deleted')->default(0)->index();
                $table->timestamp('created_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
                $table->timestamp('updated_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
                $table->timestamp('deleted_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
            });
        }

        if ($name == 'user_role' && !$this->builder->hasTable('user_role')) {
            $this->builder->create('user_role', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('role_id')->index();
                $table->timestamp('created_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
                $table->unique(['user_id', 'role_id']);
            });
        }

        if ($name == 'role' && !$this->builder->hasTable('role')) {
            $this->builder->create('role', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->unique();
                $table->string('type')->default('access')->index();
                $table->text('description')->nullable();
                $table->integer('status')->default(1)->index();
                $table->integer('deleted')->default(0)->index();
                $table->timestamp('created_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
                $table->timestamp('updated_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
                $table->timestamp('deleted_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
            });
        }

        if ($name == 'role_child' && !$this->builder->hasTable('role_child')) {
            $this->builder->create('role_child', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('role_id')->index();
                $table->unsignedBigInteger('child_id')->index();
                $table->timestamp('created_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
                $table->unique(['role_id', 'child_id']);
            });
        }

        if ($name == 'setting' && !$this->builder->hasTable('setting')) {
            $this->builder->create('setting', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->unique();
                $table->text('option')->nullable();
                $table->timestamp('created_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
                $table->timestamp('updated_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
            });
        }

        if ($name == 'tag' && !$this->builder->hasTable('tag')) {
            $this->builder->create('tag', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('parent_id')->default(0)->index();
                $table->unsignedBigInteger('author_id')->default(0)->index();
                $table->string('title')->index();
                $table->string('slug')->unique();
                $table->string('type')->default('tag')->index();
                $table->integer('status')->default(1)->index();
                $table->integer('deleted')->default(0)->index();
                $table->timestamp('created_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
                $table->timestamp('updated_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
                $table->timestamp('deleted_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
            });
        }

        if ($name == 'post' && !$this->builder->hasTable('post')) {
            $this->builder->create('post', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('parent_id')->default(0)->index();
                $table->unsignedBigInteger('author_id')->default(0)->index();
                $table->string('slug')->unique();
                $table->string('title')->index();
                $table->string('type')->index();
                $table->text('description')->nullable();
                $table->text('content')->nullable();
                $table->integer('status')->default(0)->index();
                $table->integer('deleted')->default(0)->index();
                $table->timestamp('created_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
                $table->timestamp('updated_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
                $table->timestamp('deleted_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
            });
        }

        if ($name == 'post_tag' && !$this->builder->hasTable('post_tag')) {
            $this->builder->create('post_tag', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('post_id')->index();
                $table->unsignedBigInteger('tag_id')->index();
                $table->timestamp('created_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
                $table->unique(['post_id', 'tag_id']);
            });
        }

        if ($name == 'comment' && !$this->builder->hasTable('comment')) {
            $this->builder->create('comment', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('post_id')->index();
                $table->unsignedBigInteger('parent_id')->default(0)->index();
                $table->unsignedBigInteger('author_id')->default(0)->index();
                $table->string('name')->index();
                $table->string('nickname')->index();
                $table->string('email')->index();
                $table->string('website')->nullable()->index();
                $table->text('content')->nullable();
                $table->integer('status')->default(0)->index();
                $table->integer('deleted')->default(0)->index();
                $table->timestamp('created_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
                $table->timestamp('updated_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
                $table->timestamp('deleted_at')->default(date('Y-m-d H:i:s'))->nullable()->index();
            });
        }
    }
}

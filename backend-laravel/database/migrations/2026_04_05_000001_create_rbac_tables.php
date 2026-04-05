<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('RoleMenu');
        Schema::dropIfExists('UserRole');
        Schema::dropIfExists('Menu');
        Schema::dropIfExists('Role');
        Schema::dropIfExists('AdminUser');
        Schema::enableForeignKeyConstraints();

        Schema::create('AdminUser', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->increments('id');
            $table->string('username', 50)->unique();
            $table->string('passwordHash', 255);
            $table->string('nickname', 100);
            $table->enum('status', ['ACTIVE', 'DISABLED'])->default('ACTIVE');
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('Role', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->increments('id');
            $table->string('name', 50);
            $table->string('code', 50)->unique();
            $table->string('description', 255)->nullable();
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('Menu', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->increments('id');
            $table->unsignedInteger('parentId')->nullable();
            $table->string('name', 100);
            $table->string('path', 200)->nullable();
            $table->string('component', 200)->nullable();
            $table->string('icon', 100)->nullable();
            $table->enum('type', ['DIRECTORY', 'MENU', 'BUTTON']);
            $table->string('permissionKey', 120)->nullable()->unique();
            $table->integer('sort')->default(0);
            $table->boolean('visible')->default(true);
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();

            $table->index('parentId');
            $table->index('type');
            $table->foreign('parentId')->references('id')->on('Menu')->nullOnDelete();
        });

        Schema::create('UserRole', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->unsignedInteger('userId');
            $table->unsignedInteger('roleId');

            $table->primary(['userId', 'roleId']);
            $table->foreign('userId')->references('id')->on('AdminUser')->cascadeOnDelete();
            $table->foreign('roleId')->references('id')->on('Role')->cascadeOnDelete();
        });

        Schema::create('RoleMenu', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->unsignedInteger('roleId');
            $table->unsignedInteger('menuId');

            $table->primary(['roleId', 'menuId']);
            $table->foreign('roleId')->references('id')->on('Role')->cascadeOnDelete();
            $table->foreign('menuId')->references('id')->on('Menu')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('RoleMenu');
        Schema::dropIfExists('UserRole');
        Schema::dropIfExists('Menu');
        Schema::dropIfExists('Role');
        Schema::dropIfExists('AdminUser');
        Schema::enableForeignKeyConstraints();
    }
};

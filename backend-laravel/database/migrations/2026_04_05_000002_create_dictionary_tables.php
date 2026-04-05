<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('DictItem');
        Schema::dropIfExists('DictType');
        Schema::enableForeignKeyConstraints();

        Schema::create('DictType', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->increments('id');
            $table->string('name', 100);
            $table->string('code', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->boolean('status')->default(true);
            $table->integer('sort')->default(0);
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();

            $table->index('status');
            $table->index('sort');
        });

        Schema::create('DictItem', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->increments('id');
            $table->unsignedInteger('dictTypeId');
            $table->string('label', 100);
            $table->string('value', 100);
            $table->string('tagType', 30)->nullable();
            $table->string('tagClass', 120)->nullable();
            $table->boolean('status')->default(true);
            $table->integer('sort')->default(0);
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['dictTypeId', 'value']);
            $table->index('dictTypeId');
            $table->index('status');
            $table->index('sort');
            $table->foreign('dictTypeId')->references('id')->on('DictType')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('DictItem');
        Schema::dropIfExists('DictType');
        Schema::enableForeignKeyConstraints();
    }
};

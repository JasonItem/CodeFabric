<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('StoredFile');
        Schema::dropIfExists('FileFolder');
        Schema::enableForeignKeyConstraints();

        Schema::create('FileFolder', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->increments('id');
            $table->unsignedInteger('parentId')->nullable();
            $table->string('name', 120);
            $table->integer('sort')->default(0);
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();

            $table->index('parentId');
            $table->index('sort');
            $table->foreign('parentId')->references('id')->on('FileFolder')->nullOnDelete();
        });

        Schema::create('StoredFile', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->increments('id');
            $table->unsignedInteger('folderId')->nullable();
            $table->enum('source', ['ADMIN', 'USER'])->default('ADMIN');
            $table->enum('kind', ['IMAGE', 'VIDEO', 'FILE']);
            $table->string('name', 255);
            $table->string('originalName', 255);
            $table->string('ext', 20)->nullable();
            $table->string('mimeType', 120)->nullable();
            $table->unsignedBigInteger('size');
            $table->string('relativePath', 255);
            $table->string('url', 255);
            $table->unsignedInteger('createdById')->nullable();
            $table->string('createdByName', 100)->nullable();
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();

            $table->index('folderId');
            $table->index('source');
            $table->index('kind');
            $table->index('ext');
            $table->index('createdAt');
            $table->foreign('folderId')->references('id')->on('FileFolder')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('StoredFile');
        Schema::dropIfExists('FileFolder');
        Schema::enableForeignKeyConstraints();
    }
};

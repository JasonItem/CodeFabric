<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('OperationLog');

        Schema::create('OperationLog', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->increments('id');
            $table->unsignedInteger('userId')->nullable();
            $table->string('username', 100)->nullable();
            $table->string('module', 100)->nullable();
            $table->string('action', 100)->nullable();
            $table->string('method', 16);
            $table->string('path', 255);
            $table->integer('statusCode');
            $table->boolean('success');
            $table->string('message', 255)->nullable();
            $table->string('ip', 80)->nullable();
            $table->string('location', 120)->nullable();
            $table->string('userAgent', 255)->nullable();
            $table->integer('durationMs')->nullable();
            $table->text('requestBody')->nullable();
            $table->text('responseBody')->nullable();
            $table->timestamp('createdAt')->useCurrent();

            $table->index('userId');
            $table->index('username');
            $table->index('method');
            $table->index('path');
            $table->index('statusCode');
            $table->index('success');
            $table->index('createdAt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('OperationLog');
    }
};

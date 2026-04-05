<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('LoginLog');

        Schema::create('LoginLog', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->increments('id');
            $table->unsignedInteger('userId')->nullable();
            $table->string('username', 100)->nullable();
            $table->string('client', 40)->nullable();
            $table->string('device', 40)->nullable();
            $table->string('browser', 80)->nullable();
            $table->string('os', 80)->nullable();
            $table->string('ip', 80)->nullable();
            $table->string('location', 120)->nullable();
            $table->string('userAgent', 255)->nullable();
            $table->boolean('success');
            $table->string('message', 255)->nullable();
            $table->timestamp('createdAt')->useCurrent();

            $table->index('userId');
            $table->index('username');
            $table->index('success');
            $table->index('createdAt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('LoginLog');
    }
};

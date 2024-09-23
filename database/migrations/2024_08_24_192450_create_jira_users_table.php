<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jira_users', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('jira_user_id')->unique();
            $table->string('name');
            $table->string('email');
            $table->string('jira_user_type');
            $table->boolean('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jira_users');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jira_issues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('jira_issue_id')->unique();
            $table->string('summary');
            $table->string('development_category');
            $table->text('description')->nullable();
            $table->string('status')->nullable(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jira_issues');
    }
};

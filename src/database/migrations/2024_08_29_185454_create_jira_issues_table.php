<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jira_issues', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('jira_issue_id')->unique();
            $table->string('jira_issue_key')->unique();
            $table->unsignedInteger('jira_project_id');
            $table->string('summary');
            $table->string('development_category');
            $table->string('status')->nullable(false);
            $table->timestamps();

            $table->foreign('jira_project_id')
                ->references('jira_project_id')
                ->on('jira_projects')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jira_issues');
    }
};

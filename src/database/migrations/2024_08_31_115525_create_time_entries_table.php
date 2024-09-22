<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('time_entries', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('tempo_worklog_id');
            $table->unsignedInteger('jira_issue_id');
            $table->string('jira_user_id');
            $table->double('time_spent_in_minutes');
            $table->text('description');
            $table->dateTime('entry_created_at');
            $table->dateTime('entry_updated_at');
            $table->timestamps();

            $table->foreign('jira_issue_id')
                ->references('jira_issue_id')
                ->on('jira_issues')
                ->onDelete('cascade');
            $table->foreign('jira_user_id')
                ->references('jira_user_id')
                ->on('jira_users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};

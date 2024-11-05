<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jira_team_jira_user', static function (Blueprint $table) {
            $table->uuid('jira_team_id');
            $table->uuid('jira_user_id');
            $table->timestamps();

            $table->foreign('jira_user_id')->references('id')->on('jira_users')->onDelete('cascade');
            $table->foreign('jira_team_id')->references('id')->on('jira_teams')->onDelete('cascade');

            $table->primary(['jira_team_id', 'jira_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_team_jira_user');
    }
};

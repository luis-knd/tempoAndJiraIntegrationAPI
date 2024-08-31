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
            $table->date('date');
            $table->decimal('hours');
            $table->string('description', 255);
            $table->uuid('issue_id');
            $table->uuid('tempo_user_id');
            $table->timestamps();

            $table->foreign('issue_id')->references('id')->on('jira_issues')->onDelete('cascade');
            $table->foreign('tempo_user_id')->references('id')->on('tempo_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};

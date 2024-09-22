<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jira_projects', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('jira_project_id')->unique();
            $table->string('jira_project_key')->unique();
            $table->string('name')->nullable(false);
            $table->unsignedBigInteger('jira_project_category_id')->nullable();

            $table->foreign('jira_project_category_id')
                ->references('jira_category_id')
                ->on('jira_project_categories')
                ->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jira_projects');
    }
};

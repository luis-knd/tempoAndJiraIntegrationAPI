<?php

namespace App\Jobs\v1\Jira;

use App\Models\v1\Jira\JiraProjectCategory;
use App\Services\v1\Jira\JiraApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchJiraProjectCategoriesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function handle(JiraApiService $jiraApiService): void
    {
        $categories = $jiraApiService->fetchProjectCategories();
        foreach ($categories as $category) {
            JiraProjectCategory::updateOrCreate(
                ['jira_category_id' => $category['id']],
                [
                    'name' => $category['name'],
                    'description' => $category['description']
                ]
            );
        }
    }
}

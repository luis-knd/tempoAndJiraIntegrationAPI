<?php

namespace App\Policies;

use App\Models\v1\Basic\User;
use App\Models\v1\Jira\JiraProjectCategory;

class JiraProjectCategoryPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, JiraProjectCategory $project_category): bool
    {
        //TODO: Maybe in a future is necesary to check if the user is the assignee, now it is always true.
        /*$timeEntry = TimeEntry::where('jira_issue_id', $project_category->id)->first();
        return $user->email === $timeEntry->jiraUser->email;*/
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //TODO: Maybe in a future is necesary to check if the user is the assignee, now it is always true.
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, JiraProjectCategory $project_category): bool
    {
        //TODO: Maybe in a future is necesary to check if the user is the assignee, now it is always true.
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, JiraProjectCategory $project_category): bool
    {
        //TODO: Maybe in a future is necesary to check if the user is the assignee, now it is always true.
        return true;
    }
}

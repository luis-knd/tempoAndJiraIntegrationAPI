<?php

namespace App\Policies;

class JiraProjectCategoryPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(): bool
    {
        //TODO: Maybe in a future is necesary to check if the user is the assignee, now it is always true.
        /*$timeEntry = TimeEntry::where('jira_issue_id', $project_category->id)->first();
        return $user->email === $timeEntry->jiraUser->email;*/
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(): bool
    {
        //TODO: Maybe in a future is necesary to check if the user is the assignee, now it is always true.
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(): bool
    {
        //TODO: Maybe in a future is necesary to check if the user is the assignee, now it is always true.
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(): bool
    {
        //TODO: Maybe in a future is necesary to check if the user is the assignee, now it is always true.
        return true;
    }
}

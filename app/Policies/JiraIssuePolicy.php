<?php

namespace App\Policies;

use App\Models\v1\Basic\User;
use App\Models\v1\Jira\JiraIssue;
use App\Models\v1\Tempo\TimeEntry;
use Illuminate\Auth\Access\Response;

class JiraIssuePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, JiraIssue $jiraIssue): bool
    {
        //TODO: Maybe in a future is necesary to check if the user is the assignee, now it is always true.
        /*$timeEntry = TimeEntry::where('jira_issue_id', $jiraIssue->id)->first();
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
    public function update(User $user, JiraIssue $jiraIssue): bool
    {
        //TODO: Maybe in a future is necesary to check if the user is the assignee, now it is always true.
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, JiraIssue $jiraIssue): bool
    {
        //TODO: Maybe in a future is necesary to check if the user is the assignee, now it is always true.
        return true;
    }
}

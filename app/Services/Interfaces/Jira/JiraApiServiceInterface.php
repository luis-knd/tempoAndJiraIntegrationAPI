<?php

namespace App\Services\Interfaces\Jira;

/**
 * Interface JiraApiServiceInterface
 *
 * @package   App\Services\Interfaces
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
interface JiraApiServiceInterface
{
    public function fetchUsers(array $params): array;

    public function fetchIssueByJQL(array $params): array;

    public function fetchIssueWorklogs(string $issueKey): array;

    public function fetchProjects(array $params): array;

    public function fetchProjectCategories(): array;
}

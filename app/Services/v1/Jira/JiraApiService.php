<?php

namespace App\Services\v1\Jira;

use App\Services\Interfaces\Jira\JiraApiServiceInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use JsonException;

/**
 * Class JiraApiService
 *
 * @package   App\Services\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraApiService implements JiraApiServiceInterface
{
    private Client $client;

    public function __construct()
    {
        $jiraBaseUri = config('services.jira.base_uri');
        $jiraUserName = config('services.jira.username');
        $jiraApiToken = config('services.jira.api_token');
        $jiraAuthToken = base64_encode($jiraUserName . ':' . $jiraApiToken);

        $this->client = new Client([
            'base_uri' => $jiraBaseUri,
            'headers' => [
                'Authorization' => "Basic $jiraAuthToken",
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function fetchUsers(array $params): array
    {
        try {
            $response = $this->client->get('/rest/api/3/users/search', [
                'query' => $params
            ]);
            return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (GuzzleException $e) {
            Log::error("Error fetching Jira users: {$e->getMessage()}");
            return [];
        } catch (JsonException $e) {
            Log::error("Error building json response for Jira users: {$e->getMessage()}");
            return [];
        }
    }

    public function fetchIssueByJQL(array $params): array
    {
        try {
            $response = $this->client->get("/rest/api/3/search", [
                'query' => [
                    ...$params
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            return $data ?? [];
        } catch (GuzzleException $e) {
            Log::error("Error to make the request to Jira: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            Log::error("Error fetching Jira issues: " . $e->getMessage());
            return [];
        }
    }

    public function fetchIssueWorklogs(string $issueKey): array
    {
        try {
            $response = $this->client->get("/rest/api/3/issue/{$issueKey}/worklog");
            $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            return $data['worklogs'] ?? [];
        } catch (Exception $e) {
            Log::error(sprintf("Error fetching worklogs for issue {$issueKey}: %s", $e->getMessage()));
            return [];
        }
    }

    public function fetchProjects(array $params): array
    {
        try {
            $response = $this->client->get('/rest/api/3/project/search', [
                'query' => $params
            ]);
            return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (GuzzleException $e) {
            Log::error("Error fetching Jira projects: {$e->getMessage()}");
            return [];
        } catch (JsonException $e) {
            Log::error("Error building json response for fetching Jira projects: {$e->getMessage()}");
            return [];
        }
    }

    public function fetchProjectCategories(): array
    {
        try {
            $response = $this->client->get('/rest/api/3/projectCategory');
            return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (GuzzleException $e) {
            Log::error("Error fetching Jira categories: {$e->getMessage()}");
            return [];
        } catch (Exception $e) {
            Log::error("Unknown error fetching Jira categories: {$e->getMessage()}");
            return [];
        }
    }
}

<?php

namespace App\Jobs\v1\Jira;

use App\Exceptions\BadRequestException;
use App\Models\v1\Jira\JiraUser;
use App\Services\v1\Jira\JiraApiService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FetchJiraUsersJob
 *
 * @package   App\Jobs\v1\Jira
 * @copyright 09-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class FetchJiraUsersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 1200;

    protected ?string $accountType;

    public function __construct(string $accountType = null)
    {
        $this->accountType = $accountType;
    }

    public function handle(JiraApiService $jiraApiService): void
    {
        try {
            $startAt = 0;
            $maxResults = 50;
            $jiraUsersValues = [];

            do {
                $users = $jiraApiService->fetchUsers(
                    [
                        'startAt' => $startAt,
                        'maxResults' => $maxResults
                    ]
                );
                array_push($jiraUsersValues, ...$users);
                $startAt += $maxResults;
            } while (count($users) > 0);

            foreach ($jiraUsersValues as $jiraUser) {
                if (
                    $this->accountType !== null &&
                    (!isset($jiraUser['accountType']) || $jiraUser['accountType'] !== $this->accountType)
                ) {
                    continue;
                }

                // TODO for business logic, I need to check only the atlassian users account data
                if (
                    (isset($jiraUser['accountType']) && $jiraUser['accountType'] === 'atlassian' && $jiraUser['active'])
                    && !isset($jiraUser['accountId'], $jiraUser['displayName'])
                ) {
                    throw new BadRequestException('Invalid users data', Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                // @phpstan-ignore-next-line
                JiraUser::updateOrCreate(
                    ['jira_user_id' => $jiraUser['accountId']],
                    [
                        'name' => $jiraUser['displayName'] ?? 'sinNombre',
                        'email' => $jiraUser['emailAddress'] ?? 'sinCorreo@sinEmail.com',
                        'jira_user_type' => $jiraUser['accountType'],
                        'active' => $jiraUser['active']
                    ]
                );
            }
        } catch (BadRequestException $e) {
            Log::error("BadRequestException in FetchJiraProjectsJob: " . $e->getMessage());
        } catch (Exception $e) {
            Log::error("Exception in FetchJiraProjectsJob: " . $e->getMessage());
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('FetchJiraUsersJob was failed: ' . $exception->getMessage());
    }

    public function backoff(): array
    {
        return [60, 180, 600];
    }
}

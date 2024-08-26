<?php

namespace App\Services\v1\Jira;

use App\Exceptions\UnprocessableException;
use App\Models\v1\Jira\JiraUser;
use App\Repository\Interfaces\v1\Jira\JiraUserRepositoryInterface;
use App\Services\ProcessParamsTraits;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Class JiraUserService
 *
 * @package   App\Services\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraUserService
{
    use ProcessParamsTraits;

    public function __construct(readonly JiraUserRepositoryInterface $jiraUserRepository)
    {
    }

    /**
     * Retrieves a paginated list of JiraUser objects based on provided parameters.
     *
     * @param array $params The parameters to filter and sort the list of JiraUsers.
     * @return LengthAwarePaginator The paginated list of JiraUser objects.
     * @throws UnprocessableException
     */
    public function index(array $params): LengthAwarePaginator
    {
        $users = $this->process($params);
        return $this->jiraUserRepository->findByParams(
            $users['filter'],
            $users['with'],
            $users['order'],
            $users['page']
        );
    }

    /**
     * Creates a new JiraUser object with the provided parameters and saves it to the database.
     *
     * @param array $params An associative array containing the parameters for the new JiraUser object.
     *                      The keys should be the names of the JiraUser object's properties.
     * @return JiraUser The newly created JiraUser object.
     */
    public function create(array $params): JiraUser
    {
        $jiraUser = new JiraUser();
        $this->setParams($params, $jiraUser);
        $jiraUser->save();
        return $jiraUser;
    }

    /**
     * A description of setting parameters for a JiraUser.
     *
     * @param array    $params   The parameters to set for the JiraUser
     * @param JiraUser $jiraUser The JiraUser object to set the parameters on
     */
    private function setParams(array $params, JiraUser $jiraUser): void
    {
        $jiraUser->jira_user_id = $params['jira_user_id'];
        $jiraUser->name = $params['name'];
        $jiraUser->email = $params['email'];
    }

    /**
     * Loads additional data for a given JiraUser object based on the provided parameters.
     *
     * @param JiraUser $jiraUser The JiraUser object to load data for.
     * @param array    $params   An optional array of parameters to process and load additional data.
     * @return JiraUser The JiraUser object with additional data loaded.
     * @throws UnprocessableException
     */
    public function load(JiraUser $jiraUser, array $params = []): JiraUser
    {
        $users = $this->process($params);
        if ($users['with']) {
            $jiraUser->load($users['with']);
        }
        return $jiraUser;
    }

    public function update(JiraUser $jiraUser, array $params): JiraUser
    {
        $jiraUser->update($params);
        $jiraUser->save();
        return $jiraUser;
    }

    public function delete(JiraUser $jiraUser): ?bool
    {
        return $jiraUser->delete();
    }
}

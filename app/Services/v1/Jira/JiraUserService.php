<?php

namespace App\Services\v1\Jira;

use App\Exceptions\BadRequestException;
use App\Exceptions\UnprocessableException;
use App\Models\v1\Jira\JiraUser;
use App\Repository\Eloquent\v1\Jira\JiraUserRepository;
use App\Services\ProcessParamsTraits;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JsonException;

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

    public function __construct(readonly JiraUserRepository $jiraUserRepository)
    {
    }

    /**
     * Retrieves a paginated list of JiraUser objects based on provided parameters.
     *
     * @param array $params
     * @return LengthAwarePaginator
     * @throws BadRequestException
     * @throws UnprocessableException
     * @throws JsonException
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
    public function make(array $params): JiraUser
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
     * @return void
     */
    private function setParams(array $params, JiraUser $jiraUser): void
    {
        $jiraUser->jira_user_id = $params['jira_user_id'];
        $jiraUser->name = $params['name'];
        $jiraUser->email = $params['email'];
        $jiraUser->jira_user_type = $params['jira_user_type'];
        $jiraUser->active = $params['active'];
    }

    /**
     * Loads additional data for a given JiraUser object based on the provided parameters.
     *
     * @param JiraUser $jiraUser The JiraUser object to load data for.
     * @param array    $params   An optional array of parameters to process and load additional data.
     * @return JiraUser The JiraUser object with additional data loaded.
     * @throws UnprocessableException
     * @throws JsonException
     */
    public function load(JiraUser $jiraUser, array $params = []): JiraUser
    {
        $users = $this->process($params);
        if ($users['with']) {
            $jiraUser->load($users['with']);
        }
        return $jiraUser;
    }

    /**
     * Updates an existing JiraUser object with the provided parameters and saves it to the database.
     *
     * @param JiraUser $jiraUser The JiraUser object to update.
     * @param array    $params   An associative array containing the parameters for the JiraUser object.
     * @return JiraUser The updated JiraUser object.
     */
    public function update(JiraUser $jiraUser, array $params): JiraUser
    {
        $jiraUser->update($params);
        $jiraUser->save();
        return $jiraUser;
    }

    /**
     * Deletes a JiraUser object from the database.
     *
     * @param JiraUser $jiraUser The JiraUser object to delete.
     * @return bool|null
     */
    public function delete(JiraUser $jiraUser): ?bool
    {
        return $jiraUser->delete();
    }
}

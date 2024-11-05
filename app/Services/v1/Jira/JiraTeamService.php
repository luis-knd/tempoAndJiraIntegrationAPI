<?php

namespace App\Services\v1\Jira;

use App\Exceptions\UnprocessableException;
use App\Models\v1\Jira\JiraTeam;
use App\Repository\Interfaces\v1\Jira\JiraTeamRepositoryInterface;
use App\Services\ProcessParamsTraits;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JsonException;

/**
 * Class JiraTeamService
 *
 * @package   App\Services\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraTeamService
{
    use ProcessParamsTraits;

    public function __construct(readonly JiraTeamRepositoryInterface $jiraTeamRepository)
    {
    }

    /**
     *  index
     *
     * @param array $params
     * @return LengthAwarePaginator
     * @throws UnprocessableException
     * @throws JsonException
     */
    public function index(array $params): LengthAwarePaginator
    {
        $teams = $this->process($params);
        return $this->jiraTeamRepository->findByParams(
            $teams['filter'],
            $teams['with'],
            $teams['order'],
            $teams['page']
        );
    }

    public function make(array $params): JiraTeam
    {
        $team = new JiraTeam();
        $this->setParams($params, $team);
        $team->save();
        return $team;
    }

    private function setParams(array $params, JiraTeam $team): void
    {
        $team->jira_team_id = $params['jira_team_id'];
        $team->name = $params['name'];
    }

    /**
     *  load
     *
     * @param JiraTeam $team
     * @param array    $params
     * @return JiraTeam
     * @throws UnprocessableException
     * @throws JsonException
     */
    public function load(JiraTeam $team, array $params = []): JiraTeam
    {
        $teams = $this->process($params);
        if ($teams['with']) {
            $team->load($teams['with']);
        }
        return $team;
    }

    public function update(JiraTeam $team, array $params): JiraTeam
    {
        $team->update($params);
        $team->save();
        return $team;
    }

    public function delete(JiraTeam $team): ?bool
    {
        return $team->delete();
    }
}

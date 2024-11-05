<?php

namespace App\Repository\Eloquent\v1\Jira;

use App\Models\v1\Jira\JiraIssue;
use App\Repository\Eloquent\BaseRepository;
use App\Repository\Interfaces\v1\Jira\JiraIssueRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class JiraIssueRepository
 *
 * @package   App\Providers
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraIssueRepository extends BaseRepository implements JiraIssueRepositoryInterface
{
    public function __construct(JiraIssue $jiraIssue)
    {
        parent::__construct($jiraIssue);
    }

    protected function proxyFilters(
        array &$params,
        Model|Builder $query
    ): Builder {
        $jiraProjectsParams = $this->extractParams('jiraProjects', $params);
        if ($jiraProjectsParams) {
            $query->whereHas('jiraProjects', function ($query) use ($jiraProjectsParams) {
                foreach ($jiraProjectsParams as $param) {
                    $this->processParam(
                        $query,
                        $param[0],
                        $param[2],
                        $param[1]
                    );
                }
            });
        }
        return $query;
    }
}

<?php

namespace Database\Factories\v1\Jira;

use App\Models\v1\Jira\JiraIssue;
use App\Models\v1\Jira\JiraProject;
use App\Models\v1\Jira\JiraProjectCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class JiraProjectFactory
 *
 * @package   Database\Factories\v1\Jira
 * @copyright 09-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraIssueFactory extends Factory
{
    protected $model = JiraIssue::class;

    /**
     * @throws \Random\RandomException
     */
    public function definition(): array
    {
        $issueKey = $this->faker->unique()->countryISOAlpha3 . '-' . $this->faker->unique()->randomNumber(4);
        $developmentCategory = [
            'development_category',
            'Sin categoría asignada',
            'Migración tecnológica',
            'Base instalada - Reactivo',
            'Base instalada - Preventivo',
            'Soporte Comercial',
            'Desarrollos nuevos'
        ];
        $status = [
            'Awaiting communication',
            'Awaiting development',
            'Awaiting Test',
            'Cerrada',
            'In Test',
            'Nuevo',
            'Ready to do'
        ];
        return [
            'jira_issue_id' => $this->faker->unique()->randomNumber(6, true),
            'jira_issue_key' => $issueKey,
            //@phpstan-ignore-next-line
            'jira_project_id' => JiraProject::factory()->create()->jira_project_id,
            'summary' => $this->faker->sentence(8),
            'development_category' => $developmentCategory[random_int(0, 6)],
            'status' => $status[random_int(0, 6)]
        ];
    }
}

<?php

namespace App\Models\v1\Jira;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class JiraProjectCategory
 *
 * @package   App\Models\v1\Jira
 * @copyright 09-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $id
 * @property mixed $name
 * @property mixed $description
 * @property mixed $jira_category_id
 * @method static inRandomOrder()
 * @method static updateOrCreate(array $whereCondition, array $parameters)
 * @method static where(string $string, $jira_project_id)
 * @method static first()
 */
class JiraProjectCategory extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasUuids;

    protected $table = 'jira_project_categories';

    protected $fillable = [
        'jira_category_id',
        'name',
        'description',
    ];

    protected array $dates = ['deleted_at'];

    public function projects(): HasMany
    {
        return $this->hasMany(JiraProject::class);
    }
}

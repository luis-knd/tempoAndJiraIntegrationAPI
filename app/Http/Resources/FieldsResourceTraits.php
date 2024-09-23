<?php

namespace App\Http\Resources;

use Exception;
use Illuminate\Http\Request;

/**
 * Trait FieldsResourceTraits
 *
 * @package   App\Http\Resources
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
trait FieldsResourceTraits
{
    private array $fields = [];
    private int $level = 1;
    private int $maxLevel = 1;
    private bool $possibleTransitions = false;

    /**
     * Initializes the class with the given request.
     *
     * @param Request|null $request The request object. Defaults to null.
     * @return void
     */
    protected function init(Request $request = null): void
    {
        if (!$request || $this->level > 1 || $request->isMethod('get')) {
            return;
        }

        $relations = $request->get('relations');
        if ($relations) {
            $this->findMaxLevel($relations);
            $this->possibleTransitions = str_contains($relations, 'possible_transitions');
        }

        $this->fields = explode(',', $request->get('fields', '*'));
    }

    /**
     * Determines the maximum level of related resources based on the given relations.
     *
     * @extra Example:
     *  GET api/resource <-- max level = 1
     *  GET api/resource?relations=something <-- max level = 2
     *  GET api/resource?relations=something.another <-- max level = 3
     * @param string $relations The relations string.
     * @return void
     */
    private function findMaxLevel(string $relations): void
    {
        $relatedResources = explode(',', $relations);

        if (!empty($relatedResources)) {
            $this->maxLevel = 2;
        }

        foreach ($relatedResources as $resource) {
            $levels = substr_count($resource, '.') + 2;

            if ($levels > $this->maxLevel) {
                $this->maxLevel = $levels;
            }
        }
    }

    protected function include($attribute): bool|Exception
    {
        if (!$this->fields) {
            return new Exception(__('You must to call the FieldsResourceTraits.init method'));
        }
        if ($this->fields[0] === "*") {
            return true;
        }
        return in_array($attribute, $this->fields);
    }

    public function setLevel($level): static
    {
        $this->level = $level;
        return $this;
    }

    public function depthLevel(): bool
    {
        return ($this->maxLevel > $this->level);
    }

    public function setMaxLevel($maxLevel): static
    {
        $this->maxLevel = $maxLevel;
        return $this;
    }

    public function setPossibleTransitions(bool $possible_transitions): static
    {
        $this->possibleTransitions = $possible_transitions;
        return $this;
    }
}

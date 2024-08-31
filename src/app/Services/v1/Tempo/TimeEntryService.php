<?php

namespace App\Services\v1\Tempo;

use App\Exceptions\UnprocessableException;
use App\Models\v1\Tempo\TimeEntry;
use App\Repository\Interfaces\v1\Tempo\TimeEntryRepositoryInterface;
use App\Services\ProcessParamsTraits;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Class TimeEntryService
 *
 * @package   App\Services\v1\Tempo
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class TimeEntryService
{
    use ProcessParamsTraits;

    public function __construct(readonly TimeEntryRepositoryInterface $timeEntryRepository)
    {
    }

    /**
     *  index
     *
     * @param array $params
     * @return LengthAwarePaginator
     * @throws UnprocessableException
     */
    public function index(array $params): LengthAwarePaginator
    {
        $entries = $this->process($params);
        return $this->timeEntryRepository->findByParams(
            $entries['filter'],
            $entries['with'],
            $entries['order'],
            $entries['page']
        );
    }

    /**
     *  create
     *
     * @param array $params
     * @return TimeEntry
     */
    public function make(array $params): TimeEntry
    {
        $timeEntry = new TimeEntry();
        $this->setParams($params, $timeEntry);
        $timeEntry->save();
        return $timeEntry;
    }

    private function setParams(array $params, TimeEntry $timeEntry): void
    {
        $timeEntry->date = $params['date'];
        $timeEntry->hours = $params['hours'];
        $timeEntry->description = $params['description'];
        $timeEntry->issue_id = $params['issue_id'];
        $timeEntry->tempo_user_id = $params['tempo_user_id'];
    }

    /**
     *  load
     *
     * @param TimeEntry $timeEntry
     * @param array     $params
     * @return TimeEntry
     * @throws UnprocessableException
     */
    public function load(TimeEntry $timeEntry, array $params = []): TimeEntry
    {
        $entries = $this->process($params);
        if ($entries['with']) {
            $timeEntry->load($entries['with']);
        }
        return $timeEntry;
    }

    /**
     *  update
     *
     * @param TimeEntry $timeEntry
     * @param array     $params
     * @return TimeEntry
     */
    public function update(TimeEntry $timeEntry, array $params): TimeEntry
    {
        $timeEntry->update($params);
        $timeEntry->save();
        return $timeEntry;
    }

    /**
     *  delete
     *
     * @param TimeEntry $timeEntry
     * @return bool|null
     */
    public function delete(TimeEntry $timeEntry): ?bool
    {
        return $timeEntry->delete();
    }
}

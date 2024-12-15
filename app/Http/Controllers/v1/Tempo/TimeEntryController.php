<?php

namespace App\Http\Controllers\v1\Tempo;

use App\Exceptions\UnprocessableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Auth\v1\Tempo\TimeEntryRequest;
use App\Http\Resources\v1\Tempo\TimeEntryCollection;
use App\Http\Resources\v1\Tempo\TimeEntryResource;
use App\Models\v1\Tempo\TimeEntry;
use App\Services\v1\Tempo\TimeEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use JsonException;

/**
 * Class TimeEntryController
 *
 * @package   App\Http\Controllers\v1\Tempo
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class TimeEntryController extends Controller
{
    private TimeEntryService $timeEntryService;

    public function __construct(TimeEntryService $timeEntryService)
    {
        $this->timeEntryService = $timeEntryService;
    }

    /**
     *  index
     *
     * @param TimeEntryRequest $request
     * @return JsonResponse
     * @throws JsonException
     * @throws UnprocessableException
     */
    public function index(TimeEntryRequest $request): JsonResponse
    {
            $params = $request->validated();
            $paginator = $this->timeEntryService->index($params);
            $timeEntries = new TimeEntryCollection($paginator);
            return jsonResponse(data: $timeEntries);
    }

    public function store(TimeEntryRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $timeEntry = $this->timeEntryService->make($validatedData);
        return TimeEntryResource::toJsonResponse($timeEntry);
    }

    /**
     *  show
     *
     * @param TimeEntryRequest $request
     * @param TimeEntry        $timeEntry
     * @return JsonResponse
     * @throws JsonException
     * @throws UnprocessableException
     */
    public function show(TimeEntryRequest $request, TimeEntry $timeEntry): JsonResponse
    {
            $params = $request->validated();
            Gate::authorize('view', $timeEntry);
            $entry = $this->timeEntryService->load($timeEntry, $params);
            return TimeEntryResource::toJsonResponse($entry);
    }

    public function update(TimeEntryRequest $request, TimeEntry $timeEntry): JsonResponse
    {
        Gate::authorize('update', $timeEntry);
        $params = $request->validated();
        $updatedEntry = $this->timeEntryService->update($timeEntry, $params);
        return TimeEntryResource::toJsonResponse($updatedEntry);
    }

    /**
     *  destroy
     *
     * @param TimeEntryRequest $request
     * @param TimeEntry        $timeEntry
     * @return JsonResponse
     */
    public function destroy(TimeEntryRequest $request, TimeEntry $timeEntry): JsonResponse
    {
        $request->validated();
        Gate::authorize('delete', $timeEntry);
        $this->timeEntryService->delete($timeEntry);
        return jsonResponse(message: 'TimeEntry deleted successfully.');
    }
}

<?php

namespace App\Services\v1\Tempo;

use App\Services\Interfaces\Tempo\TempoApiServiceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use JsonException;

/**
 * Class TempoApiService
 *
 * @package   App\Services\v1\Tempo
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class TempoApiService implements TempoApiServiceInterface
{
    private Client $client;

    public function __construct()
    {
        $tempoBaseUri = config('services.tempo.base_uri');
        $tempoApiToken = config('services.tempo.api_token');
        $this->client = new Client([
            'base_uri' => $tempoBaseUri,
            'headers' => [
                'Authorization' => "Bearer $tempoApiToken",
                'Accept' => 'application/json',
            ],
        ]);
    }
    public function fetchWorklogs(int $issueId): array
    {
        try {
            $response = $this->client->get("/4/worklogs/issue/$issueId");
            return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (GuzzleException $e) {
            Log::error("Error fetching Tempo time entries: {$e->getMessage()}");
            return [];
        } catch (JsonException $e) {
            Log::error("Error building json response for Tempo time entries: {$e->getMessage()}");
            return [];
        }
    }
}

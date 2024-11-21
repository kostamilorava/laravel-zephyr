<?php

namespace RedberryProducts\Zephyr\Services;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class ApiService
{
    private function baseHttp(): PendingRequest
    {
        //Base http that will include auth headers
        return Http::withToken(config('zephyr.api_key'));
    }

    public function get($requestUri): ?array
    {
        $response = $this->baseHttp()->get(rtrim(config('zephyr.base_url'), '/') . $requestUri);
        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    public function getTestCases(string $projectKey, ?int $maxResults): ?array
    {
        return $this->get('/testcases?' . http_build_query(['projectKey' => $projectKey, 'maxResults' => $maxResults ?? config('zephyr.max_test_results')]));
    }

    public function getFolders(string $projectKey, ?int $maxResults): ?array
    {
        return $this->get(
            '/folders?' . http_build_query(
                [
                    'projectKey' => $projectKey,
                    'maxResults' => $maxResults ?? config('zephyr.max_test_results'),
                ]
            )
        );
    }

    /*
    * Sends custom built JSON results to zephyr
    */
    public function sendCustomTestResultsToZephyr($data): PromiseInterface|Response
    {
        $json = json_encode($data);
        $endpoint = 'https://api.zephyrscale.smartbear.com/v2/automations/executions/custom';

        // Create zip archive in memory
        $zipPath = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFromString('file.json', $json);
        $zip->close();

        $zipContents = file_get_contents($zipPath);
        unlink($zipPath); // delete the temp file

        $endpointWithParams = $endpoint . '?' . http_build_query([
            'projectKey'          => $this->projectKey,
            'autoCreateTestCases' => json_encode(false),
        ]);

        return $this->baseHttp()
            ->acceptJson()
            ->attach('file', $zipContents, 'file.zip')
            ->post($endpointWithParams);
    }

    /*
     * Sends Junit results to zephyr
     */
    public function sendJunitTestResultsToZephyr($filePath, $projectKey): PromiseInterface|Response
    {
        $endpoint = 'https://api.zephyrscale.smartbear.com/v2/automations/executions/junit';

        // Create zip archive in memory
        $zipPath = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);
        $fileName = basename($filePath);
        $zip->addFile($filePath, $fileName);
        $zip->close();
        $zipContents = file_get_contents($zipPath);
        unlink($zipPath); // delete the temp file

        $endpointWithParams = $endpoint . '?' . http_build_query([
            'projectKey'          => $projectKey,
            'autoCreateTestCases' => json_encode(false),
        ]);

        return $this->baseHttp()
            ->acceptJson()
            ->attach('file', $zipContents, 'file.zip')
            ->post($endpointWithParams);
    }
}

<?php

namespace RedberryProducts\Zephyr\Commands;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Storage;
use RedberryProducts\Zephyr\Traits\ZephyrTrait;
use ZipArchive;

class SendResults extends Command
{
    use ZephyrTrait;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'results {--project-key=KEY}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Send test results to zephyr server';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Init needed variables
        $this->init();

        $testResultsArray = [
            'version'    => 1,
            'executions' => [],
        ];

        $xml = Storage::disk('local')->get('junit.xml');
        $xmlObject = simplexml_load_string($xml);

        $testcases = $this->extractTestcases($xmlObject);
        foreach ($testcases as $testcase) {
            preg_match_all($this->testIdPattern, $testcase['name'], $matches);

            foreach ($matches[1] as $match) {
                $testIds = explode(',', $match);

                foreach ($testIds as $testId) {
                    $testId = trim($testId);
                    $testResultsArray['executions'][] = [
                        'result'   => $testcase['failure'] ? 'Failed' : 'Passed',
                        'testCase' => [
                            'comment' => $testcase['failure'],
                            'key'     => $testId,
                        ],
                    ];
                }
            }

        }
        $result = $this->sendCustomTestResultsToZephyr($testResultsArray);
        dd($result->json());
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
        $zip = new ZipArchive();
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
        $zip = new ZipArchive();
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

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}

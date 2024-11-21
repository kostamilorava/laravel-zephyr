<?php

namespace RedberryProducts\Zephyr\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;
use RedberryProducts\Zephyr\Services\ApiService;
use RedberryProducts\Zephyr\Services\TestFilesManagerService;
use RedberryProducts\Zephyr\Traits\TestPatternMatcherTrait;

class SendResults extends Command
{
    use TestPatternMatcherTrait;

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
    public function handle(): int
    {

        $testResultsArray = [
            'version'    => 1,
            'executions' => [],
        ];

        $xml = Storage::disk('local')->get('junit.xml');
        $xmlObject = simplexml_load_string($xml);

        TestFilesManagerService::setProjectKey($this->argument('projectKey'))
            ->setCommandInstance($this);

        $testcases = TestFilesManagerService::extractTestcases($xmlObject);
        foreach ($testcases as $testcase) {
            preg_match_all($this->getTestIdPattern($this->argument('projectKey')), $testcase['name'], $matches);

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
        $result = ApiService::sendCustomTestResultsToZephyr($testResultsArray);
        //        dd($result->json());

        return self::SUCCESS;
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}

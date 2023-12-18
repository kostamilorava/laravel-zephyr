<?php

namespace RedberryProducts\Zephyr\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RedberryProducts\Zephyr\Services\TestFileCreator;
use RedberryProducts\Zephyr\Services\TestsStructureArrayBuilder;
use RedberryProducts\Zephyr\Traits\ZephyrTrait;

class GenerateCommand extends Command
{
    use ZephyrTrait;

    protected $signature = 'zephyr:generate';

    protected $description = 'Generate test files for Laravel';

    protected array $testCases;

    protected array $folders;

    private function getTestCasesDataFromZephyr(): void
    {
        $testCases = [];
        $folders = [];
        $testModeEnabled = config('zephyr.test_mode');

        if ($testModeEnabled) {
            if (Storage::disk('local')->fileExists('testCases.json') && Storage::disk('local')->fileExists('folders.json')) {
                $testCases = json_decode(Storage::disk('local')->get('testCases.json'), true);
                $folders = json_decode(Storage::disk('local')->get('folders.json'), true);
            }
        }

        if (empty($testCases) || empty($folders)) {
            $testCases = $this->syncZephyrTestsWithLaravel($this->projectKey, config('zephyr.max_test_results'));
            $folders = $this->getFolders($this->projectKey, config('zephyr.max_test_results'));
            if ($testModeEnabled) {
                $this->saveJsonDataAsFiles($testCases, $folders);
            }
        }

        $this->testCases = $testCases;
        $this->folders = $folders;
    }

    public function handle(): int
    {

        $this->initializeVariables();
        $this->getTestCasesDataFromZephyr();
        $this->createTestsDirectoryIfNotExists();

        $testsStructureArray = (new TestsStructureArrayBuilder($this->folders['values'], $this->testCases['values']))->build();

        $existingTestIds = $this->scanDirectoryForTestIds(Storage::disk('local')->path('tests/Feature'));

        (new TestFileCreator($existingTestIds, $this))->createFiles($testsStructureArray, 'tests/Feature');

        $this->cleanupEmptyFolders(Storage::disk('local')->path('tests/Feature'));

        return self::SUCCESS;

    }

    private function saveJsonDataAsFiles($testCases, $folders): void
    {
        Storage::disk('local')->put('testCases.json', json_encode($testCases));
        Storage::disk('local')->put('folders.json', json_encode($folders));
    }

    private function createTestsDirectoryIfNotExists(): void
    {
        if (! Storage::disk('local')->directoryExists('tests/Feature')) {
            Storage::disk('local')->makeDirectory('tests/Feature');
        }
    }

    public function syncZephyrTestsWithLaravel(string $projectKey, int $maxResults = 10): ?array
    {
        return $this->getDataFromZephyr('/testcases?' . http_build_query(['projectKey' => $projectKey, 'maxResults' => $maxResults]));
    }

    public function getFolders(string $projectKey, int $maxResults = 10, ?string $folderType = null): ?array
    {
        return $this->getDataFromZephyr(
            '/folders?' . http_build_query(
                [
                    'projectKey' => $projectKey,
                    'maxResults' => $maxResults,
                ]
            )
        );
    }

    public function cleanupEmptyFolders($path): void
    {
        if (! is_dir($path)) {
            $this->error('Invalid dir');

            return;
        }

        // Scan the directory
        $files = scandir($path);

        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $filePath = $path . '/' . $file;

            if (is_dir($filePath)) {
                $this->cleanupEmptyFolders($filePath);
            }
        }

        // Re-scan after recursion
        $files = scandir($path);

        // Filter out system pointers
        $files = array_filter($files, function ($file) {
            return ! in_array($file, ['.', '..']);
        });

        // If empty, delete the directory
        if (count($files) == 0) {
            rmdir($path);
        }
    }
}

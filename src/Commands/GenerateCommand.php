<?php

namespace RedberryProducts\Zephyr\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            $testCases = $this->syncZephyrTestsWithLaravel('AR', 2000);
            $folders = $this->getFolders('AR', 2000);
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

    //TODO: delete this
    //    /*
    //     *  Build an array from ZephyrGenerate response representing folder & file structure
    //     */
    //    private function buildTestsStructureArray(): array
    //    {
    //        $folders = $this->folders;
    //        $testCases = $this->testCases;
    //        // Initialize an empty array for the final structure
    //        $structure = [];
    //
    //        // Initialize an associative array where the keys are the ids
    //        $items = [];
    //
    //        // First loop: build an associative array
    //        foreach ($folders as $item) {
    //            $item['children'] = []; // Here we are adding 'children' key
    //            $item['test_cases'] = []; // Here we are adding 'test_cases' key
    //            $items[$item['id']] = $item;
    //        }
    //
    //        // Second loop: populate 'children' arrays and the root
    //        foreach ($items as $item) {
    //            if ($item['parentId'] !== null) {
    //                // If this item has a parent, append it to the parent's 'children' array
    //                $items[$item['parentId']]['children'][] = &$items[$item['id']];
    //            } else {
    //                // If this item doesn't have a parent, it's a root node; append it to the structure array
    //                $structure['children'][] = &$items[$item['id']];
    //            }
    //        }
    //
    //        // Third loop: put test cases in final array
    //        // Put test cases inside folders
    //        foreach ($testCases as $testCase) {
    //            if (is_null($testCase['folder'])) {
    //                $structure['test_cases'][] = $testCase;
    //            } else {
    //                $items[$testCase['folder']['id']]['test_cases'][] = $testCase;
    //            }
    //        }
    //
    //        return $structure;
    //    }

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

    //TODO: delete this

    //    /*
    //     *  Creates folder structure of tests
    //     */
    //
    //    private function createTestFiles($structure, string $path): void
    //    {
    //        if (isset($structure['test_cases'])) {
    //            foreach ($structure['test_cases'] as $testCase) {
    //                $testCaseFileName = isset($structure['name']) ? (Str::slug(strtolower($structure['name'])) . '.php') : 'TestCases.php';
    //                $testFilePath = rtrim($path, '/') . '/' . $testCaseFileName;
    //
    //                $testCaseExists = false;
    //                foreach ($this->existingTestIds as $key => $testArray) {
    //                    if ($testArray['test_id'] === $testCase['key']) {
    //                        $this->warn("Test case {$testCase['key']} already exists in {$testArray['file']}, skipping");
    //                        $testCaseExists = true;
    //                    }
    //                }
    //
    //                $explodedProjectKey = explode('-', $testCase['key']);
    //                $testCaseProjectKey = reset($explodedProjectKey);
    //
    //                // If testcase exists in locally available files or if project key does not match
    //                if ($testCaseExists
    //                    || $testCaseProjectKey !== $this->projectKey) {
    //
    //                    if (!$testCaseExists) {
    //                        $this->info('Project key is invalid. Skipping');
    //                    }
    //
    //                    continue;
    //                }
    //
    //                // Create test file if it does not exist
    //                if (!Storage::disk('local')->fileExists($testFilePath)) {
    //                    Storage::disk('local')->put($testFilePath, "<?php\n");
    //                }
    //                // Put test cases in test files
    //                Storage::disk('local')->append($testFilePath, "\ntest('[{$testCase['key']}] {$testCase['name']}', function () {\n\n});\n");
    //            }
    //        }
    //
    //        if (isset($structure['children'])) {
    //            foreach ($structure['children'] as $node) {
    //                $newPath = $path;
    //                if (isset($node['name'])) {
    //                    $newPath = $path . '/' . Str::slug($node['name']);
    //                }
    //
    //                if (!Storage::disk('local')->directoryExists($newPath)) {
    //                    Storage::disk('local')->makeDirectory($newPath);
    //                }
    //
    //                // Recursively create directories/files for any children
    //                if (!empty($node['children']) || !empty($node['test_cases'])) {
    //                    $this->createTestFiles($node, $newPath);
    //                }
    //            }
    //        }
    //    }

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

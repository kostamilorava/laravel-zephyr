<?php

namespace RedberryProducts\Zephyr\Services;

use Illuminate\Support\Facades\Storage;
use Str;

class TestFileCreator
{
    private array $existingTestIds;
    private string $projectKey;
    private mixed $commandInstance;

    public function __construct(array $existingTestIds, mixed $commandInstance)
    {
        $this->existingTestIds = $existingTestIds;
        $this->projectKey = config('zephyr.project_key');
        $this->commandInstance = $commandInstance;
    }

    public function createFiles(array $structure, string $path): void
    {
        $this->createTestCases($structure, $path);
        $this->createChildren($structure, $path);
    }

    private function createTestCases(array $structure, string $path): void
    {
        if (isset($structure['test_cases'])) {
            foreach ($structure['test_cases'] as $testCase) {
                if ($this->testCaseExists($testCase)) {
                    continue;
                }
                $testFilePath = $this->getTestFilePath($structure, $path, $testCase);
                $this->writeTestCaseToFile($testFilePath, $testCase);
            }
        }
    }

    private function createChildren(array $structure, string $path): void
    {
        if (isset($structure['children'])) {
            foreach ($structure['children'] as $node) {
                $newPath = $this->getNewPath($path, $node);
                Storage::disk('local')->makeDirectory($newPath);
                $this->createFiles($node, $newPath);
            }
        }
    }

    private function getTestFilePath(array $structure, string $path, array $testCase): string
    {
        $testCaseFileName = isset($structure['name']) ? (Str::slug(strtolower($structure['name'])) . '.php') : 'TestCases.php';
        return rtrim($path, '/') . '/' . $testCaseFileName;
    }

    private function testCaseExists(array $testCase): bool
    {
        foreach ($this->existingTestIds as $testArray) {
            if ($testArray['test_id'] === $testCase['key']) {
                $this->commandInstance->warn("Test case {$testCase['key']} already exists in {$testArray['file']}, skipping");
                return true;
            }
        }
        return false;
    }

    private function writeTestCaseToFile(string $testFilePath, array $testCase): void
    {
        if (!Storage::disk('local')->fileExists($testFilePath)) {
            Storage::disk('local')->put($testFilePath, "<?php\n");
        }

        Storage::disk('local')->append($testFilePath, "\ntest('[{$testCase['key']}] {$testCase['name']}', function () {\n\n});\n");
    }

    private function getNewPath(string $path, array $node): string
    {
        return isset($node['name']) ? $path . '/' . Str::slug($node['name']) : $path;
    }

}
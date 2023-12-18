<?php

namespace RedberryProducts\Zephyr\Traits;

use DOMDocument;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SimpleXMLElement;

trait ZephyrTrait
{
    private string $projectKey;

    private string $testIdPattern;

    public function initializeVariables(): void
    {
        $this->projectKey = config('zephyr.project_key');

        $this->testIdPattern = "/\[\s*(" . preg_quote($this->projectKey, '/') . "-T\d+(\s*,\s*" . preg_quote($this->projectKey, '/') . "-T\d+)*)\s*\]/";
    }

    private function mergeCypressJunitFilesIntoOne($sourceDirectory, $targetFile): void
    {
        $xml = new SimpleXMLElement('<testsuites/>');

        // Get all XML files in the source directory
        $files = glob($sourceDirectory . '/*.xml');
        foreach ($files as $file) {
            $content = simplexml_load_file($file);

            foreach ($content->testsuite as $testsuite) {
                $newTestsuite = $xml->addChild('testsuite');

                if ($testsuite->testsuite) {
                    foreach ($testsuite->testsuite as $nestedTestsuite) {
                        $newNestedTestsuite = $newTestsuite->addChild('testsuite');

                        if ($nestedTestsuite->testcase) {
                            foreach ($nestedTestsuite->testcase as $testcase) {
                                $newNestedTestsuite->addChild('testcase');
                            }
                        }
                    }
                }
            }
        }

        // Save the merged content to a new file
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        $dom->save($targetFile);
    }

    private function baseHttp(): PendingRequest
    {
        //Base http that will include headers
        return Http::withToken(config('zephyr.api_key'));
    }

    public function getDataFromZephyr($requestUri): ?array
    {
        $response = $this->baseHttp()->get(rtrim(config('zephyr.base_url'), '/') . $requestUri);
        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    /*
    *  Scans existing tests, gets names of Zephyr and puts in array
    */
    public function scanDirectoryForTestIds($dir): array
    {
        $result = [];
        $seenTests = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }
            $filePath = $file->getPathname();
            $content = file_get_contents($filePath);
            preg_match_all($this->testIdPattern, $content, $matches);

            foreach ($matches[1] as $match) {
                $testIds = explode(',', $match);

                foreach ($testIds as $testId) {
                    $testId = trim($testId);
                    // check for duplicate test id's and skip
                    if (isset($seenTests[$testId])) {
                        //echo "Warning: Duplicate test ID '$testId' found in file $filePath\n";
                        continue;
                    }
                    $seenTests[$testId] = true;
                    $result[] = ['test_id' => $testId, 'file' => $filePath];
                }
            }
        }

        return $result;
    }

    /*
     * Extracts test cases from junit xml object
     */
    private function extractTestcases(SimpleXMLElement $element): array
    {
        $testcases = [];

        foreach ($element->children() as $child) {
            if ($child->getName() === 'testcase') {
                $testcase = [
                    'name' => (string)$child['name'],
                    'class' => (string)$child['class'],
                    'classname' => (string)$child['classname'],
                    'file' => (string)$child['file'],
                    'assertions' => (int)$child['assertions'],
                    'time' => (float)$child['time'],
                    'failure' => (string)$child->failure,
                ];

                $testcases[] = $testcase;
            } else {
                $testcases = array_merge($testcases, $this->extractTestcases($child));
            }
        }

        return $testcases;
    }
}

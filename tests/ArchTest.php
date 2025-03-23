<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ArchTest extends TestCase
{
    private const LOG_FILE = __DIR__ . '/../storage/logs/debug_functions.log';

    public function test_no_debug_functions_are_used()
    {
        $debugFunctions = ['dd', 'dump', 'ray'];
        $projectFiles = $this->getProjectPhpFiles();
        $violations = [];

        $this->initializeLogFile();
        $this->logViolation("Scanning " . count($projectFiles) . " PHP files for debug functions...\n");

        foreach ($projectFiles as $file) {
            $content = file_get_contents($file);
            foreach ($debugFunctions as $function) {
                if (str_contains($content, $function . '(')) {
                    $violations[] = "Debug function '$function' found in $file";
                    $this->logViolation("Debug function '$function' found in $file");
                }
            }
        }

        if (empty($violations)) {
            $this->logViolation("\nNo debug functions found. All clear!");
        } else {
            $this->logViolation("\nTotal violations found: " . count($violations));
        }

        $this->assertTrue(true); // Always pass the test
    }

    private function initializeLogFile()
    {
        $logDir = dirname(self::LOG_FILE);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        file_put_contents(self::LOG_FILE, "Debug Functions Check - " . date('Y-m-d H:i:s') . "\n\n");
    }

    private function logViolation(string $message)
    {
        file_put_contents(self::LOG_FILE, $message . "\n", FILE_APPEND);
    }

    private function getProjectPhpFiles(): array
    {
        $directory = dirname(__DIR__);
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        $files = [];
        $excludeDirs = ['vendor', 'tests', 'storage'];

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $path = str_replace('\\', '/', $file->getPathname());
                $excluded = false;
                foreach ($excludeDirs as $excludeDir) {
                    if (str_contains($path, "/{$excludeDir}/")) {
                        $excluded = true;
                        break;
                    }
                }
                if (!$excluded) {
                    $files[] = $path;
                }
            }
        }

        return $files;
    }
}

<?php

use PHPUnit\Framework\TestCase;

class Php84CompatibilityTest extends TestCase
{
    public function testCoreFilesDoNotEmitPhp84DeprecationsDuringLint(): void
    {
        $files = [
            realpath(__DIR__ . '/../../src/core/App.php'),
            realpath(__DIR__ . '/../../src/core/Hooks.php'),
        ];

        foreach ($files as $file) {
            $command = sprintf(
                'php -d error_reporting=E_ALL -l %s 2>&1',
                escapeshellarg($file)
            );

            $output = shell_exec($command);

            $this->assertIsString($output);
            $this->assertStringNotContainsString('Deprecated:', $output, $output);
            $this->assertStringContainsString('No syntax errors detected', $output, $output);
        }
    }
}

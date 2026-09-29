<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class BootErrorTest extends TestCase {
    public function testBootErrorIsLogged(): void {
        $process = proc_open(
            [
                PHP_BINARY,
                '-d', 'variables_order=EGPCS',
                // Empty error_log means logging to stderr.
                '-d', 'error_log=',
                '-r', 'require $argv[1];',
                __DIR__ . '/../src/common.php',
            ],
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            null,
            ['SELFOSS_CONFIG_DIR' => '/nonexistent-selfoss-config-dir'] + getenv()
        );
        $this->assertIsResource($process);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('SELFOSS_CONFIG_DIR', $stdout);
        $this->assertStringContainsString('selfoss boot error: The value of SELFOSS_CONFIG_DIR', $stderr);
    }
}

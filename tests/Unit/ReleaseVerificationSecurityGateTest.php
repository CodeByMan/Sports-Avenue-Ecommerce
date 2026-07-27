<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ReleaseVerificationSecurityGateTest extends TestCase
{
    public function test_ci_runs_platform_and_composer_security_checks_for_supported_php_versions(): void
    {
        $workflow = $this->projectFile('.github/workflows/ci.yml');

        $this->assertStringContainsString("php: ['8.2', '8.3', '8.4']", $workflow);
        $this->assertStringContainsString('tools: composer:v2', $workflow);
        $this->assertCommandsAreOrdered($workflow);
        $this->assertStringContainsString('vendor/bin/phpunit --colors=never --do-not-cache-result', $workflow);
    }

    public function test_release_scripts_run_the_same_composer_security_gate(): void
    {
        foreach (['scripts/verify-release.sh', 'scripts/verify-release.ps1'] as $script) {
            $contents = $this->projectFile($script);
            $this->assertCommandsAreOrdered($contents, $script);
            $this->assertStringContainsString('vendor/bin/phpunit --colors=never --do-not-cache-result', $contents);
        }
    }

    private function assertCommandsAreOrdered(string $contents, string $context = 'CI workflow'): void
    {
        $install = strpos($contents, 'composer install --no-interaction --prefer-dist');
        $platform = strpos($contents, 'composer check-platform-reqs');
        $audit = strpos($contents, 'composer audit --locked --abandoned=report');
        $artisan = strpos($contents, 'php artisan about');

        $this->assertNotFalse($install, "$context must install Composer dependencies.");
        $this->assertNotFalse($platform, "$context must check PHP platform requirements.");
        $this->assertNotFalse($audit, "$context must audit the lock file.");
        $this->assertNotFalse($artisan, "$context must verify the Laravel runtime.");
        $this->assertTrue(
            $install < $platform && $platform < $audit && $audit < $artisan,
            "$context must install, check the platform, audit dependencies, then inspect Laravel."
        );
    }

    private function projectFile(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$path);

        $this->assertNotFalse($contents, "Unable to read repository file: $path");

        return $contents;
    }
}

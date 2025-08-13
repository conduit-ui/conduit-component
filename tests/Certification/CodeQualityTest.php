<?php

describe('Code Quality Certification', function () {
    it('passes Pest test suite', function () {
        $result = shell_exec('cd '.base_path().' && ./vendor/bin/pest --no-coverage 2>&1');

        expect($result)->not()->toBeNull();
        expect($result)->not()->toContain('FAILED');
        expect($result)->not()->toContain('Fatal error');
        expect($result)->not()->toContain('Parse error');

        // Should contain success indicators
        expect($result)->toMatch('/(PASS|OK|Tests:.*Assertions:)/');
    });

    it('passes Laravel Pint code style checks', function () {
        $result = shell_exec('cd '.base_path().' && ./vendor/bin/pint --test 2>&1');

        expect($result)->not()->toBeNull();
        expect($result)->not()->toContain('FAILED');

        // Should indicate no fixes needed or all files passed
        $passIndicators = [
            'No changes required',
            'All done',
            '✓',  // Check mark
            'PASS',
        ];

        $foundPass = false;
        foreach ($passIndicators as $indicator) {
            if (str_contains($result, $indicator)) {
                $foundPass = true;
                break;
            }
        }

        expect($foundPass)->toBeTrue('Pint should report no style issues');
    });

    it('validates PHPStan is available and configured', function () {
        // Check if PHPStan is available
        $phpstanAvailable = file_exists(base_path('vendor/bin/phpstan'));

        if (! $phpstanAvailable) {
            // If PHPStan is not installed, check if it's available globally
            $globalPhpstan = ! empty(shell_exec('which phpstan 2>/dev/null'));

            if (! $globalPhpstan) {
                $this->markTestSkipped('PHPStan not available for static analysis');

                return;
            }

            $command = 'phpstan';
        } else {
            $command = './vendor/bin/phpstan';
        }

        // Try to run PHPStan analysis
        $result = shell_exec('cd '.base_path()." && $command analyse --no-progress app/ 2>&1");

        expect($result)->not()->toBeNull();
        expect($result)->not()->toContain('Fatal error');
        expect($result)->not()->toContain('Parse error');

        // Should not have critical errors
        expect($result)->not()->toContain('[ERROR]');
    });

    it('validates composer.json structure and dependencies', function () {
        $composerPath = base_path('composer.json');
        expect(file_exists($composerPath))->toBeTrue('composer.json should exist');

        $composer = json_decode(file_get_contents($composerPath), true);
        expect($composer)->not()->toBeNull('composer.json should be valid JSON');

        // Check required fields
        expect($composer)->toHaveKey('name');
        expect($composer)->toHaveKey('require');
        expect($composer)->toHaveKey('autoload');

        // Check for required dependencies
        expect($composer['require'])->toHaveKey('php');
        expect($composer['require'])->toHaveKey('laravel-zero/framework');

        // Check for development dependencies
        if (isset($composer['require-dev'])) {
            $devDeps = $composer['require-dev'];

            // Should have at least one testing framework
            $testingFrameworks = ['pestphp/pest', 'phpunit/phpunit'];
            $hasTestingFramework = false;

            foreach ($testingFrameworks as $framework) {
                if (isset($devDeps[$framework])) {
                    $hasTestingFramework = true;
                    break;
                }
            }

            expect($hasTestingFramework)->toBeTrue('Should have a testing framework in dev dependencies');
        }
    });

    it('validates PSR-4 autoloading structure', function () {
        $composerPath = base_path('composer.json');
        $composer = json_decode(file_get_contents($composerPath), true);

        expect($composer)->toHaveKey('autoload');
        expect($composer['autoload'])->toHaveKey('psr-4');

        $psr4 = $composer['autoload']['psr-4'];

        // Should have App namespace
        expect($psr4)->toHaveKey('App\\');
        expect($psr4['App\\'])->toBe('app/');

        // Validate that the directory structure matches PSR-4
        $appPath = base_path('app');
        expect(is_dir($appPath))->toBeTrue('app/ directory should exist');
    });

    it('ensures proper file permissions', function () {
        $componentBinary = base_path('component');

        if (file_exists($componentBinary)) {
            expect(is_executable($componentBinary))->toBeTrue('Component binary should be executable');
        }

        // Check that PHP files are readable
        $phpFiles = glob(base_path('app/**/*.php'), GLOB_BRACE);

        foreach ($phpFiles as $file) {
            expect(is_readable($file))->toBeTrue("PHP file should be readable: $file");
        }
    });

    it('validates test coverage configuration', function () {
        $phpunitConfig = base_path('phpunit.xml.dist');

        if (file_exists($phpunitConfig)) {
            $xml = simplexml_load_file($phpunitConfig);
            expect($xml)->not()->toBeFalse('phpunit.xml.dist should be valid XML');

            // Should have testsuites defined
            expect(isset($xml->testsuites))->toBeTrue('PHPUnit config should define testsuites');
        }
    });

    it('checks for security vulnerabilities in dependencies', function () {
        // Run composer audit if available
        $auditResult = shell_exec('cd '.base_path().' && composer audit --format=json 2>/dev/null');

        if ($auditResult) {
            $audit = json_decode($auditResult, true);

            if ($audit && isset($audit['advisories']) && is_array($audit['advisories'])) {
                $vulnerabilityCount = count($audit['advisories']);
                expect($vulnerabilityCount)->toBe(0, 'No security vulnerabilities should be found in dependencies');
            }
        } else {
            // If composer audit is not available, just check that composer.lock exists
            expect(file_exists(base_path('composer.lock')))->toBeTrue('composer.lock should exist for dependency integrity');
        }
    });

    it('validates no syntax errors in PHP files', function () {
        $phpFiles = [];

        // Collect PHP files from app directory
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path('app'))
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }

        // Check syntax for each PHP file
        foreach ($phpFiles as $file) {
            $output = shell_exec("php -l \"$file\" 2>&1");
            expect($output)->toContain('No syntax errors detected', "File should have no syntax errors: $file");
        }
    });
});

<?php

it('can run test command', function () {
    $this->artisan('test')->assertExitCode(0);
});

it('shows summary when using list command', function () {
    $this->artisan('list')
        ->expectsOutput('Component  v0.0.1')
        ->expectsOutput('USAGE:  <command> [options] [arguments]')
        ->expectsOutput('test         Run the component tests')
        ->expectsOutput('make:command Create a new command')
        ->expectsOutput('make:test    Create a new test class')
        ->assertExitCode(0);
});

it('shows available make commands in summary', function () {
    $result = $this->artisan('list');

    $result->assertExitCode(0);

    $output = $result->getDisplay();
    expect($output)->toContain('make:command');
    expect($output)->toContain('make:test');
});

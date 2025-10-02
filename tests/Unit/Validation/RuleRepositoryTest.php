<?php

use App\Support\Validation\RuleDefinition;
use App\Support\Validation\RuleRepository;
use App\Support\Validation\RuleSet;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config(['validation.rules_path' => resource_path('validation/rules')]);
});

it('loads all available rule sets', function () {
    $repository = RuleRepository::fromConfig();

    $sets = $repository->all();

    expect($sets)
        ->toBeArray()
        ->and(count($sets))->toBeGreaterThanOrEqual(2)
        ->and($sets[0])->toBeInstanceOf(RuleSet::class);
});

it('finds a specific rule set by identifier', function () {
    $repository = RuleRepository::fromConfig();

    $uae = $repository->find('uae-wps-v1');

    expect($uae->id)->toBe('uae-wps-v1')
        ->and($uae->rules())->toHaveCount(4)
        ->and($uae->rules()->first())->toBeInstanceOf(RuleDefinition::class);
});

it('throws an exception when a rule set cannot be found', function () {
    $repository = RuleRepository::fromConfig();

    $repository->find('missing-rules');
})->throws(\InvalidArgumentException::class);

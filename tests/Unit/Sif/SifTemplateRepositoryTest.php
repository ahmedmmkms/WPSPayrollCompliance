<?php

use App\Support\Sif\SifTemplate;
use App\Support\Sif\SifTemplateRepository;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config(['sif.templates_path' => resource_path('sif/templates')]);
});

it('loads available SIF templates from disk', function () {
    $repository = SifTemplateRepository::fromConfig();

    $templates = $repository->all();

    expect($templates)
        ->toBeArray()
        ->and($templates[0])->toBeInstanceOf(SifTemplate::class);
});

it('finds a specific template by key', function () {
    $repository = SifTemplateRepository::fromConfig();

    $template = $repository->find('uae-wps-standard-v1');

    expect($template->key)->toBe('uae-wps-standard-v1')
        ->and($template->headerFields())->toContain('HDR');
});

it('throws when a template cannot be found', function () {
    $repository = SifTemplateRepository::fromConfig();

    $repository->find('missing-template');
})->throws(\InvalidArgumentException::class);

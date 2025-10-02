<?php

use App\Support\Sif\SifGenerator;
use App\Support\Sif\SifTemplateRepository;
use Carbon\Carbon;

beforeEach(function () {
    config(['sif.templates_path' => resource_path('sif/templates')]);
});

it('generates a SIF payload for the UAE WPS template', function () {
    $repository = SifTemplateRepository::fromConfig();
    $template = $repository->find('uae-wps-standard-v1');

    $generator = new SifGenerator;

    $batch = [
        'reference' => 'BATCH-001',
        'scheduled_for' => Carbon::parse('2025-10-01'),
    ];

    $employees = [
        [
            'external_id' => 'EMP001',
            'salary' => 7500.5,
            'currency' => 'aed',
        ],
    ];

    $generated = $generator->generate($template, $batch, $employees);

    expect($generated->filename)->toBe('BATCH-001-uae-wps-1.0.0.sif')
        ->and($generated->contents)->toBe("HDR|BATCH-001|2025-10-01|1\nDTL|EMP001|7500.50|AED\n");
});

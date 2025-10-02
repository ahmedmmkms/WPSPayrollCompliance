<?php

use App\Support\Mudad\MudadAdapter;
use App\Support\Mudad\MudadClient;
use App\Support\Sif\GeneratedSif;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

describe('MudadAdapter', function () {
    beforeEach(function (): void {
        Carbon::setTestNow(Carbon::parse('2025-10-03T00:00:00+00:00'));
    });

    afterEach(function (): void {
        Carbon::setTestNow();
    });

    it('submits a payroll file to the Mudad API', function (): void {
        Http::fake([
            'https://sandbox.api.mudad.gov.sa/v1/payroll/submissions' => Http::response([
                'id' => 'submission-xyz',
                'status' => 'queued',
            ], 202),
        ]);

        $client = new MudadClient([
            'base_uri' => 'https://sandbox.api.mudad.gov.sa',
            'token' => 'test-token',
        ]);

        $adapter = new MudadAdapter($client);

        $sif = new GeneratedSif('MUDAD-001.sif', "HDR|MUDAD-001|2025-10-03|1\nROW|EMP001|1234567890|FULL_TIME|7200.00|SAR|SA".str_repeat('0', 22).'\n');

        $result = $adapter->submit($sif, [
            'batch' => [
                'reference' => 'MUDAD-001',
                'metadata' => [
                    'mudad' => [
                        'portal_reference' => 'PRT-001',
                    ],
                ],
            ],
            'template' => [
                'key' => 'ksa-mudad-sandbox-v1',
                'version' => '1.0.0',
            ],
            'employees' => [
                [
                    'external_id' => 'EMP001',
                    'salary' => 7200,
                    'currency' => 'SAR',
                    'metadata' => [
                        'mudad' => [
                            'national_id' => '1234567890',
                            'contract_type' => 'full_time',
                            'bank_iban' => 'SA'.str_repeat('1', 22),
                        ],
                    ],
                ],
            ],
        ]);

        expect($result->id)->toBe('submission-xyz')
            ->and($result->status)->toBe('queued');

        Http::assertSent(function ($request) use ($sif) {
            $payload = $request->data() ?? [];
            $headers = $request->headers();

            $authorization = $headers['Authorization'][0] ?? $headers['authorization'][0] ?? null;

            return $authorization === 'Bearer test-token'
                && $request->url() === 'https://sandbox.api.mudad.gov.sa/v1/payroll/submissions'
                && ($payload['file']['filename'] ?? null) === $sif->filename
                && ($payload['file']['contents'] ?? null) === base64_encode($sif->contents);
        });

        Http::assertSentCount(1);
    });
});

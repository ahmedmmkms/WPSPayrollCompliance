<?php

declare(strict_types=1);

it('uses the storage framework views directory for compiled blade templates by default', function (): void {
    expect(config('view.compiled'))->toBe(storage_path('framework/views'));
});

<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SifExportGenerated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $integrations
     */
    public function __construct(
        public readonly string $tenantId,
        public readonly string $templateKey,
        public readonly ?string $batchId,
        public readonly string $disk,
        public readonly string $path,
        public readonly string $filename,
        public readonly string $queuedAt,
        public readonly array $integrations = [],
    ) {}
}

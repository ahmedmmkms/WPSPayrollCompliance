<?php

namespace App\Support\Sif;

class GeneratedSif
{
    public function __construct(
        public readonly string $filename,
        public readonly string $contents,
        public readonly string $mimeType = 'text/plain',
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'filename' => $this->filename,
            'contents' => $this->contents,
            'mime_type' => $this->mimeType,
        ];
    }
}

<?php

namespace App\Support\Sif;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;

class SifTemplateRepository
{
    public function __construct(
        private readonly string $directory,
    ) {}

    public static function fromConfig(): self
    {
        return new self(config('sif.templates_path'));
    }

    /**
     * @return array<int, SifTemplate>
     */
    public function all(): array
    {
        if (! File::exists($this->directory)) {
            return [];
        }

        return collect(File::files($this->directory))
            ->filter(fn ($file) => $file->getExtension() === 'json')
            ->map(fn ($file) => $this->loadFromPath($file->getPathname()))
            ->values()
            ->all();
    }

    public function find(string $key): SifTemplate
    {
        $path = $this->resolvePath($key);

        if (! File::exists($path)) {
            throw new InvalidArgumentException("SIF template [{$key}] was not found.");
        }

        return $this->loadFromPath($path);
    }

    private function resolvePath(string $key): string
    {
        $base = rtrim($this->directory, DIRECTORY_SEPARATOR);

        return $base.DIRECTORY_SEPARATOR.$key.'.json';
    }

    private function loadFromPath(string $path): SifTemplate
    {
        try {
            $contents = File::get($path);
            $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException("SIF template [{$path}] could not be decoded: {$exception->getMessage()}");
        }

        if (! is_array($data)) {
            throw new InvalidArgumentException("SIF template [{$path}] is not valid JSON.");
        }

        return SifTemplate::fromArray($data);
    }
}

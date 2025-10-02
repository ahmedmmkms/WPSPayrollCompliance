<?php

namespace App\Support\Validation;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;

class RuleRepository
{
    public function __construct(
        private readonly string $directory,
    ) {}

    public static function fromConfig(): self
    {
        return new self(config('validation.rules_path'));
    }

    /**
     * @return array<int, RuleSet>
     */
    public function all(): array
    {
        if (! File::exists($this->directory)) {
            return [];
        }

        return collect(File::files($this->directory))
            ->filter(fn ($file) => $file->getExtension() === 'json')
            ->map(fn ($file) => $this->loadFromPath($file->getPathname()))
            ->filter()
            ->values()
            ->all();
    }

    public function find(string $identifier): RuleSet
    {
        $path = $this->resolvePath($identifier);

        if (! File::exists($path)) {
            throw new InvalidArgumentException("Rule set [{$identifier}] was not found.");
        }

        return $this->loadFromPath($path);
    }

    private function resolvePath(string $identifier): string
    {
        $base = rtrim($this->directory, DIRECTORY_SEPARATOR);

        return $base.DIRECTORY_SEPARATOR.$identifier.'.json';
    }

    private function loadFromPath(string $path): RuleSet
    {
        try {
            $contents = File::get($path);
            $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException("Rule set [{$path}] could not be decoded: {$exception->getMessage()}");
        }

        if (! is_array($data)) {
            throw new InvalidArgumentException("Rule set [{$path}] is not valid JSON.");
        }

        return RuleSet::fromArray($data);
    }
}

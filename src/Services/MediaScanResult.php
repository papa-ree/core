<?php

namespace Bale\Core\Services;

/**
 * MediaScanResult
 *
 * Immutable outcome of scanning a media binary (before it is imported into
 * Bale) for backdoors, embedded/hidden code and hidden links. Produced by
 * {@see MediaSecurityScanner}.
 */
final class MediaScanResult
{
    public const SEVERITY_NONE = 'none';

    public const SEVERITY_LOW = 'low';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_HIGH = 'high';

    /**
     * Ordered severity ladder used for comparisons.
     *
     * @var array<string, int>
     */
    protected const RANK = [
        self::SEVERITY_NONE => 0,
        self::SEVERITY_LOW => 1,
        self::SEVERITY_MEDIUM => 2,
        self::SEVERITY_HIGH => 3,
    ];

    /**
     * @param  bool  $allowed  Whether the file may be imported (no HIGH findings).
     * @param  string  $severity  Highest severity found (none|low|medium|high).
     * @param  array<int, array{code: string, message: string, severity: string, sample?: string}>  $findings
     * @param  array<string, mixed>  $meta  Non-security metadata (detected type, size, dimensions…).
     */
    public function __construct(
        public readonly bool $allowed,
        public readonly string $severity = self::SEVERITY_NONE,
        public readonly array $findings = [],
        public readonly array $meta = [],
    ) {}

    public function isClean(): bool
    {
        return $this->findings === [];
    }

    public function isBlocked(): bool
    {
        return ! $this->allowed;
    }

    public function detectedType(): ?string
    {
        return $this->meta['detected_type'] ?? null;
    }

    public function hasCode(string $code): bool
    {
        foreach ($this->findings as $finding) {
            if (($finding['code'] ?? null) === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * Codes of every finding, in detection order.
     *
     * @return array<int, string>
     */
    public function codes(): array
    {
        return array_values(array_filter(array_map(
            static fn (array $finding): ?string => $finding['code'] ?? null,
            $this->findings
        )));
    }

    public function rank(string $severity): int
    {
        return self::RANK[$severity] ?? 0;
    }

    /**
     * Human readable one-line summary (safe to store in logs/CLI output).
     */
    public function summary(): string
    {
        if ($this->isClean()) {
            return 'clean';
        }

        $parts = [];

        foreach ($this->findings as $finding) {
            $parts[] = sprintf('%s [%s]', $finding['code'] ?? 'unknown', $finding['severity'] ?? 'low');
        }

        return implode(', ', array_slice($parts, 0, 6))
            .(count($parts) > 6 ? sprintf(' (+%d more)', count($parts) - 6) : '');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'severity' => $this->severity,
            'findings' => $this->findings,
            'meta' => $this->meta,
        ];
    }
}

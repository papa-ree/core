<?php

namespace Bale\Core\Services;

/**
 * WpSqlParser
 *
 * Extracts publishable WordPress posts (`post_type = post`,
 * `post_status = publish`) from a raw mysqldump-style `.sql` file, without
 * needing a live WordPress database.
 *
 * Previously lived inside the Livewire SQL migrator; extracted so both the UI
 * and the `core:migrate-wp-sql` command share one implementation.
 */
class WpSqlParser
{
    /** Total `wp_posts` rows seen in the dump (before filtering). */
    public int $totalFoundCount = 0;

    /** Rows skipped because they are not a published `post`. */
    public int $filteredCount = 0;

    /** Text values whose double-encoded bytes were repaired on the last parse. */
    public int $encodingRepairedCount = 0;

    /**
     * Undo the double-encoding that some dump clients introduce on export.
     *
     * A WordPress install that once served a latin1 connection can be exported by
     * a client that reads those bytes as CP1252 and re-encodes them as UTF-8,
     * turning `🎉` into `ðŸŽ‰` and `”` into `â€`. The transform is lossless and
     * reversible, so it is repaired automatically (see {@see repairMojibake()}),
     * but it can be disabled per call for dumps that are already correct.
     */
    public bool $repairEncoding = true;

    /**
     * Cheap sanity check for an uploaded/spooled file: does this even look like
     * a MySQL dump?
     *
     * This must stay **content-based**. The UI used to validate uploads with
     * `mimes:txt`, and Laravel's `mimes` rule compares against the extension
     * *guessed from the file contents* (finfo) — a mysqldump whose `post_content`
     * is HTML-heavy is routinely sniffed as `text/html`, so a perfectly valid
     * dump was rejected with "must be a file of type: txt" no matter what the
     * file was named.
     */
    public function looksLikeDump(string $sql): bool
    {
        return stripos($sql, 'INSERT INTO') !== false
            || stripos($sql, 'CREATE TABLE') !== false;
    }

    /**
     * @param  string  $tablePrefix  Table prefix, or '' to auto-detect any `*_posts`.
     * @param  bool|null  $repairEncoding  Override {@see $repairEncoding} for this call.
     * @return array<int, array<string, string|null>>
     */
    public function parse(string $sql, string $tablePrefix = '', ?bool $repairEncoding = null): array
    {
        $posts = [];

        $repairEncoding ??= $this->repairEncoding;

        $prefixPattern = $tablePrefix === ''
            ? '[a-zA-Z0-9_]+'
            : preg_quote($tablePrefix, '/');

        $pattern = '/INSERT\s+INTO\s+`?'.$prefixPattern.'posts`?\s*\(([^)]+)\)\s*VALUES\s*(.*?)(?=;\s*(\r?\n|$)|;\s*(?:\/\*|INSERT|UNLOCK|COMMIT|BEGIN|SET|DROP|CREATE))/is';

        if (! preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) {
            return [];
        }

        foreach ($matches as $match) {
            $columns = array_map(
                fn ($col) => trim(str_replace('`', '', $col)),
                explode(',', $match[1])
            );

            $rows = $this->extractValueRows($match[2]);
            $this->totalFoundCount += count($rows);

            foreach ($rows as $rawRow) {
                $values = $this->parseValueRow($rawRow, count($columns));
                $row = array_combine($columns, $values);

                if (! $row) {
                    continue;
                }

                $postType = $row['post_type'] ?? '';
                $postStatus = $row['post_status'] ?? '';

                if ($postType !== 'post' || $postStatus !== 'publish') {
                    $this->filteredCount++;

                    continue;
                }

                if (trim((string) ($row['post_title'] ?? '')) === '') {
                    continue;
                }

                if ($repairEncoding) {
                    $row = $this->repairRowEncoding($row);
                }

                $posts[] = $row;
            }
        }

        return $posts;
    }

    /**
     * Repair every text value of a row in place.
     *
     * @param  array<string, string|null>  $row
     * @return array<string, string|null>
     */
    protected function repairRowEncoding(array $row): array
    {
        foreach ($row as $column => $value) {
            if (! is_string($value) || $value === '' || ! preg_match('/[^\x00-\x7F]/', $value)) {
                continue;
            }

            $repaired = $this->repairMojibake($value);

            if ($repaired !== null) {
                $row[$column] = $repaired;
                $this->encodingRepairedCount++;
            }
        }

        return $row;
    }

    /**
     * Reverse a UTF-8 → CP1252 → UTF-8 double-encoding.
     *
     * Returns null when the value does not prove to be double-encoded, so a
     * correctly exported dump is left untouched:
     *
     *  - `ðŸŽ‰` (four CP1252 chars) → the raw bytes `F0 9F 8E 89` → valid UTF-8 → repaired.
     *  - `é` in a correct dump → the single byte `E9` → *not* valid UTF-8 → kept.
     *  - `🎉` in a correct dump → unmappable → `?` is introduced → kept.
     */
    protected function repairMojibake(string $value): ?string
    {
        $candidate = @mb_convert_encoding($value, 'Windows-1252', 'UTF-8');

        if ($candidate === false || $candidate === $value) {
            return null;
        }

        // A single-byte fragment (0x80–0xFF) is not valid UTF-8, so a correctly
        // exported multi-byte character can never pass this check.
        if (! mb_check_encoding($candidate, 'UTF-8')) {
            return null;
        }

        // mb_convert_encoding substitutes '?' (and sometimes U+FFFD) for
        // characters CP1252 cannot represent — never accept a lossy conversion.
        if (str_contains($candidate, "\u{FFFD}")) {
            return null;
        }

        if (substr_count($candidate, '?') > substr_count($value, '?')) {
            return null;
        }

        return $candidate;
    }

    /**
     * Split the VALUES portion of an INSERT statement into individual rows.
     *
     * @return string[]
     */
    protected function extractValueRows(string $valuesPart): array
    {
        $rows = [];
        $depth = 0;
        $inStr = false;
        $escape = false;
        $start = null;
        $len = strlen($valuesPart);

        for ($i = 0; $i < $len; $i++) {
            $char = $valuesPart[$i];

            if ($escape) {
                $escape = false;

                continue;
            }

            if ($char === '\\' && $inStr) {
                $escape = true;

                continue;
            }

            if ($char === "'" && ! $escape) {
                $inStr = ! $inStr;
            }

            if ($inStr) {
                continue;
            }

            if ($char === '(') {
                if ($depth === 0) {
                    $start = $i + 1;
                }
                $depth++;
            } elseif ($char === ')') {
                $depth--;
                if ($depth === 0 && $start !== null) {
                    $rows[] = substr($valuesPart, $start, $i - $start);
                    $start = null;
                }
            }
        }

        return $rows;
    }

    /**
     * Parse an individual row string into an array of scalar values.
     *
     * @return array<int, string|null>
     */
    protected function parseValueRow(string $row, int $expectedCount): array
    {
        $values = [];
        $current = '';
        $inStr = false;
        $escape = false;
        $len = strlen($row);

        for ($i = 0; $i < $len; $i++) {
            $char = $row[$i];

            if ($escape) {
                $current .= match ($char) {
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    "'" => "'",
                    '\\' => '\\',
                    default => $char,
                };
                $escape = false;

                continue;
            }

            if ($char === '\\' && $inStr) {
                $escape = true;

                continue;
            }

            if ($char === "'") {
                if ($inStr) {
                    if (isset($row[$i + 1]) && $row[$i + 1] === "'") {
                        $current .= "'";
                        $i++;
                    } else {
                        $inStr = false;
                    }
                } else {
                    $inStr = true;
                }

                continue;
            }

            if (! $inStr && $char === ',') {
                $values[] = $current;
                $current = '';

                continue;
            }

            $current .= $char;
        }

        $values[] = $current;

        $values = array_map(function ($v) {
            $trimmed = trim($v);
            if (strtoupper($trimmed) === 'NULL') {
                return null;
            }
            if (str_starts_with($trimmed, "'") && str_ends_with($trimmed, "'")) {
                return substr($trimmed, 1, -1);
            }

            return $trimmed;
        }, $values);

        while (count($values) < $expectedCount) {
            $values[] = '';
        }

        return array_slice($values, 0, $expectedCount);
    }
}

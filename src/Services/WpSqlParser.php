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

    /**
     * @return array<int, array<string, string|null>>
     */
    public function parse(string $sql, string $tablePrefix = ''): array
    {
        $posts = [];

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

                $posts[] = $row;
            }
        }

        return $posts;
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

<?php

namespace Bale\Core\Services;

/**
 * PostCleanerService
 *
 * Strips WordPress HTML content, removes images and anchor tags,
 * preserves paragraph structure, and returns an EditorJS-compatible
 * JSON block array suitable for the Bale CMS content field.
 */
class PostCleanerService
{
    /**
     * Process raw WordPress post_content into clean EditorJS JSON.
     *
     * Pipeline:
     *  1. Remove all <img> tags completely.
     *  2. Replace block-closing tags with newlines to preserve paragraph breaks.
     *  3. Strip all remaining HTML tags (including <a> anchor tags).
     *  4. Decode HTML entities and trim whitespace.
     *  5. Split on newlines and build EditorJS paragraph blocks.
     *
     * @param  string  $rawContent  The raw `post_content` value from the SQL dump.
     * @return array                EditorJS-compatible JSON structure (associative array).
     */
    public function clean(string $rawContent): array
    {
        // Step 1: Remove all <img> tags entirely.
        $content = preg_replace('/<img[^>]*>/i', '', $rawContent);

        // Step 2: Replace block-closing/break tags with a newline character
        //         before stripping tags — prevents text from different blocks clumping together.
        $content = preg_replace('#</?(p|div|br|h[1-6]|li|ul|ol|blockquote|section|article|header|footer)[^>]*>#i', "\n", $content);

        // Step 3: Strip ALL remaining HTML tags (covers <a>, <span>, <strong>, etc.)
        $content = strip_tags($content);

        // Step 4: Decode HTML entities (e.g. &amp; → &, &#8220; → ")
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Step 5: Normalise whitespace — trim each line, collapse multiple blank lines.
        $lines = explode("\n", $content);
        $paragraphs = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $paragraphs[] = $trimmed;
            }
        }

        // Step 6: Build EditorJS block structure.
        return $this->buildEditorJsBlocks($paragraphs);
    }

    /**
     * Build an EditorJS-compatible JSON structure from an array of clean paragraph strings.
     *
     * @param  string[]  $paragraphs
     * @return array
     */
    protected function buildEditorJsBlocks(array $paragraphs): array
    {
        $blocks = [];

        foreach ($paragraphs as $paragraph) {
            $blocks[] = [
                'type' => 'paragraph',
                'data' => [
                    'text' => $paragraph,
                ],
            ];
        }

        // If no blocks were generated, return a single empty paragraph block.
        if (empty($blocks)) {
            $blocks[] = [
                'type' => 'paragraph',
                'data' => ['text' => ''],
            ];
        }

        return [
            'time'    => now()->timestamp * 1000, // EditorJS uses milliseconds
            'blocks'  => $blocks,
            'version' => '2.29.1',
        ];
    }
}

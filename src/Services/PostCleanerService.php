<?php

namespace Bale\Core\Services;

/**
 * PostCleanerService
 *
 * Converts raw WordPress `post_content` HTML into an EditorJS-compatible
 * block array suitable for the Bale CMS `content` field.
 *
 * Images and videos are NOT dropped. Instead they are converted into
 * placeholder blocks (`image-placeholder` / `media-placeholder`) so that a
 * separate media migration service can later download, convert and upload
 * the asset to the CDN/S3, then swap the placeholder for a real `image` block.
 *
 * Placeholder block shapes:
 *   { "type": "image-placeholder", "data": { "wp_src": "...", "caption": "", "status": "pending" } }
 *   { "type": "media-placeholder", "data": { "wp_src": "...", "kind": "video", "status": "pending" } }
 */
class PostCleanerService
{
    /**
     * Tags that represent a single block of text.
     */
    protected const TEXT_TAGS = [
        'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'li', 'blockquote', 'pre', 'td', 'th',
    ];

    /**
     * Process raw WordPress post_content into clean EditorJS JSON.
     *
     * @param  string  $rawContent  The raw `post_content` value from the SQL dump.
     * @return array EditorJS-compatible JSON structure (associative array).
     */
    public function clean(string $rawContent): array
    {
        // Step 1: Remove Gutenberg block comment delimiters.
        $content = preg_replace('/<!--\s*\/?wp:[^>]*-->/i', '', $rawContent) ?? $rawContent;

        // Step 2: Parse with DOM for structure-aware extraction.
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div data-bale-root>'.$content.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $blocks = [];
        $root = $dom->getElementsByTagName('div')->item(0);

        if ($root) {
            foreach ($root->childNodes as $node) {
                $this->extractBlocks($node, $blocks);
            }
        }

        // Fallback: never return an empty block list.
        if (empty($blocks)) {
            $blocks[] = [
                'type' => 'paragraph',
                'data' => ['text' => ''],
            ];
        }

        return [
            'time' => now()->timestamp * 1000, // EditorJS uses milliseconds
            'blocks' => $blocks,
            'version' => '2.31.3',
        ];
    }

    /**
     * Recursively extract EditorJS blocks from a DOM node, preserving order.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     */
    protected function extractBlocks(\DOMNode $node, array &$blocks): void
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = trim($node->nodeValue);
            if ($text !== '') {
                $blocks[] = ['type' => 'paragraph', 'data' => ['text' => $text]];
            }

            return;
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        $tagName = strtolower($node->tagName);

        // --- Media: images become placeholders -------------------------------
        if ($tagName === 'img') {
            $src = trim($node->getAttribute('src'));
            if ($src !== '') {
                $blocks[] = [
                    'type' => 'image-placeholder',
                    'data' => [
                        'wp_src' => $src,
                        'caption' => trim($node->getAttribute('alt')),
                        'status' => 'pending',
                    ],
                ];
            }

            return;
        }

        // --- Media: videos become placeholders -------------------------------
        if ($tagName === 'video') {
            $src = trim($node->getAttribute('src'));
            if ($src === '') {
                foreach ($node->childNodes as $child) {
                    if ($child->nodeType === XML_ELEMENT_NODE && strtolower($child->tagName) === 'source') {
                        $src = trim($child->getAttribute('src'));
                        break;
                    }
                }
            }
            if ($src !== '') {
                $blocks[] = [
                    'type' => 'media-placeholder',
                    'data' => [
                        'wp_src' => $src,
                        'kind' => 'video',
                        'status' => 'pending',
                    ],
                ];
            }

            return;
        }

        // Skip figcaption (its text is folded into the media caption above).
        if ($tagName === 'figcaption') {
            return;
        }

        // --- Any element containing media: recurse (figure/div/p/wrapper) -----
        if ($node->getElementsByTagName('img')->length > 0
            || $node->getElementsByTagName('video')->length > 0) {

            $caption = '';
            $figcaptions = $node->getElementsByTagName('figcaption');
            if ($figcaptions->length > 0) {
                $caption = trim($figcaptions->item(0)->textContent);
            }

            $startIndex = count($blocks);
            foreach ($node->childNodes as $child) {
                $this->extractBlocks($child, $blocks);
            }

            if ($caption !== '') {
                for ($i = $startIndex; $i < count($blocks); $i++) {
                    if (isset($blocks[$i]['data']) && array_key_exists('caption', $blocks[$i]['data'])) {
                        // The visible <figcaption> takes precedence over the img alt text.
                        $blocks[$i]['data']['caption'] = $caption;
                    }
                }
            }

            return;
        }

        // --- Plain text blocks ------------------------------------------------
        if (in_array($tagName, self::TEXT_TAGS, true)) {
            $text = trim($node->textContent);
            if ($text !== '') {
                $blocks[] = ['type' => 'paragraph', 'data' => ['text' => $text]];
            }

            return;
        }

        // --- Generic containers: recurse, otherwise emit text -----------------
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                $this->extractBlocks($child, $blocks);
            }

            return;
        }

        $text = trim($node->textContent);
        if ($text !== '') {
            $blocks[] = ['type' => 'paragraph', 'data' => ['text' => $text]];
        }
    }
}

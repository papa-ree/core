<?php

namespace Bale\Core\Services;

use Illuminate\Support\Str;

/**
 * MediaSecurityScanner
 *
 * Static analyser that inspects a raw media binary *before* it is imported
 * into Bale, looking for the techniques used by WordPress media backdoors:
 *
 *   - embedded server-side code (`<?php`, `eval()`, `base64_decode()` …),
 *   - embedded markup / script (`<script>`, `<iframe>`, `javascript:` …),
 *   - polyglot files (a valid image with an executable payload appended),
 *   - hidden links (URLs / anchors smuggled inside EXIF/XMP/comment metadata),
 *   - disguised payloads (executable / archive / script magic bytes),
 *   - dangerous file extensions (`.php`, `.phtml`, `.svg`, `.htaccess` …).
 *
 * The scanner is intentionally dependency-free and only *reports*: it returns
 * a {@see MediaScanResult} whose `allowed` flag is false when a HIGH severity
 * finding is present. Callers decide whether to quarantine the file.
 */
class MediaSecurityScanner
{
    /**
     * Files larger than this are rejected outright (bytes). Default 256 MiB.
     */
    protected int $maxBytes = 268_435_456;

    /**
     * Files up to this size are scanned in full; larger files are scanned on
     * the first/last chunk (payloads are typically prepended or appended).
     */
    protected int $fullScanBytes = 16_777_216;

    /**
     * Number of bytes kept from the head/tail of large files.
     */
    protected int $chunkBytes = 262_144;

    /**
     * Any trailing byte beyond this count after the logical end of an image is
     * considered suspicious (polyglot / appended payload).
     */
    protected int $trailingTolerance = 16;

    /**
     * Extensions that must never be stored as-is (server executable / markup).
     *
     * @var array<int, string>
     */
    protected const DANGEROUS_EXTENSIONS = [
        'php', 'php1', 'php2', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8',
        'phtml', 'pht', 'phps', 'phar', 'inc',
        'shtml', 'shtm', 'stm', 'cgi', 'fcgi', 'pl', 'pm', 'py', 'rb',
        'sh', 'bash', 'zsh', 'ksh', 'csh', 'ps1', 'psm1', 'bat', 'cmd', 'com',
        'exe', 'dll', 'scr', 'msi', 'vbs', 'vbe', 'wsf', 'wsh', 'jar', 'class',
        'js', 'jse', 'mjs', 'htaccess', 'htpasswd',
        'html', 'htm', 'xhtml', 'xht', 'svg', 'svgz', 'xml', 'xsl', 'xslt',
        'asp', 'aspx', 'ashx', 'asmx', 'jsp', 'jspx', 'do', 'action',
    ];

    /**
     * Signature patterns: [regex, code, message, severity].
     *
     * Only patterns of 4+ bytes are treated as HIGH so that random bytes in a
     * compressed stream cannot realistically produce a false positive.
     *
     * @var array<int, array{0: string, 1: string, 2: string, 3: string}>
     */
    protected const SIGNATURES = [
        // --- Server-side code (PHP) ---------------------------------------
        ['/<\?php/i', 'php_open_tag', 'Embedded PHP opening tag (<?php)', MediaScanResult::SEVERITY_HIGH],
        ['/<\?=/i', 'php_echo_tag', 'Embedded PHP short echo tag (<?=)', MediaScanResult::SEVERITY_HIGH],
        ['/<\?(?=\s|\$)/', 'php_short_tag', 'Possible embedded PHP short tag (<?)', MediaScanResult::SEVERITY_MEDIUM],
        ['/\bphar:\/\//i', 'php_phar_stream', 'PHP phar:// stream wrapper', MediaScanResult::SEVERITY_HIGH],
        ['/\bphp:\/\/(input|filter|stdin)/i', 'php_stream', 'PHP stream wrapper (php://input|filter|stdin)', MediaScanResult::SEVERITY_HIGH],

        // --- Dangerous function calls (webshell patterns) -----------------
        ['/\beval\s*\(/i', 'fn_eval', 'eval() call', MediaScanResult::SEVERITY_HIGH],
        ['/\bshell_exec\b/i', 'fn_shell_exec', 'shell_exec() call', MediaScanResult::SEVERITY_HIGH],
        ['/\bsystem\s*\(/i', 'fn_system', 'system() call', MediaScanResult::SEVERITY_HIGH],
        ['/\bpassthru\s*\(/i', 'fn_passthru', 'passthru() call', MediaScanResult::SEVERITY_HIGH],
        ['/\bpopen\s*\(/i', 'fn_popen', 'popen() call', MediaScanResult::SEVERITY_HIGH],
        ['/\bproc_open\s*\(/i', 'fn_proc_open', 'proc_open() call', MediaScanResult::SEVERITY_HIGH],
        ['/\bbase64_decode\s*\(/i', 'fn_base64_decode', 'base64_decode() call', MediaScanResult::SEVERITY_HIGH],
        ['/\bgzinflate\s*\(/i', 'fn_gzinflate', 'gzinflate() call', MediaScanResult::SEVERITY_HIGH],
        ['/\bgzuncompress\s*\(/i', 'fn_gzuncompress', 'gzuncompress() call', MediaScanResult::SEVERITY_HIGH],
        ['/\bcreate_function\s*\(/i', 'fn_create_function', 'create_function() call', MediaScanResult::SEVERITY_HIGH],
        ['/\b(include|require)(_once)?\s*\(/i', 'fn_include', 'include()/require() call', MediaScanResult::SEVERITY_HIGH],
        ['/\bassert\s*\(/i', 'fn_assert', 'assert() call', MediaScanResult::SEVERITY_MEDIUM],
        ['/\bexec\s*\(/i', 'fn_exec', 'exec() call', MediaScanResult::SEVERITY_MEDIUM],
        ['/\bstr_rot13\s*\(/i', 'fn_str_rot13', 'str_rot13() call', MediaScanResult::SEVERITY_MEDIUM],
        ['/\bcall_user_func\s*\(/i', 'fn_call_user_func', 'call_user_func() call', MediaScanResult::SEVERITY_MEDIUM],
        ['/\bfile_put_contents\s*\(/i', 'fn_file_put_contents', 'file_put_contents() call', MediaScanResult::SEVERITY_MEDIUM],
        ['/\bcurl_exec\s*\(/i', 'fn_curl_exec', 'curl_exec() call', MediaScanResult::SEVERITY_MEDIUM],
        ['/preg_replace\s*\([^)]*\/[a-z]*e[a-z]*[\'"]/i', 'fn_preg_replace_e', 'preg_replace() with the /e modifier', MediaScanResult::SEVERITY_HIGH],

        // --- Markup / scripting injection ---------------------------------
        ['/<script[\s>\/]/i', 'script_tag', 'Embedded <script> tag', MediaScanResult::SEVERITY_HIGH],
        ['/<\/script/i', 'script_close_tag', 'Embedded </script> tag', MediaScanResult::SEVERITY_HIGH],
        ['/<iframe[\s>\/]/i', 'iframe_tag', 'Embedded <iframe>', MediaScanResult::SEVERITY_HIGH],
        ['/<object[\s>\/]/i', 'object_tag', 'Embedded <object>', MediaScanResult::SEVERITY_HIGH],
        ['/<embed[\s>\/]/i', 'embed_tag', 'Embedded <embed>', MediaScanResult::SEVERITY_HIGH],
        ['/<applet[\s>\/]/i', 'applet_tag', 'Embedded <applet>', MediaScanResult::SEVERITY_HIGH],
        ['/javascript:/i', 'javascript_uri', 'javascript: URI', MediaScanResult::SEVERITY_HIGH],
        ['/vbscript:/i', 'vbscript_uri', 'vbscript: URI', MediaScanResult::SEVERITY_HIGH],
        ['/<svg[\s>\/]/i', 'svg_tag', 'Embedded <svg> (can carry script)', MediaScanResult::SEVERITY_MEDIUM],
        ['/<meta[^>]+http-equiv\s*=\s*["\']?\s*refresh/i', 'meta_refresh', 'HTML meta refresh redirect', MediaScanResult::SEVERITY_MEDIUM],
        ['/<a\s+[^>]*href\s*=/i', 'anchor_tag', 'Embedded HTML anchor (<a href>)', MediaScanResult::SEVERITY_MEDIUM],
        ['/on(error|load|click|mouse\w+|focus|submit)\s*=/i', 'event_handler', 'Inline JavaScript event handler', MediaScanResult::SEVERITY_MEDIUM],
        ['/\bdata:(text\/html|application\/xhtml)/i', 'data_html_uri', 'data:text/html URI (HTML smuggling)', MediaScanResult::SEVERITY_MEDIUM],

        // --- Client-side code ---------------------------------------------
        ['/document\.(write|cookie)/i', 'js_document', 'JavaScript document.write()/cookie', MediaScanResult::SEVERITY_MEDIUM],
        ['/string\.fromcharcode\s*\(/i', 'js_fromcharcode', 'JavaScript String.fromCharCode() obfuscation', MediaScanResult::SEVERITY_MEDIUM],
        ['/unescape\s*\(/i', 'js_unescape', 'JavaScript unescape() obfuscation', MediaScanResult::SEVERITY_LOW],
    ];

    public function setMaxBytes(int $bytes): static
    {
        $this->maxBytes = max(1, $bytes);

        return $this;
    }

    /**
     * Scan a raw binary for embedded/hidden threats.
     *
     * @param  string  $binary  Raw file contents.
     * @param  string|null  $filename  Original filename or extension hint (e.g. "photo.php").
     * @param  string|null  $expectedKind  "image" or "video" — used to flag type mismatches.
     */
    public function scan(string $binary, ?string $filename = null, ?string $expectedKind = null): MediaScanResult
    {
        $findings = [];
        $size = strlen($binary);
        $meta = ['size' => $size];

        if ($size === 0) {
            return new MediaScanResult(false, MediaScanResult::SEVERITY_HIGH, [[
                'code' => 'empty_file',
                'message' => 'The file is empty.',
                'severity' => MediaScanResult::SEVERITY_HIGH,
            ]], $meta);
        }

        if ($size > $this->maxBytes) {
            $findings[] = [
                'code' => 'file_too_large',
                'message' => sprintf('File exceeds the %d byte scan limit.', $this->maxBytes),
                'severity' => MediaScanResult::SEVERITY_HIGH,
            ];
        }

        $head = substr($binary, 0, 4096);
        $tail = $size > 4096 ? substr($binary, -4096) : '';

        // 1. Filename / extension hint.
        $extension = $this->extension($filename);
        if ($extension !== null) {
            $meta['extension'] = $extension;

            if (in_array($extension, self::DANGEROUS_EXTENSIONS, true)) {
                $findings[] = [
                    'code' => 'dangerous_extension',
                    'message' => sprintf('Dangerous file extension ".%s".', $extension),
                    'severity' => MediaScanResult::SEVERITY_HIGH,
                ];
            }
        }

        // 2. Executable / archive / document magic bytes.
        if ($executable = $this->executableSignature($head)) {
            $findings[] = [
                'code' => 'executable_content',
                'message' => 'File starts with '.$executable.'.',
                'severity' => MediaScanResult::SEVERITY_HIGH,
            ];
        }

        // 3. Detect the real type from content.
        $imageInfo = @getimagesizefromstring($binary) ?: null;
        $detected = $this->detectType($binary) ?? ($imageInfo['mime'] ?? null);
        $meta['detected_type'] = $detected;
        if ($imageInfo) {
            $meta['width'] = $imageInfo[0];
            $meta['height'] = $imageInfo[1];
        } else {
            $meta['is_raster_image'] = false;
        }

        // 4. Signature scan (head + tail for very large files).
        $window = $binary;
        if ($size > $this->fullScanBytes) {
            $window = $head.substr($binary, -$this->chunkBytes);
            $meta['scan_window'] = 'head+tail';
        }

        foreach (self::SIGNATURES as [$pattern, $code, $message, $severity]) {
            if (preg_match($pattern, $window, $matches)) {
                $findings[] = [
                    'code' => $code,
                    'message' => $message,
                    'severity' => $severity,
                    'sample' => Str::limit(trim(preg_replace('/\s+/', ' ', $matches[0]) ?? ''), 80),
                ];
            }
        }

        // 5. Hidden links (URLs/anchor-like strings smuggled in the payload).
        $links = $this->findLinks($window);
        if ($links !== []) {
            $meta['links'] = array_slice($links, 0, 10);
            $findings[] = [
                'code' => 'hidden_link',
                'message' => sprintf('Embedded URL(s) found: %s', implode(', ', array_slice($links, 0, 3))),
                'severity' => MediaScanResult::SEVERITY_MEDIUM,
                'sample' => $links[0],
            ];
        }

        // 6. Obfuscated (long base64) payloads.
        if (preg_match('/[A-Za-z0-9+\/]{512,}={0,2}/', $window, $m)) {
            $findings[] = [
                'code' => 'base64_blob',
                'message' => 'Long base64-encoded blob detected (possible obfuscation).',
                'severity' => MediaScanResult::SEVERITY_LOW,
                'sample' => Str::limit($m[0], 60),
            ];
        }

        // 7. Polyglot / trailing data after the logical end of the image.
        if ($detected !== null && str_starts_with($detected, 'image/')) {
            $trailing = $this->trailingDataLength($binary, $detected);
            $meta['trailing_bytes'] = $trailing;

            if ($trailing > $this->trailingTolerance) {
                $findings[] = [
                    'code' => 'trailing_data',
                    'message' => sprintf('%d unexpected byte(s) after the image end (polyglot risk).', $trailing),
                    'severity' => $trailing > 1024
                        ? MediaScanResult::SEVERITY_HIGH
                        : MediaScanResult::SEVERITY_MEDIUM,
                ];
            }
        }

        // 8. Expected-kind validation.
        $findings = array_merge($findings, $this->kindFindings($expectedKind, $imageInfo, $detected));

        $severity = $this->maxSeverity($findings);

        return new MediaScanResult(
            $severity !== MediaScanResult::SEVERITY_HIGH,
            $severity,
            $findings,
            $meta,
        );
    }

    /**
     * Detect the real media MIME type from the file header.
     */
    public function detectType(string $binary): ?string
    {
        if (strlen($binary) < 12) {
            return null;
        }

        $sig = substr($binary, 0, 12);

        // Images.
        if (str_starts_with($sig, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }
        if (str_starts_with($sig, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }
        if (str_starts_with($sig, 'GIF87a') || str_starts_with($sig, 'GIF89a')) {
            return 'image/gif';
        }
        if (str_starts_with($sig, 'RIFF') && substr($sig, 8, 4) === 'WEBP') {
            return 'image/webp';
        }
        if (str_starts_with($sig, 'BM')) {
            return 'image/bmp';
        }
        if (str_starts_with($sig, "II*\x00") || str_starts_with($sig, "MM\x00*")) {
            return 'image/tiff';
        }

        // Video.
        if (substr($binary, 4, 4) === 'ftyp') {
            return 'video/mp4';
        }
        if (str_starts_with($sig, "\x1A\x45\xDF\xA3")) {
            return 'video/webm';
        }
        if (str_starts_with($sig, 'OggS')) {
            return 'video/ogg';
        }
        if (str_starts_with($sig, 'RIFF') && substr($sig, 8, 4) === 'AVI ') {
            return 'video/x-msvideo';
        }
        if (str_starts_with($sig, "\x00\x00\x01\xBA") || str_starts_with($sig, "\x00\x00\x01\xB3")) {
            return 'video/mpeg';
        }
        if (str_starts_with($sig, 'FLV')) {
            return 'video/x-flv';
        }
        if (str_starts_with($sig, "\x30\x26\xB2\x75")) {
            return 'video/x-ms-wmv';
        }

        return null;
    }

    /**
     * Resolve a safe extension for a video file based on its content, never
     * trusting a dangerous extension taken from the source URL.
     */
    public function safeVideoExtension(string $binary, ?string $hint = null): ?string
    {
        $hint = $hint ? strtolower($hint) : null;

        return match ($this->detectType($binary)) {
            'video/mp4' => in_array($hint, ['mov', 'm4v'], true) ? $hint : 'mp4',
            'video/webm' => 'webm',
            'video/ogg' => 'ogv',
            'video/x-msvideo' => 'avi',
            'video/mpeg' => 'mpg',
            'video/x-flv' => 'flv',
            'video/x-ms-wmv' => 'wmv',
            default => null,
        };
    }

    /**
     * @return array<int, array{code: string, message: string, severity: string, sample?: string}>
     */
    protected function kindFindings(?string $expectedKind, ?array $imageInfo, ?string $detected): array
    {
        if ($expectedKind === null) {
            return [];
        }

        $findings = [];

        if ($expectedKind === 'image') {
            if (! $imageInfo) {
                $findings[] = [
                    'code' => 'not_an_image',
                    'message' => 'File is not a valid raster image.',
                    'severity' => MediaScanResult::SEVERITY_HIGH,
                ];
            } elseif ($detected !== null && str_starts_with($detected, 'video/')) {
                $findings[] = [
                    'code' => 'type_mismatch',
                    'message' => 'Expected an image but the content is a video.',
                    'severity' => MediaScanResult::SEVERITY_HIGH,
                ];
            }
        }

        if ($expectedKind === 'video') {
            if ($detected === null) {
                $findings[] = [
                    'code' => 'unrecognized_video',
                    'message' => 'Unrecognized or unsupported video container.',
                    'severity' => MediaScanResult::SEVERITY_HIGH,
                ];
            } elseif (! str_starts_with($detected, 'video/')) {
                $findings[] = [
                    'code' => 'type_mismatch',
                    'message' => 'Expected a video but the content is '.$detected.'.',
                    'severity' => MediaScanResult::SEVERITY_HIGH,
                ];
            }
        }

        return $findings;
    }

    /**
     * Read the RIFF/JPEG/PNG/GIF trailer and return the number of bytes found
     * after the logical end of the image.
     */
    protected function trailingDataLength(string $binary, string $type): int
    {
        $size = strlen($binary);

        switch ($type) {
            case 'image/jpeg':
                $pos = strrpos($binary, "\xFF\xD9");

                return $pos === false ? 0 : max(0, $size - ($pos + 2));

            case 'image/png':
                $pos = strrpos($binary, 'IEND');

                return $pos === false ? 0 : max(0, $size - ($pos + 8));

            case 'image/gif':
                $pos = strrpos($binary, "\x3B");

                return $pos === false ? 0 : max(0, $size - ($pos + 1));

            case 'image/webp':
            case 'image/bmp':
                $offset = $type === 'image/webp' ? 4 : 2;
                $declared = unpack('V', substr($binary, $offset, 4))[1] ?? null;

                return $declared === null ? 0 : max(0, $size - ($declared + 8));

            default:
                return 0;
        }
    }

    /**
     * Detect executable / archive / document containers that must never be
     * treated as media.
     */
    protected function executableSignature(string $head): ?string
    {
        if (str_starts_with($head, 'MZ')) {
            return 'a Windows PE executable (MZ)';
        }
        if (str_starts_with($head, "\x7FELF")) {
            return 'an ELF executable';
        }
        if (str_starts_with($head, "\xCA\xFE\xBA\xBE")
            || str_starts_with($head, "\xCE\xFA\xED\xFE")
            || str_starts_with($head, "\xCF\xFA\xED\xFE")
            || str_starts_with($head, "\xFE\xED\xFA\xCE")
            || str_starts_with($head, "\xFE\xED\xFA\xCF")) {
            return 'a Mach-O / Java class file';
        }
        if (str_starts_with($head, '#!')) {
            return 'a script shebang (#!)';
        }
        if (str_starts_with($head, "PK\x03\x04")) {
            return 'a ZIP archive (PK\x03\x04)';
        }
        if (str_starts_with($head, "\xD0\xCF\x11\xE0")) {
            return 'a legacy OLE compound document';
        }
        if (str_starts_with($head, '%PDF')) {
            return 'a PDF document';
        }

        return null;
    }

    /**
     * Extract unique http(s) URLs found in the payload (hidden links).
     *
     * @return array<int, string>
     */
    protected function findLinks(string $binary): array
    {
        if (! preg_match_all('#https?://[^\s\x27"<>]+#i', $binary, $matches)) {
            return [];
        }

        $links = [];

        foreach ($matches[0] as $url) {
            $url = rtrim($url, '.,;:)]}\'"');
            if (strlen($url) >= 11) {
                $links[$url] = true;
            }
        }

        return array_slice(array_keys($links), 0, 25);
    }

    protected function extension(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        $extension = strtolower(pathinfo(parse_url($filename, PHP_URL_PATH) ?: $filename, PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : null;
    }

    /**
     * @param  array<int, array{severity: string}>  $findings
     */
    protected function maxSeverity(array $findings): string
    {
        $rank = [
            MediaScanResult::SEVERITY_NONE => 0,
            MediaScanResult::SEVERITY_LOW => 1,
            MediaScanResult::SEVERITY_MEDIUM => 2,
            MediaScanResult::SEVERITY_HIGH => 3,
        ];

        $max = 0;

        foreach ($findings as $finding) {
            $max = max($max, $rank[$finding['severity'] ?? MediaScanResult::SEVERITY_LOW] ?? 0);
        }

        return array_search($max, $rank, true) ?: MediaScanResult::SEVERITY_NONE;
    }
}

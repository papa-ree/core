<?php

use Bale\Core\Services\WpSqlParser;

if (! function_exists('wp_test_dump')) {
    function wp_test_dump(): string
    {
        return <<<'SQL'
-- sample WordPress dump
DROP TABLE IF EXISTS `wp_posts`;
CREATE TABLE `wp_posts` (
  `ID` bigint NOT NULL,
  `post_title` text
);
INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_modified`, `post_content`, `post_title`, `post_type`, `post_status`) VALUES
(1, 3, '2023-05-01 10:00:00', '2023-05-02 11:00:00', '<p>Halo, <strong>dunia</strong></p>', 'Berita Pertama', 'post', 'publish'),
(2, 3, '2023-06-01 10:00:00', '2023-06-01 10:00:00', '<p>Draft</p>', 'Draft Post', 'post', 'draft'),
(3, 3, '2023-07-01 10:00:00', '2023-07-01 10:00:00', '', 'Tentang Kami', 'page', 'publish'),
(4, 3, '2023-08-01 10:00:00', '2023-08-01 10:00:00', '<p>Dua</p>', 'Berita Kedua', 'post', 'publish');
SQL;
    }
}

it('parses only published posts and reports parser counters', function () {
    $parser = new WpSqlParser;
    $posts = $parser->parse(wp_test_dump());

    expect($posts)->toHaveCount(2);

    expect($posts[0]['ID'])->toBe('1');
    expect($posts[0]['post_title'])->toBe('Berita Pertama');
    // Commas inside quoted values must survive the CSV-ish splitter.
    expect($posts[0]['post_content'])->toBe('<p>Halo, <strong>dunia</strong></p>');

    expect($posts[1]['ID'])->toBe('4');
    expect($posts[1]['post_title'])->toBe('Berita Kedua');

    expect($parser->totalFoundCount)->toBe(4);
    expect($parser->filteredCount)->toBe(2);
});

it('auto-detects the table prefix but honours an explicit one', function () {
    $sql = "INSERT INTO `mysite_posts` (`ID`, `post_title`, `post_type`, `post_status`) VALUES (9, 'Prefixed', 'post', 'publish');";

    expect((new WpSqlParser)->parse($sql))->toHaveCount(1);
    expect((new WpSqlParser)->parse($sql, 'mysite_'))->toHaveCount(1);
    expect((new WpSqlParser)->parse($sql, 'wp_'))->toHaveCount(0);
});

it('returns nothing when there is no posts insert statement', function () {
    expect((new WpSqlParser)->parse('SELECT 1;'))->toBe([]);
});

it('recognises a dump by content, not by extension or sniffed mime type', function () {
    // Regression: the UI used to validate uploads with `mimes:txt`, which
    // compares the extension *guessed from the file contents* (finfo). The real
    // dindik dump is sniffed as `text/html` because post_content is HTML-heavy,
    // so a perfectly valid dump was rejected as "must be a file of type: txt" —
    // even when the file was named .txt.
    expect((new WpSqlParser)->looksLikeDump(wp_test_dump()))->toBeTrue()
        ->and((new WpSqlParser)->looksLikeDump('CREATE TABLE `wp_posts` (`ID` bigint);'))->toBeTrue();
});

it('rejects a file that is clearly not a dump', function () {
    expect((new WpSqlParser)->looksLikeDump(''))->toBeFalse()
        ->and((new WpSqlParser)->looksLikeDump('<html><body>Not a dump</body></html>'))->toBeFalse()
        ->and((new WpSqlParser)->looksLikeDump('{"blocks":[]}'))->toBeFalse();
});

if (! function_exists('wp_test_mojibake')) {
    /**
     * Reproduce what a dump client does when it reads a UTF-8 column as CP1252
     * and re-encodes the bytes as UTF-8 (`🎉` → `ðŸŽ‰`).
     */
    function wp_test_mojibake(string $utf8): string
    {
        return mb_convert_encoding($utf8, 'UTF-8', 'Windows-1252');
    }
}

it('repairs double-encoded (mojibake) text written by the dump client', function () {
    // The real dindik export turned `🎉🏆 SELAMAT` into `ðŸŽ‰ðŸŽ‰ SELAMAT` and
    // `Al-Qur’an` into `Al-Qurâ€™an`. Both are reversible, so they are repaired.
    $sql = 'INSERT INTO `wp_posts` (`ID`, `post_title`, `post_content`, `post_type`, `post_status`) VALUES '
        .'(1, \''.wp_test_mojibake('🎉🏆 SELAMAT').'\', \'<p>'.wp_test_mojibake('Al-Qur’an').'</p>\', \'post\', \'publish\');';

    $parser = new WpSqlParser;
    $posts = $parser->parse($sql);

    expect($posts[0]['post_title'])->toBe('🎉🏆 SELAMAT')
        ->and($posts[0]['post_content'])->toBe('<p>Al-Qur’an</p>')
        ->and($parser->encodingRepairedCount)->toBe(2);
});

it('leaves correctly encoded UTF-8 untouched', function () {
    // Guard: `é`/`’`/`🎉` collapse to a single byte or to `?` under the reverse
    // transform, so a correctly exported dump must never be rewritten.
    $sql = 'INSERT INTO `wp_posts` (`ID`, `post_title`, `post_content`, `post_type`, `post_status`) VALUES '
        .'(1, \'SÃO PAULO 🎉\', \'<p>Qurrota A’yun — café</p>\', \'post\', \'publish\');';

    $parser = new WpSqlParser;
    $posts = $parser->parse($sql);

    expect($posts[0]['post_title'])->toBe('SÃO PAULO 🎉')
        ->and($posts[0]['post_content'])->toBe('<p>Qurrota A’yun — café</p>')
        ->and($parser->encodingRepairedCount)->toBe(0);
});

it('can import the dump bytes verbatim when the repair is disabled', function () {
    $mojibake = wp_test_mojibake('🎉 SELAMAT');
    $sql = 'INSERT INTO `wp_posts` (`ID`, `post_title`, `post_content`, `post_type`, `post_status`) VALUES '
        ."(1, '{$mojibake}', '<p>Isi</p>', 'post', 'publish');";

    $parser = new WpSqlParser;
    $posts = $parser->parse($sql, '', false);

    expect($posts[0]['post_title'])->toBe($mojibake)
        ->and($parser->encodingRepairedCount)->toBe(0);
});

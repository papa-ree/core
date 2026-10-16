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

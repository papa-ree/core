<?php

use Bale\Core\Livewire\Pages\WpTools\SqlMigrator\Index;
use Livewire\Attributes\Validate;

/*
|--------------------------------------------------------------------------
| Chained stages of the SQL migrator UI
|--------------------------------------------------------------------------
|
| The upload form drives the whole chain (SQL → media → featured images), so it
| has to keep exposing the two switches and the legacy WordPress base URL. The
| base URL is not cosmetic: the thumbnail stage only downloads from that host
| (a dump is untrusted input, so its `guid` values are never followed blindly),
| which is why the rule must stay limited to http/https.
|
*/

it('exposes the WordPress base URL with an http/https only rule', function () {
    $rule = (new ReflectionProperty(Index::class, 'wpBase'))
        ->getAttributes(Validate::class)[0]
        ->newInstance()
        ->rule['wpBase'];

    $rule = is_array($rule) ? implode('|', $rule) : (string) $rule;

    expect($rule)->toContain('nullable')
        ->toContain('url:http,https')
        ->not->toContain('any');
});

it('chains both optional stages by default', function () {
    $component = new Index;

    expect($component->chainMedia)->toBeTrue()
        ->and($component->chainThumbnails)->toBeTrue()
        ->and($component->wpBase)->toBeNull();
});

it('blocks the upload when thumbnails are chained without a base URL', function () {
    $component = new Index;
    $component->chainThumbnails = true;
    $component->wpBase = null;

    expect($component->chainOptionError())->toContain('WordPress Base URL');

    // The media stage alone does not need it (post content usually carries
    // absolute URLs), and a local uploads mirror is a CLI-only option.
    $component->chainThumbnails = false;
    expect($component->chainOptionError())->toBeNull();

    $component->chainThumbnails = true;
    $component->wpBase = 'https://dindik.ponorogo.go.id';
    expect($component->chainOptionError())->toBeNull();
});

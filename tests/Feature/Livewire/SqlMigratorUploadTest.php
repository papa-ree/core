<?php

use Bale\Core\Livewire\Pages\WpTools\SqlMigrator\Index;
use Livewire\Attributes\Validate;

/*
|--------------------------------------------------------------------------
| Upload validation for the SQL migrator
|--------------------------------------------------------------------------
|
| The upload rule must never go back to `mimes:`/`mimetypes:`. Both compare
| against the extension *guessed from the file contents* (finfo via Symfony's
| MimeTypes): the real dindik dump is sniffed as `text/html` — post_content is
| full of HTML — so the guessed extension is `html`, not `txt`, and a perfectly
| valid dump was rejected with "must be a file of type: txt" no matter what the
| file was named. Validating the *content* (WpSqlParser::looksLikeDump) is the
| only reliable check.
|
*/

it('does not validate the dump upload through a content-sniffed mime type', function () {
    $rule = (new ReflectionProperty(Index::class, 'sqlFile'))
        ->getAttributes(Validate::class)[0]
        ->newInstance()
        ->rule['sqlFile'];

    $rule = is_array($rule) ? implode('|', $rule) : (string) $rule;

    expect($rule)->not->toContain('mimes')
        ->not->toContain('mimetypes')
        ->toContain('required')
        ->toContain('file')
        ->toContain('max:102400');
});

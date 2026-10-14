<?php

/**
 * Builds the review gallery for .github/workflows/screenshots.yml.
 *
 * Usage: php screenshot-gallery.php <dir> [--previous=<dir>]
 *
 * <dir> holds one folder per capture, named as the theme tour names them:
 * after-light and after-dark for the ref under review and, when a base was
 * captured too, before-light and before-dark. Each holds the tour's
 * NN-name.png files. Writes README.md, which GitHub renders on the branch
 * page, and index.html, for a local checkout. Prints a one-line headline.
 *
 * --previous points at the last run's files. Where a screen only differs from
 * it by rendering noise, the last run's file is kept, so the commit's diff
 * holds only screens that really changed.
 *
 * GALLERY_REF and GALLERY_SHA name what was captured, GALLERY_BASE_REF and
 * GALLERY_BASE_SHA the base it is compared with, GALLERY_RUN_URL the run.
 */
$dir = rtrim($argv[1] ?? '', '/');
$previous = null;
foreach (array_slice($argv, 2) as $option) {
    if (str_starts_with($option, '--previous=')) {
        $previous = rtrim(substr($option, strlen('--previous=')), '/');
    }
}

if ($dir === '' || ! is_dir($dir)) {
    fwrite(STDERR, "Usage: php screenshot-gallery.php <dir> [--previous=<dir>]\n");
    exit(1);
}

$ref = getenv('GALLERY_REF') ?: 'this ref';
$baseRef = getenv('GALLERY_BASE_REF') ?: null;
$runUrl = getenv('GALLERY_RUN_URL') ?: null;

/**
 * Pixels that differ between two screenshots. Chromium does not rasterise a
 * page identically every time: a run can move a few pixels by a level or two
 * out of 255. Those are not counted, and nor is a difference of a handful of
 * pixels. Pixels only one image has count, since a page that grew pushed
 * everything below it down.
 */
function changedPixels(string $before, string $after): int
{
    if (md5_file($before) === md5_file($after)) {
        return 0;
    }

    $a = imagecreatefrompng($before);
    $b = imagecreatefrompng($after);
    $sharedWidth = min(imagesx($a), imagesx($b));
    $sharedHeight = min(imagesy($a), imagesy($b));
    $changed = max(imagesx($a), imagesx($b)) * max(imagesy($a), imagesy($b)) - $sharedWidth * $sharedHeight;

    for ($y = 0; $y < $sharedHeight; $y++) {
        for ($x = 0; $x < $sharedWidth; $x++) {
            $ca = imagecolorat($a, $x, $y);
            $cb = imagecolorat($b, $x, $y);
            if ($ca !== $cb && max(
                abs((($ca >> 16) & 255) - (($cb >> 16) & 255)),
                abs((($ca >> 8) & 255) - (($cb >> 8) & 255)),
                abs(($ca & 255) - ($cb & 255)),
            ) > 2) {
                $changed++;
            }
        }
    }

    return $changed > 16 ? $changed : 0;
}

function changedPercent(string $before, string $after): float
{
    $changed = changedPixels($before, $after);
    if ($changed === 0) {
        return 0.0;
    }
    [$width, $height] = getimagesize($after);

    return 100 * $changed / ($width * $height);
}

if ($previous !== null) {
    foreach (glob("{$dir}/*/*.png") as $file) {
        $last = $previous.substr($file, strlen($dir));
        if (is_file($last) && changedPixels($last, $file) === 0) {
            copy($last, $file);
        }
    }
}

$schemes = array_values(array_filter(['light', 'dark'], fn (string $scheme): bool => is_dir("{$dir}/after-{$scheme}")));

/** @var list<array{folder: string, ref: string, scheme: string}> $columns */
$columns = [];
foreach ($schemes as $scheme) {
    if ($baseRef !== null && is_dir("{$dir}/before-{$scheme}")) {
        $columns[] = ['folder' => "before-{$scheme}", 'ref' => $baseRef, 'scheme' => $scheme];
    }
    $columns[] = ['folder' => "after-{$scheme}", 'ref' => $ref, 'scheme' => $scheme];
}

$screens = [];
foreach ($columns as $column) {
    foreach (glob("{$dir}/{$column['folder']}/*.png") as $file) {
        $screens[basename($file, '.png')] = true;
    }
}
ksort($screens);
$screens = array_keys($screens);

/** @var array<string, array<string, float>> $changes screen => scheme => percent */
$changes = [];
if ($baseRef !== null) {
    foreach ($screens as $screen) {
        foreach ($schemes as $scheme) {
            $before = "{$dir}/before-{$scheme}/{$screen}.png";
            $after = "{$dir}/after-{$scheme}/{$screen}.png";
            if (is_file($before) && is_file($after)) {
                $changes[$screen][$scheme] = changedPercent($before, $after);
            }
        }
    }
}

function title(string $screen): string
{
    [$number, $rest] = explode('-', $screen, 2) + [1 => ''];

    return "{$number}. ".ucfirst(str_replace('-', ' ', $rest));
}

function percent(?float $value): string
{
    if ($value === null) {
        return '–';
    }

    return $value === 0.0 ? 'same' : ($value < 0.01 ? '<0.01%' : number_format($value, 2).'%');
}

$differing = array_keys(array_filter($changes, fn (array $bySchemes): bool => max($bySchemes) > 0));
$missing = array_values(array_filter($screens, function (string $screen) use ($dir, $columns): bool {
    foreach ($columns as $column) {
        if (! is_file("{$dir}/{$column['folder']}/{$screen}.png")) {
            return true;
        }
    }

    return false;
}));
$logs = glob("{$dir}/logs/*.txt") ?: [];
$e = fn (string $text): string => htmlspecialchars($text, ENT_QUOTES);
$label = fn (array $column): string => "{$column['ref']} · {$column['scheme']}";
// GitHub anchors a "24. Invoice show" heading as #24-invoice-show, which is
// the screen's file name, so links below use that.

/** A ref as code, with the commit it pointed at unless the ref already is one. */
function describe(string $ref, string $sha): string
{
    return $sha === '' || str_starts_with($sha, $ref) ? "`{$ref}`" : "`{$ref}` at `".substr($sha, 0, 8).'`';
}

$captured = describe($ref, (string) getenv('GALLERY_SHA'));
$against = $baseRef !== null ? ', compared with '.describe($baseRef, (string) getenv('GALLERY_BASE_SHA')).',' : '';
$headline = $baseRef !== null
    ? count($differing).' of '.count($changes)." compared screens differ between {$baseRef} and {$ref}"
    : count($screens)." screens of {$ref}";

// README.md -- rendered by GitHub on the branch page.
$md = ["# Screenshots of {$ref}", ''];
$md[] = "Every screen the theme tour visits, captured from {$captured}{$against}"
    .' in '.implode(' and ', $schemes).' mode'
    .($runUrl !== null ? " by [this run]({$runUrl})" : '').'.'
    .' Each run is a commit on this branch, so a commit\'s diff shows what changed since the run before it.';
$md[] = '';

if ($logs !== []) {
    $md[] = '> [!WARNING]';
    $md[] = '> Some of the tour failed, so some screens are missing. See '
        .implode(', ', array_map(fn (string $log): string => '[`'.basename($log).'`](logs/'.basename($log).')', $logs)).'.';
    $md[] = '';
}

if ($baseRef !== null) {
    $md[] = "**{$headline}.**";
    $md[] = '';
    if ($differing !== []) {
        $md[] = '| Screen | '.implode(' | ', array_map('ucfirst', $schemes)).' |';
        $md[] = '|---|'.str_repeat('---:|', count($schemes));
        foreach ($differing as $screen) {
            $cells = array_map(fn (string $scheme): string => percent($changes[$screen][$scheme] ?? null), $schemes);
            $md[] = '| ['.title($screen)."](#{$screen}) | ".implode(' | ', $cells).' |';
        }
        $md[] = '';
        $md[] = 'Percentages are the share of pixels that changed.';
        $md[] = '';
    }
}

if ($missing !== []) {
    $md[] = 'Not captured on every side: '.implode(', ', array_map(fn (string $screen): string => '['.title($screen)."](#{$screen})", $missing)).'.';
    $md[] = '';
}

// GitHub's README column is about 900px wide.
$width = match (count($columns)) {
    1, 2 => 400,
    3 => 260,
    default => 200,
};
foreach ($screens as $screen) {
    $md[] = '## '.title($screen);
    $md[] = '';
    $md[] = '| '.implode(' | ', array_map(fn (array $column): string => "`{$column['ref']}` · {$column['scheme']}", $columns)).' |';
    $md[] = '|'.str_repeat('---|', count($columns));
    $cells = [];
    foreach ($columns as $column) {
        $path = "{$column['folder']}/{$screen}.png";
        $cells[] = is_file("{$dir}/{$path}")
            ? "<a href=\"{$path}\"><img src=\"{$path}\" width=\"{$width}\" alt=\"{$e($label($column))}\"></a>"
            : '*not captured*';
    }
    $md[] = '| '.implode(' | ', $cells).' |';
    $md[] = '';
}

file_put_contents("{$dir}/README.md", implode("\n", $md));

// index.html -- the same gallery for a local checkout.
$sections = '';
foreach ($screens as $screen) {
    $figures = '';
    foreach ($columns as $column) {
        $path = "{$column['folder']}/{$screen}.png";
        $figures .= '<figure><figcaption>'.$e($label($column)).'</figcaption>'
            .(is_file("{$dir}/{$path}")
                ? '<a href="'.$e($path).'" target="_blank"><img loading="lazy" src="'.$e($path).'" alt="'.$e($label($column)).'"></a>'
                : '<p class="missing">not captured</p>')
            .'</figure>';
    }
    $badge = isset($changes[$screen])
        ? ' <small>'.$e(implode(', ', array_map(fn (string $scheme): string => "{$scheme} ".percent($changes[$screen][$scheme] ?? null), array_keys($changes[$screen])))).'</small>'
        : '';
    $sections .= '<section id="'.$e($screen).'"><h2>'.$e(title($screen)).$badge.'</h2>'
        .'<div class="row" style="--columns: '.count($columns).'">'.$figures.'</div></section>';
}

$html = <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Screenshots of {$e($ref)}</title>
<style>
    body { margin: 0 auto; max-width: 1800px; padding: 24px; font: 14px/1.5 system-ui, sans-serif; color: #111827; background: #f3f4f6; }
    h1 { font-size: 22px; margin: 0 0 4px; }
    h2 { font-size: 16px; margin: 32px 0 8px; }
    h2 small { font-weight: 400; color: #6b7280; margin-left: 8px; }
    .row { display: grid; grid-template-columns: repeat(var(--columns), minmax(0, 1fr)); gap: 12px; align-items: start; }
    figure { margin: 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px; }
    figcaption { font-size: 12px; color: #4b5563; margin-bottom: 6px; }
    img { display: block; width: 100%; height: auto; border-radius: 4px; }
    .missing { color: #9ca3af; font-style: italic; }
</style>
</head>
<body>
<h1>Screenshots of {$e($ref)}</h1>
<p>{$e($headline)}.</p>
{$sections}
</body>
</html>
HTML;

file_put_contents("{$dir}/index.html", $html);

echo $headline.PHP_EOL;

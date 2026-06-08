<?php

namespace App\Services;

use enshrined\svgSanitize\Sanitizer;
use Illuminate\Http\UploadedFile;

/**
 * Sanitizes uploaded SVG files to strip XSS vectors (scripts, event
 * handlers, external references) before they are persisted or rendered.
 *
 * Laravel's `image` rule rejects SVGs by default for this reason; once we
 * opt back in with `image:allow_svg`, every accepted SVG must pass through
 * here so a malicious upload can never reach a PDF or public document view.
 */
class SvgSanitizer
{
    /**
     * Sanitize an uploaded SVG in place.
     *
     * Non-SVG uploads are left untouched and reported as valid. SVGs are
     * rewritten with the cleaned markup. Returns false only when the file
     * is an SVG that could not be parsed/sanitized, so the caller can
     * reject the upload.
     */
    public function sanitizeInPlace(UploadedFile $file): bool
    {
        if (! $this->isSvg($file)) {
            return true;
        }

        $path = $file->getRealPath();

        if ($path === false) {
            return false;
        }

        $original = file_get_contents($path);

        if ($original === false) {
            return false;
        }

        $sanitizer = new Sanitizer;
        $sanitizer->removeRemoteReferences(true);

        $clean = $sanitizer->sanitize($original);

        if ($clean === false || trim($clean) === '') {
            return false;
        }

        return file_put_contents($path, $clean) !== false;
    }

    public function isSvg(UploadedFile $file): bool
    {
        if (strtolower((string) $file->getClientOriginalExtension()) === 'svg') {
            return true;
        }

        return in_array($file->getMimeType(), ['image/svg+xml', 'image/svg'], true);
    }
}

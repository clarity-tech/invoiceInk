/**
 * Shared client-side validation for file attachments, used by the invoice form
 * and the email editor. Mirrors the server-side rules in InvoiceController
 * (mime allowlist, per-file/total size caps, max count).
 */

export const MAX_FILES = 10;
export const MAX_FILE_BYTES = 10 * 1024 * 1024;
export const MAX_TOTAL_BYTES = 25 * 1024 * 1024;

export const ALLOWED_EXTENSIONS = [
    'pdf',
    'png',
    'jpg',
    'jpeg',
    'gif',
    'webp',
    'doc',
    'docx',
    'xls',
    'xlsx',
    'ppt',
    'pptx',
    'csv',
    'txt',
] as const;

/** Human-readable byte size, e.g. `1.5 MB`. */
export function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

/** Lowercased extension without the dot, or '' when there is none. */
export function extensionOf(name: string): string {
    const dot = name.lastIndexOf('.');

    return dot >= 0 ? name.slice(dot + 1).toLowerCase() : '';
}

/** `accept` attribute value for a file input built from the allowlist. */
export function acceptAttribute(): string {
    return ALLOWED_EXTENSIONS.map((ext) => `.${ext}`).join(',');
}

/**
 * Validate incoming files against the already-selected list and return the ones
 * that may be appended, plus the first error encountered (empty string if none).
 * Pure — it never mutates the input list.
 */
export function collectValidFiles(
    existing: File[],
    incoming: FileList | File[] | null,
): { accepted: File[]; error: string } {
    if (!incoming || incoming.length === 0) {
        return { accepted: [], error: '' };
    }

    const accepted: File[] = [];
    let error = '';
    let runningTotal = existing.reduce((sum, f) => sum + f.size, 0);

    for (const file of Array.from(incoming)) {
        if (existing.length + accepted.length >= MAX_FILES) {
            error = `You may attach at most ${MAX_FILES} files.`;
            break;
        }

        if (
            !(ALLOWED_EXTENSIONS as readonly string[]).includes(
                extensionOf(file.name),
            )
        ) {
            error = `Unsupported file type: ${file.name}`;
            continue;
        }

        if (file.size > MAX_FILE_BYTES) {
            error = `${file.name} exceeds the 10MB per-file limit.`;
            continue;
        }

        if (runningTotal + file.size > MAX_TOTAL_BYTES) {
            error = 'Total attachments would exceed the 25MB limit.';
            break;
        }

        accepted.push(file);
        runningTotal += file.size;
    }

    return { accepted, error };
}

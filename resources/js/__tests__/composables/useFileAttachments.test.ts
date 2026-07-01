import { describe, it, expect } from 'vitest';
import {
    ALLOWED_EXTENSIONS,
    MAX_FILES,
    acceptAttribute,
    collectValidFiles,
    extensionOf,
    formatBytes,
} from '@/composables/useFileAttachments';

/** Build a File with a controllable byte size (jsdom ignores content length). */
function makeFile(name: string, size = 100, type = 'application/pdf'): File {
    const file = new File(['x'], name, { type });
    Object.defineProperty(file, 'size', { value: size });
    return file;
}

describe('useFileAttachments', () => {
    describe('formatBytes', () => {
        it('formats bytes, KB and MB', () => {
            expect(formatBytes(512)).toBe('512 B');
            expect(formatBytes(2048)).toBe('2.0 KB');
            expect(formatBytes(5 * 1024 * 1024)).toBe('5.0 MB');
        });
    });

    describe('extensionOf', () => {
        it('returns the lowercased extension', () => {
            expect(extensionOf('Report.PDF')).toBe('pdf');
            expect(extensionOf('archive.tar.gz')).toBe('gz');
        });

        it('returns empty string when there is no extension', () => {
            expect(extensionOf('README')).toBe('');
        });
    });

    describe('acceptAttribute', () => {
        it('lists all allowed extensions with a leading dot', () => {
            expect(acceptAttribute()).toBe(
                ALLOWED_EXTENSIONS.map((e) => `.${e}`).join(','),
            );
        });
    });

    describe('collectValidFiles', () => {
        it('accepts allowed files', () => {
            const { accepted, error } = collectValidFiles(
                [],
                [makeFile('a.pdf'), makeFile('b.png', 100, 'image/png')],
            );
            expect(accepted).toHaveLength(2);
            expect(error).toBe('');
        });

        it('rejects unsupported types but keeps the valid ones', () => {
            const { accepted, error } = collectValidFiles(
                [],
                [makeFile('ok.pdf'), makeFile('bad.exe')],
            );
            expect(accepted.map((f) => f.name)).toEqual(['ok.pdf']);
            expect(error).toContain('Unsupported file type');
        });

        it('rejects a file over the 10MB per-file limit', () => {
            const { accepted, error } = collectValidFiles(
                [],
                [makeFile('big.pdf', 11 * 1024 * 1024)],
            );
            expect(accepted).toHaveLength(0);
            expect(error).toContain('per-file limit');
        });

        it('stops once the 25MB total would be exceeded', () => {
            const existing = [makeFile('a.pdf', 20 * 1024 * 1024)];
            const { accepted, error } = collectValidFiles(existing, [
                makeFile('b.pdf', 6 * 1024 * 1024),
            ]);
            expect(accepted).toHaveLength(0);
            expect(error).toContain('25MB');
        });

        it('enforces the max file count', () => {
            const existing = Array.from({ length: MAX_FILES }, (_, i) =>
                makeFile(`f${i}.pdf`),
            );
            const { accepted, error } = collectValidFiles(existing, [
                makeFile('extra.pdf'),
            ]);
            expect(accepted).toHaveLength(0);
            expect(error).toContain(`at most ${MAX_FILES}`);
        });

        it('returns nothing for empty input', () => {
            expect(collectValidFiles([], null)).toEqual({
                accepted: [],
                error: '',
            });
        });
    });
});

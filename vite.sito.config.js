// Build del sito di presentazione housekeephotel.com (pagine statiche) → sito/dist
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { globSync } from 'node:fs';
import path from 'node:path';

const root = path.resolve('sito/pagine');

export default defineConfig({
    root,
    publicDir: path.resolve('sito/pubblico'),
    plugins: [tailwindcss()],
    build: {
        outDir: path.resolve('sito/dist'),
        emptyOutDir: true,
        rollupOptions: {
            input: Object.fromEntries(globSync('**/index.html', { cwd: root }).map((f) => [f.replace(/\/?index\.html$/, '') || 'home', path.join(root, f)])),
        },
    },
});

import { defineConfig } from 'vite';

export default defineConfig({
  base: '/assets/vendor/office/',
  publicDir: false,
  build: {
    outDir: 'public/assets/vendor/office',
    emptyOutDir: true,
    rollupOptions: {
      input: 'resources/js/private-office-viewer.js',
      output: {
        entryFileNames: 'private-office-viewer.js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash][extname]',
      },
    },
  },
});

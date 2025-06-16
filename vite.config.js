import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    emptyOutDir: true,
    minify: false,
    sourcemap: true,
    rollupOptions: {
      output: {
        // Simplify chunking to avoid conflicts
        manualChunks: undefined, // Remove manual chunking for now
      },
    },
  },
  base: '/',
  assetsInclude: ['**/*.jpg', '**/*.png', '**/*.jpeg'],
  json: {
    namedExports: true,
    stringify: false,
  },
});
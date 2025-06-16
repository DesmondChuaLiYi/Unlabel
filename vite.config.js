import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import AutoImport from 'unplugin-auto-import/vite';

export default defineConfig({
  plugins: [
    vue(),
    AutoImport({
      imports: ['vue', 'vue-router'], // Auto-import Vue and Vue Router APIs
      eslintrc: {
        enabled: true, // Optional: for ESLint support
      },
    }),
  ],
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    emptyOutDir: true,
    minify: false,
    sourcemap: true,
  },
  base: '/',
  assetsInclude: ['**/*.jpg', '**/*.png', '**/*.jpeg'],
  json: {
    namedExports: true,
    stringify: false,
  },
});
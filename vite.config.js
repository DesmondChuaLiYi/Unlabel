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
  },
  base: '/',
  assetsInclude: ['**/*.jpg', '**/*.png', '**/*.jpeg'],
  json: {
    namedExports: true,
    stringify: false,
  },
});
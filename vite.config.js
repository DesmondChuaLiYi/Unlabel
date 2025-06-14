import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost/Project/',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, '/api'),
        configure: (proxy, _options) => {
          proxy.on('proxyReq', (proxyReq, req, _res) => {
            console.log(`Proxying: ${req.url} -> ${proxyReq.path}`);
          });
          proxy.on('error', (err, _req, _res) => {
            console.error('Proxy Error:', err);
          });
        }
      }
    }
  }
});
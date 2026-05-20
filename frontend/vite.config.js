import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  // ─── Producción: el build va dentro de public/ de Laravel ────────────────
  build: {
    outDir: '../backend/public',
    emptyOutDir: false,   // NO borrar index.php ni .htaccess de Laravel
  },
  // ─── Desarrollo: proxy a artisan serve ───────────────────────────────────
  server: {
    port: 5173,
    host: true,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
})

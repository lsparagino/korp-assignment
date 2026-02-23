import { fileURLToPath } from 'node:url'
import Vue from '@vitejs/plugin-vue'
import Vuetify, { transformAssetUrls } from 'vite-plugin-vuetify'
import { defineConfig } from 'vitest/config'

export default defineConfig({
  plugins: [
    Vue(),
    Vuetify({ autoImport: true }),
  ],
  test: {
    environment: 'jsdom',
    globals: true,
    root: fileURLToPath(new URL('./', import.meta.url)),
    setupFiles: ['./src/test/polyfills.ts'],
    exclude: ['e2e/**', 'node_modules/**'],
    server: {
      deps: {
        inline: ['vuetify'],
      },
    },
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('src', import.meta.url)),
      '/images': fileURLToPath(new URL('public/images', import.meta.url)),
      'virtual:generated-layouts': fileURLToPath(new URL('src/test/stubs/generated-layouts.ts', import.meta.url)),
      'vue-router/auto-routes': fileURLToPath(new URL('src/test/stubs/auto-routes.ts', import.meta.url)),
    },
  },
})

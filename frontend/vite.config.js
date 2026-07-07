import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
    plugins: [vue()],
    base: '/dist/',
    build: {
        outDir: '../public/dist',
        emptyOutDir: true,
    },
    server: {
        proxy: {
            '/api': {
                target: 'https://slim.loc',
                changeOrigin: true,
                secure: false,
            }
        }
    }
})

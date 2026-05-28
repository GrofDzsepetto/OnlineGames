import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react-swc'

export default defineConfig({
  plugins: [react()],
  server: {
    port: 5174,        // Ezzel fixálod az 5174-es portot a fejlesztéshez
    strictPort: true,  // Opcionális: Ha az 5174 foglalt, hibát dob ahelyett, hogy elindulna az 5175-ön
    open: true         // Opcionális: Automatikusan megnyitja a böngészőt induláskor
  }
})
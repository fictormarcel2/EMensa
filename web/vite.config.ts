import { defineConfig } from "vitest/config";
import react from "@vitejs/plugin-react";
export default defineConfig({
  plugins: [react()],
  server: { proxy: { "/api": "http://localhost:8081" } },
  test: {
    environment: "jsdom",
    globals: true,
    maxWorkers: 1,
    testTimeout: 15000,
  },
});

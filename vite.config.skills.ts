import fs from 'fs';
import path from 'path';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { defineConfig, type Plugin } from 'vite';

function skillsOfflineHtmlPlugin(): Plugin {
  const outDir = path.resolve(__dirname, 'php_wamp_project/skills_graph');
  return {
    name: 'skills-offline-html-plugin',
    transformIndexHtml(html) {
      return html.replace(
        '</head>',
        '    <link rel="stylesheet" href="../assets/fonts/fonts.css" />\n  </head>'
      );
    },
    closeBundle() {
      const builtHtml = path.join(outDir, 'skills_embed.html');
      const targetHtml = path.join(outDir, 'index.html');
      if (fs.existsSync(builtHtml)) {
        fs.renameSync(builtHtml, targetHtml);
      }
    },
  };
}

export default defineConfig({
  base: './',
  plugins: [react(), tailwindcss(), skillsOfflineHtmlPlugin()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, '.'),
    },
  },
  build: {
    outDir: 'php_wamp_project/skills_graph',
    emptyOutDir: true,
    rollupOptions: {
      input: path.resolve(__dirname, 'skills_embed.html'),
    },
  },
});

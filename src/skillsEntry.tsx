import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { SkillsGraphWorkspace } from './components/SkillsGraphWorkspace';
import './index.css';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 15_000,
      refetchOnWindowFocus: false,
    },
  },
});

const urlParams =
  typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
const isEmbed = urlParams?.get('embed') === '1';
const tokenParam = urlParams?.get('token') || 'php-cookie-session';

function SkillsStandaloneApp() {
  return (
    <div className="h-screen w-screen overflow-hidden flex flex-col bg-[#141414] text-slate-100">
      {!isEmbed && (
        <header className="h-12 shrink-0 bg-[#1a1a1a] border-b border-[#2e2e2e] px-4 flex items-center justify-between">
          <div className="flex items-center gap-2.5 min-w-0">
            <span className="w-2.5 h-2.5 rounded-full bg-[#7f77dd] shrink-0" />
            <h1 className="text-xs sm:text-sm font-bold text-slate-100 truncate">
              نقشه تعاملی مهارت‌ها و علایق دانشجو — سامانه صمد
            </h1>
          </div>
          <a
            href="../dashboard.php?tab=festivals"
            className="px-3 py-1.5 rounded-lg bg-[#282828] hover:bg-[#333333] border border-[#3d3d3d] text-xs font-semibold text-slate-200 transition-colors shrink-0"
          >
            بازگشت به میزکار دانشجو
          </a>
        </header>
      )}
      <div className="flex-1 min-h-0 flex flex-col">
        <SkillsGraphWorkspace sessionToken={tokenParam} isEmbedded={isEmbed} />
      </div>
    </div>
  );
}

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <SkillsStandaloneApp />
    </QueryClientProvider>
  </StrictMode>
);

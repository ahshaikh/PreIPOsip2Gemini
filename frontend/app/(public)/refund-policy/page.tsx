'use client';

import React, { useEffect, useState } from 'react';
import { Menu, X } from 'lucide-react';
// Keep your existing imports exactly as they are
import RefundPolicyPart1 from '@/components/RefundPolicyPart1';
import RefundPolicyPart2 from '@/components/RefundPolicyPart2';
import RefundPolicyPart3 from '@/components/RefundPolicyPart3';
import RefundPolicyPart4 from '@/components/RefundPolicyPart4';
import RefundPolicyPart5 from '@/components/RefundPolicyPart5';
import RefundPolicyPart6 from '@/components/RefundPolicyPart6';
import RefundPolicyPart7 from '@/components/RefundPolicyPart7';
import RefundPolicyPart8 from '@/components/RefundPolicyPart8';
import RefundPolicyPart9 from '@/components/RefundPolicyPart9';
import RefundPolicyPart10 from '@/components/RefundPolicyPart10';
import RefundPolicyPart11 from '@/components/RefundPolicyPart11';
import RefundPolicyPart12 from '@/components/RefundPolicyPart12';
import RefundPolicyPart13 from '@/components/RefundPolicyPart13';

// 1. Define the TOC Structure (Article-based)
const TOC_ITEMS = [
  { label: '1. Title & Commencement', targetId: 'article-1' },
  { label: '2. Definitions', targetId: 'article-2' },
  { label: '3. Regulatory Framework', targetId: 'article-3' },
  { label: '4. Scope of Eligibility', targetId: 'article-4' },
  { label: '5. Transaction Provisions', targetId: 'article-5' },
  { label: '6. Temporal Limitations', targetId: 'article-6' },
  { label: '7. Request Procedures', targetId: 'article-7' },
  { label: '8. Verification Protocols', targetId: 'article-8' },
  { label: '9. Refund Calculation', targetId: 'article-9' },
  { label: '10. High-Value Refunds', targetId: 'article-10' },
  { label: '11. Processing Framework', targetId: 'article-11' },
  { label: '12. Disbursement Modes', targetId: 'article-12' },
  { label: '13. Failed Protocols', targetId: 'article-13' },
  { label: '14. Special Scenarios', targetId: 'article-14' },
  { label: '15. Exceptions', targetId: 'article-15' },
  { label: '16. Force Majeure', targetId: 'article-16' },
  { label: '17. Liability Limitations', targetId: 'article-17' },
  { label: '18. Indemnification', targetId: 'article-18' },
  { label: '19. Risk Disclosures', targetId: 'article-19' },
  { label: '20. Grievance Redressal', targetId: 'article-20' },
  { label: '21. External Disputes', targetId: 'article-21' },
  { label: '22. Arbitration', targetId: 'article-22' },
  { label: '23. Jurisdiction', targetId: 'article-23' },
  { label: '24. Amendments', targetId: 'article-24' },
  { label: '25. Entire Agreement', targetId: 'article-25' },
  { label: '26. Severability', targetId: 'article-26' },
  { label: '27. Waiver', targetId: 'article-27' },
  { label: '28. Assignment', targetId: 'article-28' },
  { label: '29. Notices', targetId: 'article-29' },
  { label: '30. Force Majeure (Ext)', targetId: 'article-30' },
  { label: '31. Miscellaneous', targetId: 'article-31' },
  { label: '32. Representations', targetId: 'article-32' },
  { label: '33. Covenants', targetId: 'article-33' },
  { label: '34. Compliance', targetId: 'article-34' },
  { label: '35. Data Protection', targetId: 'article-35' },
  { label: '36. AML & CFT', targetId: 'article-36' },
  { label: '37. SEBI Compliance', targetId: 'article-37' },
  { label: '38. Closing Clauses', targetId: 'article-38' },
  { label: '39. Contact Info', targetId: 'article-39' },
  { label: '40. Schedules I-V', targetId: 'article-40' },
  { label: '41. Acknowledgment', targetId: 'article-41' },
  { label: '42. Final Provisions', targetId: 'article-42' },
];

export default function RefundPolicyPage() {
  const [isTocOpen, setIsTocOpen] = useState(false);
  const [activeId, setActiveId] = useState('');

  // 2. Combined Logic: Inject IDs AND Setup ScrollSpy
  useEffect(() => {
    // --- STEP A: DYNAMIC ID INJECTION ---
    // This scans the DOM for <h3> tags containing "ARTICLE X" and assigns id="article-X"
    const allHeaders = document.querySelectorAll('h3');
    const targets: Element[] = [];

    allHeaders.forEach((header) => {
      const text = header.textContent || '';
      // Regex to match "ARTICLE" followed by a number (e.g., "ARTICLE 1", "ARTICLE 15")
      const match = text.match(/ARTICLE\s+(\d+)/i);
      
      if (match && match[1]) {
        const articleNumber = match[1];
        const newId = `article-${articleNumber}`;
        
        // Assign the ID to the header itself
        header.id = newId;
        
        // Add a CSS class for scroll margin (so the header isn't hidden behind the navbar)
        header.classList.add('scroll-mt-32'); 
        
        targets.push(header);
      }
    });

    // --- STEP B: SCROLL SPY OBSERVER ---
    // Now that IDs exist, we observe them
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            setActiveId(entry.target.id);
          }
        });
      },
      { 
        rootMargin: '-10% 0px -80% 0px', // Trigger when header is near top of screen
        threshold: 0 
      }
    );

    targets.forEach((target) => observer.observe(target));

    return () => targets.forEach((target) => observer.unobserve(target));
  }, []);

  // 3. Smooth Scroll Handler
  const scrollToTarget = (e: React.MouseEvent<HTMLAnchorElement>, id: string) => {
    e.preventDefault();
    const element = document.getElementById(id);
    if (element) {
      const headerOffset = 120;
      const elementPosition = element.getBoundingClientRect().top;
      const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
      
      window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
      if (window.innerWidth <= 1280) setIsTocOpen(false);
    }
  };

  return (
    <div className="policy-wrapper min-h-screen font-sans transition-colors duration-300">
      <style jsx global>{`
        :root {
          --bg-primary: #ffffff; --bg-secondary: #f8fafc; --bg-tertiary: #f1f5f9; --bg-elevated: #ffffff; --bg-hover: #e2e8f0;
          --text-primary: #0f172a; --text-secondary: #475569; --text-muted: #64748b;
          --accent-primary: #4f46e5; --accent-secondary: #6366f1; --accent-gold: #d97706;
          --border-color: #e2e8f0;
          --font-display: 'Playfair Display', serif; --font-body: 'Source Sans 3', sans-serif; --font-mono: 'JetBrains Mono', monospace;
        }
        :root[class~="dark"] {
          --bg-primary: #0a0a0f; --bg-secondary: #111116; --bg-tertiary: #1a1a23; --bg-elevated: #1c1c26; --bg-hover: #2d2d3b;
          --text-primary: #f1f5f9; --text-secondary: #94a3b8; --text-muted: #64748b;
          --accent-primary: #818cf8; --accent-secondary: #6366f1; --accent-gold: #fbbf24;
          --border-color: #2a2a35;
        }
        body, .policy-wrapper { background-color: var(--bg-primary); color: var(--text-primary); }
        .pp-layout { display: flex; max-width: 1440px; margin: 0 auto; padding: 120px 24px 60px; gap: 48px; position: relative; }
        
        /* Scroll Margin Utility (Injected by Script) */
        .scroll-mt-32 { scroll-margin-top: 8rem; }

        /* Sidebar */
        .toc-sidebar { width: 320px; flex-shrink: 0; position: sticky; top: 100px; height: calc(100vh - 140px); overflow-y: auto; border-right: 1px solid var(--border-color); padding-right: 20px; background: var(--bg-primary); transition: transform 0.3s ease; }
        .toc-title { font-family: var(--font-display); font-size: 14px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color); }
        .toc-link { display: block; padding: 6px 12px; color: var(--text-secondary); border-radius: 4px; font-size: 12px; font-weight: 500; text-decoration: none; transition: all 0.1s ease; margin-bottom: 1px; border-left: 2px solid transparent; }
        .toc-link:hover { background: var(--bg-tertiary); color: var(--text-primary); }
        .toc-link.active { background: rgba(99, 102, 241, 0.08); color: var(--accent-primary); border-left: 2px solid var(--accent-primary); font-weight: 600; }

        /* Main Content */
        .main-content { flex: 1; min-width: 0; }
        .content-wrapper { max-width: 850px; margin: 0 auto 0 0; }
        
        /* Mobile Button */
        .toc-toggle { display: none; position: fixed; bottom: 24px; right: 24px; width: 50px; height: 50px; background: var(--accent-primary); border-radius: 50%; color: white; border: none; align-items: center; justify-content: center; box-shadow: 0 4px 20px rgba(0,0,0,0.2); z-index: 1001; }
        @media (max-width: 1280px) {
          .pp-layout { flex-direction: column; padding: 100px 20px 60px; }
          .toc-sidebar { position: fixed; top: 0; left: 0; width: 300px; height: 100vh; z-index: 1000; padding: 20px; transform: translateX(-100%); box-shadow: 10px 0 30px rgba(0,0,0,0.5); }
          .toc-sidebar.open { transform: translateX(0); }
          .toc-toggle { display: flex; }
        }
        
        /* Shared Styles */
        .bg-slate-50 { background-color: var(--bg-secondary); border-color: var(--border-color); }
        .bg-slate-100 { background-color: var(--bg-tertiary); }
        .bg-white { background-color: var(--bg-primary); }
        .text-slate-900, .text-slate-800, .text-slate-700 { color: var(--text-primary); }
        .text-slate-600 { color: var(--text-secondary); }
        .border-slate-200 { border-color: var(--border-color); }
        .bg-indigo-50 { background-color: rgba(99, 102, 241, 0.1); border-color: rgba(99, 102, 241, 0.2); }
        .text-indigo-900, .text-indigo-800, .text-indigo-700, .text-indigo-600, .lucide { color: var(--accent-primary); }
        .legal-list li::before { color: var(--accent-primary); }
      `}</style>

      {/* Mobile Menu Toggle */}
      <button className="toc-toggle" onClick={() => setIsTocOpen(!isTocOpen)}>
        {isTocOpen ? <X size={24} /> : <Menu size={24} />}
      </button>

      <div className="pp-layout">
        {/* Sidebar TOC */}
        <aside className={`toc-sidebar ${isTocOpen ? 'open' : ''}`}>
          <div className="toc-title">Table of Contents</div>
          <nav>
            <ul style={{ listStyle: 'none', padding: 0 }}>
              {TOC_ITEMS.map((item, index) => (
                <li key={index}>
                  <a 
                    href={`#${item.targetId}`} 
                    onClick={(e) => scrollToTarget(e, item.targetId)}
                    className={`toc-link ${activeId === item.targetId ? 'active' : ''}`}
                  >
                    {item.label}
                  </a>
                </li>
              ))}
            </ul>
          </nav>
        </aside>

        {/* Main Content */}
        <main className="main-content">
          <div className="content-wrapper">
            <header className="mb-16 border-b border-gray-200 dark:border-gray-800 pb-10">
              <div className="flex flex-wrap gap-3 mb-6">
                <span className="inline-block px-4 py-1.5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300 text-xs font-bold uppercase tracking-wider">
                  Refund Policy
                </span>
                <span className="inline-block px-4 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">
                  Tier-1 Institutional Grade
                </span>
              </div>
              <h1 style={{ fontFamily: 'var(--font-display)', fontSize: '42px', fontWeight: '700', marginBottom: '24px', lineHeight: '1.2' }}>
                Refund Policy of PreIPOSIP.com
              </h1>
              <p className="text-lg text-slate-600 dark:text-slate-400 leading-relaxed max-w-3xl">
                A comprehensive legal framework governing refunds, cancellations, and return of consideration for Pre-IPO securities transactions, advisory services, and platform engagements.
              </p>
            </header>

            {/* Content Parts - No changes needed to these files */}
            <div className="space-y-16">
              <RefundPolicyPart1 />
              <RefundPolicyPart2 />
              <RefundPolicyPart3 />
              <RefundPolicyPart4 />
              <RefundPolicyPart5 />
              <RefundPolicyPart6 />
              <RefundPolicyPart7 />
              <RefundPolicyPart8 />
              <RefundPolicyPart9 />
              <RefundPolicyPart10 />
              <RefundPolicyPart11 />
              <RefundPolicyPart12 />
              <RefundPolicyPart13 />
            </div>

            <footer className="mt-20 pt-10 border-t border-slate-200 dark:border-slate-800">
              <p className="text-sm text-slate-500 dark:text-slate-400">© [Year] [Legal Entity Name]. All rights reserved.</p>
            </footer>
          </div>
        </main>
      </div>
    </div>
  );
}
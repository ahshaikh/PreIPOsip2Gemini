'use client';

import React, { useEffect, useState } from 'react';
import { Menu, X } from 'lucide-react';
import CookiePolicyPart1 from '@/components/CookiePolicyPart1';
import CookiePolicyPart2 from '@/components/CookiePolicyPart2';
import CookiePolicyPart3 from '@/components/CookiePolicyPart3';
import CookiePolicyPart4 from '@/components/CookiePolicyPart4';
import CookiePolicyPart5 from '@/components/CookiePolicyPart5';
import CookiePolicyPart6 from '@/components/CookiePolicyPart6';
import CookiePolicyPart7 from '@/components/CookiePolicyPart7';
import CookiePolicyPart8 from '@/components/CookiePolicyPart8';
import CookiePolicyPart9 from '@/components/CookiePolicyPart9';
import CookiePolicyPart10 from '@/components/CookiePolicyPart10';
import CookiePolicyPart11 from '@/components/CookiePolicyPart11';
import CookiePolicyPart12 from '@/components/CookiePolicyPart12';
import CookiePolicyPart13 from '@/components/CookiePolicyPart13';
import CookiePolicyPart14 from '@/components/CookiePolicyPart14';
import CookiePolicyPart15 from '@/components/CookiePolicyPart15';

// TOC Mapping for Hierarchical Navigation
const TOC_ITEMS = [
  { 
    id: 'part-1', 
    label: '1. PRELIMINARY PROVISIONS',
    subs: [
      { id: 'point-1-1', label: '1.1 Document Identification' },
      { id: 'point-1-2', label: '1.2 Legal Nature' },
      { id: 'point-1-3', label: '1.3 Regulatory Framework' },
      { id: 'point-1-4', label: '1.4 Definitions' },
      { id: 'point-1-5', label: '1.5 Interpretation' },
      { id: 'point-1-6', label: '1.6 Scope' },
    ]
  },
  { 
    id: 'part-2', 
    label: '2. TYPOLOGY OF COOKIES',
    subs: [
      { id: 'point-2-1', label: '2.1 Taxonomy' },
      { id: 'point-2-2', label: '2.2 Strictly Necessary' },
      { id: 'point-2-3', label: '2.3 Functional' },
      { id: 'point-2-4', label: '2.4 Performance/Analytics' },
      { id: 'point-2-5', label: '2.5 Targeting/Ads' },
      { id: 'point-2-6', label: '2.6 Social Media' },
      { id: 'point-2-7', label: '2.7 KYC & AML' },
      { id: 'point-2-8', label: '2.8 Retention Framework' },
    ]
  },
  { 
    id: 'part-3', 
    label: '3. PURPOSES & LEGAL BASES',
    subs: [
      { id: 'point-3-1', label: '3.1 Purpose Specification' },
      { id: 'point-3-2', label: '3.2 Primary Purposes' },
      { id: 'point-3-3', label: '3.3 Legal Bases' },
      { id: 'point-3-4', label: '3.4 Legitimate Interest' },
      { id: 'point-3-5', label: '3.5 Consent Mechanisms' },
      { id: 'point-3-6', label: '3.6 SEBI Integration' },
      { id: 'point-3-7', label: '3.7 Policy Interaction' },
    ]
  },
  { 
    id: 'part-4', 
    label: '4. USER RIGHTS & REMEDIES',
    subs: [
      { id: 'point-4-1', label: '4.1 Rights Framework' },
      { id: 'point-4-2', label: '4.2 Right to Information' },
      { id: 'point-4-3', label: '4.3 Right of Access' },
      { id: 'point-4-4', label: '4.4 Right to Rectification' },
      { id: 'point-4-5', label: '4.5 Right to Erasure' },
      { id: 'point-4-6', label: '4.6 Data Portability' },
      { id: 'point-4-7', label: '4.7 Right to Object' },
      { id: 'point-4-8', label: '4.8 Restrict Processing' },
      { id: 'point-4-9', label: '4.9 Withdraw Consent' },
      { id: 'point-4-10', label: '4.10 Automated Decisions' },
      { id: 'point-4-11', label: '4.11 Grievance Redressal' },
      { id: 'point-4-12', label: '4.12 International Rights' },
    ]
  },
  {
    id: 'part-5',
    label: '5. THIRD-PARTY GOVERNANCE',
    subs: [
      { id: 'point-5-1', label: '5.1 Ecosystem Framework' },
      { id: 'point-5-2', label: '5.2 Third-Party Registry' },
      { id: 'point-5-3', label: '5.3 DPA Framework' },
      { id: 'point-5-4', label: '5.4 Sub-Processors' },
      { id: 'point-5-5', label: '5.5 Cross-Border Transfer' },
      { id: 'point-5-6', label: '5.6 Provider Obligations' },
      { id: 'point-5-7', label: '5.7 User Control' },
      { id: 'point-5-8', label: '5.8 Accountability' },
      { id: 'point-5-9', label: '5.9 Termination' },
    ]
  },
  {
    id: 'part-6',
    label: '6. SECURITY MEASURES',
    subs: [
      { id: 'point-6-1', label: '6.1 Statutory Obligations' },
      { id: 'point-6-2', label: '6.2 Security Architecture' },
      { id: 'point-6-3', label: '6.3 Access Control' },
      { id: 'point-6-4', label: '6.4 Logging & Monitoring' },
      { id: 'point-6-7', label: '6.7 Breach Response' },
      { id: 'point-6-8', label: '6.8 Business Continuity' },
    ]
  },
  {
    id: 'part-7',
    label: '7. RETENTION & DELETION',
    subs: [
      { id: 'point-7-1', label: '7.1 Retention Principles' },
      { id: 'point-7-2', label: '7.2 Retention Schedule' },
      { id: 'point-7-3', label: '7.3 Automated Deletion' },
      { id: 'point-7-4', label: '7.4 User-Initiated Deletion' },
      { id: 'point-7-5', label: '7.5 Data Minimization' },
      { id: 'point-7-6', label: '7.6 Schedule Review' },
      { id: 'point-7-7', label: '7.7 Special Considerations' },
    ]
  },
  {
    id: 'part-8',
    label: '8. AMENDMENTS & CONTROL',
    subs: [
      { id: 'point-8-1', label: '8.1 Governance' },
      { id: 'point-8-2', label: '8.2 Classification' },
      { id: 'point-8-3', label: '8.3 Notification' },
      { id: 'point-8-4', label: '8.4 Version Control' },
      { id: 'point-8-5', label: '8.5 Acceptance' },
      { id: 'point-8-6', label: '8.6 Templates' },
      { id: 'point-8-7', label: '8.7 Disputes' },
    ]
  },
  {
    id: 'part-9',
    label: '9. CHILDREN\'S PRIVACY',
    subs: [
      { id: 'point-9-1', label: '9.1 Prohibition' },
      { id: 'point-9-2', label: '9.2 Age Verification' },
      { id: 'point-9-3', label: '9.3 Collection Ban' },
      { id: 'point-9-4', label: '9.4 Inadvertent Collection' },
      { id: 'point-9-6', label: '9.6 Young Adults' },
      { id: 'point-9-8', label: '9.8 Disclaimers' },
    ]
  },
  {
    id: 'part-10-end',
    label: '10-16. GENERAL PROVISIONS',
    subs: [
      { id: 'section-10', label: '10. Governing Law' },
      { id: 'section-11', label: '11. Dispute Resolution' },
      { id: 'section-12', label: '12. Severability' },
      { id: 'section-13', label: '13. Contact Info' },
      { id: 'section-14', label: '14. Miscellaneous' },
      { id: 'section-15', label: '15. History' },
      { id: 'section-16', label: '16. Acknowledgment' },
    ]
  }
];

export default function CookiePolicyPage() {
  const [isTocOpen, setIsTocOpen] = useState(false);
  const [activeId, setActiveId] = useState('');

  // Scroll Spy
  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            setActiveId(entry.target.id);
          }
        });
      },
      { rootMargin: '-10% 0px -80% 0px', threshold: 0 }
    );

    // Observe both Parts and specific Points
    const targets = document.querySelectorAll('section[id], div[id^="point-"], div[id^="section-"]');
    targets.forEach((target) => observer.observe(target));

    return () => targets.forEach((target) => observer.unobserve(target));
  }, []);

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
        
        /* Sidebar */
        .toc-sidebar { width: 340px; flex-shrink: 0; position: sticky; top: 100px; height: calc(100vh - 140px); overflow-y: auto; border-right: 1px solid var(--border-color); padding-right: 20px; background: var(--bg-primary); transition: transform 0.3s ease; }
        .toc-title { font-family: var(--font-display); font-size: 14px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color); }
        .toc-link { display: block; padding: 8px 12px; color: var(--text-secondary); border-radius: 4px; font-size: 13px; font-weight: 600; text-decoration: none; transition: all 0.1s ease; margin-top: 12px; margin-bottom: 4px; }
        .toc-link:hover { background: var(--bg-tertiary); color: var(--text-primary); }
        
        .toc-sub-link { display: block; padding: 4px 12px 4px 24px; color: var(--text-muted); font-size: 12px; font-weight: 400; text-decoration: none; border-left: 2px solid transparent; transition: all 0.1s ease; }
        .toc-sub-link:hover { color: var(--text-primary); border-left-color: var(--border-color); }
        .toc-sub-link.active-sub { color: var(--accent-primary); border-left-color: var(--accent-primary); background: rgba(99, 102, 241, 0.05); }

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

        /* Shared Component Styles */
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

      <button className="toc-toggle" onClick={() => setIsTocOpen(!isTocOpen)}>
        {isTocOpen ? <X size={24} /> : <Menu size={24} />}
      </button>

      <div className="pp-layout">
        <aside className={`toc-sidebar ${isTocOpen ? 'open' : ''}`}>
          <div className="toc-title">Table of Contents</div>
          <nav>
            <ul style={{ listStyle: 'none', padding: 0 }}>
              {TOC_ITEMS.map((section) => (
                <li key={section.id}>
                  <a 
                    href={`#${section.id}`} 
                    onClick={(e) => scrollToTarget(e, section.id)}
                    className="toc-link"
                  >
                    {section.label}
                  </a>
                  {section.subs && (
                    <ul style={{ listStyle: 'none', padding: 0 }}>
                      {section.subs.map((sub) => (
                        <li key={sub.id}>
                          <a 
                            href={`#${sub.id}`}
                            onClick={(e) => scrollToTarget(e, sub.id)}
                            className={`toc-sub-link ${activeId === sub.id ? 'active-sub' : ''}`}
                          >
                            {sub.label}
                          </a>
                        </li>
                      ))}
                    </ul>
                  )}
                </li>
              ))}
            </ul>
          </nav>
        </aside>

        <main className="main-content">
          <div className="content-wrapper">
            <header className="mb-16 border-b border-gray-200 dark:border-gray-800 pb-10">
              <div className="flex flex-wrap gap-3 mb-6">
                <span className="inline-block px-4 py-1.5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300 text-xs font-bold uppercase tracking-wider">
                  Cookie Policy
                </span>
                <span className="inline-block px-4 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">
                  Tier-1 Institutional Grade
                </span>
              </div>
              <h1 style={{ fontFamily: 'var(--font-display)', fontSize: '42px', fontWeight: '700', marginBottom: '24px', lineHeight: '1.2' }}>
                Cookie Policy of PreIPOSIP.com
              </h1>
              <p className="text-lg text-slate-600 dark:text-slate-400 leading-relaxed max-w-3xl">
                This Cookie Policy constitutes a legally binding agreement governing the deployment of tracking technologies on PreIPOSIP.com.
              </p>
            </header>

            <div className="space-y-16">
              <CookiePolicyPart1 />
              <CookiePolicyPart2 />
              <CookiePolicyPart3 />
              <CookiePolicyPart4 />
              <CookiePolicyPart5 />
              <CookiePolicyPart6 />
              <CookiePolicyPart7 />
              <CookiePolicyPart8 />
              <CookiePolicyPart9 />
              <CookiePolicyPart10 />
              <CookiePolicyPart11 />
              <CookiePolicyPart12 />
              <CookiePolicyPart13 />
              <CookiePolicyPart14 />
              <CookiePolicyPart15 />
            </div>

            <footer className="mt-20 pt-10 border-t border-slate-200 dark:border-slate-800">
              <p className="text-sm text-slate-500 dark:text-slate-400">© [Year] [Company Name]. All rights reserved.</p>
            </footer>
          </div>
        </main>
      </div>
    </div>
  );
}
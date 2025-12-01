'use client';

import React, { useEffect, useState } from 'react';
import Link from 'next/link';
import { Menu, X } from 'lucide-react';

export default function PrivacyPolicyPage() {
  const [isTocOpen, setIsTocOpen] = useState(false);
  const [activeSection, setActiveSection] = useState('');

  // Scroll Spy Logic
  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            setActiveSection(entry.target.id);
          }
        });
      },
      {
        rootMargin: '-20% 0px -60% 0px',
        threshold: 0,
      }
    );

    const sections = document.querySelectorAll('section[id]');
    sections.forEach((section) => observer.observe(section));

    return () => sections.forEach((section) => observer.unobserve(section));
  }, []);

  // Smooth Scroll Handler
  const scrollToSection = (e: React.MouseEvent<HTMLAnchorElement>, id: string) => {
    e.preventDefault();
    const element = document.getElementById(id);
    if (element) {
      // Adjust offset based on your global header height (approx 80px)
      const headerOffset = 100;
      const elementPosition = element.getBoundingClientRect().top;
      const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

      window.scrollTo({
        top: offsetPosition,
        behavior: 'smooth',
      });
      
      window.history.pushState(null, '', `#${id}`);
      
      if (window.innerWidth <= 1200) {
        setIsTocOpen(false);
      }
    }
  };

  return (
    <div className="privacy-policy-wrapper">
      <style jsx global>{`
        :root {
          /* --- Light Mode Defaults (New) --- */
          --bg-primary: #ffffff;
          --bg-secondary: #f8fafc; /* slate-50 */
          --bg-tertiary: #f1f5f9;  /* slate-100 */
          --bg-elevated: #ffffff;
          --bg-hover: #e2e8f0;     /* slate-200 */
          
          --text-primary: #0f172a;   /* slate-900 */
          --text-secondary: #475569; /* slate-600 */
          --text-muted: #64748b;     /* slate-500 */
          --text-accent: #4f46e5;    /* indigo-600 */
          
          --accent-primary: #4f46e5;   /* indigo-600 */
          --accent-secondary: #6366f1; /* indigo-500 */
          --accent-gold: #d97706;      /* amber-600 */
          --accent-emerald: #059669;   /* emerald-600 */
          --accent-rose: #e11d48;      /* rose-600 */
          
          --border-color: #e2e8f0; /* slate-200 */
          --border-light: #cbd5e1; /* slate-300 */
          
          --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
          --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
          --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
          
          /* Layout Constants */
          --radius-sm: 6px;
          --radius-md: 10px;
          --radius-lg: 16px;
          --toc-width: 300px;
          --header-height: 70px;
          
          --font-display: 'Playfair Display', Georgia, serif;
          --font-body: 'Source Sans 3', -apple-system, BlinkMacSystemFont, sans-serif;
          --font-mono: 'JetBrains Mono', 'Fira Code', monospace;
        }

        /* --- Dark Mode Overrides (Your Original Colors) --- */
        :root[class~="dark"] {
          --bg-primary: #0a0a0f;
          --bg-secondary: #12121a;
          --bg-tertiary: #1a1a25;
          --bg-elevated: #22222f;
          --bg-hover: #2a2a3a;
          
          --text-primary: #f0f0f5;
          --text-secondary: #a0a0b0;
          --text-muted: #6a6a7a;
          --text-accent: #7c9aff;
          
          --accent-primary: #6366f1;
          --accent-secondary: #818cf8;
          --accent-gold: #f59e0b;
          --accent-emerald: #10b981;
          --accent-rose: #f43f5e;
          
          --border-color: #2a2a3a;
          --border-light: #3a3a4a;
          
          --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.4);
          --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.5);
          --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.6);
        }

        .privacy-policy-wrapper {
          font-family: var(--font-body);
          font-size: 16px;
          line-height: 1.75;
          color: var(--text-primary);
          background: var(--bg-primary);
          /* Use transition for smooth theme switching */
          transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Layout Container */
        .pp-layout {
          display: flex;
          max-width: 1440px;
          margin: 0 auto;
          padding: 120px 24px 60px;
          gap: 48px;
          position: relative;
        }

        /* Sticky Sidebar for Desktop */
        .toc-sidebar {
          width: var(--toc-width);
          flex-shrink: 0;
          position: sticky; 
          top: 100px; 
          height: calc(100vh - 140px);
          overflow-y: auto;
          border-right: 1px solid var(--border-color);
          padding-right: 20px;
          scrollbar-width: thin;
          transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .toc-header {
          padding-bottom: 16px;
          border-bottom: 1px solid var(--border-color);
          margin-bottom: 16px;
        }

        .toc-title {
          font-family: 'Playfair Display', serif;
          font-size: 14px;
          font-weight: 600;
          color: var(--text-muted);
          text-transform: uppercase;
          letter-spacing: 1.5px;
        }

        .toc-list {
          list-style: none;
          padding: 0;
          margin: 0;
        }

        .toc-link {
          display: flex;
          align-items: flex-start;
          gap: 10px;
          padding: 8px 10px;
          text-decoration: none;
          color: var(--text-secondary);
          border-radius: 6px;
          font-size: 14px;
          font-weight: 500;
          transition: all 0.2s ease;
          line-height: 1.4;
          cursor: pointer;
        }

        .toc-link:hover {
          background: var(--bg-hover);
          color: var(--text-primary);
        }

        .toc-link.active {
          background: rgba(99, 102, 241, 0.15);
          color: var(--accent-secondary);
        }

        .toc-number {
          font-family: 'JetBrains Mono', monospace;
          font-size: 12px;
          color: var(--accent-primary);
          min-width: 24px;
        }

        .toc-sub-list {
          list-style: none;
          margin-left: 42px;
          margin-top: 4px;
        }

        .toc-sub-link {
          display: block;
          padding: 4px 10px;
          text-decoration: none;
          color: var(--text-muted);
          font-size: 13px;
          border-left: 2px solid var(--border-color);
          transition: all 0.2s ease;
          cursor: pointer;
        }

        .toc-sub-link:hover {
          color: var(--text-secondary);
          border-left-color: var(--accent-primary);
        }

        /* Main Content Area */
        .main-content {
          flex: 1;
          min-width: 0; 
        }

        .content-wrapper {
          max-width: 850px;
          margin: 0 auto 0 0;
        }

        /* Typography & Elements */
        .doc-type {
          display: inline-block;
          background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
          color: white;
          padding: 6px 16px;
          border-radius: 20px;
          font-size: 12px;
          font-weight: 600;
          text-transform: uppercase;
          letter-spacing: 1px;
          margin-bottom: 20px;
        }

        h1.doc-title {
          font-family: 'Playfair Display', serif;
          font-size: 48px;
          font-weight: 700;
          color: var(--text-primary);
          margin-bottom: 20px;
          line-height: 1.2;
        }

        .doc-description {
          font-size: 20px;
          color: var(--text-secondary);
          line-height: 1.6;
          max-width: 720px;
          padding: 20px 24px;
          background: var(--bg-secondary);
          border-left: 4px solid var(--accent-primary);
          border-radius: 0 10px 10px 0;
          transition: background-color 0.3s ease;
        }

        .doc-meta {
          display: flex;
          flex-wrap: wrap;
          gap: 24px;
          margin-top: 28px;
          padding-top: 20px;
          border-top: 1px solid var(--border-color);
        }

        .meta-item {
          display: flex;
          align-items: center;
          gap: 8px;
          font-size: 14px;
          color: var(--text-secondary);
        }

        .meta-label { color: var(--text-muted); }
        .meta-value { font-weight: 600; color: var(--text-primary); }

        /* Sections */
        section { margin-bottom: 56px; scroll-margin-top: 120px; }
        
        .section-header {
          display: flex;
          align-items: flex-start;
          gap: 16px;
          margin-bottom: 24px;
          padding-bottom: 16px;
          border-bottom: 1px solid var(--border-color);
        }

        .section-number {
          font-family: 'JetBrains Mono', monospace;
          font-size: 14px;
          font-weight: 600;
          color: var(--accent-primary);
          background: rgba(99, 102, 241, 0.1);
          padding: 6px 12px;
          border-radius: 6px;
        }

        h2.section-title {
          font-family: 'Playfair Display', serif;
          font-size: 28px;
          font-weight: 600;
          color: var(--text-primary);
          margin: 0;
        }

        h3.subsection-title {
          font-size: 20px;
          font-weight: 600;
          color: var(--text-primary);
          margin: 32px 0 16px;
          display: flex;
          align-items: center;
          gap: 12px;
        }

        .subsection-number {
          font-family: 'JetBrains Mono', monospace;
          font-size: 13px;
          color: var(--text-muted);
        }

        p { margin-bottom: 16px; color: var(--text-secondary); }

        /* Definitions Box */
        .definitions-box {
          background: var(--bg-secondary);
          border: 1px solid var(--border-color);
          border-radius: 16px;
          padding: 32px;
          margin: 24px 0;
          transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .definitions-title {
          font-family: 'Playfair Display', serif;
          font-size: 18px;
          font-weight: 600;
          color: var(--accent-gold);
          margin-bottom: 24px;
          display: flex;
          align-items: center;
          gap: 10px;
        }

        .definitions-title::before {
          content: "§";
          font-size: 24px;
          opacity: 0.6;
        }

        .definition-item {
          display: grid;
          grid-template-columns: 200px 1fr;
          gap: 16px;
          padding: 16px 0;
          border-bottom: 1px solid var(--border-color);
        }

        .definition-item:last-child { border-bottom: none; padding-bottom: 0; }
        .definition-item:first-of-type { padding-top: 0; }

        .definition-term {
          font-family: 'JetBrains Mono', monospace;
          font-size: 14px;
          font-weight: 600;
          color: var(--accent-secondary);
        }

        .definition-meaning {
          font-size: 15px;
          color: var(--text-secondary);
          line-height: 1.6;
        }

        /* Legal Reference Box */
        .legal-ref {
          background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(99, 102, 241, 0.1));
          border: 1px solid rgba(16, 185, 129, 0.3);
          border-radius: 10px;
          padding: 20px 24px;
          margin: 24px 0;
        }

        .legal-ref-header {
          display: flex;
          align-items: center;
          gap: 10px;
          font-size: 13px;
          font-weight: 600;
          color: var(--accent-emerald);
          text-transform: uppercase;
          letter-spacing: 0.5px;
          margin-bottom: 12px;
        }

        .legal-ref-content {
            font-size: 15px;
            color: var(--text-secondary);
        }

        /* Data Table */
        .data-table {
          width: 100%;
          border-collapse: collapse;
          margin: 24px 0;
          background: var(--bg-secondary);
          border-radius: 10px;
          overflow: hidden;
          border: 1px solid var(--border-color);
          transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .data-table th {
          background: var(--bg-tertiary);
          padding: 16px 20px;
          text-align: left;
          font-weight: 600;
          font-size: 13px;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          color: var(--text-muted);
          border-bottom: 1px solid var(--border-color);
        }

        .data-table td {
          padding: 16px 20px;
          border-bottom: 1px solid var(--border-color);
          font-size: 15px;
          color: var(--text-secondary);
          vertical-align: top;
        }

        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: var(--bg-hover); }

        /* Lists */
        .legal-list { margin: 16px 0; padding-left: 0; list-style: none; }
        .legal-list li {
          position: relative;
          padding: 12px 0 12px 36px;
          border-bottom: 1px solid var(--border-color);
          color: var(--text-secondary);
        }
        .legal-list li:last-child { border-bottom: none; }
        .legal-list li::before {
          content: counter(list-item, lower-alpha) ")";
          counter-increment: list-item;
          position: absolute;
          left: 0;
          font-family: 'JetBrains Mono', monospace;
          font-size: 14px;
          color: var(--accent-primary);
          font-weight: 600;
        }
        .legal-list { counter-reset: list-item; }
        
        .roman-list { counter-reset: roman-list; }
        .roman-list li::before {
          content: "(" counter(roman-list, lower-roman) ")";
          counter-increment: roman-list;
        }

        /* Callouts */
        .callout {
          padding: 20px 24px;
          border-radius: 10px;
          margin: 24px 0;
        }
        .callout-info { background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.3); }
        .callout-warning { background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); }
        .callout-important { background: rgba(244, 63, 94, 0.1); border: 1px solid rgba(244, 63, 94, 0.3); }
        
        .callout-header {
          display: flex;
          align-items: center;
          gap: 10px;
          font-weight: 600;
          margin-bottom: 10px;
          color: var(--text-primary);
        }

        .placeholder {
          background: rgba(245, 158, 11, 0.15);
          color: var(--accent-gold);
          padding: 2px 8px;
          border-radius: 4px;
          font-family: 'JetBrains Mono', monospace;
          font-size: 13px;
          font-weight: 500;
        }

        /* Responsive Mobile */
        .toc-toggle {
          display: none;
          position: fixed;
          bottom: 24px;
          right: 24px;
          width: 56px;
          height: 56px;
          background: var(--accent-primary);
          border: none;
          border-radius: 50%;
          color: white;
          font-size: 24px;
          cursor: pointer;
          box-shadow: 0 10px 25px rgba(0,0,0,0.6);
          z-index: 1001;
        }

        @media (max-width: 1200px) {
          .pp-layout {
            flex-direction: column;
            padding: 120px 24px 60px;
          }
          .toc-sidebar { 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 280px; 
            height: 100vh; 
            z-index: 1000;
            background: var(--bg-secondary);
            padding: 20px;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
          }
          .toc-sidebar.open { transform: translateX(0); }
          .main-content { width: 100%; }
          .toc-toggle { display: flex; align-items: center; justify-content: center; }
          .rights-grid { grid-template-columns: 1fr; }
          .definition-item { grid-template-columns: 1fr; gap: 8px; }
        }
      `}</style>

      {/* Layout Wrapper - No standalone header/footer */}
      <div className="pp-layout">
        
        {/* Sidebar TOC */}
        <aside className={`toc-sidebar ${isTocOpen ? 'open' : ''}`}>
          <div className="toc-header">
            <h2 className="toc-title">Table of Contents</h2>
          </div>
          <nav className="toc-list">
            <ul className="space-y-1">
              {[
                { id: 'definitions', label: 'Definitions & Interpretation' },
                { id: 'scope', label: 'Scope & Applicability' },
                { id: 'collection', label: 'Information We Collect', subs: [
                    { id: 'collection-personal', label: 'Personal Data' },
                    { id: 'collection-sensitive', label: 'Sensitive Personal Data' },
                    { id: 'collection-technical', label: 'Technical Data' },
                    { id: 'collection-financial', label: 'Financial Data' }
                ]},
                { id: 'purposes', label: 'Purposes of Processing' },
                { id: 'legal-basis', label: 'Legal Basis for Processing' },
                { id: 'kyc', label: 'KYC & Identity Verification' },
                { id: 'disclosure', label: 'Disclosure & Data Sharing' },
                { id: 'cross-border', label: 'Cross-Border Data Transfers' },
                { id: 'retention', label: 'Data Retention & Deletion' },
                { id: 'security', label: 'Data Security Measures' },
                { id: 'rights', label: 'Your Data Principal Rights' },
                { id: 'cookies', label: 'Cookies & Tracking' },
                { id: 'minors', label: "Children's Privacy" },
                { id: 'grievance', label: 'Grievance Redressal' },
                { id: 'changes', label: 'Changes to This Policy' },
                { id: 'governing-law', label: 'Governing Law' },
                { id: 'contact', label: 'Contact Information' }
              ].map((item, idx) => (
                <li key={item.id} className="toc-item">
                  <a 
                    href={`#${item.id}`} 
                    className={`toc-link ${activeSection === item.id ? 'active' : ''}`}
                    onClick={(e) => scrollToSection(e, item.id)}
                  >
                    <span className="toc-number">{idx + 1}.</span>
                    <span className="toc-text">{item.label}</span>
                  </a>
                  {item.subs && (
                    <ul className="toc-sub-list">
                      {item.subs.map(sub => (
                        <li key={sub.id}>
                          <a 
                            href={`#${sub.id}`}
                            className="toc-sub-link"
                            onClick={(e) => scrollToSection(e, sub.id)}
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

        {/* Mobile Toggle */}
        <button 
          className="toc-toggle"
          onClick={() => setIsTocOpen(!isTocOpen)}
        >
          {isTocOpen ? <X size={24} /> : <Menu size={24} />}
        </button>

        {/* Main Content */}
        <main className="main-content">
          <div className="content-wrapper">
            
            {/* Document Header */}
            <header className="doc-header">
              <span className="doc-type">Legal Document</span>
              <h1 className="doc-title">Privacy Policy</h1>
              <p className="doc-description">
                This Privacy Policy constitutes a legally binding agreement that governs how we collect, process, store, protect, and share your personal information when you use our pre-IPO investment platform, ensuring transparency and your control over your data in accordance with applicable Indian data protection laws.
              </p>
              
              <div className="doc-meta">
                <div className="meta-item">
                  <span className="meta-label">Effective Date:</span>
                  <span className="meta-value"><span className="placeholder">[EFFECTIVE_DATE]</span></span>
                </div>
                <div className="meta-item">
                  <span className="meta-label">Version:</span>
                  <span className="meta-value">1.0</span>
                </div>
                <div className="meta-item">
                  <span className="meta-label">Last Updated:</span>
                  <span className="meta-value"><span className="placeholder">[LAST_UPDATED_DATE]</span></span>
                </div>
                <div className="meta-item">
                  <span className="meta-label">Jurisdiction:</span>
                  <span className="meta-value">Republic of India</span>
                </div>
              </div>
            </header>

            {/* Section 1: Definitions */}
            <section className="section" id="definitions">
              <div className="section-header">
                <span className="section-number">1.</span>
                <h2 className="section-title">Definitions & Interpretation</h2>
              </div>
              <div className="section-content">
                <p>
                  In this Privacy Policy, unless the context otherwise requires, the following terms shall have the meanings ascribed to them below. Words importing the singular shall include the plural and vice versa, and words importing any gender shall include all genders.
                </p>
                
                <div className="definitions-box">
                  <h3 className="definitions-title">Defined Terms</h3>
                  
                  <div className="definition-item">
                    <span className="definition-term">"Company"</span>
                    <span className="definition-meaning">Means <span className="placeholder">[COMPANY_LEGAL_NAME]</span>, a company incorporated under the Companies Act, 2013, having its registered office at <span className="placeholder">[REGISTERED_ADDRESS]</span>, and includes its successors, assigns, and affiliates. Also referred to as "We," "Us," "Our," or "Platform."</span>
                  </div>
                  
                  <div className="definition-item">
                    <span className="definition-term">"Data Principal"</span>
                    <span className="definition-meaning">As defined under the Digital Personal Data Protection Act, 2023 ("DPDP Act"), means the individual to whom the Personal Data relates, being the natural person who uses or accesses the Platform. Also referred to as "You," "Your," "User," or "Investor."</span>
                  </div>
                  
                  <div className="definition-item">
                    <span className="definition-term">"Data Fiduciary"</span>
                    <span className="definition-meaning">As defined under Section 2(i) of the DPDP Act, 2023, means any person who alone or in conjunction with other persons determines the purpose and means of processing of Personal Data. The Company acts as a Data Fiduciary under this Policy.</span>
                  </div>
                  
                  <div className="definition-item">
                    <span className="definition-term">"Personal Data"</span>
                    <span className="definition-meaning">As defined under Section 2(t) of the DPDP Act, 2023, means any data about an individual who is identifiable by or in relation to such data, including but not limited to name, address, email, phone number, and financial identifiers.</span>
                  </div>
                  
                  <div className="definition-item">
                    <span className="definition-term">"Sensitive Personal Data or Information" (SPDI)</span>
                    <span className="definition-meaning">As defined under Rule 3 of the Information Technology (Reasonable Security Practices and Procedures and Sensitive Personal Data or Information) Rules, 2011, includes passwords, financial information, health data, biometric data, sexual orientation, and any data specified under Section 3 of the DPDP Act.</span>
                  </div>
                  
                  <div className="definition-item">
                    <span className="definition-term">"Processing"</span>
                    <span className="definition-meaning">As defined under Section 2(x) of the DPDP Act, 2023, means a wholly or partly automated operation or set of operations performed on digital Personal Data, including collection, recording, organization, structuring, storage, adaptation, retrieval, use, alignment, combination, indexing, sharing, disclosure, restriction, erasure, or destruction.</span>
                  </div>
                  
                  <div className="definition-item">
                    <span className="definition-term">"Consent"</span>
                    <span className="definition-meaning">As defined under Section 6 of the DPDP Act, 2023, means any freely given, specific, informed, unconditional, and unambiguous indication of the Data Principal's wishes by which they, by a clear affirmative action, signify agreement to the processing of Personal Data.</span>
                  </div>
                  
                  <div className="definition-item">
                    <span className="definition-term">"KYC"</span>
                    <span className="definition-meaning">Means Know Your Customer, the mandatory process of identity verification as prescribed under the Prevention of Money-Laundering Act, 2002, SEBI (KYC Registration Agency) Regulations, 2011, and RBI Master Direction – Know Your Customer (KYC) Direction, 2016.</span>
                  </div>
                  
                  <div className="definition-item">
                    <span className="definition-term">"Platform"</span>
                    <span className="definition-meaning">Means the website located at <span className="placeholder">[WEBSITE_URL]</span>, the mobile application "<span className="placeholder">[APP_NAME]</span>", and any associated services, APIs, tools, or features provided by the Company.</span>
                  </div>
                  
                  <div className="definition-item">
                    <span className="definition-term">"SIP"</span>
                    <span className="definition-meaning">Means Systematic Investment Plan, a structured method of investing fixed amounts at regular intervals in pre-IPO opportunities offered through the Platform.</span>
                  </div>
                  
                  <div className="definition-item">
                    <span className="definition-term">"Pre-IPO Securities"</span>
                    <span className="definition-meaning">Means shares, debentures, or other securities of companies that are not yet listed on any recognized stock exchange in India, offered to eligible investors prior to their Initial Public Offering.</span>
                  </div>
                  
                  <div className="definition-item">
                    <span className="definition-term">"Third Party"</span>
                    <span className="definition-meaning">Means any entity, person, or service provider other than the Data Principal and the Company, including but not limited to payment processors, KYC verification agencies, cloud service providers, and regulatory authorities.</span>
                  </div>
                  
                  <div className="definition-item">
                    <span className="definition-term">"Cookies"</span>
                    <span className="definition-meaning">Means small text files placed on your device by the Platform to store information about your preferences, session data, and browsing behavior to enhance user experience and enable essential functionalities.</span>
                  </div>
                  
                  <div className="definition-item">
                    <span className="definition-term">"Data Breach"</span>
                    <span className="definition-meaning">Means any unauthorized access, acquisition, use, disclosure, or destruction of Personal Data that compromises the confidentiality, integrity, or availability of such data, as contemplated under Section 8(6) of the DPDP Act, 2023.</span>
                  </div>
                </div>
                
                <div className="legal-ref">
                  <div className="legal-ref-header">
                    <span>⚖️</span>
                    <span>Legal Framework Reference</span>
                  </div>
                  <div className="legal-ref-content">
                    This Policy is drafted in compliance with the Digital Personal Data Protection Act, 2023 ("DPDP Act"), the Information Technology Act, 2000 ("IT Act"), the Information Technology (Reasonable Security Practices and Procedures and Sensitive Personal Data or Information) Rules, 2011 ("SPDI Rules"), SEBI regulations, and other applicable Indian laws governing data protection and privacy.
                  </div>
                </div>
              </div>
            </section>

            {/* Section 2: Scope */}
            <section className="section" id="scope">
              <div className="section-header">
                <span className="section-number">2.</span>
                <h2 className="section-title">Scope & Applicability</h2>
              </div>
              <div className="section-content">
                <p>
                  This Privacy Policy applies to all Personal Data and Sensitive Personal Data or Information collected, processed, stored, or transferred by the Company through the Platform, regardless of the medium or device used to access our services.
                </p>
                
                <div className="subsection" id="scope-territorial">
                  <h3 className="subsection-title">
                    <span className="subsection-number">2.1</span>
                    Territorial Scope
                  </h3>
                  <p>
                    This Policy applies to all users who are residents of India and access the Platform from within or outside the territory of India. For users accessing the Platform from jurisdictions outside India, local privacy laws may also apply, and such users are advised to review applicable regulations in their jurisdiction.
                  </p>
                </div>
                
                <div className="subsection" id="scope-persons">
                  <h3 className="subsection-title">
                    <span className="subsection-number">2.2</span>
                    Persons Covered
                  </h3>
                  <p>This Policy applies to:</p>
                  <ul className="legal-list">
                    <li>Registered users who have created an account on the Platform;</li>
                    <li>Visitors who browse the Platform without registration;</li>
                    <li>Prospective investors who initiate but do not complete the registration process;</li>
                    <li>Referral partners and affiliates who participate in our referral program;</li>
                    <li>Authorized representatives of corporate or institutional investors;</li>
                    <li>Any third parties whose data is provided to us by users (with appropriate consent).</li>
                  </ul>
                </div>
                
                <div className="subsection" id="scope-exclusions">
                  <h3 className="subsection-title">
                    <span className="subsection-number">2.3</span>
                    Exclusions
                  </h3>
                  <p>This Policy does not apply to:</p>
                  <ul className="legal-list">
                    <li>Data that is anonymized or aggregated in a manner that prevents identification of individuals;</li>
                    <li>Data processed by third-party websites or services linked from our Platform, which are governed by their own privacy policies;</li>
                    <li>Data of employees, contractors, or service providers of the Company, which is governed by separate employment and contractor agreements;</li>
                    <li>Publicly available data from government registries or other public sources.</li>
                  </ul>
                </div>
              </div>
            </section>

            {/* Section 3: Information Collection */}
            <section className="section" id="collection">
              <div className="section-header">
                <span className="section-number">3.</span>
                <h2 className="section-title">Information We Collect</h2>
              </div>
              <div className="section-content">
                <p>
                  We collect information that is necessary for the provision of our investment services, compliance with regulatory requirements, and improvement of user experience. The categories of information collected are detailed below.
                </p>
                
                <div className="subsection" id="collection-personal">
                  <h3 className="subsection-title">
                    <span className="subsection-number">3.1</span>
                    Personal Data
                  </h3>
                  <table className="data-table">
                    <thead>
                      <tr>
                        <th>Data Category</th>
                        <th>Specific Data Elements</th>
                        <th>Collection Method</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>Identity Information</td>
                        <td>Full legal name, date of birth, gender, nationality, photograph, PAN, Aadhaar number (with consent), voter ID, passport number</td>
                        <td>Registration forms, KYC verification, DigiLocker</td>
                      </tr>
                      <tr>
                        <td>Contact Information</td>
                        <td>Email address, mobile number, residential address (current and permanent), emergency contact details</td>
                        <td>Registration forms, profile updates</td>
                      </tr>
                      <tr>
                        <td>Professional Information</td>
                        <td>Occupation, employer name, annual income, source of income, net worth declaration</td>
                        <td>Investment suitability assessment</td>
                      </tr>
                      <tr>
                        <td>Nominee Information</td>
                        <td>Nominee name, relationship, contact details, share allocation percentage</td>
                        <td>Nominee registration form</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                
                <div className="subsection" id="collection-sensitive">
                  <h3 className="subsection-title">
                    <span className="subsection-number">3.2</span>
                    Sensitive Personal Data or Information (SPDI)
                  </h3>
                  <div className="callout callout-important">
                    <div className="callout-header">
                      <span>⚠️</span>
                      <span>Explicit Consent Required</span>
                    </div>
                    <div className="callout-content">
                      Collection of SPDI is subject to your explicit, informed, and freely given consent as mandated under Rule 5(1) of the SPDI Rules, 2011 and Section 6 of the DPDP Act, 2023. You may withdraw consent at any time, subject to legal retention requirements.
                    </div>
                  </div>
                  <table className="data-table">
                    <thead>
                      <tr>
                        <th>SPDI Category</th>
                        <th>Purpose of Collection</th>
                        <th>Legal Basis</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>Financial Information</td>
                        <td>Bank account details, demat account details, payment instrument information for processing investments and payouts</td>
                        <td>Contractual necessity, Regulatory compliance (PMLA, SEBI)</td>
                      </tr>
                      <tr>
                        <td>Biometric Data</td>
                        <td>Aadhaar-based e-KYC, facial recognition for video verification (if applicable)</td>
                        <td>Explicit consent, Regulatory compliance (UIDAI, SEBI)</td>
                      </tr>
                      <tr>
                        <td>Authentication Credentials</td>
                        <td>Passwords (encrypted), security questions, OTP verification records, MFA tokens</td>
                        <td>Account security, Contractual necessity</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                
                <div className="subsection" id="collection-technical">
                  <h3 className="subsection-title">
                    <span className="subsection-number">3.3</span>
                    Technical & Usage Data
                  </h3>
                  <table className="data-table">
                    <thead>
                      <tr>
                        <th>Data Category</th>
                        <th>Specific Data Elements</th>
                        <th>Purpose</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>Device Information</td>
                        <td>Device type, operating system, browser type and version, screen resolution, device identifiers (IMEI for mobile), language settings</td>
                        <td>Platform optimization, Security verification</td>
                      </tr>
                      <tr>
                        <td>Log Data</td>
                        <td>IP address, access timestamps, pages visited, click patterns, referral URLs, session duration</td>
                        <td>Security monitoring, Analytics, Fraud prevention</td>
                      </tr>
                      <tr>
                        <td>Location Data</td>
                        <td>Approximate location derived from IP address, GPS data (with explicit consent for mobile app)</td>
                        <td>Regulatory compliance (location-based restrictions), Fraud prevention</td>
                      </tr>
                      <tr>
                        <td>Communication Records</td>
                        <td>Emails, chat logs, support tickets, call recordings (with consent), in-app messages</td>
                        <td>Customer support, Dispute resolution, Quality assurance</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                
                <div className="subsection" id="collection-financial">
                  <h3 className="subsection-title">
                    <span className="subsection-number">3.4</span>
                    Financial & Transactional Data
                  </h3>
                  <table className="data-table">
                    <thead>
                      <tr>
                        <th>Data Category</th>
                        <th>Specific Data Elements</th>
                        <th>Retention Period</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>Investment Records</td>
                        <td>SIP enrollment details, investment amounts, frequency, portfolio holdings, bonus allocations</td>
                        <td>Account lifetime + 8 years (SEBI requirement)</td>
                      </tr>
                      <tr>
                        <td>Payment Information</td>
                        <td>Transaction IDs, payment method, UPI IDs (masked), bank reference numbers, payment status</td>
                        <td>Account lifetime + 8 years</td>
                      </tr>
                      <tr>
                        <td>Tax Information</td>
                        <td>TDS deductions, Form 26AS reconciliation data, capital gains records</td>
                        <td>7 years from end of relevant assessment year</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </section>

            {/* Section 4: Purposes */}
            <section className="section" id="purposes">
              <div className="section-header">
                <span className="section-number">4.</span>
                <h2 className="section-title">Purposes of Processing</h2>
              </div>
              <div className="section-content">
                <p>
                  We process your Personal Data only for specific, explicit, and legitimate purposes. The following table provides a comprehensive overview of processing purposes and their legal justifications.
                </p>
                
                <table className="data-table">
                  <thead>
                    <tr>
                      <th>Purpose</th>
                      <th>Description</th>
                      <th>Legal Basis</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><strong>Account Creation & Management</strong></td>
                      <td>Creating your investor account, authenticating your identity, managing your profile, and providing access to Platform features</td>
                      <td>Contractual necessity (Section 7(a), DPDP Act)</td>
                    </tr>
                    <tr>
                      <td><strong>KYC & Regulatory Compliance</strong></td>
                      <td>Verifying your identity as mandated by SEBI, PMLA, and other regulatory requirements; conducting due diligence</td>
                      <td>Legal obligation (Section 7(c), DPDP Act); PMLA 2002; SEBI Regulations</td>
                    </tr>
                    <tr>
                      <td><strong>Investment Processing</strong></td>
                      <td>Processing your SIP investments, allocating pre-IPO shares, calculating and crediting bonuses, managing portfolio</td>
                      <td>Contractual necessity</td>
                    </tr>
                    <tr>
                      <td><strong>Payment Processing</strong></td>
                      <td>Processing payments via payment gateways, reconciling transactions, handling refunds and withdrawals</td>
                      <td>Contractual necessity</td>
                    </tr>
                    <tr>
                      <td><strong>Communication</strong></td>
                      <td>Sending transactional notifications, investment confirmations, regulatory disclosures, account alerts, and customer support responses</td>
                      <td>Contractual necessity; Legal obligation</td>
                    </tr>
                    <tr>
                      <td><strong>Marketing (with Consent)</strong></td>
                      <td>Sending promotional offers, investment opportunities, newsletters, and educational content</td>
                      <td>Consent (Section 6, DPDP Act)</td>
                    </tr>
                    <tr>
                      <td><strong>Fraud Prevention & Security</strong></td>
                      <td>Detecting and preventing fraudulent activities, unauthorized access, money laundering, and suspicious transactions</td>
                      <td>Legitimate interest; Legal obligation (PMLA)</td>
                    </tr>
                    <tr>
                      <td><strong>Analytics & Platform Improvement</strong></td>
                      <td>Analyzing usage patterns, improving user experience, developing new features, conducting research</td>
                      <td>Legitimate interest (with anonymization where possible)</td>
                    </tr>
                    <tr>
                      <td><strong>Legal Proceedings</strong></td>
                      <td>Establishing, exercising, or defending legal claims; responding to regulatory inquiries and court orders</td>
                      <td>Legal obligation; Legitimate interest</td>
                    </tr>
                    <tr>
                      <td><strong>Referral Program Administration</strong></td>
                      <td>Managing referral relationships, tracking referral bonuses, maintaining MLM tier structures</td>
                      <td>Contractual necessity</td>
                    </tr>
                  </tbody>
                </table>
                
                <div className="callout callout-info">
                  <div className="callout-header">
                    <span>ℹ️</span>
                    <span>Purpose Limitation Principle</span>
                  </div>
                  <div className="callout-content">
                    In accordance with Section 5 of the DPDP Act, 2023, we shall not process your Personal Data for any purpose other than those specified above without obtaining fresh consent, except where such processing is required by applicable law.
                  </div>
                </div>
              </div>
            </section>

            {/* Section 5: Legal Basis */}
            <section className="section" id="legal-basis">
              <div className="section-header">
                <span className="section-number">5.</span>
                <h2 className="section-title">Legal Basis for Processing</h2>
              </div>
              <div className="section-content">
                <p>
                  Under the DPDP Act, 2023 and existing Indian data protection framework, we rely on the following legal grounds for processing your Personal Data:
                </p>
                
                <div className="subsection">
                  <h3 className="subsection-title">
                    <span className="subsection-number">5.1</span>
                    Consent (Section 6, DPDP Act)
                  </h3>
                  <p>
                    For processing that requires explicit consent, including marketing communications, optional features, and collection of SPDI beyond regulatory requirements. Consent obtained shall be free, specific, informed, unconditional, and unambiguous, and may be withdrawn at any time through the mechanisms provided in this Policy.
                  </p>
                </div>
                
                <div className="subsection">
                  <h3 className="subsection-title">
                    <span className="subsection-number">5.2</span>
                    Certain Legitimate Uses (Section 7, DPDP Act)
                  </h3>
                  <p>Processing without consent is permitted in the following circumstances:</p>
                  <ul className="legal-list roman-list">
                    <li>For the performance of any function under any law, rule, or regulation, including KYC requirements under PMLA and SEBI regulations;</li>
                    <li>For compliance with any judgment, decree, or order of any court, tribunal, or regulatory authority;</li>
                    <li>For responding to a medical emergency involving a threat to your life or health;</li>
                    <li>For employment-related purposes, where applicable;</li>
                    <li>For the performance of the contract to which you are a party, including the investment services agreement.</li>
                  </ul>
                </div>
                
                <div className="legal-ref">
                  <div className="legal-ref-header">
                    <span>⚖️</span>
                    <span>Regulatory Obligations</span>
                  </div>
                  <div className="legal-ref-content">
                    <p><strong>Key regulatory frameworks requiring data processing:</strong></p>
                    <ul style={{ marginTop: '12px', marginLeft: '20px', color: 'var(--text-secondary)' }}>
                      <li style={{ marginBottom: '8px' }}>Prevention of Money-Laundering Act, 2002 (Customer Due Diligence, Record Keeping)</li>
                      <li style={{ marginBottom: '8px' }}>SEBI (KYC Registration Agency) Regulations, 2011</li>
                      <li style={{ marginBottom: '8px' }}>SEBI (Investment Advisers) Regulations, 2013</li>
                      <li style={{ marginBottom: '8px' }}>Income Tax Act, 1961 (TDS compliance, PAN verification)</li>
                      <li style={{ marginBottom: '8px' }}>Foreign Exchange Management Act, 1999 (for NRI investors)</li>
                      <li style={{ marginBottom: '8px' }}>Companies Act, 2013 (beneficial ownership disclosure)</li>
                    </ul>
                  </div>
                </div>
              </div>
            </section>

            {/* Section 6: KYC */}
            <section className="section" id="kyc">
              <div className="section-header">
                <span className="section-number">6.</span>
                <h2 className="section-title">KYC & Identity Verification</h2>
              </div>
              <div className="section-content">
                <p>
                  As a platform facilitating investments in securities, we are mandated to conduct comprehensive Know Your Customer (KYC) verification. This section details our KYC processes and data handling practices.
                </p>
                
                <div className="subsection">
                  <h3 className="subsection-title">
                    <span className="subsection-number">6.1</span>
                    DigiLocker Integration
                  </h3>
                  <p>
                    We offer integration with DigiLocker, a Government of India initiative, for seamless verification of identity documents. When you authorize access to DigiLocker:
                  </p>
                  <ul className="legal-list">
                    <li>We receive verified copies of documents you explicitly authorize (Aadhaar, PAN, Driving License, etc.);</li>
                    <li>Document authenticity is verified directly with issuing authorities;</li>
                    <li>We do not store your DigiLocker credentials;</li>
                    <li>You may revoke DigiLocker access at any time through your DigiLocker account settings.</li>
                  </ul>
                </div>
                
                <div className="subsection">
                  <h3 className="subsection-title">
                    <span className="subsection-number">6.2</span>
                    Aadhaar-Based e-KYC
                  </h3>
                  <div className="callout callout-warning">
                    <div className="callout-header">
                      <span>📋</span>
                      <span>Voluntary Nature of Aadhaar</span>
                    </div>
                    <div className="callout-content">
                      In compliance with the Supreme Court judgment in K.S. Puttaswamy v. Union of India (2018), provision of Aadhaar for KYC is voluntary. Alternative KYC methods using PAN, Passport, Voter ID, or Driving License are available.
                    </div>
                  </div>
                  <p>
                    If you opt for Aadhaar-based e-KYC, we use UIDAI-authorized APIs to verify your identity. We do not store your full Aadhaar number; only the last four digits and Virtual ID (if provided) are retained for reference.
                  </p>
                </div>
                
                <div className="subsection">
                  <h3 className="subsection-title">
                    <span className="subsection-number">6.3</span>
                    Video-Based Customer Identification Process (V-CIP)
                  </h3>
                  <p>
                    Where required or opted for, we may conduct Video-Based Customer Identification Process in accordance with RBI and SEBI guidelines. This involves:
                  </p>
                  <ul className="legal-list">
                    <li>Live video interaction with our authorized personnel;</li>
                    <li>Real-time verification of identity documents;</li>
                    <li>Capture of geo-coordinates and timestamp;</li>
                    <li>Recording of the video session (with explicit consent) for audit purposes.</li>
                  </ul>
                </div>
              </div>
            </section>

            {/* Section 7: Disclosure */}
            <section className="section" id="disclosure">
              <div className="section-header">
                <span className="section-number">7.</span>
                <h2 className="section-title">Disclosure & Data Sharing</h2>
              </div>
              <div className="section-content">
                <p>
                  We do not sell, rent, or trade your Personal Data to third parties for their marketing purposes. We may share your data only in the following circumstances and with the following categories of recipients:
                </p>
                
                <table className="data-table">
                  <thead>
                    <tr>
                      <th>Recipient Category</th>
                      <th>Purpose of Sharing</th>
                      <th>Data Shared</th>
                      <th>Safeguards</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><strong>Payment Processors</strong></td>
                      <td>Processing payments, refunds, withdrawals</td>
                      <td>Transaction details, payment instrument info (tokenized)</td>
                      <td>PCI-DSS certified; Data Processing Agreements</td>
                    </tr>
                    <tr>
                      <td><strong>KYC Service Providers</strong></td>
                      <td>Identity verification, document validation</td>
                      <td>Identity documents, biometric data (with consent)</td>
                      <td>SEBI/RBI registered; Contractual confidentiality</td>
                    </tr>
                    <tr>
                      <td><strong>Cloud Infrastructure Providers</strong></td>
                      <td>Hosting, storage, computing services</td>
                      <td>All Platform data (encrypted)</td>
                      <td>ISO 27001 certified; Data localization compliance</td>
                    </tr>
                    <tr>
                      <td><strong>Pre-IPO Issuer Companies</strong></td>
                      <td>Share allocation, cap table management</td>
                      <td>Investor name, demat details, holding information</td>
                      <td>Confidentiality agreements; Regulatory compliance</td>
                    </tr>
                    <tr>
                      <td><strong>Registrar & Transfer Agents</strong></td>
                      <td>Share transfers, corporate actions</td>
                      <td>Demat details, investor identification</td>
                      <td>SEBI registered; Regulatory oversight</td>
                    </tr>
                    <tr>
                      <td><strong>Regulatory Authorities</strong></td>
                      <td>Compliance with legal obligations</td>
                      <td>As required by law (may include full transaction history)</td>
                      <td>Statutory requirement; Judicial oversight</td>
                    </tr>
                    <tr>
                      <td><strong>Auditors & Legal Advisors</strong></td>
                      <td>Audit, legal compliance, dispute resolution</td>
                      <td>As necessary for professional services</td>
                      <td>Professional confidentiality; Privilege</td>
                    </tr>
                    <tr>
                      <td><strong>Analytics Providers</strong></td>
                      <td>Usage analytics, Platform improvement</td>
                      <td>Anonymized/aggregated usage data</td>
                      <td>Data anonymization; No PII shared</td>
                    </tr>
                  </tbody>
                </table>
                
                <div className="callout callout-important">
                  <div className="callout-header">
                    <span>🔒</span>
                    <span>Data Processor Requirements</span>
                  </div>
                  <div className="callout-content">
                    All third parties processing data on our behalf are bound by Data Processing Agreements that mandate: implementation of appropriate security measures; processing only as per our instructions; assistance with regulatory compliance; notification of data breaches; and data deletion upon termination of services.
                  </div>
                </div>
              </div>
            </section>

            {/* Section 8: Cross-Border */}
            <section className="section" id="cross-border">
              <div className="section-header">
                <span className="section-number">8.</span>
                <h2 className="section-title">Cross-Border Data Transfers</h2>
              </div>
              <div className="section-content">
                <p>
                  Your Personal Data is primarily stored and processed within the territory of India in compliance with data localization requirements. However, in certain circumstances, transfers to jurisdictions outside India may be necessary.
                </p>
                
                <div className="subsection">
                  <h3 className="subsection-title">
                    <span className="subsection-number">8.1</span>
                    Data Localization Compliance
                  </h3>
                  <p>
                    In accordance with RBI circulars on data localization (RBI/2017-18/153) and Section 16 of the DPDP Act, 2023, the following categories of data are stored exclusively on servers located in India:
                  </p>
                  <ul className="legal-list">
                    <li>Payment transaction data and records;</li>
                    <li>KYC information and identity documents;</li>
                    <li>Financial account information (bank accounts, demat accounts);</li>
                    <li>Investment transaction history and portfolio data.</li>
                  </ul>
                </div>
                
                <div className="subsection">
                  <h3 className="subsection-title">
                    <span className="subsection-number">8.2</span>
                    Permitted Transfers
                  </h3>
                  <p>
                    Transfer of Personal Data outside India may occur only to countries or territories notified by the Central Government under Section 16(1) of the DPDP Act, 2023, or under conditions prescribed therein. Such transfers shall be subject to:
                  </p>
                  <ul className="legal-list">
                    <li>Adequacy decisions by the Central Government;</li>
                    <li>Standard Contractual Clauses (when prescribed);</li>
                    <li>Binding Corporate Rules (for intra-group transfers);</li>
                    <li>Your explicit consent for specific transfer purposes.</li>
                  </ul>
                </div>
                
                <div className="legal-ref">
                  <div className="legal-ref-header">
                    <span>⚖️</span>
                    <span>Regulatory Framework</span>
                  </div>
                  <div className="legal-ref-content">
                    Until specific rules under Section 16 of the DPDP Act are notified, cross-border transfers shall be governed by the provisions of the IT Act, 2000 and SPDI Rules, 2011, which permit transfers to countries with "same level of data protection" or with Data Principal's consent.
                  </div>
                </div>
              </div>
            </section>

            {/* Section 9: Retention */}
            <section className="section" id="retention">
              <div className="section-header">
                <span className="section-number">9.</span>
                <h2 className="section-title">Data Retention & Deletion</h2>
              </div>
              <div className="section-content">
                <p>
                  We retain your Personal Data only for as long as necessary to fulfill the purposes for which it was collected, comply with legal obligations, resolve disputes, and enforce our agreements.
                </p>
                
                <table className="data-table">
                  <thead>
                    <tr>
                      <th>Data Category</th>
                      <th>Retention Period</th>
                      <th>Legal Basis for Retention</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>KYC Records</td>
                      <td>5 years after cessation of business relationship</td>
                      <td>PMLA Act, 2002; Rule 10(1)</td>
                    </tr>
                    <tr>
                      <td>Transaction Records</td>
                      <td>8 years from date of transaction</td>
                      <td>SEBI regulations; Companies Act, 2013</td>
                    </tr>
                    <tr>
                      <td>Tax Records (TDS, Capital Gains)</td>
                      <td>7 years from end of relevant assessment year</td>
                      <td>Income Tax Act, 1961; Section 149</td>
                    </tr>
                    <tr>
                      <td>Communication Records</td>
                      <td>3 years from date of communication</td>
                      <td>Legitimate interest; Dispute resolution</td>
                    </tr>
                    <tr>
                      <td>Log & Technical Data</td>
                      <td>2 years from date of collection</td>
                      <td>IT Act, 2000; Security requirements</td>
                    </tr>
                    <tr>
                      <td>Marketing Preferences</td>
                      <td>Until consent withdrawal + 30 days</td>
                      <td>Consent-based processing</td>
                    </tr>
                    <tr>
                      <td>Account Data (post-closure)</td>
                      <td>8 years from account closure</td>
                      <td>Regulatory requirements; Legal claims limitation</td>
                    </tr>
                  </tbody>
                </table>
                
                <div className="subsection">
                  <h3 className="subsection-title">
                    <span className="subsection-number">9.1</span>
                    Data Deletion Procedures
                  </h3>
                  <p>Upon expiry of the retention period or upon your valid erasure request (subject to legal retention requirements):</p>
                  <ul className="legal-list">
                    <li>Active databases: Data is securely deleted within 30 days;</li>
                    <li>Backup systems: Data is deleted within 90 days during routine backup rotation;</li>
                    <li>Paper records (if any): Securely shredded;</li>
                    <li>Data shared with processors: Deletion confirmation obtained from all processors.</li>
                  </ul>
                </div>
              </div>
            </section>

            {/* Section 10: Security */}
            <section className="section" id="security">
              <div className="section-header">
                <span className="section-number">10.</span>
                <h2 className="section-title">Data Security Measures</h2>
              </div>
              <div className="section-content">
                <p>
                  We implement reasonable security practices and procedures as mandated under Section 8 of the DPDP Act, 2023 and Rule 8 of the SPDI Rules, 2011, commensurate with the sensitivity of Personal Data handled.
                </p>
                
                <div className="subsection">
                  <h3 className="subsection-title">
                    <span className="subsection-number">10.1</span>
                    Technical Security Measures
                  </h3>
                  <ul className="legal-list">
                    <li><strong>Encryption:</strong> All Personal Data encrypted at rest (AES-256) and in transit (TLS 1.3);</li>
                    <li><strong>Access Controls:</strong> Role-based access control (RBAC) with principle of least privilege;</li>
                    <li><strong>Authentication:</strong> Multi-factor authentication (MFA) for all user accounts and administrative access;</li>
                    <li><strong>Network Security:</strong> Firewalls, intrusion detection/prevention systems (IDS/IPS), DDoS protection;</li>
                    <li><strong>Monitoring:</strong> 24/7 security monitoring, automated threat detection, security information and event management (SIEM);</li>
                    <li><strong>Vulnerability Management:</strong> Regular penetration testing, vulnerability assessments, timely patching.</li>
                  </ul>
                </div>
                
                <div className="subsection">
                  <h3 className="subsection-title">
                    <span className="subsection-number">10.2</span>
                    Organizational Security Measures
                  </h3>
                  <ul className="legal-list">
                    <li><strong>Security Policies:</strong> Comprehensive information security policy aligned with ISO 27001;</li>
                    <li><strong>Employee Training:</strong> Mandatory security awareness training for all personnel;</li>
                    <li><strong>Background Checks:</strong> Verification of employees with access to sensitive data;</li>
                    <li><strong>Incident Response:</strong> Documented incident response plan with defined escalation procedures;</li>
                    <li><strong>Business Continuity:</strong> Regular backups, disaster recovery procedures, geographic redundancy.</li>
                  </ul>
                </div>
                
                <div className="subsection">
                  <h3 className="subsection-title">
                    <span className="subsection-number">10.3</span>
                    Data Breach Notification
                  </h3>
                  <p>
                    In the event of a Personal Data Breach, we shall comply with Section 8(6) of the DPDP Act, 2023 and notify:
                  </p>
                  <ul className="legal-list">
                    <li>The Data Protection Board of India, in the form and manner prescribed;</li>
                    <li>Affected Data Principals, without undue delay, where the breach is likely to result in high risk to their rights and interests;</li>
                    <li>CERT-In, within 6 hours of becoming aware of the incident, as per CERT-In Directions dated 28.04.2022;</li>
                    <li>SEBI, if required under applicable securities regulations.</li>
                  </ul>
                </div>
              </div>
            </section>

            {/* Section 11: Rights */}
            <section className="section" id="rights">
              <div className="section-header">
                <span className="section-number">11.</span>
                <h2 className="section-title">Your Data Principal Rights</h2>
              </div>
              <div className="section-content">
                <p>
                  Under the DPDP Act, 2023 and other applicable laws, you have the following rights in relation to your Personal Data. These rights may be exercised subject to applicable legal exemptions.
                </p>
                
                <div className="rights-grid">
                  <div className="right-card">
                    <div className="right-title">
                      <div className="right-icon">📋</div>
                      Right of Access
                    </div>
                    <div className="right-desc">
                      Section 11(1) - Obtain confirmation of whether your Personal Data is being processed and access a summary of such data and processing activities.
                    </div>
                  </div>
                  
                  <div className="right-card">
                    <div className="right-title">
                      <div className="right-icon">✏️</div>
                      Right to Correction
                    </div>
                    <div className="right-desc">
                      Section 11(2) - Request correction of inaccurate or misleading Personal Data, and completion of incomplete data.
                    </div>
                  </div>
                  
                  <div className="right-card">
                    <div className="right-title">
                      <div className="right-icon">🗑️</div>
                      Right to Erasure
                    </div>
                    <div className="right-desc">
                      Section 11(3) - Request erasure of Personal Data that is no longer necessary for the purpose for which it was collected, subject to legal retention requirements.
                    </div>
                  </div>
                  
                  <div className="right-card">
                    <div className="right-title">
                      <div className="right-icon">⏸️</div>
                      Right to Withdraw Consent
                    </div>
                    <div className="right-desc">
                      Section 6(4) - Withdraw consent at any time with the same ease with which it was given. Withdrawal shall not affect lawfulness of processing before withdrawal.
                    </div>
                  </div>
                  
                  <div className="right-card">
                    <div className="right-title">
                      <div className="right-icon">👤</div>
                      Right to Nominate
                    </div>
                    <div className="right-desc">
                      Section 11(5) - Nominate an individual who may exercise your rights in the event of your death or incapacity.
                    </div>
                  </div>
                  
                  <div className="right-card">
                    <div className="right-title">
                      <div className="right-icon">⚖️</div>
                      Right to Grievance Redressal
                    </div>
                    <div className="right-desc">
                      Section 11(4) - Lodge complaints with our Grievance Officer and, if unsatisfied, escalate to the Data Protection Board of India.
                    </div>
                  </div>
                </div>
                
                <div className="subsection">
                  <h3 className="subsection-title">
                    <span className="subsection-number">11.1</span>
                    Exercising Your Rights
                  </h3>
                  <p>To exercise any of the above rights:</p>
                  <ul className="legal-list">
                    <li>Submit a request through your account settings on the Platform;</li>
                    <li>Email our Data Protection Officer at <span className="placeholder">[DPO_EMAIL]</span>;</li>
                    <li>Write to our Grievance Officer at the address provided in Section 14.</li>
                  </ul>
                  <p style={{ marginTop: '16px' }}>
                    We will verify your identity before processing any request. Requests will be responded to within <strong>30 days</strong> or such period as prescribed under applicable law. In complex cases, this period may be extended by an additional <strong>30 days</strong> with prior notice.
                  </p>
                </div>
                
                <div className="callout callout-info">
                  <div className="callout-header">
                    <span>ℹ️</span>
                    <span>Limitations</span>
                  </div>
                  <div className="callout-content">
                    Certain rights may be limited where processing is necessary for compliance with legal obligations, establishment or defense of legal claims, or protection of rights of other individuals. We will inform you of any limitations when responding to your request.
                  </div>
                </div>
              </div>
            </section>

            {/* Section 12: Cookies */}
            <section className="section" id="cookies">
              <div className="section-header">
                <span className="section-number">12.</span>
                <h2 className="section-title">Cookies & Tracking Technologies</h2>
              </div>
              <div className="section-content">
                <p>
                  We use cookies and similar tracking technologies to enhance your experience, analyze usage patterns, and provide personalized content. This section explains the types of cookies we use and your choices regarding them.
                </p>
                
                <table className="data-table">
                  <thead>
                    <tr>
                      <th>Cookie Type</th>
                      <th>Purpose</th>
                      <th>Duration</th>
                      <th>Consent Required</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><strong>Essential Cookies</strong></td>
                      <td>Authentication, security, session management, CSRF protection</td>
                      <td>Session to 24 hours</td>
                      <td>No (strictly necessary)</td>
                    </tr>
                    <tr>
                      <td><strong>Functional Cookies</strong></td>
                      <td>User preferences, language settings, accessibility options</td>
                      <td>1 year</td>
                      <td>No (legitimate interest)</td>
                    </tr>
                    <tr>
                      <td><strong>Analytics Cookies</strong></td>
                      <td>Usage analytics, performance monitoring, error tracking</td>
                      <td>2 years</td>
                      <td>Yes</td>
                    </tr>
                    <tr>
                      <td><strong>Marketing Cookies</strong></td>
                      <td>Personalized advertisements, conversion tracking, retargeting</td>
                      <td>90 days to 2 years</td>
                      <td>Yes</td>
                    </tr>
                  </tbody>
                </table>
                
                <div className="subsection">
                  <h3 className="subsection-title">
                    <span className="subsection-number">12.1</span>
                    Managing Cookie Preferences
                  </h3>
                  <p>You may manage your cookie preferences through:</p>
                  <ul className="legal-list">
                    <li>Our cookie consent banner displayed on first visit and accessible via "Cookie Settings" in the footer;</li>
                    <li>Your browser settings (note: disabling essential cookies may impair Platform functionality);</li>
                    <li>Opt-out mechanisms for specific analytics providers (Google Analytics opt-out browser add-on).</li>
                  </ul>
                </div>
              </div>
            </section>

            {/* Section 13: Children */}
            <section className="section" id="minors">
              <div className="section-header">
                <span className="section-number">13.</span>
                <h2 className="section-title">Children's Privacy</h2>
              </div>
              <div className="section-content">
                <p>
                  The Platform is not intended for use by individuals below the age of <strong>18 years</strong>. We do not knowingly collect Personal Data from children.
                </p>
                
                <div className="callout callout-important">
                  <div className="callout-header">
                    <span>🔒</span>
                    <span>Age Restriction</span>
                  </div>
                  <div className="callout-content">
                    <p>Investment services on this Platform are available only to individuals who have attained the age of majority (18 years) under the Indian Majority Act, 1875. In accordance with Section 9 of the DPDP Act, 2023, processing of Personal Data of children below 18 years requires verifiable parental consent.</p>
                  </div>
                </div>
                
                <p>
                  If we become aware that we have collected Personal Data from a child without appropriate consent, we shall take immediate steps to delete such data from our records. Parents or guardians who believe that their child has provided Personal Data to us without consent should contact our Grievance Officer immediately.
                </p>
              </div>
            </section>

            {/* Section 14: Grievance */}
            <section className="section" id="grievance">
              <div className="section-header">
                <span className="section-number">14.</span>
                <h2 className="section-title">Grievance Redressal</h2>
              </div>
              <div className="section-content">
                <p>
                  In accordance with Section 11(4) of the DPDP Act, 2023, Rule 5(9) of the SPDI Rules, 2011, and the Information Technology (Intermediary Guidelines and Digital Media Ethics Code) Rules, 2021, we have appointed a Grievance Officer to address your concerns regarding data protection and privacy.
                </p>
                
                <div className="contact-box">
                  <h3 className="contact-title">Grievance Officer Details</h3>
                  <div className="contact-item">
                    <span className="contact-label">Name:</span>
                    <span className="contact-value"><span className="placeholder">[GRIEVANCE_OFFICER_NAME]</span></span>
                  </div>
                  <div className="contact-item">
                    <span className="contact-label">Designation:</span>
                    <span className="contact-value"><span className="placeholder">[DESIGNATION]</span></span>
                  </div>
                  <div className="contact-item">
                    <span className="contact-label">Email:</span>
                    <span className="contact-value"><a href="mailto:[GRIEVANCE_EMAIL]"><span className="placeholder">[GRIEVANCE_EMAIL]</span></a></span>
                  </div>
                  <div className="contact-item">
                    <span className="contact-label">Phone:</span>
                    <span className="contact-value"><span className="placeholder">[GRIEVANCE_PHONE]</span></span>
                  </div>
                  <div className="contact-item">
                    <span className="contact-label">Address:</span>
                    <span className="contact-value"><span className="placeholder">[GRIEVANCE_ADDRESS]</span></span>
                  </div>
                  <div className="contact-item">
                    <span className="contact-label">Working Hours:</span>
                    <span className="contact-value">Monday to Friday, 10:00 AM to 6:00 PM IST (excluding public holidays)</span>
                  </div>
                </div>
                
                <div className="subsection">
                  <h3 className="subsection-title">
                    <span className="subsection-number">14.1</span>
                    Grievance Resolution Timeline
                  </h3>
                  <ul className="legal-list">
                    <li><strong>Acknowledgment:</strong> Within 24 hours of receipt of complaint;</li>
                    <li><strong>Initial Response:</strong> Within 15 days of receipt;</li>
                    <li><strong>Resolution:</strong> Within 30 days, or such extended period as communicated;</li>
                    <li><strong>Escalation:</strong> If unsatisfied, you may escalate to the Data Protection Board of India as per Section 13 of the DPDP Act, 2023.</li>
                  </ul>
                </div>
              </div>
            </section>

            {/* Section 15: Changes */}
            <section className="section" id="changes">
              <div className="section-header">
                <span className="section-number">15.</span>
                <h2 className="section-title">Changes to This Policy</h2>
              </div>
              <div className="section-content">
                <p>
                  We reserve the right to modify, amend, or update this Privacy Policy from time to time to reflect changes in our data practices, legal requirements, or business operations.
                </p>
                
                <div className="subsection">
                  <h3 className="subsection-title">
                    <span className="subsection-number">15.1</span>
                    Notification of Changes
                  </h3>
                  <ul className="legal-list">
                    <li><strong>Material Changes:</strong> We will notify you by email and/or prominent notice on the Platform at least 30 days before the effective date of significant changes that affect how we process your data;</li>
                    <li><strong>Minor Changes:</strong> Non-material updates (clarifications, formatting) may be made without advance notice;</li>
                    <li><strong>Version History:</strong> Previous versions of this Policy will be archived and available upon request;</li>
                    <li><strong>Continued Use:</strong> Your continued use of the Platform after the effective date of any changes constitutes acceptance of the updated Policy.</li>
                  </ul>
                </div>
                
                <div className="subsection">
                  <h3 className="subsection-title">
                    <span className="subsection-number">15.2</span>
                    Fresh Consent
                  </h3>
                  <p>
                    Where changes to processing activities require fresh consent under Section 6 of the DPDP Act, 2023, we shall seek such consent before implementing the changes. You will have the option to accept or decline such changes.
                  </p>
                </div>
              </div>
            </section>

            {/* Section 16: Governing Law */}
            <section className="section" id="governing-law">
              <div className="section-header">
                <span className="section-number">16.</span>
                <h2 className="section-title">Governing Law & Jurisdiction</h2>
              </div>
              <div className="section-content">
                <p>
                  This Privacy Policy shall be governed by and construed in accordance with the laws of the Republic of India, including but not limited to:
                </p>
                <ul className="legal-list">
                  <li>The Digital Personal Data Protection Act, 2023;</li>
                  <li>The Information Technology Act, 2000 and rules thereunder;</li>
                  <li>The Indian Contract Act, 1872;</li>
                  <li>Applicable SEBI regulations and RBI directions.</li>
                </ul>
                
                <div className="legal-ref">
                  <div className="legal-ref-header">
                    <span>⚖️</span>
                    <span>Exclusive Jurisdiction</span>
                  </div>
                  <div className="legal-ref-content">
                    Any disputes arising out of or in connection with this Privacy Policy shall be subject to the exclusive jurisdiction of the courts at <span className="placeholder">[JURISDICTION_CITY]</span>, India. This does not affect your statutory right to lodge complaints with the Data Protection Board of India.
                  </div>
                </div>
              </div>
            </section>

            {/* Section 17: Contact */}
            <section className="section" id="contact">
              <div className="section-header">
                <span className="section-number">17.</span>
                <h2 className="section-title">Contact Information</h2>
              </div>
              <div className="section-content">
                <p>
                  For any questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us through any of the following channels:
                </p>
                
                <div className="contact-box">
                  <h3 className="contact-title">Data Fiduciary Contact Details</h3>
                  <div className="contact-item">
                    <span className="contact-label">Company Name:</span>
                    <span className="contact-value"><span className="placeholder">[COMPANY_LEGAL_NAME]</span></span>
                  </div>
                  <div className="contact-item">
                    <span className="contact-label">CIN:</span>
                    <span className="contact-value"><span className="placeholder">[COMPANY_CIN]</span></span>
                  </div>
                  <div className="contact-item">
                    <span className="contact-label">Registered Address:</span>
                    <span className="contact-value"><span className="placeholder">[REGISTERED_ADDRESS]</span></span>
                  </div>
                  <div className="contact-item">
                    <span className="contact-label">Email (General):</span>
                    <span className="contact-value"><a href="mailto:[GENERAL_EMAIL]"><span className="placeholder">[GENERAL_EMAIL]</span></a></span>
                  </div>
                  <div className="contact-item">
                    <span className="contact-label">Email (Privacy):</span>
                    <span className="contact-value"><a href="mailto:[PRIVACY_EMAIL]"><span className="placeholder">[PRIVACY_EMAIL]</span></a></span>
                  </div>
                  <div className="contact-item">
                    <span className="contact-label">Phone:</span>
                    <span className="contact-value"><span className="placeholder">[CONTACT_PHONE]</span></span>
                  </div>
                  <div className="contact-item">
                    <span className="contact-label">Website:</span>
                    <span className="contact-value"><a href="[WEBSITE_URL]"><span className="placeholder">[WEBSITE_URL]</span></a></span>
                  </div>
                </div>
                
                <div className="callout callout-info" style={{ marginTop: '32px' }}>
                  <div className="callout-header">
                    <span>📧</span>
                    <span>Preferred Communication Channel</span>
                  </div>
                  <div className="callout-content">
                    For fastest response, we recommend using the privacy-specific email address for all data protection inquiries. Please include "Privacy Inquiry" in the subject line and provide your registered email/phone number for verification purposes.
                  </div>
                </div>
              </div>
            </section>

            {/* Footer */}
            <footer className="doc-footer">
              <p className="footer-text">
                © <span className="placeholder">[YEAR]</span> <span className="placeholder">[COMPANY_NAME]</span>. All rights reserved.
              </p>
              <p className="footer-text" style={{ marginTop: '8px' }}>
                Document ID: PP-v1.0-<span className="placeholder">[DATE_CODE]</span> | Classification: Public
              </p>

            </footer>

          </div>
        </main>
      </div>
    </div>
  );
}
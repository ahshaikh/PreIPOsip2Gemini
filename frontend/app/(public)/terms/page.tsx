'use client';

import React, { useEffect, useState } from 'react';
import Link from 'next/link';
import { Menu, X, AlertTriangle, FileText, Scale, ShieldAlert, Gavel, Clock, Landmark, FileCheck, Lock, AlertCircle, ChevronRight } from 'lucide-react';

export default function TermsOfServicePage() {
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
      { rootMargin: '-20% 0px -60% 0px', threshold: 0 }
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
      const headerOffset = 100;
      const elementPosition = element.getBoundingClientRect().top;
      const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

      window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
      window.history.pushState(null, '', `#${id}`);
      if (window.innerWidth <= 1200) setIsTocOpen(false);
    }
  };

  return (
    <div className="terms-wrapper">
      <style jsx global>{`
        :root {
          /* Light Mode Defaults */
          --bg-primary: #ffffff;
          --bg-secondary: #f8fafc;
          --bg-tertiary: #f1f5f9;
          --bg-hover: #e2e8f0;
          --text-primary: #0f172a;
          --text-secondary: #334155;
          --text-muted: #64748b;
          --accent-primary: #4f46e5;
          --accent-secondary: #6366f1;
          --accent-gold: #d97706;
          --accent-emerald: #059669;
          --accent-rose: #e11d48;
          --border-color: #e2e8f0;
          --toc-width: 340px;
          --radius-md: 8px;
          
          --font-display: 'Playfair Display', Georgia, serif;
          --font-body: 'Source Sans 3', sans-serif;
          --font-mono: 'JetBrains Mono', monospace;
        }

        /* Dark Mode Overrides */
        :root[class~="dark"] {
          --bg-primary: #0a0a0f;
          --bg-secondary: #12121a;
          --bg-tertiary: #1a1a25;
          --bg-hover: #2a2a3a;
          --text-primary: #f0f0f5;
          --text-secondary: #94a3b8;
          --text-muted: #64748b;
          --accent-primary: #6366f1;
          --accent-secondary: #818cf8;
          --accent-gold: #f59e0b;
          --accent-emerald: #10b981;
          --accent-rose: #f43f5e;
          --border-color: #2a2a3a;
        }

        .terms-wrapper {
          font-family: var(--font-body);
          color: var(--text-primary);
          background: var(--bg-primary);
          line-height: 1.8;
        }

        /* Layout Container */
        .terms-layout {
          display: flex;
          max-width: 1600px;
          margin: 0 auto;
          padding: 120px 24px 100px;
          gap: 64px;
          position: relative;
        }

        /* Sidebar */
        .toc-sidebar {
          width: var(--toc-width);
          flex-shrink: 0;
          position: sticky;
          top: 120px;
          height: calc(100vh - 160px);
          overflow-y: auto;
          border-right: 1px solid var(--border-color);
          padding-right: 24px;
          scrollbar-width: thin;
        }

        .toc-header {
          padding-bottom: 20px;
          border-bottom: 1px solid var(--border-color);
          margin-bottom: 20px;
        }

        .toc-title {
          font-family: var(--font-display);
          font-size: 16px;
          font-weight: 700;
          text-transform: uppercase;
          letter-spacing: 1.5px;
          color: var(--text-muted);
        }

        .toc-list { list-style: none; padding: 0; margin: 0; }
        
        .toc-link {
          display: flex;
          gap: 12px;
          padding: 10px 12px;
          text-decoration: none;
          color: var(--text-secondary);
          border-radius: 6px;
          font-size: 14px;
          font-weight: 500;
          transition: all 0.2s;
          line-height: 1.4;
          align-items: flex-start;
        }

        .toc-link:hover { background: var(--bg-hover); color: var(--text-primary); }
        .toc-link.active { background: rgba(99, 102, 241, 0.1); color: var(--accent-primary); border-left: 3px solid var(--accent-primary); font-weight: 600; }

        .toc-sub-list { margin-left: 28px; margin-top: 4px; border-left: 1px solid var(--border-color); }
        .toc-sub-link { display: block; padding: 6px 12px; color: var(--text-muted); font-size: 13px; text-decoration: none; transition: color 0.2s; }
        .toc-sub-link:hover { color: var(--text-primary); }

        /* Main Content */
        .main-content { flex: 1; min-width: 0; }
        .content-wrapper { max-width: 900px; }

        /* Doc Header */
        .doc-header { margin-bottom: 64px; padding-bottom: 40px; border-bottom: 1px solid var(--border-color); }
        
        /* Logo Styles */
        .doc-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
            text-decoration: none;
        }
        .doc-logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 24px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        .doc-logo-text {
            font-family: var(--font-display);
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        .doc-type { 
            background: rgba(99, 102, 241, 0.1);
            color: var(--accent-primary); padding: 6px 16px; border-radius: 20px; 
            font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
            border: 1px solid rgba(99, 102, 241, 0.2);
        }
        .doc-title { font-family: var(--font-display); font-size: 56px; font-weight: 800; margin: 24px 0; line-height: 1.1; letter-spacing: -0.02em; }
        .doc-intro { font-size: 18px; color: var(--text-secondary); border-left: 4px solid var(--accent-primary); padding: 24px 32px; background: var(--bg-secondary); border-radius: 0 12px 12px 0; }

        .doc-meta { display: flex; gap: 48px; margin-top: 40px; font-size: 14px; color: var(--text-secondary); border-top: 1px solid var(--border-color); padding-top: 24px; }
        .meta-label { color: var(--text-muted); margin-right: 8px; text-transform: uppercase; font-size: 11px; letter-spacing: 1px; font-weight: 600; }
        .meta-value { font-weight: 700; color: var(--text-primary); font-family: var(--font-mono); }

        /* Sections */
        .section { margin-bottom: 80px; scroll-margin-top: 120px; }
        .section-header { display: flex; gap: 20px; margin-bottom: 32px; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; align-items: flex-end; }
        .section-number { 
            font-family: var(--font-mono); font-size: 16px; font-weight: 700; color: var(--accent-primary); 
            background: rgba(99, 102, 241, 0.1); padding: 8px 16px; border-radius: 8px; height: fit-content;
        }
        .section-title { font-family: var(--font-display); font-size: 32px; font-weight: 700; color: var(--text-primary); margin: 0; line-height: 1.2; }

        /* Content Elements */
        h3 { font-size: 20px; font-weight: 700; margin: 40px 0 20px; color: var(--text-primary); display: flex; gap: 12px; align-items: center; border-left: 3px solid var(--accent-gold); padding-left: 16px; }
        h4 { font-size: 16px; font-weight: 700; margin: 24px 0 12px; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.5px; }
        p { margin-bottom: 20px; color: var(--text-secondary); text-align: justify; }
        
        /* Legal Lists */
        .legal-list { list-style: none; padding: 0; counter-reset: legal-counter; margin: 24px 0; }
        .legal-list li { position: relative; padding: 8px 0 8px 48px; color: var(--text-secondary); }
        .legal-list li::before {
            counter-increment: legal-counter;
            content: "(" counter(legal-counter, lower-alpha) ")";
            position: absolute; left: 0; font-family: var(--font-mono); font-size: 14px; color: var(--accent-primary); font-weight: 600;
        }
        
        .roman-list { counter-reset: roman-counter; }
        .roman-list li::before {
            counter-increment: roman-counter;
            content: "(" counter(roman-counter, lower-roman) ")";
        }

        /* Callouts */
        .callout { padding: 32px; border-radius: 12px; margin: 32px 0; border: 1px solid transparent; position: relative; overflow: hidden; }
        .callout-danger { background: rgba(244, 63, 94, 0.03); border-color: rgba(244, 63, 94, 0.2); }
        .callout-warning { background: rgba(245, 158, 11, 0.03); border-color: rgba(245, 158, 11, 0.2); }
        .callout-info { background: rgba(99, 102, 241, 0.03); border-color: rgba(99, 102, 241, 0.2); }
        
        .callout-header { display: flex; gap: 12px; align-items: center; font-weight: 700; margin-bottom: 16px; text-transform: uppercase; font-size: 14px; letter-spacing: 1px; }
        .callout-danger .callout-header { color: var(--accent-rose); }
        .callout-warning .callout-header { color: var(--accent-gold); }
        .callout-info .callout-header { color: var(--accent-primary); }

        /* Definitions Table */
        .def-grid { display: grid; gap: 1px; background: var(--border-color); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; margin: 32px 0; }
        .def-row { display: grid; grid-template-columns: 240px 1fr; background: var(--bg-secondary); }
        .def-term { padding: 20px 24px; font-family: var(--font-mono); font-size: 13px; color: var(--accent-secondary); background: var(--bg-tertiary); font-weight: 700; display: flex; align-items: center; }
        .def-desc { padding: 20px 24px; color: var(--text-secondary); font-size: 15px; line-height: 1.6; }

        /* Responsive */
        @media (max-width: 1200px) {
            .terms-layout { flex-direction: column; padding: 100px 24px; }
            .toc-sidebar { position: fixed; top: 0; left: 0; width: 320px; height: 100vh; background: var(--bg-secondary); z-index: 1000; transform: translateX(-100%); transition: transform 0.3s; padding: 32px; box-shadow: 10px 0 30px rgba(0,0,0,0.2); }
            .toc-sidebar.open { transform: translateX(0); }
            .main-content { width: 100%; }
            .def-row { grid-template-columns: 1fr; }
            .def-term { border-bottom: 1px solid var(--border-color); background: var(--bg-hover); }
            .doc-title { font-size: 36px; }
        }
      `}</style>

      <div className="terms-layout">
        {/* TOC Sidebar */}
        <aside className={`toc-sidebar ${isTocOpen ? 'open' : ''}`}>
          <div className="toc-header">
            <h2 className="toc-title">Table of Contents</h2>
          </div>
          <nav>
            <ul className="toc-list">
              {[
                { id: 'agreement-parties', label: '1. Parties & Recitals' },
                { id: 'definitions', label: '2. Definitions & Interpretation' },
                { id: 'acceptance', label: '3. Acceptance of Terms' },
                { id: 'eligibility', label: '4. Eligibility & Competency' },
                { id: 'account-registration', label: '5. Account Registration & KYC' },
                { id: 'service-scope', label: '6. Scope of Services & Role' },
                { id: 'investments', label: '7. Terms of Investment' },
                { id: 'sip-framework', label: '8. Systematic Investment Plan (SIP) Framework' },
                { id: 'payments-settlements', label: '9. Payments, Settlements & Refunds' },
                { id: 'fees-charges', label: '10. Fees, Charges & Taxes' },
                { id: 'risk-factors', label: '11. Comprehensive Risk Disclosures' },
                { id: 'user-obligations', label: '12. User Obligations & Conduct' },
                { id: 'privacy-data', label: '13. Data Protection & Privacy' },
                { id: 'ip-rights', label: '14. Intellectual Property Rights' },
                { id: 'confidentiality', label: '15. Confidentiality' },
                { id: 'indemnification', label: '16. Indemnification' },
                { id: 'limitation-liability', label: '17. Limitation of Liability' },
                { id: 'termination', label: '18. Term & Termination' },
                { id: 'dispute-resolution', label: '19. Governing Law & Dispute Resolution' },
                { id: 'force-majeure', label: '20. Force Majeure' },
                { id: 'miscellaneous', label: '21. Miscellaneous Provisions' },
                { id: 'contact', label: '22. Contact & Grievance Redressal' }
              ].map((item) => (
                <li key={item.id}>
                  <a 
                    href={`#${item.id}`} 
                    className={`toc-link ${activeSection === item.id ? 'active' : ''}`}
                    onClick={(e) => scrollToSection(e, item.id)}
                  >
                    <span>{item.label}</span>
                  </a>
                </li>
              ))}
            </ul>
          </nav>
        </aside>

        {/* Mobile Toggle */}
        <button 
          className="fixed bottom-8 right-8 z-50 w-16 h-16 bg-[var(--accent-primary)] text-white rounded-full flex items-center justify-center shadow-2xl lg:hidden transition-transform active:scale-90"
          onClick={() => setIsTocOpen(!isTocOpen)}
        >
          {isTocOpen ? <X size={28} /> : <Menu size={28} />}
        </button>

        {/* Main Content */}
        <main className="main-content">
          <div className="content-wrapper">
            
            {/* Header */}
            <header className="doc-header">
              {/* Logo added here */}
              <div className="mb-8">
                <Link href="/" className="doc-logo">
                    <div className="doc-logo-icon">P</div>
                    <span className="doc-logo-text">PreIPO SIP</span>
                </Link>
              </div>

              <span className="doc-type">Master Service Agreement</span>
              <h1 className="doc-title">Terms of Service</h1>
              <p className="doc-intro">
                This Master Service Agreement ("Agreement") is a legally binding contract governing your use of the PreIPO SIP Platform. It sets forth the terms under which you may access investment opportunities in unlisted securities, private equity, and other alternative assets.
              </p>
              
              <div className="doc-meta">
                <div><span className="meta-label">Last Revised:</span><span className="meta-value">October 25, 2025</span></div>
                <div><span className="meta-label">Effective From:</span><span className="meta-value">November 01, 2025</span></div>
                <div><span className="meta-label">Version:</span><span className="meta-value">2.4 (SEBI/PMLA Compliant)</span></div>
              </div>
            </header>

            {/* 1. Preamble */}
            <section id="agreement-parties" className="section">
              <div className="section-header">
                <span className="section-number">01</span>
                <h2 className="section-title">Parties & Recitals</h2>
              </div>
              <p>
                This <strong>MASTER SERVICE AGREEMENT</strong> (hereinafter referred to as the "Agreement" or "Terms") is made and entered into by and between:
              </p>
              <div className="callout callout-info">
                <div className="callout-header"><Landmark size={18} /> The Company</div>
                <p>
                  <strong>PRE IPO SIP PRIVATE LIMITED</strong>, a company incorporated under the provisions of the Companies Act, 2013, bearing CIN <strong>U65990MH2025OPC194372</strong>, and having its registered office at Office No. 14, 2nd Floor, Crystal Business Park, Near Golden Nest Road, Mira Bhayandar (East), Thane, Maharashtra – 401107 (hereinafter referred to as "Company", "Platform", "We", "Us", or "Our", which expression shall, unless repugnant to the context or meaning thereof, be deemed to include its successors and permitted assigns).
                </p>
              </div>
              <p className="text-center font-bold my-4">AND</p>
              <div className="callout callout-info">
                <div className="callout-header"><FileCheck size={18} /> The User</div>
                <p>
                  Any natural person, legal entity, or other organization who accesses, browses, registers, or uses the Platform for the purpose of availing Services (hereinafter referred to as "User", "Investor", "You", or "Your").
                </p>
              </div>
              <p>
                <strong>WHEREAS:</strong>
                <br />
                A. The Company operates an online technology platform at <em>www.preiposip.com</em> and associated mobile applications (collectively, the "Platform") that facilitates the discovery, purchase, and sale of unlisted securities.<br />
                B. The User wishes to avail the Services provided by the Company on the Platform in accordance with the terms and conditions set forth herein.<br />
                C. The Parties intend to be legally bound by this Agreement.
              </p>
            </section>

            {/* 2. Definitions */}
            <section id="definitions" className="section">
              <div className="section-header">
                <span className="section-number">02</span>
                <h2 className="section-title">Definitions & Interpretation</h2>
              </div>
              <p>In this Agreement, unless the context otherwise requires, the following terms shall have the meanings ascribed to them below:</p>
              <div className="def-grid">
                {[
                  { term: 'Applicable Law', desc: 'Means all applicable statutes, enactments, acts of legislature or parliament, laws, ordinances, rules, bye-laws, regulations, notifications, guidelines, policies, directions, directives, and orders of any governmental authority, statutory authority, tribunal, board, or court in India, including but not limited to the Companies Act, 2013, SEBI Act, 1992, SCRA, 1956, FEMA, 1999, PMLA, 2002, and IT Act, 2000.' },
                  { term: 'Unlisted Securities', desc: 'Means equity shares, preference shares, debentures, bonds, or any other financial instruments of companies that are not listed on any recognized stock exchange in India (BSE/NSE) at the time of the transaction.' },
                  { term: 'SIP (Systematic Investment Plan)', desc: 'Refers to the facility provided by the Platform enabling Users to invest a fixed amount at regular intervals (e.g., monthly) to purchase Unlisted Securities, subject to availability and price fluctuations.' },
                  { term: 'KYC (Know Your Customer)', desc: 'Means the process of verifying the identity of the User as mandated by the Prevention of Money Laundering Act, 2002, and relevant SEBI/RBI circulars.' },
                  { term: 'Escrow Account', desc: 'Means a bank account maintained by a SEBI-registered Trustee or a scheduled commercial bank where funds from the Investor are held temporarily before being transferred to the Seller upon successful execution of the trade.' },
                  { term: 'RTA', desc: 'Means Registrar and Share Transfer Agent, an entity registered with SEBI responsible for maintaining the records of holders of securities issued by a company.' },
                  { term: 'Transaction', desc: 'Means any order placed by the User on the Platform for the purchase or sale of Unlisted Securities.' },
                  { term: 'Working Day', desc: 'Means any day other than Saturday, Sunday, or a public holiday on which commercial banks in Mumbai are open for business.' }
                ].map((item, i) => (
                  <div className="def-row" key={i}>
                    <div className="def-term">{item.term}</div>
                    <div className="def-desc">{item.desc}</div>
                  </div>
                ))}
              </div>
            </section>

            {/* 3. Acceptance */}
            <section id="acceptance" className="section">
              <div className="section-header">
                <span className="section-number">03</span>
                <h2 className="section-title">Acceptance of Terms</h2>
              </div>
              <p>
                3.1. <strong>Binding Agreement:</strong> By accessing, browsing, or registering on the Platform, You explicitly acknowledge that You have read, understood, and agree to be bound by this Agreement. If You do not agree with any part of these Terms, You must immediately discontinue use of the Platform.
              </p>
              <p>
                3.2. <strong>Electronic Record:</strong> This Agreement is an electronic record in terms of the Information Technology Act, 2000. It is generated by a computer system and does not require any physical or digital signatures.
              </p>
              <p>
                3.3. <strong>Modifications:</strong> The Company reserves the right to modify, amend, or update these Terms at any time. Such changes will be effective immediately upon posting on the Platform. Your continued use of the Platform constitutes acceptance of the modified Terms.
              </p>
            </section>

            {/* 4. Eligibility */}
            <section id="eligibility" className="section">
              <div className="section-header">
                <span className="section-number">04</span>
                <h2 className="section-title">Eligibility & Competency</h2>
              </div>
              <p>Use of the Platform is available only to persons who can form legally binding contracts under the <strong>Indian Contract Act, 1872</strong>. You represent and warrant that:</p>
              <ul className="legal-list">
                <li>You are at least 18 years of age (if an individual);</li>
                <li>You are of sound mind and not disqualified from contracting by any law;</li>
                <li>You are not an undischarged insolvent;</li>
                <li>You are a resident of India or a Non-Resident Indian (NRI) / Person of Indian Origin (PIO) permitted to invest under the Foreign Exchange Management Act, 1999 (FEMA);</li>
                <li>You possess a valid Permanent Account Number (PAN) issued by the Income Tax Department of India;</li>
                <li>You hold a valid Demat Account with a SEBI-registered Depository Participant in India (NSDL/CDSL).</li>
              </ul>
              <div className="callout callout-danger">
                <div className="callout-header"><ShieldAlert size={18} /> Prohibited Jurisdictions</div>
                <p>
                  The Platform is not intended for use by persons located in jurisdictions where the distribution of such material would be contrary to local law or regulation (e.g., United States residents subject to FATCA/SEC regulations). It is Your responsibility to ensure compliance with local laws.
                </p>
              </div>
            </section>

            {/* 5. Account Registration */}
            <section id="account-registration" className="section">
              <div className="section-header">
                <span className="section-number">05</span>
                <h2 className="section-title">Account Registration & KYC</h2>
              </div>
              <h3>5.1 Registration Process</h3>
              <p>
                To access the Services, You must register and create an account ("User Account") by providing accurate, current, and complete information. You agree to keep this information updated at all times.
              </p>
              
              <h3>5.2 KYC Obligations (PMLA Compliance)</h3>
              <p>
                As a platform dealing in financial products, We are mandated to conduct Know Your Customer (KYC) verification. You agree to submit:
              </p>
              <ul className="legal-list">
                <li>Self-attested copy of PAN Card;</li>
                <li>Proof of Address (Aadhaar/Passport/Voter ID/Driving License);</li>
                <li>Proof of Bank Account (Cancelled Cheque/Passbook/Bank Statement);</li>
                <li>Client Master Report (CMR) of Your Demat Account.</li>
              </ul>
              <p>
                We may use third-party verification services (e.g., DigiLocker, KRA) to validate Your information. Failure to provide valid KYC documents may result in account suspension or rejection of transactions.
              </p>

              <h3>5.3 Account Security</h3>
              <p>
                You are solely responsible for maintaining the confidentiality of Your login credentials (Username, Password, MPIN, OTP). You agree to notify Us immediately of any unauthorized use of Your account. The Company shall not be liable for any loss or damage arising from Your failure to comply with this security obligation.
              </p>
            </section>

            {/* 6. Scope of Services */}
            <section id="service-scope" className="section">
              <div className="section-header">
                <span className="section-number">06</span>
                <h2 className="section-title">Scope of Services & Role</h2>
              </div>
              <p>The Company operates as a technology aggregator and facilitator. Our role is strictly limited to:</p>
              <ul className="legal-list">
                <li>Providing a digital platform for the discovery of price and availability of Unlisted Securities;</li>
                <li>Facilitating the execution of purchase and sale orders between willing buyers and sellers;</li>
                <li>Coordinating with RTAs and Depository Participants for the settlement of trades;</li>
                <li>Providing administrative support for SIP management.</li>
              </ul>

              <div className="callout callout-warning">
                <div className="callout-header"><AlertCircle size={18} /> Execution-Only Platform (No Advisory)</div>
                <p>
                  <strong>WE ARE NOT AN INVESTMENT ADVISOR.</strong> The information, research reports, data, and analysis provided on the Platform are for informational purposes only and do not constitute "Investment Advice" as defined under the SEBI (Investment Advisers) Regulations, 2013. You acknowledge that You are making investment decisions based on Your own independent assessment of risks and suitability.
                </p>
              </div>
            </section>

            {/* 7. Investment Terms */}
            <section id="investments" className="section">
              <div className="section-header">
                <span className="section-number">07</span>
                <h2 className="section-title">Terms of Investment</h2>
              </div>
              <h3>7.1 Order Placement</h3>
              <p>
                All orders placed on the Platform are binding offers to purchase or sell. Once an order is confirmed and funds are remitted, it cannot be cancelled by the User unless explicitly permitted by the Platform rules.
              </p>

              <h3>7.2 Inventory & Pricing</h3>
              <p>
                Unlisted Securities are not traded on a public exchange. Prices displayed on the Platform are indicative and derived from secondary market demand-supply dynamics. The Company does not control the price. Allocation of shares is subject to availability of inventory with the seller. In case of inventory shortfall, the Company reserves the right to cancel the order and refund the amount.
              </p>

              <h3>7.3 Settlement Process</h3>
              <p>
                Settlement is typically conducted on a T+3 basis (Transaction date + 3 working days), subject to:
              </p>
              <ul className="legal-list">
                <li>Successful receipt of funds in the Company's/Escrow account;</li>
                <li>Verification of User's Demat details;</li>
                <li>Approval of transfer by the Investee Company's Board (where applicable, specifically for Private Limited companies);</li>
                <li>Processing time by the RTA and Depositories.</li>
              </ul>
            </section>

            {/* 8. SIP Framework */}
            <section id="sip-framework" className="section">
              <div className="section-header">
                <span className="section-number">08</span>
                <h2 className="section-title">Systematic Investment Plan (SIP) Framework</h2>
              </div>
              <p>
                The SIP facility on PreIPO SIP is a contractual arrangement to procure Unlisted Securities periodically. It differs fundamentally from Mutual Fund SIPs.
              </p>

              <h3>8.1 SIP Contract</h3>
              <p>
                By enrolling in a SIP, You authorize the Company to place orders for a specified value or quantity of a specific security at regular intervals (e.g., monthly).
              </p>

              <h3>8.2 Price Variance</h3>
              <p>
                You acknowledge that the price of Unlisted Securities fluctuates. The execution price for each SIP installment will be the prevailing market price on the SIP Due Date, which may be higher or lower than the price at the time of initial enrollment.
              </p>

              <h3>8.3 Payment Mandate</h3>
              <p>
                You agree to register a valid e-NACH / UPI Autopay mandate for the SIP amount. It is Your responsibility to ensure sufficient balance in Your bank account on the SIP Due Date.
              </p>

              <h3>8.4 Missed Installments</h3>
              <p>
                If a SIP installment fails due to insufficient funds or payment rejection:
              </p>
              <ul className="legal-list">
                <li>We may re-attempt the transaction within 3 working days;</li>
                <li>Bank charges for failed mandates may be recovered from You;</li>
                <li>Three (3) consecutive failed installments will result in the automatic termination of the SIP facility;</li>
                <li>Users with terminated SIPs may be barred from accessing preferential "SIP Pricing" tiers in the future.</li>
              </ul>
            </section>

            {/* 9. Payments */}
            <section id="payments-settlements" className="section">
              <div className="section-header">
                <span className="section-number">09</span>
                <h2 className="section-title">Payments, Settlements & Refunds</h2>
              </div>
              <h3>9.1 Payment Modes</h3>
              <p>
                Payments must be made via banking channels (NEFT/RTGS/IMPS) or UPI from a bank account registered in the name of the User. Third-party payments or cash deposits are strictly prohibited and will be rejected/refunded after deducting handling charges.
              </p>

              <h3>9.2 Refund Policy</h3>
              <p>
                Refunds are processed <strong>ONLY</strong> under the following conditions:
              </p>
              <ul className="legal-list">
                <li><strong>Inventory Stockout:</strong> If the Company/Seller cannot fulfill the order due to unavailability of shares.</li>
                <li><strong>RTA Rejection:</strong> If the Registrar rejects the share transfer due to technical reasons not attributable to the User.</li>
                <li><strong>Regulatory Ban:</strong> If trading in the specific security is suspended by a regulatory authority.</li>
              </ul>
              <p>
                Refunds will be processed to the source bank account within 7-10 working days. The Company shall not be liable to pay any interest on such refund amounts.
              </p>
            </section>

            {/* 10. Fees */}
            <section id="fees-charges" className="section">
              <div className="section-header">
                <span className="section-number">10</span>
                <h2 className="section-title">Fees, Charges & Taxes</h2>
              </div>
              <h3>10.1 Platform Fees</h3>
              <p>
                The Company charges a facilitation fee/margin on transactions, which is included in the price or charged separately as displayed on the trade confirmation page.
              </p>

              <h3>10.2 Statutory Levies</h3>
              <p>
                You are responsible for all statutory levies and taxes associated with the transaction, including but not limited to:
              </p>
              <ul className="legal-list">
                <li><strong>Stamp Duty:</strong> Applicable on the transfer of shares (typically 0.015% for demat transfers);</li>
                <li><strong>GST:</strong> 18% GST is applicable on the Platform Fee/Service Charge;</li>
                <li><strong>TDS:</strong> Tax Deducted at Source, if applicable under the Income Tax Act.</li>
              </ul>

              <h3>10.3 Capital Gains Tax</h3>
              <p>
                You are solely responsible for calculating and paying Capital Gains Tax (Short Term or Long Term) upon the sale of these securities. Unlisted shares held for more than 24 months are generally treated as Long Term Capital Assets.
              </p>
            </section>

            {/* 11. Risk Factors */}
            <section id="risk-factors" className="section">
              <div className="section-header">
                <span className="section-number">11</span>
                <h2 className="section-title">Comprehensive Risk Disclosures</h2>
              </div>
              <div className="callout callout-danger">
                <div className="callout-header"><AlertTriangle size={18} /> WARNING: READ CAREFULLY</div>
                <p>
                  Investment in unlisted securities is speculative and involves a high degree of risk. You should not invest unless You can afford to lose Your entire capital.
                </p>
              </div>
              
              <h3>11.1 Liquidity Risk</h3>
              <p>
                Unlike listed shares, unlisted securities do not have an active trading market. You may not be able to sell Your shares when You want to, or at a price You find acceptable. The Platform does not guarantee any exit, buyback, or liquidity.
              </p>

              <h3>11.2 Regulatory & Lock-in Risk</h3>
              <p>
                If the Investee Company goes public (IPO), pre-IPO shares are typically subject to a mandatory <strong>Lock-in Period of 6 months</strong> (or as prescribed by SEBI ICDR Regulations) from the date of listing. During this period, You cannot sell, transfer, or pledge these shares.
              </p>

              <h3>11.3 Valuation Risk</h3>
              <p>
                Prices of unlisted shares are not discovered through a transparent exchange mechanism. They are based on off-market demand and supply. The price You pay may be significantly higher than the fair value or the future listing price.
              </p>

              <h3>11.4 Company Risk</h3>
              <p>
                Unlisted companies are subject to lower disclosure requirements than listed companies. Information availability regarding financial health, governance, and operations may be limited. There is a risk that the Investee Company may never list or may go bankrupt.
              </p>
            </section>

            {/* 12. User Obligations */}
            <section id="user-obligations" className="section">
              <div className="section-header">
                <span className="section-number">12</span>
                <h2 className="section-title">User Obligations & Conduct</h2>
              </div>
              <p>You agree NOT to:</p>
              <ul className="legal-list">
                <li>Use the Platform for money laundering, terrorist financing, or any illegal activity;</li>
                <li>Probe, scan, or test the vulnerability of the Platform or breach security authentication measures;</li>
                <li>Use any robot, spider, scraper, or other automated means to access the Platform data ("Data Scraping");</li>
                <li>Interfere with the proper working of the Platform or impose an unreasonable load on our infrastructure;</li>
                <li>Disseminate false or misleading information regarding any security listed on the Platform.</li>
              </ul>
            </section>

            {/* 13. Data Protection */}
            <section id="privacy-data" className="section">
              <div className="section-header">
                <span className="section-number">13</span>
                <h2 className="section-title">Data Protection & Privacy</h2>
              </div>
              <p>
                Your privacy is critical to us. Our collection, storage, and use of Your personal data are governed by our <strong>Privacy Policy</strong>, which complies with the Digital Personal Data Protection Act, 2023 and the Information Technology Act, 2000. By using the Platform, You consent to the processing of Your data as described therein.
              </p>
            </section>

            {/* 14. IP */}
            <section id="ip-rights" className="section">
              <div className="section-header">
                <span className="section-number">14</span>
                <h2 className="section-title">Intellectual Property Rights</h2>
              </div>
              <p>
                The Platform, including its code, design, interfaces, research reports, and logos, is the proprietary property of Pre IPO Sip Pvt Ltd. You are granted a limited, non-exclusive, revocable license to access the Platform for personal investment purposes. You may not copy, scrape, reproduce, or resell any data found on the Platform without written permission.
              </p>
            </section>

            {/* 15. Confidentiality */}
            <section id="confidentiality" className="section">
              <div className="section-header">
                <span className="section-number">15</span>
                <h2 className="section-title">Confidentiality</h2>
              </div>
              <p>
                You acknowledge that the research reports, valuation data, and seller information provided on the Platform may contain confidential information. You agree not to disclose such information to third parties without prior written consent, except as required by law.
              </p>
            </section>

            {/* 16. Indemnification */}
            <section id="indemnification" className="section">
              <div className="section-header">
                <span className="section-number">16</span>
                <h2 className="section-title">Indemnification</h2>
              </div>
              <p>
                You agree to indemnify, defend, and hold harmless the Company, its directors, officers, employees, and agents from and against any and all losses, liabilities, claims, damages, costs, and expenses (including legal fees) arising out of or related to:
              </p>
              <ul className="legal-list">
                <li>Your breach of this Agreement or any Platform Document;</li>
                <li>Your violation of any applicable law, rule, or regulation (including SEBI, FEMA, PMLA);</li>
                <li>Your infringement of any third-party intellectual property or other rights;</li>
                <li>Any fraud, negligence, or willful misconduct by You.</li>
              </ul>
            </section>

            {/* 17. Limitation of Liability */}
            <section id="limitation-liability" className="section">
              <div className="section-header">
                <span className="section-number">17</span>
                <h2 className="section-title">Limitation of Liability</h2>
              </div>
              <p>
                TO THE MAXIMUM EXTENT PERMITTED BY LAW, THE COMPANY SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, OR ANY LOSS OF PROFITS OR REVENUES, WHETHER INCURRED DIRECTLY OR INDIRECTLY, OR ANY LOSS OF DATA, USE, GOODWILL, OR OTHER INTANGIBLE LOSSES, RESULTING FROM:
              </p>
              <ul className="legal-list">
                <li>YOUR ACCESS TO OR USE OF OR INABILITY TO ACCESS OR USE THE PLATFORM;</li>
                <li>ANY CONDUCT OR CONTENT OF ANY THIRD PARTY ON THE PLATFORM;</li>
                <li>ANY UNAUTHORIZED ACCESS, USE, OR ALTERATION OF YOUR TRANSMISSIONS OR CONTENT;</li>
                <li>MARKET FLUCTUATIONS OR DIMINUTION IN VALUE OF INVESTMENTS.</li>
              </ul>
              <p>
                IN NO EVENT SHALL THE COMPANY'S AGGREGATE LIABILITY FOR ALL CLAIMS RELATED TO THE SERVICES EXCEED THE TOTAL FEES PAID BY YOU TO THE COMPANY FOR THE SPECIFIC TRANSACTION GIVING RISE TO THE CLAIM.
              </p>
            </section>

            {/* 18. Termination */}
            <section id="termination" className="section">
              <div className="section-header">
                <span className="section-number">18</span>
                <h2 className="section-title">Term & Termination</h2>
              </div>
              <p>
                This Agreement shall remain in full force and effect while You use the Platform. The Company may terminate or suspend Your access to the Platform at any time, without prior notice or liability, for any reason whatsoever, including without limitation if You breach the Terms. Upon termination, Your right to use the Platform will immediately cease.
              </p>
            </section>

            {/* 19. Dispute Resolution */}
            <section id="dispute-resolution" className="section">
              <div className="section-header">
                <span className="section-number">19</span>
                <h2 className="section-title">Governing Law & Dispute Resolution</h2>
              </div>
              <div className="callout callout-info">
                <div className="callout-header"><Gavel size={18} /> Arbitration Agreement</div>
                <p>
                  All disputes, differences, or claims arising out of or in connection with this Agreement shall be referred to and finally resolved by binding <strong>Arbitration</strong> in accordance with the Arbitration and Conciliation Act, 1996.
                </p>
              </div>
              <ul className="legal-list">
                <li><strong>Seat & Venue:</strong> Mumbai, Maharashtra, India.</li>
                <li><strong>Language:</strong> English.</li>
                <li><strong>Arbitrator:</strong> A sole arbitrator appointed mutually by the Parties, or failing agreement, by the High Court of Bombay.</li>
                <li><strong>Jurisdiction:</strong> Subject to arbitration, the courts in Mumbai shall have exclusive jurisdiction.</li>
              </ul>
            </section>

            {/* 20. Force Majeure */}
            <section id="force-majeure" className="section">
              <div className="section-header">
                <span className="section-number">20</span>
                <h2 className="section-title">Force Majeure</h2>
              </div>
              <p>
                The Company shall not be liable for any failure to perform its obligations hereunder where such failure results from any cause beyond the Company's reasonable control, including, without limitation, mechanical, electronic or communications failure or degradation, acts of God, war, terrorism, riots, embargoes, acts of civil or military authorities, fire, floods, accidents, strikes, or shortages of transportation facilities, fuel, energy, labor or materials, or failure of the banking or depository systems.
              </p>
            </section>
            
            {/* 21. Miscellaneous */}
            <section id="miscellaneous" className="section">
              <div className="section-header">
                <span className="section-number">21</span>
                <h2 className="section-title">Miscellaneous Provisions</h2>
              </div>
              <h3>21.1 Severability</h3>
              <p>
                If any provision of this Agreement is held to be invalid or unenforceable, such provision shall be struck and the remaining provisions shall be enforced.
              </p>
              <h3>21.2 Assignment</h3>
              <p>
                You may not assign or transfer these Terms, by operation of law or otherwise, without Company's prior written consent. Any attempt by You to assign or transfer these Terms, without such consent, will be null. The Company may assign or transfer these Terms, at its sole discretion, without restriction.
              </p>
              <h3>21.3 Entire Agreement</h3>
              <p>
                These Terms, together with the Privacy Policy and any other legal notices published by the Company on the Platform, shall constitute the entire agreement between you and the Company concerning the Platform.
              </p>
            </section>

            {/* 22. Contact */}
            <section id="contact" className="section">
              <div className="section-header">
                <span className="section-number">22</span>
                <h2 className="section-title">Contact & Grievance Redressal</h2>
              </div>
              <p>
                In accordance with the Information Technology Act, 2000 and rules made thereunder, the contact details of the Grievance Officer are provided below:
              </p>
              <div className="grid md:grid-cols-2 gap-6 mt-6">
                <div className="p-6 bg-[var(--bg-secondary)] border border-[var(--border-color)] rounded-lg">
                  <h4 className="text-[var(--text-primary)] mb-2 font-semibold flex items-center gap-2"><ShieldAlert size={16} /> Grievance Officer</h4>
                  <p className="text-sm text-[var(--text-secondary)] mb-1 font-bold">Mr. Rajesh Kumar</p>
                  <p className="text-sm text-[var(--text-secondary)] mb-2">Compliance Head</p>
                  <a href="mailto:grievance@preiposip.com" className="text-[var(--accent-primary)] text-sm hover:underline">grievance@preiposip.com</a>
                </div>
                <div className="p-6 bg-[var(--bg-secondary)] border border-[var(--border-color)] rounded-lg">
                  <h4 className="text-[var(--text-primary)] mb-2 font-semibold flex items-center gap-2"><Landmark size={16} /> Registered Office</h4>
                  <p className="text-sm text-[var(--text-secondary)] leading-relaxed">
                    <strong>PreIPO SIP Private Limited</strong><br/>
                    Office No. 14, 2nd Floor, Crystal Business Park,<br/>
                    Near Golden Nest Road, Mira Bhayandar (East),<br/>
                    Thane, Maharashtra – 401107
                  </p>
                </div>
              </div>
            </section>

          </div>
        </main>
      </div>
    </div>
  );
}
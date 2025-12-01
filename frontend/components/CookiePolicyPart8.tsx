'use client';

import React from 'react';
import { Network, Shield, Globe } from 'lucide-react';

export default function CookiePolicyPart8() {
  return (
    <section id="part-5" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 5</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">THIRD-PARTY COOKIE PROVIDERS, DATA SHARING, CROSS-BORDER TRANSFERS, AND CONTRACTUAL SAFEGUARDS</h2>
      </div>

      {/* 5.1 */}
      <div id="point-5-1" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Network className="text-indigo-600" size={20} /> 5.1 THIRD-PARTY ECOSYSTEM GOVERNANCE FRAMEWORK
        </h3>
        
        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.1.1 Foundational Principles:</h4>
            <p className="text-slate-600 mb-1">The Platform's engagement with third-party cookie providers and data processors is governed by the following principles:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Data Minimization:</strong> Third parties receive only data strictly necessary for specified purposes;</li>
                <li>(b) <strong>Purpose Limitation:</strong> Third-party processing is restricted to purposes explicitly authorized by Users;</li>
                <li>(c) <strong>Confidentiality:</strong> All third parties are bound by strict confidentiality obligations;</li>
                <li>(d) <strong>Security:</strong> Third parties must implement security measures equivalent to or exceeding Platform's own standards;</li>
                <li>(e) <strong>Sub-Processor Control:</strong> Third parties cannot engage sub-processors without Platform's prior written authorization;</li>
                <li>(f) <strong>Compliance Verification:</strong> Regular audits verify third-party compliance with contractual obligations;</li>
                <li>(g) <strong>Liability Chain:</strong> Third parties indemnify Platform for breaches arising from their processing;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.1.2 Legal Basis for Third-Party Data Sharing:</h4>
            <p className="text-slate-600 mb-1">Data sharing with third parties is justified on the following legal bases:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>User Consent:</strong> Obtained through cookie consent mechanism for optional third-party cookies;</li>
                <li>(b) <strong>Contractual Necessity:</strong> Where third-party services are essential for Platform functionality (e.g., payment processors, hosting providers);</li>
                <li>(c) <strong>Legal Obligation:</strong> Where disclosure is mandated by law (e.g., to law enforcement, tax authorities, SEBI);</li>
                <li>(d) <strong>Legitimate Interests:</strong> For fraud prevention, security, and Platform improvement, subject to balancing test;</li>
            </ul>
        </div>
      </div>
    </section>
  );
}
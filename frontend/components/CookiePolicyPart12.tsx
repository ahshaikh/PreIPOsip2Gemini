'use client';

import React from 'react';
import { Clock, Trash2, Database, AlertOctagon } from 'lucide-react';

export default function CookiePolicyPart12() {
  return (
    <section id="part-7" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 7</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">COOKIE LIFESPAN, RETENTION POLICIES, DELETION MECHANISMS, AND DATA MINIMIZATION FRAMEWORK</h2>
      </div>

      {/* 7.1 */}
      <div id="point-7-1" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Clock className="text-indigo-600" size={20} /> 7.1 FOUNDATIONAL RETENTION PRINCIPLES
        </h3>
        
        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.1.1 Statutory and Regulatory Framework:</h4>
            <p className="text-slate-600 mb-2">Cookie data retention is governed by multiple, sometimes overlapping, legal obligations:</p>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Prevention of Money Laundering Act, 2002:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Section 12(1)(a): Records of transactions to be maintained for five years from date of transaction;</li>
                        <li>Rule 3 of PML (Maintenance of Records) Rules, 2005: All records pertaining to transactions and identity of clients to be preserved for five years from date of cessation of transaction;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) SEBI (Investment Advisers) Regulations, 2013:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Regulation 16(1): Investment advisers shall maintain books of account, records and documents for minimum period of ten years;</li>
                        <li>Scope includes: client agreements, risk profiling, investment advice, communications, transaction confirmations;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Companies Act, 2013:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Section 128(5): Books of account to be preserved for eight years immediately preceding financial year;</li>
                        <li>Section 88: Register of members to be maintained permanently;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(d) Income Tax Act, 1961:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Section 44AA: Books of account to be maintained for six years from end of relevant assessment year;</li>
                        <li>Section 92CA: Transfer pricing documentation for eight years;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(e) Information Technology Act, 2000:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>No specific retention mandate for cookies;</li>
                        <li>Retention must align with "reasonable security practices" under Section 43A;</li>
                        <li>Data minimization principles require deletion when no longer necessary;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(f) Limitation Act, 1963:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Section 2(j): Period of limitation for civil suits typically three years;</li>
                        <li>Practical implication: retention for potential litigation defense;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.1.2 Balancing Competing Obligations:</h4>
            <p className="text-slate-600 mb-2">The Platform navigates competing principles of:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1 mb-4">
                <li>(a) <strong>Data Minimization:</strong> Collect and retain only what is necessary, delete when purpose fulfilled;</li>
                <li>(b) <strong>Regulatory Compliance:</strong> Retain data for prescribed statutory periods regardless of ongoing necessity;</li>
                <li>(c) <strong>User Rights:</strong> Facilitate erasure requests while respecting legal retention obligations;</li>
                <li>(d) <strong>Business Necessity:</strong> Maintain records for operational, analytical, and defensive purposes;</li>
            </ul>
            <p className="text-slate-700 font-semibold mb-2">Resolution Framework:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Legal retention obligations take precedence over data minimization;</li>
                <li>Cookie data tied to regulatory requirements retained for maximum statutory period (10 years for SEBI);</li>
                <li>Cookie data not subject to legal retention deleted according to purpose-based schedules;</li>
                <li>Users informed of retention obligations preventing full erasure;</li>
            </ul>
        </div>
      </div>

      {/* 7.2 */}
      <div id="point-7-2" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4">7.2 COMPREHENSIVE COOKIE RETENTION SCHEDULE</h3>

        {/* 7.2.1 */}
        <div className="mb-8">
            <h4 className="text-lg font-bold text-slate-700 mb-3">7.2.1 Strictly Necessary Cookies:</h4>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Cookie Name</th>
                            <th className="px-6 py-3">Type</th>
                            <th className="px-6 py-3">Purpose</th>
                            <th className="px-6 py-3">Active Lifespan</th>
                            <th className="px-6 py-3">Post-Purpose Retention</th>
                            <th className="px-6 py-3">Legal Basis for Retention</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-mono text-indigo-600">PHPSESSID / JSESSIONID</td>
                            <td className="px-6 py-4">Session</td>
                            <td className="px-6 py-4">Session management</td>
                            <td className="px-6 py-4">Session duration</td>
                            <td className="px-6 py-4">Immediate deletion</td>
                            <td className="px-6 py-4">N/A - Session only</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-mono text-indigo-600">auth_token</td>
                            <td className="px-6 py-4">Persistent</td>
                            <td className="px-6 py-4">User authentication</td>
                            <td className="px-6 py-4">30 days</td>
                            <td className="px-6 py-4">10 years</td>
                            <td className="px-6 py-4">SEBI Reg 16(1) - Client interaction records</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-mono text-indigo-600">csrf_token</td>
                            <td className="px-6 py-4">Session</td>
                            <td className="px-6 py-4">Security (CSRF protection)</td>
                            <td className="px-6 py-4">Session duration</td>
                            <td className="px-6 py-4">Immediate deletion</td>
                            <td className="px-6 py-4">N/A - Session only</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-mono text-indigo-600">cookie_consent</td>
                            <td className="px-6 py-4">Persistent</td>
                            <td className="px-6 py-4">Consent management</td>
                            <td className="px-6 py-4">12 months</td>
                            <td className="px-6 py-4">5 years</td>
                            <td className="px-6 py-4">PMLA Rule 3 - User consent records</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-mono text-indigo-600">secure_transaction_id</td>
                            <td className="px-6 py-4">Session</td>
                            <td className="px-6 py-4">Transaction tracking</td>
                            <td className="px-6 py-4">Transaction duration</td>
                            <td className="px-6 py-4">10 years</td>
                            <td className="px-6 py-4">SEBI Reg 16(1) + PMLA Rule 3</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-mono text-indigo-600">kyc_verification_status</td>
                            <td className="px-6 py-4">Persistent</td>
                            <td className="px-6 py-4">Compliance verification</td>
                            <td className="px-6 py-4">90 days</td>
                            <td className="px-6 py-4">5 years from last transaction</td>
                            <td className="px-6 py-4">PMLA Rule 3</td>
                        </tr>
                        <tr className="bg-white">
                            <td className="px-6 py-4 font-mono text-indigo-600">geo_jurisdiction</td>
                            <td className="px-6 py-4">Session</td>
                            <td className="px-6 py-4">Jurisdictional compliance</td>
                            <td className="px-6 py-4">Session duration</td>
                            <td className="px-6 py-4">3 years</td>
                            <td className="px-6 py-4">Regulatory compliance records</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p className="text-slate-700 font-semibold mb-2">Deletion Triggers:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Session cookies: Automatic deletion upon browser closure or 8-hour absolute timeout (whichever is earlier);</li>
                <li>Persistent cookies: Automatic expiration per specified lifespan;</li>
                <li>Backend records: Retention per legal basis, then automated deletion after retention period;</li>
            </ul>
        </div>

        {/* 7.2.2 */}
        <div className="mb-8">
            <h4 className="text-lg font-bold text-slate-700 mb-3">7.2.2 Functional Cookies:</h4>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Cookie Name</th>
                            <th className="px-6 py-3">Type</th>
                            <th className="px-6 py-3">Active Lifespan</th>
                            <th className="px-6 py-3">Post-Active Retention</th>
                            <th className="px-6 py-3">Deletion Trigger</th>
                            <th className="px-6 py-3">Override Mechanism</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">user_preferences</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">12 months</td><td className="px-6 py-4">6 months</td><td className="px-6 py-4">Inactivity + retention period</td><td className="px-6 py-4">User can delete via preference center</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">language_preference</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">12 months</td><td className="px-6 py-4">6 months</td><td className="px-6 py-4">Inactivity + retention period</td><td className="px-6 py-4">User can delete via preference center</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">dashboard_layout</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">6 months</td><td className="px-6 py-4">3 months</td><td className="px-6 py-4">Inactivity + retention period</td><td className="px-6 py-4">User can delete via preference center</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">recently_viewed</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">30 days</td><td className="px-6 py-4">Immediate</td><td className="px-6 py-4">30 days from last view</td><td className="px-6 py-4">User can clear history</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">saved_filters</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">90 days</td><td className="px-6 py-4">Immediate</td><td className="px-6 py-4">90 days from last use</td><td className="px-6 py-4">User can clear filters</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">chat_session</td><td className="px-6 py-4">Session</td><td className="px-6 py-4">Session duration</td><td className="px-6 py-4">3 years</td><td className="px-6 py-4">Session end + regulatory retention</td><td className="px-6 py-4">Customer service records</td></tr>
                        <tr className="bg-white"><td className="px-6 py-4">onboarding_status</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">6 months</td><td className="px-6 py-4">Immediate</td><td className="px-6 py-4">Onboarding completion + 6 months</td><td className="px-6 py-4">User can reset onboarding</td></tr>
                    </tbody>
                </table>
            </div>
            <p className="text-slate-700 font-semibold mb-2">Data Minimization Implementation:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Functional cookies contain minimal identifiable data;</li>
                <li>Preference data stored as codes/flags rather than detailed personal information;</li>
                <li>Automatic deletion upon User account closure (subject to regulatory retention for transactional data);</li>
            </ul>
        </div>

        {/* 7.2.3 */}
        <div className="mb-8">
            <h4 className="text-lg font-bold text-slate-700 mb-3">7.2.3 Performance and Analytics Cookies:</h4>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Cookie Name</th>
                            <th className="px-6 py-3">Provider</th>
                            <th className="px-6 py-3">Active Lifespan</th>
                            <th className="px-6 py-3">Anonymization</th>
                            <th className="px-6 py-3">Aggregation Period</th>
                            <th className="px-6 py-3">Deletion Schedule</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">_ga</td><td className="px-6 py-4">Google Analytics</td><td className="px-6 py-4">24 months</td><td className="px-6 py-4">IP anonymized</td><td className="px-6 py-4">Daily aggregation</td><td className="px-6 py-4">26 months total</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">_gid</td><td className="px-6 py-4">Google Analytics</td><td className="px-6 py-4">24 hours</td><td className="px-6 py-4">IP anonymized</td><td className="px-6 py-4">Daily aggregation</td><td className="px-6 py-4">7 days total</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">_gat</td><td className="px-6 py-4">Google Analytics</td><td className="px-6 py-4">1 minute</td><td className="px-6 py-4">IP anonymized</td><td className="px-6 py-4">Real-time</td><td className="px-6 py-4">1 minute (request throttling)</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">analytics_session</td><td className="px-6 py-4">Internal</td><td className="px-6 py-4">Session</td><td className="px-6 py-4">Pseudonymized</td><td className="px-6 py-4">Weekly aggregation</td><td className="px-6 py-4">14 months total</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">performance_metrics</td><td className="px-6 py-4">Internal</td><td className="px-6 py-4">30 days</td><td className="px-6 py-4">Aggregated</td><td className="px-6 py-4">Monthly rollup</td><td className="px-6 py-4">18 months total</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">ab_test_variant</td><td className="px-6 py-4">Internal</td><td className="px-6 py-4">30 days</td><td className="px-6 py-4">Group-level only</td><td className="px-6 py-4">Per test completion</td><td className="px-6 py-4">Test completion + 12 months</td></tr>
                        <tr className="bg-white"><td className="px-6 py-4">heatmap_session</td><td className="px-6 py-4">Hotjar</td><td className="px-6 py-4">Session</td><td className="px-6 py-4">IP anonymized</td><td className="px-6 py-4">Session end</td><td className="px-6 py-4">365 days</td></tr>
                    </tbody>
                </table>
            </div>
            <p className="text-slate-700 font-semibold mb-2">Progressive Anonymization:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li><strong>Day 1-30:</strong> Raw data with pseudonymous identifiers for debugging;</li>
                <li><strong>Day 31-180:</strong> Aggregated to daily summaries, individual sessions deleted;</li>
                <li><strong>Day 181-365:</strong> Aggregated to weekly summaries, daily data deleted;</li>
                <li><strong>Day 366+:</strong> Aggregated to monthly summaries, weekly data deleted;</li>
                <li><strong>After retention period:</strong> Complete deletion including aggregates;</li>
            </ul>
        </div>

        {/* 7.2.4 */}
        <div className="mb-8">
            <h4 className="text-lg font-bold text-slate-700 mb-3">7.2.4 Targeting and Advertising Cookies:</h4>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Cookie Name</th>
                            <th className="px-6 py-3">Provider</th>
                            <th className="px-6 py-3">Active Lifespan</th>
                            <th className="px-6 py-3">Consent Withdrawal Effect</th>
                            <th className="px-6 py-3">Regulatory Retention</th>
                            <th className="px-6 py-3">Final Deletion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">_fbp</td><td className="px-6 py-4">Facebook</td><td className="px-6 py-4">90 days</td><td className="px-6 py-4">Immediate cessation</td><td className="px-6 py-4">None (consent-based)</td><td className="px-6 py-4">90 days from withdrawal</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">IDE</td><td className="px-6 py-4">Google DoubleClick</td><td className="px-6 py-4">13 months</td><td className="px-6 py-4">Immediate cessation</td><td className="px-6 py-4">None (consent-based)</td><td className="px-6 py-4">13 months from withdrawal</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">_gcl_au</td><td className="px-6 py-4">Google AdSense</td><td className="px-6 py-4">90 days</td><td className="px-6 py-4">Immediate cessation</td><td className="px-6 py-4">None (consent-based)</td><td className="px-6 py-4">90 days from withdrawal</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">personalization_id</td><td className="px-6 py-4">Twitter</td><td className="px-6 py-4">24 months</td><td className="px-6 py-4">Immediate cessation</td><td className="px-6 py-4">None (consent-based)</td><td className="px-6 py-4">24 months from withdrawal</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">li_sugr</td><td className="px-6 py-4">LinkedIn</td><td className="px-6 py-4">90 days</td><td className="px-6 py-4">Immediate cessation</td><td className="px-6 py-4">None (consent-based)</td><td className="px-6 py-4">90 days from withdrawal</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">retargeting_pixel</td><td className="px-6 py-4">Internal</td><td className="px-6 py-4">180 days</td><td className="px-6 py-4">Immediate cessation</td><td className="px-6 py-4">None (consent-based)</td><td className="px-6 py-4">180 days from withdrawal</td></tr>
                        <tr className="bg-white"><td className="px-6 py-4">investor_profile</td><td className="px-6 py-4">Internal</td><td className="px-6 py-4">12 months</td><td className="px-6 py-4">Conversion to anonymized insights</td><td className="px-6 py-4">If tied to investment: 10 years</td><td className="px-6 py-4">Per regulatory requirement</td></tr>
                    </tbody>
                </table>
            </div>
            <p className="text-slate-700 font-semibold mb-2">Consent-Based Deletion:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Upon consent withdrawal: cessation of active processing immediately;</li>
                <li>Cookie deleted from User's browser immediately;</li>
                <li>Backend data: marked for deletion and excluded from active processing;</li>
                <li>Physical deletion: within 30 days of consent withdrawal (unless regulatory retention applies);</li>
            </ul>
            <p className="text-slate-700 font-semibold mt-4 mb-2">Advertising Data Segregation:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Pure advertising data (no investment nexus): deleted per consent-based schedule;</li>
                <li>Advertising data linked to investment transactions: retained per SEBI requirements but segregated, not used for advertising;</li>
            </ul>
        </div>

        {/* 7.2.5 */}
        <div className="mb-8">
            <h4 className="text-lg font-bold text-slate-700 mb-3">7.2.5 Social Media Cookies:</h4>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Cookie Name</th>
                            <th className="px-6 py-3">Platform</th>
                            <th className="px-6 py-3">Active Lifespan</th>
                            <th className="px-6 py-3">User Control</th>
                            <th className="px-6 py-3">Platform Retention</th>
                            <th className="px-6 py-3">Platform Privacy Policy</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">datr / fr</td><td className="px-6 py-4">Facebook</td><td className="px-6 py-4">24 months</td><td className="px-6 py-4">Facebook Ad Preferences</td><td className="px-6 py-4">Per Facebook policy</td><td className="px-6 py-4">https://www.facebook.com/privacy</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">LOGIN_INFO / PREF</td><td className="px-6 py-4">YouTube</td><td className="px-6 py-4">24 months</td><td className="px-6 py-4">Google Account settings</td><td className="px-6 py-4">Per Google policy</td><td className="px-6 py-4">https://policies.google.com/privacy</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">lang / bcookie</td><td className="px-6 py-4">LinkedIn</td><td className="px-6 py-4">Session / 24 months</td><td className="px-6 py-4">LinkedIn privacy settings</td><td className="px-6 py-4">Per LinkedIn policy</td><td className="px-6 py-4">https://www.linkedin.com/legal/privacy-policy</td></tr>
                        <tr className="bg-white"><td className="px-6 py-4">guest_id</td><td className="px-6 py-4">Twitter</td><td className="px-6 py-4">24 months</td><td className="px-6 py-4">Twitter privacy settings</td><td className="px-6 py-4">Per Twitter policy</td><td className="px-6 py-4">https://twitter.com/privacy</td></tr>
                    </tbody>
                </table>
            </div>
            <p className="text-slate-600 mb-2"><strong>Platform Note:</strong> Social media cookies are controlled by third-party platforms. Platform provides:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Links to third-party privacy policies and opt-out mechanisms;</li>
                <li>Ability to disable social media cookies through Platform preference center (prevents future deployment);</li>
                <li>Cannot delete cookies already set by third parties (User must use third-party controls);</li>
            </ul>
        </div>

        {/* 7.2.6 */}
        <div className="mb-8">
            <h4 className="text-lg font-bold text-slate-700 mb-3">7.2.6 KYC and AML Compliance Cookies:</h4>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Cookie Name</th>
                            <th className="px-6 py-3">Purpose</th>
                            <th className="px-6 py-3">Active Lifespan</th>
                            <th className="px-6 py-3">Regulatory Retention</th>
                            <th className="px-6 py-3">Final Deletion</th>
                            <th className="px-6 py-3">Exemptions from User Deletion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">kyc_status</td><td className="px-6 py-4">KYC verification tracking</td><td className="px-6 py-4">12 months</td><td className="px-6 py-4">5 years from last transaction</td><td className="px-6 py-4">5 years + 6 months</td><td className="px-6 py-4">PMLA Section 12(1)(a)</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">aml_check</td><td className="px-6 py-4">AML screening results</td><td className="px-6 py-4">Session</td><td className="px-6 py-4">5 years from screening date</td><td className="px-6 py-4">5 years + 6 months</td><td className="px-6 py-4">PMLA Rule 3</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">document_verification</td><td className="px-6 py-4">Document verification status</td><td className="px-6 py-4">90 days</td><td className="px-6 py-4">5 years from verification</td><td className="px-6 py-4">5 years + 6 months</td><td className="px-6 py-4">PMLA Rule 9</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">pep_screening</td><td className="px-6 py-4">PEP/sanctions screening</td><td className="px-6 py-4">180 days</td><td className="px-6 py-4">5 years from screening</td><td className="px-6 py-4">5 years + 6 months</td><td className="px-6 py-4">PMLA Rule 3</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">risk_category</td><td className="px-6 py-4">Customer risk classification</td><td className="px-6 py-4">12 months</td><td className="px-6 py-4">5 years from last update</td><td className="px-6 py-4">5 years + 6 months</td><td className="px-6 py-4">PMLA Rule 9</td></tr>
                        <tr className="bg-white"><td className="px-6 py-4">cdd_timestamp</td><td className="px-6 py-4">Due diligence timestamp</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">5 years from CDD completion</td><td className="px-6 py-4">5 years + 6 months</td><td className="px-6 py-4">PMLA Rule 3</td></tr>
                    </tbody>
                </table>
            </div>
            <p className="text-slate-700 font-semibold mb-2">Regulatory Override:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>User erasure requests cannot override PMLA retention requirements;</li>
                <li>Platform informs Users of legal inability to delete;</li>
                <li>Data restricted to compliance use only during regulatory retention period;</li>
                <li>Access limited to compliance team and regulatory authorities;</li>
                <li>Automated deletion triggered at end of retention period;</li>
            </ul>
        </div>
      </div>

      {/* 7.3 */}
      <div id="point-7-3" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Database className="text-indigo-600" size={20} /> 7.3 AUTOMATED DELETION MECHANISMS
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.3.1 Cookie Expiration Technology:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Browser-Level Expiration:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>All persistent cookies include explicit expiration timestamp;</li>
                        <li>Browser automatically deletes expired cookies;</li>
                        <li>Platform does not rely solely on browser expiration for backend data deletion;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Server-Side Session Management:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Session cookies invalidated server-side at expiration;</li>
                        <li>Session data purged from session store (Redis/Memcached);</li>
                        <li>Database session records archived then deleted per retention schedule;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.3.2 Automated Backend Deletion Systems:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Daily Deletion Job:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Executes at 02:00 IST daily;</li>
                        <li>Identifies cookie-derived data exceeding retention period;</li>
                        <li>Applies deletion logic respecting regulatory retention requirements;</li>
                        <li>Generates deletion audit log;</li>
                    </ul>
                    <div className="bg-slate-900 text-slate-300 p-4 rounded mt-2 font-mono text-xs overflow-x-auto">
                        <pre>{`FOR EACH cookie_record IN database:
    IF cookie_record.category == "regulatory_required":
        IF current_date > (transaction_date + regulatory_retention_period):
            DELETE cookie_record
            LOG deletion WITH reason="regulatory_retention_expired"
    ELSE IF cookie_record.category == "consent_based":
        IF consent_withdrawn AND current_date > (withdrawal_date + grace_period):
            DELETE cookie_record
            LOG deletion WITH reason="consent_withdrawn"
        ELSE IF current_date > (last_active_date + retention_period):
            DELETE cookie_record
            LOG deletion WITH reason="retention_period_expired"
    ELSE IF cookie_record.category == "session":
        IF current_date > (session_end + immediate_deletion):
            DELETE cookie_record
            LOG deletion WITH reason="session_expired"`}</pre>
                    </div>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Weekly Aggregation and Anonymization Job:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Executes every Sunday at 03:00 IST;</li>
                        <li>Aggregates granular analytics data to summary statistics;</li>
                        <li>Deletes individual-level data after aggregation;</li>
                        <li>Applies k-anonymity and differential privacy techniques;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Monthly Audit and Cleanup:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>First day of month, 04:00 IST;</li>
                        <li>Comprehensive scan for orphaned data;</li>
                        <li>Identifies data without valid retention justification;</li>
                        <li>Flags anomalies for manual review;</li>
                        <li>Deletes confirmed unnecessary data;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.3.3 Secure Deletion Standards:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Cryptographic Erasure:</strong> For encrypted data, destruction of encryption keys renders data irretrievable; Key deletion logged and verified; Encrypted data overwritten or securely deleted post-key destruction;</li>
                <li>(b) <strong>Database Deletion:</strong> Hard DELETE operations (not soft delete/archive) for data past retention; Database vacuum operations to reclaim storage; Backup deletion coordinated across all backup generations;</li>
                <li>(c) <strong>Verification:</strong> Post-deletion verification queries confirm absence of data; Quarterly audit samples random deletion operations for compliance;</li>
            </ul>
        </div>
      </div>

      {/* 7.4 */}
      <div id="point-7-4" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Trash2 className="text-indigo-600" size={20} /> 7.4 USER-INITIATED DELETION PROCEDURES
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.4.1 Self-Service Cookie Deletion:</h4>
            
            <div className="ml-4 mb-4">
                <p className="text-slate-700 font-semibold mb-2">(a) Browser-Based Deletion:</p>
                <p className="text-slate-600 mb-2">Platform provides detailed instructions at [URL]/cookie-deletion:</p>
                <ul className="list-none pl-4 text-slate-600 space-y-3">
                    <li><strong>Google Chrome:</strong> 1. Settings {'>'} Privacy and Security {'>'} Cookies and other site data {'>'} See all cookies and site data; 2. Search for "preiposip.com"; 3. Click trash icon to remove all Platform cookies; 4. Alternatively: Settings {'>'} Privacy and Security {'>'} Clear browsing data {'>'} Cookies and other site data {'>'} Time range: All time</li>
                    <li><strong>Mozilla Firefox:</strong> 1. Options {'>'} Privacy & Security {'>'} Cookies and Site Data {'>'} Manage Data; 2. Search for "preiposip.com"; 3. Click "Remove Selected" or "Remove All"</li>
                    <li><strong>Apple Safari:</strong> 1. Preferences {'>'} Privacy {'>'} Manage Website Data; 2. Search for "preiposip.com"; 3. Click "Remove" or "Remove All"</li>
                    <li><strong>Microsoft Edge:</strong> 1. Settings {'>'} Privacy, search, and services {'>'} Choose what to clear {'>'} Cookies and other site data; 2. Clear now</li>
                    <li><strong>Mobile Browsers:</strong> iOS Safari: Settings {'>'} Safari {'>'} Clear History and Website Data; Android Chrome: Chrome {'>'} Settings {'>'} Privacy {'>'} Clear browsing data {'>'} Cookies and site data</li>
                </ul>
            </div>

            <div className="ml-4 mb-4">
                <p className="text-slate-700 font-semibold mb-2">(b) Platform Cookie Preference Center:</p>
                <p className="text-slate-600 mb-1">Accessible via footer link on every page or User account dashboard:</p>
                <ol className="list-decimal pl-6 text-slate-600 space-y-1">
                    <li>Navigate to "Cookie Settings" or "Manage Cookies"</li>
                    <li>Toggle off all optional cookie categories</li>
                    <li>Click "Save Preferences" - immediate effect on future cookie deployment</li>
                    <li>Existing cookies: Platform instructs browser to delete via JavaScript commands</li>
                    <li>Backend data: User can request deletion via "Delete My Cookie Data" button</li>
                </ol>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.4.2 Formal Deletion Requests:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Data Subject Access Request (DSAR) - Erasure:</p>
                    <p className="text-slate-600 mb-1">Per Part 4, Section 4.5 of this Policy, Users may submit formal erasure requests:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Email to dpo@preiposip.com with subject "Cookie Data Deletion Request"</li>
                        <li>Online form at [URL]/data-deletion</li>
                        <li>Specify cookie categories for deletion or request comprehensive deletion</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Processing Timeline:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Acknowledgment: 48 hours</li>
                        <li>Identity verification: 3-5 business days</li>
                        <li>Assessment of deletion request: 7 business days</li>
                        <li>Deletion execution: 15 business days from verification</li>
                        <li>Confirmation notification: Within 30 days total</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Deletion Scope Assessment:</p>
                    <p className="text-slate-600">Platform determines: Which cookies can be deleted immediately (consent-based, no regulatory retention); Which cookies subject to regulatory retention (PMLA, SEBI) - User informed of inability to delete; Which cookies tied to ongoing contractual relationship - User offered service suspension or account closure;</p>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.4.3 Account Closure and Comprehensive Deletion:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Account Closure Request:</p>
                    <p className="text-slate-600">Users may close accounts via: Account settings {'>'} Close Account; Email request to support@preiposip.com; Written request to registered office</p>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Pre-Closure Requirements:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Settlement of all pending transactions;</li>
                        <li>Withdrawal of invested funds;</li>
                        <li>Resolution of outstanding obligations;</li>
                        <li>Confirmation of closure intent (cooling-off period: 30 days);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Post-Closure Data Retention:</p>
                    <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                        <table className="w-full text-sm text-left text-slate-600">
                            <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th className="px-6 py-3">Data Category</th>
                                    <th className="px-6 py-3">Immediate Deletion</th>
                                    <th className="px-6 py-3">Regulatory Retention</th>
                                    <th className="px-6 py-3">Final Deletion Timeline</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Session and functional cookies</td><td className="px-6 py-4">Yes</td><td className="px-6 py-4">N/A</td><td className="px-6 py-4">Immediate</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Analytics cookies (non-investment)</td><td className="px-6 py-4">Yes</td><td className="px-6 py-4">N/A</td><td className="px-6 py-4">30 days</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Marketing cookies</td><td className="px-6 py-4">Yes</td><td className="px-6 py-4">N/A</td><td className="px-6 py-4">Immediate</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Transaction records</td><td className="px-6 py-4">No</td><td className="px-6 py-4">PMLA (5 years) + SEBI (10 years)</td><td className="px-6 py-4">10 years from last transaction</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">KYC documents</td><td className="px-6 py-4">No</td><td className="px-6 py-4">PMLA (5 years from account closure)</td><td className="px-6 py-4">5 years from closure</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Investment advisory records</td><td className="px-6 py-4">No</td><td className="px-6 py-4">SEBI (10 years)</td><td className="px-6 py-4">10 years from last advice</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Communication records</td><td className="px-6 py-4">No</td><td className="px-6 py-4">3 years (limitation period)</td><td className="px-6 py-4">3 years from closure</td></tr>
                                <tr className="bg-white"><td className="px-6 py-4">Aggregate analytics (anonymized)</td><td className="px-6 py-4">No</td><td className="px-6 py-4">Business intelligence</td><td className="px-6 py-4">Retained indefinitely (anonymized)</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(d) User Notification:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>At account closure: Detailed explanation of what data is deleted immediately vs. retained; Specific retention periods and legal basis for each category; Confirmation that retained data will not be used for marketing or active processing; Access restrictions: retained data accessible only to compliance team and regulators; Automated deletion triggers at end of retention periods;</li>
                    </ul>
                </div>
            </div>
        </div>
      </div>

      {/* 7.5 */}
      <div id="point-7-5" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Database className="text-indigo-600" size={20} /> 7.5 DATA MINIMIZATION PRINCIPLES AND PRACTICES
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.5.1 Collection Minimization:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Purpose Test:</strong> Before deploying any cookie, Platform verifies: Specific, explicit, legitimate purpose identified; Cookie is necessary and proportionate to achieve purpose; No less intrusive alternative available; Legal basis for processing exists;</li>
                <li>(b) <strong>Data Element Minimization:</strong> Cookies collect minimum data necessary: Session cookies: opaque identifiers only, no personal attributes; Functional cookies: preference codes/flags, not detailed personal information; Analytics cookies: pseudonymized identifiers, aggregated where possible; No collection of SPDI in cookies unless absolutely necessary and encrypted;</li>
                <li>(c) <strong>Examples of Minimization:</strong> Location cookie stores: country code (IN) rather than precise GPS coordinates; Language preference: ISO code (en, hi) rather than full browser fingerprint; Investment profile: risk category (conservative, moderate, aggressive) rather than detailed financial information;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.5.2 Processing Minimization:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Access Controls:</strong> Cookie-derived data accessible only to personnel with legitimate need-to-know: Analytics team: access to analytics cookies, not KYC cookies; Compliance team: access to KYC/AML cookies, not marketing cookies; Marketing team: access to consent-based advertising cookies only;</li>
                <li>(b) <strong>Purpose Limitation:</strong> Cookie data used only for specified purposes: Analytics cookies: not used for individualized decision-making without consent; Session cookies: not shared with third parties or used for profiling; Consent-based cookies: use immediately ceased upon consent withdrawal;</li>
                <li>(c) <strong>Automated Processing Limitations:</strong> Automated decision-making based on cookies restricted to: Strictly necessary operations (fraud detection, security); Explicitly consented purposes (personalized investment recommendations); Non-consequential decisions (content personalization for UX); Significant decisions involving legal effects or similar impact require human oversight.</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.5.3 Storage Minimization:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Retention Limits:</p>
                    <p className="text-slate-600">Per comprehensive schedule in Section 7.2: No indefinite retention absent specific legal requirement; Active review of retention periods annually; Reduction of retention periods where legal/business justification diminishes;</p>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Data Archival Strategy:</p>
                    <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                        <table className="w-full text-sm text-left text-slate-600">
                            <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th className="px-6 py-3">Data Age</th>
                                    <th className="px-6 py-3">Storage Tier</th>
                                    <th className="px-6 py-3">Accessibility</th>
                                    <th className="px-6 py-3">Use Restrictions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">0-90 days</td><td className="px-6 py-4">Hot storage (SSD)</td><td className="px-6 py-4">Real-time access</td><td className="px-6 py-4">Active use permitted per purpose</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">91 days - 1 year</td><td className="px-6 py-4">Warm storage (HDD)</td><td className="px-6 py-4">Retrieval within minutes</td><td className="px-6 py-4">Audit, investigation, User rights requests</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">1-5 years</td><td className="px-6 py-4">Cold storage (AWS Glacier)</td><td className="px-6 py-4">Retrieval within hours</td><td className="px-6 py-4">Regulatory compliance, legal requirements only</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">5-10 years</td><td className="px-6 py-4">Deep archive (Glacier Deep Archive)</td><td className="px-6 py-4">Retrieval within 12 hours</td><td className="px-6 py-4">SEBI requirements, legal holds only</td></tr>
                                <tr className="bg-white"><td className="px-6 py-4">10+ years</td><td className="px-6 py-4">Deletion</td><td className="px-6 py-4">N/A</td><td className="px-6 py-4">Complete deletion (no legal basis for retention)</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Storage Optimization:</p>
                    <p className="text-slate-600">Compression of archived data (up to 90% size reduction); Deduplication of redundant cookie records; Aggregation and anonymization reducing storage footprint;</p>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.5.4 Sharing Minimization:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Third-Party Sharing Limitations:</strong> Per Part 5, sharing cookie data with third parties only when: Strictly necessary for service provision; User has provided explicit consent; Legal obligation requires disclosure; Contractual necessity for Platform operations;</li>
                <li>(b) <strong>Data Disclosed vs. Data Withheld:</strong> Platform discloses to third parties: Minimum data necessary for specified service; Pseudonymized identifiers where possible; Aggregated data preferentially over individual-level data; Platform withholds from third parties: SPDI unless encrypted and third party has equivalent security; KYC data unless third party is authorized KRA or compliance service; Comprehensive User profiles (selective attribute sharing only);</li>
            </ul>
        </div>
      </div>

      {/* 7.6 */}
      <div id="point-7-6" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <AlertOctagon className="text-indigo-600" size={20} /> 7.6 RETENTION SCHEDULE REVIEW AND UPDATE PROCESS
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.6.1 Annual Retention Policy Review:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Review Committee:</strong> Data Protection Officer (Chair); Chief Information Security Officer; Chief Compliance Officer (SEBI/PMLA); Legal Counsel; Chief Technology Officer;</li>
                <li>(b) <strong>Review Scope:</strong> Assessment of current retention periods against legal requirements; Evaluation of business necessity for retention; Analysis of User feedback and erasure requests; Review of international best practices and regulatory developments; Cost-benefit analysis of extended retention;</li>
                <li>(c) <strong>Review Triggers:</strong> Annual scheduled review (January each year); Change in applicable law or regulation; Regulatory guidance or enforcement action; Significant data breach or security incident; Emerging international standards (e.g., new GDPR guidance);</li>
                <li>(d) <strong>Documentation:</strong> Meeting minutes with rationale for retention decisions; Updated retention schedule with version control; Impact assessment of any retention period changes; Communication plan for Users if material changes;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.6.2 Continuous Monitoring:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Quarterly Metrics:</strong> Volume of data stored by cookie category; Number of automated deletions executed; User-initiated deletion requests processed; Regulatory retention obligations complied with; Storage costs by data age and tier;</li>
                <li>(b) <strong>Anomaly Detection:</strong> Data volumes exceeding expected ranges; Deletion job failures or errors; Data persisting beyond retention periods; Unauthorized access to archived data;</li>
                <li>(c) <strong>Compliance Auditing:</strong> Semi-annual internal audit of retention compliance; Annual external audit by independent auditor; Verification of deletion processes through sampling; Assessment of legal retention requirement adherence;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.6.3 User Communication of Retention Updates:</h4>
            <p className="text-slate-600 mb-2">When retention periods are modified:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Material Increases (Longer Retention):</strong> 60 days advance notice via email; Clear explanation of reasons for increased retention; Legal basis and regulatory requirement cited; User right to object or close account; Update to Cookie Policy with version tracking;</li>
                <li>(b) <strong>Material Decreases (Shorter Retention):</strong> Notice via email and Platform announcement; Explanation of enhanced privacy protection; Implementation timeline;</li>
                <li>(c) <strong>Non-Material Changes:</strong> Update to Cookie Policy with change log; Notification in next scheduled User communication;</li>
            </ul>
        </div>
      </div>

      {/* 7.7 */}
      <div id="point-7-7" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <AlertOctagon className="text-indigo-600" size={20} /> 7.7 SPECIAL RETENTION CONSIDERATIONS
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.7.1 Litigation Hold:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Triggering Events:</strong> Receipt of legal notice, demand letter, or lawsuit filing; Regulatory investigation or enforcement action notice; Internal investigation revealing potential legal claims; Reasonable anticipation of litigation;</li>
                <li>(b) <strong>Scope of Hold:</strong> Suspension of automated deletion for data potentially relevant to litigation; Identification of custodians and data sources; Preservation of data in current state; Documentation of hold implementation;</li>
                <li>(c) <strong>Duration:</strong> Until litigation concluded and appeal periods expired; Until regulatory investigation closed; Until legal counsel provides written release authorization;</li>
                <li>(d) <strong>User Communication:</strong> Notification to affected Users that data cannot be deleted due to legal hold; Explanation of legal requirement without disclosing sensitive case details; Commitment to deletion upon hold release;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.7.2 Deceased User Data:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Notification Process:</strong> Legal heirs/nominees notify Platform of User's death; Submission of death certificate and succession documents; Verification of legal heir's authority;</li>
                <li>(b) <strong>Data Handling:</strong> Investment and financial data: handled per applicable succession laws and securities regulations; Personal cookie data: deleted within 90 days of death verification (unless regulatory retention applies); Account access: terminated immediately; Outstanding dues/credits: settled with legal heirs;</li>
                <li>(c) <strong>Memorial Options:</strong> Platform does not offer memorialization features (unlike social media); All data deleted post-settlement of account (subject to regulatory retention);</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.7.3 Minor User Data (Inadvertent Collection):</h4>
            <p className="text-slate-600 mb-1">Platform prohibits minors ({`<`}18 years) from using services. If inadvertently collected:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Identification:</strong> User self-reports age below 18; Age verification during KYC reveals minor status; Third-party report or regulatory notice;</li>
                <li>(b) <strong>Response:</strong> Immediate account suspension; All cookies deleted immediately; All personal data deleted within 7 days; No regulatory retention obligation (PMLA/SEBI apply to adults only); Parental notification if contact information available;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">7.7.4 Cross-Border Retention Complexity:</h4>
            <p className="text-slate-600 mb-1">For Users in multiple jurisdictions with conflicting retention requirements:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>EEA Users:</strong> GDPR retention principles (necessity, proportionality); Potential conflict with Indian PMLA 5-year requirement; Resolution: retain data in India to comply with Indian law, but restrict EEA data access/processing to minimum necessary;</li>
                <li>(b) <strong>California Users:</strong> CCPA/CPRA allow retention for legal obligations; No conflict with Indian retention requirements;</li>
                <li>(c) <strong>Jurisdiction-Specific Retention:</strong> Where technically feasible, implement jurisdiction-specific retention schedules; where not feasible, apply maximum retention period with safeguards.</li>
            </ul>
        </div>
      </div>
    </section>
  );
}
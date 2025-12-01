'use client';

import React from 'react';
import { Server, CreditCard, Users, BarChart2 } from 'lucide-react';

export default function CookiePolicyPart9() {
  return (
    <section id="part-5-registry" className="section mb-12">
      <div id="point-5-2" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Server className="text-indigo-600" size={20} /> 5.2 COMPREHENSIVE THIRD-PARTY REGISTRY
        </h3>

        <div className="mb-8">
            <h4 className="text-lg font-bold text-slate-700 mb-3">5.2.1 Analytics and Performance Service Providers:</h4>
            <div className="overflow-x-auto mb-6 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Provider</th>
                            <th className="px-6 py-3">Service</th>
                            <th className="px-6 py-3">Data Shared</th>
                            <th className="px-6 py-3">Purpose</th>
                            <th className="px-6 py-3">Data Location</th>
                            <th className="px-6 py-3">Legal Basis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-semibold">Google LLC</td>
                            <td className="px-6 py-4">Google Analytics</td>
                            <td className="px-6 py-4">IP address (anonymized), browser info, page views, session data</td>
                            <td className="px-6 py-4">Platform analytics and performance monitoring</td>
                            <td className="px-6 py-4">United States</td>
                            <td className="px-6 py-4">User consent / Legitimate interests</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-semibold">Hotjar Ltd.</td>
                            <td className="px-6 py-4">Heatmaps & Session Recording</td>
                            <td className="px-6 py-4">Mouse movements, clicks, scroll depth, form interactions</td>
                            <td className="px-6 py-4">User experience optimization</td>
                            <td className="px-6 py-4">Malta (EEA)</td>
                            <td className="px-6 py-4">User consent</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-semibold">Mixpanel, Inc.</td>
                            <td className="px-6 py-4">Product Analytics</td>
                            <td className="px-6 py-4">Event tracking, user flows, feature adoption</td>
                            <td className="px-6 py-4">Product development and improvement</td>
                            <td className="px-6 py-4">United States</td>
                            <td className="px-6 py-4">User consent</td>
                        </tr>
                        <tr className="bg-white">
                            <td className="px-6 py-4 font-semibold">New Relic, Inc.</td>
                            <td className="px-6 py-4">Application Performance Monitoring</td>
                            <td className="px-6 py-4">Server response times, error logs, API performance</td>
                            <td className="px-6 py-4">Technical performance optimization</td>
                            <td className="px-6 py-4">United States</td>
                            <td className="px-6 py-4">Legitimate interests</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div className="mb-8">
            <h4 className="text-lg font-bold text-slate-700 mb-3">5.2.2 Advertising and Marketing Service Providers:</h4>
            <div className="overflow-x-auto mb-6 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Provider</th>
                            <th className="px-6 py-3">Service</th>
                            <th className="px-6 py-3">Data Shared</th>
                            <th className="px-6 py-3">Purpose</th>
                            <th className="px-6 py-3">Data Location</th>
                            <th className="px-6 py-3">Legal Basis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-semibold">Meta Platforms, Inc. (Facebook)</td>
                            <td className="px-6 py-4">Facebook Pixel</td>
                            <td className="px-6 py-4">User identifiers, browsing behavior, conversion events</td>
                            <td className="px-6 py-4">Advertising and retargeting</td>
                            <td className="px-6 py-4">United States</td>
                            <td className="px-6 py-4">User consent</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-semibold">Google LLC</td>
                            <td className="px-6 py-4">Google Ads / DoubleClick</td>
                            <td className="px-6 py-4">Device IDs, ad interactions, conversion tracking</td>
                            <td className="px-6 py-4">Advertising campaign management</td>
                            <td className="px-6 py-4">United States</td>
                            <td className="px-6 py-4">User consent</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-semibold">LinkedIn Corporation</td>
                            <td className="px-6 py-4">LinkedIn Insight Tag</td>
                            <td className="px-6 py-4">Professional profile data, page views, conversions</td>
                            <td className="px-6 py-4">B2B advertising and targeting</td>
                            <td className="px-6 py-4">United States</td>
                            <td className="px-6 py-4">User consent</td>
                        </tr>
                        <tr className="bg-white">
                            <td className="px-6 py-4 font-semibold">Twitter, Inc.</td>
                            <td className="px-6 py-4">Twitter Ads</td>
                            <td className="px-6 py-4">Tweet interactions, user identifiers, website visits</td>
                            <td className="px-6 py-4">Social media advertising</td>
                            <td className="px-6 py-4">United States</td>
                            <td className="px-6 py-4">User consent</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div className="mb-8">
            <h4 className="text-lg font-bold text-slate-700 mb-3">5.2.3 Customer Support and Communication Providers:</h4>
            <div className="overflow-x-auto mb-6 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Provider</th>
                            <th className="px-6 py-3">Service</th>
                            <th className="px-6 py-3">Data Shared</th>
                            <th className="px-6 py-3">Purpose</th>
                            <th className="px-6 py-3">Data Location</th>
                            <th className="px-6 py-3">Legal Basis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-semibold">Zendesk, Inc.</td>
                            <td className="px-6 py-4">Customer Support Platform</td>
                            <td className="px-6 py-4">User queries, email addresses, support tickets</td>
                            <td className="px-6 py-4">Customer service delivery</td>
                            <td className="px-6 py-4">United States</td>
                            <td className="px-6 py-4">Contractual necessity</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-semibold">Intercom, Inc.</td>
                            <td className="px-6 py-4">Live Chat & Messaging</td>
                            <td className="px-6 py-4">Chat transcripts, user profiles, behavioral triggers</td>
                            <td className="px-6 py-4">Real-time customer engagement</td>
                            <td className="px-6 py-4">United States</td>
                            <td className="px-6 py-4">User consent</td>
                        </tr>
                        <tr className="bg-white">
                            <td className="px-6 py-4 font-semibold">Twilio Inc.</td>
                            <td className="px-6 py-4">SMS & Communication APIs</td>
                            <td className="px-6 py-4">Phone numbers, SMS content, delivery status</td>
                            <td className="px-6 py-4">Transactional notifications</td>
                            <td className="px-6 py-4">United States</td>
                            <td className="px-6 py-4">Contractual necessity</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div className="mb-8">
            <h4 className="text-lg font-bold text-slate-700 mb-3">5.2.4 Infrastructure and Technical Service Providers:</h4>
            <div className="overflow-x-auto mb-6 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Provider</th>
                            <th className="px-6 py-3">Service</th>
                            <th className="px-6 py-3">Data Shared</th>
                            <th className="px-6 py-3">Purpose</th>
                            <th className="px-6 py-3">Data Location</th>
                            <th className="px-6 py-3">Legal Basis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-semibold">Amazon Web Services, Inc.</td>
                            <td className="px-6 py-4">Cloud Hosting</td>
                            <td className="px-6 py-4">All platform data (encrypted)</td>
                            <td className="px-6 py-4">Infrastructure hosting and storage</td>
                            <td className="px-6 py-4">India (Mumbai Region) with backup in Singapore</td>
                            <td className="px-6 py-4">Contractual necessity</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-semibold">Cloudflare, Inc.</td>
                            <td className="px-6 py-4">CDN & Security</td>
                            <td className="px-6 py-4">IP addresses, request logs, security threat data</td>
                            <td className="px-6 py-4">Content delivery and DDoS protection</td>
                            <td className="px-6 py-4">Global (with India PoPs)</td>
                            <td className="px-6 py-4">Legitimate interests (security)</td>
                        </tr>
                        <tr className="bg-white">
                            <td className="px-6 py-4 font-semibold">MongoDB, Inc.</td>
                            <td className="px-6 py-4">Database Services</td>
                            <td className="px-6 py-4">User data, transaction records</td>
                            <td className="px-6 py-4">Database management</td>
                            <td className="px-6 py-4">India with backup in Singapore</td>
                            <td className="px-6 py-4">Contractual necessity</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div className="mb-8">
            <h4 className="text-lg font-bold text-slate-700 mb-3">5.2.5 Payment and Financial Service Providers:</h4>
            <div className="overflow-x-auto mb-6 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Provider</th>
                            <th className="px-6 py-3">Service</th>
                            <th className="px-6 py-3">Data Shared</th>
                            <th className="px-6 py-3">Purpose</th>
                            <th className="px-6 py-3">Data Location</th>
                            <th className="px-6 py-3">Legal Basis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-semibold">Razorpay Software Pvt. Ltd.</td>
                            <td className="px-6 py-4">Payment Gateway</td>
                            <td className="px-6 py-4">Financial information, transaction details, UPI IDs</td>
                            <td className="px-6 py-4">Payment processing</td>
                            <td className="px-6 py-4">India</td>
                            <td className="px-6 py-4">Contractual necessity</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-semibold">PayU Payments Pvt. Ltd.</td>
                            <td className="px-6 py-4">Payment Gateway</td>
                            <td className="px-6 py-4">Card details (tokenized), payment status</td>
                            <td className="px-6 py-4">Alternative payment processing</td>
                            <td className="px-6 py-4">India</td>
                            <td className="px-6 py-4">Contractual necessity</td>
                        </tr>
                        <tr className="bg-white">
                            <td className="px-6 py-4 font-semibold">NSE Clearing Limited</td>
                            <td className="px-6 py-4">Securities Clearing</td>
                            <td className="px-6 py-4">Transaction details, demat account info</td>
                            <td className="px-6 py-4">Securities settlement</td>
                            <td className="px-6 py-4">India</td>
                            <td className="px-6 py-4">Legal obligation</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div className="mb-8">
            <h4 className="text-lg font-bold text-slate-700 mb-3">5.2.6 Compliance and Verification Service Providers:</h4>
            <div className="overflow-x-auto mb-6 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Provider</th>
                            <th className="px-6 py-3">Service</th>
                            <th className="px-6 py-3">Data Shared</th>
                            <th className="px-6 py-3">Purpose</th>
                            <th className="px-6 py-3">Data Location</th>
                            <th className="px-6 py-3">Legal Basis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-semibold">NSDL e-Governance Infrastructure Ltd.</td>
                            <td className="px-6 py-4">e-KYC Services</td>
                            <td className="px-6 py-4">Aadhaar number (encrypted), biometric data</td>
                            <td className="px-6 py-4">Identity verification</td>
                            <td className="px-6 py-4">India</td>
                            <td className="px-6 py-4">Legal obligation (PMLA)</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-semibold">CDSL Ventures Ltd. (CVL)</td>
                            <td className="px-6 py-4">KYC Registration Agency</td>
                            <td className="px-6 py-4">PAN, identity documents, KYC records</td>
                            <td className="px-6 py-4">Centralized KYC registry</td>
                            <td className="px-6 py-4">India</td>
                            <td className="px-6 py-4">Legal obligation (SEBI KYC norms)</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-semibold">TransUnion CIBIL Ltd.</td>
                            <td className="px-6 py-4">Credit Bureau</td>
                            <td className="px-6 py-4">Financial history, credit scores, loan accounts</td>
                            <td className="px-6 py-4">Credit risk assessment</td>
                            <td className="px-6 py-4">India</td>
                            <td className="px-6 py-4">Legitimate interests (fraud prevention)</td>
                        </tr>
                        <tr className="bg-white">
                            <td className="px-6 py-4 font-semibold">Dow Jones Risk & Compliance</td>
                            <td className="px-6 py-4">Sanctions & PEP Screening</td>
                            <td className="px-6 py-4">User identities, business relationships</td>
                            <td className="px-6 py-4">AML/CFT compliance</td>
                            <td className="px-6 py-4">United States</td>
                            <td className="px-6 py-4">Legal obligation (PMLA)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div className="mb-8">
            <h4 className="text-lg font-bold text-slate-700 mb-3">5.2.7 Social Media Integration Providers:</h4>
            <div className="overflow-x-auto mb-6 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Provider</th>
                            <th className="px-6 py-3">Service</th>
                            <th className="px-6 py-3">Data Shared</th>
                            <th className="px-6 py-3">Purpose</th>
                            <th className="px-6 py-3">Data Location</th>
                            <th className="px-6 py-3">Legal Basis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-semibold">Google LLC</td>
                            <td className="px-6 py-4">YouTube Embedded Videos</td>
                            <td className="px-6 py-4">Video viewing data, device IDs</td>
                            <td className="px-6 py-4">Educational content delivery</td>
                            <td className="px-6 py-4">United States</td>
                            <td className="px-6 py-4">User consent</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4 font-semibold">Meta Platforms, Inc.</td>
                            <td className="px-6 py-4">Facebook Social Plugins</td>
                            <td className="px-6 py-4">Social sharing activity, profile data</td>
                            <td className="px-6 py-4">Content sharing functionality</td>
                            <td className="px-6 py-4">United States</td>
                            <td className="px-6 py-4">User consent</td>
                        </tr>
                        <tr className="bg-white">
                            <td className="px-6 py-4 font-semibold">LinkedIn Corporation</td>
                            <td className="px-6 py-4">LinkedIn Share Button</td>
                            <td className="px-6 py-4">Professional network sharing</td>
                            <td className="px-6 py-4">Professional content distribution</td>
                            <td className="px-6 py-4">United States</td>
                            <td className="px-6 py-4">User consent</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
      </div>
    </section>
  );
}
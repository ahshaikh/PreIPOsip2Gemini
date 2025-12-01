'use client';

import React from 'react';
import { Megaphone, Share2, ShieldCheck, Clock } from 'lucide-react';

export default function CookiePolicyPart3() {
  return (
    <section id="part-3" className="section mb-12">
      {/* 2.5 */}
      <div id="point-2-5" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Megaphone className="text-indigo-600" size={20} /> 2.5 TARGETING AND ADVERTISING COOKIES
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.5.1 Legal Basis for Processing:</h4>
            <p className="text-slate-600 mb-2">Targeting and Advertising Cookies require explicit, informed, and freely given User consent pursuant to:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) Rule 5(3) of the IT Rules, 2011 requiring prior permission for commercial communications;</li>
                <li>(b) SEBI restrictions on financial product advertising to ensure appropriate investor targeting;</li>
                <li>(c) GDPR standards (for EEA Users) requiring opt-in consent for behavioral advertising;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.5.2 Functional Purpose:</h4>
            <p className="text-slate-600 mb-2">These cookies enable:</p>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Relevant Content Delivery:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Display of Pre-IPO opportunities matching User investment profile;</li>
                        <li>Personalized educational content based on User sophistication level;</li>
                        <li>Sector-specific market insights aligned with User interests;</li>
                        <li>Tailored investment webinar and event recommendations;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Retargeting and Remarketing:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Display of Platform advertisements on third-party websites;</li>
                        <li>Email campaign personalization based on browsing history;</li>
                        <li>Frequency capping to prevent advertisement fatigue;</li>
                        <li>Cross-device tracking for consistent User experience;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) SEBI-Compliant Investor Targeting:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Verification of Accredited Investor status before displaying certain opportunities;</li>
                        <li>Risk profiling to ensure suitable investment recommendations;</li>
                        <li>Restriction of high-risk products to qualified investors;</li>
                        <li>Compliance with SEBI appropriateness and suitability requirements;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(d) Advertising Performance Measurement:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Click-through rates and conversion attribution;</li>
                        <li>Return on advertising spend (ROAS) calculation;</li>
                        <li>Campaign effectiveness analysis;</li>
                        <li>Cost per acquisition (CPA) optimization;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.5.3 Technical Specifications:</h4>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Cookie Name</th>
                            <th className="px-6 py-3">Type</th>
                            <th className="px-6 py-3">Duration</th>
                            <th className="px-6 py-3">Domain</th>
                            <th className="px-6 py-3">Data Collected</th>
                            <th className="px-6 py-3">Third-Party Provider</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">_fbp</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">90 days</td><td className="px-6 py-4">.facebook.com</td><td className="px-6 py-4">Browser and user identifier</td><td className="px-6 py-4">Facebook Pixel</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">IDE</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">13 months</td><td className="px-6 py-4">.doubleclick.net</td><td className="px-6 py-4">User preferences and targeting</td><td className="px-6 py-4">Google DoubleClick</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">_gcl_au</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">90 days</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Google AdSense visitor tracking</td><td className="px-6 py-4">Google AdSense</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">personalization_id</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">24 months</td><td className="px-6 py-4">.twitter.com</td><td className="px-6 py-4">Twitter advertising identifier</td><td className="px-6 py-4">Twitter Ads</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">li_sugr</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">90 days</td><td className="px-6 py-4">.linkedin.com</td><td className="px-6 py-4">LinkedIn advertising tracking</td><td className="px-6 py-4">LinkedIn Ads</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">retargeting_pixel</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">180 days</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Retargeting campaign data</td><td className="px-6 py-4">Internal Retargeting</td></tr>
                        <tr className="bg-white"><td className="px-6 py-4">investor_profile</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">12 months</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Investment preferences and risk profile</td><td className="px-6 py-4">Internal Targeting</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.5.4 Third-Party Data Sharing:</h4>
            <p className="text-slate-600 mb-2">When Users consent to Targeting and Advertising Cookies, certain anonymized data may be shared with:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1 mb-2">
                <li>(a) <strong>Advertising Networks:</strong> Google Ads, Facebook Business, LinkedIn Marketing Solutions, Twitter Ads;</li>
                <li>(b) <strong>Data Management Platforms (DMPs):</strong> For audience segmentation and targeting optimization;</li>
                <li>(c) <strong>Demand-Side Platforms (DSPs):</strong> For programmatic advertising placement;</li>
                <li>(d) <strong>Analytics Providers:</strong> For cross-platform attribution analysis;</li>
            </ul>
            <p className="text-slate-700 font-semibold mb-1">Data Sharing Safeguards:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>All third parties are contractually bound by data processing agreements;</li>
                <li>Data is shared in pseudonymized or aggregated form wherever possible;</li>
                <li>Users retain the right to withdraw consent at any time;</li>
                <li>Third parties are prohibited from using data for purposes other than those specified;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.5.5 SEBI Compliance Considerations:</h4>
            <p className="text-slate-600 mb-2">Given the Platform's focus on Pre-IPO securities, advertising activities are subject to:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) SEBI (Investment Advisers) Regulations, 2013 - Regulation 18 (Advertisement Code):
                    <ul className="list-disc pl-6 mt-1">
                        <li>All advertisements must be clear, fair, and not misleading;</li>
                        <li>Risk warnings must accompany investment opportunity communications;</li>
                        <li>Past performance disclaimers must be prominently displayed;</li>
                        <li>No guarantees of returns may be explicitly or implicitly made;</li>
                    </ul>
                </li>
                <li>(b) SEBI (Issue of Capital and Disclosure Requirements) Regulations, 2018:
                    <ul className="list-disc pl-6 mt-1">
                        <li>Restrictions on publicity prior to filing of offer documents;</li>
                        <li>Prohibition on making projections or forecasts;</li>
                        <li>Requirement for balanced disclosure of risks and benefits;</li>
                    </ul>
                </li>
                <li>(c) SEBI (Prohibition of Fraudulent and Unfair Trade Practices) Regulations, 2003:
                    <ul className="list-disc pl-6 mt-1">
                        <li>Prohibition on manipulative or deceptive communications;</li>
                        <li>Requirement for substantiated claims;</li>
                        <li>Prevention of artificial market creation;</li>
                    </ul>
                </li>
            </ul>
        </div>
      </div>

      {/* 2.6 */}
      <div id="point-2-6" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Share2 className="text-indigo-600" size={20} /> 2.6 SOCIAL MEDIA COOKIES
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.6.1 Legal Basis for Processing:</h4>
            <p className="text-slate-600 mb-2">Social Media Cookies are deployed based on:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) User consent through cookie preference settings;</li>
                <li>(b) Legitimate interests in facilitating content sharing and social engagement;</li>
                <li>(c) Contractual relationships with social media platforms as data processors;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.6.2 Functional Purpose:</h4>
            <p className="text-slate-600 mb-2">These cookies enable:</p>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Social Sharing Functionality:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>One-click sharing of Pre-IPO insights on social platforms;</li>
                        <li>Content recommendation tracking;</li>
                        <li>Social login integration (Login with Facebook, Google, LinkedIn);</li>
                        <li>Profile information import for streamlined registration;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Social Engagement Tracking:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Measurement of social media referral traffic;</li>
                        <li>Tracking of shared content performance;</li>
                        <li>Identification of influential users and brand advocates;</li>
                        <li>Social sentiment analysis;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Embedded Social Content:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Display of social media feeds and timelines;</li>
                        <li>Embedded YouTube videos for educational content;</li>
                        <li>LinkedIn profile verification for accredited investor status;</li>
                        <li>Twitter feeds for real-time market updates;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.6.3 Technical Specifications:</h4>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Cookie Name</th>
                            <th className="px-6 py-3">Type</th>
                            <th className="px-6 py-3">Duration</th>
                            <th className="px-6 py-3">Domain</th>
                            <th className="px-6 py-3">Data Collected</th>
                            <th className="px-6 py-3">Social Platform</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">datr / fr</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">24 months</td><td className="px-6 py-4">.facebook.com</td><td className="px-6 py-4">Browser identification and ad delivery</td><td className="px-6 py-4">Facebook</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">LOGIN_INFO / PREF</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">24 months</td><td className="px-6 py-4">.youtube.com</td><td className="px-6 py-4">User preferences and activity</td><td className="px-6 py-4">YouTube</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">lang / bcookie</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">Session/24 months</td><td className="px-6 py-4">.linkedin.com</td><td className="px-6 py-4">Language preference and browser ID</td><td className="px-6 py-4">LinkedIn</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">guest_id / personalization_id</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">24 months</td><td className="px-6 py-4">.twitter.com</td><td className="px-6 py-4">User identification for content</td><td className="px-6 py-4">Twitter</td></tr>
                        <tr className="bg-white"><td className="px-6 py-4">SID / HSID / SSID</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">24 months</td><td className="px-6 py-4">.google.com</td><td className="px-6 py-4">Google account integration</td><td className="px-6 py-4">Google</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.6.4 Third-Party Platform Privacy Policies:</h4>
            <p className="text-slate-600 mb-2">Users should review the privacy policies and cookie policies of social media platforms:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Facebook: https://www.facebook.com/privacy/explanation</li>
                <li>LinkedIn: https://www.linkedin.com/legal/privacy-policy</li>
                <li>Twitter: https://twitter.com/privacy</li>
                <li>YouTube/Google: https://policies.google.com/privacy</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.6.5 Data Processing Considerations:</h4>
            <p className="text-slate-600 mb-2">Social Media Cookies may result in:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) User data being transmitted to servers located outside India;</li>
                <li>(b) Association of Platform activity with User's social media profile;</li>
                <li>(c) Use of data for social platform's own advertising purposes;</li>
                <li>(d) Creation of User profiles across multiple websites and services;</li>
            </ul>
        </div>
      </div>

      {/* 2.7 */}
      <div id="point-2-7" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <ShieldCheck className="text-indigo-600" size={20} /> 2.7 KYC AND AML COMPLIANCE COOKIES
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.7.1 Legal Basis for Processing:</h4>
            <p className="text-slate-600 mb-2">These cookies are deployed pursuant to mandatory legal obligations under:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) Prevention of Money Laundering Act, 2002 - Section 12;</li>
                <li>(b) Prevention of Money Laundering (Maintenance of Records) Rules, 2005 - Rules 3 and 9;</li>
                <li>(c) SEBI (Know Your Client Registration Agency) Regulations, 2011;</li>
                <li>(d) Companies Act, 2013 - Section 92 (Register of members);</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.7.2 Functional Purpose:</h4>
            <p className="text-slate-600 mb-2">KYC and AML Compliance Cookies facilitate:</p>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Identity Verification:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Storage of KYC completion status;</li>
                        <li>Document verification workflow management;</li>
                        <li>Aadhaar-based e-KYC integration;</li>
                        <li>PAN card verification status;</li>
                        <li>Video-based identification (VKYC) session management;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Transaction Monitoring:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Detection of unusual transaction patterns;</li>
                        <li>Flagging of transactions exceeding prescribed thresholds;</li>
                        <li>Beneficial ownership verification;</li>
                        <li>Politically Exposed Person (PEP) screening;</li>
                        <li>Sanctions list matching;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Record-Keeping:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Audit trail of KYC document submissions;</li>
                        <li>Timestamp verification of compliance activities;</li>
                        <li>Periodic KYC updation reminders;</li>
                        <li>Customer due diligence (CDD) process tracking;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(d) Risk Assessment:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Customer risk categorization (low/medium/high);</li>
                        <li>Enhanced due diligence (EDD) triggering;</li>
                        <li>Source of wealth verification tracking;</li>
                        <li>Ongoing monitoring of business relationship;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.7.3 Technical Specifications:</h4>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Cookie Name</th>
                            <th className="px-6 py-3">Type</th>
                            <th className="px-6 py-3">Duration</th>
                            <th className="px-6 py-3">Domain</th>
                            <th className="px-6 py-3">Data Collected</th>
                            <th className="px-6 py-3">Purpose</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">kyc_status</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">12 months</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">KYC verification completion status</td><td className="px-6 py-4">Compliance verification</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">aml_check</td><td className="px-6 py-4">Session</td><td className="px-6 py-4">Session duration</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">AML screening results</td><td className="px-6 py-4">Risk assessment</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">document_verification</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">90 days</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Document verification status</td><td className="px-6 py-4">Identity confirmation</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">pep_screening</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">180 days</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">PEP and sanctions check status</td><td className="px-6 py-4">Regulatory screening</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">risk_category</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">12 months</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Customer risk classification</td><td className="px-6 py-4">Risk-based approach</td></tr>
                        <tr className="bg-white"><td className="px-6 py-4">cdd_timestamp</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">60 months</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Customer due diligence timestamps</td><td className="px-6 py-4">Record-keeping</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.7.4 Retention Period Justification:</h4>
            <p className="text-slate-600 text-justify">Pursuant to Rule 3 of the PML (Maintenance of Records) Rules, 2005, records of KYC and transaction data must be maintained for a period of five years from the date of transaction. Consequently, certain compliance cookies may be retained for extended periods to satisfy regulatory obligations.</p>
        </div>
      </div>

      {/* 2.8 */}
      <div id="point-2-8" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Clock className="text-indigo-600" size={20} /> 2.8 DATA RETENTION FRAMEWORK
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.8.1 General Retention Principles:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Necessity Principle:</strong> Cookie data is retained only for as long as necessary to fulfill the purposes for which it was collected;</li>
                <li>(b) <strong>Regulatory Compliance:</strong> Minimum retention periods are observed where mandated by law (e.g., 5 years for PMLA compliance);</li>
                <li>(c) <strong>Data Minimization:</strong> Periodic reviews are conducted to identify and delete obsolete cookie data;</li>
                <li>(d) <strong>User Rights:</strong> Users may request deletion of non-essential cookie data subject to legal obligations;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.8.2 Category-Specific Retention Periods:</h4>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Cookie Category</th>
                            <th className="px-6 py-3">Standard Retention</th>
                            <th className="px-6 py-3">Regulatory Minimum</th>
                            <th className="px-6 py-3">Legal Basis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Strictly Necessary</td><td className="px-6 py-4">Session to 30 days</td><td className="px-6 py-4">N/A</td><td className="px-6 py-4">Contractual necessity</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Functional</td><td className="px-6 py-4">Session to 12 months</td><td className="px-6 py-4">N/A</td><td className="px-6 py-4">User consent</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Performance/Analytics</td><td className="px-6 py-4">14 days to 24 months</td><td className="px-6 py-4">N/A</td><td className="px-6 py-4">Legitimate interests</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Targeting/Advertising</td><td className="px-6 py-4">90 days to 24 months</td><td className="px-6 py-4">N/A</td><td className="px-6 py-4">User consent</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Social Media</td><td className="px-6 py-4">Session to 24 months</td><td className="px-6 py-4">N/A</td><td className="px-6 py-4">User consent</td></tr>
                        <tr className="bg-white"><td className="px-6 py-4">KYC/AML Compliance</td><td className="px-6 py-4">60 months</td><td className="px-6 py-4">60 months</td><td className="px-6 py-4">Legal obligation (PMLA)</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.8.3 Automated Deletion Mechanisms:</h4>
            <p className="text-slate-600 mb-2">The Platform implements automated cookie expiration and deletion processes:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) Session cookies are automatically deleted upon browser closure;</li>
                <li>(b) Persistent cookies expire according to their specified duration;</li>
                <li>(c) Backend systems purge expired cookie data on a monthly basis;</li>
                <li>(d) Users may manually delete cookies through browser settings at any time;</li>
            </ul>
        </div>
      </div>
    </section>
  );
}
'use client';

import React from 'react';
import { Info, Lock, Settings, BarChart2 } from 'lucide-react';

export default function CookiePolicyPart2() {
  return (
    <section id="part-2" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 2</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">TYPOLOGY OF COOKIES, TECHNICAL SPECIFICATIONS, AND DATA COLLECTION MATRICES</h2>
      </div>

      {/* 2.1 */}
      <div id="point-2-1" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4">2.1 COMPREHENSIVE COOKIE TAXONOMY</h3>
        <p className="text-slate-600 text-justify">
            The Platform deploys multiple categories of cookies and tracking technologies, each serving distinct functional purposes and operating under specific legal bases. This Section provides an exhaustive classification and technical specification of all cookies utilized.
        </p>
      </div>

      {/* 2.2 */}
      <div id="point-2-2" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Lock className="text-indigo-600" size={20} /> 2.2 STRICTLY NECESSARY COOKIES
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.2.1 Legal Basis for Processing:</h4>
            <p className="text-slate-600 mb-2">These cookies are deployed pursuant to:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) Contractual necessity under Section 10 of the Indian Contract Act, 1872, as they are essential for the performance of the contract between the User and the Platform;</li>
                <li>(b) Legitimate interests under Rule 5(1) of the IT Rules, 2011, as they are indispensable for providing the services explicitly requested by the User;</li>
                <li>(c) Security obligations under Section 43A of the IT Act, 2000, requiring reasonable security practices;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.2.2 Functional Purpose:</h4>
            <p className="text-slate-600 mb-2">Strictly Necessary Cookies are technically indispensable for the Platform's core functionality and cannot be disabled without fundamentally impairing the User's ability to access and utilize Platform Services. These cookies enable:</p>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Authentication and Session Management:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>User login authentication and session persistence;</li>
                        <li>Verification of User credentials against stored KYC database;</li>
                        <li>Prevention of unauthorized access to User accounts;</li>
                        <li>Session token generation and validation;</li>
                        <li>Logout functionality and session termination;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Security Functions:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Cross-Site Request Forgery (CSRF) protection tokens;</li>
                        <li>Detection and prevention of fraudulent activities;</li>
                        <li>Implementation of rate limiting to prevent denial-of-service attacks;</li>
                        <li>Monitoring for suspicious activity patterns indicative of account compromise;</li>
                        <li>Encryption key management for secure data transmission;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Core Transactional Operations:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Shopping cart functionality for Pre-IPO investment selections;</li>
                        <li>Transaction flow management through multi-step processes;</li>
                        <li>Temporary storage of investment application data during submission;</li>
                        <li>Payment gateway integration and transaction verification;</li>
                        <li>Digital signature authentication for legally binding documents;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(d) Regulatory Compliance Functions:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Implementation of consent management mechanisms;</li>
                        <li>Recording of User acknowledgments and acceptances;</li>
                        <li>Audit trail generation for regulatory reporting;</li>
                        <li>Timestamp verification for time-sensitive transactions;</li>
                        <li>Geographic location verification for jurisdictional compliance;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.2.3 Technical Specifications:</h4>
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
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">PHPSESSID / JSESSIONID</td><td className="px-6 py-4">Session</td><td className="px-6 py-4">Session duration</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Unique session identifier</td><td className="px-6 py-4">Session management</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">auth_token</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">30 days</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Encrypted authentication token</td><td className="px-6 py-4">User authentication</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">csrf_token</td><td className="px-6 py-4">Session</td><td className="px-6 py-4">Session duration</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">CSRF prevention token</td><td className="px-6 py-4">Security protection</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">cookie_consent</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">12 months</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">User consent preferences</td><td className="px-6 py-4">Consent management</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">secure_transaction_id</td><td className="px-6 py-4">Session</td><td className="px-6 py-4">Transaction duration</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Transaction reference</td><td className="px-6 py-4">Transaction tracking</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">kyc_verification_status</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">90 days</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">KYC completion status</td><td className="px-6 py-4">Compliance verification</td></tr>
                        <tr className="bg-white"><td className="px-6 py-4">geo_jurisdiction</td><td className="px-6 py-4">Session</td><td className="px-6 py-4">Session duration</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Geographic location code</td><td className="px-6 py-4">Jurisdictional compliance</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.2.4 Legal Justification for Non-Consensual Deployment:</h4>
            <p className="text-slate-600 mb-2">Pursuant to internationally recognized standards including the ePrivacy Directive (EU) 2002/58/EC (as amended) and its Indian law equivalents, Strictly Necessary Cookies do not require prior User consent as they are:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) Essential for providing the information society service explicitly requested by the User;</li>
                <li>(b) Necessary for compliance with legal obligations imposed upon the Platform;</li>
                <li>(c) Indispensable for protecting vital interests of Users and third parties;</li>
            </ul>
        </div>
      </div>

      {/* 2.3 */}
      <div id="point-2-3" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Settings className="text-indigo-600" size={20} /> 2.3 FUNCTIONAL COOKIES
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.3.1 Legal Basis for Processing:</h4>
            <p className="text-slate-600 mb-2">Functional Cookies are deployed on the basis of:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) User consent obtained through the cookie banner mechanism described in Part 4;</li>
                <li>(b) Legitimate interests of the Platform in enhancing User experience, subject to the User's right to object;</li>
                <li>(c) Implied consent through continued use of the Platform after receiving notice;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.3.2 Functional Purpose:</h4>
            <p className="text-slate-600 mb-2">Functional Cookies enhance the Platform's usability and enable personalized features, though the Platform remains accessible without them (albeit with reduced functionality). These cookies facilitate:</p>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Preference Management:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Storage of User-selected display preferences (language, currency, time zone);</li>
                        <li>Font size and accessibility settings;</li>
                        <li>Dashboard layout and widget configurations;</li>
                        <li>Notification preferences and alert settings;</li>
                        <li>Report format preferences (PDF, Excel, CSV);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Enhanced User Experience:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Remember me functionality for streamlined login;</li>
                        <li>Auto-fill suggestions for frequently used information;</li>
                        <li>Recently viewed Pre-IPO opportunities;</li>
                        <li>Saved search filters and investment criteria;</li>
                        <li>Personalized dashboard configurations;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Communication Features:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Chat widget status and conversation history;</li>
                        <li>Help desk ticket references;</li>
                        <li>Document upload progress tracking;</li>
                        <li>Form auto-save functionality;</li>
                        <li>Collaborative document editing features;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(d) Platform Navigation:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Last visited page for seamless resumption;</li>
                        <li>Navigation breadcrumb trails;</li>
                        <li>Collapsed/expanded menu states;</li>
                        <li>Tutorial completion status;</li>
                        <li>Onboarding progress tracking;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.3.3 Technical Specifications:</h4>
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
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">user_preferences</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">12 months</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Display and interface settings</td><td className="px-6 py-4">User customization</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">language_preference</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">12 months</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Selected language code</td><td className="px-6 py-4">Localization</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">dashboard_layout</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">6 months</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Widget positions and visibility</td><td className="px-6 py-4">Dashboard customization</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">recently_viewed</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">30 days</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Investment opportunity IDs</td><td className="px-6 py-4">Navigation assistance</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">saved_filters</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">90 days</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Search and filter criteria</td><td className="px-6 py-4">Search optimization</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">chat_session</td><td className="px-6 py-4">Session</td><td className="px-6 py-4">Session duration</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Chat session ID</td><td className="px-6 py-4">Customer support</td></tr>
                        <tr className="bg-white"><td className="px-6 py-4">onboarding_status</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">6 months</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Tutorial completion flags</td><td className="px-6 py-4">User education</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.3.4 Impact of Disabling Functional Cookies:</h4>
            <p className="text-slate-600 mb-2">Users who opt out of Functional Cookies will experience:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Loss of customized preferences upon each new session;</li>
                <li>Inability to save personalized dashboard configurations;</li>
                <li>Reduced auto-fill and auto-save functionality;</li>
                <li>Manual re-entry of repetitive information;</li>
                <li>Default settings applied across all sessions;</li>
            </ul>
        </div>
      </div>

      {/* 2.4 */}
      <div id="point-2-4" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <BarChart2 className="text-indigo-600" size={20} /> 2.4 PERFORMANCE AND ANALYTICS COOKIES
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.4.1 Legal Basis for Processing:</h4>
            <p className="text-slate-600 mb-2">Performance and Analytics Cookies are deployed pursuant to:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) Explicit User consent obtained through the cookie consent mechanism;</li>
                <li>(b) Legitimate interests of the Platform in improving service quality, subject to User rights;</li>
                <li>(c) Compliance with SEBI investment advisory obligations to maintain service quality standards;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.4.2 Functional Purpose:</h4>
            <p className="text-slate-600 mb-2">These cookies enable the Platform to:</p>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Platform Performance Monitoring:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Page load times and latency measurements;</li>
                        <li>Error rate tracking and debugging assistance;</li>
                        <li>Server response time analysis;</li>
                        <li>API endpoint performance metrics;</li>
                        <li>Database query optimization data;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) User Behavior Analytics:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Aggregate traffic patterns and peak usage periods;</li>
                        <li>Page view statistics and content engagement metrics;</li>
                        <li>User journey mapping through the investment funnel;</li>
                        <li>Feature utilization rates and adoption metrics;</li>
                        <li>A/B testing of interface improvements;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Business Intelligence:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Conversion rate optimization for investment completions;</li>
                        <li>Abandonment analysis at various transaction stages;</li>
                        <li>Search query analysis for content optimization;</li>
                        <li>Device and browser compatibility assessment;</li>
                        <li>Geographic distribution of User base;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(d) SEBI Regulatory Compliance:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Record-keeping of advisory interactions as required under SEBI (Investment Advisers) Regulations, 2013;</li>
                        <li>Documentation of risk disclosure acknowledgments;</li>
                        <li>Audit trail of investment recommendation delivery;</li>
                        <li>Monitoring of suitability assessment completions;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.4.3 Technical Specifications:</h4>
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
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">_ga</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">24 months</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Unique user identifier, timestamp</td><td className="px-6 py-4">Google Analytics</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">_gid</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">24 hours</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">User and session identifier</td><td className="px-6 py-4">Google Analytics</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">_gat</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">1 minute</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Request throttling</td><td className="px-6 py-4">Google Analytics</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">analytics_session</td><td className="px-6 py-4">Session</td><td className="px-6 py-4">Session duration</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Session tracking data</td><td className="px-6 py-4">Internal Analytics</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">performance_metrics</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">30 days</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">Page load and interaction times</td><td className="px-6 py-4">Internal Performance</td></tr>
                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">ab_test_variant</td><td className="px-6 py-4">Persistent</td><td className="px-6 py-4">30 days</td><td className="px-6 py-4">.preiposip.com</td><td className="px-6 py-4">A/B test group assignment</td><td className="px-6 py-4">Internal Testing</td></tr>
                        <tr className="bg-white"><td className="px-6 py-4">heatmap_session</td><td className="px-6 py-4">Session</td><td className="px-6 py-4">Session duration</td><td className="px-6 py-4">hotjar.com</td><td className="px-6 py-4">User interaction heatmap data</td><td className="px-6 py-4">Hotjar</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.4.4 Data Elements Collected:</h4>
            <p className="text-slate-600 mb-2">Performance and Analytics Cookies may collect and process the following data elements:</p>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Technical Data:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>IP address (anonymized where technically feasible);</li>
                        <li>Browser type, version, and language settings;</li>
                        <li>Operating system and device type;</li>
                        <li>Screen resolution and viewport dimensions;</li>
                        <li>Referring website or source of visit;</li>
                        <li>Entry and exit pages;</li>
                        <li>Timestamp of visits;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Behavioral Data:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Pages visited and time spent on each page;</li>
                        <li>Links and buttons clicked;</li>
                        <li>Scroll depth and content engagement;</li>
                        <li>Search queries entered (excluding personal information);</li>
                        <li>Forms abandoned and completion rates;</li>
                        <li>File downloads and document views;</li>
                        <li>Video playback and engagement metrics;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Aggregated Investment Activity Data:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Categories of Pre-IPO opportunities viewed;</li>
                        <li>Investment amount ranges (aggregated, not individual);</li>
                        <li>Frequency of Platform visits;</li>
                        <li>Time of day and day of week usage patterns;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">2.4.5 Data Anonymization and Pseudonymization:</h4>
            <p className="text-slate-600 mb-2">The Platform implements the following measures to protect User privacy:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) IP address anonymization through truncation of the last octet;</li>
                <li>(b) Hashing of unique identifiers to prevent reverse identification;</li>
                <li>(c) Aggregation of data to prevent individual User identification;</li>
                <li>(d) Prohibition on merging analytics data with personally identifiable information;</li>
                <li>(e) Regular data minimization reviews to delete unnecessary collected data;</li>
            </ul>
        </div>
      </div>
    </section>
  );
}
'use client';

import React from 'react';
import { FileCheck, MousePointer, AlertOctagon, Link2 } from 'lucide-react';

export default function CookiePolicyPart5() {
  return (
    <section id="part-3-continued" className="section mb-12">
      {/* 3.4.2 and onwards */}
      <div id="point-3-4" className="subsection mb-10 scroll-mt-32">
        <h4 className="text-lg font-bold text-slate-700 mb-3">3.4.2 LIA for Performance and Analytics Cookies</h4>
        
        <div className="ml-4 space-y-4">
            <div>
                <p className="text-slate-700 font-semibold">(a) Purpose Test - Legitimate Interest Identified:</p>
                <p className="text-slate-600 mb-1">The Platform has a legitimate interest in:</p>
                <ul className="list-none pl-4 text-slate-600 space-y-1">
                    <li>(i) Understanding how Users interact with the Platform to identify technical issues, optimize performance, and enhance usability;</li>
                    <li>(ii) Conducting business intelligence analysis to improve service offerings and remain competitive in the Pre-IPO marketplace;</li>
                    <li>(iii) Demonstrating compliance with SEBI investment advisory obligations regarding service quality and investor protection;</li>
                </ul>
            </div>

            <div>
                <p className="text-slate-700 font-semibold">(b) Necessity Test - Processing Necessity Analysis:</p>
                <div className="ml-4 space-y-2">
                    <div>
                        <p className="text-slate-700 italic">(i) Is processing necessary?</p>
                        <p className="text-slate-600">Yes. Without analytics cookies, the Platform cannot: Identify and rectify technical errors affecting User experience; Optimize page load times and server performance; Understand User journey through investment funnel to identify friction points; Make data-driven decisions regarding feature development and resource allocation;</p>
                    </div>
                    <div>
                        <p className="text-slate-700 italic">(ii) Can the same objective be achieved through less intrusive means?</p>
                        <p className="text-slate-600">No reasonable alternative exists that would: Provide equivalent granularity of insights; Enable real-time monitoring and rapid issue resolution; Maintain cost-effectiveness and operational efficiency;</p>
                    </div>
                    <div>
                        <p className="text-slate-700 italic">(iii) Is the extent of processing proportionate?</p>
                        <p className="text-slate-600">Yes. The Platform: Collects only data necessary for analytics purposes; Implements IP anonymization and data aggregation; Prohibits merging of analytics data with directly identifiable Personal Information; Applies strict data retention limits (maximum 24 months);</p>
                    </div>
                </div>
            </div>

            <div>
                <p className="text-slate-700 font-semibold">(c) Balancing Test - Interests, Rights, and Freedoms Assessment:</p>
                <ul className="list-none pl-4 text-slate-600 space-y-2">
                    <li>(i) <strong>User Expectations:</strong> Users reasonably expect that a sophisticated fintech platform will employ analytics to maintain service quality and security. Such processing aligns with normal business practices in the financial services sector;</li>
                    <li>(ii) <strong>Nature of Personal Information:</strong> Analytics cookies collect primarily technical and behavioral data, not sensitive personal information as defined in Rule 3 of IT Rules, 2011;</li>
                    <li>(iii) <strong>Impact on User:</strong> Processing has minimal adverse impact on Users: Data is pseudonymized and aggregated; No direct marketing or profiling decisions are made solely based on analytics data; Users retain ability to opt out through browser settings;</li>
                    <li>(iv) <strong>Safeguards Implemented:</strong> Transparent disclosure of analytics practices in this Policy; Implementation of data minimization and purpose limitation principles; Contractual restrictions on third-party analytics providers; Regular privacy impact assessments;</li>
                </ul>
            </div>

            <div>
                <p className="text-slate-700 font-semibold">(d) Conclusion:</p>
                <p className="text-slate-600">The Platform's legitimate interests in deploying Performance and Analytics Cookies do not override the interests, rights, and freedoms of Users, particularly given the safeguards implemented. Processing is therefore lawful on the basis of legitimate interests, subject to User's right to object.</p>
            </div>
        </div>

        <h4 className="text-lg font-bold text-slate-700 mb-3 mt-6">3.4.3 LIA Documentation and Review</h4>
        <ul className="list-none pl-4 text-slate-600 space-y-1">
            <li>(i) This LIA is formally documented and maintained in the Platform's compliance records;</li>
            <li>(ii) The assessment is reviewed annually or upon material changes to processing activities;</li>
            <li>(iii) Users may request a copy of the LIA by contacting the Data Protection Officer;</li>
            <li>(iv) The Platform maintains a register of all processing activities relying on legitimate interests;</li>
        </ul>
      </div>

      {/* 3.5 */}
      <div id="point-3-5" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <MousePointer className="text-indigo-600" size={20} /> 3.5 CONSENT MECHANISMS AND IMPLEMENTATION
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.5.1 Cookie Consent Banner Architecture</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Initial Presentation:</p>
                    <p className="text-slate-600 mb-1">Upon first visit to the Platform, Users are presented with a clear, prominent cookie consent banner that:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Appears before any non-essential cookies are deployed;</li>
                        <li>(ii) Provides concise, plain language explanation of cookie usage;</li>
                        <li>(iii) Offers granular consent options for different cookie categories;</li>
                        <li>(iv) Includes prominent links to this comprehensive Cookie Policy;</li>
                        <li>(v) Enables Users to accept all, reject all, or customize preferences;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Information Provided in Banner:</p>
                    <p className="text-slate-600 mb-1">The consent banner discloses:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) That the Platform uses cookies and tracking technologies;</li>
                        <li>(ii) Categories of cookies deployed (Strictly Necessary, Functional, Analytics, Advertising, Social Media);</li>
                        <li>(iii) Primary purposes of each category;</li>
                        <li>(iv) Consequence of accepting or rejecting optional cookies;</li>
                        <li>(v) User's right to change preferences at any time;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Consent Options:</p>
                    <p className="text-slate-600 mb-1">Users are presented with the following options:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) <strong>"Accept All Cookies"</strong> - Provides consent for all cookie categories except those already justified by legal obligation or contractual necessity;</li>
                        <li>(ii) <strong>"Reject All Optional Cookies"</strong> - Deploys only Strictly Necessary and Legally Required cookies;</li>
                        <li>(iii) <strong>"Customize Preferences"</strong> - Opens detailed preference center allowing granular selection of cookie categories and specific purposes;</li>
                        <li>(iv) <strong>"Learn More"</strong> - Links to this comprehensive Cookie Policy for detailed information;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.5.2 Cookie Preference Center</h4>
            <p className="text-slate-600 mb-2">The Platform provides a persistent Cookie Preference Center accessible via: (i) Footer link on all pages ("Cookie Settings" or "Manage Cookies"); (ii) User account dashboard settings; (iii) Email communication footers;</p>
            
            <div className="ml-4">
                <p className="text-slate-700 font-semibold">(a) Preference Center Features:</p>
                <ul className="list-none pl-4 text-slate-600 space-y-1">
                    <li>(i) <strong>Category-Level Controls:</strong> Toggle switches for each optional cookie category;</li>
                    <li>(ii) <strong>Purpose-Level Controls:</strong> For each category, detailed explanation of specific processing purposes with individual consent options;</li>
                    <li>(iii) <strong>Real-Time Effect:</strong> Changes take immediate effect without requiring page refresh where technically feasible;</li>
                    <li>(iv) <strong>Clear Explanations:</strong> Plain language descriptions of what each cookie category does and the impact of accepting or rejecting;</li>
                    <li>(v) <strong>List of Specific Cookies:</strong> Expandable sections listing actual cookie names, providers, expiration periods, and data collected;</li>
                    <li>(vi) <strong>Third-Party Links:</strong> Direct links to privacy policies of third-party cookie providers;</li>
                </ul>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.5.3 Consent Recording and Audit Trail</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Consent Log Maintenance:</p>
                    <p className="text-slate-600 mb-1">The Platform maintains comprehensive records of all consent interactions, including:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) <strong>User Identifier:</strong> Pseudonymized User ID or session identifier;</li>
                        <li>(ii) <strong>Timestamp:</strong> Precise date and time of consent decision;</li>
                        <li>(iii) <strong>Consent Scope:</strong> Specific categories and purposes consented to or rejected;</li>
                        <li>(iv) <strong>Consent Version:</strong> Version number of Cookie Policy and consent mechanism presented;</li>
                        <li>(v) <strong>User Action:</strong> Whether User actively consented, rejected, or took no action;</li>
                        <li>(vi) <strong>IP Address:</strong> (Hashed) for verification and fraud prevention purposes;</li>
                        <li>(vii) <strong>User Agent:</strong> Browser and device information for technical validation;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Consent Refresh Mechanisms:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Consent is re-requested if Cookie Policy undergoes material changes affecting User rights or processing purposes;</li>
                        <li>(ii) Consent may be periodically refreshed (e.g., annually) to ensure ongoing informed consent;</li>
                        <li>(iii) Users are notified via email of material Policy changes and prompted to review preferences;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Audit Trail for Regulatory Compliance:</p>
                    <p className="text-slate-600 mb-1">Consent records are:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Maintained for a minimum of five years to satisfy PMLA record-keeping obligations;</li>
                        <li>(ii) Available for production during SEBI inspections or regulatory audits;</li>
                        <li>(iii) Stored securely with encryption and access controls;</li>
                        <li>(iv) Regularly backed up to prevent data loss;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.5.4 Special Considerations for Vulnerable Users</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Minors:</p>
                    <p className="text-slate-600 mb-1">The Platform does not knowingly collect information from or target services to individuals under 18 years of age. If the Platform becomes aware that a minor has provided information:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Such information will be promptly deleted;</li>
                        <li>(ii) The minor's account will be deactivated;</li>
                        <li>(iii) Parental notification will be provided where feasible and legally required;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Users with Disabilities:</p>
                    <p className="text-slate-600 mb-1">The consent mechanism is designed to be accessible:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Keyboard navigation support for motor-impaired Users;</li>
                        <li>(ii) Screen reader compatibility for visually impaired Users;</li>
                        <li>(iii) High contrast and adjustable text size options;</li>
                        <li>(iv) Alternative consent methods (email, telephone) available upon request;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.5.5 Cross-Border Data Transfer Consent</h4>
            <p className="text-slate-600 mb-2">Where cookies result in transfer of Personal Information outside India, the Platform:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Explicitly discloses such transfers in the consent mechanism;</li>
                <li>(ii) Identifies recipient countries and their data protection standards;</li>
                <li>(iii) Obtains specific consent for international data transfers where required;</li>
                <li>(iv) Implements Standard Contractual Clauses (SCCs) or other appropriate safeguards;</li>
            </ul>
        </div>
      </div>

      {/* 3.6 */}
      <div id="point-3-6" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <AlertOctagon className="text-indigo-600" size={20} /> 3.6 INTEGRATION WITH SEBI INVESTMENT ADVISORY FRAMEWORK
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.6.1 SEBI-Mandated Disclosures and Consent</h4>
            <p className="text-slate-600 mb-2">Pursuant to SEBI (Investment Advisers) Regulations, 2013, the Platform obtains specific consents and provides disclosures beyond general cookie consent:</p>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Investment Advisory Agreement:</p>
                    <p className="text-slate-600 mb-1">Prior to providing personalized investment advice, the Platform:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Executes a written Investment Advisory Agreement as required by Regulation 15(1);</li>
                        <li>(ii) Discloses fee structure, conflicts of interest, and risks;</li>
                        <li>(iii) Obtains consent for collection and processing of financial and investment information;</li>
                        <li>(iv) Explains how cookie data contributes to suitability assessments;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Risk Profiling Consent:</p>
                    <p className="text-slate-600 mb-1">Users explicitly consent to:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Collection of financial information, income details, existing investments, and liabilities;</li>
                        <li>(ii) Assessment of risk tolerance through questionnaires and behavioral analysis;</li>
                        <li>(iii) Use of cookie data to monitor investment behavior and identify changes in risk profile;</li>
                        <li>(iv) Periodic updating of risk profile based on ongoing User interactions;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Disclosure of Data Usage in Advisory Process:</p>
                    <p className="text-slate-600 mb-1">The Platform transparently discloses:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) How cookie-derived behavioral data informs investment recommendations;</li>
                        <li>(ii) Types of algorithms or automated decision-making employed;</li>
                        <li>(iii) User's right to request human review of automated recommendations;</li>
                        <li>(iv) Limitations of algorithm-based advice and potential for error;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.6.2 Suitability and Appropriateness Assessments</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Information Collection for Assessments:</p>
                    <p className="text-slate-600 mb-1">Cookies facilitate collection of:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) User's investment objectives (capital appreciation, income generation, preservation);</li>
                        <li>(ii) Time horizon for investments (short-term, medium-term, long-term);</li>
                        <li>(iii) Liquidity needs and financial constraints;</li>
                        <li>(iv) Past investment experience and sophistication level;</li>
                        <li>(v) Behavioral patterns indicating risk appetite;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Automated Suitability Filtering:</p>
                    <p className="text-slate-600 mb-1">Cookie data enables:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Automatic filtering of Pre-IPO opportunities based on User's risk profile;</li>
                        <li>(ii) Display of risk warnings for investments exceeding User's stated risk tolerance;</li>
                        <li>(iii) Prevention of access to high-risk products for conservative investors;</li>
                        <li>(iv) Personalized educational content to address knowledge gaps;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Ongoing Monitoring and Alerts:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Continuous monitoring of User portfolio concentration and diversification;</li>
                        <li>(ii) Alerts when User's investment behavior deviates significantly from stated objectives;</li>
                        <li>(iii) Triggers for mandatory suitability reassessment;</li>
                        <li>(iv) Documentation of all suitability determinations for regulatory audit;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.6.3 Conflicts of Interest Management</h4>
            <p className="text-slate-600 mb-1">The Platform uses cookies to:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Track and disclose any affiliated products or revenue-sharing arrangements;</li>
                <li>(ii) Identify situations where Platform's financial interests may conflict with User's best interests;</li>
                <li>(iii) Document disclosure of conflicts to Users;</li>
                <li>(iv) Monitor effectiveness of conflict mitigation measures;</li>
            </ul>
        </div>
      </div>

      {/* 3.7 */}
      <div id="point-3-7" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Link2 className="text-indigo-600" size={20} /> 3.7 INTERACTION WITH PRIVACY POLICY AND OTHER LEGAL DOCUMENTS
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.7.1 Relationship with Privacy Policy:</h4>
            <p className="text-slate-600 mb-1">This Cookie Policy supplements and should be read in conjunction with the Platform's Privacy Policy, which governs:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Broader Personal Information collection beyond cookies;</li>
                <li>(ii) Data subject rights (access, rectification, deletion, portability);</li>
                <li>(iii) Third-party data sharing arrangements;</li>
                <li>(iv) International data transfer mechanisms;</li>
                <li>(v) Data breach notification procedures;</li>
                <li>(vi) Contact information for Data Protection Officer;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.7.2 Relationship with Terms of Service:</h4>
            <p className="text-slate-600 mb-1">The Terms of Service govern:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Contractual relationship between User and Platform;</li>
                <li>(ii) Acceptable use policies and User obligations;</li>
                <li>(iii) Intellectual property rights;</li>
                <li>(iv) Limitation of liability and dispute resolution;</li>
                <li>(v) Governing law and jurisdiction;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.7.3 Hierarchy in Event of Conflict:</h4>
            <p className="text-slate-600 mb-1">In the event of any inconsistency between this Cookie Policy and other documents:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Mandatory provisions of Indian law prevail over all contractual terms;</li>
                <li>(ii) Express written agreements executed between Parties prevail over standard terms;</li>
                <li>(iii) Terms of Service prevail over this Cookie Policy for matters of contractual relationship;</li>
                <li>(iv) This Cookie Policy prevails over Privacy Policy for specific cookie-related matters;</li>
            </ul>
        </div>
      </div>
    </section>
  );
}
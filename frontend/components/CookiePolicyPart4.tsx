'use client';

import React from 'react';
import { Target, FileText, Scale, Shield } from 'lucide-react';

export default function CookiePolicyPart4() {
  return (
    <section id="part-4" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 3</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">PURPOSES OF PROCESSING, LEGAL BASES, LEGITIMATE INTEREST ASSESSMENTS, AND CONSENT MECHANISMS</h2>
      </div>

      {/* 3.1 */}
      <div id="point-3-1" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4">3.1 COMPREHENSIVE PURPOSE SPECIFICATION</h3>
        <p className="text-slate-600 text-justify">
            Pursuant to the data minimization and purpose limitation principles enshrined in Rule 5(2) of the IT Rules, 2011, and internationally recognized data protection standards, the Platform hereby specifies with precision the purposes for which cookies and tracking technologies are deployed and Personal Information is processed.
        </p>
      </div>

      {/* 3.2 */}
      <div id="point-3-2" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Target className="text-indigo-600" size={20} /> 3.2 PRIMARY PURPOSES OF COOKIE DEPLOYMENT
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.2.1 Platform Operations and Service Delivery</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Core Service Provision:</p>
                    <p className="text-slate-600 mb-1">The Platform processes cookie-derived data to:</p>
                    <div className="ml-4 space-y-2">
                        <div>
                            <p className="text-slate-700 italic">(i) Enable User Access and Authentication:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Verify User identity through secure session management;</li>
                                <li>Maintain persistent login states in accordance with User preferences;</li>
                                <li>Implement multi-factor authentication (MFA) protocols where required;</li>
                                <li>Prevent unauthorized access through real-time session validation;</li>
                                <li>Facilitate secure logout and session termination procedures;</li>
                            </ul>
                        </div>
                        <div>
                            <p className="text-slate-700 italic">(ii) Execute Investment Transactions:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Process User instructions to invest in Pre-IPO Securities;</li>
                                <li>Maintain transaction integrity throughout multi-step workflows;</li>
                                <li>Generate legally binding digital agreements and confirmations;</li>
                                <li>Interface with payment gateways for secure fund transfers;</li>
                                <li>Record transaction timestamps for regulatory audit trails;</li>
                            </ul>
                        </div>
                        <div>
                            <p className="text-slate-700 italic">(iii) Deliver Platform Content and Features:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Display personalized investment opportunity dashboards;</li>
                                <li>Render customized market research and analytical reports;</li>
                                <li>Provide access to educational materials and investor resources;</li>
                                <li>Enable portfolio tracking and performance monitoring;</li>
                                <li>Facilitate communication between Users and Platform representatives;</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Investment Advisory Services (SEBI-Regulated):</p>
                    <p className="text-slate-600 mb-1">In compliance with SEBI (Investment Advisers) Regulations, 2013, cookies enable:</p>
                    <div className="ml-4 space-y-2">
                        <div>
                            <p className="text-slate-700 italic">(i) Suitability and Appropriateness Assessment:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Collection of User financial information, investment objectives, and risk tolerance pursuant to Regulation 15(1);</li>
                                <li>Maintenance of client profiling data to ensure investment recommendations align with User circumstances;</li>
                                <li>Documentation of risk disclosure acknowledgments;</li>
                                <li>Periodic review and updating of User investment profiles;</li>
                            </ul>
                        </div>
                        <div>
                            <p className="text-slate-700 italic">(ii) Personalized Investment Recommendations:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Analysis of User preferences to suggest suitable Pre-IPO opportunities;</li>
                                <li>Filtering of investment products based on User's accredited investor status;</li>
                                <li>Customization of content delivery based on User sophistication level;</li>
                                <li>Provision of sector-specific insights aligned with User portfolio strategy;</li>
                            </ul>
                        </div>
                        <div>
                            <p className="text-slate-700 italic">(iii) Regulatory Record-Keeping:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Maintenance of comprehensive records of all investment advice provided, as mandated by Regulation 16;</li>
                                <li>Documentation of User interactions, queries, and responses;</li>
                                <li>Preservation of audit trails for SEBI inspection and compliance verification;</li>
                                <li>Timestamped logs of risk warnings and disclosures delivered to Users;</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.2.2 Compliance with Legal and Regulatory Obligations</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Know Your Customer (KYC) and Anti-Money Laundering (AML) Compliance:</p>
                    <p className="text-slate-600 mb-1">Pursuant to the Prevention of Money Laundering Act, 2002, and the PML (Maintenance of Records) Rules, 2005, the Platform processes cookie data to:</p>
                    <div className="ml-4 space-y-2">
                        <div>
                            <p className="text-slate-700 italic">(i) Identity Verification:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Facilitate collection and verification of officially valid documents under Rule 9;</li>
                                <li>Implement Aadhaar-based e-KYC procedures in compliance with UIDAI guidelines;</li>
                                <li>Conduct Video-based KYC (VKYC) as per RBI Master Directions;</li>
                                <li>Verify Permanent Account Number (PAN) through Income Tax Department databases;</li>
                            </ul>
                        </div>
                        <div>
                            <p className="text-slate-700 italic">(ii) Customer Due Diligence (CDD):</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Assess and categorize customer risk profiles (low, medium, high risk);</li>
                                <li>Conduct enhanced due diligence (EDD) for high-risk customers;</li>
                                <li>Verify beneficial ownership in accordance with Rule 9(1A);</li>
                                <li>Screen against Politically Exposed Person (PEP) lists and sanctions databases;</li>
                            </ul>
                        </div>
                        <div>
                            <p className="text-slate-700 italic">(iii) Transaction Monitoring and Suspicious Activity Reporting:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Monitor transaction patterns for unusual or suspicious activities;</li>
                                <li>Flag transactions exceeding prescribed threshold limits under Section 12(1)(b);</li>
                                <li>Generate Suspicious Transaction Reports (STRs) for submission to Financial Intelligence Unit - India (FIU-IND);</li>
                                <li>Maintain comprehensive transaction records for five years as mandated by Rule 3;</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) SEBI Regulatory Compliance:</p>
                    <div className="ml-4 space-y-2">
                        <div>
                            <p className="text-slate-700 italic">(i) Investment Adviser Obligations:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Maintain records of advisory services for ten years pursuant to Regulation 16(1);</li>
                                <li>Ensure disclosure of conflicts of interest as required by Regulation 15(2);</li>
                                <li>Implement fair treatment of clients and prevention of mis-selling;</li>
                                <li>Facilitate SEBI inspections and regulatory audits;</li>
                            </ul>
                        </div>
                        <div>
                            <p className="text-slate-700 italic">(ii) Insider Trading Prevention:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Monitor access to Unpublished Price Sensitive Information (UPSI);</li>
                                <li>Maintain structured digital databases (SDD) as required by Regulation 3 of SEBI (Prohibition of Insider Trading) Regulations, 2015;</li>
                                <li>Implement trading window restrictions for designated persons;</li>
                                <li>Track disclosure of shareholding and trading by insiders;</li>
                            </ul>
                        </div>
                        <div>
                            <p className="text-slate-700 italic">(iii) Market Abuse Prevention:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Detect and prevent manipulative, fraudulent, or deceptive practices under SEBI (Prohibition of Fraudulent and Unfair Trade Practices) Regulations, 2003;</li>
                                <li>Monitor for pump-and-dump schemes in Pre-IPO securities;</li>
                                <li>Prevent dissemination of false or misleading information;</li>
                                <li>Report market manipulation to SEBI authorities;</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Tax Compliance and Reporting:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Implementation of Tax Deduction at Source (TDS) provisions under the Income Tax Act, 1961;</li>
                        <li>(ii) Preparation of Form 26AS statements and Annual Information Returns (AIR);</li>
                        <li>(iii) Compliance with Goods and Services Tax (GST) invoicing and reporting requirements;</li>
                        <li>(iv) Maintenance of transaction records for Income Tax assessments and audits;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(d) Companies Act, 2013 Compliance:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Maintenance of register of members/shareholders under Section 88;</li>
                        <li>(ii) Record-keeping for statutory audit purposes under Section 143;</li>
                        <li>(iii) Documentation of related party transactions under Section 188;</li>
                        <li>(iv) Compliance with disclosure norms and corporate governance requirements;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.2.3 Security, Fraud Prevention, and Risk Management</h4>
            <p className="text-slate-600 mb-2">The Platform processes cookie data to:</p>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Cyber Security Measures:</p>
                    <div className="ml-4 space-y-2">
                        <div>
                            <p className="text-slate-700 italic">(i) Threat Detection and Prevention:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Identify and block malicious bots, scrapers, and automated attacks;</li>
                                <li>Detect Cross-Site Scripting (XSS) and SQL injection attempts;</li>
                                <li>Implement rate limiting to prevent brute force attacks;</li>
                                <li>Monitor for Distributed Denial of Service (DDoS) patterns;</li>
                            </ul>
                        </div>
                        <div>
                            <p className="text-slate-700 italic">(ii) Security Incident Response:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Generate real-time alerts for suspicious login attempts from unusual locations;</li>
                                <li>Trigger step-up authentication for high-risk transactions;</li>
                                <li>Maintain comprehensive security logs for forensic analysis;</li>
                                <li>Facilitate incident investigation and breach notification procedures;</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Fraud Detection and Prevention:</p>
                    <div className="ml-4 space-y-2">
                        <div>
                            <p className="text-slate-700 italic">(i) Identity Theft Prevention:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Cross-verify User behavioral patterns against historical baseline;</li>
                                <li>Detect account takeover attempts through anomaly detection algorithms;</li>
                                <li>Identify use of stolen credentials or compromised devices;</li>
                                <li>Implement device fingerprinting to recognize trusted devices;</li>
                            </ul>
                        </div>
                        <div>
                            <p className="text-slate-700 italic">(ii) Transaction Fraud Mitigation:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Flag transactions inconsistent with User's typical behavior;</li>
                                <li>Detect coordinated fraud rings through pattern analysis;</li>
                                <li>Prevent money laundering through layering and integration detection;</li>
                                <li>Identify synthetic identity fraud and document manipulation;</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Legal and Regulatory Risk Management:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Protection against regulatory penalties and enforcement actions;</li>
                        <li>(ii) Defense against civil litigation and damage claims;</li>
                        <li>(iii) Preservation of evidence for legal proceedings;</li>
                        <li>(iv) Compliance with court orders, subpoenas, and regulatory requests;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.2.4 Platform Improvement and Research</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Service Quality Enhancement:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Analysis of User feedback and satisfaction metrics;</li>
                        <li>(ii) Identification of technical issues, bugs, and performance bottlenecks;</li>
                        <li>(iii) Optimization of page load times and server response rates;</li>
                        <li>(iv) Enhancement of mobile application usability and accessibility;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Product Development:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Testing of new features and functionality through A/B testing;</li>
                        <li>(ii) Development of innovative investment tools and calculators;</li>
                        <li>(iii) Creation of educational content based on User engagement patterns;</li>
                        <li>(iv) Expansion of Platform capabilities to address User needs;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Market Research and Business Intelligence:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Analysis of Pre-IPO market trends and investor preferences;</li>
                        <li>(ii) Assessment of competitive landscape and industry developments;</li>
                        <li>(iii) Evaluation of pricing strategies and fee structures;</li>
                        <li>(iv) Strategic planning based on aggregate User behavior insights;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.2.5 Marketing, Communication, and User Engagement</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Direct Marketing Communications:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Email newsletters featuring curated Pre-IPO investment opportunities;</li>
                        <li>(ii) SMS alerts for time-sensitive investment openings (subject to TRAI DND regulations);</li>
                        <li>(iii) Push notifications for mobile application Users;</li>
                        <li>(iv) Personalized content recommendations based on User interests;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Advertising and Retargeting:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Display of relevant advertisements on third-party websites and platforms;</li>
                        <li>(ii) Retargeting of Users who have viewed specific Pre-IPO opportunities;</li>
                        <li>(iii) Lookalike audience creation for customer acquisition;</li>
                        <li>(iv) Attribution analysis of marketing campaign effectiveness;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Customer Relationship Management:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Tracking of User lifecycle stages (prospect, active investor, dormant);</li>
                        <li>(ii) Personalized outreach based on User engagement patterns;</li>
                        <li>(iii) Win-back campaigns for inactive Users;</li>
                        <li>(iv) Referral program tracking and reward attribution;</li>
                    </ul>
                </div>
            </div>
        </div>
      </div>

      {/* 3.3 */}
      <div id="point-3-3" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Scale className="text-indigo-600" size={20} /> 3.3 LEGAL BASES FOR PROCESSING UNDER INDIAN LAW
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.3.1 Consent (Rule 5 of IT Rules, 2011)</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Definition and Requirements:</p>
                    <p className="text-slate-600 mb-1">"Consent" means any freely given, specific, informed, and unambiguous indication of the User's agreement to the processing of Personal Information, which may be expressed through:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Affirmative action (opt-in consent mechanisms);</li>
                        <li>(ii) Clear written statement, including electronic means;</li>
                        <li>(iii) Oral consent with verifiable audit trail (for telephonic interactions);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Conditions for Valid Consent:</p>
                    <p className="text-slate-600 mb-1">For consent to constitute a valid legal basis, it must satisfy:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-2">
                        <li>(i) <strong>Free Will:</strong> Consent must be voluntary and not obtained through coercion, undue influence, or as a precondition for service provision where such conditioning is unreasonable;</li>
                        <li>(ii) <strong>Specificity:</strong> Consent must relate to clearly defined, particularized purposes, not blanket authorization for all processing activities;</li>
                        <li>(iii) <strong>Informed Nature:</strong> The User must receive clear and comprehensive information regarding:
                            <ul className="list-disc pl-6 mt-1 space-y-1">
                                <li>Identity of the data controller (Platform);</li>
                                <li>Specific purposes of processing;</li>
                                <li>Categories of Personal Information to be collected;</li>
                                <li>Recipients or categories of recipients of data;</li>
                                <li>Duration of data retention;</li>
                                <li>Existence of User rights and complaint mechanisms;</li>
                            </ul>
                        </li>
                        <li>(iv) <strong>Unambiguous Indication:</strong> Consent must be expressed through clear affirmative action, not inferred from silence, pre-ticked boxes, or inactivity;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Cookie Categories Requiring Consent:</p>
                    <p className="text-slate-600 mb-1">The following cookie categories are deployed only upon obtaining explicit User consent:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Functional Cookies (optional enhancement features);</li>
                        <li>Performance and Analytics Cookies;</li>
                        <li>Targeting and Advertising Cookies;</li>
                        <li>Social Media Cookies;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(d) Right to Withdraw Consent:</p>
                    <p className="text-slate-600 mb-1">Users retain the absolute right to withdraw consent at any time, which can be exercised through:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Cookie preference center accessible via Platform interface;</li>
                        <li>(ii) Browser settings and cookie management tools;</li>
                        <li>(iii) Written request to the Data Protection Officer;</li>
                    </ul>
                    <p className="text-slate-600 mt-1">Withdrawal of consent shall not affect the lawfulness of processing conducted prior to such withdrawal, but shall prospectively terminate consent-based processing.</p>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.3.2 Contractual Necessity (Section 10, Indian Contract Act, 1872)</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Legal Principle:</p>
                    <p className="text-slate-600">Processing is lawful where it is necessary for the performance of a contract to which the User is a party, or for taking steps at the User's request prior to entering into a contract.</p>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Application to Cookie Processing:</p>
                    <p className="text-slate-600 mb-1">Strictly Necessary Cookies deployed for authentication, session management, transaction processing, and security are justified on the basis of contractual necessity, as:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) The User has entered into a binding contract with the Platform through acceptance of Terms of Service;</li>
                        <li>(ii) Provision of Platform Services is contingent upon deployment of such cookies;</li>
                        <li>(iii) The User has explicitly requested access to investment opportunities and related services;</li>
                        <li>(iv) Processing is objectively necessary and proportionate to fulfill contractual obligations;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Limitation on Contractual Necessity:</p>
                    <p className="text-slate-600 mb-1">Processing cannot be justified as "contractually necessary" if:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) The same objective can be reasonably achieved through less intrusive means;</li>
                        <li>(ii) The processing serves purposes collateral to core service delivery;</li>
                        <li>(iii) The User is coerced into accepting non-essential processing as a condition for service;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.3.3 Legal Obligation (Section 43A, IT Act, 2000; PMLA, 2002; SEBI Regulations)</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Legal Principle:</p>
                    <p className="text-slate-600">Processing is lawful where it is necessary for compliance with a legal obligation to which the Platform is subject under Indian law.</p>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Application to Cookie Processing:</p>
                    <p className="text-slate-600 mb-1">KYC/AML Compliance Cookies and regulatory record-keeping functions are justified on the basis of legal obligation, including:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) <strong>PMLA Obligations:</strong> Sections 12 and 12A mandate maintenance of KYC records and transaction monitoring;</li>
                        <li>(ii) <strong>SEBI Obligations:</strong> Investment Advisers Regulations require maintenance of comprehensive client records and advisory documentation;</li>
                        <li>(iii) <strong>IT Act Security Obligations:</strong> Section 43A requires implementation of reasonable security practices to protect sensitive personal data;</li>
                        <li>(iv) <strong>Income Tax Obligations:</strong> Sections 139A (PAN), 206C (TCS), and related provisions require transaction documentation;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Proportionality Assessment:</p>
                    <p className="text-slate-600 mb-1">Processing under legal obligation must be:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Strictly limited to what is necessary to satisfy the specific legal requirement;</li>
                        <li>(ii) Proportionate to the regulatory objective being pursued;</li>
                        <li>(iii) Subject to appropriate safeguards to protect User rights;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.3.4 Legitimate Interests (Analogous to GDPR Article 6(1)(f))</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Legal Framework:</p>
                    <p className="text-slate-600 mb-1">While the IT Act, 2000 and IT Rules, 2011 do not explicitly recognize "legitimate interests" as a legal basis, Indian jurisprudence and the proposed Digital Personal Data Protection Act, 2023 acknowledge the principle that processing may be lawful where:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) The Platform has a legitimate interest in processing;</li>
                        <li>(ii) Processing is necessary for pursuit of that interest;</li>
                        <li>(iii) The User's interests, rights, and freedoms do not override the Platform's legitimate interest;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Application to Cookie Processing:</p>
                    <p className="text-slate-600">Performance and Analytics Cookies may be justified on the basis of legitimate interests, subject to conducting a Legitimate Interest Assessment (LIA) as detailed in Section 3.4 below.</p>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Legitimate Interests Claimed by Platform:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) <strong>Business Operations:</strong> Efficient administration, financial management, and quality assurance;</li>
                        <li>(ii) <strong>Service Improvement:</strong> Enhancement of User experience, Platform functionality, and service quality;</li>
                        <li>(iii) <strong>Fraud Prevention:</strong> Protection of Platform, Users, and third parties against financial crime;</li>
                        <li>(iv) <strong>Network Security:</strong> Defense against cyber threats, malware, and unauthorized access;</li>
                        <li>(v) <strong>Regulatory Compliance:</strong> Demonstration of compliance with SEBI and other regulatory requirements;</li>
                        <li>(vi) <strong>Legal Defense:</strong> Preservation of evidence and documentation for potential litigation;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">3.4 LEGITIMATE INTEREST ASSESSMENT (LIA) FRAMEWORK</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">3.4.1 Purpose and Methodology</p>
                    <p className="text-slate-600 mb-1">The Platform conducts comprehensive Legitimate Interest Assessments to evaluate whether processing of cookie data on the basis of legitimate interests is lawful, fair, and transparent. The LIA methodology follows the three-part test:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li><strong>(a) Purpose Test:</strong> Is there a legitimate interest being pursued?</li>
                        <li><strong>(b) Necessity Test:</strong> Is the processing necessary for that purpose?</li>
                        <li><strong>(c) Balancing Test:</strong> Do the User's interests, rights, and freedoms override the legitimate interest?</li>
                    </ul>
                </div>
            </div>
        </div>
      </div>
    </section>
  );
}
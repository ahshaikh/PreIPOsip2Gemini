'use client';

import React from 'react';
import { ShieldAlert, UserX, School, Shield, Lock, Activity, AlertTriangle, FileText } from 'lucide-react';

export default function CookiePolicyPart14() {
  return (
    <section id="part-9" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 9</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">CHILDREN'S PRIVACY, AGE VERIFICATION, PROHIBITION ON MINOR DATA COLLECTION, AND SAFEGUARDING MECHANISMS</h2>
      </div>

      {/* 9.1 */}
      <div id="point-9-1" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <UserX className="text-indigo-600" size={20} /> 9.1 ABSOLUTE PROHIBITION ON SERVICES TO MINORS
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.1.1 Statutory and Regulatory Framework:</h4>
            
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Legal Definition of "Minor":</p>
                    <p className="text-slate-600 mb-1">Pursuant to Indian law, a "minor" is defined as any person who has not attained the age of majority:</p>
                    <div className="ml-4 space-y-2">
                        <div>
                            <p className="text-slate-700 italic">(i) Indian Majority Act, 1875 - Section 3:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Age of majority: 18 years;</li>
                                <li>Exception: where guardian appointed by court or property under superintendence of Court of Wards - 21 years;</li>
                                <li>For purposes of this Policy: any person under 18 years is a minor;</li>
                            </ul>
                        </div>
                        <div>
                            <p className="text-slate-700 italic">(ii) Indian Contract Act, 1872 - Section 11:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Minors are incompetent to contract;</li>
                                <li>Agreements with minors are void ab initio (from the beginning);</li>
                                <li>No ratification possible upon attaining majority;</li>
                            </ul>
                        </div>
                        <div>
                            <p className="text-slate-700 italic">(iii) SEBI Regulations:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>Investment in securities permitted only for persons of majority age;</li>
                                <li>Demat accounts for minors require guardian operation;</li>
                                <li>Investment advisory services cannot be provided to minors directly;</li>
                            </ul>
                        </div>
                        <div>
                            <p className="text-slate-700 italic">(iv) Prevention of Money Laundering Act, 2002:</p>
                            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                                <li>KYC requirements apply to adults only;</li>
                                <li>Minor accounts subject to different due diligence (guardian-operated);</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div>
                    <p className="text-slate-700 font-semibold">(b) International Standards:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-2">
                        <li>(i) <strong>UN Convention on the Rights of the Child (UNCRC):</strong> India is signatory (ratified 1992); Article 16: Right to privacy for children; Additional protections required for children's personal data;</li>
                        <li>(ii) <strong>GDPR Article 8 (for EEA Users):</strong> Information society services to children under 16 require parental consent; Member states may lower age to 13; Enhanced protections for children's data;</li>
                        <li>(iii) <strong>COPPA (USA - for reference):</strong> Children's Online Privacy Protection Act; Prohibits collection from children under 13 without verifiable parental consent; Industry standard informing global practices;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.1.2 Platform's Categorical Position:</h4>
            <p className="text-slate-600 mb-2">The Platform categorically prohibits the provision of services to minors and does not knowingly collect Personal Information, including cookie data, from any person under 18 years of age.</p>
            
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Rationale for Prohibition:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) <strong>Legal Incapacity:</strong> Minors cannot enter into binding contracts, rendering Terms of Service void;</li>
                        <li>(ii) <strong>Regulatory Compliance:</strong> SEBI regulations prohibit investment advisory to minors; securities investments require legal capacity;</li>
                        <li>(iii) <strong>KYC Impossibility:</strong> Minors cannot independently complete KYC as required by PMLA;</li>
                        <li>(iv) <strong>Heightened Vulnerability:</strong> Children require special protection; financial services pose risks inappropriate for minors;</li>
                        <li>(v) <strong>Ethical Responsibility:</strong> Platform's commitment to responsible investing includes protecting vulnerable populations;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) No "Parental Consent" Exception:</p>
                    <p className="text-slate-600 mb-1">Unlike general-purpose websites that may serve children with parental consent, the Platform does not offer such option because:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>(i) Financial services and investment advisory are inherently unsuitable for minors;</li>
                        <li>(ii) Guardian-operated accounts for minors (permitted under securities law) are outside Platform's service scope;</li>
                        <li>(iii) Regulatory complexity and liability concerns render minor participation impractical;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.1.3 Terms of Service Age Restriction:</h4>
            <p className="text-slate-600 mb-2">The Platform's Terms of Service contain explicit age representation:</p>
            <div className="bg-slate-50 p-4 rounded border-l-4 border-indigo-500 text-slate-700 italic mb-2">
                "By accepting these Terms of Service, you represent and warrant that you are at least 18 years of age and have the legal capacity to enter into this binding agreement. If you are under 18 years of age, you are expressly prohibited from accessing or using the Platform."
            </div>
            <p className="text-slate-600 mb-1">This representation is:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Prominently displayed during registration;</li>
                <li>Included in checkbox acknowledgment: "I confirm that I am 18 years of age or older ☐";</li>
                <li>Legally binding on User;</li>
                <li>Grounds for immediate account termination if false;</li>
            </ul>
        </div>
      </div>

      {/* 9.2 */}
      <div id="point-9-2" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <ShieldAlert className="text-indigo-600" size={20} /> 9.2 AGE VERIFICATION MECHANISMS
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.2.1 Multi-Layered Age Verification:</h4>
            <p className="text-slate-600 mb-2">The Platform implements defense-in-depth approach to prevent minor access:</p>
            
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Layer 1: Self-Declaration During Registration</p>
                    <p className="text-slate-600 font-semibold mb-1">Registration Form Requirements:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Date of birth field (mandatory);</li>
                        <li>Explicit age confirmation checkbox;</li>
                        <li>Warning message: "If you are under 18, you may not use this Platform";</li>
                        <li>Automated rejection if date of birth indicates age &lt;18;</li>
                    </ul>
                    <div className="bg-slate-900 text-slate-300 p-4 rounded mt-2 font-mono text-xs">
                        <pre>{`IF (current_date - date_of_birth) < 18 years:
    DISPLAY error: "You must be 18 or older to register"
    BLOCK registration
    LOG attempted_minor_registration
ELSE:
    PROCEED to next registration step`}</pre>
                    </div>
                </div>

                <div>
                    <p className="text-slate-700 font-semibold">(b) Layer 2: PAN Card Verification (India)</p>
                    <p className="text-slate-600 font-semibold mb-1">Mandatory for All Users:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Permanent Account Number (PAN) required for registration;</li>
                        <li>PAN linked to date of birth in Income Tax database;</li>
                        <li>Platform verifies PAN through Income Tax e-filing portal API or NSDL/UTIITSL;</li>
                        <li>Cross-verification: PAN record date of birth matches registration date of birth;</li>
                    </ul>
                    <p className="text-slate-600 font-semibold mt-1 mb-1">Discrepancy Handling:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>If dates don't match: registration suspended pending manual review;</li>
                        <li>If PAN belongs to minor: automatic rejection with explanation;</li>
                        <li>PAN age verification provides reliable, government-validated age confirmation;</li>
                    </ul>
                </div>

                <div>
                    <p className="text-slate-700 font-semibold">(c) Layer 3: Aadhaar-Based e-KYC (India)</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li><strong>Aadhaar e-KYC Process:</strong> User authenticates via Aadhaar OTP or biometric; Aadhaar XML response includes date of birth; Third layer of age verification; Aadhaar data cross-checked against PAN and self-declared date of birth;</li>
                        <li><strong>Triple Verification:</strong> Self-declared DOB = PAN DOB = Aadhaar DOB → Verification passed; Any mismatch → Manual review and potential rejection;</li>
                    </ul>
                </div>

                <div>
                    <p className="text-slate-700 font-semibold">(d) Layer 4: Bank Account Verification</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li><strong>Bank Account Linkage:</strong> Users must link bank account for investment transactions; Bank accounts held only by persons with legal capacity (18+); Bank account verification provides additional age confirmation proxy; Exception: Minor accounts (operated by guardian) flagged and rejected at onboarding;</li>
                    </ul>
                </div>

                <div>
                    <p className="text-slate-700 font-semibold">(e) Layer 5: Continuous Monitoring and Anomaly Detection</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li><strong>Behavioral Red Flags:</strong> Account activity patterns inconsistent with adult behavior; IP addresses associated with educational institutions (schools); Email addresses with school domains (@school.edu.in); Social media profiles (if linked) indicating minor status; Customer support inquiries revealing minor status;</li>
                        <li><strong>Automated Flagging:</strong> Machine learning models trained to detect minor-associated patterns; Flagged accounts subject to re-verification; Immediate suspension pending age confirmation;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.2.2 Age Verification for International Users:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Non-India Users:</p>
                    <p className="text-slate-600 mb-1">Where PAN and Aadhaar unavailable:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-2">
                        <li>(i) <strong>Government-Issued Photo ID:</strong> Passport, driver's license, national ID card; Document must show date of birth; Manual verification by KYC team; Age calculated from document DOB;</li>
                        <li>(ii) <strong>Credit Check (Where Applicable):</strong> Credit bureau reports (e.g., Experian, Equifax in respective countries); Credit history generally exists only for adults; Cross-verification of age;</li>
                        <li>(iii) <strong>Enhanced Due Diligence:</strong> Video KYC with visual age assessment; Verification call with age confirmation questions; Higher scrutiny for borderline ages (18-21);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Age Ambiguity:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>If User appears younger than declared age during video KYC: additional verification required;</li>
                        <li>Selfie with government ID for comparison;</li>
                        <li>Parental guardian contact (if suspicion of minor using adult's credentials);</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.2.3 Age Re-Verification:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Periodic Re-Verification:</strong> Users registered with borderline ages (18-19) subject to annual re-verification; Particularly if KYC documents near expiration require renewal; Ensures ongoing compliance with age requirements;</li>
                <li>(b) <strong>Triggered Re-Verification:</strong> Suspicious activity suggesting account takeover by minor; User profile changes (e.g., email to school domain); Third-party report alleging minor use; Regulatory inquiry or complaint;</li>
            </ul>
        </div>
      </div>

      {/* 9.3 */}
      <div id="point-9-3" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Lock className="text-indigo-600" size={20} /> 9.3 PROHIBITION ON COLLECTION FROM MINORS
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.3.1 Cookie Deployment Restrictions:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) No Targeting of Minors:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Platform does not deploy cookies designed to attract or engage minors;</li>
                        <li>No content, advertisements, or features directed at children;</li>
                        <li>No use of child-appealing imagery, language, or game-like features;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) No Processing of Minor Data:</p>
                    <p className="text-slate-600 mb-1">If, despite safeguards, a minor accesses Platform:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Cookies may technically deploy before age verification complete;</li>
                        <li>Such cookie data not processed for any purpose;</li>
                        <li>Immediate deletion upon minor status discovery;</li>
                        <li>No addition to databases or use in analytics;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.3.2 Third-Party Cookie Restrictions:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Contractual Prohibitions:</strong> All third-party cookie providers contractually prohibited from: Using Platform cookie data to create profiles of minors; Targeted advertising to minors using Platform-sourced data; Sharing Platform data with services directed at children; Cross-contextual use of data if minor status suspected;</li>
                <li>(b) <strong>Compliance Verification:</strong> Annual certification from third parties: "No data used for minor-directed services"; Audit right to verify compliance; Immediate termination for violations;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.3.3 Sensitive Personal Information of Minors:</h4>
            <p className="text-slate-600 mb-2">Even more stringent protections for SPDI:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Absolute Prohibition:</strong> Platform will never: Collect biometric data from minors; Process financial information of minors; Store health information of minors; Collect passwords or credentials from minors;</li>
                <li>(b) <strong>Technical Safeguards:</strong> Database constraints preventing insertion of records with age {`<`}18 into SPDI tables; Application-level validation rejecting minor data; Encryption keys for SPDI tables different from general data (additional protection);</li>
            </ul>
        </div>
      </div>

      {/* 9.4 */}
      <div id="point-9-4" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <AlertTriangle className="text-indigo-600" size={20} /> 9.4 PROCEDURES FOR HANDLING INADVERTENT COLLECTION
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.4.1 Discovery of Minor User:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Discovery Methods:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) <strong>Self-Disclosure:</strong> User contacts Platform stating they are under 18;</li>
                        <li>(ii) <strong>Parental Notification:</strong> Parent/guardian informs Platform their child accessed services;</li>
                        <li>(iii) <strong>KYC Verification Failure:</strong> Subsequent KYC check reveals User is minor;</li>
                        <li>(iv) <strong>Third-Party Report:</strong> School, law enforcement, or regulatory authority notifies Platform;</li>
                        <li>(v) <strong>Internal Detection:</strong> Platform's monitoring systems flag minor-associated patterns;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Immediate Response Protocol:</p>
                    <p className="text-slate-600 font-bold mb-1">Within 1 Hour of Discovery:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Account access immediately suspended;</li>
                        <li>Session terminated (all active sessions invalidated);</li>
                        <li>Flag applied to account: "MINOR - DO NOT PROCESS";</li>
                        <li>Alert sent to Data Protection Officer and Compliance Officer;</li>
                        <li>Incident logged in Child Safety Log;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.4.2 Data Deletion Procedure:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Comprehensive Data Inventory:</p>
                    <p className="text-slate-600 font-bold mb-1">Within 24 hours, Platform compiles complete inventory of minor's data:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>All cookies associated with User's sessions;</li>
                        <li>Personal Information collected during registration;</li>
                        <li>KYC documents submitted;</li>
                        <li>Transaction history (if any);</li>
                        <li>Communication records (emails, chats);</li>
                        <li>Analytics and behavioral data;</li>
                        <li>Third-party shared data;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Deletion Execution:</p>
                    <p className="text-slate-600 font-bold mb-1">Within 7 Days of Discovery:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-2">
                        <li>(i) <strong>Cookie Data:</strong> All cookies associated with User immediately invalidated; Backend cookie records deleted (hard delete, not soft); Third-party cookie providers notified to delete data; Browser-side cookies flagged for deletion next User attempt (will be rejected);</li>
                        <li>(ii) <strong>Personal Information:</strong> Complete deletion from all databases; Deletion from backup systems (all generations); Pseudonymized data purged (even if de-identified); Cache and temporary files cleared;</li>
                        <li>(iii) <strong>Third-Party Data:</strong> Deletion requests sent to all third parties who received data; Confirmation of deletion obtained within 14 days; Follow-up if third party non-compliant;</li>
                        <li>(iv) <strong>Audit Logs:</strong> Exception: anonymized logs retained documenting that minor inappropriately accessed Platform; Purpose: demonstrate compliance and prevent recurrence; All identifying information redacted; Log entry: "Minor access incident - User ID [REDACTED] - Date [X] - Data deleted per procedure";</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Deletion Verification:</p>
                    <p className="text-slate-600 font-bold mb-1">Within 14 Days:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>DPO conducts verification queries confirming data absence;</li>
                        <li>Random database sampling;</li>
                        <li>Third-party confirmation receipts reviewed;</li>
                        <li>Certification of deletion issued and filed;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.4.3 No Regulatory Retention Exception:</h4>
            <p className="text-slate-600 mb-2">Unlike adult User data, minor data not subject to PMLA or SEBI retention requirements:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Rationale:</strong> PMLA applies to valid customer relationships; minor contracts void ab initio; SEBI advisory records apply to lawful clients; minors cannot lawfully receive advisory; No legal relationship established → no retention obligation;</li>
                <li>(b) <strong>Regulatory Notification:</strong> If minor conducted any transactions (should be impossible, but if system failure): notify SEBI and FIU-IND; Transactions reversed and funds returned to source; Platform assumes liability for any processing of minor transactions;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.4.4 Parental Notification:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Mandatory Notification:</p>
                    <p className="text-slate-600 mb-1">Where parent/guardian contact information available:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Written notification within 5 days of discovery;</li>
                        <li>Sent via registered post and email;</li>
                        <li>Content: "We have identified that a minor accessed our Platform. We have immediately suspended the account and deleted all collected data.";</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Information Provided to Parent:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>What information was collected;</li>
                        <li>How it was used (if at all);</li>
                        <li>Actions taken (deletion, account termination);</li>
                        <li>Contact information for queries;</li>
                        <li>Assurance of no financial liability;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Where Parent Contact Unknown:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Best efforts to identify parent through submitted documents;</li>
                        <li>If impossible: document efforts and proceed with deletion;</li>
                        <li>No public notification (to protect minor's privacy);</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.4.5 Root Cause Analysis and Prevention:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Incident Investigation:</strong> How did minor bypass age verification? System failure, User fraud, or process gap? Were multiple minors affected? What improvements needed?</li>
                <li>(b) <strong>Corrective Actions:</strong> Within 30 days of incident: Strengthen age verification mechanisms; Enhanced training for KYC team; Update registration forms for clarity; Additional monitoring rules implemented; Technology enhancements (e.g., stricter PAN verification);</li>
                <li>(c) <strong>Incident Reporting:</strong> Internal report to Board of Directors (quarterly summary); Annual transparency report: "X minor access incidents detected and resolved"; Regulatory notification if systemic issue identified;</li>
            </ul>
        </div>
      </div>

      {/* 9.5 */}
      <div id="point-9-5" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <School className="text-indigo-600" size={20} /> 9.5 EDUCATIONAL INSTITUTION BLOCKING
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.5.1 Proactive Blocking Measures:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>IP Address Blocking:</strong> Platform maintains database of IP ranges associated with schools, colleges (for students {`<`}18); Access from such IPs: additional verification or blocking; Updated quarterly from public databases and ISP information;</li>
                <li>(b) <strong>Email Domain Restrictions:</strong> School email domains (@school.k12.in, @school.edu.in) flagged; Registration attempts from school emails: additional age verification required; Warning: "School email detected. Confirm you are 18+ and using personal email for financial services.";</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.5.2 Geographic Considerations:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Location-Based Assessment:</strong> Users accessing from locations near schools (via GPS cookies): additional scrutiny; Not outright blocked (legitimate adult Users exist), but flagged for enhanced verification;</li>
                <li>(b) <strong>Time-Based Patterns:</strong> Access during school hours from education institution IPs: red flag; Correlation of multiple factors for risk scoring;</li>
            </ul>
        </div>
      </div>

      {/* 9.6 */}
      <div id="point-9-6" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Activity className="text-indigo-600" size={20} /> 9.6 SPECIAL PROTECTIONS FOR YOUNG ADULTS (18-21)
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.6.1 Enhanced Disclosures:</h4>
            <p className="text-slate-600 mb-2">While Platform serves Users 18+, recognition that young adults may have limited financial literacy:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Mandatory Risk Education:</strong> Users aged 18-21 (first 3 years of eligibility) required to complete financial literacy module; Interactive content explaining investment risks, Pre-IPO market volatility; Comprehension quiz (minimum 80% score required); Certificate of completion before investment permitted;</li>
                <li>(b) <strong>Enhanced Risk Warnings:</strong> Additional disclosure: "You are in a high-risk age group for investment mistakes. Please consider seeking advice from experienced investors or financial advisors."; Pop-up confirmations for first investment; Cooling-off period: 48 hours between account opening and first transaction for Users 18-19;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.6.2 Parental Involvement (Optional):</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Voluntary Parental Advisory:</strong> Users 18-21 may optionally designate parent/guardian as "Trusted Advisor"; Trusted Advisor receives copies of investment confirmations (with User consent); No decision-making power (User retains full control); Purely informational and educational;</li>
                <li>(b) <strong>Family Account Linking:</strong> Option to link accounts with parent (both adults); Shared educational resources; Family investment discussions facilitated;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.6.3 Investment Limits:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Age-Based Limits:</p>
                    <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                        <table className="w-full text-sm text-left text-slate-600">
                            <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th className="px-6 py-3">Age Group</th>
                                    <th className="px-6 py-3">Maximum Single Investment</th>
                                    <th className="px-6 py-3">Maximum Annual Investment</th>
                                    <th className="px-6 py-3">Rationale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">18-19 years</td><td className="px-6 py-4">INR 25,000</td><td className="px-6 py-4">INR 1,00,000</td><td className="px-6 py-4">Limited experience; protect from substantial loss</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">20-21 years</td><td className="px-6 py-4">INR 50,000</td><td className="px-6 py-4">INR 2,50,000</td><td className="px-6 py-4">Moderate experience; gradual increase</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">22-25 years</td><td className="px-6 py-4">INR 1,00,000</td><td className="px-6 py-4">INR 5,00,000</td><td className="px-6 py-4">Maturing investor</td></tr>
                                <tr className="bg-white"><td className="px-6 py-4">26+ years</td><td className="px-6 py-4">No age-based limit</td><td className="px-6 py-4">No age-based limit</td><td className="px-6 py-4">Full discretion (subject to accredited investor norms)</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Override Mechanism:</p>
                    <p className="text-slate-600 mb-1">Young adults may request limit increase by:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Demonstrating financial capacity (income proof, net worth statement);</li>
                        <li>Completing advanced investor education;</li>
                        <li>Providing investment experience documentation;</li>
                        <li>Approval by Platform's Investment Advisory Committee;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.6.4 Extra Layer of Consent:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Enhanced Cookie Consent:</strong> Users 18-21 receive additional explanation of cookies and tracking; "Cookies for Beginners" tutorial video; Simplified, more detailed privacy notice; More frequent consent refresh (every 6 months vs. annually);</li>
                <li>(b) <strong>Easy Opt-Out:</strong> Simplified process to disable all non-essential cookies; Emphasis on User control and privacy rights; No pressure or dark patterns;</li>
            </ul>
        </div>
      </div>

      {/* 9.7 */}
      <div id="point-9-7" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Shield className="text-indigo-600" size={20} /> 9.7 COMMITMENT TO CHILD SAFETY
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.7.1 Organizational Commitment:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Child Safety Policy:</strong> Platform maintains separate comprehensive Child Safety Policy; Board-approved commitment to protecting minors; Zero tolerance for any services to minors; Annual review and updates;</li>
                <li>(b) <strong>Training:</strong> All employees trained on child safety obligations; Customer service team: specialized training on handling minor access incidents; KYC team: age verification best practices; Legal and compliance: regulatory requirements for child protection;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.7.2 Industry Collaboration:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Information Sharing:</strong> Platform participates in industry forums on child online safety; Shares best practices (anonymized) for age verification; Collaborates with SEBI and law enforcement on child protection;</li>
                <li>(b) <strong>Technology Solutions:</strong> Investment in age verification technologies; Evaluation of AI-based age estimation tools (face analysis); Pilot programs for enhanced verification methods;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.7.3 Reporting Mechanisms:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Public Reporting:</strong> Dedicated email: childsafety@preiposip.com; Anonymous reporting form for suspected minor use; 24-hour response commitment;</li>
                <li>(b) <strong>Law Enforcement Cooperation:</strong> Immediate cooperation with police or child protection agencies; Production of records if minor exploitation suspected; Proactive reporting to National Commission for Protection of Child Rights (NCPCR) if systemic issue;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.7.4 Continuous Improvement:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Annual Assessment:</strong> Review of minor access incidents (if any); Effectiveness of age verification measures; Benchmarking against international best practices; Technology upgrades and process improvements;</li>
                <li>(b) <strong>External Audit:</strong> Every 2 years: independent audit of child safety measures; Auditor specializing in child online protection; Report shared with Board and (summary) with Users;</li>
            </ul>
        </div>
      </div>

      {/* 9.8 */}
      <div id="point-9-8" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <FileText className="text-indigo-600" size={20} /> 9.8 LEGAL DISCLAIMERS AND LIMITATIONS
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.8.1 User Responsibility:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Accurate Information Obligation:</strong> Users are legally obligated to provide truthful information: False age representation: material breach of Terms of Service; Criminal liability: possible under Section 66D of IT Act (personation using computer resource); Platform relies on User representations in good faith;</li>
                <li>(b) <strong>Parental Responsibility:</strong> Parents/guardians responsible for supervising minors' internet use: Platform encourages parental controls and monitoring; Not Platform's obligation to monitor each User's household; Platform provides tools (age verification), but cannot guarantee 100% prevention;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.8.2 Limitation of Liability:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Best Efforts Standard:</strong> Platform commits to commercially reasonable efforts to prevent minor access: Industry-standard age verification; Proactive monitoring and detection; Rapid response to discovered incidents;</li>
                <li>(b) <strong>No Absolute Guarantee:</strong> Despite best efforts, sophisticated fraud may circumvent safeguards: Minor using adult's credentials; Falsified identity documents; Proxy registration by adult on minor's behalf; Platform not liable for: Fraudulent misrepresentation by Users; Parental negligence in supervising minors; Consequences of minor accessing Platform through unauthorized means;</li>
                <li>(c) <strong>Indemnification:</strong> Users agree to indemnify Platform if their misrepresentation of age causes: Regulatory penalties or fines; Legal defense costs; Reputational harm; Any damages resulting from minor access;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">9.8.3 Regulatory Compliance Efforts:</h4>
            <p className="text-slate-600">Platform maintains records demonstrating: Robust age verification procedures; Prompt deletion upon discovery; Good faith compliance with all applicable laws; Industry-leading child protection measures; Such records serve as defense in potential regulatory inquiries.</p>
        </div>
      </div>
    </section>
  );
}
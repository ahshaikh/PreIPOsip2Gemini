'use client';

import React from 'react';
import { PenTool, Scale, Megaphone, GitCommit, ScrollText, MessageSquare, AlertTriangle } from 'lucide-react';

export default function CookiePolicyPart13() {
  return (
    <section id="part-8" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 8</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">POLICY AMENDMENTS, CHANGE MANAGEMENT, USER NOTIFICATION, AND VERSION CONTROL</h2>
      </div>

      {/* 8.1 */}
      <div id="point-8-1" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <PenTool className="text-indigo-600" size={20} /> 8.1 AMENDMENT AUTHORITY AND GOVERNANCE
        </h3>
        
        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.1.1 Authority to Amend:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Primary Amendment Authority:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Board of Directors of the Company retains ultimate authority to approve material amendments;</li>
                        <li>Data Protection Officer (DPO) authorized to approve non-material technical or administrative amendments;</li>
                        <li>Chief Compliance Officer authorized to approve amendments mandated by regulatory changes;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Amendment Proposal Process:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-2">
                        <li>(i) <strong>Initiation:</strong> Any department may propose amendments via written submission to DPO; DPO maintains Amendment Request Register documenting all proposals; Proposal must include: rationale, impact assessment, legal analysis, User impact evaluation;</li>
                        <li>(ii) <strong>Review:</strong> Legal team reviews for compliance with applicable law; Privacy team assesses impact on User rights and data protection; Technology team evaluates technical feasibility; Compliance team verifies regulatory alignment;</li>
                        <li>(iii) <strong>Approval Workflow:</strong>
                            <div className="overflow-x-auto mt-2 rounded-lg border border-slate-200">
                                <table className="w-full text-sm text-left text-slate-600">
                                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                                        <tr>
                                            <th className="px-6 py-3">Amendment Type</th>
                                            <th className="px-6 py-3">Initial Review</th>
                                            <th className="px-6 py-3">Approval Authority</th>
                                            <th className="px-6 py-3">Timeline</th>
                                            <th className="px-6 py-3">Board Resolution Required</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Material (affecting User rights)</td><td className="px-6 py-4">DPO + Legal</td><td className="px-6 py-4">Board of Directors</td><td className="px-6 py-4">30-60 days</td><td className="px-6 py-4">Yes</td></tr>
                                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Regulatory Compliance</td><td className="px-6 py-4">Compliance Officer</td><td className="px-6 py-4">Chief Compliance Officer</td><td className="px-6 py-4">7-15 days</td><td className="px-6 py-4">No (Board notification)</td></tr>
                                        <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Technical/Administrative</td><td className="px-6 py-4">DPO</td><td className="px-6 py-4">Data Protection Officer</td><td className="px-6 py-4">3-7 days</td><td className="px-6 py-4">No</td></tr>
                                        <tr className="bg-white"><td className="px-6 py-4">Emergency/Security</td><td className="px-6 py-4">CISO + DPO</td><td className="px-6 py-4">Emergency Committee</td><td className="px-6 py-4">24-48 hours</td><td className="px-6 py-4">Yes (retroactive)</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.1.2 Amendment Oversight:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Privacy Impact Assessment (PIA):</p>
                    <p className="text-slate-600 mb-1">For all material amendments, conduct comprehensive PIA including:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) <strong>Necessity Assessment:</strong> Is the amendment necessary and proportionate? Can objectives be achieved through less intrusive means? What is the legal basis for changes?</li>
                        <li>(ii) <strong>Impact Analysis:</strong> How many Users affected? What User rights are impacted? What risks arise from the amendment? What mitigation measures are implemented?</li>
                        <li>(iii) <strong>Stakeholder Consultation:</strong> Input from User representatives or focus groups; Consultation with regulatory authorities if required; Review by independent privacy counsel;</li>
                        <li>(iv) <strong>Documentation:</strong> PIA report maintained for 7 years; Available for regulatory inspection; Summary published for User transparency;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Legal Compliance Review:</p>
                    <p className="text-slate-600 mb-1">Amendments reviewed against:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Information Technology Act, 2000 and IT Rules, 2011;</li>
                        <li>SEBI (Investment Advisers) Regulations, 2013;</li>
                        <li>Prevention of Money Laundering Act, 2002;</li>
                        <li>Companies Act, 2013;</li>
                        <li>Consumer Protection Act, 2019;</li>
                        <li>Contract Act, 1872;</li>
                        <li>International standards (GDPR, CCPA where applicable);</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.1.3 Board Resolution Requirements:</h4>
            <p className="text-slate-600 mb-1">For material amendments, Board resolution must:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Specify effective date of amendment;</li>
                <li>Authorize DPO to implement necessary technical and operational changes;</li>
                <li>Approve User notification communication;</li>
                <li>Direct quarterly reporting on implementation and User response;</li>
                <li>Be documented in Board minutes and Company records;</li>
            </ul>
        </div>
      </div>

      {/* 8.2 */}
      <div id="point-8-2" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Scale className="text-indigo-600" size={20} /> 8.2 CLASSIFICATION OF AMENDMENTS
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.2.1 Material Changes:</h4>
            <p className="text-slate-600 mb-1">Amendments are classified as "material" if they:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1 mb-2">
                <li>(a) <strong>Expand Processing Purposes:</strong> Introduction of new cookie categories; Use of existing cookies for additional purposes; New data elements collected through cookies; Extension of data sharing to new third parties;</li>
                <li>(b) <strong>Diminish User Rights:</strong> Reduction in User control or choice mechanisms; Limitation on erasure or access rights; Extended retention periods (unless legally mandated); Restrictions on objection or withdrawal rights;</li>
                <li>(c) <strong>Increase Privacy Risks:</strong> New cross-border data transfers; Use of more intrusive tracking technologies; Implementation of automated decision-making affecting Users; Sharing with third parties in jurisdictions with weaker protections;</li>
                <li>(d) <strong>Modify Legal Obligations:</strong> Changes to governing law or jurisdiction; Alterations to dispute resolution mechanisms; Modifications to liability limitations; Changes to consent withdrawal procedures;</li>
            </ul>
            <p className="text-slate-600 font-semibold">Examples of Material Changes:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Deploying facial recognition cookies for authentication;</li>
                <li>Sharing cookie data with credit bureaus for credit scoring;</li>
                <li>Using cookies to make automated investment suitability decisions;</li>
                <li>Transferring data to countries without adequacy decisions;</li>
                <li>Extending analytics cookie retention from 24 to 36 months;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.2.2 Non-Material Changes:</h4>
            <p className="text-slate-600 mb-1">Amendments classified as "non-material" include:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1 mb-2">
                <li>(a) <strong>Administrative Updates:</strong> Correction of typographical errors; Clarification of existing provisions without substantive change; Formatting or organizational improvements; Update of contact information (addresses, phone numbers); Addition of explanatory examples;</li>
                <li>(b) <strong>Technical Refinements:</strong> Cookie name changes (same functionality); Update of technical specifications for clarity; Addition of browser-specific instructions; Enhancement of security measures beyond minimum required;</li>
                <li>(c) <strong>Legal References:</strong> Citation of new case law supporting existing practices; Update of statutory references to amended legislation (no practice change); Addition of cross-references to related policies;</li>
                <li>(d) <strong>Compliance Enhancements:</strong> Adoption of higher standards than legally required; Additional User-friendly features (e.g., new deletion tools); Enhanced transparency measures; Stronger security protocols;</li>
            </ul>
            <p className="text-slate-600 font-semibold">Examples of Non-Material Changes:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Correcting "Section 43B" to "Section 43A" (typographical error);</li>
                <li>Adding direct links to third-party privacy policies;</li>
                <li>Updating DPO email from old to new domain;</li>
                <li>Clarifying that "30 days" means "30 calendar days";</li>
                <li>Adding cookie preference center screenshots for clarity;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.2.3 Regulatory Compliance Changes:</h4>
            <p className="text-slate-600 mb-1">Amendments mandated by change in law or regulation:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Legislative Changes:</strong> New IT Rules or amendments to existing Rules; SEBI circulars modifying disclosure or record-keeping requirements; RBI directives on data localization or security; Enactment of comprehensive data protection legislation;</li>
                <li>(b) <strong>Regulatory Guidance:</strong> SEBI clarifications on cookie use in investment advisory; CERT-In guidelines on security incident reporting; MeitY notifications under IT Act; Court judgments affecting data protection practices;</li>
                <li>(c) <strong>Expedited Process:</strong> Fast-track approval to ensure timely compliance; User notification may be concurrent with or shortly after implementation (rather than advance notice); Clear explanation that changes are legally mandated;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.2.4 Emergency Amendments:</h4>
            <p className="text-slate-600 mb-1">Immediate amendments in response to:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Security Incidents:</strong> Data breach requiring immediate security enhancements; Discovery of critical vulnerability; Active cyberattack necessitating defensive measures;</li>
                <li>(b) <strong>Regulatory Orders:</strong> SEBI or RBI directive requiring immediate action; Court injunction or interim order; CERT-In emergency directive;</li>
                <li>(c) <strong>Process:</strong> DPO and CISO jointly authorize emergency amendment; Implementation within 24-48 hours; Retroactive Board approval at next meeting; User notification as soon as practicable (within 7 days); Detailed post-incident report documenting necessity and proportionality;</li>
            </ul>
        </div>
      </div>

      {/* 8.3 */}
      <div id="point-8-3" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Megaphone className="text-indigo-600" size={20} /> 8.3 USER NOTIFICATION FRAMEWORK
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.3.1 Advance Notice Requirements:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Material Changes:</p>
                    <p className="text-slate-600 font-bold mb-1">Minimum Notice Period: 60 days before effective date</p>
                    <p className="text-slate-600 mb-1">Notification Methods (Multi-Channel):</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-2">
                        <li>(i) <strong>Email Notification:</strong> Sent to User's registered email address; Subject line: "Important Update to Cookie Policy - Action May Be Required"; Plain language summary of key changes; Link to full updated Policy with tracked changes; Deadline for objection or consent withdrawal; Consequences of continued use;</li>
                        <li>(ii) <strong>Platform Banner:</strong> Prominent banner on all pages stating "We've updated our Cookie Policy"; Persistent until User acknowledges or 60 days elapse; Click-through to detailed change summary;</li>
                        <li>(iii) <strong>In-App Notification:</strong> Push notification for mobile app Users; Dashboard alert with dismissible notice; Summary of material changes;</li>
                        <li>(iv) <strong>Account Dashboard Notice:</strong> Dedicated section in User account: "Policy Updates"; Side-by-side comparison of old vs. new provisions; Interactive FAQ addressing common concerns;</li>
                        <li>(v) <strong>Postal Mail (for High-Value Users):</strong> Users with investments {'>'}INR 10 lakhs receive physical mail; Registered post with acknowledgment; Suitable for Users who may not regularly check email;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Non-Material Changes:</p>
                    <p className="text-slate-600 font-bold mb-1">Notice Period: Concurrent with effective date</p>
                    <p className="text-slate-600 mb-1">Notification Methods:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Email notification with subject: "Cookie Policy Updated";</li>
                        <li>Update notification in next monthly newsletter or communication;</li>
                        <li>Change log published on Policy page;</li>
                        <li>No action required from Users;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Regulatory Compliance Changes:</p>
                    <p className="text-slate-600 font-bold mb-1">Notice Period: Within 15 days of implementation</p>
                    <p className="text-slate-600 mb-1">Notification Methods:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Email with subject: "Cookie Policy Updated for Legal Compliance";</li>
                        <li>Explanation of regulatory requirement driving change;</li>
                        <li>Assurance that changes do not expand Platform's data use beyond legal mandate;</li>
                        <li>Link to relevant regulation or SEBI circular;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(d) Emergency Amendments:</p>
                    <p className="text-slate-600 font-bold mb-1">Notice Period: Within 7 days of implementation</p>
                    <p className="text-slate-600 mb-1">Notification Methods:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Immediate email to all active Users;</li>
                        <li>Subject: "Urgent Cookie Policy Update - Security Enhancement";</li>
                        <li>Explanation of emergency circumstances;</li>
                        <li>Assurance regarding User rights protection;</li>
                        <li>Commitment to full transparency post-incident resolution;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.3.2 Content of Notification:</h4>
            <p className="text-slate-600 mb-2">All User notifications must include:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Identification of Amendment:</strong> Policy name and version number (e.g., "Cookie Policy v2.0"); Effective date of amendment; Brief description: "This update modifies how we use analytics cookies";</li>
                <li>(b) <strong>Summary of Changes:</strong> Layered disclosure: short summary (2-3 sentences) + detailed explanation; Bullet points for easy scanning; Plain language without excessive legal jargon; Examples illustrating practical impact;</li>
                <li>(c) <strong>Reason for Changes:</strong> "To comply with new SEBI regulations..."; "To enhance User privacy and control..."; "To improve Platform security following industry best practices...";</li>
                <li>(d) <strong>Impact on Users:</strong> "This change may affect how we personalize your experience"; "You may need to update your cookie preferences"; "No action required - your existing consents remain valid";</li>
                <li>(e) <strong>User Actions and Rights:</strong> Options available: accept, object, withdraw consent, close account; Deadline for action (for material changes requiring consent); Instructions for exercising rights; Consequences of each choice;</li>
                <li>(f) <strong>Contact Information:</strong> DPO email and phone for questions; Link to FAQ or help center; Invitation to reach out with concerns;</li>
                <li>(g) <strong>Assurances:</strong> Commitment to User privacy and data protection; Confirmation of regulatory compliance; Transparency regarding ongoing data protection efforts;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.3.3 Acknowledgment and Consent Mechanisms:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) For Material Changes Requiring Fresh Consent:</p>
                    <p className="text-slate-600 font-semibold mb-1">Explicit Opt-In Required:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>User presented with revised cookie consent banner;</li>
                        <li>Clear "Accept" and "Reject" options (no pre-ticked boxes);</li>
                        <li>"Continue without accepting" option with service limitations explained;</li>
                        <li>Granular control maintained (accept some, reject other categories);</li>
                    </ul>
                    <p className="text-slate-600 font-semibold mt-2 mb-1">Consent Refresh Process:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>User login triggers consent request modal;</li>
                        <li>Cannot access full Platform functionality until consent decision made;</li>
                        <li>Option to defer decision and review detailed Policy;</li>
                        <li>Consent decision logged with timestamp and IP address;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) For Material Changes Not Requiring Fresh Consent:</p>
                    <p className="text-slate-600 font-semibold mb-1">Acknowledgment Mechanism:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>User acknowledges notification receipt and Policy review;</li>
                        <li>Checkbox: "I have read and understand the updated Cookie Policy";</li>
                        <li>Continued use after 60-day notice period constitutes acceptance (clearly stated);</li>
                        <li>Option to object or withdraw consent remains available;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) For Non-Material Changes:</p>
                    <p className="text-slate-600 font-semibold mb-1">Passive Acceptance:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Continued use of Platform constitutes acceptance;</li>
                        <li>No explicit acknowledgment required;</li>
                        <li>Users retain right to review changes and object if desired;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.3.4 Multi-Lingual Notification:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Language Availability:</strong> Notifications available in English and Hindi (mandatory); Additional regional languages upon request: Tamil, Telugu, Gujarati, Marathi, Bengali, Kannada; User's language preference (set in account) determines notification language;</li>
                <li>(b) <strong>Translation Accuracy:</strong> Professional legal translation services engaged; Translations reviewed by native speakers with legal expertise; Disclaimer: "In case of discrepancy, English version prevails";</li>
                <li>(c) <strong>Accessibility:</strong> Screen-reader compatible HTML emails; High contrast versions for visually impaired; Text-to-speech option in Platform interface; Large print PDF versions available upon request;</li>
            </ul>
        </div>
      </div>

      {/* 8.4 */}
      <div id="point-8-4" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <GitCommit className="text-indigo-600" size={20} /> 8.4 VERSION CONTROL AND DOCUMENT MANAGEMENT
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.4.1 Versioning System:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Version Numbering Convention:</p>
                    <p className="text-slate-600 font-mono mb-1">Format: v[MAJOR].[MINOR].[PATCH]</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li><strong>MAJOR:</strong> Incremented for material changes requiring Board approval and User consent refresh (e.g., v1.0 → v2.0);</li>
                        <li><strong>MINOR:</strong> Incremented for non-material changes or regulatory compliance updates (e.g., v2.0 → v2.1);</li>
                        <li><strong>PATCH:</strong> Incremented for administrative corrections or typographical fixes (e.g., v2.1 → v2.1.1);</li>
                    </ul>
                    <p className="text-slate-600 mt-2 font-semibold">Examples:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>v1.0 (Initial Policy, [Date])</li>
                        <li>v1.1 (Addition of new third-party analytics provider, [Date])</li>
                        <li>v2.0 (Introduction of AI-based personalization requiring fresh consent, [Date])</li>
                        <li>v2.0.1 (Correction of DPO email address, [Date])</li>
                        <li>v2.1 (Update for SEBI cybersecurity circular compliance, [Date])</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Effective Date Tracking:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Each version includes prominent "Effective Date" and "Last Updated" fields;</li>
                        <li>Effective date: when Policy legally binds new Users and governs new processing;</li>
                        <li>Last updated: when document was technically modified (may include non-substantive changes);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Change Log Maintenance:</p>
                    <p className="text-slate-600 mb-1">Comprehensive Change Log Published at [URL]/cookie-policy/changelog:</p>
                    <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                        <table className="w-full text-sm text-left text-slate-600">
                            <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th className="px-6 py-3">Version</th>
                                    <th className="px-6 py-3">Effective Date</th>
                                    <th className="px-6 py-3">Change Type</th>
                                    <th className="px-6 py-3">Summary of Changes</th>
                                    <th className="px-6 py-3">Sections Modified</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">v2.1</td><td className="px-6 py-4">[Date]</td><td className="px-6 py-4">Regulatory Compliance</td><td className="px-6 py-4">Updated breach notification timeline per CERT-In Directions 2022</td><td className="px-6 py-4">Section 6.7.4(a)</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">v2.0.1</td><td className="px-6 py-4">[Date]</td><td className="px-6 py-4">Administrative</td><td className="px-6 py-4">Corrected DPO email address</td><td className="px-6 py-4">Section 8.3.2(f)</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">v2.0</td><td className="px-6 py-4">[Date]</td><td className="px-6 py-4">Material Change</td><td className="px-6 py-4">Added AI-driven investment personalization; requires fresh consent</td><td className="px-6 py-4">Sections 2.4, 3.2.2, 4.10</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">v1.1</td><td className="px-6 py-4">[Date]</td><td className="px-6 py-4">Non-Material</td><td className="px-6 py-4">Added Hotjar as analytics provider</td><td className="px-6 py-4">Section 5.2.1</td></tr>
                                <tr className="bg-white"><td className="px-6 py-4">v1.0</td><td className="px-6 py-4">[Date]</td><td className="px-6 py-4">Initial Version</td><td className="px-6 py-4">Initial publication of Cookie Policy</td><td className="px-6 py-4">All sections</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(d) Change Highlighting:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Updated Policy document includes track changes or highlighted sections showing modifications;</li>
                        <li>Side-by-side comparison view available: old version | new version;</li>
                        <li>Color coding: additions in green, deletions in red, modifications in yellow;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.4.2 Archival of Historical Versions:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Perpetual Archival:</strong> All previous versions of Cookie Policy maintained indefinitely; Archived versions available at: [URL]/cookie-policy/archive/v[version-number]; Each archived version clearly marked "SUPERSEDED - For Historical Reference Only"; Links to next and previous versions for chronological navigation;</li>
                <li>(b) <strong>Purpose of Archival:</strong> Legal defense: demonstrating practices in effect at specific time; Regulatory audit: providing historical compliance documentation; User inquiries: addressing questions about past practices; Internal analysis: tracking evolution of privacy practices;</li>
                <li>(c) <strong>Search and Retrieval:</strong> Archive indexed by version, date, and keywords; Search functionality to locate specific historical provisions; Downloadable PDF versions for offline reference; API access for programmatic retrieval (for legal/compliance teams);</li>
                <li>(d) <strong>Certification:</strong> Each archived version includes cryptographic hash (SHA-256) proving authenticity; Timestamp from trusted timestamping authority; Digital signature of authorized signatory; Ensures non-repudiation and evidential value in legal proceedings;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.4.3 Cross-Referencing and Integration:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Internal Cross-References:</p>
                    <p className="text-slate-600 mb-1">Cookie Policy version aligned with corresponding Privacy Policy, Terms of Service versions; Matrix maintained showing version compatibility:</p>
                    <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                        <table className="w-full text-sm text-left text-slate-600">
                            <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th className="px-6 py-3">Cookie Policy</th>
                                    <th className="px-6 py-3">Privacy Policy</th>
                                    <th className="px-6 py-3">Terms of Service</th>
                                    <th className="px-6 py-3">Effective Date Range</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">v2.1</td><td className="px-6 py-4">v3.2</td><td className="px-6 py-4">v4.5</td><td className="px-6 py-4">[Date] - Present</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">v2.0</td><td className="px-6 py-4">v3.1</td><td className="px-6 py-4">v4.5</td><td className="px-6 py-4">[Date] - [Date]</td></tr>
                                <tr className="bg-white"><td className="px-6 py-4">v1.1</td><td className="px-6 py-4">v3.0</td><td className="px-6 py-4">v4.3</td><td className="px-6 py-4">[Date] - [Date]</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) External References:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Policy cites specific statutory provisions with version effective at time;</li>
                        <li>Updates to cited laws trigger review of Policy for continued accuracy;</li>
                        <li>Footnotes or endnotes provide full legal citations;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Policy Suite Consistency:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Amendments to Cookie Policy reviewed for consistency with related policies;</li>
                        <li>Definitions, terms, and processes aligned across policy suite;</li>
                        <li>Conflicts resolved through specific precedence rules (per Part 3, Section 3.7.3);</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.4.4 Digital Signature and Authentication:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Authoritative Version:</strong> Published version on Platform website ([URL]/cookie-policy) is sole authoritative version; Digitally signed by DPO and CTO using public key infrastructure (PKI); Digital signature verifiable using Platform's public key;</li>
                <li>(b) <strong>Tamper-Evident Technology:</strong> Blockchain-based timestamping for critical Policy versions; Immutable record of publication date and content hash; Third-party verification service (e.g., Proof of Existence) for additional assurance;</li>
                <li>(c) <strong>PDF Versions:</strong> Downloadable PDF includes: Digital signature metadata; Certification by Platform's legal counsel; QR code linking to online verification page; Watermark: "Official Version - [Date]";</li>
            </ul>
        </div>
      </div>

      {/* 8.5 */}
      <div id="point-8-5" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <ScrollText className="text-indigo-600" size={20} /> 8.5 CONTINUED USE AS ACCEPTANCE
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.5.1 Legal Doctrine:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Browsewrap vs. Clickwrap:</p>
                    <p className="text-slate-600 mb-1"><strong>Browsewrap (Traditional Approach):</strong></p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>User's continued use of Platform after notice of Policy changes constitutes acceptance;</li>
                        <li>Valid under Indian contract law if reasonable notice provided;</li>
                        <li>Courts scrutinize whether User had actual or constructive notice;</li>
                    </ul>
                    <p className="text-slate-600 mb-1 mt-2"><strong>Clickwrap (Enhanced Approach for Material Changes):</strong></p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>User required to affirmatively accept updated Policy;</li>
                        <li>Higher enforceability standard;</li>
                        <li>Reduces ambiguity and strengthens consent validity;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Platform's Hybrid Approach:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li><strong>Material Changes:</strong> Clickwrap - explicit acceptance required;</li>
                        <li><strong>Non-Material Changes:</strong> Browsewrap - continued use constitutes acceptance;</li>
                        <li>Clear communication regarding acceptance mechanism for each amendment type;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.5.2 Notice Standards for Browsewrap Validity:</h4>
            <p className="text-slate-600 mb-1">For continued use to constitute valid acceptance, Platform ensures:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Conspicuous Notice:</strong> Prominent banner on all pages; Contrasting colors (e.g., yellow background, black text); Above-the-fold placement; Persistent (not dismissible until viewed);</li>
                <li>(b) <strong>Reasonable Opportunity to Review:</strong> Minimum 60 days advance notice for material changes; Policy accessible via clear, understandable link; Summary of changes provided, not just link to full Policy; FAQ addressing anticipated User concerns;</li>
                <li>(c) <strong>Clear Language:</strong> "By continuing to use our Platform after [Effective Date], you agree to the updated Cookie Policy"; "If you do not agree, please discontinue use and close your account"; "You have until [Date] to object or withdraw consent";</li>
                <li>(d) <strong>Documented Notice:</strong> Records maintained proving notice was provided: Email delivery receipts; Banner impression logs; In-app notification delivery confirmations; Evidence available for litigation if acceptance disputed;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.5.3 Objection and Opt-Out Rights:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Right to Object:</strong> Users may object to material amendments within 60-day notice period; Objection methods: email to dpo@preiposip.com, online objection form, written letter; Platform evaluates objection and responds within 15 days;</li>
                <li>(b) <strong>Service Suspension Option:</strong> For Users unwilling to accept material changes; Suspend account (not close) for up to 12 months; No fees charged during suspension; Investment holdings maintained; User may reactivate by accepting updated Policy;</li>
                <li>(c) <strong>Account Closure Option:</strong> Users dissatisfied with amendments may close account; Expedited closure process (15 days instead of standard 30); Assistance provided in transferring investments; Clear explanation of data retention obligations despite closure;</li>
                <li>(d) <strong>Partial Acceptance/Rejection:</strong> For cookie-specific changes, Users may: Accept some amended cookie categories; Reject others; Continue using Platform with adjusted functionality; Granular control maintained through preference center;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.5.4 Regulatory Compliance and Fairness:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Consumer Protection Act, 2019:</strong> Amendments comply with unfair contract terms provisions; Material amendments not imposed unilaterally without genuine choice; Users always retain option to discontinue relationship;</li>
                <li>(b) <strong>Contract Act, 1872:</strong> Amendments proposed in good faith; No coercion or undue influence; Reasonable notice and opportunity to decline; Consideration: continued provision of Platform services;</li>
                <li>(c) <strong>SEBI Investor Protection:</strong> Amendments do not prejudice User's investment rights; Investment holdings unaffected by cookie policy amendments; Clear distinction between platform policy and investment terms;</li>
            </ul>
        </div>
      </div>

      {/* 8.6 */}
      <div id="point-8-6" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <MessageSquare className="text-indigo-600" size={20} /> 8.6 COMMUNICATION TEMPLATES AND SAMPLES
        </h3>

        <div className="mb-6 space-y-6">
            <div className="bg-slate-50 p-6 rounded-lg border border-slate-200">
                <h4 className="text-lg font-bold text-slate-700 mb-3">8.6.1 Material Change Notification Template:</h4>
                <hr className="border-slate-300 mb-3"/>
                <p className="text-slate-800 font-bold">Subject: Important Update to PreIPOSIP Cookie Policy - Review Required</p>
                <p className="text-slate-600 mt-2">Dear [User Name],</p>
                <p className="text-slate-600 mt-2">We are writing to inform you of important updates to our Cookie Policy that will take effect on [Effective Date, 60 days from now].</p>
                <p className="text-slate-600 mt-2"><strong>What's Changing?</strong><br/>[2-3 sentence plain language summary, e.g., "We are introducing enhanced analytics tools to better understand how investors use our Platform. This will involve deploying additional cookies that track your interactions with investment opportunities. We believe this will help us improve our service, but we want your explicit consent."]</p>
                <p className="text-slate-600 mt-2"><strong>Why This Change?</strong><br/>[Brief explanation: regulatory requirement / enhanced security / service improvement]</p>
                <p className="text-slate-600 mt-2"><strong>What This Means for You:</strong><br/>- [Impact 1: e.g., "You will see a new cookie consent prompt when you next log in"]<br/>- [Impact 2: e.g., "You can choose to accept or decline these new cookies"]<br/>- [Impact 3: e.g., "Declining will not affect your ability to invest, but some personalization features will be unavailable"]</p>
                <p className="text-slate-600 mt-2"><strong>Action Required:</strong><br/>Please review the updated Cookie Policy by [Date]. When you next log in, you will be asked to accept or decline the updated terms.</p>
                <p className="text-slate-600 mt-2"><strong>Your Rights:</strong><br/>- Review the full updated Policy: [Link]<br/>- See side-by-side comparison of changes: [Link]<br/>- Object to the changes: [Link to objection form]<br/>- Close your account (if you disagree): [Link to account closure]</p>
                <p className="text-slate-600 mt-2"><strong>Questions?</strong><br/>Our Data Protection Officer is available to address your concerns:<br/>- Email: dpo@preiposip.com<br/>- Phone: [Number]<br/>- FAQ: [Link]</p>
                <p className="text-slate-600 mt-2">We value your privacy and trust. Thank you for being a valued member of the PreIPOSIP community.</p>
                <p className="text-slate-600 mt-2">Sincerely,<br/>[Name], Data Protection Officer<br/>PreIPOSIP.com</p>
            </div>

            <div className="bg-slate-50 p-6 rounded-lg border border-slate-200">
                <h4 className="text-lg font-bold text-slate-700 mb-3">8.6.2 Non-Material Change Notification Template:</h4>
                <hr className="border-slate-300 mb-3"/>
                <p className="text-slate-800 font-bold">Subject: PreIPOSIP Cookie Policy Updated</p>
                <p className="text-slate-600 mt-2">Dear [User Name],</p>
                <p className="text-slate-600 mt-2">We have made minor updates to our Cookie Policy, effective [Date].</p>
                <p className="text-slate-600 mt-2"><strong>What Changed?</strong><br/>[Brief description: e.g., "We've updated our contact information and clarified how you can delete cookies through your browser settings."]</p>
                <p className="text-slate-600 mt-2"><strong>Do You Need to Do Anything?</strong><br/>No action is required. These updates do not affect how we collect or use your information.</p>
                <p className="text-slate-600 mt-2"><strong>Review the Changes:</strong><br/>Updated Policy: [Link]<br/>Change Log: [Link]</p>
                <p className="text-slate-600 mt-2">If you have questions, contact us at dpo@preiposip.com.</p>
                <p className="text-slate-600 mt-2">Best regards,<br/>PreIPOSIP Team</p>
            </div>

            <div className="bg-slate-50 p-6 rounded-lg border border-slate-200">
                <h4 className="text-lg font-bold text-slate-700 mb-3">8.6.3 Regulatory Compliance Change Notification:</h4>
                <hr className="border-slate-300 mb-3"/>
                <p className="text-slate-800 font-bold">Subject: Cookie Policy Updated for Legal Compliance</p>
                <p className="text-slate-600 mt-2">Dear [User Name],</p>
                <p className="text-slate-600 mt-2">To comply with new regulations issued by [SEBI/CERT-In/RBI], we have updated our Cookie Policy, effective [Date].</p>
                <p className="text-slate-600 mt-2"><strong>Regulatory Requirement:</strong><br/>[Brief description of regulation: e.g., "CERT-In now requires breach notification within 6 hours. We have updated our Policy to reflect this faster notification timeline."]</p>
                <p className="text-slate-600 mt-2"><strong>Impact on You:</strong><br/>[Explanation: e.g., "This change benefits you by ensuring faster notification if any security incident occurs."]</p>
                <p className="text-slate-600 mt-2"><strong>Required by Law:</strong><br/>These changes are mandated by Indian law and are not optional. Your continued use of the Platform indicates acceptance of these legally required updates.</p>
                <p className="text-slate-600 mt-2"><strong>Review the Changes:</strong><br/>Updated Policy: [Link]<br/>Relevant Regulation: [Link to SEBI/CERT-In circular]</p>
                <p className="text-slate-600 mt-2">Questions? Contact dpo@preiposip.com.</p>
                <p className="text-slate-600 mt-2">Sincerely,<br/>[Name], Chief Compliance Officer<br/>PreIPOSIP.com</p>
            </div>
        </div>
      </div>

      {/* 8.7 */}
      <div id="point-8-7" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <AlertTriangle className="text-indigo-600" size={20} /> 8.7 DISPUTE RESOLUTION FOR POLICY AMENDMENTS
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.7.1 Internal Dispute Resolution:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>User Complaints Regarding Amendments:</strong> Complaints submitted to Grievance Officer (per Part 4, Section 4.11); Expedited review within 7 days for amendment-related complaints; DPO and legal counsel evaluate User concerns; Response provided explaining rationale or offering accommodation;</li>
                <li>(b) <strong>Possible Resolutions:</strong> Clarification that change is non-material or misunderstood; Modification of amendment if User raises valid concerns affecting substantial number of Users; Grant of exception or customized terms for User (if feasible and not creating regulatory issues); Account suspension or closure without penalty if resolution not possible;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.7.2 Regulatory Escalation:</h4>
            <p className="text-slate-600 mb-1">If User dissatisfied with Platform's resolution:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Complaint to SEBI (via SCORES portal);</li>
                <li>Complaint to Ministry of Electronics and IT;</li>
                <li>Consumer forum complaint under Consumer Protection Act;</li>
                <li>Civil suit (as last resort);</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">8.7.3 Class Action Considerations:</h4>
            <p className="text-slate-600 mb-1">In the event multiple Users object to material amendments:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Platform may conduct User survey or hold virtual town hall;</li>
                <li>Consider postponing amendment to address widespread concerns;</li>
                <li>Engage independent privacy counsel to review amendment fairness;</li>
                <li>Document good faith efforts for potential defense in class action;</li>
            </ul>
        </div>
      </div>
    </section>
  );
}
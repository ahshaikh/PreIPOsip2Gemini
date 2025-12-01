'use client';

import React from 'react';
import { Shield, Eye, Edit, Trash2, ArrowRightLeft } from 'lucide-react';

export default function CookiePolicyPart6() {
  return (
    <section id="part-4" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 4</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">USER RIGHTS, EXERCISE PROCEDURES, AND REMEDIAL MECHANISMS</h2>
      </div>

      {/* 4.1 */}
      <div id="point-4-1" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Shield className="text-indigo-600" size={20} /> 4.1 FUNDAMENTAL RIGHTS FRAMEWORK
        </h3>
        <p className="text-slate-600 mb-2">The Platform recognizes and upholds Users' fundamental rights relating to Personal Information processed through cookies and tracking technologies. These rights derive from:</p>
        <ul className="list-none pl-4 text-slate-600 space-y-1">
            <li>(a) Constitutional protection of privacy under Article 21 of the Constitution of India as interpreted in <em>Justice K.S. Puttaswamy (Retd.) v. Union of India</em> (2017) 10 SCC 1;</li>
            <li>(b) Statutory rights under the Information Technology Act, 2000 and IT Rules, 2011;</li>
            <li>(c) Contractual rights arising from this Policy and related agreements;</li>
            <li>(d) International standards including GDPR (for EEA Users) and CCPA (for California Users);</li>
        </ul>
      </div>

      {/* 4.2 */}
      <div id="point-4-2" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4">4.2 RIGHT TO INFORMATION AND TRANSPARENCY</h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.2.1 Scope of Right:</h4>
            <p className="text-slate-600 mb-2">Every User has the right to receive clear, transparent, intelligible, and easily accessible information regarding:</p>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Identity of Data Controller:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Legal name, registered address, and contact details of the Platform entity;</li>
                        <li>Registration numbers (CIN under Companies Act, SEBI Investment Adviser Registration);</li>
                        <li>Contact information for Data Protection Officer (DPO);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Processing Operations:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Categories of cookies deployed and their specific purposes;</li>
                        <li>Legal bases justifying each category of processing;</li>
                        <li>Duration for which cookies remain active;</li>
                        <li>Categories of third parties with whom data may be shared;</li>
                        <li>Countries to which data may be transferred and applicable safeguards;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) User Rights:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Comprehensive explanation of all rights available to Users;</li>
                        <li>Procedures for exercising rights and expected response timelines;</li>
                        <li>Right to lodge complaints with regulatory authorities;</li>
                        <li>Availability of judicial remedies;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.2.2 Platform's Transparency Obligations:</h4>
            <p className="text-slate-600 mb-2">The Platform fulfills its transparency obligations through:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>This Cookie Policy:</strong> Providing exhaustive documentation of cookie practices;</li>
                <li>(b) <strong>Just-in-Time Notices:</strong> Contextual information provided at point of data collection through: Cookie consent banners with layered information design; Tooltips and information icons explaining specific data collection; In-app notifications for new processing activities;</li>
                <li>(c) <strong>Privacy Dashboard:</strong> Dedicated User interface displaying: Active cookies currently deployed on User's device; Personal Information collected and processing purposes; Third parties who have received User data; Historical log of User's consent decisions;</li>
                <li>(d) <strong>Regular Communications:</strong> Proactive notifications regarding: Material changes to cookie practices or this Policy; Annual privacy checkups and consent refresh opportunities; Data breach notifications as required by law;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.2.3 Language and Accessibility:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) This Policy is available in English and Hindi, with additional regional language translations provided upon request;</li>
                <li>(b) Plain language summaries accompany legal terminology;</li>
                <li>(c) Audio and large-print versions available for Users with disabilities;</li>
                <li>(d) Visual aids, infographics, and videos supplement textual explanations;</li>
            </ul>
        </div>
      </div>

      {/* 4.3 */}
      <div id="point-4-3" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Eye className="text-indigo-600" size={20} /> 4.3 RIGHT OF ACCESS (DATA SUBJECT ACCESS REQUEST)
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.3.1 Scope of Right:</h4>
            <p className="text-slate-600 mb-2">Users have the right to obtain from the Platform:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Confirmation:</strong> Whether Personal Information relating to the User is being processed through cookies;</li>
                <li>(b) <strong>Access to Data:</strong> A copy of all Personal Information undergoing processing, including: Cookie identifiers associated with the User; Timestamps of cookie deployment and expiration; Purposes for which each cookie was deployed; Categories of data collected through each cookie; Third parties to whom cookie data has been disclosed; Geographic locations where data is stored;</li>
                <li>(c) <strong>Processing Information:</strong> Source of data if not collected directly from User; Existence of automated decision-making, including profiling; Logic involved in algorithmic processing; Significance and envisaged consequences of such processing; Safeguards applied for international data transfers;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.3.2 Exercise Procedure:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Submission of Access Request:</p>
                    <p className="text-slate-600 mb-1">Users may submit Data Subject Access Requests (DSAR) through:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) <strong>Online Portal:</strong> Dedicated DSAR submission form accessible via User account dashboard at [URL];</li>
                        <li>(ii) <strong>Email:</strong> Written request sent to dpo@preiposip.com with subject "DSAR - Cookie Data Access Request";</li>
                        <li>(iii) <strong>Postal Mail:</strong> Written request sent to: Data Protection Officer, [Legal Entity Name], [Registered Address], [City, State, PIN Code]</li>
                        <li>(iv) <strong>In-Person:</strong> By visiting the Platform's registered office during business hours with prior appointment;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Identity Verification:</p>
                    <p className="text-slate-600 mb-1">To protect against fraudulent requests, the Platform requires identity verification through:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Login to User account using existing credentials (preferred method);</li>
                        <li>(ii) Submission of government-issued photo ID (Aadhaar, PAN, Passport, Driving License);</li>
                        <li>(iii) Answering security questions related to User's account history;</li>
                        <li>(iv) Video verification call with Platform's identity verification team;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Information to be Provided in Request:</p>
                    <p className="text-slate-600 mb-1">To facilitate efficient processing, Users should provide:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Full name and registered email address;</li>
                        <li>(ii) User ID or account number (if known);</li>
                        <li>(iii) Specific time period for which data is requested (if applicable);</li>
                        <li>(iv) Preferred format for data delivery (PDF, CSV, JSON, etc.);</li>
                        <li>(v) Preferred delivery method (secure download link, encrypted email, physical mail);</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.3.3 Platform Response Timeline:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Initial Acknowledgment:</strong> Within 72 hours of receiving a valid DSAR;</li>
                <li>(b) <strong>Substantive Response:</strong> Within 30 days for straightforward requests; Within 60 days for complex requests involving large volumes of data or third-party coordination; Within 90 days for exceptionally complex requests, with interim updates every 30 days;</li>
                <li>(c) <strong>Extensions:</strong> If additional time is required, the Platform will: Notify the User within the initial 30-day period; Explain reasons for extension; Provide estimated completion date;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.3.4 Format of Access Response:</h4>
            <p className="text-slate-600 mb-1">The Platform provides access in:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Structured Format:</strong> Machine-readable format (JSON, XML, CSV) enabling data portability;</li>
                <li>(b) <strong>Human-Readable Format:</strong> PDF report with clear explanations and context;</li>
                <li>(c) <strong>Secure Delivery:</strong> Via encrypted email, password-protected download link, or secure portal access;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.3.5 Charges and Fees:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>First Request:</strong> Provided free of charge;</li>
                <li>(b) <strong>Subsequent Requests:</strong> Where requests are manifestly unfounded, excessive, or repetitive (more than once every six months), the Platform may charge a reasonable fee based on administrative costs, not exceeding INR 500 per request;</li>
                <li>(c) <strong>Large Volume Requests:</strong> For requests requiring extensive manual retrieval or third-party costs, actual costs may be charged with prior notice and User consent;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.3.6 Limitations on Access Right:</h4>
            <p className="text-slate-600 mb-2">Access may be denied or restricted where:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Legal Privilege:</strong> Data is subject to attorney-client privilege or legal professional privilege;</li>
                <li>(b) <strong>Third-Party Rights:</strong> Disclosure would adversely affect rights and freedoms of other individuals;</li>
                <li>(c) <strong>Trade Secrets:</strong> Information contains confidential commercial information or trade secrets;</li>
                <li>(d) <strong>Ongoing Investigations:</strong> Data is part of ongoing fraud investigation, litigation, or regulatory inquiry;</li>
                <li>(e) <strong>National Security:</strong> Disclosure is prohibited under national security or law enforcement provisions;</li>
                <li>(f) <strong>Statutory Prohibitions:</strong> Disclosure is explicitly prohibited by law (e.g., PMLA restrictions on tipping off);</li>
            </ul>
            <p className="text-slate-600 mt-2">Where access is denied or restricted, the Platform provides: Written explanation of grounds for denial; Reference to applicable legal provision; Information about User's right to challenge the decision;</p>
        </div>
      </div>

      {/* 4.4 */}
      <div id="point-4-4" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Edit className="text-indigo-600" size={20} /> 4.4 RIGHT TO RECTIFICATION
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.4.1 Scope of Right:</h4>
            <p className="text-slate-600 mb-2">Users have the right to obtain rectification of inaccurate Personal Information and completion of incomplete data processed through cookies, including:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) Correction of erroneous cookie-derived data (e.g., incorrect location data);</li>
                <li>(b) Updating of User preferences and profile information;</li>
                <li>(c) Amendment of investment objectives and risk tolerance assessments;</li>
                <li>(d) Correction of KYC information and contact details;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.4.2 Exercise Procedure:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Online Rectification:</strong> Users can directly update most information through: Account settings dashboard; Profile management interface; Cookie preference center;</li>
                <li>(b) <strong>Assisted Rectification:</strong> For data Users cannot directly modify: Submit rectification request via email to dpo@preiposip.com; Use "Report Incorrect Data" feature in User dashboard; Contact customer support via chat, phone, or email;</li>
                <li>(c) <strong>Required Information:</strong> Specific data element requiring correction; Current (incorrect) value and proposed (correct) value; Supporting documentation where applicable (e.g., updated ID for KYC correction);</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.4.3 Platform Response Timeline:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Minor Corrections:</strong> Within 48 hours for profile and preference updates;</li>
                <li>(b) <strong>KYC Updates:</strong> Within 7 business days, subject to re-verification requirements;</li>
                <li>(c) <strong>Complex Corrections:</strong> Within 30 days for data requiring third-party coordination or system updates;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.4.4 Third-Party Notification:</h4>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Notifies such third parties of the rectification unless impossible or involves disproportionate effort;</li>
                <li>Provides User with list of notified third parties upon request;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.4.5 Historical Record Preservation:</h4>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Maintains audit trail of corrections with timestamps;</li>
                <li>Preserves original data in archived form for statutory retention period;</li>
                <li>Clearly marks corrected data as "amended" with version history;</li>
            </ul>
        </div>
      </div>

      {/* 4.5 */}
      <div id="point-4-5" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Trash2 className="text-indigo-600" size={20} /> 4.5 RIGHT TO ERASURE ("RIGHT TO BE FORGOTTEN")
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.5.1 Scope of Right:</h4>
            <p className="text-slate-600 mb-2">Users have the right to obtain erasure of Personal Information processed through cookies where:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Purpose Fulfilled:</strong> Data is no longer necessary for purposes for which it was collected;</li>
                <li>(b) <strong>Consent Withdrawn:</strong> User withdraws consent on which processing is based and no other legal ground exists;</li>
                <li>(c) <strong>Legitimate Objection:</strong> User objects to processing based on legitimate interests and no overriding legitimate grounds exist;</li>
                <li>(d) <strong>Unlawful Processing:</strong> Data has been unlawfully processed;</li>
                <li>(e) <strong>Legal Obligation:</strong> Erasure is required for compliance with legal obligation;</li>
                <li>(f) <strong>Minors:</strong> Data was collected from a minor without proper parental consent;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.5.2 Exercise Procedure:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Self-Service Deletion:</strong> Users can delete certain data through account settings; Clear browser cookies and cache for immediate cookie removal; Disable cookies through cookie preference center;</li>
                <li>(b) <strong>Full Account Deletion Request:</strong> Submit written request to dpo@preiposip.com with subject "Account Deletion Request"; Complete account closure form available in User dashboard; Identity verification required to prevent fraudulent deletion;</li>
                <li>(c) <strong>Selective Data Deletion:</strong> Specify particular data categories for deletion; Explain grounds for erasure request;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.5.3 Platform Response Timeline:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Cookie Deletion:</strong> Immediate upon User action through browser or preference center;</li>
                <li>(b) <strong>Account Data Deletion:</strong> Within 30 days of verified request;</li>
                <li>(c) <strong>Backup Deletion:</strong> Within 90 days from backup systems and disaster recovery archives;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.5.4 Limitations on Erasure Right:</h4>
            <p className="text-slate-600 mb-2">Erasure may be refused where retention is necessary for:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Legal Obligations:</strong> PMLA requirement to maintain records for five years from transaction date; SEBI Investment Advisers Regulation requirement to maintain records for ten years; Income Tax Act requirement to maintain records for assessment and reassessment periods; Companies Act requirement to maintain member registers and financial records;</li>
                <li>(b) <strong>Contractual Obligations:</strong> Ongoing contractual relationship requiring data retention; Performance of obligations under investment agreements; Calculation and payment of returns on investments;</li>
                <li>(c) <strong>Legal Claims:</strong> Establishment, exercise, or defense of legal claims; Pending or anticipated litigation involving User; Regulatory investigations or enforcement actions;</li>
                <li>(d) <strong>Public Interest:</strong> Compliance with court orders or regulatory directives; Prevention and detection of fraud and financial crime; Protection of rights and safety of other Users;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.5.5 Anonymization Alternative:</h4>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Pseudonymization or anonymization of data to render it non-identifiable;</li>
                <li>Restriction of processing to storage only with no active use;</li>
                <li>Logical deletion (marking as deleted) while maintaining technical retention for compliance;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.5.6 Third-Party Erasure:</h4>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Takes reasonable steps to inform third parties of erasure request;</li>
                <li>Requests third parties to erase links to, copies of, or replication of the data;</li>
                <li>Documents efforts undertaken for User's records;</li>
            </ul>
        </div>
      </div>

      {/* 4.6 */}
      <div id="point-4-6" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <ArrowRightLeft className="text-indigo-600" size={20} /> 4.6 RIGHT TO DATA PORTABILITY
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.6.1 Scope of Right:</h4>
            <p className="text-slate-600 mb-2">Users have the right to:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Receive Personal Information</strong> in a structured, commonly used, and machine-readable format;</li>
                <li>(b) <strong>Transmit Data</strong> to another service provider (where technically feasible);</li>
            </ul>
            <p className="text-slate-600 mt-1">This right applies to data: Provided by User to Platform; Processed on basis of User consent or contractual necessity; Processed by automated means (including cookie data);</p>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.6.2 Data Included in Portability:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>User Profile Information:</strong> Account details, contact information, preferences; KYC documentation and verification status; Investment profile and risk assessments;</li>
                <li>(b) <strong>Cookie-Derived Data:</strong> User preferences and customization settings; Browsing history and interaction logs; Analytics data (to extent processed based on consent);</li>
                <li>(c) <strong>Transaction History:</strong> Investment transactions and portfolio holdings; Payment history and statements; Document repository (prospectuses, agreements, confirmations);</li>
                <li>(d) <strong>Communication Records:</strong> Email correspondence with Platform; Chat transcripts and support tickets; Notifications and alerts received;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.6.3 Excluded Data:</h4>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Inferred or derived data generated by Platform's algorithms;</li>
                <li>Data belonging to or identifying other Users;</li>
                <li>Proprietary Platform data or trade secrets;</li>
                <li>Data subject to legal restrictions on disclosure;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.6.4 Exercise Procedure:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Submission of Portability Request:</strong> Via User dashboard "Export My Data" feature; Email request to dpo@preiposip.com with subject "Data Portability Request"; Specification of data categories and preferred format;</li>
                <li>(b) <strong>Available Formats:</strong> JSON (JavaScript Object Notation); CSV (Comma-Separated Values); XML (Extensible Markup Language); PDF (Portable Document Format) with attachments;</li>
                <li>(c) <strong>Delivery Methods:</strong> Secure download link with password protection; Encrypted email attachment; API access for direct transmission to another service provider;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.6.5 Platform Response Timeline:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Standard Requests:</strong> Within 30 days;</li>
                <li>(b) <strong>Large Data Sets:</strong> Within 60 days with interim notification;</li>
                <li>(c) <strong>Direct Transmission:</strong> Within 45 days for coordination with receiving service provider;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.6.6 Technical Interoperability:</h4>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Uses standardized data formats to facilitate portability;</li>
                <li>Provides API documentation for receiving service providers;</li>
                <li>Offers technical support for data import to other platforms;</li>
                <li>Does not charge fees for first portability request;</li>
            </ul>
        </div>
      </div>
    </section>
  );
}
'use client';

import React from 'react';
import { Award, Briefcase, FileCheck, Shield } from 'lucide-react';

export default function RefundPolicyPart9() {
  return (
    <section id="part-9" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 9</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">REPRESENTATIONS, WARRANTIES, COVENANTS AND COMPLIANCE</h2>
      </div>

      {/* ARTICLE 32 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Award className="text-indigo-600" size={20} /> ARTICLE 32: REPRESENTATIONS AND WARRANTIES
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">32.1 Platform's Representations and Warranties</h4>
          <p className="text-slate-600 mb-2">The Platform represents and warrants to each Stakeholder that:</p>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Corporate Status and Authority</p>
              <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(i) <strong>Legal Existence:</strong> Platform is a [company/limited liability partnership] duly incorporated, validly existing, and in good standing under the laws of India; Certificate of Incorporation number: [●]; Registered office: [●]; Corporate Identity Number (CIN)/Limited Liability Partnership Identification Number (LLPIN): [●];</li>
                <li>(ii) <strong>Corporate Power:</strong> Platform has full corporate power and authority to: Carry on its business as presently conducted; Own, lease, and operate its properties and assets; Enter into and perform its obligations under this Policy; Provide refund services as contemplated herein;</li>
                <li>(iii) <strong>Authorization:</strong> Execution, delivery, and performance of this Policy have been duly authorized by all necessary corporate action; Board resolutions or equivalent authorizations obtained; No breach of constitutional documents (Memorandum, Articles of Association, LLP Agreement);</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Regulatory Compliance and Registrations</p>
              <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(i) <strong>Licenses and Registrations:</strong> Platform holds all necessary licenses, registrations, approvals, and permits required under Applicable Law to conduct its business, including but not limited to: SEBI registration (if applicable): [Registration Type and Number]; GST Registration: [GSTIN]; PAN: [●]; TAN: [●]; PMLA registration/compliance status; Any other material regulatory registrations;</li>
                <li>(ii) <strong>Validity:</strong> All registrations are valid, subsisting, and not subject to suspension, revocation, or cancellation proceedings;</li>
                <li>(iii) <strong>Compliance:</strong> Platform is in material compliance with terms and conditions of all registrations and licenses;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) No Conflicts or Violations</p>
              <p className="text-slate-600 mb-1">Execution and performance of this Policy does not and shall not:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Violate any provision of Platform's constitutional documents;</li>
                <li>(ii) Violate, breach, or constitute default under: Any law, statute, regulation, or rule applicable to Platform; Any judicial or administrative order, judgment, or decree binding on Platform; Any material agreement, contract, or instrument to which Platform is party;</li>
                <li>(iii) Require consent, approval, authorization, or notice to any third party or governmental authority (other than consents already obtained);</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Financial and Operational Capacity</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Financial Soundness:</strong> Platform has adequate financial resources and operational capacity to perform its obligations under this Policy;</li>
                <li>(ii) <strong>Banking Arrangements:</strong> Platform maintains valid banking relationships with scheduled commercial banks necessary for processing refunds;</li>
                <li>(iii) <strong>Insurance:</strong> Platform maintains appropriate professional indemnity, cyber liability, and other insurance coverage for its business operations (details available on request);</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(e) No Pending Material Litigation</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) There is no pending or, to Platform's knowledge, threatened: Litigation, arbitration, or administrative proceeding; Investigation or inquiry by regulatory authorities; Criminal prosecution or enforcement action; That would materially adversely affect Platform's ability to perform obligations under this Policy;</li>
                <li>(ii) Platform is not subject to any unsatisfied judgment, order, or decree that would materially impair its operations;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(f) Data Security and Technology</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Platform employs industry-standard security measures to protect Stakeholder data and prevent unauthorized access, use, or disclosure;</li>
                <li>(ii) Platform's technology infrastructure is reasonably designed to: Process transactions securely; Maintain data integrity and availability; Prevent, detect, and respond to cyber threats;</li>
                <li>(iii) Platform maintains business continuity and disaster recovery arrangements;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(g) Anti-Money Laundering and Sanctions Compliance</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Platform has implemented and maintains appropriate AML/CFT (Anti-Money Laundering/Combating Financing of Terrorism) policies and procedures in compliance with PMLA;</li>
                <li>(ii) Platform conducts business in compliance with applicable economic sanctions laws;</li>
                <li>(iii) Platform is not subject to any sanctions imposed by Government of India, United Nations, or other applicable sanctions regimes;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(h) Intellectual Property</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Platform owns or has valid licenses to use all intellectual property necessary for operation of its business and Platform website/applications;</li>
                <li>(ii) To Platform's knowledge, operation of Platform does not infringe any third-party intellectual property rights;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(i) No Misrepresentation</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) All information provided by Platform to Stakeholders in this Policy and related communications is true, accurate, and not misleading;</li>
                <li>(ii) No material fact has been concealed or suppressed that would affect Stakeholder's decision to transact with Platform;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">32.2 Stakeholder's Representations and Warranties</h4>
          <p className="text-slate-600 mb-2">Each Stakeholder, by accepting this Policy and transacting with Platform, represents and warrants that:</p>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Legal Capacity and Authority</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Individual Stakeholders:</strong> Stakeholder is of legal age (18 years or above) and sound mind; Has legal capacity to enter into binding contracts; Not subject to any legal disability or incapacity;</li>
                <li>(ii) <strong>Corporate/Institutional Stakeholders:</strong> Duly incorporated/constituted and validly existing under applicable law; Has corporate/organizational power to enter into and perform this Policy; All necessary authorizations (board resolutions, partner consents, trustee approvals) obtained; Signatory has authority to bind the entity;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Identity and KYC Representations</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>True and Accurate Information:</strong> All information provided during KYC process and in application forms is: True, complete, and accurate in all material respects; Not misleading or deceptive; Current and up-to-date;</li>
                <li>(ii) <strong>Identity Documents:</strong> All identity documents (PAN, Aadhaar, passport, etc.) are genuine and belong to Stakeholder; Not forged, tampered, or obtained fraudulently; Not belonging to any other person;</li>
                <li>(iii) <strong>Beneficial Ownership:</strong> Information regarding beneficial ownership is accurate and complete; No undisclosed beneficial owners or controllers; Ultimate beneficiary of transactions disclosed truthfully;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Source of Funds</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) All funds used for payments to Platform are: From legitimate sources; Not proceeds of crime, money laundering, or illegal activities; Not connected to terrorist financing or sanctioned entities; Belong to Stakeholder or person on whose behalf Stakeholder is transacting (with proper authorization);</li>
                <li>(ii) Stakeholder has adequate financial resources to fulfill payment obligations;</li>
                <li>(iii) Payments made from accounts owned or controlled by Stakeholder or from properly authorized sources;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Regulatory and Legal Compliance</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Eligible Investor:</strong> Stakeholder meets eligibility criteria for investing in Pre-IPO securities under Applicable Law; Not barred or prohibited from making such investments; Compliance with foreign investment norms (if applicable);</li>
                <li>(ii) <strong>No Legal Restrictions:</strong> Stakeholder is not: Subject to bankruptcy, insolvency, or winding-up proceedings; Under legal disability or incompetence; Prohibited by court order or regulatory direction from transacting; Listed in any sanctions list or designated entity list;</li>
                <li>(iii) <strong>Tax Compliance:</strong> Stakeholder is compliant with income tax laws and GST (if applicable); Has valid PAN and has filed tax returns as required; Not engaged in tax evasion or avoidance schemes;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(e) No Money Laundering or Terrorist Financing</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Stakeholder is not engaged in or connected with: Money laundering or placement/layering of illicit funds; Terrorist financing or support to terrorist organizations; Organized crime or criminal enterprises; Sanctions violations or prohibited transactions;</li>
                <li>(ii) Stakeholder will not use Platform's services for any unlawful purpose;</li>
                <li>(iii) Transactions are for legitimate economic purposes with real commercial substance;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(f) No Fraudulent Intent</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Stakeholder is not engaging with Platform with intent to: Defraud Platform or third parties; Manipulate markets or prices; Engage in insider trading or market abuse; Launder money or conceal criminal proceeds;</li>
                <li>(ii) All representations made to Platform are in good faith;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(g) Acknowledgment and Understanding</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Policy Review:</strong> Stakeholder has read and understood this entire Policy; Has had adequate opportunity to seek legal, financial, and tax advice; Has received satisfactory explanations for any queries;</li>
                <li>(ii) <strong>Risk Acknowledgment:</strong> Understands risks associated with Pre-IPO investments as disclosed in Article 19; Has adequate risk appetite and financial capacity to bear losses; Not relying on any representations not contained in written documentation;</li>
                <li>(iii) <strong>Voluntary Transaction:</strong> Entering into transactions voluntarily without coercion, undue influence, or misrepresentation; Making independent investment decisions;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(h) Bank Account Ownership</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Bank accounts provided for refund credit are: Owned and controlled by Stakeholder; Operated legitimately and not subject to fraud or suspicion; Not used for money laundering or illegal activities;</li>
                <li>(ii) If bank account belongs to third party: Proper written authorization obtained from account holder; Legitimate reason for using third-party account (e.g., corporate treasury, escrow arrangement); Full disclosure made to Platform;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(i) No Undisclosed Conflicts</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Stakeholder has disclosed all material conflicts of interest;</li>
                <li>(ii) Not acting on behalf of undisclosed principals or beneficial owners;</li>
                <li>(iii) Not subject to any agreement or arrangement that conflicts with obligations under this Policy;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(j) Compliance with Terms</p>
              <p className="text-slate-600">Stakeholder agrees to comply with: All terms and conditions of this Policy; Platform's Terms of Use and other policies; All reasonable instructions and requirements of Platform; All Applicable Laws in connection with transactions;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">32.3 Survival and Repetition of Representations</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Survival:</strong> All representations and warranties survive execution of transactions and shall continue during entire period of relationship with Platform; Survive refund processing and completion; Form basis for indemnification obligations;</li>
            <li>(b) <strong>Deemed Repetition:</strong> Representations deemed repeated on each date that: Stakeholder submits transaction or refund request; Makes payment to Platform; Receives refund or other payment from Platform;</li>
            <li>(c) <strong>Duty to Update:</strong> If any representation becomes untrue or inaccurate: Stakeholder must immediately notify Platform in writing; Provide updated accurate information; Cooperate with Platform's verification and due diligence;</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">32.4 Consequences of Breach of Representations</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Material Breach</p>
              <p className="text-slate-600 mb-1">Breach of any representation or warranty constitutes material breach of this Policy entitling Platform to:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Immediate Actions:</strong> Suspend or terminate Stakeholder's User Account; Reject or cancel pending transactions; Refuse refund requests or withhold refund payments; Freeze amounts pending investigation;</li>
                <li>(ii) <strong>Legal Remedies:</strong> Claim damages for losses suffered due to misrepresentation; Rescind transactions induced by misrepresentation; Report to law enforcement and regulatory authorities; Initiate civil and criminal proceedings;</li>
                <li>(iii) <strong>Financial Consequences:</strong> Forfeiture of payments made; Recovery of refunds already paid (if obtained through misrepresentation); Interest and penalty on amounts recoverable; Legal costs and collection expenses;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Innocent Misrepresentation</p>
              <p className="text-slate-600">Even if misrepresentation was innocent or negligent (not fraudulent): Platform entitled to rescind transaction; Stakeholder must indemnify Platform for third-party claims; Platform may report to regulators if material violation involved;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Fraudulent Misrepresentation</p>
              <p className="text-slate-600">If misrepresentation was fraudulent: All civil and criminal remedies available to Platform; Stakeholder liable for punitive damages; Permanent blacklisting from Platform; Reporting to SEBI, FIU-IND, and law enforcement; Cooperation with prosecuting authorities;</p>
            </div>
          </div>
        </div>
      </div>

      {/* ARTICLE 33 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Briefcase className="text-indigo-600" size={20} /> ARTICLE 33: COVENANTS AND CONTINUING OBLIGATIONS
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">33.1 Stakeholder's Affirmative Covenants</h4>
          <p className="text-slate-600 mb-2">Throughout the period of relationship with Platform, Stakeholder covenants and agrees to:</p>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Maintenance of Accurate Information</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>KYC Updates:</strong> Update KYC information within 30 days of any change in: Name, address, contact details; Nationality, residential status; Occupation, income bracket; Corporate structure (for entities); Submit fresh KYC documents when existing documents expire; Respond promptly to Platform's requests for information updates;</li>
                <li>(ii) <strong>Bank Account Changes:</strong> Notify Platform immediately of: Bank account closure; Change in bank account details; Freezing or attachment of accounts; Provide alternate valid account details for refund processing;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Compliance with Laws</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Comply with all Applicable Laws in connection with: Use of Platform's services; Transactions in Pre-IPO securities; Receipt and use of refunds; Tax obligations and reporting;</li>
                <li>(ii) Obtain all necessary approvals, licenses, and consents required under law;</li>
                <li>(iii) Not use Platform's services for any unlawful purpose;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Cooperation with Platform</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Information Requests:</strong> Respond to Platform's reasonable requests for information within timelines specified; Provide additional documentation for verification or due diligence; Cooperate with Platform's compliance reviews and audits;</li>
                <li>(ii) <strong>Investigation Cooperation:</strong> Cooperate with Platform's investigations of suspicious activity; Provide explanations and documentation as requested; Not obstruct or interfere with compliance processes;</li>
                <li>(iii) <strong>Regulatory Cooperation:</strong> Cooperate with regulatory inquiries or audits; Provide information directly to regulators if required; Authorize Platform to disclose information to regulators;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Prompt Communication</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Notify Platform immediately of: Any circumstance that would affect ability to complete transactions; Litigation, investigation, or regulatory proceeding involving Stakeholder; Change in financial circumstances materially affecting creditworthiness; Bankruptcy, insolvency, or financial distress; Any event that would render representations untrue;</li>
                <li>(ii) Respond to Platform's communications within reasonable timeframes;</li>
                <li>(iii) Maintain active and accessible email and phone contact;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(e) Security and Confidentiality</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Account Security:</strong> Maintain confidentiality of login credentials, passwords, OTPs; Not share User Account access with unauthorized persons; Immediately report suspected unauthorized access or security breach; Use strong passwords and enable two-factor authentication;</li>
                <li>(ii) <strong>Device Security:</strong> Maintain security of devices used to access Platform; Use updated antivirus and security software; Not access Platform from public or unsecured networks for sensitive transactions;</li>
                <li>(iii) <strong>Confidential Information:</strong> Maintain confidentiality of proprietary information received from Platform; Not disclose transaction details or commercially sensitive information; Protect against unauthorized use or disclosure;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(f) Prohibited Activities</p>
              <p className="text-slate-600 mb-1">Stakeholder covenants NOT to:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Fraudulent Conduct:</strong> Engage in fraud, misrepresentation, or deception; Provide false information or forged documents; Impersonate another person or entity;</li>
                <li>(ii) <strong>Market Manipulation:</strong> Engage in insider trading or market manipulation; Collude with other investors to manipulate prices; Create artificial demand or supply;</li>
                <li>(iii) <strong>System Abuse:</strong> Attempt to hack, disrupt, or damage Platform's systems; Use automated scripts, bots, or crawlers without authorization; Overload or stress-test systems; Reverse engineer or decompile Platform's software;</li>
                <li>(iv) <strong>Regulatory Violations:</strong> Violate securities laws, PMLA, FEMA, or other financial regulations; Facilitate prohibited transactions or sanctions violations; Assist others in violating laws;</li>
                <li>(v) <strong>Abuse of Process:</strong> Submit vexatious or frivolous refund requests; Abuse grievance mechanisms or dispute resolution processes; Engage in forum shopping or multiplicity of proceedings;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">33.2 Platform's Affirmative Covenants</h4>
          <p className="text-slate-600 mb-2">Platform covenants to:</p>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Regulatory Compliance</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Maintain all required licenses, registrations, and regulatory approvals;</li>
                <li>(ii) Comply with SEBI regulations, PMLA requirements, and other Applicable Laws;</li>
                <li>(iii) File required regulatory returns and reports timely;</li>
                <li>(iv) Cooperate with regulatory inspections, audits, and examinations;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Service Standards</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Process refund requests in accordance with timelines and procedures specified in this Policy;</li>
                <li>(ii) Maintain adequate staff, systems, and resources for refund processing;</li>
                <li>(iii) Implement reasonable security measures to protect Stakeholder data;</li>
                <li>(iv) Maintain business continuity and disaster recovery arrangements;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Transparency and Disclosure</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Provide clear, accurate, and timely information to Stakeholders;</li>
                <li>(ii) Disclose material changes affecting services or refund terms;</li>
                <li>(iii) Maintain updated contact information and grievance mechanisms;</li>
                <li>(iv) Publish audited financial statements (to extent required by law);</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Fair Treatment</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Treat all Stakeholders fairly and without discrimination;</li>
                <li>(ii) Process refunds objectively based on Policy terms and facts;</li>
                <li>(iii) Not abuse discretionary powers arbitrarily or capriciously;</li>
                <li>(iv) Provide reasons for rejections or adverse decisions;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(e) Data Protection</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Protect Stakeholder data in accordance with Privacy Policy and data protection laws;</li>
                <li>(ii) Not use or disclose data except as permitted by law and Privacy Policy;</li>
                <li>(iii) Implement reasonable safeguards against data breaches;</li>
                <li>(iv) Notify Stakeholders of material data breaches as required by law;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(f) Financial Prudence</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Maintain adequate capital and reserves for business operations;</li>
                <li>(ii) Ensure funds received from Stakeholders held securely and not misappropriated;</li>
                <li>(iii) Maintain insurance coverage for professional liability and cyber risks;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">33.3 Negative Covenants (Platform)</h4>
          <p className="text-slate-600 mb-2">Platform covenants NOT to:</p>
          <ul className="list-none pl-4 text-slate-600 space-y-1">
            <li>(a) Without limiting generality of Platform's obligations:</li>
            <li>(i) Engage in fraud, misrepresentation, or market manipulation;</li>
            <li>(ii) Misappropriate or misuse Stakeholder funds;</li>
            <li>(iii) Operate in violation of regulatory directions or license conditions;</li>
            <li>(iv) Discriminate against Stakeholders based on race, religion, caste, gender, or other protected characteristics;</li>
            <li>(v) Abuse market position or engage in unfair trade practices;</li>
          </ul>
        </div>
      </div>

      {/* ARTICLE 34 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <FileCheck className="text-indigo-600" size={20} /> ARTICLE 34: COMPLIANCE OBLIGATIONS AND REGULATORY REPORTING
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">34.1 Platform's Compliance Framework</h4>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Compliance Function</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Chief Compliance Officer (CCO):</strong> Designated senior officer responsible for compliance; Reports to Board of Directors or Audit Committee; Independence from business operations; Adequate resources and authority;</li>
                <li>(ii) <strong>Compliance Department:</strong> Dedicated team for compliance monitoring; Staffed with qualified compliance professionals; Ongoing training and development;</li>
                <li>(iii) <strong>Compliance Manual:</strong> Comprehensive policies and procedures for regulatory compliance; Covering SEBI regulations, PMLA, FEMA, tax laws, etc.; Regularly reviewed and updated; Available for regulatory inspection;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) SEBI Compliance</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Registration Maintenance:</strong> Valid SEBI registration maintained (if applicable); Renewal applications filed timely; Compliance with registration conditions;</li>
                <li>(ii) <strong>Periodic Reporting:</strong> Quarterly financial results submitted to SEBI; Annual audited financial statements; Activity reports and statistical data; Investor grievance reports;</li>
                <li>(iii) <strong>Code of Conduct:</strong> Implementation of SEBI's Code of Conduct for intermediaries; Training of personnel on ethical standards; Internal controls and surveillance mechanisms;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Prevention of Money Laundering Act (PMLA) Compliance</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>KYC/AML Policy:</strong> Board-approved KYC and AML policy; Risk-based approach to customer due diligence; Enhanced due diligence for high-risk customers;</li>
                <li>(ii) <strong>Customer Identification Program (CIP):</strong> Verification of identity using reliable documents; Collection of PAN, Aadhaar, address proof; Verification of corporate entities (CIN, incorporation certificate); Beneficial ownership identification;</li>
                <li>(iii) <strong>Record Keeping:</strong> Maintenance of KYC records for 5 years after cessation of relationship; Transaction records for 5 years after completion of transaction; Records in retrievable format for regulatory inspection;</li>
                <li>(iv) <strong>Suspicious Transaction Reporting:</strong> Monitoring of transactions for suspicious patterns; Filing of Suspicious Transaction Reports (STRs) with FIU-IND within 7 days of determination; Cash Transaction Reports (CTRs) for cash transactions exceeding ₹10 lakhs (if applicable); Confidentiality of STR filing maintained;</li>
                <li>(v) <strong>Designated Director/Officer:</strong> Appointed as per PMLA requirements; Responsible for AML compliance and FIU-IND reporting; Interface with law enforcement;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) FEMA Compliance</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>For transactions involving non-residents or foreign currency:</strong> Compliance with FDI/FPI regulations; Pricing guidelines and valuation norms; Reporting to RBI as required (Form FC-GPR, etc.); KYC and source of funds verification for NRIs;</li>
                <li>(ii) <strong>Authorized Dealer bank relationship for foreign exchange transactions;</strong></li>
                <li>(iii) <strong>Documentation and record-keeping as per FEMA requirements;</strong></li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(e) Tax Compliance</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>GST Compliance:</strong> Valid GST registration; Timely filing of GST returns (GSTR-1, GSTR-3B); Proper invoicing and tax collection; Input tax credit reconciliation;</li>
                <li>(ii) <strong>Income Tax Compliance:</strong> TDS deduction and deposit as per Income Tax Act; Issuance of TDS certificates (Form 16A); Annual income tax return filing; Transfer pricing documentation (if applicable);</li>
                <li>(iii) <strong>Securities Transaction Tax (STT):</strong> Collection and deposit (if applicable to transactions);</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(f) Data Protection and Privacy Compliance</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Compliance with Information Technology Act, 2000 and rules thereunder:</strong> Reasonable security practices and procedures; Data breach notification protocols; Consent management for data processing;</li>
                <li>(ii) <strong>Implementation of Privacy Policy;</strong></li>
                <li>(iii) <strong>Data localization compliance (if mandated);</strong></li>
                <li>(iv) <strong>Cross-border data transfer compliance;</strong></li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">34.2 Stakeholder's Compliance Obligations</h4>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) KYC Compliance</p>
              <p className="text-slate-600 mb-1">Stakeholder must:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Complete KYC process as per Platform's requirements;</li>
                <li>(ii) Provide true, accurate, and complete information;</li>
                <li>(iii) Submit clear, legible copies of valid identity documents;</li>
                <li>(iv) Update KYC records when information changes or documents expire;</li>
                <li>(v) Consent to KYC verification through third-party agencies;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Tax Compliance</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>PAN Requirement:</strong> Provide valid PAN for all financial transactions; PAN must belong to Stakeholder (not borrowed or fraudulent); Update Platform if PAN changes;</li>
                <li>(ii) <strong>Tax Declarations:</strong> Declare residential status (resident/non-resident) accurately; Provide Form 15G/15H if claiming exemption from TDS (subject to eligibility); Submit tax residency certificate if claiming treaty benefits;</li>
                <li>(iii) <strong>Tax Reporting:</strong> Report refund receipts in income tax returns as appropriate; Report interest income on delayed refunds; Maintain documentation for tax audit purposes;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Investment Eligibility Compliance</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Ensure compliance with investor eligibility criteria under SEBI regulations;</li>
                <li>(ii) If foreign investor: Comply with FPI or FEMA regulations;</li>
                <li>(iii) Not invest in violation of sectoral caps or restricted sectors;</li>
                <li>(iv) Obtain necessary approvals (FIPB, RBI, etc.) if required;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Securities Law Compliance</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Comply with insider trading regulations:</strong> Not trade on unpublished price-sensitive information (UPSI); Disclose insider status if applicable; Comply with trading window restrictions;</li>
                <li>(ii) <strong>Comply with takeover regulations:</strong> Disclosure of shareholding if crosses thresholds; Open offer obligations if applicable;</li>
                <li>(iii) Comply with disclosure and reporting norms;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(e) Anti-Money Laundering Cooperation</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Provide information regarding source of funds when requested;</li>
                <li>(ii) Not use Platform for money laundering or terrorist financing;</li>
                <li>(iii) Report suspicious activity to Platform if aware;</li>
                <li>(iv) Cooperate with AML investigations;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">34.3 Regulatory Reporting and Disclosure</h4>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Platform's Reporting to Regulators</p>
              <p className="text-slate-600 mb-1">Platform shall report to SEBI, RBI, FIU-IND, and other regulators:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Periodic Reports:</strong> Quarterly activity reports; Annual audited accounts; Investor grievance data; AML/CFT compliance reports;</li>
                <li>(ii) <strong>Incident Reports:</strong> Material incidents (system breaches, fraud, operational failures); Regulatory violations or breaches discovered; Significant litigations or arbitrations;</li>
                <li>(iii) <strong>Suspicious Transaction Reports (STRs):</strong> Filed with FIU-IND as per PMLA; Confidential; not disclosed to Stakeholders;</li>
                <li>(iv) <strong>Ad-hoc Information:</strong> In response to regulatory queries or inspections; As directed by regulators from time to time;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Stakeholder Information Disclosure</p>
              <p className="text-slate-600 mb-1">Platform may disclose Stakeholder information to regulators:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Routine Regulatory Reporting:</strong> Aggregated statistical data; Client classification data; Transaction data as required;</li>
                <li>(ii) <strong>Specific Regulatory Inquiries:</strong> In response to SEBI/RBI/FIU investigation; Pursuant to statutory information requests; Court orders or search warrants;</li>
                <li>(iii) <strong>STR-Related Disclosure:</strong> Information included in STRs filed with FIU-IND; Stakeholder not informed when STR filed (to prevent tipping off);</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Stakeholder Consent and Waiver</p>
              <p className="text-slate-600 mb-1">By accepting this Policy, Stakeholder:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Consents to Platform disclosing information to regulators as required by law;</li>
                <li>(ii) Waives confidentiality to extent necessary for regulatory compliance;</li>
                <li>(iii) Authorizes Platform to cooperate with regulatory investigations;</li>
                <li>(iv) Will not hold Platform liable for compliant regulatory disclosures;</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
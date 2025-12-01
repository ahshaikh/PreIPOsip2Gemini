'use client';

import React from 'react';
import { ShieldCheck, Lock, Activity } from 'lucide-react';

export default function RefundPolicyPart10() {
  return (
    <section id="part-10" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 10</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">DATA PROTECTION, AML/CFT, AND SPECIFIC SEBI COMPLIANCE</h2>
      </div>

      {/* ARTICLE 35 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <ShieldCheck className="text-indigo-600" size={20} /> ARTICLE 35: DATA PROTECTION AND PRIVACY
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">35.1 Incorporation of Privacy Policy</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-1">
            <li>(a) This Article supplements Platform's Privacy Policy (available at www.preiposip.com/privacy);</li>
            <li>(b) In case of conflict, Privacy Policy prevails for data protection matters;</li>
            <li>(c) Stakeholders must review and accept Privacy Policy separately;</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">35.2 Data Collection and Processing</h4>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Personal Data Collected</p>
              <p className="text-slate-600 mb-1">Platform collects following categories of personal data:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Identity Information:</strong> Name, date of birth, gender; PAN, Aadhaar, passport, voter ID; Photographs, signatures;</li>
                <li>(ii) <strong>Contact Information:</strong> Address (residential, correspondence); Email address, phone number;</li>
                <li>(iii) <strong>Financial Information:</strong> Bank account details (account number, IFSC, bank name); Income details, net worth; Demat account details; Transaction history, payment records;</li>
                <li>(iv) <strong>Demographic and Profile Data:</strong> Occupation, education; Investment experience and risk profile; Preferences and interests;</li>
                <li>(v) <strong>Technical Data:</strong> IP address, device identifiers; Browser type, operating system; Login timestamps, session data; Cookies and tracking data;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Purpose of Data Processing</p>
              <p className="text-slate-600 mb-1">Personal data processed for following purposes:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Service Delivery:</strong> Creating and managing User Accounts; Processing transactions and refund requests; Communication regarding services; Customer support and grievance redressal;</li>
                <li>(ii) <strong>Compliance and Legal:</strong> KYC/AML verification and compliance; Regulatory reporting; Tax compliance (TDS, GST); Responding to legal process;</li>
                <li>(iii) <strong>Risk Management:</strong> Fraud detection and prevention; Credit assessment; Monitoring for suspicious activity; Security and audit purposes;</li>
                <li>(iv) <strong>Business Operations:</strong> Analytics and business intelligence; Service improvement; Market research;</li>
                <li>(v) <strong>Marketing (with consent):</strong> Promotional communications; Product recommendations;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Legal Basis for Processing</p>
              <p className="text-slate-600 mb-1">Processing based on:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Consent:</strong> Explicit consent obtained for specific processing activities;</li>
                <li>(ii) <strong>Contractual Necessity:</strong> Processing necessary to perform contract with Stakeholder;</li>
                <li>(iii) <strong>Legal Obligation:</strong> Processing required for compliance with PMLA, SEBI regulations, tax laws;</li>
                <li>(iv) <strong>Legitimate Interests:</strong> Fraud prevention, security, business analytics (subject to balancing test);</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">35.3 Data Security Measures</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Technical Safeguards</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Encryption:</strong> Data encrypted in transit (SSL/TLS); Sensitive data encrypted at rest; Encryption key management;</li>
                <li>(ii) <strong>Access Controls:</strong> Role-based access control (RBAC); Multi-factor authentication for systems; Logging and monitoring of data access;</li>
                <li>(iii) <strong>Network Security:</strong> Firewalls and intrusion detection/prevention systems; Regular security patching and updates; Vulnerability assessments and penetration testing;</li>
                <li>(iv) <strong>Backup and Recovery:</strong> Regular data backups; Disaster recovery and business continuity plans; Tested restoration procedures;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Organizational Safeguards</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Policies and Procedures:</strong> Information security policy; Data classification and handling procedures; Incident response plan;</li>
                <li>(ii) <strong>Personnel:</strong> Background verification of employees; Confidentiality and non-disclosure agreements; Security awareness training; Disciplinary measures for violations;</li>
                <li>(iii) <strong>Third-Party Management:</strong> Due diligence on service providers; Contractual data protection obligations; Periodic audits of vendors;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Incident Response</p>
              <p className="text-slate-600 mb-1">In event of data breach:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Internal Response:</strong> Immediate containment and investigation; Root cause analysis; Remediation measures;</li>
                <li>(ii) <strong>Notification:</strong> Affected Stakeholders notified within 72 hours (or as required by law); Regulatory authorities notified as required; Law enforcement informed if criminal activity;</li>
                <li>(iii) <strong>Support:</strong> Assistance to affected Stakeholders; Credit monitoring or identity theft protection (if warranted);</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">35.4 Data Retention</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Retention Periods</p>
              <p className="text-slate-600 mb-1">Personal data retained as follows:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Active Relationship:</strong> Throughout relationship period;</li>
                <li>(ii) <strong>Post-Relationship:</strong> KYC records: 5 years after cessation (PMLA requirement); Transaction records: 5 years after completion (PMLA); Financial records: 8 years (Companies Act); Tax records: 6 years (Income Tax Act); Legal/litigation records: Until matter concluded + 3 years;</li>
                <li>(iii) <strong>Minimum Retention:</strong> Longer of above periods or as required by Applicable Law;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Secure Deletion</p>
              <p className="text-slate-600">After retention period: Data securely deleted or anonymized; Deletion logs maintained; Backups purged on schedule;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">35.5 Stakeholder Rights</h4>
          <p className="text-slate-600 mb-2">Stakeholders have following rights (subject to legal limitations):</p>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Access:</strong> Right to access personal data held by Platform;</li>
            <li>(b) <strong>Rectification:</strong> Right to correct inaccurate or incomplete data;</li>
            <li>(c) <strong>Erasure:</strong> Right to deletion (subject to legal retention requirements);</li>
            <li>(d) <strong>Restriction:</strong> Right to restrict processing in certain circumstances;</li>
            <li>(e) <strong>Portability:</strong> Right to receive data in structured, machine-readable format (to extent technically feasible);</li>
            <li>(f) <strong>Objection:</strong> Right to object to processing based on legitimate interests;</li>
            <li>(g) <strong>Withdrawal of Consent:</strong> Right to withdraw consent (prospective effect only);</li>
          </ul>
          <p className="text-slate-600 mt-2"><strong>Exercise of Rights:</strong> Written request to privacy@preiposip.com; Response within 30 days; Identity verification required; No fee for reasonable requests;</p>
        </div>
      </div>

      {/* ARTICLE 36 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Lock className="text-indigo-600" size={20} /> ARTICLE 36: ANTI-MONEY LAUNDERING (AML) AND COUNTER-TERRORIST FINANCING (CFT)
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">36.1 AML/CFT Policy Framework</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Commitment</p>
              <p className="text-slate-600 mb-1">Platform is committed to:</p>
              <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Preventing use of services for money laundering or terrorist financing;</li>
                <li>Compliance with PMLA, 2002 and Rules thereunder;</li>
                <li>Implementation of risk-based approach to AML/CFT;</li>
                <li>Cooperation with law enforcement and FIU-IND;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Regulatory Foundation</p>
              <p className="text-slate-600 mb-1">AML/CFT framework based on:</p>
              <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Prevention of Money Laundering Act, 2002;</li>
                <li>PML Rules, 2005 (as amended);</li>
                <li>SEBI circulars on AML obligations;</li>
                <li>RBI Master Directions on KYC;</li>
                <li>FATF (Financial Action Task Force) recommendations;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">36.2 Customer Due Diligence (CDD)</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Standard CDD</p>
              <p className="text-slate-600 mb-1">For all Stakeholders:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Identity Verification:</strong> Official valid documents (PAN mandatory); Aadhaar-based e-KYC or paper-based KYC; Biometric authentication (if applicable);</li>
                <li>(ii) <strong>Address Verification:</strong> Utility bills, bank statements, Aadhaar; Not older than 3 months;</li>
                <li>(iii) <strong>Risk Profiling:</strong> Assessment of customer risk category (low/medium/high); Based on occupation, income, transaction pattern, geography;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Enhanced Due Diligence (EDD)</p>
              <p className="text-slate-600 mb-1">For high-risk Stakeholders:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>High-Risk Categories:</strong> Non-resident customers; Politically Exposed Persons (PEPs); High net-worth individuals (HNI); Cash-intensive businesses; Customers from high-risk jurisdictions (FATF list);</li>
                <li>(ii) <strong>Additional Measures:</strong> Source of wealth and funds documentation; Purpose and intended nature of business relationship; Senior management approval for establishing relationship; Enhanced ongoing monitoring; More frequent CDD refresh;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Simplified Due Diligence (SDD)</p>
              <p className="text-slate-600 mb-1">For low-risk Stakeholders (with regulatory approval): Government departments; Public sector undertakings; Regulated entities (banks, insurance companies); Simplified documentation may be permitted;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Ongoing Due Diligence</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Transaction Monitoring:</strong> Continuous monitoring of transactions; Identification of unusual or suspicious patterns; Comparison with customer profile;</li>
                <li>(ii) <strong>Periodic Review:</strong> CDD refresh at defined intervals: Low risk: Every 10 years; Medium risk: Every 8 years; High risk: Every 2 years; More frequent if customer circumstances change;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">36.3 Suspicious Transaction Monitoring</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Red Flags and Indicators</p>
              <p className="text-slate-600 mb-1">Platform monitors for suspicious patterns including:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Transaction Patterns:</strong> Transactions inconsistent with customer profile; Rapid movement of funds without economic rationale; Structuring (breaking large transactions into smaller ones to avoid reporting); Round-tripping or circular transactions; Use of multiple accounts without apparent reason;</li>
                <li>(ii) <strong>Customer Behavior:</strong> Reluctance to provide information or documents; Provision of false or suspicious documents; Unusual concern about compliance procedures; Frequent change of contact details or accounts;</li>
                <li>(iii) <strong>Transaction Characteristics:</strong> Transactions involving high-risk jurisdictions; Payments from/to unrelated third parties; Transactions just below reporting thresholds; Complex or unusual transaction structures;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Investigation Process</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Alert generated by monitoring system or reported by staff;</li>
                <li>(ii) Preliminary review by compliance team;</li>
                <li>(iii) Investigation and information gathering;</li>
                <li>(iv) Assessment whether transaction suspicious;</li>
                <li>(v) Escalation to CCO or Designated Director for determination;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Suspicious Transaction Reporting (STR)</p>
              <p className="text-slate-600 mb-1">If transaction determined suspicious:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>STR Filing:</strong> Filed with FIU-IND within 7 days of determination; Through FIU-IND online portal; Contains all prescribed information;</li>
                <li>(ii) <strong>Confidentiality:</strong> STR filing strictly confidential; Stakeholder not informed (to prevent "tipping off"); Internal knowledge restricted to need-to-know basis;</li>
                <li>(iii) <strong>Tipping Off Prohibition:</strong> Disclosing STR filing is criminal offense under PMLA; Staff trained on confidentiality obligations; Violation may result in imprisonment;</li>
                <li>(iv) <strong>Continued Relationship:</strong> STR filing alone does not require termination of relationship; Platform may continue servicing customer unless directed otherwise by authorities; Enhanced monitoring continues;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">36.4 Sanctions Screening</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Screening Against Lists</p>
              <p className="text-slate-600 mb-1">All Stakeholders screened against:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>United Nations Security Council Sanctions Lists:</strong> Al-Qaida Sanctions List; ISIL (Da'esh) & Al-Qaida Sanctions List; Other UNSC sanctions lists;</li>
                <li>(ii) <strong>OFAC Lists (U.S. Treasury):</strong> Specially Designated Nationals (SDN) List; Sectoral Sanctions Identifications List;</li>
                <li>(iii) <strong>EU Sanctions Lists:</strong> Consolidated list of persons, groups, and entities subject to EU financial sanctions;</li>
                <li>(iv) <strong>Domestic Lists:</strong> Lists published by Ministry of Home Affairs; SEBI debarred entities list; RBI wilful defaulters list;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Screening Process</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Onboarding:</strong> Name screening during KYC process;</li>
                <li>(ii) <strong>Ongoing:</strong> Periodic rescreening (quarterly or more frequent);</li>
                <li>(iii) <strong>Transaction-Level:</strong> Screening of transaction counterparties;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Match Handling</p>
              <p className="text-slate-600 mb-1">If potential match identified:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Manual review and investigation;</li>
                <li>(ii) False positive elimination (name variations, common names);</li>
                <li>(iii) If true match: Relationship declined or terminated immediately; Transaction blocked; Assets frozen if required by sanctions regime; Reported to competent authorities;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">36.5 Record Keeping</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Records Maintained</p>
              <p className="text-slate-600 mb-1">For AML/CFT purposes:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) All KYC documents and CDD records;</li>
                <li>(ii) Transaction records including: Nature and date of transaction; Amount and currency; Payment mode and instruments; Beneficiary details;</li>
                <li>(iii) Correspondence with Stakeholders;</li>
                <li>(iv) Risk assessment and monitoring records;</li>
                <li>(v) STR internal documentation (analysis, determination);</li>
                <li>(vi) Account opening and closing records;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Retention</p>
              <p className="text-slate-600">Minimum 5 years after cessation of relationship; Transaction records: 5 years after completion; Longer if ongoing investigation or litigation; Records in retrievable format;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Access</p>
              <p className="text-slate-600">Records made available to: Internal and external auditors; Regulatory authorities (SEBI, RBI, FIU-IND); Law enforcement agencies; Courts (pursuant to orders);</p>
            </div>
          </div>
        </div>
      </div>

      {/* ARTICLE 37 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Activity className="text-indigo-600" size={20} /> ARTICLE 37: SPECIFIC SEBI COMPLIANCE PROVISIONS
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">37.1 SEBI Registration and Authorization</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Platform's SEBI Status</p>
              <p className="text-slate-600 mb-1">The Platform operates in compliance with SEBI regulations and holds the following registrations (as applicable):</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Registration Category:</strong> [Investment Adviser/Research Analyst/Stock Broker/Other - As Applicable]</li>
                <li>(ii) <strong>Registration Number:</strong> [SEBI Registration Number]</li>
                <li>(iii) <strong>Date of Registration:</strong> [Date]</li>
                <li>(iv) <strong>Validity:</strong> [Valid until / Perpetual subject to compliance]</li>
                <li>(v) <strong>Registered Office:</strong> [Address as per SEBI Records]</li>
                <li>(vi) <strong>Principal Officer:</strong> [Name and Designation]</li>
                <li>(vii) <strong>Compliance Officer:</strong> [Name and Contact Details]</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Scope of Authorization</p>
              <p className="text-slate-600 mb-1">Platform is authorized to:</p>
              <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>[Specific activities as per SEBI registration];</li>
                <li>Facilitate transactions in unlisted securities subject to applicable regulations;</li>
                <li>Provide investment advisory services (if registered);</li>
                <li>Conduct research and publish research reports (if registered);</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Limitations</p>
              <p className="text-slate-600 mb-1">Platform is NOT authorized to:</p>
              <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Guarantee returns or assure profits on investments;</li>
                <li>Accept deposits from public;</li>
                <li>Operate collective investment schemes without SEBI approval;</li>
                <li>Engage in activities outside scope of registration;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">37.2 Investor Protection Obligations</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Fair Disclosure</p>
              <p className="text-slate-600 mb-1">Platform commits to:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Material Information Disclosure:</strong> All material information affecting investment decisions disclosed; No suppression or concealment of adverse information; Timely disclosure of conflicts of interest;</li>
                <li>(ii) <strong>Risk Disclosure:</strong> Comprehensive risk disclosures provided (as per Article 19); Specific risks highlighted for each transaction type; Risk Disclosure Document provided and acknowledged by Stakeholder;</li>
                <li>(iii) <strong>Fee Transparency:</strong> All fees, charges, commissions disclosed upfront; No hidden charges or undisclosed expenses; Fee structure as per Schedule I to this Policy;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Suitability and Appropriateness</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Know Your Client (KYC):</strong> Detailed client profiling including financial situation, investment objectives, risk appetite; Investment suitability assessment; Documentation of suitability determination;</li>
                <li>(ii) <strong>Product Appropriateness:</strong> Pre-IPO investments suitable only for sophisticated investors; Warning provided to investors lacking adequate risk tolerance; Right to decline unsuitable transactions;</li>
                <li>(iii) <strong>Categorization of Clients:</strong> Individual investors; High Net Worth Individuals (HNI); Institutional investors; Different service levels and protections based on category;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Execution Quality</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Platform commits to: Best execution of transactions; Fair pricing and valuation; Transparent allocation in oversubscribed opportunities; No preferential treatment except as disclosed and justified;</li>
                <li>(ii) Conflicts of interest managed through: Chinese walls and information barriers; Disclosure of principal vs. agency capacity; Independent valuation for proprietary transactions;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Client Asset Protection</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Segregation of Funds:</strong> Client funds held in separate bank accounts (escrow/trust accounts); Not commingled with Platform's proprietary funds; Reconciliation and reporting;</li>
                <li>(ii) <strong>Demat Account Security:</strong> Client securities held in client's own demat account; Platform does not hold client securities except as custodian with proper authorization;</li>
                <li>(iii) <strong>Insurance:</strong> Professional indemnity insurance maintained; Cyber liability insurance; Coverage details available on request;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">37.3 SEBI Code of Conduct Compliance</h4>
          <p className="text-slate-600 mb-2">Platform and its personnel comply with SEBI Code of Conduct including:</p>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Integrity and Fairness</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Act with integrity in all dealings;</li>
                <li>(ii) Avoid fraudulent, manipulative, or deceptive practices;</li>
                <li>(iii) Not engage in unfair trade practices;</li>
                <li>(iv) Maintain high standards of professional conduct;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Due Diligence</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Conduct adequate due diligence on: Securities and issuers recommended; Counterparties in transactions; Service providers and intermediaries;</li>
                <li>(ii) Maintain due diligence records;</li>
                <li>(iii) Update due diligence periodically;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Independence and Conflicts</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Independence:</strong> Exercise independent professional judgment; Not unduly influenced by issuers, distributors, or other parties;</li>
                <li>(ii) <strong>Conflict Disclosure:</strong> Disclose all material conflicts of interest; Manage conflicts through policies and procedures; Avoid conflicts where management impracticable;</li>
                <li>(iii) <strong>Personal Trading:</strong> Employees subject to personal trading restrictions; Pre-clearance and reporting requirements; Prohibition on front-running or misuse of client information;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Confidentiality</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Maintain confidentiality of client information;</li>
                <li>(ii) Not misuse unpublished price-sensitive information (UPSI);</li>
                <li>(iii) Comply with insider trading regulations;</li>
                <li>(iv) Exceptions: Disclosure required by law or with client consent;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(e) Client Priority</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Client interests paramount;</li>
                <li>(ii) Client orders executed before proprietary orders;</li>
                <li>(iii) No misuse of client assets or positions;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">37.4 Regulatory Reporting and Inspection</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Periodic Reports to SEBI</p>
              <p className="text-slate-600 mb-1">Platform files with SEBI:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Quarterly Reports:</strong> Financial results (unaudited); Activity reports (number of clients, transactions, revenues); Investor grievance data; Compliance status;</li>
                <li>(ii) <strong>Annual Reports:</strong> Audited financial statements; Annual activity report; Compliance certificate from auditor; Certificate from Compliance Officer;</li>
                <li>(iii) <strong>Event-Based Reporting:</strong> Material changes (management, control, business model); Regulatory violations or breaches; Litigation or arbitration exceeding threshold; Cyber incidents or data breaches;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Inspection and Audit</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>SEBI Inspection:</strong> Platform subject to periodic SEBI inspection; Full cooperation with inspectors; Production of books, records, and documents; Rectification of deficiencies identified;</li>
                <li>(ii) <strong>Internal Audit:</strong> Annual internal audit covering compliance, operations, financials; Audit committee review; Management action on audit findings;</li>
                <li>(iii) <strong>External Audit:</strong> Statutory audit by SEBI-empaneled chartered accountants; Audit of specific areas (AML, cybersecurity) as required;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Document Preservation</p>
              <p className="text-slate-600">All records maintained as per SEBI requirements: Minimum retention periods (typically 5-8 years); Secure storage and retrieval systems; Protection against tampering or destruction;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">37.5 Investor Grievance Redressal</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) SEBI SCORES Registration</p>
              <p className="text-slate-600 mb-1">Platform registered on SEBI SCORES (Complaints Redress System):</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Investors can file complaints online;</li>
                <li>(ii) Platform must respond within 30 days;</li>
                <li>(iii) SEBI monitors resolution;</li>
                <li>(iv) Unresolved complaints escalated to SEBI;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Grievance Redressal Mechanism</p>
              <p className="text-slate-600">As detailed in Article 20, aligned with SEBI requirements: Designated Grievance Officer; Defined timelines for resolution; Escalation matrix; Periodic reporting to Board;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Performance Metrics</p>
              <p className="text-slate-600">Platform monitors and reports: Total grievances received; Grievances resolved within timeline; Average resolution time; Nature and category of grievances; Systemic issues and corrective actions;</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
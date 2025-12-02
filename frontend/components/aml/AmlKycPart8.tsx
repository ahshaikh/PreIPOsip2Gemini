// components/aml/AmlKycPart8.tsx
'use client';

import React from "react";

export default function AmlKycPart8() {
  return (
    <div>

      {/* 14.2.2 Transaction Records */}
      <section id="transaction-records" className="section">
        <div className="section-header">
          <span className="section-number">14.2.2</span>
          <h2 className="section-title">Transaction Records</h2>
        </div>

        {/* CATEGORY F */}
        <h3>Category F: Individual Transaction Records</h3>

        <p>For each transaction, maintain:</p>

        <h4>(a) Core Transaction Details:</h4>
        <ul className="legal-list">
          <li>Transaction date and timestamp</li>
          <li>Transaction reference number (unique identifier)</li>
          <li>Transaction type (purchase/sale of securities, transfer, pledge, etc.)</li>
          <li>Securities details:</li>
          <li className="ml-6">Name of issuer company</li>
          <li className="ml-6">Type of security (equity shares, preference shares, convertibles, etc.)</li>
          <li className="ml-6">ISIN (if available)</li>
          <li className="ml-6">Number of securities</li>
          <li className="ml-6">Face value and issue price</li>
          <li className="ml-6">Transaction price per unit</li>
          <li className="ml-6">Total transaction value</li>
        </ul>

        <h4>(b) Party Details:</h4>
        <ul className="legal-list">
          <li>Buyer details (name, Customer ID, PAN, account details)</li>
          <li>Seller details (name, Customer ID/entity details, PAN, account details)</li>
          <li>Beneficial ownership declarations (if transaction involves change in beneficial ownership)</li>
          <li>Third-party involvement (if any) with details and authorization</li>
        </ul>

        <h4>(c) Payment and Settlement Records:</h4>
        <ul className="legal-list">
          <li>Payment mode (bank transfer, cheque, demand draft, etc.)</li>
          <li>Remitter bank account details</li>
          <li>Beneficiary bank account details</li>
          <li>Payment reference numbers</li>
          <li>Payment date and settlement date</li>
          <li>Currency, exchange rate</li>
          <li>Escrow arrangements (if applicable)</li>
          <li>Payment receipts and confirmations</li>
        </ul>

        <h4>(d) Supporting Documentation:</h4>
        <ul className="legal-list">
          <li>Share transfer forms (if physical transfer)</li>
          <li>Share certificates (if physical)</li>
          <li>Delivery instructions</li>
          <li>Board resolutions approving transfer (if applicable)</li>
          <li>No-objection certificates from company (if required)</li>
          <li>Compliance certificates</li>
          <li>Valuation reports</li>
          <li>Tax documentation</li>
        </ul>

        <h4>(e) Transaction Approvals and Processing:</h4>
        <ul className="legal-list">
          <li>Internal approval records</li>
          <li>Risk assessment for the transaction</li>
          <li>Compliance clearances</li>
          <li>Processing logs and system audit trails</li>
          <li>Confirmation communications to Customer</li>
          <li>Contract notes or confirmations</li>
        </ul>

        {/* CATEGORY G */}
        <h3>Category G: Aggregated Transaction Records</h3>

        <ul className="legal-list">
          <li>Monthly transaction summaries</li>
          <li>Annual transaction reports</li>
          <li>Portfolio statements</li>
          <li>Holdings reports</li>
          <li>Capital gains/loss statements</li>
        </ul>

        {/* CATEGORY H */}
        <h3>Category H: Alert and Investigation Records</h3>

        <ul className="legal-list">
          <li>All alerts generated</li>
          <li>Alert details (date, type, triggering rule, Customer, transaction)</li>
          <li>Investigation notes</li>
          <li>Customer explanations</li>
          <li>Additional documents obtained</li>
          <li>Disposition (cleared, escalated, STR filed)</li>
          <li>Supervisor approvals</li>
          <li>Timeline documentation</li>
        </ul>

        {/* CATEGORY I */}
        <h3>Category I: Suspicious Transaction Reports (STRs)</h3>

        <ul className="legal-list">
          <li>Copy of each STR filed</li>
          <li>FIU-IND acknowledgment receipts</li>
          <li>STR reference numbers</li>
          <li>Investigation files</li>
          <li>Queries and responses</li>
          <li>Law enforcement correspondence</li>
          <li>Post-STR monitoring reports</li>
          <li>Account actions (restriction, closure, etc.)</li>
        </ul>

        {/* CATEGORY J */}
        <h3>Category J: Other Regulatory Reports</h3>

        <ul className="legal-list">
          <li>Cash Transaction Reports (CTRs)</li>
          <li>Cross-Border Wire Transfer Reports (CBWTs)</li>
          <li>Counterfeit Currency Reports (CCRs)</li>
          <li>Any other reports filed</li>
          <li>Acknowledgments and correspondence</li>
        </ul>

        {/* CATEGORY K */}
        <h3>Category K: Risk Assessment Records</h3>

        <ul className="legal-list">
          <li>Customer risk assessments</li>
          <li>Risk scoring calculations</li>
          <li>Risk categorization changes</li>
          <li>Product risk assessments</li>
          <li>Channel risk assessments</li>
          <li>Geographic risk assessments</li>
          <li>Enterprise-wide risk assessment reports</li>
        </ul>

        {/* CATEGORY L */}
        <h3>Category L: Training and Awareness Records</h3>

        <ul className="legal-list">
          <li>Training materials</li>
          <li>Attendance records</li>
          <li>Assessment/test results</li>
          <li>Certification records</li>
          <li>Training calendars</li>
          <li>Specialized compliance training records</li>
        </ul>

        {/* CATEGORY M */}
        <h3>Category M: Audit and Review Records</h3>

        <ul className="legal-list">
          <li>Internal audit reports</li>
          <li>External audit reports</li>
          <li>Management responses</li>
          <li>Corrective action plans</li>
          <li>Implementation status</li>
          <li>Board/committee minutes relating to AML/KYC</li>
        </ul>

        {/* CATEGORY N */}
        <h3>Category N: Policy and Procedure Documents</h3>

        <ul className="legal-list">
          <li>AML/KYC Policy (all versions)</li>
          <li>Standard Operating Procedures</li>
          <li>Policy amendments and approvals</li>
          <li>Regulatory circulars and guidance</li>
          <li>Legal opinions</li>
          <li>Compliance manuals</li>
        </ul>

        {/* CATEGORY O */}
        <h3>Category O: System and Technology Records</h3>

        <ul className="legal-list">
          <li>System configuration documents</li>
          <li>Rule parameters and thresholds</li>
          <li>System change logs</li>
          <li>User access logs</li>
          <li>System audit trails</li>
          <li>Data backup and recovery logs</li>
          <li>Cybersecurity incident reports</li>
        </ul>

      </section>

      {/* 14.3 Record Format and Medium */}
      <section id="record-format" className="section">
        <div className="section-header">
          <span className="section-number">14.3</span>
          <h2 className="section-title">Record Format and Medium</h2>
        </div>

        <h3>14.3.1 Physical vs. Digital Records</h3>

        <h4>(a) Physical Records:</h4>
        <ul className="legal-list">
          <li>Secure, fireproof storage</li>
          <li>Organized systematically</li>
          <li>Access-controlled</li>
          <li>Protected from deterioration</li>
          <li>Regular condition checks</li>
        </ul>

        <h4>(b) Digital Records:</h4>
        <ul className="legal-list">
          <li>Secure encrypted storage</li>
          <li>Multiple backups</li>
          <li>Access controls and authentication</li>
          <li>Audit trails</li>
          <li>Protection against cyber threats</li>
          <li>Compliance with IT Act, 2000</li>
        </ul>

        <h4>(c) Hybrid Approach:</h4>
        <ul className="legal-list">
          <li>Scan physical documents</li>
          <li>Store digital copies in non-editable PDF</li>
          <li>Physical originals may be destroyed only after verification</li>
          <li>Critical docs kept in both formats</li>
        </ul>

        <h3>14.3.2 Admissibility in Evidence</h3>

        <ul className="legal-list">
          <li>Compliance with IT Act (Section 65B)</li>
          <li>Proper authentication</li>
          <li>Digital signatures</li>
          <li>Audit trails ensuring integrity</li>
          <li>Ability to produce certified copies</li>
        </ul>

      </section>

      {/* 14.4 Record Indexing and Retrieval */}
      <section id="record-indexing" className="section">
        <div className="section-header">
          <span className="section-number">14.4</span>
          <h2 className="section-title">Record Indexing and Retrieval</h2>
        </div>

        <h3>14.4.1 Indexing System</h3>

        <h4>(a) Customer-Centric Indexing:</h4>
        <ul className="legal-list">
          <li>Unique Customer ID</li>
          <li>Searchable by name, PAN, account number, Customer ID</li>
        </ul>

        <h4>(b) Transaction-Centric Indexing:</h4>
        <ul className="legal-list">
          <li>Unique transaction reference number</li>
          <li>Searchable by multiple attributes</li>
        </ul>

        <h4>(c) Date-Based Indexing:</h4>
        <ul className="legal-list">
          <li>Chronological organization</li>
        </ul>

        <h4>(d) Category-Based Indexing:</h4>
        <ul className="legal-list">
          <li>Records categorized by type</li>
        </ul>

        <h3>14.4.2 Retrieval Timelines</h3>

        <ul className="legal-list">
          <li>(a) Current Customer Records: Within 1 hour</li>
          <li>(b) Archived Customer Records: Within 24 hours</li>
          <li>(c) Regulatory Requests: Within 24–48 hours</li>
          <li>(d) Internal Audit: Same day–3 days</li>
        </ul>

        <h3>14.4.3 Search and Retrieval Functionality</h3>

        <ul className="legal-list">
          <li>Full-text search</li>
          <li>Boolean operators</li>
          <li>Wildcard searches</li>
          <li>Date range searches</li>
          <li>Export</li>
          <li>Batch retrieval</li>
        </ul>

      </section>

      {/* 14.5 Record Destruction Policy */}
      <section id="record-destruction" className="section">
        <div className="section-header">
          <span className="section-number">14.5</span>
          <h2 className="section-title">Record Destruction Policy</h2>
        </div>

        <h3>14.5.1 Retention Period Compliance</h3>
        <ul className="legal-list">
          <li>Minimum 10 years from cessation of relationship</li>
          <li>Longer if litigation/investigation ongoing</li>
          <li>Longer if regulatory directive</li>
        </ul>

        <h3>14.5.2 Destruction Authorization</h3>
        <ul className="legal-list">
          <li>Compliance identifies eligible records</li>
          <li>Verify no legal hold</li>
          <li>Principal Officer authorizes</li>
          <li>DD approval for sensitive records</li>
        </ul>

        <h3>14.5.3 Destruction Method</h3>

        <h4>(a) Physical Records:</h4>
        <ul className="legal-list">
          <li>Cross-cut shredding (P-4 minimum)</li>
          <li>Incineration for highly sensitive docs</li>
          <li>Witnessed and logged</li>
          <li>Vendor certificate</li>
        </ul>

        <h4>(b) Digital Records:</h4>
        <ul className="legal-list">
          <li>Secure deletion (multi-pass overwrites)</li>
          <li>All backup copies destroyed</li>
          <li>Destruction logged</li>
          <li>Certificate of destruction</li>
        </ul>

        <h3>14.5.4 Destruction Log</h3>

        <ul className="legal-list">
          <li>Date</li>
          <li>Description</li>
          <li>Retention period</li>
          <li>Authorization</li>
          <li>Method</li>
          <li>Witness/vendor certificate</li>
        </ul>

      </section>

      {/* 15. Information Security and Confidentiality */}
      <section id="information-security" className="section">
        <div className="section-header">
          <span className="section-number">15</span>
          <h2 className="section-title">Information Security and Confidentiality</h2>
        </div>

        <h3>15.1 Confidentiality Obligations</h3>

        <h4>15.1.1 Legal Basis</h4>

        <ul className="legal-list">
          <li>Common Law Duty</li>
          <li>Contractual obligations</li>
          <li>IT Act, 2000</li>
          <li>DPDP Act, 2023</li>
          <li>PMLA Section 14</li>
        </ul>

        <h4>15.1.2 Scope of Confidential Information</h4>

        <ul className="legal-list">
          <li>Customer identity info</li>
          <li>Financial information</li>
          <li>Portfolio details</li>
          <li>Transaction details</li>
          <li>KYC documents</li>
          <li>Risk assessments</li>
          <li>Communications</li>
          <li>STR-related info</li>
          <li>Any info obtained during relationship</li>
        </ul>

        <h4>15.1.3 Duty of Personnel</h4>

        <h5>(a) During Employment/Engagement:</h5>
        <ul className="legal-list">
          <li>Need-to-know basis</li>
          <li>No unauthorized disclosure</li>
          <li>Protect info</li>
        </ul>

        <h5>(b) Post-Employment/Engagement:</h5>
        <ul className="legal-list">
          <li>Confidentiality continues</li>
          <li>No disclosure or use</li>
          <li>Return/delete all documents</li>
        </ul>

        <h5>(c) Breach Consequences:</h5>
        <ul className="legal-list">
          <li>Disciplinary action</li>
          <li>Civil liability</li>
          <li>Criminal liability</li>
          <li>Professional misconduct charges</li>
        </ul>

        <h4>15.1.4 NDAs</h4>

        <ul className="legal-list">
          <li>All personnel sign NDAs</li>
          <li>NDAs survive termination</li>
          <li>Specify remedies for breach</li>
        </ul>

      </section>

      {/* 15.2 Information Security Framework */}
      <section id="information-security-framework" className="section">
        <div className="section-header">
          <span className="section-number">15.2</span>
          <h2 className="section-title">Information Security Framework</h2>
        </div>

        <h3>15.2.1 ISO 27001 Alignment</h3>

        <ul className="legal-list">
          <li>Information Security Policies</li>
          <li>Organization of Information Security</li>
          <li>Human Resource Security</li>
          <li>Asset Management</li>
          <li>Access Control</li>
          <li>Cryptography</li>
          <li>Physical Security</li>
          <li>Operations Security</li>
          <li>Communications Security</li>
          <li>Development & Maintenance</li>
          <li>Supplier Relationships</li>
          <li>Incident Management</li>
          <li>Business Continuity</li>
          <li>Compliance</li>
        </ul>

        <h3>15.2.2 Information Classification</h3>

        <h4>(a) Public</h4>
        <ul className="legal-list">
          <li>Information in public domain</li>
        </ul>

        <h4>(b) Internal</h4>
        <ul className="legal-list">
          <li>Operational documents</li>
          <li>General records</li>
        </ul>

        <h4>(c) Confidential</h4>
        <ul className="legal-list">
          <li>Customer info</li>
          <li>Business plans</li>
          <li>Contract terms</li>
        </ul>

        <h4>(d) Highly Confidential / Restricted</h4>
        <ul className="legal-list">
          <li>STRs</li>
          <li>Investigation files</li>
          <li>Legal proceedings</li>
          <li>Security credentials</li>
        </ul>

      </section>

      {/* 15.2.3 Access Control Framework */}
      <section id="access-control" className="section">
        <div className="section-header">
          <span className="section-number">15.2.3</span>
          <h2 className="section-title">Access Control Framework</h2>
        </div>

        <h4>Principle of Least Privilege</h4>

        <h4>(a) Role-Based Access Control (RBAC):</h4>
        <ul className="legal-list">
          <li>Access by job role</li>
          <li>Standard profiles</li>
          <li>Approval required for deviations</li>
        </ul>

        <h4>(b) User Authentication:</h4>
        <ul className="legal-list">
          <li>Unique user IDs</li>
          <li>Strong passwords</li>
          <li>MFA for sensitive systems</li>
          <li>Biometric authentication</li>
          <li>Session timeouts</li>
        </ul>

        <h4>(c) Access Authorization:</h4>
        <ul className="legal-list">
          <li>Formal access requests</li>
          <li>Manager approval</li>
          <li>PO/DD approval for sensitive data</li>
          <li>Time-bound access</li>
        </ul>

        <h4>(d) Access Review:</h4>
        <ul className="legal-list">
          <li>Quarterly reviews</li>
          <li>Annual audits</li>
          <li>Immediate removal for terminated staff</li>
        </ul>

        <h4>(e) Privileged Access Management:</h4>
        <ul className="legal-list">
          <li>Enhanced controls</li>
          <li>All privileged actions logged</li>
          <li>Periodic reviews</li>
          <li>Segregation of duties</li>
        </ul>

      </section>

      {/* 15.2.4 Physical Security */}
      <section id="physical-security" className="section">
        <div className="section-header">
          <span className="section-number">15.2.4</span>
          <h2 className="section-title">Physical Security</h2>
        </div>

        <h4>(a) Premises Security:</h4>
        <ul className="legal-list">
          <li>Controlled access</li>
          <li>Visitor management</li>
          <li>CCTV</li>
          <li>After-hours restrictions</li>
        </ul>

        <h4>(b) Secure Storage:</h4>
        <ul className="legal-list">
          <li>Locked cabinets</li>
          <li>Restricted file room</li>
          <li>Access log</li>
          <li>Fire & environmental controls</li>
        </ul>

        <h4>(c) Device Security:</h4>
        <ul className="legal-list">
          <li>Encrypted devices</li>
          <li>Password/PIN locking</li>
          <li>Remote wipe</li>
          <li>No personal device storage</li>
        </ul>

        <h4>(d) Clean Desk Policy:</h4>
        <ul className="legal-list">
          <li>No unattended documents</li>
          <li>Screens locked</li>
          <li>Documents secured daily</li>
          <li>Shredding for disposal</li>
        </ul>

      </section>

      {/* 15.2.5 Network and System Security */}
      <section id="network-security" className="section">
        <div className="section-header">
          <span className="section-number">15.2.5</span>
          <h2 className="section-title">Network and System Security</h2>
        </div>

        <h4>(a) Perimeter Security:</h4>
        <ul className="legal-list">
          <li>Firewalls</li>
          <li>IDS/IPS</li>
          <li>DDoS protection</li>
          <li>Regular VAPT</li>
        </ul>

        <h4>(b) Endpoint Security:</h4>
        <ul className="legal-list">
          <li>Antivirus</li>
          <li>Patch management</li>
          <li>Device hardening</li>
        </ul>

        <h4>(c) Data Encryption:</h4>
        <ul className="legal-list">
          <li>TLS for data in transit</li>
          <li>Database/disk encryption</li>
          <li>Key management</li>
          <li>End-to-end encryption for sensitive data</li>
        </ul>

        <h4>(d) Secure Development:</h4>
        <ul className="legal-list">
          <li>Secure coding practices</li>
          <li>Code reviews</li>
          <li>Security testing</li>
          <li>Third-party audits</li>
        </ul>

        <h4>(e) Database Security:</h4>
        <ul className="legal-list">
          <li>Encrypted databases</li>
          <li>Restricted access</li>
          <li>Database activity monitoring</li>
          <li>Regular encrypted backups</li>
        </ul>

      </section>

    </div>
  );
}

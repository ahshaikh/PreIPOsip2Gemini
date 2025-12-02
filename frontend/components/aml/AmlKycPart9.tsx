// components/aml/AmlKycPart9.tsx
'use client';

import React from "react";

export default function AmlKycPart9() {
  return (
    <div>

      {/* 15.2.6 Data Backup and Business Continuity */}
      <section id="data-backup-bcp" className="section">
        <div className="section-header">
          <span className="section-number">15.2.6</span>
          <h2 className="section-title">Data Backup and Business Continuity</h2>
        </div>

        <h3>(a) Backup Policy:</h3>
        <ul className="legal-list">
          <li>Daily incremental backups of all critical data</li>
          <li>Weekly full backups</li>
          <li>Backups encrypted and stored securely</li>
          <li>Off-site backup storage (geographically separate location)</li>
          <li>Cloud backup with reputable provider (data residency in India)</li>
        </ul>

        <h3>(b) Backup Testing:</h3>
        <ul className="legal-list">
          <li>Quarterly backup restoration tests</li>
          <li>Verification of backup integrity</li>
          <li>Documentation of restore procedures</li>
          <li>Recovery Time Objective (RTO) and Recovery Point Objective (RPO) defined and tested</li>
        </ul>

        <h3>(c) Business Continuity Plan (BCP):</h3>
        <ul className="legal-list">
          <li>Documented plan for continuing operations during disruptions</li>
          <li>Disaster Recovery (DR) procedures for IT systems</li>
          <li>Alternative work arrangements (backup office, work-from-home)</li>
          <li>Communication plans for stakeholders</li>
          <li>Annual BCP testing and drills</li>
        </ul>
      </section>

      {/* 15.3 Cybersecurity Measures */}
      <section id="cybersecurity-measures" className="section">
        <div className="section-header">
          <span className="section-number">15.3</span>
          <h2 className="section-title">Cybersecurity Measures</h2>
        </div>

        {/* 15.3.1 Cyber Threat Protection */}
        <h3>15.3.1 Cyber Threat Protection</h3>

        <h4>(a) Malware Protection:</h4>
        <ul className="legal-list">
          <li>Enterprise-grade antivirus/anti-malware</li>
          <li>Email filtering and scanning</li>
          <li>Web filtering blocking malicious sites</li>
          <li>Sandboxing of suspicious files</li>
        </ul>

        <h4>(b) Phishing Prevention:</h4>
        <ul className="legal-list">
          <li>Email authentication (SPF, DKIM, DMARC)</li>
          <li>Phishing simulation training for employees</li>
          <li>Reporting mechanism for suspicious emails</li>
          <li>Takedown procedures for phishing sites impersonating Platform</li>
        </ul>

        <h4>(c) Ransomware Protection:</h4>
        <ul className="legal-list">
          <li>Network segmentation to limit spread</li>
          <li>Regular backups enabling recovery without ransom</li>
          <li>Application whitelisting</li>
          <li>Privilege restriction preventing unauthorized encryption</li>
        </ul>

        <h4>(d) Insider Threat Mitigation:</h4>
        <ul className="legal-list">
          <li>User activity monitoring</li>
          <li>Data Loss Prevention (DLP) systems</li>
          <li>Monitoring for anomalous access patterns</li>
          <li>Background checks for sensitive roles</li>
          <li>Exit procedures ensuring access removal and device return</li>
        </ul>

        {/* 15.3.2 Security Monitoring */}
        <h3>15.3.2 Security Monitoring and Incident Response</h3>

        <h4>(a) Security Operations Center (SOC) or Equivalent:</h4>
        <ul className="legal-list">
          <li>24×7 monitoring of security events (in-house or outsourced)</li>
          <li>Security Information and Event Management (SIEM) system</li>
          <li>Log aggregation and correlation</li>
          <li>Alert generation and triage</li>
        </ul>

        <h4>(b) Incident Response Plan:</h4>
        <ul className="legal-list">
          <li>Defined process for detecting, reporting, and responding to security incidents</li>
          <li>Incident response team with defined roles</li>
          <li>Escalation procedures</li>
          <li>Communication protocols (internal and external)</li>
          <li>Forensic investigation procedures</li>
          <li>Post-incident review and lessons learned</li>
        </ul>

        <h4>(c) Incident Categories:</h4>
        <ul className="legal-list">
          <li>Cyber Attacks: Hacking attempts, DDoS, malware infections</li>
          <li>Data Breaches: Unauthorized access to Customer data</li>
          <li>Insider Incidents: Misuse of access by Personnel</li>
          <li>Physical Security: Theft, loss of devices/documents</li>
          <li>Third-Party: Vendor/service-provider incidents</li>
        </ul>

        <h4>(d) Incident Reporting:</h4>
        <ul className="legal-list">
          <li>Internal reporting to Principal Officer & Designated Director immediately</li>
          <li>Reporting to CERT-In under IT Act requirements</li>
          <li>Reporting to Data Protection Authority under DPDP Act (if personal data breach)</li>
          <li>Reporting to affected Customers if required</li>
          <li>Reporting to SEBI/regulators if operations impacted</li>
        </ul>
      </section>

      {/* 15.4 Third-Party and Vendor Security */}
      <section id="vendor-security" className="section">
        <div className="section-header">
          <span className="section-number">15.4</span>
          <h2 className="section-title">Third-Party and Vendor Security</h2>
        </div>

        <h3>15.4.1 Vendor Due Diligence</h3>

        <p>Before engaging third-party service providers with access to Customer data:</p>
        <ul className="legal-list">
          <li>(a) Security assessment questionnaire</li>
          <li>(b) Review of vendor security policies</li>
          <li>(c) Verification of ISO27001/SOC2 certifications</li>
          <li>(d) Reference checks</li>
          <li>(e) Financial stability assessment</li>
          <li>(f) Legal and regulatory compliance verification</li>
        </ul>

        <h3>15.4.2 Contractual Protections</h3>
        <p>Vendor contracts include:</p>

        <ul className="legal-list">
          <li>(a) Confidentiality and NDA obligations</li>
          <li>(b) Information security requirements</li>
          <li>(c) Mandatory incident notification</li>
          <li>(d) Right to audit vendor's controls</li>
          <li>(e) Data residency in India</li>
          <li>(f) Data return/destruction upon termination</li>
          <li>(g) Sub-contracting restrictions</li>
          <li>(h) Liability & indemnity for breaches</li>
          <li>(i) Compliance with PMLA, IT Act, DPDP Act</li>
        </ul>

        <h3>15.4.3 Ongoing Vendor Management</h3>
        <ul className="legal-list">
          <li>(a) Annual vendor security reviews</li>
          <li>(b) Audit of high-risk vendors</li>
          <li>(c) Monitoring vendor security incidents</li>
          <li>(d) Performance reviews including security metrics</li>
          <li>(e) Periodic reassessment of vendor risk</li>
        </ul>
      </section>

      {/* 15.5 Security Awareness and Training */}
      <section id="security-awareness" className="section">
        <div className="section-header">
          <span className="section-number">15.5</span>
          <h2 className="section-title">Security Awareness and Training</h2>
        </div>

        <h3>15.5.1 General Security Training</h3>
        <ul className="legal-list">
          <li>(a) Security awareness training during onboarding</li>
          <li>(b) Annual refresher training covering:</li>
          <ul className="legal-list ml-6">
            <li>Information security policies</li>
            <li>Confidentiality obligations</li>
            <li>Password security</li>
            <li>Social engineering & phishing</li>
            <li>Physical security (clean desk, visitor management)</li>
            <li>Incident reporting</li>
            <li>Acceptable use of IT resources</li>
          </ul>
          <li>(c) Training acknowledgment and assessment</li>
          <li>(d) Training records maintained for audit</li>
        </ul>

        <h3>15.5.2 Role-Specific Training</h3>
        <ul className="legal-list">
          <li>(a) IT Team: security, secure development, system hardening</li>
          <li>(b) Compliance Team: AML/KYC confidentiality, STR anti-tipping-off</li>
          <li>(c) Customer-Facing Staff: social engineering awareness</li>
          <li>(d) Management: governance, risk, incident response</li>
        </ul>

        <h3>15.5.3 Security Culture</h3>
        <ul className="legal-list">
          <li>(a) Leadership commitment</li>
          <li>(b) Security champions in departments</li>
          <li>(c) Regular security communications</li>
          <li>(d) Incident reporting encouraged without blame</li>
          <li>(e) Recognition for good practices</li>
          <li>(f) Consequences for violations</li>
        </ul>
      </section>

      {/* 16. DATA PROTECTION AND PRIVACY COMPLIANCE */}
      <section id="data-protection" className="section">
        <div className="section-header">
          <span className="section-number">16</span>
          <h2 className="section-title">Data Protection and Privacy Compliance</h2>
        </div>

        {/* 16.1 Legal Framework */}
        <h3>16.1 Legal Framework for Data Protection</h3>

        <h4>16.1.1 Digital Personal Data Protection Act, 2023 (DPDP Act)</h4>
        <p>The Platform complies with the DPDP Act, which regulates:</p>

        <ul className="legal-list">
          <li>(a) Processing of digital personal data</li>
          <li>(b) Rights of data principals (individuals)</li>
          <li>(c) Obligations of data fiduciaries</li>
          <li>(d) Cross-border data transfers</li>
          <li>(e) Data breaches and penalties</li>
        </ul>

        <h4>16.1.2 Information Technology Act, 2000 and Rules</h4>
        <ul className="legal-list">
          <li>(a) Section 43A obligations</li>
          <li>(b) SPDI Rules, 2011 compliance</li>
          <li>(c) Protection of Sensitive Personal Data or Information</li>
        </ul>

        <h4>16.1.3 Sensitive Personal Data</h4>
        <p>SPDI includes:</p>
        <ul className="legal-list">
          <li>Passwords</li>
          <li>Financial information</li>
          <li>Biometric data</li>
          <li>Medical records</li>
          <li>Sexual orientation</li>
        </ul>

        <p>All Customer data is treated as SPDI.</p>

        {/* 16.2 Data Processing Principles */}
        <h3>16.2 Data Processing Principles</h3>

        <h4>16.2.1 Lawfulness, Fairness, and Transparency</h4>
        <p>Personal data processed lawfully, fairly, transparently.</p>

        <h4>16.2.2 Purpose Limitation</h4>
        <ul className="legal-list">
          <li>Used only for explicit, legitimate purposes</li>
          <li>No incompatible secondary use</li>
        </ul>

        <h4>16.2.3 Data Minimization</h4>
        <ul className="legal-list">
          <li>No excess data collected</li>
        </ul>

        <h4>16.2.4 Accuracy</h4>
        <ul className="legal-list">
          <li>Verified at collection</li>
          <li>Corrected when inaccurate</li>
        </ul>

        <h4>16.2.5 Storage Limitation</h4>
        <ul className="legal-list">
          <li>Retention limited to 10 years (or legally required)</li>
        </ul>

        <h4>16.2.6 Integrity and Confidentiality</h4>

        <h4>16.2.7 Accountability</h4>
        <ul className="legal-list">
          <li>Policies + monitoring + assessments</li>
        </ul>

        {/* 16.3 Customer Rights */}
        <h3>16.3 Customer Rights (Data Principal Rights)</h3>

        <h4>16.3.1 Right to Information</h4>
        <p>Customers have right to know:</p>
        <ul className="legal-list">
          <li>Data collected</li>
          <li>Purpose</li>
          <li>Sharing</li>
          <li>Retention</li>
        </ul>

        <h4>16.3.2 Right to Access</h4>
        <ul className="legal-list">
          <li>Copy of data</li>
          <li>Response within 30 days</li>
        </ul>

        <h4>16.3.3 Right to Correction</h4>
        <ul className="legal-list">
          <li>Correction within 14 days</li>
        </ul>

        <h4>16.3.4 Right to Erasure</h4>
        <ul className="legal-list">
          <li>Permitted when legally allowed (not during PMLA retention)</li>
        </ul>

        <h4>16.3.5 Right to Data Portability</h4>
        <p>Data may be provided in structured format.</p>

        <h4>16.3.6 Right to Withdraw Consent</h4>
        <p>Applies only where processing is consent-based.</p>

        <h4>16.3.7 Right to Lodge Complaint</h4>
        <ul className="legal-list">
          <li>With Grievance Officer</li>
          <li>With Data Protection Authority</li>
        </ul>

        {/* 16.4 Consent Management */}
        <h3>16.4 Consent Management</h3>

        <h4>16.4.1 When Consent Required</h4>
        <ul className="legal-list">
          <li>Aadhaar usage</li>
          <li>Credit bureau checks</li>
          <li>Marketing</li>
        </ul>

        <h4>16.4.2 Valid Consent Characteristics</h4>
        <ul className="legal-list">
          <li>Free</li>
          <li>Informed</li>
          <li>Specific</li>
          <li>Unambiguous</li>
          <li>Withdrawable</li>
        </ul>

        <h4>16.4.3 Consent Recording</h4>
        <ul className="legal-list">
          <li>Date/time</li>
          <li>Method</li>
          <li>Purpose</li>
          <li>Consent text</li>
        </ul>

        {/* 16.5 Data Sharing and Disclosure */}
        <h3>16.5 Data Sharing and Disclosure</h3>

        <h4>16.5.1 Permitted Disclosures Without Consent</h4>
        <ul className="legal-list">
          <li>FIU-IND</li>
          <li>SEBI, RBI, MCA</li>
          <li>ED, CBI, Police</li>
          <li>Court orders</li>
          <li>Income Tax Department</li>
          <li>Service providers (with NDA + DPA)</li>
        </ul>

        <h4>16.5.2 Third-Party Data Processing Agreements</h4>
        <ul className="legal-list">
          <li>Written DPA</li>
          <li>Scope & purpose</li>
          <li>Security requirements</li>
          <li>Sub-processor restrictions</li>
        </ul>

        <h4>16.5.3 Cross-Border Transfers</h4>
        <ul className="legal-list">
          <li>Only to adequate jurisdictions</li>
          <li>Or with explicit consent</li>
          <li>Or Standard Clauses</li>
        </ul>

        {/* 16.6 Privacy Governance */}
        <h3>16.6 Privacy Governance</h3>

        <h4>16.6.1 Grievance Officer / DPO</h4>
        <ul className="legal-list">
          <li>Handles complaints</li>
          <li>Responds within 30 days</li>
          <li>Contact details published</li>
        </ul>

        <h4>16.6.2 Privacy Policy</h4>
        <ul className="legal-list">
          <li>Published publicly</li>
          <li>Plain language</li>
          <li>Includes rights, purposes, disclosures</li>
        </ul>

        <h4>16.6.3 Privacy Impact Assessments</h4>
        <ul className="legal-list">
          <li>Required for new products</li>
        </ul>

        <h4>16.6.4 Data Breach Response</h4>
        <ul className="legal-list">
          <li>Containment + investigation</li>
          <li>Notify Authority within 72 hours</li>
          <li>Notify Customers if high risk</li>
          <li>Remediation + security strengthening</li>
        </ul>

      </section>

    </div>
  );
}

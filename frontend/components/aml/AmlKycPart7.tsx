// components/aml/AmlKycPart7.tsx
'use client';

import React from "react";

export default function AmlKycPart7() {
  return (
    <div>

      {/* 10.6.3 Secure Storage */}
      <section id="secure-storage" className="section">
        <div className="section-header">
          <span className="section-number">10.6.3</span>
          <h2 className="section-title">Secure Storage</h2>
        </div>

        <ul className="legal-list">
          <li>(a) Stored in secure, access-controlled location (physical safe for hard copies; encrypted digital storage)</li>
          <li>(b) Accessible only to Principal Officer, Designated Director, and specifically authorized personnel</li>
          <li>(c) Protected by multi-factor authentication (for digital records)</li>
          <li>(d) Backed up regularly with secure off-site backup</li>
          <li>(e) Segregated from general KYC records</li>
          <li>(f) Protected from unauthorized access, theft, loss, or destruction</li>
        </ul>
      </section>

      {/* 11. CASH TRANSACTION REPORTING */}
      <section id="ctr" className="section">
        <div className="section-header">
          <span className="section-number">11</span>
          <h2 className="section-title">Cash Transaction Reporting (CTR)</h2>
        </div>

        {/* 11.1 Legal Mandate */}
        <h3>11.1 Legal Mandate for Cash Transaction Reporting</h3>

        <h4>11.1.1 Statutory Basis</h4>

        <p>
          Rule 3(1)(b) of the PML Rules, 2005 requires reporting entities to furnish information on:
        </p>

        <blockquote className="legal-quote">
          “Cash transactions where forged or counterfeit currency notes or bank notes have been used as genuine or
          where any forgery of a valuable security or a document has taken place facilitating the transactions; and”
        </blockquote>

        <blockquote className="legal-quote">
          “All cash transactions of the value of more than rupees ten lakh or its equivalent in foreign currency.”
        </blockquote>

        <p>Additionally, FIU-IND Master Direction mandates reporting of:</p>

        <ul className="legal-list">
          <li>Cash Transaction Reports (CTR) for transactions ≥ INR 10,00,000</li>
          <li>Reporting on monthly basis</li>
        </ul>

        <h4>11.1.2 Definition of Cash Transaction</h4>

        <h5>"Cash" includes:</h5>
        <ul className="legal-list">
          <li>(a) Currency notes and coins</li>
          <li>(b) Foreign currency notes and coins</li>
          <li>(c) Traveler’s cheques</li>
          <li>(d) Demand drafts/pay orders/banker’s cheques purchased with cash</li>
          <li>(e) Instruments immediately convertible to cash</li>
        </ul>

        <h5>"Cash Transaction" means:</h5>
        <ul className="legal-list">
          <li>Receipt of cash by Platform</li>
          <li>Payment of cash by Platform</li>
          <li>Includes single or multiple connected cash transactions</li>
        </ul>

        <h4>11.1.3 Platform’s Cash Policy</h4>

        <h5>(a) Prohibition on Large Cash Transactions:</h5>
        <ul className="legal-list">
          <li>Cash transactions above INR 2,00,000 prohibited</li>
          <li>Banking channels must be used for substantial payments</li>
        </ul>

        <h5>(b) Small Cash Acceptance (Rare Exceptions):</h5>
        <ul className="legal-list">
          <li>Cash may be accepted only for fees/subscription &lt; INR 50,000</li>
          <li>Cash refund &lt; INR 50,000</li>
          <li>≥ INR 50,000 requires Principal Officer approval</li>
        </ul>

        <h5>(c) Justification for Policy:</h5>
        <ul className="legal-list">
          <li>Pre-IPO transactions high-value & not retail</li>
          <li>All customers have bank accounts</li>
          <li>Cash poses significant ML risk</li>
          <li>Aligned with SEBI/PMLA guidelines</li>
        </ul>

        {/* 11.2 Thresholds and Reporting */}
        <h3>11.2 Cash Transaction Thresholds and Reporting</h3>

        <h4>11.2.1 Reporting Threshold</h4>

        <ul className="legal-list">
          <li>(a) Single cash transaction ≥ INR 10,00,000</li>
          <li>(b) Series of connected transactions aggregating ≥ INR 10,00,000</li>
        </ul>

        <h5>"Connected Transactions" include:</h5>
        <ul className="legal-list">
          <li>Multiple transactions in same month</li>
          <li>Structuring to avoid threshold</li>
          <li>Same/related parties</li>
        </ul>

        <h4>11.2.2 CTR Filing Timeline</h4>

        <ul className="legal-list">
          <li>Monthly basis</li>
          <li>Report period: 1st–Month End</li>
          <li>Deadline: 15 days after month-end</li>
        </ul>

        {/* 11.3 CTR Filing Procedures */}
        <h3>11.3 CTR Filing Procedures</h3>

        <h4>11.3.1 Data Compilation</h4>

        <ul className="legal-list">
          <li>(a) End-of-month cash transaction report generated</li>
          <li>(b) Review for single ≥10L or aggregate ≥10L</li>
          <li>(c) Compile customer details</li>
          <li>(d) Verify against source documents</li>
        </ul>

        <h4>11.3.2 CTR Format and Content</h4>

        <h5>(a) Reporting Entity Information</h5>
        <p>Same as STR.</p>

        <h5>(b) Cash Transaction Details</h5>
        <ul className="legal-list">
          <li>Date</li>
          <li>Type (receipt/payment)</li>
          <li>Amount</li>
          <li>Currency</li>
          <li>Mode</li>
          <li>Purpose</li>
        </ul>

        <h5>(c) Customer Information</h5>
        <ul className="legal-list">
          <li>Full KYC details</li>
          <li>Occupation</li>
          <li>Account number</li>
        </ul>

        <h5>(d) Series of Transactions</h5>
        <ul className="legal-list">
          <li>Each transaction listed</li>
          <li>Total aggregate</li>
          <li>Connection explanation</li>
        </ul>

        <h4>11.3.3 CTR Filing System</h4>

        <ul className="legal-list">
          <li>(a) Filed through FINnet</li>
          <li>(b) XML upload</li>
          <li>(c) Digital signature</li>
          <li>(d) Acknowledgment retained</li>
        </ul>

        {/* 11.4 Cash Transaction Red Flags */}
        <h3>11.4 Cash Transaction Red Flags and Suspicious Activity</h3>

        <h4>11.4.1 High-Risk Indicators</h4>
        <ul className="legal-list">
          <li>(a) Customer insists on cash</li>
          <li>(b) Large denomination notes</li>
          <li>(c) Suspicious-looking cash</li>
          <li>(d) Cannot explain source</li>
          <li>(e) Inconsistent with profile</li>
          <li>(f) Structuring below 10L</li>
          <li>(g) Cash after bank cash deposit</li>
          <li>(h) Geographic anomalies</li>
        </ul>

        <h4>11.4.2 Dual Reporting — CTR and STR</h4>
        <ul className="legal-list">
          <li>(a) File both if suspicious and ≥ threshold</li>
          <li>(b) STR within 7 days</li>
          <li>(c) CTR by month-end deadline</li>
          <li>(d) Cross-reference in narratives</li>
        </ul>

        {/* 11.5 Counterfeit Currency */}
        <h3>11.5 Counterfeit Currency Reporting</h3>

        <h4>11.5.1 Identification of Counterfeit Currency</h4>

        <h5>(a) Immediate Actions</h5>
        <ul className="legal-list">
          <li>Transaction halted</li>
          <li>Note segregated</li>
          <li>Customer informed neutrally</li>
          <li>Bank/law enforcement consulted</li>
        </ul>

        <h5>(b) Verification</h5>
        <ul className="legal-list">
          <li>Visual inspection</li>
          <li>UV examination</li>
          <li>Compare with RBI guidelines</li>
          <li>Bank confirmation</li>
        </ul>

        <h5>(c) If Confirmed Counterfeit</h5>
        <ul className="legal-list">
          <li>Report to police</li>
          <li>CCR to FIU-IND</li>
          <li>Note seized</li>
          <li>Transaction rejected</li>
        </ul>

        <h4>11.5.2 Counterfeit Currency Report (CCR)</h4>

        <ul className="legal-list">
          <li>Denomination & serial</li>
          <li>Date & circumstances</li>
          <li>Customer details</li>
          <li>Police complaint details</li>
          <li>Suspicious indicators</li>
          <li>Timeline: 7 days</li>
        </ul>

      </section>

      {/* 12. Other Regulatory Reporting */}
      <section id="other-regulatory-reporting" className="section">
        <div className="section-header">
          <span className="section-number">12</span>
          <h2 className="section-title">Other Regulatory Reporting Obligations</h2>
        </div>

        <h3>12.1 Cross-Border Wire Transfer Reporting</h3>

        <h4>12.1.1 Applicability</h4>
        <ul className="legal-list">
          <li>(a) CBWT report filed for cross-border transfers ≥ INR 5,00,000</li>
          <li>(b) Includes inward/outward remittances</li>
          <li>(c) Includes transit transfers</li>
        </ul>

        <h4>12.1.2 Information Required</h4>
        <ul className="legal-list">
          <li>Ordering customer information</li>
          <li>Beneficiary information</li>
          <li>Intermediary bank details</li>
          <li>Amount, currency, purpose</li>
          <li>Value date</li>
        </ul>

        <h4>12.1.3 Timeline</h4>
        <p>Monthly; 15 days after month end.</p>

        <h3>12.2 NPO Transaction Reporting</h3>

        <h4>12.2.1 Applicability</h4>
        <ul className="legal-list">
          <li>Charitable trusts</li>
          <li>NGOs</li>
          <li>Foundations</li>
          <li>Religious organizations</li>
        </ul>

        <h4>12.2.2 Enhanced Due Diligence for NPOs</h4>

        <ul className="legal-list">
          <li>(a) Automatically Medium-Risk</li>
          <li>(b) Enhanced verification required</li>
          <li>(c) Ongoing monitoring for FCRA/tax compliance</li>
        </ul>

        <h3>12.3 Reports to Other Regulatory Authorities</h3>

        <h4>12.3.1 SEBI Reporting</h4>
        <ul className="legal-list">
          <li>(a) Suspected insider trading</li>
          <li>(b) Manipulation</li>
          <li>(c) Fraudulent practices</li>
          <li>(d) Operational incidents</li>
          <li>(e) Compliance breaches</li>
        </ul>

        <h4>12.3.2 MCA Reporting</h4>
        <ul className="legal-list">
          <li>Companies Act violations</li>
          <li>Document falsification</li>
          <li>Beneficial ownership concealment</li>
        </ul>

        <h4>12.3.3 Income Tax Department Reporting</h4>
        <ul className="legal-list">
          <li>(a) AIR filings</li>
          <li>(b) Transactions with tax-evasion indicators</li>
        </ul>

        <h4>12.3.4 RBI Reporting</h4>
        <ul className="legal-list">
          <li>(a) Suspected FEMA violations</li>
          <li>(b) Consult AD banks</li>
        </ul>

      </section>

      {/* 13. FIU-IND Interface */}
      <section id="fiu-interface" className="section">
        <div className="section-header">
          <span className="section-number">13</span>
          <h2 className="section-title">FIU-IND Interface and Relationship Management</h2>
        </div>

        <h3>13.1 Registration and Accreditation</h3>

        <h4>13.1.1 Mandatory Registration</h4>

        <ul className="legal-list">
          <li>(a) Register as Reporting Entity</li>
          <li>(b) Obtain unique code</li>
          <li>(c) Complete FINnet registration</li>
          <li>(d) Update details on change</li>
        </ul>

        <h4>13.1.2 Principal Officer Credentials</h4>
        <ul className="legal-list">
          <li>(a) PO details submitted</li>
          <li>(b) FINnet credentials issued</li>
          <li>(c) Backup accounts if permitted</li>
          <li>(d) Credentials secured & changed periodically</li>
        </ul>

        <h3>13.2 Ongoing Communication</h3>

        <h4>13.2.1 DPOC</h4>
        <p>Principal Officer serves as single point of contact.</p>

        <h4>13.2.2 Response to Communications</h4>
        <ul className="legal-list">
          <li>Acknowledgment within 24 hours</li>
          <li>Substantive response within 7–14 days</li>
          <li>Extensions requested with justification</li>
        </ul>

        <h4>13.2.3 Proactive Updates</h4>
        <ul className="legal-list">
          <li>Change in PO</li>
          <li>Change in DD</li>
          <li>Change in address</li>
          <li>Material changes in business model</li>
        </ul>

        <h3>13.3 Utilizing FIU-IND Resources</h3>

        <h4>13.3.1 Advisories and Typologies</h4>
        <ul className="legal-list">
          <li>Subscribe to FIU-IND circulars</li>
          <li>Review promptly</li>
          <li>Update rules</li>
          <li>Use for training</li>
        </ul>

        <h4>13.3.2 FIU-IND Feedback</h4>
        <ul className="legal-list">
          <li>STR quality feedback</li>
          <li>Implement improvements</li>
        </ul>

        <h4>13.3.3 Industry Consultations</h4>
        <ul className="legal-list">
          <li>Participate in consultations</li>
          <li>Attend meetings</li>
          <li>Share challenges</li>
        </ul>

        <h3>13.4 Inspections</h3>

        <h4>13.4.1 Cooperation with FIU-IND Inspections</h4>
        <ul className="legal-list">
          <li>Provide access to systems</li>
          <li>Provide documents</li>
          <li>Provide staff access</li>
          <li>Implement recommendations</li>
        </ul>

        <h4>13.4.2 Annual AML Returns</h4>
        <ul className="legal-list">
          <li>File accurately</li>
          <li>PO + DD sign</li>
          <li>External audit if required</li>
        </ul>

      </section>

      {/* 14. Record Keeping */}
      <section id="record-keeping" className="section">
        <div className="section-header">
          <span className="section-number">14</span>
          <h2 className="section-title">Record Keeping and Data Retention Requirements</h2>
        </div>

        <h3>14.1 Legal Framework for Record Keeping</h3>

        <h4>14.1.1 Statutory Mandate</h4>
        <ul className="legal-list">
          <li>(a) Maintain transaction records</li>
          <li>(b) Maintain client identification</li>
          <li>(c) Maintain suspicious transaction records</li>
          <li>(d) Maintain transaction details</li>
        </ul>

        <h4>14.1.2 Retention Period</h4>
        <p>
          Minimum 10 years from cessation of business relationship. Period starts at account closure date.
        </p>

        <h4>14.1.3 Purpose of Record Keeping</h4>
        <ul className="legal-list">
          <li>Enable reconstruction of transactions</li>
          <li>Provide audit trail</li>
          <li>Facilitate law enforcement investigations</li>
          <li>Support litigation</li>
          <li>Demonstrate compliance</li>
          <li>Establish due diligence defense</li>
        </ul>

        <h3>14.2 Categories of Records to be Maintained</h3>

        <h4>14.2.1 Customer Identification Records</h4>

        <h5>Category A: Identity Verification Documents</h5>

        <h6>(a) For Individuals:</h6>
        <ul className="legal-list">
          <li>OVDs</li>
          <li>Passport</li>
          <li>Driving License</li>
          <li>PAN</li>
          <li>Voter ID</li>
          <li>Aadhaar (masked)</li>
          <li>Address proofs</li>
          <li>Photographs</li>
          <li>Income documents</li>
          <li>SoF/SoW docs</li>
          <li>EDD documents</li>
        </ul>

        <h6>(b) For Body Corporate:</h6>
        <ul className="legal-list">
          <li>Certificate of Incorporation</li>
          <li>MOA/AOA</li>
          <li>PAN</li>
          <li>GST</li>
          <li>Board Resolution</li>
          <li>List of Directors</li>
          <li>Shareholding pattern</li>
          <li>Audited financials</li>
          <li>BO declarations</li>
          <li>Address proof</li>
        </ul>

        <h6>(c) For Trusts:</h6>
        <ul className="legal-list">
          <li>Trust Deed</li>
          <li>Registration</li>
          <li>PAN</li>
          <li>Trustee KYC</li>
          <li>Settlor details</li>
          <li>Beneficiary details</li>
        </ul>

        <h6>(d) For Partnerships/LLPs:</h6>
        <ul className="legal-list">
          <li>Partnership Deed/LLP Agreement</li>
          <li>Registration</li>
          <li>PAN</li>
          <li>Partner KYC</li>
          <li>Authorization documents</li>
          <li>Financial statements</li>
        </ul>

        <h5>Category B: Account Opening and Relationship Documentation</h5>
        <ul className="legal-list">
          <li>Account opening form</li>
          <li>Terms accepted</li>
          <li>Risk categorization</li>
          <li>CDD questionnaire</li>
          <li>EDD documentation</li>
          <li>Profile declarations</li>
          <li>Consent forms</li>
          <li>Signatures</li>
          <li>Address declarations</li>
        </ul>

        <h5>Category C: Verification Records</h5>
        <ul className="legal-list">
          <li>Video KYC recordings</li>
          <li>IPV reports</li>
          <li>Digital verification logs</li>
          <li>CKYC downloads</li>
          <li>PAN verification</li>
          <li>Bank verification</li>
          <li>Employer/business verification</li>
          <li>Address verification</li>
        </ul>

        <h5>Category D: Screening and Background Check Records</h5>
        <ul className="legal-list">
          <li>PEP screening</li>
          <li>Adverse media</li>
          <li>Sanctions list</li>
          <li>Court records</li>
          <li>Credit bureau reports</li>
          <li>Reference checks</li>
          <li>Internet research</li>
        </ul>

        <h5>Category E: Ongoing Due Diligence Records</h5>
        <ul className="legal-list">
          <li>Periodic KYC updates</li>
          <li>Risk rating reviews</li>
          <li>Profile change communications</li>
          <li>Updated documents</li>
          <li>Re-verification</li>
        </ul>

      </section>

    </div>
  );
}

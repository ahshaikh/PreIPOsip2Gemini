// components/aml/AmlKycPart19.tsx
'use client';

import React from "react";

export default function AmlKycPart19() {
  return (
    <div>

      {/* 28.2.2 Reasonable Ground of Suspicion */}
      <section id="reasonable-suspicion" className="section">
        <div className="section-header">
          <span className="section-number">28.2.2</span>
          <h2 className="section-title">What Constitutes "Reasonable Ground of Suspicion"</h2>
        </div>

        <p>The Platform files STR when transaction exhibits:</p>

        <h4>(a) Single Red Flag of High Severity:</h4>
        <ul className="legal-list">
          <li>Direct nexus to known criminal entity</li>
          <li>Structuring pattern clearly designed to evade reporting thresholds</li>
          <li>Source of funds from sanctioned jurisdiction or entity</li>
          <li>Customer admits illicit source during due diligence</li>
        </ul>

        <h4>(b) Multiple Red Flags of Moderate Severity:</h4>
        <ul className="legal-list">
          <li>Unusual transaction pattern + implausible source of funds explanation</li>
          <li>High-risk customer profile + sudden spike in activity + reluctance to provide documents</li>
          <li>Cash-intensive business + inconsistent transaction patterns + third-party payments</li>
        </ul>

        <h4>(c) Operational Judgment:</h4>
        <p>Platform Personnel are trained that "reasonable ground" is a <strong>lower threshold</strong> than "balance of probabilities." If doubt exists, file the STR.</p>
      </section>

      {/* 28.2.3 STR Filing Process */}
      <section id="str-filing" className="section">
        <div className="section-header">
          <span className="section-number">28.2.3</span>
          <h2 className="section-title">STR Filing Process</h2>
        </div>

        <h4>Step 1: Alert Generation</h4>
        <ul className="legal-list">
          <li>Automated system alert</li>
          <li>Manual detection</li>
        </ul>

        <h4>Step 2: Investigation</h4>
        <ul className="legal-list">
          <li>Compliance officer review within 24 hours</li>
          <li>Review customer profile & records</li>
          <li>Assess red flags</li>
          <li>Request information without tipping-off</li>
        </ul>

        <h4>Step 3: Decision</h4>
        <ul className="legal-list">
          <li>Decision made on reasonable suspicion</li>
          <li>Document rationale for non-filing</li>
        </ul>

        <h4>Step 4: STR Preparation</h4>
        <ul className="legal-list">
          <li>Complete FINnet STR</li>
          <li>Attach documents</li>
          <li>Doubt narrative</li>
          <li>Senior review</li>
        </ul>

        <h4>Step 5: Filing</h4>
        <ul className="legal-list">
          <li>File STR within 7 days</li>
          <li>Acknowledgment stored</li>
        </ul>

        <h4>Step 6: Follow-Up</h4>
        <ul className="legal-list">
          <li>Respond to FIU-IND queries</li>
          <li>Continue monitoring</li>
          <li>Maintain confidentiality</li>
        </ul>
      </section>

      {/* 28.2.4 CTR */}
      <section id="ctr" className="section">
        <div className="section-header">
          <span className="section-number">28.2.4</span>
          <h2 className="section-title">Cash Transaction Reports (CTR)</h2>
        </div>

        <ul className="legal-list">
          <li><strong>Threshold:</strong> Rs. 10 lakhs single or linked</li>
          <li><strong>Timeline:</strong> 15 days</li>
          <li><strong>Format:</strong> FINnet CTR</li>
          <li>CTR is threshold-based; STR is suspicion-based; both may apply</li>
        </ul>
      </section>

      {/* 28.2.5 CCR */}
      <section id="ccr" className="section">
        <div className="section-header">
          <span className="section-number">28.2.5</span>
          <h2 className="section-title">Counterfeit Currency Reports (CCR)</h2>
        </div>

        <ul className="legal-list">
          <li>Report within 24 hours</li>
          <li>Notify police</li>
          <li>Preserve evidence</li>
          <li>Cooperate fully</li>
        </ul>
      </section>

      {/* 28.2.6 NPO Reporting */}
      <section id="npo-reporting" className="section">
        <div className="section-header">
          <span className="section-number">28.2.6</span>
          <h2 className="section-title">Non-Profit Organization (NPO) Transaction Reporting</h2>
        </div>

        <ul className="legal-list">
          <li>Enhanced scrutiny for NGOs/trusts</li>
          <li>Verify registration</li>
          <li>Understand source/use of funds</li>
          <li>File STR for suspicious activity</li>
        </ul>
      </section>

      {/* 28.3 SEBI Inspections */}
      <section id="sebi-inspections" className="section">
        <div className="section-header">
          <span className="section-number">28.3.1</span>
          <h2 className="section-title">SEBI Inspections and Investigations</h2>
        </div>

        <h4>Inspection Procedures</h4>

        <h5>(a) Notice</h5>
        <ul className="legal-list">
          <li>Advance notice specifying date, scope, documents</li>
        </ul>

        <h5>(b) Preparation</h5>
        <ul className="legal-list">
          <li>Organize documents</li>
          <li>Brief personnel</li>
          <li>Point persons</li>
          <li>Ensure senior management availability</li>
        </ul>

        <h5>(c) During Inspection</h5>
        <ul className="legal-list">
          <li>Cooperate fully</li>
          <li>Truthful responses</li>
          <li>Take notes</li>
          <li>Provide documents later if needed</li>
        </ul>

        <h5>(d) Post Inspection</h5>
        <ul className="legal-list">
          <li>Respond to observations</li>
          <li>Implement corrective actions</li>
          <li>SEBI final report</li>
        </ul>

        <h4>Investigations</h4>
        <ul className="legal-list">
          <li>More adversarial; potential enforcement</li>
          <li>Summons under SEBI Act Sec 11(2)(h)</li>
          <li>Legal counsel recommended</li>
          <li>Comply fully</li>
        </ul>
      </section>

      {/* 28.3.2 FIU-IND */}
      <section id="fiu-ind" className="section">
        <div className="section-header">
          <span className="section-number">28.3.2</span>
          <h2 className="section-title">FIU-IND Interactions</h2>
        </div>

        <h4>STR Queries</h4>
        <ul className="legal-list">
          <li>Respond within 3–7 days</li>
          <li>Provide complete information</li>
          <li>Maintain confidentiality</li>
        </ul>

        <h4>Compliance Audits</h4>
        <ul className="legal-list">
          <li>Review STR quality</li>
          <li>Assess AML/KYC framework</li>
          <li>Address deficiencies</li>
          <li>Penalties possible</li>
        </ul>

        <h4>Advisories</h4>
        <ul className="legal-list">
          <li>Typologies</li>
          <li>Red flags</li>
          <li>Integrate into monitoring</li>
        </ul>
      </section>

      {/* 28.3.3 Enforcement Directorate */}
      <section id="ed-proceedings" className="section">
        <div className="section-header">
          <span className="section-number">28.3.3</span>
          <h2 className="section-title">Enforcement Directorate (ED) Proceedings</h2>
        </div>

        <h4>(a) Summons under PMLA Sec 50</h4>
        <ul className="legal-list">
          <li>Mandatory compliance</li>
          <li>Legal representation permitted</li>
        </ul>

        <h4>(b) Search and Seizure</h4>
        <ul className="legal-list">
          <li>Document proceedings</li>
          <li>Inventory seizure</li>
        </ul>

        <h4>(c) Provisional Attachment</h4>
        <ul className="legal-list">
          <li>File objections within 30 days</li>
        </ul>

        <h4>(d) Arrest</h4>
        <ul className="legal-list">
          <li>Right to legal counsel</li>
          <li>Apply for bail</li>
        </ul>

        <h4>(e) Prosecution</h4>
        <ul className="legal-list">
          <li>Special Court</li>
          <li>Document preservation</li>
        </ul>
      </section>

      {/* 28.3.4 Income Tax */}
      <section id="income-tax" className="section">
        <div className="section-header">
          <span className="section-number">28.3.4</span>
          <h2 className="section-title">Income Tax Department Proceedings</h2>
        </div>

        <h4>(a) Information Requests</h4>
        <ul className="legal-list">
          <li>PAN, transaction, payment info</li>
        </ul>

        <h4>(b) TDS Compliance</h4>
        <ul className="legal-list">
          <li>TDS deduction & deposit</li>
          <li>Certificates</li>
          <li>Quarterly returns</li>
        </ul>

        <h4>(c) Survey/Search</h4>
        <ul className="legal-list">
          <li>Cooperate fully</li>
          <li>Statements</li>
          <li>Books & documents</li>
        </ul>
      </section>

      {/* 28.3.5 Police & CBI */}
      <section id="police-cbi" className="section">
        <div className="section-header">
          <span className="section-number">28.3.5</span>
          <h2 className="section-title">Police and CBI Investigations</h2>
        </div>

        <h4>Criminal Complaints</h4>
        <ul className="legal-list">
          <li>Cooperate</li>
          <li>Provide documents</li>
          <li>Legal defense if charges filed</li>
        </ul>

        <h4>Witness Cases</h4>
        <ul className="legal-list">
          <li>Provide statements</li>
          <li>Attend trial if summoned</li>
        </ul>

        <h4>Economic Offences Wings</h4>
        <ul className="legal-list">
          <li>Provide records</li>
          <li>Professional cooperation</li>
        </ul>
      </section>

      {/* 28.4 Court Orders */}
      <section id="court-orders" className="section">
        <div className="section-header">
          <span className="section-number">28.4</span>
          <h2 className="section-title">Court Orders and Legal Process</h2>
        </div>

        <h4>28.4.1 Compliance</h4>
        <ul className="legal-list">
          <li>Verify authenticity</li>
          <li>Legal review</li>
          <li>Comply or challenge</li>
          <li>Document actions</li>
        </ul>

        <h4>28.4.2 Customer Info Disclosure</h4>
        <ul className="legal-list">
          <li>Court order overrides confidentiality</li>
          <li>No customer notification required</li>
        </ul>

        <h4>28.4.3 Garnishment</h4>
        <ul className="legal-list">
          <li>Freeze amount</li>
          <li>Respond to garnisher</li>
          <li>Customer notified if permitted</li>
        </ul>
      </section>

      {/* 28.5 Cross-Border */}
      <section id="cross-border" className="section">
        <div className="section-header">
          <span className="section-number">28.5</span>
          <h2 className="section-title">International Cooperation and Cross-Border Issues</h2>
        </div>

        <h4>28.5.1 MLAT Requests</h4>
        <ul className="legal-list">
          <li>Requests through Indian authorities</li>
          <li>Compliance per Indian law</li>
        </ul>

        <h4>28.5.2 Foreign Regulators</h4>
        <ul className="legal-list">
          <li>Consult SEBI</li>
          <li>Approval required</li>
          <li>No direct customer data sharing</li>
        </ul>

        <h4>28.5.3 Sanctions Compliance</h4>
        <ul className="legal-list">
          <li>UN sanctions binding</li>
          <li>Freeze accounts if match</li>
          <li>Voluntary enhanced due diligence</li>
        </ul>
      </section>

      {/* 28.6 Whistleblower */}
      <section id="whistleblower" className="section">
        <div className="section-header">
          <span className="section-number">28.6</span>
          <h2 className="section-title">Whistleblower Protection and Internal Reporting</h2>
        </div>

        <h4>28.6.1 Reporting Encouragement</h4>
        <ul className="legal-list">
          <li>Anonymous channels</li>
          <li>Retaliation protection</li>
          <li>Confidentiality</li>
          <li>Feedback</li>
        </ul>

        <h4>28.6.2 Reportable Concerns</h4>
        <ul className="legal-list">
          <li>ML/TF suspicion</li>
          <li>AML/KYC violations</li>
          <li>Pressure to ignore STRs</li>
          <li>Bribery/corruption</li>
          <li>Data manipulation</li>
          <li>Senior misconduct</li>
        </ul>

        <h4>28.6.3 Legal Protection</h4>
        <ul className="legal-list">
          <li>PMLA protection</li>
          <li>Companies Act protection</li>
          <li>Contractual protections</li>
        </ul>
      </section>

      {/* 28.7 Documentation */}
      <section id="reg-docs" className="section">
        <div className="section-header">
          <span className="section-number">28.7</span>
          <h2 className="section-title">Documentation and Record-Keeping of Regulatory Interactions</h2>
        </div>

        <h4>28.7.1 Required Records</h4>
        <ul className="legal-list">
          <li>Date/time</li>
          <li>Authority</li>
          <li>Nature of interaction</li>
          <li>Documents provided</li>
          <li>Statements</li>
          <li>Follow-up actions</li>
        </ul>

        <h4>28.7.2 Regulatory Affairs Log</h4>
        <ul className="legal-list">
          <li>Chronological records</li>
          <li>Deadline tracking</li>
          <li>Quarterly reporting</li>
        </ul>

        <h4>28.7.3 Privilege</h4>
        <ul className="legal-list">
          <li>Attorney-client privilege</li>
          <li>Work product</li>
          <li>Privilege log</li>
        </ul>
      </section>

      {/* 28.8 Public Enforcement */}
      <section id="public-enforcement" className="section">
        <div className="section-header">
          <span className="section-number">28.8</span>
          <h2 className="section-title">Public Enforcement Actions and Reputational Management</h2>
        </div>

        <h4>28.8.1 Responding to Public Enforcement</h4>
        <ul className="legal-list">
          <li>Legal response</li>
          <li>Public communication</li>
          <li>Customer communication</li>
          <li>Internal response</li>
        </ul>

        <h4>28.8.2 Media & PR</h4>
        <ul className="legal-list">
          <li>Designated spokesperson</li>
          <li>Consistent messaging</li>
          <li>Transparency</li>
          <li>Legal review</li>
          <li>Social media monitoring</li>
        </ul>

        <h4>28.8.3 Stakeholder Management</h4>
        <ul className="legal-list">
          <li>Board</li>
          <li>Investors</li>
          <li>Partners</li>
          <li>Employees</li>
          <li>Customers</li>
        </ul>
      </section>

      {/* 29. POLICY GOVERNANCE */}
      <section id="policy-governance" className="section">
        <div className="section-header">
          <span className="section-number">29</span>
          <h2 className="section-title">POLICY GOVERNANCE AND OVERSIGHT STRUCTURE</h2>
        </div>

        <h3>29.1 Board of Directors - Ultimate Accountability</h3>

        <h4>29.1.1 Board Responsibilities</h4>
        <ul className="legal-list">
          <li>Approve policy</li>
          <li>Appoint Principal Officer</li>
          <li>Allocate resources</li>
          <li>Monitor compliance</li>
          <li>Strategic direction</li>
          <li>Training</li>
          <li>Review audits</li>
          <li>Approve exceptions</li>
          <li>Annual certification</li>
        </ul>

        <h4>29.1.2 Board Composition</h4>
        <ul className="legal-list">
          <li>Director with compliance experience</li>
          <li>Independent directors</li>
          <li>Audit Committee expertise</li>
        </ul>

        <h4>29.1.3 Reporting Cadence</h4>

        <h5>Quarterly Reports:</h5>
        <ul className="legal-list">
          <li>Alert summary</li>
          <li>STR stats</li>
          <li>Account actions</li>
          <li>Inspections</li>
          <li>Complaints</li>
          <li>Training</li>
          <li>Tech updates</li>
          <li>Regulatory changes</li>
        </ul>

        <h5>Annual Report:</h5>
        <ul className="legal-list">
          <li>AMl/KYC review</li>
          <li>Statistical analysis</li>
          <li>External audit</li>
          <li>Benchmark</li>
          <li>Recommendations</li>
          <li>Budget</li>
        </ul>

        <h4>29.1.4 Liability</h4>
        <ul className="legal-list">
          <li>Duty of care</li>
          <li>PMLA liability</li>
          <li>Reputational risk</li>
          <li>Due diligence</li>
          <li>D&O insurance</li>
        </ul>

        <h3>29.2 Principal Officer - Operational Leadership</h3>

        <h4>29.2.1 Appointment</h4>
        <ul className="legal-list">
          <li>Senior management</li>
          <li>Reports to Board/CEO</li>
          <li>Qualified and experienced</li>
        </ul>

      </section>

    </div>
  );
}

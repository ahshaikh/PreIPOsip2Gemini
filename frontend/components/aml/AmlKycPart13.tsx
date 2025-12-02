// components/aml/AmlKycPart13.tsx
'use client';

import React from "react";

export default function AmlKycPart13() {
  return (
    <div>

      {/* 20. INTERNAL AUDIT AND COMPLIANCE TESTING */}
      <section id="internal-audit" className="section">
        <div className="section-header">
          <span className="section-number">20</span>
          <h2 className="section-title">INTERNAL AUDIT AND COMPLIANCE TESTING</h2>
        </div>

        {/* 20.1 Internal Audit Framework */}
        <h3>20.1 Internal Audit Framework</h3>

        <h4>20.1.1 Purpose and Objectives</h4>

        <ul className="legal-list">
          <li>(a) <strong>Assess Effectiveness:</strong> Evaluate whether AML/KYC controls are designed effectively and operating as intended;</li>
          <li>(b) <strong>Identify Gaps:</strong> Detect deficiencies, weaknesses, or control failures before they result in regulatory violations or financial crimes;</li>
          <li>(c) <strong>Ensure Compliance:</strong> Verify adherence to PMLA, SEBI regulations, Platform policies, and industry best practices;</li>
          <li>(d) <strong>Provide Assurance:</strong> Offer independent assurance to Board, senior management, and regulators that AML/KYC program is robust;</li>
          <li>(e) <strong>Drive Improvement:</strong> Recommend enhancements to processes, systems, and controls;</li>
          <li>(f) <strong>Deter Misconduct:</strong> Create accountability through regular examination;</li>
          <li>(g) <strong>Support Risk Management:</strong> Validate risk assessments and ensure controls commensurate with risks.</li>
        </ul>

        <h4>20.1.2 Statutory and Regulatory Requirements</h4>

        <p>While PMLA does not explicitly mandate internal audit, it is:</p>
        <ul className="legal-list">
          <li>Industry best practice and regulatory expectation</li>
          <li>Implicit in SEBI's requirement for intermediaries to have robust compliance frameworks</li>
          <li>Essential for demonstrating due diligence and good faith to regulators</li>
        </ul>

        <h4>20.1.3 Audit Philosophy – Risk-Based Approach</h4>

        <p>Internal audit applies risk-based methodology:</p>
        <ul className="legal-list">
          <li>High-risk areas audited more frequently and in greater depth</li>
          <li>Audit resources allocated based on risk assessment</li>
          <li>Emerging risks and regulatory focus areas prioritized</li>
          <li>Flexible audit plan adjusted for changing risk landscape</li>
        </ul>
      </section>

      {/* 20.2 Internal Audit Structure and Independence */}
      <section id="audit-structure" className="section">
        <div className="section-header">
          <span className="section-number">20.2</span>
          <h2 className="section-title">Internal Audit Structure and Independence</h2>
        </div>

        <h3>20.2.1 Organizational Structure</h3>

        <h4>(a) Internal Audit Department:</h4>
        <ul className="legal-list">
          <li>Dedicated internal audit function within Platform</li>
          <li>Staffed with qualified auditors (CAs, CISA, CIA, or equivalent experience)</li>
          <li>Adequate resources and budget</li>
          <li>Access to specialized expertise when needed (IT auditors, forensic specialists)</li>
        </ul>

        <h4>(b) Head of Internal Audit:</h4>
        <ul className="legal-list">
          <li>Senior professional with minimum 10 years audit/compliance experience</li>
          <li>Preferably with financial services and AML/KYC specialization</li>
          <li>Reports functionally to Audit Committee/Board</li>
          <li>Reports administratively to CEO/Designated Director</li>
        </ul>

        <h3>20.2.2 Independence and Objectivity</h3>

        <div className="callout">
          <p><strong>Critical Requirement:</strong> Internal Audit must be independent of functions being audited.</p>
        </div>

        <h4>(a) Structural Independence:</h4>
        <ul className="legal-list">
          <li>Separate from Compliance, Operations, Finance</li>
          <li>No operational responsibilities</li>
          <li>Not involved in design/implementation of controls being audited</li>
          <li>Advisory input allowed but primary role is assurance</li>
        </ul>

        <h4>(b) Functional Reporting:</h4>
        <ul className="legal-list">
          <li>Direct reporting to Audit Committee/Board</li>
          <li>Audit Committee approves audit plan, budget, resources</li>
          <li>Findings reported directly to Audit Committee</li>
        </ul>

        <h4>(c) Unrestricted Access:</h4>
        <ul className="legal-list">
          <li>Access to all records, systems, premises, Personnel</li>
          <li>Authority to interview Personnel without management present</li>
          <li>No management-imposed scope restrictions</li>
        </ul>

        <h4>(d) Objectivity:</h4>
        <ul className="legal-list">
          <li>No conflicts of interest</li>
          <li>No auditing areas they previously worked in within 2 years</li>
          <li>Findings evidence-based</li>
          <li>Professional skepticism upheld</li>
        </ul>

        <h3>20.2.3 Audit Committee Oversight</h3>

        <h4>(a) Audit Committee Composition:</h4>
        <ul className="legal-list">
          <li>Board-level committee</li>
          <li>Majority independent directors (if structure permits)</li>
          <li>At least one financial/audit expert</li>
          <li>Designated Director usually included</li>
        </ul>

        <h4>(b) Audit Committee Responsibilities:</h4>
        <ul className="legal-list">
          <li>Approve annual plan and budget</li>
          <li>Review audit reports</li>
          <li>Monitor implementation of recommendations</li>
          <li>Assess adequacy of internal audit function</li>
          <li>Meet privately with Head of IA</li>
          <li>Escalate serious findings to Board</li>
        </ul>
      </section>

      {/* 20.3 Annual Audit Plan and Scope */}
      <section id="annual-audit-plan" className="section">
        <div className="section-header">
          <span className="section-number">20.3</span>
          <h2 className="section-title">Annual Audit Plan and Scope</h2>
        </div>

        <h3>20.3.1 Risk-Based Audit Planning</h3>

        <h4>(a) Enterprise Risk Assessment:</h4>
        <ul className="legal-list">
          <li>Review of risk assessments (customer, product, geographic, channel)</li>
          <li>Identify inherent risks in Pre-IPO model</li>
          <li>Regulatory focus areas</li>
          <li>Past audit findings</li>
          <li>Management/PO/DD input</li>
        </ul>

        <h4>(b) Audit Universe:</h4>
        <ul className="legal-list">
          <li>Customer onboarding and KYC</li>
          <li>CDD/EDD</li>
          <li>Beneficial ownership</li>
          <li>Transaction monitoring</li>
          <li>Alert investigation</li>
          <li>STR/CTR filing</li>
          <li>Record keeping</li>
          <li>Information security</li>
          <li>Training programs</li>
          <li>Vendor management</li>
          <li>SEBI regulation compliance</li>
          <li>IT controls</li>
          <li>DPDP compliance</li>
          <li>Whistleblower effectiveness</li>
        </ul>

        <h4>(c) Audit Prioritization:</h4>
        <ul className="legal-list">
          <li>High-risk = high priority</li>
          <li>Regulatory focus</li>
          <li>Time since last audit</li>
          <li>New processes/systems</li>
          <li>Previous deficiencies</li>
        </ul>

        <h4>(d) Annual Plan Document:</h4>
        <ul className="legal-list">
          <li>List of audits</li>
          <li>Scope/objectives</li>
          <li>Timeline</li>
          <li>Resource allocation</li>
          <li>Budget</li>
          <li>Approved by Audit Committee</li>
        </ul>

        <h3>20.3.2 Audit Frequency</h3>

        <ul className="legal-list">
          <li><strong>Comprehensive AML/KYC Audit:</strong> annually</li>
          <li><strong>High-risk processes:</strong> Annually / semi-annually</li>
          <li><strong>Medium-risk:</strong> Every 18–24 months</li>
          <li><strong>Low-risk:</strong> Every 2–3 years</li>
          <li><strong>Continuous auditing:</strong> critical controls</li>
          <li><strong>Ad hoc audits:</strong> regulatory inquiry, breach, whistleblower, system failure</li>
        </ul>
      </section>

      {/* 20.4 Audit Execution and Methodology */}
      <section id="audit-execution" className="section">
        <div className="section-header">
          <span className="section-number">20.4</span>
          <h2 className="section-title">Audit Execution and Methodology</h2>
        </div>

        <h3>20.4.1 Audit Phases</h3>

        <h4>(a) Phase 1 - Planning and Preparation (Week 1–2):</h4>
        <ul className="legal-list">
          <li>Audit notification</li>
          <li>Review policies, past reports, regulatory guidance</li>
          <li>Preliminary interviews</li>
          <li>Develop audit program</li>
          <li>Team briefing</li>
        </ul>

        <h4>(b) Phase 2 - Fieldwork and Testing (Week 3–6):</h4>
        <ul className="legal-list">
          <li>Process walkthroughs</li>
          <li>Control testing</li>
          <li>Sample testing (30–50 KYC files, alerts, STRs)</li>
          <li>System testing (TM system config)</li>
          <li>Document review</li>
          <li>Personnel interviews</li>
          <li>Data analytics</li>
          <li>Evidence collection</li>
        </ul>

        <h4>(c) Phase 3 - Reporting and Finalization (Week 7–8):</h4>
        <ul className="legal-list">
          <li>Document findings</li>
          <li>Risk rating</li>
          <li>Root cause analysis</li>
          <li>Draft report</li>
          <li>Management exit meeting</li>
          <li>Management responses</li>
          <li>Final report issued</li>
        </ul>

        <h3>20.4.2 Audit Testing Procedures</h3>

        <h4>(a) Customer Onboarding and KYC Testing:</h4>
        <ul className="legal-list">
          <li>Statistical sampling</li>
          <li>Verify completeness of documentation</li>
          <li>PAN/address verification</li>
          <li>Beneficial ownership checks</li>
          <li>PEP/sanctions/adverse media checks</li>
          <li>Risk categorization</li>
          <li>Approvals verification</li>
          <li>Timeliness checks</li>
        </ul>

        <h4>(b) Transaction Monitoring Testing:</h4>
        <ul className="legal-list">
          <li>Rule configuration review</li>
          <li>Alert volume analysis</li>
          <li>False negative testing</li>
          <li>Alert investigation review</li>
          <li>STR quality review</li>
        </ul>

        <h4>(c) Record Keeping Testing:</h4>
        <ul className="legal-list">
          <li>10-year retention</li>
          <li>Digital/physical security</li>
          <li>Retrieval tests</li>
          <li>Backup and DR checks</li>
        </ul>

        <h4>(d) Training Effectiveness Testing:</h4>
        <ul className="legal-list">
          <li>100% training completion</li>
          <li>Content adequacy</li>
          <li>Assessment scores</li>
          <li>Personnel interviews</li>
        </ul>

        <h4>(e) Ongoing Monitoring & KYC Refresh Testing:</h4>
        <ul className="legal-list">
          <li>Timely refresh</li>
          <li>KYC quality</li>
          <li>Risk rating update</li>
          <li>Screening checks</li>
        </ul>

        <h3>20.4.3 Use of Data Analytics</h3>

        <h4>(a) Exception Testing:</h4>
        <ul className="legal-list">
          <li>Identify incomplete KYC, threshold breaches, expired documents</li>
        </ul>

        <h4>(b) Pattern Analysis:</h4>
        <ul className="legal-list">
          <li>Shared details, identical SOF narratives, clustering below thresholds</li>
        </ul>

        <h4>(c) Red Flag Scanning:</h4>
        <ul className="legal-list">
          <li>Automated detection</li>
        </ul>

        <h4>(d) Benchmarking:</h4>
        <ul className="legal-list">
          <li>Compare risk categories, alert volumes, STR rate, KYC rejection rates</li>
        </ul>
      </section>

      {/* 20.5 Audit Findings and Risk Ratings */}
      <section id="audit-findings" className="section">
        <div className="section-header">
          <span className="section-number">20.5</span>
          <h2 className="section-title">Audit Findings and Risk Ratings</h2>
        </div>

        <h3>20.5.1 Classification of Findings</h3>

        <h4>(a) Nature:</h4>
        <ul className="legal-list">
          <li>Control Deficiency</li>
          <li>Control Gap</li>
          <li>Policy Violation</li>
          <li>Regulatory Non-Compliance</li>
          <li>Best Practice Opportunity</li>
        </ul>

        <h4>(b) Severity/Risk Rating:</h4>

        <h5><strong>Critical:</strong></h5>
        <ul className="legal-list">
          <li>PMLA/SEBI violations</li>
          <li>Systemic failures</li>
          <li>No transaction monitoring</li>
          <li>STR failures</li>
          <li>Senior management misconduct</li>
        </ul>

        <h5><strong>High:</strong></h5>
        <ul className="legal-list">
          <li>Significant defects</li>
          <li>Material impact</li>
          <li>Repeat findings</li>
        </ul>

        <h5><strong>Medium:</strong></h5>
        <ul className="legal-list">
          <li>Moderate risk</li>
          <li>Procedural gaps</li>
        </ul>

        <h5><strong>Low:</strong></h5>
        <ul className="legal-list">
          <li>Minor issues</li>
          <li>Documentation gaps</li>
        </ul>

        <h3>20.5.2 Audit Report Structure</h3>

        <h4>(a) Executive Summary:</h4>
        <ul className="legal-list">
          <li>Opinion: Satisfactory / Needs Improvement / Unsatisfactory</li>
          <li>Scope, methodology</li>
          <li>Key findings</li>
        </ul>

        <h4>(b) Detailed Findings:</h4>
        <ul className="legal-list">
          <li>Observation</li>
          <li>Criteria</li>
          <li>Condition</li>
          <li>Cause</li>
          <li>Effect/Risk</li>
          <li>Recommendation</li>
          <li>Management response</li>
        </ul>

        <h4>(c) Positive Observations:</h4>
        <ul className="legal-list">
          <li>Strong controls</li>
          <li>Improvements</li>
        </ul>

        <h4>(d) Appendices:</h4>
        <ul className="legal-list">
          <li>Scope/methodology</li>
          <li>Sample lists</li>
          <li>Supporting data</li>
        </ul>

        <h3>20.5.3 Report Distribution</h3>

        <ul className="legal-list">
          <li>Audit Committee/Board</li>
          <li>Designated Director</li>
          <li>Principal Officer</li>
          <li>CEO/senior management</li>
          <li>Auditee</li>
          <li>Regulators if requested</li>
        </ul>
      </section>

      {/* 20.6 Management Response and Action Plans */}
      <section id="management-response" className="section">
        <div className="section-header">
          <span className="section-number">20.6</span>
          <h2 className="section-title">Management Response and Action Plans</h2>
        </div>

        <h3>20.6.1 Management Response Requirements</h3>

        <h4>(a) Acceptance or Disagreement:</h4>
        <ul className="legal-list">
          <li>Management accepts or challenges finding with evidence</li>
          <li>Audit Committee resolves disputes</li>
        </ul>

        <h4>(b) Corrective Action Plan:</h4>
        <ul className="legal-list">
          <li>Specific remediation actions</li>
          <li>Resources</li>
          <li>Responsible owner</li>
          <li>Target date</li>
          <li>Milestones</li>
        </ul>

        <h4>(c) Root Cause Mitigation:</h4>
        <ul className="legal-list">
          <li>Address actual weaknesses</li>
          <li>Process/system changes</li>
          <li>Training updates</li>
        </ul>

        <h4>(d) Timeline:</h4>
        <ul className="legal-list">
          <li><strong>Critical:</strong> plan in 7 days; fix in 30</li>
          <li><strong>High:</strong> plan in 15 days; fix in 60–90</li>
          <li><strong>Medium:</strong> fix in 90–180</li>
          <li><strong>Low:</strong> fix in 180</li>
        </ul>

      </section>

    </div>
  );
}

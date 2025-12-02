// components/aml/AmlKycPart20.tsx
'use client';

import React from "react";

export default function AmlKycPart20() {
  return (
    <div>

      {/* 29.2.2 Responsibilities and Authority */}
      <section id="po-responsibilities" className="section">
        <div className="section-header">
          <span className="section-number">29.2.2</span>
          <h2 className="section-title">Responsibilities and Authority of Principal Officer</h2>
        </div>

        <h3>Core Responsibilities</h3>
        <ul className="legal-list">
          <li>(a) <strong>Policy Oversight</strong>: Overall responsibility for implementation and enforcement of AML/KYC Policy;</li>
          <li>(b) <strong>STR Filing</strong>: Timely and accurate filing of STRs, CTRs, and other reports;</li>
          <li>(c) <strong>Risk Assessment</strong>: Conduct enterprise-wide ML/TF risk assessments;</li>
          <li>(d) <strong>Customer Due Diligence</strong>: Oversee CDD and EDD processes;</li>
          <li>(e) <strong>Transaction Monitoring</strong>: Supervise automated and manual monitoring;</li>
          <li>(f) <strong>Training</strong>: Design and implement AML/KYC training programs;</li>
          <li>(g) <strong>Regulatory Liaison</strong>: Primary contact with SEBI, FIU-IND, and authorities;</li>
          <li>(h) <strong>Internal Investigations</strong>: Lead internal compliance investigations;</li>
          <li>(i) <strong>Recordkeeping</strong>: Ensure proper maintenance and retention of records;</li>
          <li>(j) <strong>Technology</strong>: Oversee compliance technologies;</li>
          <li>(k) <strong>Reporting</strong>: Provide regular reports to Board/senior management;</li>
          <li>(l) <strong>Continuous Improvement</strong>: Identify gaps and strengthen framework.</li>
        </ul>

        <h3>Authority of Principal Officer</h3>
        <ul className="legal-list">
          <li>Approve or reject customer onboarding</li>
          <li>Freeze or close accounts based on ML/TF risk</li>
          <li>Refuse or reverse transactions</li>
          <li>Access all customer and transaction records</li>
          <li>Requisition additional resources</li>
          <li>Engage external consultants/forensics experts</li>
          <li>Recommend disciplinary action for non-compliance</li>
        </ul>
      </section>

      {/* 29.2.3 Protection & Indemnification */}
      <section id="po-protection" className="section">
        <div className="section-header">
          <span className="section-number">29.2.3</span>
          <h2 className="section-title">Protection and Indemnification of Principal Officer</h2>
        </div>

        <h4>Acting in Good Faith</h4>
        <ul className="legal-list">
          <li>Protected under PMLA Sec 12(1A)</li>
          <li>Indemnified by Platform</li>
          <li>Covered by D&O insurance</li>
          <li>Provided legal counsel</li>
        </ul>

        <h4>No Protection If:</h4>
        <ul className="legal-list">
          <li>Acting with malice or negligence</li>
          <li>Knowingly filing false reports</li>
          <li>Personal benefit from non-compliance</li>
          <li>Acting outside authority</li>
        </ul>
      </section>

      {/* 29.2.4 Deputy Principal Officer */}
      <section id="deputy-po" className="section">
        <div className="section-header">
          <span className="section-number">29.2.4</span>
          <h2 className="section-title">Deputy Principal Officer</h2>
        </div>

        <ul className="legal-list">
          <li>(a) Deputy appointed for continuity;</li>
          <li>(b) Acts in Principal Officer’s absence;</li>
          <li>(c) Both cannot be absent simultaneously;</li>
          <li>(d) Receives same training and authority.</li>
        </ul>
      </section>

      {/* 29.3 AML/KYC Committee */}
      <section id="aml-committee" className="section">
        <div className="section-header">
          <span className="section-number">29.3</span>
          <h2 className="section-title">AML/KYC Compliance Committee</h2>
        </div>

        <h3>29.3.1 Committee Composition</h3>
        <ul className="legal-list">
          <li>Principal Officer (Chair)</li>
          <li>CFO / Finance Head</li>
          <li>CTO / IT Security Head</li>
          <li>COO / Operations Leader</li>
          <li>Legal Counsel</li>
          <li>Customer Service Head</li>
          <li>Internal Audit Representative</li>
        </ul>

        <p>Meetings: Quarterly minimum; ad hoc as needed.</p>

        <h3>29.3.2 Committee Mandate</h3>

        <h4>Strategic Functions</h4>
        <ul className="legal-list">
          <li>Policy amendment review</li>
          <li>Approve risk methodologies</li>
          <li>Set risk thresholds</li>
          <li>Oversee technology implementations</li>
          <li>Monitor emerging typologies</li>
        </ul>

        <h4>Operational Functions</h4>
        <ul className="legal-list">
          <li>Review high-risk customers</li>
          <li>Decide complex CDD/EDD cases</li>
          <li>Approve policy exceptions</li>
          <li>Address systemic issues</li>
          <li>Review complaints & disputes</li>
          <li>Coordinate external audits</li>
        </ul>

        <h4>Governance Functions</h4>
        <ul className="legal-list">
          <li>Quarterly Board reporting</li>
          <li>Regulatory compliance</li>
          <li>Monitor KPIs</li>
          <li>Prepare for inspections</li>
          <li>Annual compliance budget review</li>
        </ul>

        <h3>29.3.3 Decision-Making & Documentation</h3>
        <ul className="legal-list">
          <li>All decisions documented</li>
          <li>Rationale recorded</li>
          <li>Dissent noted</li>
          <li>Minutes retained</li>
          <li>Privilege considerations applied</li>
        </ul>
      </section>

      {/* 29.4 AML/KYC Organizational Structure */}
      <section id="organizational-structure" className="section">
        <div className="section-header">
          <span className="section-number">29.4</span>
          <h2 className="section-title">Organizational Structure for AML/KYC Compliance</h2>
        </div>

        <h3>29.4.1 Three Lines of Defense Model</h3>

        <h4>First Line: Business Operations</h4>
        <ul className="legal-list">
          <li>Account opening teams</li>
          <li>Customer service</li>
          <li>Transaction processing</li>
          <li><strong>Responsibility:</strong> Execute AML/KYC controls</li>
          <li><strong>Accountability:</strong> Operational management</li>
        </ul>

        <h4>Second Line: Compliance Function</h4>
        <ul className="legal-list">
          <li>Principal Officer & team</li>
          <li>Risk management</li>
          <li>Legal & regulatory affairs</li>
          <li><strong>Responsibility:</strong> Develop & monitor controls</li>
          <li><strong>Accountability:</strong> Principal Officer to Board</li>
        </ul>

        <h4>Third Line: Internal Audit</h4>
        <ul className="legal-list">
          <li>Independent from operations & compliance</li>
          <li><strong>Responsibility:</strong> Verify AML/KYC effectiveness</li>
          <li><strong>Accountability:</strong> Chief Audit Executive to Audit Committee</li>
        </ul>

        <h3>29.4.2 Reporting Lines & Independence</h3>
        <ul className="legal-list">
          <li>Principal Officer reports to MD/CEO or Board</li>
          <li>Compliance independent from business units</li>
          <li>Internal audit independent from both</li>
          <li>No conflict of interest in performance evaluations</li>
        </ul>

        <h3>29.4.3 Staffing and Resources</h3>
        <ul className="legal-list">
          <li>Adequate staffing based on volume</li>
          <li>Qualifications (CAMS, CFE, CA, CS, CFA, LL.B)</li>
          <li>Advanced compliance technology</li>
          <li>Independent compliance budget</li>
          <li>Continuous training</li>
        </ul>
      </section>

      {/* 30. Policy Review and Version Control */}
      <section id="policy-review" className="section">
        <div className="section-header">
          <span className="section-number">30</span>
          <h2 className="section-title">Policy Review, Amendment, and Version Control</h2>
        </div>

        <h3>30.1 Mandatory Annual Review</h3>

        <h4>30.1.1 Annual Review Process</h4>
        <ul className="legal-list">
          <li>Initiate within 9 months of FY-end</li>
          <li>Complete before financial statements</li>
          <li>Publish within 30 days of approval</li>
        </ul>

        <h4>Scope</h4>
        <ul className="legal-list">
          <li>Regulatory changes</li>
          <li>Enterprise-Wide Risk Assessment</li>
          <li>Effectiveness analysis</li>
          <li>International benchmarks</li>
          <li>Tech changes</li>
          <li>Operational feedback</li>
        </ul>

        <h4>30.1.2 Review Participants</h4>
        <ul className="legal-list">
          <li>Principal Officer</li>
          <li>Compliance Committee</li>
          <li>Legal Counsel</li>
          <li>External consultants</li>
          <li>Business units</li>
          <li>Board approval</li>
        </ul>

        <h4>30.1.3 Documentation</h4>
        <ul className="legal-list">
          <li>Written report</li>
          <li>Change summary</li>
          <li>Board resolution</li>
          <li>Communication to Personnel</li>
          <li>Updated version published</li>
        </ul>

        <h3>30.2 Interim Amendments</h3>

        <h4>30.2.1 Triggers</h4>
        <ul className="legal-list">
          <li>Urgent regulatory changes</li>
          <li>ML/TF incidents</li>
          <li>Regulatory directives</li>
          <li>Audit findings</li>
          <li>Business model changes</li>
        </ul>

        <h4>30.2.2 Amendment Process</h4>
        <ul className="legal-list">
          <li>Proposal by Principal Officer</li>
          <li>Legal review</li>
          <li>Committee review</li>
          <li>Board approval</li>
          <li>Implementation</li>
          <li>Notifications</li>
          <li>Publication</li>
        </ul>

        <h4>30.2.3 Emergency Protocol</h4>
        <ul className="legal-list">
          <li>Interim measures approved by MD/CEO</li>
          <li>Board notified in 24 hours</li>
          <li>Formal process within 7 days</li>
          <li>Full documentation</li>
        </ul>

        <h3>30.3 Version Control & Document Management</h3>

        <h4>30.3.1 Version Numbering</h4>
        <ul className="legal-list">
          <li>Format X.Y</li>
          <li>X = major revision</li>
          <li>Y = interim change</li>
        </ul>

        <h4>30.3.2 Change Tracking</h4>
        <ul className="legal-list">
          <li>Version number + effective date</li>
          <li>Change log appendix</li>
          <li>Redline version internally</li>
          <li>Historical archive</li>
        </ul>

        <h4>30.3.3 Communication</h4>
        <ul className="legal-list">
          <li>Email to Personnel</li>
          <li>Updated training</li>
          <li>Customer notice if rights affected</li>
          <li>Regulator notification if required</li>
        </ul>

        <h3>30.4 Accessibility & Publication</h3>

        <h4>30.4.1 Public Availability</h4>
        <ul className="legal-list">
          <li>Policy on website</li>
          <li>PDF download</li>
          <li>Version history list</li>
          <li>Archived versions for regulators</li>
        </ul>

        <h4>30.4.2 Internal Availability</h4>
        <ul className="legal-list">
          <li>Intranet copy</li>
          <li>Quick reference guides</li>
          <li>Searchable format</li>
          <li>Training integration</li>
        </ul>
      </section>

      {/* 31. Recordkeeping */}
      <section id="recordkeeping" className="section">
        <div className="section-header">
          <span className="section-number">31</span>
          <h2 className="section-title">Recordkeeping, Data Retention, and Archival</h2>
        </div>

        <h3>31.1 Statutory Obligations</h3>

        <h4>31.1.1 PMLA Requirements</h4>
        <ul className="legal-list">
          <li>KYC documents</li>
          <li>Transaction records</li>
          <li>Risk assessments</li>
          <li>Correspondence</li>
          <li>Suspicious activity</li>
          <li>Account files</li>
        </ul>
        <p><strong>Retention:</strong> 10 years from last transaction or closure.</p>

        <h4>31.1.2 Additional Regulatory Requirements</h4>
        <ul className="legal-list">
          <li>SEBI records: 5–8 years</li>
          <li>Tax records: 7–10 years</li>
          <li>Companies Act registers: Permanent</li>
          <li>Platform standard: 10 years minimum</li>
        </ul>

        <h3>31.2 Recordkeeping Systems</h3>

        <h4>31.2.1 Electronic Records</h4>
        <ul className="legal-list">
          <li>Centralized repository</li>
          <li>Full-text search</li>
          <li>Role-based access</li>
          <li>Backups</li>
          <li>Encryption</li>
          <li>Integrity checks</li>
        </ul>

        <h4>31.2.2 Physical Records</h4>
        <ul className="legal-list">
          <li>Digitization</li>
          <li>Secure storage</li>
          <li>Access logs</li>
          <li>Certified destruction</li>
        </ul>

        <h4>31.2.3 Format Standards</h4>
        <ul className="legal-list">
          <li>Digital signatures</li>
          <li>PDF/A archival</li>
          <li>Metadata</li>
          <li>Audit trails</li>
        </ul>

        <h3>31.3 Data Retention Schedule</h3>

        <div className="table-wrapper">
          <table className="legal-table">
            <thead>
              <tr>
                <th>Record Type</th>
                <th>Retention Period</th>
                <th>Legal Basis</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>KYC documents</td>
                <td>10 years after closure</td>
                <td>PMLA</td>
              </tr>
              <tr>
                <td>Transaction records</td>
                <td>10 years</td>
                <td>PMLA</td>
              </tr>
              <tr>
                <td>STRs</td>
                <td>10 years</td>
                <td>PMLA</td>
              </tr>
              <tr>
                <td>Audit reports</td>
                <td>Permanent</td>
                <td>Best practice</td>
              </tr>
              <tr>
                <td>Complaints</td>
                <td>10 years</td>
                <td>SEBI</td>
              </tr>
              <tr>
                <td>Security incidents</td>
                <td>10 years</td>
                <td>Internal</td>
              </tr>
              <tr>
                <td>Employee checks</td>
                <td>10 years</td>
                <td>Internal</td>
              </tr>
            </tbody>
          </table>
        </div>

        <h4>31.3.2 Retention Review Process</h4>
        <ul className="legal-list">
          <li>Annual review</li>
          <li>Legal holds</li>
          <li>Destruction approval</li>
          <li>Certificate of destruction</li>
        </ul>

        <h3>31.4 Record Retrieval & Production</h3>

        <h4>31.4.1 Internal Retrieval</h4>
        <ul className="legal-list">
          <li>Formal request</li>
          <li>Access logging</li>
          <li>2-day SLA</li>
          <li>Quality control</li>
        </ul>

        <h4>31.4.2 Regulatory Production</h4>
        <ul className="legal-list">
          <li>7–30 day timeline</li>
          <li>Dedicated team</li>
          <li>Legal review</li>
          <li>Production log</li>
          <li>Follow-up</li>
        </ul>

        <h4>31.4.3 Court Subpoenas</h4>
        <ul className="legal-list">
          <li>Legal review</li>
          <li>Objections</li>
          <li>Protective orders</li>
          <li>Rolling production</li>
          <li>Privilege log</li>
          <li>Certification</li>
        </ul>

        <h3>31.5 Cross-Border Data Considerations</h3>

        <h4>31.5.1 Data Localization</h4>
        <ul className="legal-list">
          <li>Comply with Indian requirements</li>
          <li>Critical data retained in India</li>
          <li>Mirror copies</li>
          <li>Regulator access</li>
        </ul>

        <h4>31.5.2 Cross-Border Transfers</h4>
        <ul className="legal-list">
          <li>Legal basis required</li>
          <li>Recipient country adequate protection</li>
          <li>Standard contractual clauses</li>
          <li>Onward transfer restrictions</li>
        </ul>
      </section>

      {/* 32. Severability & Legal Effect */}
      <section id="severability" className="section">
        <div className="section-header">
          <span className="section-number">32</span>
          <h2 className="section-title">Severability, Interpretation, and Legal Effect</h2>
        </div>

        <h3>32.1 Severability Clause</h3>

        <h4>32.1.1 Severability Provision</h4>
        <ul className="legal-list">
          <li>Invalid provisions severed</li>
          <li>Reformation to lawful extent</li>
          <li>Other provisions continue</li>
          <li>Replacement required</li>
        </ul>

        <h4>32.1.2 Examples</h4>
        <ul className="legal-list">
          <li>Retention period adjustments</li>
          <li>Monitoring threshold changes</li>
          <li>STR timeline modifications</li>
        </ul>

        <h3>32.2 Conflict & Hierarchy</h3>

        <h4>32.2.1 Order of Precedence</h4>
        <ul className="legal-list">
          <li>(1) Statutes</li>
          <li>(2) Regulations</li>
          <li>(3) Regulatory circulars</li>
          <li>(4) This AML/KYC Policy</li>
          <li>(5) Terms of Service</li>
          <li>(6) SOPs</li>
          <li>(7) Personnel discretion</li>
        </ul>

        <h4>Conflict Resolution Principles</h4>
        <ul className="legal-list">
          <li>Higher authority prevails</li>
          <li>Interpretation favors compliance</li>
          <li>No deviation without approval</li>
        </ul>

        <h4>32.2.2 Policy Conflicts</h4>
        <ul className="legal-list">
          <li>AML/KYC prevails where compliance impacted</li>
          <li>Harmonization preferred</li>
          <li>Escalate irreconcilable conflicts</li>
          <li>Amend conflicting policies</li>
        </ul>

        <h3>32.3 Entire Agreement</h3>

        <h4>32.3.1 Complete Agreement</h4>
        <ul className="legal-list">
          <li>Includes Terms of Service, Privacy Policy, Disclosures, etc.</li>
        </ul>

        <h4>32.3.2 No Extrinsic Representations</h4>
        <ul className="legal-list">
          <li>No reliance on oral statements</li>
          <li>Customer service cannot modify policy</li>
          <li>Written terms prevail</li>
          <li>Clarifications must be in writing</li>
        </ul>

      </section>

    </div>
  );
}

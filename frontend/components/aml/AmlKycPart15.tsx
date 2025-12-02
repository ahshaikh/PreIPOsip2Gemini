// components/aml/AmlKycPart15.tsx
'use client';

import React from "react";

export default function AmlKycPart15() {
  return (
    <div>

      {/* 22.2.2 CAP Approval Process */}
      <section id="cap-approval-process" className="section">
        <div className="section-header">
          <span className="section-number">22.2.2</span>
          <h2 className="section-title">CAP Approval Process</h2>
        </div>

        <h4>(a) Initial CAP:</h4>
        <ul className="legal-list">
          <li>Prepared by responsible department/function</li>
          <li>Reviewed by Principal Officer</li>
          <li>Critical findings: Designated Director and/or Audit Committee approval</li>
          <li>Approved CAP documented and communicated</li>
        </ul>

        <h4>(b) Revisions:</h4>
        <ul className="legal-list">
          <li>If timeline cannot be met: Revision with justification required</li>
          <li>Approval by same authority that approved original CAP</li>
          <li>Extensions granted only for valid reasons (complexity, resource constraints)</li>
          <li>Not granted for lack of prioritization or effort</li>
        </ul>
      </section>

      {/* 22.3 Remediation Execution and Monitoring */}
      <section id="remediation-execution" className="section">
        <div className="section-header">
          <span className="section-number">22.3</span>
          <h2 className="section-title">Remediation Execution and Monitoring</h2>
        </div>

        <h3>22.3.1 Project Management Approach</h3>

        <p>For complex remediations:</p>
        <ul className="legal-list">
          <li>Project plan with tasks, dependencies, timelines</li>
          <li>Project manager assigned</li>
          <li>Regular status meetings</li>
          <li>Risk and issue tracking</li>
          <li>Stakeholder communication</li>
        </ul>

        <h3>22.3.2 Progress Tracking</h3>

        <h4>(a) Remediation Database/Tracker:</h4>
        <ul className="legal-list">
          <li>All open CAPs logged in centralized system</li>
          <li>Status updated regularly (Not Started / In Progress / Completed / Validated)</li>
          <li>% completion for multi-step actions</li>
          <li>RAG (Red-Amber-Green) status:</li>

          <ul className="legal-list ml-6">
            <li><strong>Green:</strong> On track</li>
            <li><strong>Amber:</strong> At risk of delay</li>
            <li><strong>Red:</strong> Delayed or blocked</li>
          </ul>
        </ul>

        <h4>(b) Ownership and Accountability:</h4>
        <ul className="legal-list">
          <li>Action owners provide regular updates (weekly or bi-weekly for critical items)</li>
          <li>Escalation if actions off-track</li>
          <li>Management review of overdue items</li>
        </ul>

        <h4>(c) Evidence Collection:</h4>
        <p>As actions completed, evidence collected:</p>
        <ul className="legal-list">
          <li>Revised policies/procedures (with version control and approval)</li>
          <li>Training completion records</li>
          <li>System configuration screenshots</li>
          <li>Sample testing results</li>
          <li>Communications/announcements</li>
        </ul>
        <p>Evidence uploaded to tracker</p>

        <h3>22.3.3 Status Reporting</h3>

        <h4>(a) Weekly:</h4>
        <ul className="legal-list">
          <li>Principal Officer reviews critical and high-priority CAPs</li>
          <li>Escalates issues to Designated Director</li>
        </ul>

        <h4>(b) Monthly:</h4>
        <ul className="legal-list">
          <li>Comprehensive remediation status report to senior management</li>
          <li>Metrics: Total open findings, completed this month, overdue, projected completion</li>
          <li>Deep dive on delayed items</li>
        </ul>

        <h4>(c) Quarterly:</h4>
        <ul className="legal-list">
          <li>Formal report to Audit Committee and Board</li>
          <li>Overall remediation progress</li>
          <li>Aging analysis</li>
          <li>Resource needs</li>
          <li>Risks and challenges</li>
        </ul>

        <h4>(d) To Regulators (If Required):</h4>
        <ul className="legal-list">
          <li>Progress reports as required by regulatory orders</li>
          <li>Typically monthly or quarterly</li>
          <li>Detailed evidence of actions taken</li>
          <li>Certification by Designated Director or CEO</li>
        </ul>
      </section>

      {/* 22.4 Validation and Closure */}
      <section id="validation-closure" className="section">
        <div className="section-header">
          <span className="section-number">22.4</span>
          <h2 className="section-title">Validation and Closure</h2>
        </div>

        <h3>22.4.1 Validation Process</h3>
        <p>Before finding marked as closed:</p>

        <h4>(a) Documentation Review:</h4>
        <ul className="legal-list">
          <li>Internal Audit or Compliance reviews evidence</li>
          <li>Verifies action completed as specified in CAP</li>
          <li>Assesses adequacy of documentation</li>
        </ul>

        <h4>(b) Effectiveness Testing:</h4>
        <ul className="legal-list">
          <li>Not just completion but effectiveness assessed</li>
          <li>Example: If action was "provide training," test whether training improved performance</li>
          <li>Sample testing of new process/control to verify operating effectively</li>
        </ul>

        <h4>(c) Sustainability Check:</h4>
        <ul className="legal-list">
          <li>Verify solution embedded in business-as-usual</li>
          <li>Not dependent on heroic efforts or single person</li>
          <li>Process documented and communicated</li>
          <li>Ongoing monitoring in place</li>
        </ul>

        <h4>(d) Approval:</h4>
        <ul className="legal-list">
          <li>Internal Audit or Compliance signs off on closure</li>
          <li>For critical findings: Designated Director approval</li>
          <li>For regulatory findings: Regulator may need to concur</li>
        </ul>

        <h3>22.4.2 Closure Documentation</h3>

        <p>Closure package includes:</p>
        <ul className="legal-list">
          <li>CAP with all actions marked complete</li>
          <li>Evidence of completion for each action</li>
          <li>Testing results demonstrating effectiveness</li>
          <li>Approval signatures</li>
          <li>Date of closure</li>
        </ul>

        <p>Retained in audit file and remediation tracker.</p>

        <h3>22.4.3 Communication</h3>

        <ul className="legal-list">
          <li>Action owner notified of successful closure</li>
          <li>Audit Committee informed in next quarterly report</li>
          <li>Organization-wide communication for major remediation projects (recognize team effort, share lessons learned)</li>
        </ul>
      </section>

      {/* 22.5 Repeat Findings and Escalation */}
      <section id="repeat-findings" className="section">
        <div className="section-header">
          <span className="section-number">22.5</span>
          <h2 className="section-title">Repeat Findings and Escalation</h2>
        </div>

        <h3>22.5.1 Identifying Repeat Findings</h3>

        <p>Repeat finding: Same or similar deficiency identified in subsequent audit despite prior remediation.</p>

        <p>Causes:</p>
        <ul className="legal-list">
          <li>Remediation was superficial (addressed symptom, not root cause)</li>
          <li>Remediation not sustained (reverted to old ways)</li>
          <li>Remediation not implemented fully</li>
          <li>New root cause emerged</li>
        </ul>

        <h3>22.5.2 Enhanced Response to Repeat Findings</h3>

        <h4>(a) Elevated Risk Rating:</h4>
        <ul className="legal-list">
          <li>Repeat finding automatically escalated one level (Medium → High; High → Critical)</li>
        </ul>

        <h4>(b) Senior Management Engagement:</h4>
        <ul className="legal-list">
          <li>Designated Director and CEO personally engaged</li>
          <li>Understanding why first remediation failed</li>
          <li>Commitment to more robust solution</li>
        </ul>

        <h4>(c) Independent Review:</h4>
        <ul className="legal-list">
          <li>External consultant or advisor brought in</li>
          <li>Fresh perspective on root cause and solution</li>
          <li>Benchmarking against best practices</li>
        </ul>

        <h4>(d) Accelerated Timeline:</h4>
        <ul className="legal-list">
          <li>Faster remediation required</li>
          <li>More frequent monitoring</li>
        </ul>

        <h4>(e) Accountability Measures:</h4>
        <ul className="legal-list">
          <li>Performance consequences for responsible managers</li>
          <li>May affect compensation, promotion</li>
          <li>Repeat pattern of failures: Fitness and propriety review</li>
        </ul>

        <h3>22.5.3 Regulatory Implications</h3>

        <ul className="legal-list">
          <li>Repeat findings viewed seriously by regulators</li>
          <li>Indicates lack of commitment or capability</li>
          <li>May result in enhanced regulatory oversight</li>
          <li>Increased likelihood of enforcement action</li>
        </ul>
      </section>

      {/* 22.6 Continuous Improvement Culture */}
      <section id="continuous-improvement" className="section">
        <div className="section-header">
          <span className="section-number">22.6</span>
          <h2 className="section-title">Continuous Improvement Culture</h2>
        </div>

        <h3>22.6.1 Learning from Findings</h3>

        <p>Beyond fixing individual issues, extract lessons:</p>
        <ul className="legal-list">
          <li>Themes across multiple findings (e.g., training gap, resource constraints, process complexity)</li>
          <li>Systemic improvements addressing themes</li>
          <li>Sharing lessons across organization</li>
          <li>Incorporating into training programs</li>
        </ul>

        <h3>22.6.2 Proactive Improvements</h3>

        <p>Not waiting for audit findings:</p>
        <ul className="legal-list">
          <li>Self-assessment by departments</li>
          <li>Benchmarking against peers</li>
          <li>Adopting emerging best practices</li>
          <li>Investing in technology and automation</li>
          <li>Continuous process optimization</li>
        </ul>

        <h3>22.6.3 Culture of Accountability</h3>

        <ul className="legal-list">
          <li>Compliance everyone's responsibility</li>
          <li>Speak-up culture (identifying issues early)</li>
          <li>Reward proactive identification and remediation</li>
          <li>Balance accountability (consequences for violations) with learning culture (not punitive for honest mistakes)</li>
        </ul>
      </section>

      {/* 23. TECHNOLOGY AND SYSTEM REQUIREMENTS */}
      <section id="technology-systems" className="section">
        <div className="section-header">
          <span className="section-number">23</span>
          <h2 className="section-title">TECHNOLOGY AND SYSTEM REQUIREMENTS FOR AML/KYC COMPLIANCE</h2>
        </div>

        {/* 23.1 Technology Framework Overview */}
        <h3>23.1 Technology Framework Overview</h3>

        <h4>23.1.1 Role of Technology in AML/KYC</h4>

        <p>Technology is fundamental to effective AML/KYC compliance, enabling:</p>
        <ul className="legal-list">
          <li>(a) <strong>Scale and Efficiency:</strong> Handle large volumes of customers and transactions impossible to manage manually;</li>
          <li>(b) <strong>Real-Time Monitoring:</strong> Detect suspicious patterns as they occur;</li>
          <li>(c) <strong>Consistency:</strong> Apply rules uniformly without human variability or bias;</li>
          <li>(d) <strong>Audit Trail:</strong> Comprehensive logging of all activities for regulatory accountability;</li>
          <li>(e) <strong>Data Analytics:</strong> Advanced analytics and pattern recognition beyond human capability;</li>
          <li>(f) <strong>Integration:</strong> Seamless data flow across systems eliminating manual errors and delays;</li>
          <li>(g) <strong>Regulatory Reporting:</strong> Automated generation and filing of regulatory reports;</li>
          <li>(h) <strong>Risk Management:</strong> Dynamic risk assessment and scoring.</li>
        </ul>

        <h4>23.1.2 Technology Investment Philosophy</h4>

        <p>The Platform recognizes that:</p>
        <ul className="legal-list">
          <li>Technology is not optional but essential regulatory infrastructure</li>
          <li>Adequate investment in compliance technology protects Platform from far greater costs (penalties, reputational damage, business disruption)</li>
          <li>Technology should be fit-for-purpose, scalable, and maintainable</li>
          <li>Build vs. Buy decisions based on core competency and cost-effectiveness</li>
          <li>Best-of-breed solutions preferred over generic tools</li>
        </ul>

        <h4>23.1.3 Regulatory Expectations</h4>

        <p>While regulators generally do not mandate specific technologies, they expect:</p>
        <ul className="legal-list">
          <li>Systems adequate for business model, risk profile, and transaction volumes</li>
          <li>Regular system upgrades and maintenance</li>
          <li>Documented system testing and validation</li>
          <li>Business continuity and disaster recovery capabilities</li>
          <li>Data security and integrity</li>
          <li>Audit trails and reporting capabilities</li>
        </ul>
      </section>

      {/* 23.2 Core AML/KYC Technology Systems */}
      <section id="core-systems" className="section">
        <div className="section-header">
          <span className="section-number">23.2</span>
          <h2 className="section-title">Core AML/KYC Technology Systems</h2>
        </div>

        {/* 23.2.1 CRM and KYC System */}
        <h3>23.2.1 Customer Relationship Management (CRM) and KYC System</h3>

        <p><strong>Purpose:</strong> Centralized repository for all customer information and KYC documentation.</p>

        <h4>Key Features Required:</h4>

        <h5>(a) Customer Data Management:</h5>
        <ul className="legal-list">
          <li>Single customer view aggregating all information</li>
          <li>Storage of identity documents, financial information, declarations</li>
          <li>Version control for document updates</li>
          <li>Linkage of related customers (family members, group entities)</li>
          <li>Relationship mapping for beneficial ownership structures</li>
        </ul>

        <h5>(b) Digital Document Management:</h5>
        <ul className="legal-list">
          <li>Upload and storage of scanned documents</li>
          <li>OCR (Optical Character Recognition) for data extraction from documents</li>
          <li>Document classification and indexing</li>
          <li>Secure access controls</li>
          <li>Retention and deletion management per policy</li>
        </ul>

        <h5>(c) Workflow Management:</h5>
        <ul className="legal-list">
          <li>Onboarding workflows with defined stages and approvals</li>
          <li>Task assignment and tracking</li>
          <li>SLA monitoring and alerts</li>
          <li>Escalation mechanisms</li>
          <li>Status dashboards for management visibility</li>
        </ul>

        <h5>(d) Risk Assessment Module:</h5>
        <ul className="legal-list">
          <li>Automated risk scoring based on customer parameters</li>
          <li>Risk categorization (Low/Medium/High)</li>
          <li>Override capability with approval and justification</li>
          <li>Risk rating history and change tracking</li>
        </ul>

        <h5>(e) Periodic Review and Refresh:</h5>
        <ul className="legal-list">
          <li>Automated alerts for KYC due for refresh</li>
          <li>Workflow for KYC update process</li>
          <li>Comparison of updated vs. previous KYC data</li>
          <li>Trigger for risk re-assessment</li>
        </ul>

        <h5>(f) Integration Capabilities:</h5>
        <ul className="legal-list">
          <li>API integration with transaction monitoring system</li>
          <li>Integration with e-KYC providers (UIDAI, DigiLocker, CKYC)</li>
          <li>Integration with verification services (PAN verification, bank account verification)</li>
          <li>Integration with screening databases</li>
        </ul>

        <h5>(g) Reporting and Analytics:</h5>
        <ul className="legal-list">
          <li>KYC completion reports</li>
          <li>Aging reports (overdue KYC refresh)</li>
          <li>Risk distribution reports</li>
          <li>TAT (Turnaround Time) analysis</li>
          <li>Exception reports</li>
        </ul>

        <h4>Implementation Considerations:</h4>
        <ul className="legal-list">
          <li>Cloud-based or on-premise based on data residency requirements and cost</li>
          <li>Mobile accessibility for field staff</li>
          <li>Multi-language support if needed</li>
          <li>Scalability for customer growth</li>
          <li>Vendor support and SLAs</li>
        </ul>

        {/* 23.2.2 TMS */}
        <h3>23.2.2 Transaction Monitoring System (TMS)</h3>

        <p><strong>Purpose:</strong> Real-time monitoring of all transactions to detect suspicious patterns and generate alerts.</p>

        <h4>Key Features Required:</h4>

        <h5>(a) Real-Time Data Ingestion:</h5>
        <ul className="legal-list">
          <li>Capture transaction data as it occurs (near real-time, typically within minutes)</li>
          <li>Support for high transaction volumes without lag</li>
          <li>Data validation and quality checks</li>
          <li>Handling of multiple transaction types</li>
        </ul>

        <h5>(b) Rule Engine:</h5>
        <ul className="legal-list">
          <li>Flexible rules engine supporting complex logic</li>
          <li>Parameterized rules (thresholds, timeframes easily configurable)</li>
          <li>Boolean operators (AND, OR, NOT)</li>
          <li>Support for scenario-based rules (multi-step patterns)</li>
          <li>Rule versioning and change management</li>
          <li>A/B testing capability for rule tuning</li>
        </ul>

        <h5>(c) Pre-Configured Rule Library:</h5>
        <ul className="legal-list">
          <li>Standard AML scenarios (structuring, rapid movement, round amounts, etc.)</li>
          <li>Pre-IPO specific rules</li>
          <li>Regularly updated based on typologies</li>
          <li>Customization capability</li>
        </ul>

        <h5>(d) Pattern Recognition and Behavioral Analytics:</h5>
        <ul className="legal-list">
          <li>Baseline behavior for each customer</li>
          <li>Deviation detection</li>
          <li>Peer group analysis</li>
          <li>Link analysis</li>
          <li>Machine learning anomaly detection (if advanced)</li>
        </ul>

        <h5>(e) Alert Generation and Prioritization:</h5>
        <ul className="legal-list">
          <li>Automated alerts</li>
          <li>Risk scoring</li>
          <li>Alert deduplication</li>
          <li>Alert aggregation</li>
        </ul>

        <h5>(f) Case Management:</h5>
        <ul className="legal-list">
          <li>Alert assignment</li>
          <li>Investigation workflow</li>
          <li>SLA tracking</li>
          <li>Documentation</li>
          <li>Approval workflows</li>
          <li>Escalations</li>
        </ul>

        <h5>(g) False Positive Management:</h5>
        <ul className="legal-list">
          <li>Tuning tools</li>
          <li>Whitelisting capability</li>
          <li>Alert analytics</li>
        </ul>

        <h5>(h) Integration:</h5>
        <ul className="legal-list">
          <li>Data feed from payment/transaction systems</li>
          <li>KYC system integration</li>
          <li>Screening databases</li>
          <li>STR/CTR system integration</li>
        </ul>

        <h5>(i) Reporting and Analytics:</h5>
        <ul className="legal-list">
          <li>Alert volumes</li>
          <li>Aging</li>
          <li>Investigator productivity</li>
          <li>Rule effectiveness</li>
        </ul>

        <h5>(j) Audit Trail:</h5>
        <ul className="legal-list">
          <li>Complete logging</li>
          <li>Access logs</li>
          <li>Rule changes</li>
          <li>Alert actions</li>
        </ul>

        <h4>Technical Architecture:</h4>
        <ul className="legal-list">
          <li>High availability</li>
          <li>Load balancing</li>
          <li>Optimized database</li>
          <li>Retention strategy</li>
        </ul>

        <h4>Vendor Selection:</h4>
        <ul className="legal-list">
          <li>Track record</li>
          <li>Regulatory compliance</li>
          <li>Scalability</li>
          <li>Support</li>
        </ul>

        {/* 23.2.3 Screening System */}
        <h3>23.2.3 Screening System</h3>

        <p><strong>Purpose:</strong> Screen customers and transactions against sanctions, PEPs, and adverse media lists.</p>

        <h4>Key Features:</h4>

        <h5>(a) Comprehensive Watchlists:</h5>
        <ul className="legal-list">
          <li>UN list</li>
          <li>OFAC</li>
          <li>EU lists</li>
          <li>National sanctions lists</li>
          <li>PEP databases</li>
          <li>Adverse media</li>
        </ul>

        <h5>(b) Screening Scope:</h5>
        <ul className="legal-list">
          <li>Onboarding</li>
          <li>Periodic re-screening</li>
          <li>Transaction screening</li>
        </ul>

        <h5>(c) Fuzzy Matching:</h5>
        <ul className="legal-list">
          <li>Name variations</li>
          <li>Spelling differences</li>
          <li>Phonetic matching</li>
        </ul>

        <h5>(d) False Positive Reduction:</h5>
        <ul className="legal-list">
          <li>DOB matching</li>
          <li>Address matching</li>
          <li>ID number matching</li>
        </ul>

        <h5>(e) Alert Management:</h5>
        <ul className="legal-list">
          <li>Risk scoring</li>
          <li>Investigation workflow</li>
          <li>Documentation</li>
        </ul>

        <h5>(f) Case Resolution:</h5>
        <ul className="legal-list">
          <li>True match → Escalation</li>
          <li>False positive → Documentation</li>
          <li>Partial match → EDD</li>
        </ul>

        <h5>(g) Audit Trail:</h5>
        <ul className="legal-list">
          <li>All results logged</li>
          <li>Decisions retained</li>
        </ul>

        {/* 23.2.4 STR/CTR Filing System */}
        <h3>23.2.4 STR/CTR Filing and Regulatory Reporting System</h3>

        <p><strong>Purpose:</strong> Prepare and file regulatory reports with FIU-IND and authorities.</p>

        <h4>Key Features:</h4>

        <h5>(a) STR Preparation:</h5>
        <ul className="legal-list">
          <li>Template-based entry</li>
          <li>Auto-populated from investigations</li>
          <li>Attachments</li>
          <li>Validation checks</li>
        </ul>

        <h5>(b) FINnet Integration:</h5>
        <ul className="legal-list">
          <li>Direct submission</li>
          <li>XML generation</li>
          <li>Digital signatures</li>
          <li>Error handling</li>
        </ul>

        <h5>(c) CTR/CBWT Compilation:</h5>
        <ul className="legal-list">
          <li>Automated aggregation</li>
          <li>Bulk submission</li>
        </ul>

        <h5>(d) Deadline Tracking:</h5>
        <ul className="legal-list">
          <li>STR 7-day timers</li>
          <li>Approaching deadline alerts</li>
        </ul>

        <h5>(e) STR Register:</h5>
        <ul className="legal-list">
          <li>Central repository</li>
          <li>Searchable</li>
          <li>FIU-IND correspondence</li>
        </ul>

        <h5>(f) Confidentiality Controls:</h5>
        <ul className="legal-list">
          <li>Restricted access</li>
          <li>Audit logs</li>
          <li>Encryption</li>
        </ul>

      </section>

    </div>
  );
}

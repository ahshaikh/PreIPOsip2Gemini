// components/aml/AmlKycPart14.tsx
'use client';

import React from "react";

export default function AmlKycPart14() {
  return (
    <div>

      {/* 20.6.2 Tracking and Follow-Up */}
      <section id="tracking-followup" className="section">
        <div className="section-header">
          <span className="section-number">20.6.2</span>
          <h2 className="section-title">Tracking and Follow-Up</h2>
        </div>

        <h4>(a) Audit Issue Tracker:</h4>
        <ul className="legal-list">
          <li>Database/system tracking all open audit findings</li>
          <li>Status updated regularly (not started / in progress / completed)</li>
          <li>Aging reports highlighting overdue items</li>
          <li>Escalation for delayed remediations</li>
        </ul>

        <h4>(b) Evidence of Remediation:</h4>
        <ul className="legal-list">
          <li>Management provides evidence that action completed</li>
          <li>Documentation, revised procedures, system screenshots, training records, etc.</li>
          <li>Internal Audit reviews evidence and validates closure</li>
        </ul>

        <h4>(c) Follow-Up Audits:</h4>
        <ul className="legal-list">
          <li>Critical findings: Follow-up audit within 3–6 months</li>
          <li>Repeat findings: Enhanced scrutiny in next audit</li>
          <li>Test whether remediation effective and sustained</li>
        </ul>

        <h4>(d) Quarterly Reporting:</h4>
        <ul className="legal-list">
          <li>Status of open audit findings reported to Audit Committee quarterly</li>
          <li>Aging analysis</li>
          <li>Overdue items escalated</li>
          <li>Accountability for delays</li>
        </ul>
      </section>

      {/* 20.7 Continuous Monitoring and KRIs */}
      <section id="continuous-monitoring" className="section">
        <div className="section-header">
          <span className="section-number">20.7</span>
          <h2 className="section-title">Continuous Monitoring and Key Risk Indicators (KRIs)</h2>
        </div>

        <h3>20.7.1 Continuous Monitoring Program</h3>
        <p>Between formal audits, continuous monitoring provides ongoing assurance:</p>

        <h4>(a) Automated Controls Testing:</h4>
        <ul className="legal-list">
          <li>Daily/weekly automated tests of critical controls</li>
          <li>Examples:</li>
          <ul className="legal-list ml-6">
            <li>All accounts activated have complete KYC</li>
            <li>All high-value transactions reviewed by compliance</li>
            <li>All alerts investigated within SLA</li>
            <li>No STR filing deadline missed</li>
          </ul>
        </ul>

        <h4>(b) Exception Reporting:</h4>
        <ul className="legal-list">
          <li>System-generated reports of exceptions</li>
          <li>Distributed to compliance and management daily/weekly</li>
          <li>Prompt investigation and remediation</li>
        </ul>

        <h4>(c) Dashboard Monitoring:</h4>
        <ul className="legal-list">
          <li>Real-time dashboards of key metrics</li>
          <li>Visible to management and Internal Audit</li>
          <li>Trend analysis</li>
        </ul>

        <h3>20.7.2 Key Risk Indicators (KRIs)</h3>
        <p>Quantitative metrics monitored continuously:</p>

        <h4>(a) Customer Onboarding KRIs:</h4>
        <ul className="legal-list">
          <li>% of new customers with complete KYC (Target: 100%)</li>
          <li>Average time to complete KYC (Target: &lt;7 days)</li>
          <li>% with overdue KYC refresh (Target: &lt;5%)</li>
          <li>KYC rejection rate</li>
          <li>% of high-risk customers with EDD completed (Target: 100%)</li>
        </ul>

        <h4>(b) Transaction Monitoring KRIs:</h4>
        <ul className="legal-list">
          <li>Alert volume per 1000 transactions</li>
          <li>% alerts investigated within SLA (Target: &gt;95%)</li>
          <li>Alert-to-STR conversion rate (1–5%)</li>
          <li>Average alert investigation time (&lt;48 hours for P1)</li>
        </ul>

        <h4>(c) STR Filing KRIs:</h4>
        <ul className="legal-list">
          <li>Number of STRs filed</li>
          <li>% filed within 7-day deadline (Target: 100%)</li>
          <li>STR quality scores</li>
          <li>FIU-IND queries received</li>
        </ul>

        <h4>(d) Training KRIs:</h4>
        <ul className="legal-list">
          <li>% Personnel with current training (Target: 100%)</li>
          <li>Avg. assessment score (&gt;85%)</li>
          <li>Training hours per employee (&gt;8 hours/year)</li>
        </ul>

        <h4>(e) Record Keeping KRIs:</h4>
        <ul className="legal-list">
          <li>Record retrieval time (&lt;1 hour)</li>
          <li>% meeting retention requirements (100%)</li>
          <li>Data backup success rate (100%)</li>
        </ul>

        <h4>(f) System Performance KRIs:</h4>
        <ul className="legal-list">
          <li>TM system uptime (&gt;99.5%)</li>
          <li>KYC system availability (&gt;99%)</li>
          <li>Data processing delay (&lt;1 hour)</li>
        </ul>

        <h4>KRI Thresholds:</h4>
        <ul className="legal-list">
          <li><strong>Green:</strong> Within range</li>
          <li><strong>Yellow:</strong> Approaching limit</li>
          <li><strong>Red:</strong> Breach → Immediate action</li>
        </ul>

        <p>KRIs reviewed weekly by Principal Officer, monthly by Designated Director, quarterly by Audit Committee.</p>
      </section>

      {/* 21. External Audit & Regulatory Inspections */}
      <section id="external-audit" className="section">
        <div className="section-header">
          <span className="section-number">21</span>
          <h2 className="section-title">EXTERNAL AUDIT AND REGULATORY INSPECTIONS</h2>
        </div>

        {/* 21.1 External Audit Framework */}
        <h3>21.1 External Audit Framework</h3>

        <h4>21.1.1 Statutory Audit</h4>

        <h5>(a) Annual Financial Audit:</h5>
        <ul className="legal-list">
          <li>Statutory auditors appointed per Companies Act</li>
          <li>Audit includes compliance with significant laws</li>
          <li>Review of internal financial controls</li>
          <li>AML/KYC compliance indirectly reviewed</li>
        </ul>

        <h5>(b) Auditor's Responsibility:</h5>
        <ul className="legal-list">
          <li>Report on legal compliance</li>
          <li>Report on internal controls</li>
          <li>Escalate material weaknesses</li>
        </ul>

        <h5>(c) Platform's Cooperation:</h5>
        <ul className="legal-list">
          <li>Full access to records</li>
          <li>Respond to queries</li>
          <li>Facilitate meetings</li>
          <li>Implement recommendations</li>
        </ul>

        <h4>21.1.2 Concurrent Audit (If Applicable)</h4>

        <ul className="legal-list">
          <li>Monthly/quarterly cycles</li>
          <li>Focus on high-risk areas</li>
          <li>Early detection of issues</li>
        </ul>

        <h4>21.1.3 Specialized AML/KYC Audit</h4>

        <h5>(a) When Conducted:</h5>
        <ul className="legal-list">
          <li>Annually / bi-annually</li>
          <li>Pre-inspection readiness</li>
          <li>Post significant changes</li>
          <li>Regulatory concern</li>
          <li>Investor due diligence</li>
        </ul>

        <h5>(b) Scope:</h5>
        <ul className="legal-list">
          <li>Full AML/KYC program review</li>
          <li>Benchmarking</li>
          <li>Effectiveness assessment</li>
          <li>Gap identification</li>
        </ul>

        <h5>(c) External Auditor Selection:</h5>
        <ul className="legal-list">
          <li>Reputed AML/KYC experts</li>
          <li>Regulatory experience</li>
          <li>Independence</li>
        </ul>

        <h5>(d) Deliverables:</h5>
        <ul className="legal-list">
          <li>Audit report</li>
          <li>Gap analysis</li>
          <li>Recommendations</li>
          <li>Remediation roadmap</li>
          <li>Board presentation</li>
        </ul>
      </section>

      {/* 21.2 Regulatory Inspections */}
      <section id="regulatory-inspections" className="section">
        <div className="section-header">
          <span className="section-number">21.2</span>
          <h2 className="section-title">Regulatory Inspections and Examinations</h2>
        </div>

        <h3>21.2.1 Regulatory Authorities with Inspection Rights</h3>

        <h4>(a) FIU-IND:</h4>
        <ul className="legal-list">
          <li>PMLA inspection authority</li>
          <li>Focus: AML/KYC, STR quality, record keeping</li>
          <li>Frequency: 2–5 years</li>
        </ul>

        <h4>(b) SEBI:</h4>
        <ul className="legal-list">
          <li>Inspection under SEBI Act</li>
          <li>Focus: compliance & investor protection</li>
          <li>Frequency: 3–5 years</li>
        </ul>

        <h4>(c) Enforcement Directorate (ED):</h4>
        <ul className="legal-list">
          <li>Criminal investigation under PMLA</li>
          <li>Search, summon, record inspection</li>
        </ul>

        <h4>(d) Reserve Bank of India (if applicable):</h4>
        <ul className="legal-list">
          <li>AML/KYC review in NBFC/other license cases</li>
        </ul>

        <h4>(e) Other Authorities:</h4>
        <ul className="legal-list">
          <li>Income Tax Department</li>
          <li>Registrar of Companies</li>
          <li>State enforcement agencies</li>
        </ul>

        <h3>21.2.2 Inspection Process</h3>

        <h4>(a) Inspection Notice:</h4>
        <ul className="legal-list">
          <li>Notice 7–30 days in advance (except ED raids)</li>
          <li>Includes scope, dates, records needed</li>
        </ul>

        <h4>(b) Pre-Inspection Preparation:</h4>

        <ul className="legal-list">
          <li>Notify Designated Director & PO immediately</li>
          <li>Engage legal counsel</li>
          <li>Form response team</li>
          <li>Prepare documents, mock inspection</li>
        </ul>

        <h4>(c) During Inspection:</h4>

        <h5>Day 1 – Opening Meeting:</h5>
        <ul className="legal-list">
          <li>Regulator explains scope</li>
          <li>Platform presents AML/KYC overview</li>
        </ul>

        <h5>Days 2–5 – Fieldwork:</h5>
        <ul className="legal-list">
          <li>Review documents</li>
          <li>KYC sampling</li>
          <li>System demonstrations</li>
          <li>Personnel interviews</li>
          <li>Data analysis</li>
        </ul>

        <h5>Exit Meeting:</h5>
        <ul className="legal-list">
          <li>Preliminary findings presented</li>
          <li>Platform responds</li>
        </ul>

        <h4>(d) Post-Inspection:</h4>

        <h5>Inspection Report:</h5>
        <ul className="legal-list">
          <li>Findings, deficiencies, breaches</li>
        </ul>

        <h5>Platform Response:</h5>
        <ul className="legal-list">
          <li>Detailed written response in 15–30 days</li>
          <li>Evidence of remediation</li>
        </ul>

        <h5>Follow-Up:</h5>
        <ul className="legal-list">
          <li>Progress reports</li>
          <li>Possible follow-up inspection</li>
          <li>Enforcement actions for serious violations</li>
        </ul>

        <h3>21.2.3 Common Inspection Focus Areas</h3>

        <h4>(a) Governance:</h4>
        <ul className="legal-list">
          <li>Board involvement</li>
          <li>DD/PO functioning</li>
        </ul>

        <h4>(b) Risk Assessment:</h4>
        <ul className="legal-list">
          <li>Comprehensiveness and adequacy</li>
        </ul>

        <h4>(c) CDD:</h4>
        <ul className="legal-list">
          <li>Quality, BO, EDD, PEP checks</li>
        </ul>

        <h4>(d) Transaction Monitoring:</h4>
        <ul className="legal-list">
          <li>Configuration, alert handling</li>
        </ul>

        <h4>(e) STR Quality:</h4>
        <ul className="legal-list">
          <li>Completeness and timeliness</li>
        </ul>

        <h4>(f) Record Keeping:</h4>
        <ul className="legal-list">
          <li>Retention, accessibility, security</li>
        </ul>

        <h4>(g) Training:</h4>
        <ul className="legal-list">
          <li>Coverage and effectiveness</li>
        </ul>

        <h4>(h) Independent Testing:</h4>
        <ul className="legal-list">
          <li>Frequency, quality, remediation</li>
        </ul>

        <h3>21.2.4 Managing Inspection Outcomes</h3>

        <h4>(a) No Findings:</h4>
        <ul className="legal-list">
          <li>Continue strong compliance culture</li>
        </ul>

        <h4>(b) Minor Findings:</h4>
        <ul className="legal-list">
          <li>Prompt remediation</li>
        </ul>

        <h4>(c) Significant Findings:</h4>
        <ul className="legal-list">
          <li>Board involvement, detailed remediation plan</li>
        </ul>

        <h4>(d) Enforcement Action:</h4>
        <ul className="legal-list">
          <li>Fines/sanctions</li>
          <li>Immediate remediation</li>
          <li>Legal strategy</li>
        </ul>
      </section>

      {/* 21.3 Coordination Between Audits */}
      <section id="coordination-audits" className="section">
        <div className="section-header">
          <span className="section-number">21.3</span>
          <h2 className="section-title">Coordination Between Internal and External Audits</h2>
        </div>

        <h3>21.3.1 Information Sharing</h3>
        <ul className="legal-list">
          <li>Internal audit reports shared with external auditors</li>
          <li>External audit findings tracked by internal audit</li>
        </ul>

        <h3>21.3.2 Joint Planning</h3>
        <ul className="legal-list">
          <li>Avoid operational burden</li>
          <li>Cover gaps and overlaps</li>
          <li>Leverage each other's work</li>
        </ul>

        <h3>21.3.3 Unified Remediation Tracking</h3>
        <ul className="legal-list">
          <li>All findings tracked in one system</li>
          <li>Unified reporting to Audit Committee</li>
        </ul>
      </section>

      {/* 22. Remediation and CAP */}
      <section id="remediation" className="section">
        <div className="section-header">
          <span className="section-number">22</span>
          <h2 className="section-title">REMEDIATION AND CORRECTIVE ACTION PLANS</h2>
        </div>

        <h3>22.1 Remediation Framework</h3>

        <h4>22.1.1 Purpose of Remediation</h4>
        <ul className="legal-list">
          <li>Correct deficiencies</li>
          <li>Prevent recurrence</li>
          <li>Enhance AML/KYC effectiveness</li>
          <li>Demonstrate accountability</li>
          <li>Meet regulatory expectations</li>
        </ul>

        <h4>22.1.2 Principles of Effective Remediation</h4>

        <h5>(a) Root Cause Focus:</h5>
        <ul className="legal-list">
          <li>Address causes, not symptoms</li>
        </ul>

        <h5>(b) Prioritization:</h5>
        <ul className="legal-list">
          <li>Critical/high findings first</li>
        </ul>

        <h5>(c) Accountability:</h5>
        <ul className="legal-list">
          <li>Clear ownership</li>
          <li>Consequences for delays</li>
        </ul>

        <h5>(d) Measurable and Verifiable:</h5>
        <ul className="legal-list">
          <li>Specific, measurable actions</li>
          <li>Evidence of completion</li>
        </ul>

        <h5>(e) Sustainability:</h5>
        <ul className="legal-list">
          <li>Process/system improvements</li>
          <li>Long-term solutions</li>
        </ul>

        <h3>22.2 Corrective Action Plan (CAP) Development</h3>

        <h4>22.2.1 CAP Components</h4>

        <h5>(a) Finding Summary:</h5>
        <ul className="legal-list">
          <li>Description, risk rating, source</li>
        </ul>

        <h5>(b) Root Cause Analysis:</h5>
        <ul className="legal-list">
          <li>Underlying systemic causes</li>
        </ul>

        <h5>(c) Corrective Actions:</h5>

        <h6>Immediate Actions (Quick Wins):</h6>
        <ul className="legal-list">
          <li>Urgent risk mitigation</li>
        </ul>

        <h6>Short-Term Actions:</h6>
        <ul className="legal-list">
          <li>30–90 day targeted fixes</li>
        </ul>

        <h6>Long-Term/Systemic Actions:</h6>
        <ul className="legal-list">
          <li>90–180+ day structural improvements</li>
        </ul>

        <h5>(d) Responsible Party:</h5>
        <ul className="legal-list">
          <li>Accountable owner name/title</li>
        </ul>

        <h5>(e) Target Completion Date:</h5>
        <ul className="legal-list">
          <li>Urgent but realistic timelines</li>
        </ul>

        <h5>(f) Resources Required:</h5>
        <ul className="legal-list">
          <li>Budget, personnel, technology, consultants</li>
        </ul>

        <h5>(g) Success Metrics:</h5>
        <ul className="legal-list">
          <li>Measurable indicators of completion</li>
        </ul>

        <h5>(h) Validation Method:</h5>
        <ul className="legal-list">
          <li>Evidence, testing, and reviewer identification</li>
        </ul>

      </section>

    </div>
  );
}

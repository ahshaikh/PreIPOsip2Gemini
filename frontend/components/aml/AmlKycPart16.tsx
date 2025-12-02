// components/aml/AmlKycPart16.tsx
'use client';

import React from "react";

export default function AmlKycPart16() {
  return (
    <div>

      {/* 23.2.5 Record Keeping and DMS */}
      <section id="dms" className="section">
        <div className="section-header">
          <span className="section-number">23.2.5</span>
          <h2 className="section-title">Record Keeping and Document Management System (DMS)</h2>
        </div>

        <p><strong>Purpose:</strong> Secure storage, retrieval, and retention management of all AML/KYC records.</p>

        <h4>(a) Centralized Repository:</h4>
        <ul className="legal-list">
          <li>All KYC documents, transaction records, compliance files</li>
          <li>Hierarchical folder structure</li>
          <li>Metadata tagging for easy search</li>
        </ul>

        <h4>(b) Version Control:</h4>
        <ul className="legal-list">
          <li>Document versions tracked</li>
          <li>Audit trail of changes</li>
          <li>Ability to retrieve historical versions</li>
        </ul>

        <h4>(c) Retention Management:</h4>
        <ul className="legal-list">
          <li>Automated retention period calculation (10 years from relationship end)</li>
          <li>Alerts for records eligible for destruction</li>
          <li>Secure deletion after retention period</li>
        </ul>

        <h4>(d) Search and Retrieval:</h4>
        <ul className="legal-list">
          <li>Full-text search</li>
          <li>Metadata search (customer name, PAN, date range, document type)</li>
          <li>Advanced search with filters</li>
          <li>Batch retrieval for regulatory requests</li>
        </ul>

        <h4>(e) Access Controls:</h4>
        <ul className="legal-list">
          <li>Role-based access</li>
          <li>Granular permissions (view only, edit, delete)</li>
          <li>Audit logs of all access and actions</li>
        </ul>

        <h4>(f) Encryption:</h4>
        <ul className="legal-list">
          <li>At-rest encryption</li>
          <li>In-transit encryption</li>
          <li>Encryption key management</li>
        </ul>

        <h4>(g) Backup and Recovery:</h4>
        <ul className="legal-list">
          <li>Regular automated backups</li>
          <li>Geographically distributed backups</li>
          <li>Tested recovery procedures</li>
          <li>RTO and RPO defined</li>
        </ul>

        <h4>(h) Integration:</h4>
        <ul className="legal-list">
          <li>API access for other systems</li>
          <li>Email ingestion</li>
          <li>Scanner integration</li>
        </ul>

        <h4>Implementation Options:</h4>
        <ul className="legal-list">
          <li>Enterprise DMS (SharePoint, OpenText, Documentum)</li>
          <li>Cloud DMS (Box, Dropbox Business)</li>
          <li>Specialized compliance DMS</li>
          <li>Custom-built solutions</li>
        </ul>
      </section>

      {/* 23.2.6 e-KYC Systems */}
      <section id="e-kyc" className="section">
        <div className="section-header">
          <span className="section-number">23.2.6</span>
          <h2 className="section-title">e-KYC and Digital Verification Systems</h2>
        </div>

        <p><strong>Purpose:</strong> Enable remote customer onboarding with digital identity verification.</p>

        <h4>(a) Aadhaar-based e-KYC:</h4>
        <ul className="legal-list">
          <li>UIDAI API integration</li>
          <li>OTP authentication</li>
          <li>Demographic data fetch</li>
          <li>Aadhaar masking compliance</li>
          <li>Virtual ID support</li>
          <li>Consent capture and management</li>
        </ul>

        <h4>(b) Video KYC (V-CIP):</h4>
        <ul className="legal-list">
          <li>Live HD video</li>
          <li>Screen recording</li>
          <li>Liveness detection</li>
          <li>Geo-tagging</li>
          <li>Document display</li>
          <li>Timestamp + digital signature</li>
          <li>Secure storage (10 years)</li>
        </ul>

        <h4>(c) DigiLocker Integration:</h4>
        <ul className="legal-list">
          <li>Document fetch via API</li>
          <li>Authenticity verification</li>
          <li>Consent-based access</li>
        </ul>

        <h4>(d) PAN Verification:</h4>
        <ul className="legal-list">
          <li>Real-time PAN verification</li>
          <li>Name match</li>
          <li>Status check</li>
        </ul>

        <h4>(e) Bank Account Verification:</h4>
        <ul className="legal-list">
          <li>Penny drop verification</li>
          <li>Name match</li>
          <li>Account status</li>
        </ul>

        <h4>(f) CKYC Integration:</h4>
        <ul className="legal-list">
          <li>Search existing CKYC</li>
          <li>Download/upload KYC</li>
          <li>Compliance with CKYC norms</li>
        </ul>

        <h4>(g) OCR and Data Extraction:</h4>
        <ul className="legal-list">
          <li>Automated data extraction</li>
          <li>Reduced errors</li>
          <li>Pre-filled KYC forms</li>
        </ul>

        <h4>(h) Biometric Verification (Optional):</h4>
        <ul className="legal-list">
          <li>Fingerprint / iris scan</li>
          <li>Enhanced security</li>
        </ul>

        <h4>Vendor Options:</h4>
        <ul className="legal-list">
          <li>NSDL e-Gov</li>
          <li>Signzy, HyperVerge</li>
          <li>IDfy, AuthBridge</li>
          <li>Karza Technologies</li>
        </ul>
      </section>

      {/* 23.3 Integration and Data Flow */}
      <section id="integration-flow" className="section">
        <div className="section-header">
          <span className="section-number">23.3</span>
          <h2 className="section-title">System Integration and Data Flow</h2>
        </div>

        <h3>23.3.1 Integration Architecture</h3>

        <p><strong>Seamless integration essential.</strong></p>

        <div className="callout">
          <p><strong>Onboarding Flow:</strong></p>
          <p>Customer → KYC System → e-KYC → Screening → Risk scoring → Approval → Activation</p>
        </div>

        <div className="callout">
          <p><strong>Transaction Flow:</strong></p>
          <p>Transaction → TMS → Rule engine → Alert → Investigation → STR → Complete/block</p>
        </div>

        <div className="callout">
          <p><strong>Periodic Review:</strong></p>
          <p>Scheduler → KYC Refresh → Re-verification → Re-screening → Updated profile</p>
        </div>

        <h4>Integration Methods:</h4>
        <ul className="legal-list">
          <li>APIs (preferred)</li>
          <li>Secure file transfer (CSV/XML)</li>
          <li>Database-level integration</li>
          <li>Middleware / ESB</li>
        </ul>

        <h3>23.3.2 Data Governance</h3>

        <h4>(a) Master Data Management:</h4>
        <ul className="legal-list">
          <li>Single source of truth</li>
          <li>Standardized definitions</li>
          <li>Validation rules</li>
          <li>Deduplication</li>
        </ul>

        <h4>(b) Data Lineage:</h4>
        <ul className="legal-list">
          <li>Track data origin</li>
          <li>Audit transformations</li>
        </ul>

        <h4>(c) Data Quality:</h4>
        <ul className="legal-list">
          <li>DQ checks</li>
          <li>Exception reporting</li>
          <li>Regular cleansing</li>
        </ul>

        <h3>23.3.3 API Security</h3>

        <ul className="legal-list">
          <li>Authentication</li>
          <li>Authorization</li>
          <li>Encryption</li>
          <li>Rate limiting</li>
          <li>Logging</li>
          <li>Monitoring</li>
        </ul>
      </section>

      {/* 23.4 System Testing */}
      <section id="system-testing" className="section">
        <div className="section-header">
          <span className="section-number">23.4</span>
          <h2 className="section-title">System Testing and Validation</h2>
        </div>

        <h3>23.4.1 User Acceptance Testing (UAT)</h3>

        <h4>(a) Test Planning:</h4>
        <ul className="legal-list">
          <li>Functional scenarios</li>
          <li>Exception tests</li>
          <li>Edge cases</li>
        </ul>

        <h4>(b) Test Execution:</h4>
        <ul className="legal-list">
          <li>Compliance + end users</li>
          <li>Systematic execution</li>
          <li>Defect logs</li>
        </ul>

        <h4>(c) Defect Resolution:</h4>
        <ul className="legal-list">
          <li>Vendor fixes</li>
          <li>Retesting</li>
        </ul>

        <h4>(d) Sign-Off:</h4>
        <ul className="legal-list">
          <li>Compliance + IT approval</li>
          <li>DD + PO approval</li>
        </ul>

        <h3>23.4.2 Parallel Run</h3>

        <ul className="legal-list">
          <li>New vs old system comparison</li>
          <li>1–3 months typical</li>
          <li>Cutover after accuracy confirmed</li>
        </ul>

        <h3>23.4.3 Ongoing Validation</h3>

        <h4>(a) TMS Validation:</h4>
        <ul className="legal-list">
          <li>Annual validation</li>
          <li>Inject test transactions</li>
          <li>Alert accuracy testing</li>
        </ul>

        <h4>(b) Screening System Validation:</h4>
        <ul className="legal-list">
          <li>Known match tests</li>
          <li>False positive analysis</li>
          <li>Coverage verification</li>
        </ul>

        <h4>(c) System Audits:</h4>
        <ul className="legal-list">
          <li>IT audits</li>
          <li>Application audits</li>
        </ul>
      </section>

      {/* 23.5 Maintenance */}
      <section id="system-maintenance" className="section">
        <div className="section-header">
          <span className="section-number">23.5</span>
          <h2 className="section-title">System Maintenance and Upgrades</h2>
        </div>

        <h3>23.5.1 Regular Maintenance</h3>

        <h4>(a) Software Updates:</h4>
        <ul className="legal-list">
          <li>Patches</li>
          <li>Security updates</li>
          <li>Feature updates</li>
        </ul>

        <h4>(b) Database Maintenance:</h4>
        <ul className="legal-list">
          <li>Backups</li>
          <li>Index optimization</li>
          <li>Archival</li>
        </ul>

        <h4>(c) Performance Monitoring:</h4>
        <ul className="legal-list">
          <li>Response time</li>
          <li>Uptime</li>
          <li>Error rates</li>
          <li>Capacity planning</li>
        </ul>

        <h3>23.5.2 Change Management</h3>

        <h4>(a) Change Request:</h4>
        <ul className="legal-list">
          <li>Formal documentation</li>
          <li>Business + compliance approval</li>
        </ul>

        <h4>(b) Impact Assessment:</h4>
        <ul className="legal-list">
          <li>Technical analysis</li>
          <li>Compliance analysis</li>
        </ul>

        <h4>(c) Testing:</h4>
        <ul className="legal-list">
          <li>Dev/test environment</li>
          <li>UAT</li>
          <li>Regression</li>
        </ul>

        <h4>(d) Documentation:</h4>
        <ul className="legal-list">
          <li>Update manuals</li>
          <li>Update SOPs</li>
        </ul>

        <h4>(e) Deployment:</h4>
        <ul className="legal-list">
          <li>Maintenance window</li>
          <li>Rollback plan</li>
          <li>Post-deployment verification</li>
        </ul>

        <h4>(f) Communication:</h4>
        <ul className="legal-list">
          <li>User notifications</li>
          <li>Training</li>
        </ul>

        <h3>23.5.3 Version Control</h3>

        <ul className="legal-list">
          <li>Version-controlled configs</li>
          <li>Rollback capability</li>
          <li>Change logs</li>
        </ul>
      </section>

      {/* 23.6 Emerging Technologies */}
      <section id="emerging-tech" className="section">
        <div className="section-header">
          <span className="section-number">23.6</span>
          <h2 className="section-title">Emerging Technologies in AML/KYC</h2>
        </div>

        <h3>23.6.1 Artificial Intelligence / ML</h3>

        <h4>(a) Transaction Monitoring:</h4>
        <ul className="legal-list">
          <li>Anomaly detection</li>
          <li>Self-learning models</li>
          <li>Reduced false positives</li>
          <li>Network analysis</li>
        </ul>

        <h4>(b) Document Verification:</h4>
        <ul className="legal-list">
          <li>Forgery detection</li>
          <li>Deepfake detection</li>
          <li>Quality assessment</li>
        </ul>

        <h4>(c) Name Matching:</h4>
        <ul className="legal-list">
          <li>NLP based matching</li>
          <li>Context-aware</li>
        </ul>

        <h4>(d) Predictive Analytics:</h4>
        <ul className="legal-list">
          <li>Suspicious behavior prediction</li>
        </ul>

        <h4>(e) Investigation Assistance:</h4>
        <ul className="legal-list">
          <li>Automated evidence gathering</li>
          <li>Pattern summarization</li>
        </ul>

        <h4>AI Implementation Considerations:</h4>
        <ul className="legal-list">
          <li>Regulatory acceptance</li>
          <li>Explainability</li>
          <li>Data quality</li>
          <li>Human oversight</li>
        </ul>

        <h3>23.6.2 Blockchain / DLT</h3>

        <h4>Potential Uses:</h4>
        <ul className="legal-list">
          <li>Shared KYC</li>
          <li>Transaction traceability</li>
          <li>Automated compliance</li>
        </ul>

        <h4>Status:</h4>
        <ul className="legal-list">
          <li>Pilot stage</li>
          <li>Regulatory evolution</li>
        </ul>

        <h3>23.6.3 RPA</h3>

        <ul className="legal-list">
          <li>Data entry automation</li>
          <li>Report generation</li>
          <li>Investigation assistance</li>
          <li>KYC refresh automation</li>
        </ul>

        <h3>23.6.4 Biometric Technologies</h3>

        <ul className="legal-list">
          <li>Fingerprint, facial recognition, iris</li>
          <li>Liveness detection</li>
          <li>Enhanced security</li>
        </ul>

        <h3>23.6.5 Cloud Computing</h3>

        <h4>Benefits:</h4>
        <ul className="legal-list">
          <li>Scalability</li>
          <li>Cost-effectiveness</li>
          <li>Recovery</li>
          <li>Latest technology</li>
        </ul>

        <h4>Considerations:</h4>
        <ul className="legal-list">
          <li>Data residency (India)</li>
          <li>Security</li>
          <li>Vendor lock-in</li>
          <li>Regulatory compliance</li>
        </ul>

        <h4>Platform Approach:</h4>
        <ul className="legal-list">
          <li>Evaluate non-critical systems first</li>
          <li>Ensure localization compliance</li>
          <li>Prefer Indian data centers</li>
          <li>Strong SLAs</li>
        </ul>
      </section>

      {/* 24. BCP & DR */}
      <section id="bcp-dr" className="section">
        <div className="section-header">
          <span className="section-number">24</span>
          <h2 className="section-title">BUSINESS CONTINUITY AND DISASTER RECOVERY FOR AML/KYC SYSTEMS</h2>
        </div>

        <h3>24.1 Business Continuity Planning (BCP)</h3>

        <h4>24.1.1 Objectives</h4>
        <ul className="legal-list">
          <li>Natural disasters</li>
          <li>Technology failures</li>
          <li>Infrastructure failures</li>
          <li>Pandemics</li>
          <li>Terrorist attacks</li>
          <li>Vendor failures</li>
        </ul>

        <h4>24.1.2 Regulatory Expectations</h4>
        <ul className="legal-list">
          <li>SEBI BCP requirements</li>
          <li>Demonstrated capability</li>
          <li>Tested and updated plans</li>
        </ul>

        <h4>24.1.3 Business Impact Analysis</h4>

        <div className="definition-table">
          <div className="dt-row">
            <div className="dt-cell"><strong>Critical (RTO &le; 4 hr)</strong></div>
            <div className="dt-cell">
              Transaction monitoring, screening, KYC data access, STR filing
            </div>
          </div>
          <div className="dt-row">
            <div className="dt-cell"><strong>Important (RTO 24 hr)</strong></div>
            <div className="dt-cell">Onboarding, alerts, KYC refresh</div>
          </div>
          <div className="dt-row">
            <div className="dt-cell"><strong>Standard (RTO 72 hr)</strong></div>
            <div className="dt-cell">Reports, training, audit</div>
          </div>
        </div>

        <p><strong>RPO:</strong> 1 hour or less; no data loss acceptable.</p>

        <h3>24.2 Disaster Recovery (DR)</h3>

        <h4>24.2.1 DR Strategy</h4>

        <h5>(a) Data Backup:</h5>
        <ul className="legal-list">
          <li>Continuous replication</li>
          <li>Hourly incrementals</li>
          <li>Daily full backups</li>
          <li>On-site + off-site + cloud</li>
          <li>Backup testing: monthly/quarterly/annual</li>
        </ul>

        <h5>(b) System Redundancy:</h5>
        <ul className="legal-list">
          <li><strong>Hot Site:</strong> Real-time failover</li>
          <li><strong>Warm Site:</strong> Hours</li>
          <li><strong>Cold Site:</strong> Slow but cheap</li>
        </ul>

        <h5>(c) High Availability:</h5>
        <ul className="legal-list">
          <li>Load balancing</li>
          <li>DB clustering</li>
          <li>Redundant networks</li>
          <li>UPS & power backup</li>
        </ul>

        <h4>24.2.2 DR Procedures</h4>

        <ul className="legal-list">
          <li>Disaster declaration</li>
          <li>DR activation</li>
          <li>Failover</li>
          <li>Verification</li>
          <li>Communication</li>
          <li>Failback</li>
        </ul>

        <h4>24.2.3 DR Testing</h4>

        <h5>(a) Types:</h5>
        <ul className="legal-list">
          <li>Tabletop (quarterly)</li>
          <li>Simulation (semi-annually)</li>
          <li>Live drill (annually)</li>
        </ul>

        <h5>(b) Documentation:</h5>
        <ul className="legal-list">
          <li>Test plan</li>
          <li>Execution log</li>
          <li>Issues</li>
          <li>RTO/RPO results</li>
          <li>Lessons learned</li>
        </ul>

      </section>

    </div>
  );
}

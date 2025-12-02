// components/aml/AmlKycPart10.tsx
'use client';

import React from "react";

export default function AmlKycPart10() {
  return (
    <div>

      {/* 17. TRAINING AND AWARENESS PROGRAMS */}
      <section id="training-awareness" className="section">
        <div className="section-header">
          <span className="section-number">17</span>
          <h2 className="section-title">TRAINING AND AWARENESS PROGRAMS</h2>
        </div>

        {/* 17.1 Training Framework and Objectives */}
        <h3>17.1 Training Framework and Objectives</h3>

        <h4>17.1.1 Statutory Mandate</h4>
        <p>
          Rule 4 of the Prevention of Money Laundering (Maintenance of Records) Rules, 2005 requires that:
        </p>

        <div className="callout">
          <p>
            “Every reporting entity shall evolve an ongoing employee training programme so that the members
            of the staff are adequately trained in AML and KYC procedures.”
          </p>
        </div>

        <p>Additionally, SEBI circulars mandate regular training for intermediaries on AML/KYC compliance.</p>

        <h4>17.1.2 Training Objectives</h4>
        <p>The Platform's training program aims to:</p>

        <ul className="legal-list">
          <li>(a) Create Awareness: Ensure all Personnel understand money laundering and terrorist financing risks specific to Pre-IPO securities market;</li>
          <li>(b) Build Competence: Equip Personnel with knowledge and skills to implement AML/KYC procedures effectively;</li>
          <li>(c) Foster Compliance Culture: Develop organizational culture where compliance is valued and prioritized;</li>
          <li>(d) Update Knowledge: Keep Personnel informed of regulatory changes, emerging typologies, and evolving best practices;</li>
          <li>(e) Ensure Accountability: Make Personnel aware of their responsibilities and consequences of non-compliance;</li>
          <li>(f) Reduce Operational Risk: Minimize errors, oversights, and deliberate violations through education;</li>
          <li>(g) Protect Platform: Build robust defense against regulatory scrutiny and potential penalties.</li>
        </ul>

        <h4>17.1.3 Training Principles</h4>
        <ul className="legal-list">
          <li>(a) Universal Participation: All Personnel without exception must undergo AML/KYC training</li>
          <li>(b) Role-Based Customization: Training intensity and content calibrated to job responsibilities</li>
          <li>(c) Continuous Learning: Training is ongoing, not one-time event</li>
          <li>(d) Practical Focus: Emphasis on real-world scenarios, case studies, and practical application</li>
          <li>(e) Assessment-Based: Learning verified through testing and evaluation</li>
          <li>(f) Documentation: Complete records of all training activities maintained</li>
          <li>(g) Regular Refresh: Annual refresher training mandatory for all Personnel</li>
        </ul>

        {/* 17.2 Training Categories */}
        <h3>17.2 Training Categories and Target Audience</h3>

        {/* 17.2.1 Induction Training */}
        <h4>17.2.1 Category 1: Induction Training (New Joiners)</h4>

        <div className="definition-grid">
          <div><strong>Target Audience:</strong></div>
          <div>All new employees, officers, consultants, agents joining the Platform</div>

          <div><strong>Timing:</strong></div>
          <div>Within 30 days of joining (before handling Customer data or transactions)</div>

          <div><strong>Duration:</strong></div>
          <div>Minimum 4 hours</div>
        </div>

        <h5>Content:</h5>
        <ul className="legal-list">
          <li>Introduction to money laundering and terrorist financing</li>
          <li>Pre-IPO market vulnerabilities and specific risks</li>
          <li>Overview of PMLA, PML Rules, and regulatory framework</li>
          <li>Platform's AML/KYC Policy - key provisions</li>
          <li>Customer identification and verification procedures</li>
          <li>Documentation requirements</li>
          <li>Red flag indicators</li>
          <li>Reporting suspicious activities internally</li>
          <li>Confidentiality and anti-tipping off obligations</li>
          <li>Consequences of non-compliance</li>
          <li>Role-specific responsibilities</li>
        </ul>

        <h5>Delivery Method:</h5>
        <ul className="legal-list">
          <li>Instructor-led classroom/virtual session</li>
          <li>Self-paced e-learning modules</li>
          <li>Combination approach</li>
        </ul>

        <h5>Assessment:</h5>
        <ul className="legal-list">
          <li>Post-training assessment test (minimum 80% passing score)</li>
          <li>Certificate issued upon successful completion</li>
          <li>Re-training if assessment failed</li>
        </ul>

        {/* 17.2.2 Annual Refresher */}
        <h4>17.2.2 Category 2: Annual Refresher Training (All Personnel)</h4>

        <div className="definition-grid">
          <div><strong>Target Audience:</strong></div>
          <div>All Personnel who completed induction training</div>

          <div><strong>Timing:</strong></div>
          <div>Annually (within 12 months of previous training)</div>

          <div><strong>Duration:</strong></div>
          <div>Minimum 2–3 hours</div>
        </div>

        <h5>Content:</h5>
        <ul className="legal-list">
          <li>Regulatory updates in past year</li>
          <li>Case studies of money laundering in Pre-IPO/unlisted securities</li>
          <li>Emerging typologies and new red flags</li>
          <li>FIU-IND advisories and circulars</li>
          <li>Platform policy updates</li>
          <li>Review of key concepts and procedures</li>
          <li>Common errors and lessons learned</li>
          <li>Refresher on confidentiality and reporting obligations</li>
        </ul>

        <h5>Delivery Method:</h5>
        <ul className="legal-list">
          <li>Virtual/in-person workshops</li>
          <li>E-learning modules</li>
          <li>Webinars</li>
        </ul>

        <h5>Assessment:</h5>
        <ul className="legal-list">
          <li>Post-training quiz</li>
          <li>Participation certificate</li>
          <li>Attendance mandatory and monitored</li>
        </ul>

        {/* 17.2.3 Role-Specific Advanced Training */}
        <h4>17.2.3 Category 3: Role-Specific Advanced Training</h4>

        <h5>(a) Compliance Team</h5>
        <ul className="legal-list">
          <li>Advanced customer due diligence techniques</li>
          <li>Complex beneficial ownership structures</li>
          <li>In-depth transaction monitoring and investigation</li>
          <li>STR/CTR preparation and filing</li>
          <li>FINnet reporting</li>
          <li>Regulatory interaction</li>
          <li>Legal framework deep-dive</li>
        </ul>

        <h5>(b) Customer-Facing Staff</h5>
        <ul className="legal-list">
          <li>Customer interaction practices</li>
          <li>Identifying red flags during onboarding</li>
          <li>Document verification techniques</li>
          <li>Handling customer situations</li>
          <li>Escalation procedures</li>
        </ul>

        <h5>(c) Transaction Processing & Operations</h5>
        <ul className="legal-list">
          <li>Transaction scrutiny and validation</li>
          <li>Payment verification procedures</li>
          <li>System alerts handling</li>
          <li>Documentation</li>
        </ul>

        <h5>(d) Senior Management</h5>
        <ul className="legal-list">
          <li>Regulatory developments</li>
          <li>Risk assessment</li>
          <li>Governance responsibilities</li>
          <li>Case studies of regulatory actions</li>
        </ul>

        <h5>(e) IT & Technology Team</h5>
        <ul className="legal-list">
          <li>Monitoring systems</li>
          <li>Data security & encryption</li>
          <li>Audit trails & logging</li>
          <li>CYBER + AML intersection</li>
        </ul>

        <h5>(f) Legal & Risk Teams</h5>
        <ul className="legal-list">
          <li>Detailed legal framework</li>
          <li>Handling regulatory inspections</li>
          <li>Drafting policies</li>
        </ul>

        {/* 17.2.4 Specialized Training */}
        <h4>17.2.4 Category 4: Specialized Training on Emerging Topics</h4>

        <p>Topics may include:</p>
        <ul className="legal-list">
          <li>New regulatory requirements</li>
          <li>Emerging typologies</li>
          <li>Cryptocurrency-linked risks</li>
          <li>AI-based transaction monitoring</li>
          <li>FIU-IND red alerts</li>
          <li>Training after compliance incidents</li>
        </ul>

        {/* 17.3 Training Content */}
        <h3>17.3 Training Content and Curriculum</h3>

        <h4>17.3.1 Module 1: Understanding Money Laundering & Terrorist Financing</h4>

        <h5>(a) What is Money Laundering:</h5>
        <ul className="legal-list">
          <li>Definition under PMLA Section 3</li>
          <li>Proceeds of crime</li>
          <li>Three stages: Placement, Layering, Integration</li>
          <li>Case studies</li>
        </ul>

        <h5>(b) What is Terrorist Financing:</h5>
        <ul className="legal-list">
          <li>Legal framework</li>
          <li>Sources and channels</li>
          <li>International obligations</li>
        </ul>

        <h5>(c) Impact and Consequences:</h5>
        <ul className="legal-list">
          <li>Economic impact</li>
          <li>Social impact</li>
          <li>Reputational damage</li>
          <li>Legal penalties</li>
        </ul>

        {/* Module 2 */}
        <h4>17.3.2 Module 2: Regulatory Framework</h4>

        <ul className="legal-list">
          <li>PMLA provisions</li>
          <li>SEBI regulations</li>
          <li>FIU-IND role</li>
          <li>Companies Act</li>
          <li>Income Tax Act</li>
          <li>FEMA</li>
          <li>IT Act</li>
        </ul>

        {/* Module 3 */}
        <h4>17.3.3 Module 3: Pre-IPO Market Specific Risks</h4>
        <ul className="legal-list">
          <li>Market characteristics</li>
          <li>ML vulnerabilities</li>
          <li>Typologies & case studies</li>
        </ul>

        {/* Module 4 */}
        <h4>17.3.4 Module 4: Customer Due Diligence Procedures</h4>
        <ul className="legal-list">
          <li>Customer Identification Program</li>
          <li>CDD/EDD</li>
          <li>Beneficial ownership</li>
          <li>Practical exercises</li>
        </ul>

        {/* Module 5 */}
        <h4>17.3.5 Module 5: Transaction Monitoring and Red Flags</h4>
        <ul className="legal-list">
          <li>Monitoring purpose</li>
          <li>Automated/manual monitoring</li>
          <li>Red flags</li>
          <li>Investigation techniques</li>
        </ul>

        {/* Module 6 */}
        <h4>17.3.6 Module 6: Suspicious Transaction Reporting</h4>
        <ul className="legal-list">
          <li>What constitutes suspicion</li>
          <li>STR process</li>
          <li>Anti-tipping off</li>
          <li>Other reports (CTR, CCR, CBWT)</li>
        </ul>

        {/* Module 7 */}
        <h4>17.3.7 Module 7: Record Keeping and Information Security</h4>
        <ul className="legal-list">
          <li>Record types</li>
          <li>Retention</li>
          <li>Security</li>
          <li>DPDP compliance</li>
        </ul>

        {/* Module 8 */}
        <h4>17.3.8 Module 8: Roles, Responsibilities, and Accountability</h4>
        <ul className="legal-list">
          <li>Organizational structure</li>
          <li>Individual responsibilities</li>
          <li>Consequences of non-compliance</li>
          <li>Ethical considerations</li>
        </ul>

        {/* 17.4 Delivery Methods */}
        <h3>17.4 Training Delivery Methods</h3>

        <h4>17.4.1 Instructor-Led Training (ILT)</h4>

        <h5>(a) Classroom Training:</h5>
        <ul className="legal-list">
          <li>In-person sessions</li>
          <li>Interactive lectures and Q&A</li>
          <li>Max 25 participants</li>
        </ul>

        <h5>(b) Virtual Instructor-Led Training (VILT):</h5>
        <ul className="legal-list">
          <li>Live online sessions</li>
          <li>Interactive tools (polls, chat)</li>
          <li>Recordings available</li>
        </ul>

        <h5>Advantages:</h5>
        <ul className="legal-list">
          <li>Real-time interaction</li>
          <li>Peer learning</li>
          <li>Immediate feedback</li>
        </ul>

      </section>

    </div>
  );
}

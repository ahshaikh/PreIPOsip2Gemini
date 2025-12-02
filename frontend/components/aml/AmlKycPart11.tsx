// components/aml/AmlKycPart11.tsx
'use client';

import React from "react";

export default function AmlKycPart11() {
  return (
    <div>

      {/* 17.4.2 E-Learning */}
      <section id="elearning" className="section">
        <div className="section-header">
          <span className="section-number">17.4.2</span>
          <h2 className="section-title">E-Learning</h2>
        </div>

        <h3>(a) Self-Paced Online Modules:</h3>
        <ul className="legal-list">
          <li>Web-based courses accessible 24x7</li>
          <li>Multimedia content (videos, animations, simulations)</li>
          <li>Suitable for foundational content and refreshers</li>
          <li>Progress tracking and analytics</li>
        </ul>

        <h3>(b) Learning Management System (LMS):</h3>
        <ul className="legal-list">
          <li>Centralized platform for training content</li>
          <li>User enrollment and tracking</li>
          <li>Assessment and certification</li>
          <li>Reporting and compliance monitoring</li>
        </ul>

        <h3>Advantages:</h3>
        <ul className="legal-list">
          <li>Flexibility for learners</li>
          <li>Cost-effective for large audience</li>
          <li>Consistent content delivery</li>
          <li>Easy updates and version control</li>
        </ul>
      </section>

      {/* 17.4.3 Blended Learning */}
      <section id="blended-learning" className="section">
        <div className="section-header">
          <span className="section-number">17.4.3</span>
          <h2 className="section-title">Blended Learning</h2>
        </div>

        <p>Combination of ILT and e-learning:</p>
        <ul className="legal-list">
          <li>E-learning for foundational concepts</li>
          <li>ILT for advanced topics, discussions, case studies</li>
          <li>Maximizes benefits of both methods</li>
        </ul>
      </section>

      {/* 17.4.4 On-the-Job Training and Mentoring */}
      <section id="onjob-training" className="section">
        <div className="section-header">
          <span className="section-number">17.4.4</span>
          <h2 className="section-title">On-the-Job Training and Mentoring</h2>
        </div>

        <h3>(a) Job Shadowing:</h3>
        <ul className="legal-list">
          <li>New compliance officers shadow experienced staff</li>
          <li>Observe real KYC reviews, investigations, STR preparation</li>
        </ul>

        <h3>(b) Mentoring:</h3>
        <ul className="legal-list">
          <li>Experienced compliance professionals mentor juniors</li>
          <li>Regular check-ins and guidance</li>
          <li>Knowledge transfer</li>
        </ul>

        <h3>(c) Practical Assignments:</h3>
        <ul className="legal-list">
          <li>Hands-on tasks under supervision</li>
          <li>Gradual increase in complexity and independence</li>
        </ul>
      </section>

      {/* 17.4.5 Workshops and Seminars */}
      <section id="workshops" className="section">
        <div className="section-header">
          <span className="section-number">17.4.5</span>
          <h2 className="section-title">Workshops and Seminars</h2>
        </div>

        <h3>(a) Internal Workshops:</h3>
        <ul className="legal-list">
          <li>Quarterly compliance workshops</li>
          <li>Theme-based (e.g., “Beneficial Ownership Complexities”)</li>
          <li>Cross-functional participation</li>
          <li>Case study discussions</li>
        </ul>

        <h3>(b) External Seminars:</h3>
        <ul className="legal-list">
          <li>Industry conferences and seminars</li>
          <li>Regulatory body organized training (SEBI, FIU-IND)</li>
          <li>Professional certification programs (CAMS, etc.)</li>
          <li>Networking with peers</li>
        </ul>
      </section>

      {/* 17.5 Training Assessment and Certification */}
      <section id="training-assessment" className="section">
        <div className="section-header">
          <span className="section-number">17.5</span>
          <h2 className="section-title">Training Assessment and Certification</h2>
        </div>

        <h3>17.5.1 Assessment Methods</h3>

        <h4>(a) Knowledge Tests:</h4>
        <ul className="legal-list">
          <li>Multiple choice questions</li>
          <li>True/False questions</li>
          <li>Case-based scenario questions</li>
          <li>Minimum passing score: 80%</li>
          <li>Immediate feedback on e-learning tests</li>
          <li>Proctored tests for critical roles</li>
        </ul>

        <h4>(b) Practical Assessments:</h4>
        <ul className="legal-list">
          <li>Document verification exercises</li>
          <li>Transaction review and red flag identification</li>
          <li>Mock STR preparation</li>
          <li>Role-play scenarios</li>
          <li>Supervisor evaluation</li>
        </ul>

        <h4>(c) Continuous Assessment:</h4>
        <ul className="legal-list">
          <li>Performance observations on-the-job</li>
          <li>Quality of KYC reviews conducted</li>
          <li>Accuracy of alert investigations</li>
          <li>Compliance with procedures</li>
        </ul>

        <h3>17.5.2 Certification</h3>

        <p>Upon successful completion:</p>
        <ul className="legal-list">
          <li>Certificate issued with employee name, training topic, date, score</li>
          <li>Certificate validity: 1 year</li>
          <li>Certificates maintained in Personnel files</li>
          <li>LMS records certificate issuance</li>
        </ul>

        <h3>17.5.3 Remediation for Failed Assessments</h3>

        <p>If employee fails assessment:</p>
        <ul className="legal-list">
          <li>Feedback on areas of weakness</li>
          <li>Additional study materials provided</li>
          <li>Re-training session</li>
          <li>Re-assessment within 15 days</li>
          <li>If repeated failure: Escalation to manager and HR</li>
          <li>May affect performance evaluation</li>
          <li>Cannot perform role functions until certified</li>
        </ul>
      </section>

      {/* 17.6 Training Documentation and Records */}
      <section id="training-records" className="section">
        <div className="section-header">
          <span className="section-number">17.6</span>
          <h2 className="section-title">Training Documentation and Records</h2>
        </div>

        <h3>17.6.1 Training Records Maintained</h3>

        <p>For each training session:</p>
        <ul className="legal-list">
          <li>Training date and duration</li>
          <li>Training topic/module</li>
          <li>Trainer/instructor name</li>
          <li>Participant list with signatures (for ILT)</li>
          <li>Attendance records (login records for e-learning)</li>
          <li>Training materials used</li>
          <li>Assessment scores</li>
          <li>Certificates issued</li>
          <li>Feedback from participants</li>
        </ul>

        <h3>17.6.2 Individual Training Records</h3>

        <ul className="legal-list">
          <li>Training history (all sessions attended)</li>
          <li>Dates and topics</li>
          <li>Assessment scores</li>
          <li>Certifications held</li>
          <li>Next scheduled training</li>
          <li>Gaps or overdue training flagged</li>
        </ul>

        <h3>17.6.3 Aggregate Training Reports</h3>

        <p>Quarterly and annual reports:</p>
        <ul className="legal-list">
          <li>Total training hours delivered</li>
          <li>Participation rate</li>
          <li>Compliance with mandatory training</li>
          <li>Average assessment scores</li>
          <li>Training effectiveness metrics</li>
          <li>Trends and insights</li>
          <li>Recommendations for improvement</li>
        </ul>

        <h3>17.6.4 Retention</h3>

        <ul className="legal-list">
          <li>Duration of Personnel employment + 5 years</li>
          <li>Minimum 10 years for compliance team</li>
          <li>Available for regulatory inspection</li>
        </ul>
      </section>

      {/* 17.7 Training Quality Assurance */}
      <section id="training-quality-assurance" className="section">
        <div className="section-header">
          <span className="section-number">17.7</span>
          <h2 className="section-title">Training Quality Assurance</h2>
        </div>

        <h3>17.7.1 Trainer Qualifications</h3>

        <h4>(a) Internal Trainers:</h4>
        <ul className="legal-list">
          <li>Compliance professionals with minimum 5 years experience</li>
          <li>Subject matter expertise</li>
          <li>Train-the-trainer certification</li>
          <li>Strong communication and presentation skills</li>
        </ul>

        <h4>(b) External Trainers:</h4>
        <ul className="legal-list">
          <li>CAMS, CFE, lawyers, CAs with AML specialization</li>
          <li>Industry experience</li>
          <li>References and credentials verified</li>
          <li>Contract specifying deliverables and quality standards</li>
        </ul>

        <h3>17.7.2 Training Content Review</h3>

        <h4>(a) Annual Content Update:</h4>
        <ul className="legal-list">
          <li>Review training materials annually</li>
          <li>Update for regulatory changes</li>
          <li>New case studies and typologies</li>
          <li>Remove outdated content</li>
          <li>Version control</li>
        </ul>

        <h4>(b) Approval Process:</h4>
        <ul className="legal-list">
          <li>Principal Officer approval</li>
          <li>Legal review</li>
          <li>Subject matter expert review</li>
        </ul>

        <h3>17.7.3 Training Effectiveness Evaluation</h3>

        <h4>(a) Kirkpatrick Model – Four Levels:</h4>

        <h5>Level 1 – Reaction:</h5>
        <ul className="legal-list">
          <li>Post-training feedback surveys</li>
          <li>Trainer effectiveness</li>
          <li>Logistics feedback</li>
        </ul>

        <h5>Level 2 – Learning:</h5>
        <ul className="legal-list">
          <li>Assessment test scores</li>
          <li>Pre-test vs post-test comparison</li>
          <li>Knowledge retention checks</li>
        </ul>

        <h5>Level 3 – Behavior:</h5>
        <ul className="legal-list">
          <li>On-the-job observation</li>
          <li>Quality of KYC reviews</li>
          <li>Reduction in errors</li>
          <li>Manager assessments</li>
        </ul>

        <h5>Level 4 – Results:</h5>
        <ul className="legal-list">
          <li>Reduction in compliance lapses</li>
          <li>Improved audit scores</li>
          <li>Reduced customer complaints</li>
          <li>Better STR quality</li>
          <li>No regulatory penalties</li>
        </ul>

        <h4>(b) Continuous Improvement:</h4>
        <ul className="legal-list">
          <li>Review effectiveness data</li>
          <li>Adjust content and frequency</li>
          <li>Address gaps</li>
          <li>Benchmark against industry</li>
        </ul>
      </section>

      {/* 17.8 Training Budget and Resources */}
      <section id="training-budget" className="section">
        <div className="section-header">
          <span className="section-number">17.8</span>
          <h2 className="section-title">Training Budget and Resources</h2>
        </div>

        <h3>17.8.1 Budget Allocation</h3>
        <ul className="legal-list">
          <li>Trainer fees</li>
          <li>LMS licensing</li>
          <li>Training materials</li>
          <li>E-learning content development</li>
          <li>Venue and logistics</li>
          <li>Certification fees</li>
          <li>Travel for external training</li>
          <li>Contingency (10–15%)</li>
        </ul>

        <h3>17.8.2 Resource Allocation</h3>
        <ul className="legal-list">
          <li>Training coordinator/team</li>
          <li>LMS administrator</li>
          <li>Training facilities</li>
          <li>Technology infrastructure</li>
          <li>Training during work hours</li>
        </ul>

      </section>

      {/* 18. HUMAN RESOURCES AND PERSONNEL MANAGEMENT */}
      <section id="hr-personnel" className="section">
        <div className="section-header">
          <span className="section-number">18</span>
          <h2 className="section-title">HUMAN RESOURCES AND PERSONNEL MANAGEMENT</h2>
        </div>

        <h3>18.1 Pre-Employment Screening</h3>

        <h4>18.1.1 Background Verification – All Personnel</h4>

        <p>Before onboarding any employee, officer, consultant, or agent:</p>

        <h5>(a) Identity Verification:</h5>
        <ul className="legal-list">
          <li>Verify PAN, Aadhaar, Passport, etc.</li>
          <li>Address verification</li>
          <li>Date of birth confirmation</li>
        </ul>

        <h5>(b) Educational Qualifications:</h5>
        <ul className="legal-list">
          <li>Verify degree/diploma certificates</li>
          <li>University verification for critical roles</li>
          <li>Verify CA/CFA/CAMS certifications</li>
        </ul>

        <h5>(c) Employment History:</h5>
        <ul className="legal-list">
          <li>Verify last two employers</li>
          <li>Reference checks</li>
          <li>Verify designations and duration</li>
          <li>Ask reason for leaving</li>
          <li>Explain employment gaps</li>
        </ul>

        <h5>(d) Criminal Record Check:</h5>
        <ul className="legal-list">
          <li>Police verification certificate</li>
          <li>Self-declaration of cases</li>
          <li>Court database check</li>
          <li>Any financial crime: rejection</li>
        </ul>

        <h5>(e) Credit History Check (Sensitive Roles):</h5>
        <ul className="legal-list">
          <li>Credit bureau check</li>
          <li>Check bankruptcies</li>
          <li>Financial distress = red flag</li>
        </ul>

        <h5>(f) Adverse Media Check:</h5>
        <ul className="legal-list">
          <li>Internet search</li>
          <li>Scams/regulatory actions</li>
          <li>Public social media review</li>
        </ul>

        <h5>(g) Regulatory Debarment Check:</h5>
        <ul className="legal-list">
          <li>SEBI debarred list</li>
          <li>RBI willful defaulters</li>
        </ul>

        <h5>(h) References:</h5>
        <ul className="legal-list">
          <li>At least two references</li>
        </ul>

        <h3>18.1.2 Enhanced Screening for Sensitive Positions</h3>

        <ul className="legal-list">
          <li>More extensive background checks</li>
          <li>Detailed financial review</li>
          <li>Ethics/integrity interview</li>
          <li>Psychometric assessments</li>
          <li>CEO/Board approval</li>
        </ul>

        <h3>18.1.3 Onboarding Documentation</h3>

        <ul className="legal-list">
          <li>Employment contract</li>
          <li>Code of Conduct acknowledgment</li>
          <li>AML/KYC Policy acknowledgment</li>
          <li>NDA</li>
          <li>Conflict of Interest declaration</li>
          <li>Acceptable Use Policy</li>
          <li>Employee Handbook receipt</li>
        </ul>

        <h3>18.2 Ongoing Personnel Integrity Management</h3>

        <h4>18.2.1 Code of Conduct</h4>

        <h5>(a) Integrity and Ethics:</h5>
        <ul className="legal-list">
          <li>Honesty</li>
          <li>Compliance with law</li>
          <li>Avoid conflicts</li>
          <li>No bribes</li>
        </ul>

        <h5>(b) Confidentiality:</h5>
        <ul className="legal-list">
          <li>Customer info confidentiality</li>
          <li>Anti-tipping off</li>
          <li>Social media restrictions</li>
        </ul>

        <h5>(c) Conflict of Interest:</h5>
        <ul className="legal-list">
          <li>Disclosure of investments</li>
          <li>Disclosure of relationships</li>
          <li>Recusal where needed</li>
        </ul>

        <h5>(d) Insider Trading:</h5>
        <ul className="legal-list">
          <li>No trading on MNPI</li>
          <li>Pre-clearance needed</li>
        </ul>

        <h5>(e) Professional Conduct:</h5>
        <ul className="legal-list">
          <li>Respectful workplace</li>
          <li>No harassment</li>
          <li>Report misconduct</li>
        </ul>

        <h3>18.2.2 Annual Declarations</h3>

        <h5>(a) Conflict of Interest Declaration:</h5>
        <ul className="legal-list">
          <li>Financial interests</li>
          <li>Related party relationships</li>
          <li>Changes year-to-year</li>
        </ul>

        <h5>(b) Compliance Declaration:</h5>
        <ul className="legal-list">
          <li>Certification of compliance</li>
          <li>Disclosure of violations</li>
        </ul>

        <h5>(c) Gifts and Hospitality:</h5>
        <ul className="legal-list">
          <li>Declare gifts &gt; INR 5,000</li>
          <li>Hospitality beyond courtesies</li>
        </ul>

        <h5>(d) Outside Employment:</h5>
        <ul className="legal-list">
          <li>Disclose any external work</li>
        </ul>

        <h3>18.2.3 Periodic Background Re-Checks</h3>

        <ul className="legal-list">
          <li>Every 3–5 years</li>
          <li>Criminal check</li>
          <li>Credit check</li>
          <li>Adverse media screening</li>
        </ul>

        <h3>18.2.4 Monitoring for Indicators of Compromise</h3>

        <ul className="legal-list">
          <li>Sudden unexplained wealth</li>
          <li>Financial distress</li>
          <li>Close relationships with customers</li>
          <li>Reluctance to take leave</li>
          <li>Secretiveness</li>
          <li>Unusual system access</li>
          <li>Attempts to bypass controls</li>
        </ul>

        <h3>18.3 Separation and Exit Management</h3>

        <h4>18.3.1 Voluntary Resignation</h4>
        <ul className="legal-list">
          <li>Written resignation</li>
          <li>Exit interview</li>
          <li>Confidentiality emphasis</li>
        </ul>

        <h4>18.3.2 Termination for Cause</h4>

        <h5>Grounds:</h5>
        <ul className="legal-list">
          <li>Policy violation</li>
          <li>Tipping off</li>
          <li>Falsification of documents</li>
          <li>Bribery</li>
          <li>Unauthorized disclosure</li>
          <li>Failure to report suspicion</li>
          <li>Collusion with customers</li>
          <li>Repeated non-compliance</li>
        </ul>

        <h5>Process:</h5>
        <ul className="legal-list">
          <li>Investigation</li>
          <li>Show-cause notice</li>
          <li>Decision</li>
          <li>Termination letter</li>
          <li>Recovery of company property</li>
        </ul>

        <h4>18.3.3 Exit Procedures</h4>

        <h5>(a) Access Revocation:</h5>
        <ul className="legal-list">
          <li>IT access disabled</li>
          <li>Physical access revoked</li>
          <li>Return of devices</li>
        </ul>

        <h5>(b) Exit Clearance:</h5>
        <ul className="legal-list">
          <li>Department clearances</li>
          <li>Return of documents</li>
          <li>Settlement of dues</li>
        </ul>

        <h5>(c) Confidentiality Reminder:</h5>
        <ul className="legal-list">
          <li>NDA survival clause</li>
          <li>Consequences explained</li>
        </ul>

        <h5>(d) Documentation:</h5>
        <ul className="legal-list">
          <li>Exit interview notes</li>
          <li>Clearance certificate</li>
          <li>Full & final settlement</li>
        </ul>

        <h3>18.3.4 Post-Employment Monitoring</h3>

        <ul className="legal-list">
          <li>Monitor for leaks or misuse</li>
          <li>Legal action if breach</li>
        </ul>

        <h3>18.4 Performance Management and AML/KYC Compliance</h3>

        <h4>18.4.1 Compliance as Performance Metric</h4>

        <h5>(a) Compliance Team:</h5>
        <ul className="legal-list">
          <li>Quality of KYC reviews</li>
          <li>Timeliness of investigations</li>
          <li>STR quality</li>
          <li>Audit findings</li>
          <li>Training completion</li>
          <li>Regulatory feedback</li>
        </ul>

        <h5>(b) Customer-Facing Staff:</h5>
        <ul className="legal-list">
          <li>Onboarding documentation accuracy</li>
          <li>Risk assessments quality</li>
          <li>Red flag escalation</li>
          <li>Procedure compliance</li>
        </ul>

        <h5>(c) Senior Management:</h5>
        <ul className="legal-list">
          <li>Program effectiveness</li>
          <li>Audit outcomes</li>
          <li>No penalties</li>
          <li>Compliance culture</li>
          <li>Resource allocation</li>
        </ul>

        <h5>(d) All Personnel:</h5>
        <ul className="legal-list">
          <li>Mandatory training completion</li>
          <li>No violations</li>
          <li>Suspicious activity reporting</li>
          <li>Adherence to Code of Conduct</li>
        </ul>

      </section>

    </div>
  );
}

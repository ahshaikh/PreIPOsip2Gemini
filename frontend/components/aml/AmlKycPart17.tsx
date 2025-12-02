// components/aml/AmlKycPart17.tsx
'use client';

import React from "react";

export default function AmlKycPart17() {
  return (
    <div>

      {/* 24.3 Operational Continuity Measures */}
      <section id="operational-continuity" className="section">
        <div className="section-header">
          <span className="section-number">24.3</span>
          <h2 className="section-title">Operational Continuity Measures</h2>
        </div>

        <h3>24.3.1 Alternate Work Arrangements</h3>

        <h4>(a) Work-from-Home (WFH) Capability:</h4>
        <ul className="legal-list">
          <li>VPN access for remote connectivity</li>
          <li>Laptops for all critical staff</li>
          <li>Secure authentication (MFA)</li>
          <li>Cloud-based systems accessible remotely</li>
          <li>Collaboration tools (video conferencing, messaging)</li>
          <li>Proven capability during COVID-19 pandemic</li>
        </ul>

        <h4>(b) Backup Office Location:</h4>
        <ul className="legal-list">
          <li>Alternate office space in different geographic area</li>
          <li>Available for use if primary office unusable</li>
          <li>Basic infrastructure (desks, connectivity)</li>
        </ul>

        <h4>(c) Staff Augmentation:</h4>
        <ul className="legal-list">
          <li>Cross-training (multiple people can perform each critical function)</li>
          <li>Succession planning (backups for key roles like Principal Officer)</li>
          <li>Vendor arrangements for temporary staff if needed</li>
        </ul>

        <h3>24.3.2 Vendor and Service Provider Continuity</h3>

        <h4>(a) Vendor BCP Assessment:</h4>
        <ul className="legal-list">
          <li>Review vendor's BCP and DR capabilities before engagement</li>
          <li>Annual reassessment</li>
          <li>Vendor must have plans for continuity of services to Platform</li>
        </ul>

        <h4>(b) Alternate Vendors:</h4>
        <ul className="legal-list">
          <li>For critical services (e.g., transaction monitoring system): Identify alternate vendors</li>
          <li>Having options reduces dependency risk</li>
        </ul>

        <h4>(c) SLAs:</h4>
        <ul className="legal-list">
          <li>Define uptime commitments (e.g., 99.9% availability)</li>
          <li>Financial penalties for SLA breaches</li>
          <li>Escalation process during outages</li>
        </ul>

        <h3>24.3.3 Communication Plans</h3>

        <h4>(a) Internal Communication:</h4>
        <ul className="legal-list">
          <li>Emergency contact list</li>
          <li>Communication tree</li>
          <li>Updates via email, SMS, WhatsApp</li>
          <li>Status dashboard</li>
        </ul>

        <h4>(b) External Communication:</h4>
        <ul className="legal-list">
          <li>Customers: Service status updates</li>
          <li>Regulators: Notify disruptions affecting compliance</li>
          <li>Vendors/partners: Coordination</li>
          <li>Media: If public incident</li>
        </ul>

      </section>

      {/* 24.4 Pandemic Planning */}
      <section id="pandemic-planning" className="section">
        <div className="section-header">
          <span className="section-number">24.4</span>
          <h2 className="section-title">Pandemic and Health Emergency Planning</h2>
        </div>

        <h4>(a) Remote Work Readiness:</h4>
        <ul className="legal-list">
          <li>Assume office may be inaccessible</li>
          <li>All compliance tasks performable remotely</li>
          <li>Digital workflows (paperless)</li>
        </ul>

        <h4>(b) Health and Safety Protocols:</h4>
        <ul className="legal-list">
          <li>Hygiene, distancing, health screening</li>
          <li>Isolation protocols</li>
          <li>Mental health support</li>
        </ul>

        <h4>(c) Regulatory Flexibility:</h4>
        <ul className="legal-list">
          <li>Awareness of reliefs</li>
          <li>Document disruptions</li>
          <li>Proactive regulatory communication</li>
        </ul>
      </section>

      {/* 24.5 Crisis Management */}
      <section id="crisis-management" className="section">
        <div className="section-header">
          <span className="section-number">24.5</span>
          <h2 className="section-title">Crisis Management and Incident Response</h2>
        </div>

        <h3>24.5.1 Crisis Management Team</h3>

        <ul className="legal-list">
          <li>CEO / senior executive</li>
          <li>Designated Director</li>
          <li>Principal Officer</li>
          <li>IT Head</li>
          <li>HR Head</li>
          <li>Legal Counsel</li>
          <li>Communications/PR</li>
        </ul>

        <h3>24.5.2 Incident Response Phases</h3>

        <h4>(a) Detection and Assessment (Hour 0-2):</h4>
        <ul className="legal-list">
          <li>Incident detected</li>
          <li>Severity assessed</li>
          <li>Decision to activate BCP/DR</li>
        </ul>

        <h4>(b) Response and Containment (Hour 2-24):</h4>
        <ul className="legal-list">
          <li>Implement immediate measures</li>
          <li>Contain impact</li>
          <li>Activate DR if needed</li>
          <li>Communicate</li>
        </ul>

        <h4>(c) Recovery (Day 1-7):</h4>
        <ul className="legal-list">
          <li>Restore normal operations</li>
          <li>Verify systems</li>
          <li>Address backlog</li>
        </ul>

        <h4>(d) Post-Incident Review (Week 2):</h4>
        <ul className="legal-list">
          <li>Comprehensive review</li>
          <li>Lessons learned</li>
          <li>Plan updates</li>
        </ul>

        <h3>24.5.3 Documentation</h3>

        <ul className="legal-list">
          <li>Incident log</li>
          <li>Decision log</li>
          <li>Communication log</li>
          <li>Financial impact</li>
          <li>Lessons learned report</li>
        </ul>
      </section>

      {/* 25. Cyber Resilience */}
      <section id="cyber-resilience" className="section">
        <div className="section-header">
          <span className="section-number">25</span>
          <h2 className="section-title">CYBER RESILIENCE FOR AML/KYC SYSTEMS</h2>
        </div>

        <h3>25.1 Cybersecurity Threat Landscape</h3>

        <h4>25.1.1 Threats to AML/KYC Systems</h4>

        <h5>(a) Data Breaches:</h5>
        <ul className="legal-list">
          <li>Unauthorized access</li>
          <li>PII theft</li>
          <li>Regulatory and reputational impact</li>
        </ul>

        <h5>(b) Ransomware:</h5>
        <ul className="legal-list">
          <li>Data encryption</li>
          <li>Ransom demands</li>
        </ul>

        <h5>(c) Insider Threats:</h5>
        <ul className="legal-list">
          <li>Malicious insiders</li>
          <li>Negligent insiders</li>
        </ul>

        <h5>(d) Phishing and Social Engineering:</h5>
        <ul className="legal-list">
          <li>Credential theft</li>
          <li>Spear phishing</li>
        </ul>

        <h5>(e) DDoS Attacks:</h5>
        <ul className="legal-list">
          <li>System overwhelm</li>
          <li>Disruption</li>
        </ul>

        <h5>(f) Supply Chain Attacks:</h5>
        <ul className="legal-list">
          <li>Vendor compromise</li>
        </ul>

        <h5>(g) APTs:</h5>
        <ul className="legal-list">
          <li>Sophisticated, long-term attacks</li>
        </ul>

        <h4>25.1.2 Unique Vulnerabilities</h4>
        <ul className="legal-list">
          <li>High-value PII</li>
          <li>STR confidentiality</li>
          <li>Complex integrations</li>
          <li>Remote access</li>
        </ul>

        <h3>25.2 Cybersecurity Controls</h3>

        <h4>25.2.1 Preventive Controls</h4>
        <p>As covered in Part 5 Section 15.2.5</p>

        <h4>25.2.2 Detective Controls</h4>

        <h5>(a) Security Monitoring:</h5>
        <ul className="legal-list">
          <li>24x7 SOC/MSSP</li>
          <li>SIEM</li>
          <li>Anomaly detection</li>
        </ul>

        <h5>(b) Intrusion Detection:</h5>
        <ul className="legal-list">
          <li>NIDS</li>
          <li>HIDS</li>
        </ul>

        <h5>(c) User Activity Monitoring:</h5>
        <ul className="legal-list">
          <li>Privileged user monitoring</li>
          <li>Unusual patterns</li>
        </ul>

        <h5>(d) Threat Intelligence:</h5>
        <ul className="legal-list">
          <li>IOC integration</li>
          <li>Emerging threat awareness</li>
        </ul>

        <h5>(e) Vulnerability Scanning:</h5>
        <ul className="legal-list">
          <li>Weekly/monthly scans</li>
          <li>Pen tests</li>
        </ul>

        <h4>25.2.3 Responsive Controls</h4>

        <h5>(a) Incident Response Plan:</h5>
        <ul className="legal-list">
          <li>CIRT team</li>
          <li>Playbooks</li>
          <li>Communications</li>
        </ul>

        <h5>(b) Incident Classification:</h5>
        <ul className="legal-list">
          <li>Critical: active breach</li>
          <li>High: blocked attempt</li>
          <li>Medium: suspicious events</li>
          <li>Low: false positives</li>
        </ul>

        <h5>(c) Containment Strategies:</h5>
        <ul className="legal-list">
          <li>Isolation</li>
          <li>Blocked IPs</li>
          <li>Password resets</li>
        </ul>

        <h5>(d) Eradication:</h5>
        <ul className="legal-list">
          <li>Remove malware</li>
          <li>Patch systems</li>
        </ul>

        <h5>(e) Recovery:</h5>
        <ul className="legal-list">
          <li>Clean backups</li>
          <li>Integrity checks</li>
        </ul>

        <h5>(f) Post-Incident:</h5>
        <ul className="legal-list">
          <li>Forensics</li>
          <li>Notifications</li>
          <li>Insurance</li>
          <li>Lessons learned</li>
        </ul>

      </section>

      {/* 25.3 Cyber Incident Notification */}
      <section id="cyber-notification" className="section">
        <div className="section-header">
          <span className="section-number">25.3</span>
          <h2 className="section-title">Cyber Incident Notification Requirements</h2>
        </div>

        <h3>25.3.1 Regulatory Notifications</h3>

        <h4>(a) CERT-In:</h4>
        <ul className="legal-list">
          <li>Notify within 6 hours</li>
          <li>For specified incidents</li>
        </ul>

        <h4>(b) Data Protection Authority (DPDP Act):</h4>
        <ul className="legal-list">
          <li>Notify within 72 hours</li>
          <li>Details of breach</li>
        </ul>

        <h4>(c) SEBI/FIU-IND:</h4>
        <ul className="legal-list">
          <li>If compliance affected</li>
          <li>If data compromised</li>
        </ul>

        <h4>(d) Cyber Insurance Provider:</h4>
        <ul className="legal-list">
          <li>Notify immediately</li>
        </ul>

        <h3>25.3.2 Customer Notifications</h3>

        <h4>(a) When Required:</h4>
        <ul className="legal-list">
          <li>High-risk data breach</li>
          <li>Identity theft risk</li>
        </ul>

        <h4>(b) Notification Content:</h4>
        <ul className="legal-list">
          <li>Nature of breach</li>
          <li>Data compromised</li>
          <li>Consequences</li>
          <li>Measures taken</li>
        </ul>

        <h4>(c) Timing:</h4>
        <ul className="legal-list">
          <li>Without undue delay</li>
        </ul>

        <h4>(d) Channels:</h4>
        <ul className="legal-list">
          <li>Email</li>
          <li>SMS</li>
          <li>Platform notifications</li>
        </ul>

      </section>

      {/* 25.4 Cybersecurity Awareness */}
      <section id="cyber-awareness" className="section">
        <div className="section-header">
          <span className="section-number">25.4</span>
          <h2 className="section-title">Cybersecurity Awareness and Training</h2>
        </div>

        <h3>25.4.1 Security Awareness Training</h3>

        <h4>(a) Annual Mandatory Training:</h4>
        <ul className="legal-list">
          <li>Password security</li>
          <li>Phishing awareness</li>
          <li>Social engineering</li>
          <li>Incident reporting</li>
        </ul>

        <h4>(b) Just-in-Time Awareness:</h4>
        <ul className="legal-list">
          <li>Threat alerts</li>
          <li>Security tips</li>
        </ul>

        <h4>(c) Gamification:</h4>
        <ul className="legal-list">
          <li>Quizzes</li>
          <li>Simulated phishing</li>
        </ul>

        <h3>25.4.2 Technical Training</h3>

        <ul className="legal-list">
          <li>Security tools</li>
          <li>Certifications (CISSP, CEH)</li>
          <li>Threat hunting</li>
        </ul>

        <h3>25.4.3 Executive Briefings</h3>

        <ul className="legal-list">
          <li>Quarterly briefings</li>
          <li>Threat landscape</li>
          <li>Security posture</li>
        </ul>

      </section>

      {/* 25.5 Cyber Insurance */}
      <section id="cyber-insurance" className="section">
        <div className="section-header">
          <span className="section-number">25.5</span>
          <h2 className="section-title">Cyber Insurance</h2>
        </div>

        <h3>25.5.1 Coverage</h3>

        <h4>First-Party Costs:</h4>
        <ul className="legal-list">
          <li>Incident response</li>
          <li>Data recovery</li>
          <li>Business interruption</li>
          <li>Ransom payments</li>
        </ul>

        <h4>Third-Party Liabilities:</h4>
        <ul className="legal-list">
          <li>Customer lawsuits</li>
          <li>Regulatory penalties</li>
        </ul>

        <h3>25.5.2 Policy Considerations</h3>

        <ul className="legal-list">
          <li>Coverage limits</li>
          <li>Exclusions</li>
          <li>Retroactive date</li>
        </ul>

        <h3>25.5.3 Risk Assessment</h3>

        <ul className="legal-list">
          <li>Cybersecurity assessment</li>
          <li>Audit history</li>
        </ul>

      </section>

      {/* 25.6 Vendor Cyber Risk */}
      <section id="vendor-cyber-risk" className="section">
        <div className="section-header">
          <span className="section-number">25.6</span>
          <h2 className="section-title">Vendor and Third-Party Cyber Risk Management</h2>
        </div>

        <h3>25.6.1 Vendor Cybersecurity Due Diligence</h3>

        <h4>(a) Security Questionnaire:</h4>
        <ul className="legal-list">
          <li>Policies</li>
          <li>Access controls</li>
          <li>Encryption</li>
          <li>IR capability</li>
          <li>Certifications</li>
          <li>Insurance</li>
        </ul>

        <h4>(b) Certifications:</h4>
        <ul className="legal-list">
          <li>ISO 27001</li>
          <li>SOC 2</li>
          <li>Pen test reports</li>
        </ul>

        <h4>(c) On-Site Assessment:</h4>
        <ul className="legal-list">
          <li>Facility visit</li>
          <li>Control verification</li>
        </ul>

        <h3>25.6.2 Contractual Protections</h3>

        <ul className="legal-list">
          <li>Security obligations</li>
          <li>Notification requirements</li>
          <li>Right to audit</li>
          <li>Liability/indemnification</li>
        </ul>

        <h3>25.6.3 Ongoing Monitoring</h3>

        <ul className="legal-list">
          <li>Annual reassessment</li>
          <li>Updated certifications</li>
        </ul>

        <h3>25.6.4 Vendor Incident Response</h3>

        <ul className="legal-list">
          <li>Immediate notification</li>
          <li>Containment</li>
          <li>Evidence preservation</li>
        </ul>

      </section>

      {/* 25.7 RegTech */}
      <section id="regtech" className="section">
        <div className="section-header">
          <span className="section-number">25.7</span>
          <h2 className="section-title">Regulatory Technology (RegTech) and Cybersecurity</h2>
        </div>

        <h3>25.7.1 RegTech Solutions</h3>

        <ul className="legal-list">
          <li>Cloud-based SaaS</li>
          <li>AI-powered analytics</li>
          <li>API-driven integrations</li>
        </ul>

        <h4>Cybersecurity Implications:</h4>
        <ul className="legal-list">
          <li>Data with third parties</li>
          <li>Increased attack surface</li>
        </ul>

        <h3>25.7.2 Secure RegTech Adoption</h3>

        <h4>(a) Vendor Selection:</h4>
        <ul className="legal-list">
          <li>Security track record</li>
          <li>Data residency</li>
        </ul>

        <h4>(b) Secure Integration:</h4>
        <ul className="legal-list">
          <li>API security</li>
          <li>Data minimization</li>
        </ul>

        <h4>(c) Governance:</h4>
        <ul className="legal-list">
          <li>Security reviews</li>
          <li>Exit strategy</li>
        </ul>

      </section>

      {/* 25.8 Emerging Cyber Threats */}
      <section id="emerging-threats" className="section">
        <div className="section-header">
          <span className="section-number">25.8</span>
          <h2 className="section-title">Emerging Cyber Threats and Future Preparedness</h2>
        </div>

        <h3>25.8.1 Quantum Computing Threat</h3>
        <ul className="legal-list">
          <li>Potential to break encryption</li>
          <li>10–20 year horizon</li>
          <li>Post-quantum standards emerging</li>
        </ul>

        <h3>25.8.2 AI-Powered Attacks</h3>
        <ul className="legal-list">
          <li>AI for phishing</li>
          <li>Deepfakes</li>
          <li>Automated hacking</li>
        </ul>

        <h3>25.8.3 IoT and OT Risks</h3>
        <ul className="legal-list">
          <li>IoT attack vectors</li>
          <li>Network segmentation needed</li>
        </ul>

        <h3>25.8.4 Continuous Adaptation</h3>
        <ul className="legal-list">
          <li>Security posture review</li>
          <li>Investment in new tech</li>
          <li>Industry collaboration</li>
        </ul>

      </section>

    </div>
  );
}

// components/aml/AmlKycPart6.tsx
'use client';

import React from "react";

export default function AmlKycPart6() {
  return (
    <div>

      {/* 10.2.2 Assessment Criteria */}
      <section id="assessment-criteria" className="section">
        <div className="section-header">
          <span className="section-number">10.2.2</span>
          <h2 className="section-title">Assessment Criteria</h2>
        </div>

        <p>
          When evaluating whether a transaction is suspicious, the Principal Officer shall consider:
        </p>

        <h4>(a) Customer Profile Consistency:</h4>
        <ul className="legal-list">
          <li>Does transaction align with Customer's stated occupation, income, and financial capacity?</li>
          <li>Is transaction consistent with Customer's declared purpose of account opening?</li>
          <li>Does Customer's background support ability to conduct such transaction?</li>
        </ul>

        <h4>(b) Economic Rationale:</h4>
        <ul className="legal-list">
          <li>Is there a logical, legitimate business or investment purpose?</li>
          <li>Do commercial terms make sense for both parties?</li>
          <li>Is transaction economically viable or does it result in apparent loss to Customer?</li>
        </ul>

        <h4>(c) Transaction Structure and Complexity:</h4>
        <ul className="legal-list">
          <li>Is structure unnecessarily complex for the apparent purpose?</li>
          <li>Are there layers or intermediaries without clear justification?</li>
          <li>Does structure appear designed to obscure fund source or beneficial ownership?</li>
        </ul>

        <h4>(d) Pattern Analysis:</h4>
        <ul className="legal-list">
          <li>Is this an isolated transaction or part of a pattern?</li>
          <li>Are there similar transactions by same Customer or related parties?</li>
          <li>Does pattern suggest layering, structuring, or other money laundering technique?</li>
        </ul>

        <h4>(e) Fund Source and Destination:</h4>
        <ul className="legal-list">
          <li>Can Customer provide credible explanation and documentation of fund source?</li>
          <li>Are funds moving to/from high-risk jurisdictions?</li>
          <li>Are third parties involved without reasonable explanation?</li>
        </ul>

        <h4>(f) Customer Behavior:</h4>
        <ul className="legal-list">
          <li>Is Customer cooperative or evasive?</li>
          <li>Does Customer's knowledge/sophistication match stated background?</li>
          <li>Are there indicators of being coached or following instructions?</li>
        </ul>

        <h4>(g) Counterparty Characteristics:</h4>
        <ul className="legal-list">
          <li>Is counterparty legitimate, verifiable entity/individual?</li>
          <li>Are there red flags associated with seller/buyer?</li>
          <li>Are there connections or relationships not disclosed?</li>
        </ul>

        <h4>(h) Red Flag Indicators:</h4>
        <ul className="legal-list">
          <li>How many red flags (from Part 3, Section 9) are present?</li>
          <li>Are red flags severe or minor?</li>
          <li>Can red flags be adequately explained by Customer?</li>
        </ul>
      </section>

      {/* 10.2.3 Documentation of Suspicion */}
      <section id="documentation-of-suspicion" className="section">
        <div className="section-header">
          <span className="section-number">10.2.3</span>
          <h2 className="section-title">Documentation of Suspicion Determination</h2>
        </div>

        <p>The Principal Officer shall document:</p>

        <ul className="legal-list">
          <li>(a) Specific facts and circumstances giving rise to suspicion;</li>
          <li>(b) Red flags identified and their significance;</li>
          <li>(c) Customer explanations obtained (if any) and assessment of their credibility;</li>
          <li>(d) Pattern analysis or contextual factors considered;</li>
          <li>(e) Legal and regulatory references supporting determination;</li>
          <li>(f) Consultation with legal counsel or senior management (if any);</li>
          <li>(g) Decision rationale: Why transaction is suspicious and reportable;</li>
          <li>(h) Date and time of determination.</li>
        </ul>
      </section>

      {/* 10.3 STR Filing Procedures */}
      <section id="str-procedures" className="section">
        <div className="section-header">
          <span className="section-number">10.3</span>
          <h2 className="section-title">Suspicious Transaction Report (STR) Filing Procedures</h2>
        </div>

        {/* 10.3.1 Timeline */}
        <h3>10.3.1 Timeline for Filing</h3>

        <p><strong>Critical Requirement:</strong> STR must be filed with FIU-IND within 7 calendar days of determination.</p>

        <h4>Timeline breakdown:</h4>
        <ul className="legal-list">
          <li><strong>Day 0:</strong> Suspicion determination date</li>
          <li><strong>Day 1–3:</strong> STR preparation, internal review</li>
          <li><strong>Day 4–5:</strong> Designated Director review</li>
          <li><strong>Day 6:</strong> Final review & quality check</li>
          <li><strong>Day 7:</strong> STR filing deadline</li>
        </ul>

        <p><strong>Non-Negotiable Rule:</strong> 7-day timeline is statutory, non-extendable.</p>

        <p><strong>Contingency:</strong> If Day 7 is a holiday, file before last working day.</p>

        {/* 10.3.2 Filing Format & System */}
        <h3>10.3.2 STR Filing Format and System</h3>

        <h4>(a) FINnet System:</h4>
        <ul className="legal-list">
          <li>STR filed electronically via FINnet</li>
          <li>Platform registered with FIU-IND</li>
          <li>Principal Officer & backups authorized</li>
          <li>System 24×7 available</li>
        </ul>

        <h4>(b) STR Format:</h4>
        <ul className="legal-list">
          <li>XML format per FIU-IND specification</li>
          <li>Reporting entity details</li>
          <li>Customer/subject details</li>
          <li>Transaction details</li>
          <li>Suspicion narrative</li>
          <li>Supporting documents</li>
        </ul>

        <h4>(c) Technical Requirements:</h4>
        <ul className="legal-list">
          <li>Digital signature of Principal Officer</li>
          <li>Validation checks passed</li>
          <li>Acknowledgment receipt archived</li>
        </ul>

        {/* 10.3.3 STR Content Requirements */}
        <h3>10.3.3 STR Content Requirements</h3>

        <h4>(a) Section 1: Reporting Entity Information</h4>
        <ul className="legal-list">
          <li>Platform's legal name</li>
          <li>Registration details</li>
          <li>Principal Officer details</li>
          <li>FIU-IND reporting code</li>
          <li>Date/time of STR generation</li>
        </ul>

        <h4>(b) Section 2: Subject Information</h4>

        <h5>For Individuals:</h5>
        <ul className="legal-list">
          <li>Full name, aliases</li>
          <li>Date of birth, gender, nationality</li>
          <li>PAN (mandatory)</li>
          <li>Aadhaar (if available)</li>
          <li>Passport (for NRIs/foreign nationals)</li>
          <li>Address, phone, email</li>
          <li>Occupation/employer</li>
          <li>Bank account details</li>
        </ul>

        <h5>For Entities:</h5>
        <ul className="legal-list">
          <li>Legal name</li>
          <li>Entity type</li>
          <li>Registration number (CIN/LLPIN)</li>
          <li>PAN</li>
          <li>Registered office</li>
          <li>Director/partner/trustee details</li>
          <li>Beneficial owners</li>
          <li>Bank account details</li>
        </ul>

        <h5>Multiple Subjects:</h5>
        <ul className="legal-list">
          <li>Details recorded for each</li>
          <li>Relationships explained</li>
        </ul>

        <h4>(c) Section 3: Transaction Information</h4>
        <ul className="legal-list">
          <li>Transaction date/time</li>
          <li>Transaction type (purchase/sale, etc.)</li>
          <li>Value/amount</li>
          <li>Currency</li>
          <li>Payment mode</li>
          <li>Bank details</li>
          <li>Reference numbers</li>
          <li>Whether attempted or completed</li>
          <li>All linked transactions if part of pattern</li>
        </ul>

        <h4>(d) Section 4: Reason for Suspicion (Critical)</h4>
        <p>Must include:</p>
        <ul className="legal-list">
          <li>Specific red flags</li>
          <li>Profile inconsistencies</li>
          <li>Pattern analysis</li>
          <li>Lack of economic rationale</li>
          <li>Customer behavior issues</li>
          <li>Due diligence concerns</li>
        </ul>

        <p><strong>Example narrative from file included verbatim.</strong></p>

        <h4>Supporting Documentation:</h4>
        <ul className="legal-list">
          <li>List of attached documents</li>
          <li>Investigation file reference</li>
          <li>Legal advice summary</li>
        </ul>

        <h4>(e) Section 5: Additional Information</h4>
        <ul className="legal-list">
          <li>Previous STRs</li>
          <li>Related parties</li>
          <li>Actions taken</li>
          <li>Relationship status</li>
        </ul>

        {/* 10.3.4 STR Quality Standards */}
        <h3>10.3.4 STR Quality Standards</h3>

        <h4>(a) Completeness:</h4>
        <ul className="legal-list">
          <li>No missing mandatory fields</li>
          <li>All known details included</li>
        </ul>

        <h4>(b) Accuracy:</h4>
        <ul className="legal-list">
          <li>Verified against KYC records</li>
          <li>All numbers/dates checked</li>
        </ul>

        <h4>(c) Clarity:</h4>
        <ul className="legal-list">
          <li>Clear English</li>
          <li>Chronological narrative</li>
        </ul>

        <h4>(d) Objectivity:</h4>
        <ul className="legal-list">
          <li>Facts vs opinions clearly separated</li>
          <li>No speculation</li>
        </ul>

        <h4>(e) Actionability:</h4>
        <ul className="legal-list">
          <li>Sufficient detail for FIU-IND to act</li>
          <li>Clear leads provided</li>
        </ul>

        {/* 10.3.5 Internal Approval */}
        <h3>10.3.5 Internal Approval Process</h3>

        <h4>Step 1: Principal Officer Preparation (Day 1–3)</h4>
        <ul className="legal-list">
          <li>Draft STR prepared</li>
          <li>Supporting docs compiled</li>
          <li>Investigation reviewed</li>
          <li>Narrative finalized</li>
        </ul>

        <h4>Step 2: Legal Review (Day 3–4)</h4>
        <ul className="legal-list">
          <li>Complex cases reviewed by counsel</li>
          <li>Legal input documented</li>
        </ul>

        <h4>Step 3: Designated Director Approval (Day 4–5)</h4>
        <ul className="legal-list">
          <li>STR reviewed</li>
          <li>Legal requirements checked</li>
          <li>Approval or revisions requested</li>
        </ul>

        <h4>Step 4: Final Quality Check (Day 6)</h4>
        <ul className="legal-list">
          <li>Validation checks</li>
          <li>Proofreading</li>
          <li>Attachment verification</li>
        </ul>

        <h4>Step 5: Filing (Day 7)</h4>
        <ul className="legal-list">
          <li>STR uploaded to FINnet</li>
          <li>Digital signature applied</li>
          <li>Acknowledgment downloaded</li>
        </ul>

        {/* 10.3.6 Special Circumstances */}
        <h3>10.3.6 STR Filing in Special Circumstances</h3>

        <h4>(a) Attempted but Incomplete Transactions:</h4>
        <ul className="legal-list">
          <li>STR still filed if suspicious</li>
          <li>Narrative must clarify attempt status</li>
        </ul>

        <h4>(b) Transactions Already Completed:</h4>
        <ul className="legal-list">
          <li>Post-facto suspicion → file within 7 days</li>
        </ul>

        <h4>(c) Ongoing Patterns:</h4>
        <ul className="legal-list">
          <li>Single comprehensive STR or multiple linked STRs</li>
        </ul>

        <h4>(d) Multiple Customers:</h4>
        <ul className="legal-list">
          <li>Individual STRs with cross-references</li>
        </ul>

        <h4>(e) Joint/Corporate Accounts:</h4>
        <ul className="legal-list">
          <li>All holders/UBOs listed</li>
        </ul>

        <h4>(f) PEPs:</h4>
        <ul className="legal-list">
          <li>Enhanced review, mandatory counsel input</li>
        </ul>

      </section>

      {/* 10.4 Confidentiality */}
      <section id="str-confidentiality" className="section">
        <div className="section-header">
          <span className="section-number">10.4</span>
          <h2 className="section-title">Confidentiality and Anti-Tipping Off Provisions</h2>
        </div>

        <h3>10.4.1 Absolute Confidentiality Requirement</h3>
        <p>
          Section 14 of PMLA imposes criminal liability for unauthorized disclosure of STR-related information.
        </p>

        <blockquote className="legal-quote">
          “Whoever being an officer, employee, advisor, consultant or any other person... discloses any information which may adversely affect the investigation... shall be punishable with imprisonment up to three years or fine up to five lakh rupees or both.”
        </blockquote>

        <h3>10.4.2 Anti-Tipping Off Protocol</h3>

        <h4>(a) Prohibition on Disclosure to Customer:</h4>
        <ul className="legal-list">
          <li>No disclosure of STR filing</li>
          <li>No mention of investigation</li>
          <li>No red flag discussion</li>
        </ul>

        <h4>(b) Communication During Investigation:</h4>
        <ul className="legal-list">
          <li>Use neutral phrases like “routine verification”</li>
          <li>No regulatory references</li>
        </ul>

        <h4>(c) If Transaction Must Be Rejected:</h4>
        <ul className="legal-list">
          <li>Reason attributed to internal policy, not suspicion</li>
        </ul>

        <h4>(d) Limitation of Internal Access:</h4>
        <ul className="legal-list">
          <li>Need-to-know basis</li>
          <li>Strict access controls</li>
        </ul>

        <h4>(e) Training:</h4>
        <ul className="legal-list">
          <li>Mandatory annual training</li>
          <li>Role-based confidentiality guidance</li>
        </ul>

        <h4>(f) Vendor Restrictions:</h4>
        <ul className="legal-list">
          <li>NDA required</li>
          <li>Authorization required</li>
        </ul>

        <h3>10.4.3 Disclosure Exceptions</h3>
        <ul className="legal-list">
          <li>To FIU-IND</li>
          <li>Law enforcement when required</li>
          <li>Court orders</li>
          <li>Statutory auditors</li>
          <li>Regulators</li>
          <li>Internal compliance/legal on need-to-know basis</li>
        </ul>

        <p>All disclosures logged with date, purpose, and authorization.</p>
      </section>

      {/* 10.5 Post-Filing Actions */}
      <section id="post-filing-actions" className="section">
        <div className="section-header">
          <span className="section-number">10.5</span>
          <h2 className="section-title">Post-Filing Actions and Follow-Up</h2>
        </div>

        <h3>10.5.1 Immediate Post-Filing Actions</h3>

        <h4>(a) Acknowledgment Management:</h4>
        <ul className="legal-list">
          <li>Acknowledgment downloaded</li>
          <li>STR reference number logged</li>
        </ul>

        <h4>(b) Enhanced Monitoring:</h4>
        <ul className="legal-list">
          <li>Customer flagged</li>
          <li>Future transactions escalated</li>
        </ul>

        <h4>(c) Relationship Evaluation:</h4>
        <ul className="legal-list">
          <li>Continue × Enhanced monitoring</li>
          <li>Restrict × Limited mode</li>
          <li>Terminate × Closure if severe</li>
        </ul>

        <h4>(d) Internal Documentation:</h4>
        <ul className="legal-list">
          <li>Investigation file archived</li>
          <li>Case closure report prepared</li>
        </ul>

        <h3>10.5.2 Response to FIU-IND Queries</h3>

        <h4>(a) Prioritization:</h4>
        <ul className="legal-list">
          <li>Highest priority</li>
        </ul>

        <h4>(b) Information Compilation:</h4>
        <ul className="legal-list">
          <li>KYC documents</li>
          <li>Transaction records</li>
          <li>Communications</li>
        </ul>

        <h4>(c) Response Format:</h4>
        <ul className="legal-list">
          <li>Cover letter</li>
          <li>Point-wise reply</li>
          <li>Indexed documents</li>
        </ul>

        <h4>(d) Internal Approval:</h4>
        <ul className="legal-list">
          <li>Designated Director approval</li>
        </ul>

        <h4>(e) Documentation:</h4>
        <ul className="legal-list">
          <li>Copy of response maintained</li>
        </ul>

        <h3>10.5.3 Interaction with Law Enforcement</h3>

        <h4>(a) Verification:</h4>
        <ul className="legal-list">
          <li>Credentials verified</li>
          <li>Authorization obtained</li>
        </ul>

        <h4>(b) Cooperation:</h4>
        <ul className="legal-list">
          <li>Full cooperation</li>
        </ul>

        <h4>(c) Legal Compliance:</h4>
        <ul className="legal-list">
          <li>Orders checked</li>
          <li>Legal counsel consulted</li>
        </ul>

        <h4>(d) Confidentiality:</h4>
        <ul className="legal-list">
          <li>No tipping off</li>
        </ul>

        <h4>(e) Documentation:</h4>
        <ul className="legal-list">
          <li>All interactions recorded</li>
        </ul>
      </section>

      {/* 10.6 STR Register */}
      <section id="str-register" className="section">
        <div className="section-header">
          <span className="section-number">10.6</span>
          <h2 className="section-title">STR Register and Record Keeping</h2>
        </div>

        <h3>10.6.1 STR Register Maintenance</h3>

        <div className="def-grid">
          <div className="def-row"><div className="def-term">Serial Number</div><div className="def-desc">Sequential numbering</div></div>
          <div className="def-row"><div className="def-term">STR Reference Number</div><div className="def-desc">FIU-IND assigned</div></div>
          <div className="def-row"><div className="def-term">Date of Suspicion</div><div className="def-desc">Determination date</div></div>
          <div className="def-row"><div className="def-term">Date of STR Filing</div><div className="def-desc">Submission date</div></div>
          <div className="def-row"><div className="def-term">Customer Name</div><div className="def-desc">Subject name</div></div>
          <div className="def-row"><div className="def-term">Customer ID</div><div className="def-desc">Internal ID</div></div>
          <div className="def-row"><div className="def-term">PAN</div><div className="def-desc">PAN of Customer</div></div>
          <div className="def-row"><div className="def-term">Transaction Date(s)</div><div className="def-desc">Dates of suspicious activity</div></div>
          <div className="def-row"><div className="def-term">Transaction Amount</div><div className="def-desc">Amount in INR</div></div>
          <div className="def-row"><div className="def-term">Brief Description</div><div className="def-desc">One-line summary</div></div>
          <div className="def-row"><div className="def-term">Red Flags Identified</div><div className="def-desc">Codes/description</div></div>
          <div className="def-row"><div className="def-term">Investigation Officer</div><div className="def-desc">Officer name</div></div>
          <div className="def-row"><div className="def-term">Principal Officer</div><div className="def-desc">Officer filing STR</div></div>
          <div className="def-row"><div className="def-term">Designated Director Approval</div><div className="def-desc">Date/signature</div></div>
          <div className="def-row"><div className="def-term">Post-Filing Action</div><div className="def-desc">Continue/Restrict/Terminate</div></div>
          <div className="def-row"><div className="def-term">FIU-IND Follow-Up</div><div className="def-desc">Queries (if any)</div></div>
          <div className="def-row"><div className="def-term">Law Enforcement Action</div><div className="def-desc">Any proceedings</div></div>
        </div>

        <h3>10.6.2 Record Retention Requirements</h3>

        <p>Retain for minimum 10 years:</p>

        <ul className="legal-list">
          <li>STR copy + attachments</li>
          <li>FINnet acknowledgment</li>
          <li>Complete investigation file</li>
          <li>KYC file</li>
          <li>Transaction records</li>
          <li>FIU-IND correspondence</li>
          <li>Law enforcement documents</li>
          <li>Monitoring reports</li>
        </ul>

      </section>
    </div>
  );
}

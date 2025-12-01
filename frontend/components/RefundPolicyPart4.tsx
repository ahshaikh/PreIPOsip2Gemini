'use client';

import React from 'react';
import { Search } from 'lucide-react';

export default function RefundPolicyPart4() {
  return (
    <section id="part-4" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 4</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">VERIFICATION AND VALIDATION PROTOCOLS</h2>
      </div>

      {/* ARTICLE 8 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Search className="text-indigo-600" size={20} /> ARTICLE 8: VERIFICATION AND VALIDATION PROTOCOLS
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">8.1 Multi-Level Verification Framework</h4>
          <p className="text-slate-600 mb-2">All refund requests shall undergo a structured, multi-level verification process designed to ensure:</p>
          <ul className="list-disc pl-6 text-slate-600 space-y-1">
            <li>Authenticity of the request and supporting documentation;</li>
            <li>Eligibility under this Policy and Applicable Law;</li>
            <li>Compliance with PMLA/AML requirements;</li>
            <li>Detection and prevention of fraud, money laundering, or abuse;</li>
            <li>Protection of Platform's legitimate interests and risk mitigation.</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">8.2 Level 1: Automated System Verification</h4>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Initial Screening</p>
              <p className="text-slate-600 text-sm mb-1">Upon receipt, refund requests are subjected to automated system checks:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(i) <strong>Data Completeness Check:</strong> Verification that all mandatory fields are populated and mandatory documents are attached;</li>
                <li>(ii) <strong>Transaction Verification:</strong> Cross-referencing of Transaction ID, amount, date against Platform's transaction database;</li>
                <li>(iii) <strong>Identity Verification:</strong> Matching of Stakeholder details against KYC records on file;</li>
                <li>(iv) <strong>Bank Account Verification:</strong> Confirming that refund account matches original payment source account or KYC-verified accounts;</li>
                <li>(v) <strong>Duplicate Detection:</strong> Checking for duplicate or multiple requests for the same transaction;</li>
                <li>(vi) <strong>Blacklist Screening:</strong> Screening against internal blacklists, fraud databases, and negative lists;</li>
                <li>(vii) <strong>Risk Scoring:</strong> Algorithmic risk assessment based on transaction value, Stakeholder history, request pattern, and red flags;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Outcome of Level 1</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li><strong>Green Flag:</strong> Request proceeds to Level 2 verification;</li>
                <li><strong>Yellow Flag:</strong> Request flagged for enhanced scrutiny at Level 2;</li>
                <li><strong>Red Flag:</strong> Request escalated to Level 3 or Compliance Team for investigation;</li>
                <li><strong>Auto-Rejection:</strong> Requests failing basic validation criteria (e.g., time-barred, duplicate, incomplete) may be auto-rejected with system-generated communication.</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">8.3 Level 2: Manual Review and Assessment</h4>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Assignment to Refund Officer</p>
              <p className="text-slate-600 text-sm mb-1">Requests passing Level 1 are assigned to designated Refund Processing Officers (RPO) based on:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Transaction value thresholds;</li>
                <li>Complexity of the case;</li>
                <li>Specialized knowledge requirements;</li>
                <li>Workload distribution;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Document Scrutiny</p>
              <p className="text-slate-600 text-sm mb-1">The RPO shall:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Examine all submitted documents for completeness, consistency, and authenticity;</li>
                <li>Verify signatures against specimen signatures on file;</li>
                <li>Cross-check facts and figures across multiple documents;</li>
                <li>Identify discrepancies, contradictions, or red flags;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Eligibility Assessment</p>
              <p className="text-slate-600 text-sm mb-1">The RPO shall:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Determine whether the refund ground falls within eligible categories under Article 4 and 5;</li>
                <li>Assess whether temporal limitations under Article 6 are satisfied;</li>
                <li>Evaluate whether conditions precedent for refund have been fulfilled;</li>
                <li>Calculate refund quantum considering deductions, forfeitures, and adjustments;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Transaction Investigation</p>
              <p className="text-slate-600 text-sm mb-1">The RPO may:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Review complete transaction history and audit trail;</li>
                <li>Examine email correspondence and chat logs between Platform and Stakeholder;</li>
                <li>Contact relevant departments (operations, technology, legal) for clarifications;</li>
                <li>Obtain statements from Platform personnel involved in the transaction;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(e) Third-Party Verification</p>
              <p className="text-slate-600 text-sm mb-1">Where necessary, the RPO may:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Contact banks to verify payment details and account ownership;</li>
                <li>Verify documents with issuing authorities (ROC, depository, etc.);</li>
                <li>Obtain confirmation from issuers, sellers, or counterparties;</li>
                <li>Engage external experts for technical or legal opinions;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(f) Preliminary Recommendation</p>
              <p className="text-slate-600 text-sm mb-1">The RPO shall prepare a detailed note containing:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Summary of facts and transaction background;</li>
                <li>Analysis of eligibility and compliance;</li>
                <li>Assessment of documentation and verification findings;</li>
                <li>Calculation of refund amount with deductions breakup;</li>
                <li>Preliminary recommendation: Approve/Reject/Seek Clarification/Escalate;</li>
                <li>Justification and reasoning for recommendation;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(g) Timeline for Level 2</p>
              <p className="text-slate-600 text-sm mb-1">Level 2 review shall ordinarily be completed within:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>7 (seven) Business Days for straightforward cases;</li>
                <li>15 (fifteen) Business Days for moderately complex cases;</li>
                <li>21 (twenty-one) Business Days for complex cases requiring extensive verification.</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">8.4 Level 3: Senior Management Review and Approval</h4>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Escalation Triggers</p>
              <p className="text-slate-600 text-sm mb-1">Requests are escalated to Level 3 if:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Refund amount exceeds ₹10,00,000 (Rupees Ten Lakhs);</li>
                <li>Request involves allegations of fraud, misrepresentation, or Platform liability;</li>
                <li>Legal implications or litigation risk is identified;</li>
                <li>RPO recommends rejection but Stakeholder is high-value or institutional client;</li>
                <li>Unusual circumstances or first-of-its-kind scenario requiring policy interpretation;</li>
                <li>Compliance or regulatory concerns are flagged;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Review by Senior Officers</p>
              <p className="text-slate-600 text-sm mb-1">Level 3 review involves:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Chief Financial Officer (CFO) or designated Finance Head;</li>
                <li>Chief Compliance Officer (CCO) or Compliance Head;</li>
                <li>Legal Counsel or Head of Legal Department;</li>
                <li>Chief Operating Officer (COO) for operational matters;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Collegiate Decision-Making</p>
              <p className="text-slate-600 text-sm mb-1">Level 3 decisions are made collegially through:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Review of comprehensive case file prepared by RPO;</li>
                <li>Independent assessment by each reviewing officer;</li>
                <li>Discussion in Refund Review Committee meeting;</li>
                <li>Consensus-based decision or majority decision where consensus elusive;</li>
                <li>Detailed minutes recording rationale, dissent (if any), and final decision;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Final Authority</p>
              <p className="text-slate-600 text-sm mb-1">For refunds exceeding ₹25,00,000 (Rupees Twenty-Five Lakhs) or involving significant legal exposure, final approval shall rest with:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Managing Director/Chief Executive Officer; or</li>
                <li>Board of Directors or Committee thereof;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(e) Timeline for Level 3</p>
              <p className="text-slate-600 text-sm mb-1">Level 3 review shall be completed within 15 (fifteen) Business Days of escalation, extendable by another 15 (fifteen) Business Days for exceptional complexity.</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">8.5 PMLA/AML Verification Protocol</h4>
          <p className="text-slate-600 mb-2">Given the Platform's obligations under PMLA and SEBI's AML guidelines, every refund request is subjected to mandatory AML screening:</p>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Source of Funds Verification</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Original payment source must be identified and verified;</li>
                <li>Consistency between declared income source and transaction size must be established;</li>
                <li>High-value transactions (exceeding ₹10,00,000) require additional income documentation;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Beneficial Ownership Verification</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>For corporate Stakeholders, beneficial ownership as per Companies (Significant Beneficial Owners) Rules, 2018 must be verified;</li>
                <li>Ultimate beneficiary of refund must be identified and screened;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Sanctions and PEP Screening</p>
              <p className="text-slate-600 text-sm mb-1">All Stakeholders requesting refunds are screened against:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>UN Security Council sanctions lists;</li>
                <li>OFAC (Office of Foreign Assets Control) lists;</li>
                <li>EU sanctions lists;</li>
                <li>Domestic sanctions and debarred entities lists;</li>
                <li>Politically Exposed Persons (PEP) databases;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Transaction Pattern Analysis</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Evaluation of transaction pattern for indicators of structuring, layering, or circular transactions;</li>
                <li>Assessment of business rationale and economic substance of original transaction and refund request;</li>
                <li>Red flags: Multiple small transactions followed by consolidated refund request, frequent refund requests, mismatched transaction purposes;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(e) Enhanced Due Diligence (EDD)</p>
              <p className="text-slate-600 text-sm mb-1">EDD shall be triggered for:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Non-resident Stakeholders or foreign currency transactions;</li>
                <li>Transactions involving high-risk jurisdictions (as per FATF lists);</li>
                <li>Cash-intensive businesses or cash transactions;</li>
                <li>Stakeholders with adverse media reports or litigation history;</li>
                <li>Unusual transaction patterns or circumstances;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(f) Suspicious Transaction Reporting</p>
              <p className="text-slate-600 text-sm mb-1">If verification reveals reasonable grounds to suspect money laundering, terrorist financing, or fraud:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Refund processing shall be suspended immediately;</li>
                <li>Suspicious Transaction Report (STR) shall be filed with FIU-IND within prescribed timelines;</li>
                <li>Law enforcement authorities may be informed;</li>
                <li>Refund shall remain suspended pending clearance from authorities;</li>
                <li>Confidentiality of STR filing shall be strictly maintained;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(g) Refund to Source Account Mandate</p>
              <p className="text-slate-600 text-sm mb-1">As a fundamental AML control:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Refunds shall ordinarily be credited only to the same bank account from which original payment was received;</li>
                <li>Exceptions require CCO approval and additional documentation:
                  <ul className="list-disc pl-6 mt-1 space-y-1">
                    <li>Bank account closure certificate if original account closed;</li>
                    <li>Court order or legal documentation for change of account;</li>
                    <li>Succession documents for refund to legal heirs;</li>
                    <li>Corporate restructuring documents for entity changes;</li>
                  </ul>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">8.6 Documentary Evidence Standards</h4>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Authentication Requirements</p>
              <p className="text-slate-600 text-sm mb-1">Documents must meet the following standards:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Original documents or certified true copies for physical submissions;</li>
                <li>Digital documents must be signed with valid Digital Signature Certificate (DSC) under IT Act, 2000;</li>
                <li>Scanned copies must be clear, complete, and legible;</li>
                <li>Foreign documents must be apostilled or legalized as per Hague Convention;</li>
                <li>Translated documents must be accompanied by certified translations by sworn translators;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Acceptable Forms of Evidence</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Notarized affidavits for factual statements;</li>
                <li>Statutory declarations for specific assertions;</li>
                <li>Expert certificates (CA certificates, legal opinions, technical reports);</li>
                <li>Official communications (SEBI orders, court orders, regulatory notices);</li>
                <li>Business records and correspondence maintained in ordinary course;</li>
                <li>Digital audit trails and system-generated logs;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Evidentiary Weight</p>
              <p className="text-slate-600 text-sm mb-1">The Platform shall assess evidence using reasonable commercial standards:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Higher weight to contemporaneous documents versus post-facto statements;</li>
                <li>Independent third-party evidence preferred over self-serving statements;</li>
                <li>Official/statutory documents carry greater weight than private documents;</li>
                <li>Corroborated evidence preferred over uncorroborated claims;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">8.7 Quality Assurance and Audit</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Internal Audit</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Random sample audit of 10% of processed refund requests quarterly;</li>
                <li>100% audit of refunds exceeding ₹5,00,000;</li>
                <li>Review of rejected requests for consistency and fairness;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Compliance Oversight</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>CCO shall review monthly refund processing reports;</li>
                <li>Exception reports for policy deviations or unusual patterns;</li>
                <li>Annual compliance certification by CCO to Board;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) External Audit</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Statutory auditors shall review refund processes as part of annual audit;</li>
                <li>Specific audit of AML compliance in refund processing;</li>
                <li>Recommendations for process improvements.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
'use client';

import React from 'react';
import { Settings, CreditCard, AlertTriangle, Users } from 'lucide-react';

export default function RefundPolicyPart5() {
  return (
    <section id="part-5" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 5</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">REFUND PROCESSING AND DISBURSEMENT MECHANISMS, MODE OF REFUND, AND FAILED REFUND PROTOCOLS</h2>
      </div>

      {/* Article 11 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Settings className="text-indigo-600" size={20} /> ARTICLE 11: REFUND PROCESSING FRAMEWORK
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">11.1 Processing Architecture and Workflow</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Centralized Refund Management System</p>
            <p className="text-slate-600 mb-1">The Platform operates a centralized, technology-enabled refund management system that:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Maintains complete audit trail of all refund requests from initiation to completion;</li>
              <li>Generates unique Refund Request Number (RRN) for tracking and reference;</li>
              <li>Automates workflow routing based on transaction type, value, and complexity;</li>
              <li>Integrates with banking systems for seamless disbursement;</li>
              <li>Maintains compliance repository for regulatory reporting;</li>
              <li>Enables real-time status tracking for Stakeholders;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Standard Operating Procedures (SOP)</p>
            <p className="text-slate-600 mb-1">All refund processing shall be conducted in accordance with documented SOPs covering:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Receipt and acknowledgment protocols;</li>
              <li>Document verification checklists;</li>
              <li>Escalation matrices and approval hierarchies;</li>
              <li>Quality control and review procedures;</li>
              <li>Disbursement authorization and execution;</li>
              <li>Post-disbursement reconciliation and closure;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Segregation of Duties</p>
            <p className="text-slate-600 mb-1">To ensure internal controls and prevent fraud:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Refund request initiation and approval shall be performed by different personnel;</li>
              <li>Financial approval and disbursement execution shall be segregated;</li>
              <li>Verification and quality control shall be independent of processing team;</li>
              <li>IT system access shall be role-based with audit logging;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(d) Service Level Commitments</p>
            <p className="text-slate-600 mb-2">Subject to receipt of complete documentation and satisfaction of all conditions precedent:</p>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
              <table className="w-full text-sm text-left text-slate-600">
                <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-6 py-3">Refund Category</th>
                    <th className="px-6 py-3">Acknowledgment</th>
                    <th className="px-6 py-3">Assessment & Decision</th>
                    <th className="px-6 py-3">Disbursement (Post-Approval)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Simple/Routine (up to ₹1 lakh)</td><td className="px-6 py-4">1 Business Day</td><td className="px-6 py-4">7 Business Days</td><td className="px-6 py-4">3 Business Days</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Standard (₹1-10 lakhs)</td><td className="px-6 py-4">2 Business Days</td><td className="px-6 py-4">15 Business Days</td><td className="px-6 py-4">5 Business Days</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Complex (₹10-25 lakhs)</td><td className="px-6 py-4">2 Business Days</td><td className="px-6 py-4">21 Business Days</td><td className="px-6 py-4">7 Business Days</td></tr>
                  <tr className="bg-white"><td className="px-6 py-4">High-Value (above ₹25 lakhs)</td><td className="px-6 py-4">3 Business Days</td><td className="px-6 py-4">30 Business Days</td><td className="px-6 py-4">10 Business Days</td></tr>
                </tbody>
              </table>
            </div>
            <p className="text-slate-600 text-sm italic">Provided that: These timelines are indicative and may be extended in cases involving: Incomplete or deficient documentation; Third-party verification; Regulatory holds; Force Majeure; Banking system delays; Disputed facts.</p>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">11.2 Approval Authority Matrix</h4>
          <p className="text-slate-600 mb-2">The following approval matrix shall govern refund authorizations:</p>
          <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
            <table className="w-full text-sm text-left text-slate-600">
              <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="px-6 py-3">Refund Amount</th>
                  <th className="px-6 py-3">Primary Approver</th>
                  <th className="px-6 py-3">Secondary Approver</th>
                  <th className="px-6 py-3">Final Authority</th>
                </tr>
              </thead>
              <tbody>
                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Up to ₹50,000</td><td className="px-6 py-4">Refund Processing Officer</td><td className="px-6 py-4">Team Lead - Refunds</td><td className="px-6 py-4">Not Required</td></tr>
                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">₹50,001 to ₹5,00,000</td><td className="px-6 py-4">Team Lead - Refunds</td><td className="px-6 py-4">Manager - Operations</td><td className="px-6 py-4">Not Required</td></tr>
                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">₹5,00,001 to ₹25,00,000</td><td className="px-6 py-4">Manager - Operations</td><td className="px-6 py-4">Chief Financial Officer</td><td className="px-6 py-4">Chief Compliance Officer (Concurrence)</td></tr>
                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">₹25,00,001 to ₹1,00,00,000</td><td className="px-6 py-4">Chief Financial Officer</td><td className="px-6 py-4">Chief Operating Officer</td><td className="px-6 py-4">Managing Director/CEO</td></tr>
                <tr className="bg-white"><td className="px-6 py-4">Above ₹1,00,00,000</td><td className="px-6 py-4">CFO + COO (Joint)</td><td className="px-6 py-4">Managing Director/CEO</td><td className="px-6 py-4">Board of Directors/Board Committee</td></tr>
              </tbody>
            </table>
          </div>
          <p className="text-slate-700 font-semibold mb-1">Special Cases Requiring Enhanced Approval:</p>
          <ul className="list-disc pl-6 text-slate-600 space-y-1">
            <li>Fraud allegations: CCO mandatory approval regardless of amount;</li>
            <li>Litigation matters: Legal Head mandatory approval;</li>
            <li>Regulatory non-compliance: CCO and Legal Head joint approval;</li>
            <li>Precedent-setting cases: MD/CEO approval regardless of amount;</li>
            <li>Policy deviations: Board Committee approval;</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">11.3 Communication and Stakeholder Engagement</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Acknowledgment Communication:</strong> Email confirmation with RRN and estimated processing timeline; SMS alert to registered mobile number; User Account dashboard update reflecting request status; Checklist of documents received and any deficiencies identified;</li>
            <li>(b) <strong>Status Updates:</strong> At each significant milestone: Document verification completed; Request forwarded for approval; Approval granted/additional information required/rejection; Disbursement initiated; Refund completed;</li>
            <li>(c) <strong>Deficiency Communication:</strong> If documentation incomplete: Detailed deficiency memorandum specifying missing/inadequate documents; Deadline for curing deficiencies (typically 15 days); Consequences of non-compliance (rejection of request); Contact details for clarifications;</li>
            <li>(d) <strong>Approval/Rejection Communication:</strong> Final decision communicated through: Formal decision letter via email and registered post; Detailed reasoning for rejection (if applicable); Breakup of refund calculation (if approved); Expected disbursement date and mode; Grievance redressal mechanism if Stakeholder dissatisfied;</li>
            <li>(e) <strong>Relationship Management for High-Value Clients:</strong> Dedicated relationship manager assigned; Proactive status updates via phone/email; Facilitation of documentation and clarifications; Senior management accessibility for escalations;</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">11.4 Record Maintenance and Documentation</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Comprehensive Record Keeping:</strong> The Platform shall maintain the following records for each refund request: Complete application file with all submitted documents; Internal processing notes, assessment reports, and recommendations; Approval records with date, time, and authorized signatory details; Communication trail (emails, letters, SMS, call logs); Payment processing documents (disbursement instructions, bank confirmations); Post-disbursement reconciliation and closure documentation;</li>
            <li>(b) <strong>Retention Period:</strong> Minimum 5 (five) years from date of refund completion for routine cases; Minimum 8 (eight) years for high-value refunds (above ₹25 lakhs); Indefinite retention for cases involving fraud, litigation, or regulatory proceedings; As mandated under specific statutes (PMLA: 5 years; Companies Act: 8 years; Income Tax: 6 years);</li>
            <li>(c) <strong>Data Security and Confidentiality:</strong> All records maintained in secure, encrypted digital repository; Physical records stored in restricted-access secure locations; Access controls based on need-to-know and role-based permissions; Regular backup and disaster recovery protocols; Compliance with IT Act, 2000 and data protection regulations;</li>
            <li>(d) <strong>Regulatory Reporting:</strong> Periodic reports to internal audit and compliance; Exception reports for high-value, delayed, or rejected refunds; Annual statistical reporting to Board of Directors; Regulatory filings as required by SEBI, RBI, FIU-IND; Production of records to auditors, regulators, or courts as mandated;</li>
          </ul>
        </div>
      </div>

      {/* Article 12 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <CreditCard className="text-indigo-600" size={20} /> ARTICLE 12: MODES AND MECHANISMS OF REFUND DISBURSEMENT
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">12.1 Permitted Modes of Refund</h4>
          <p className="text-slate-600 mb-2">Refunds shall be processed exclusively through the following modes:</p>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Electronic Fund Transfer (Primary Mode)</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>RTGS (Real Time Gross Settlement):</strong>
                <ul className="list-disc pl-6 mt-1 space-y-1">
                  <li>For refunds of ₹2,00,000 and above;</li>
                  <li>Real-time, irrevocable fund transfer;</li>
                  <li>Processed during RBI-specified RTGS hours (currently 7:00 AM to 6:00 PM on Business Days);</li>
                  <li>Charges: As per Platform's banker's schedule, typically ₹25-50 per transaction;</li>
                </ul>
              </li>
              <li>(ii) <strong>NEFT (National Electronic Funds Transfer):</strong>
                <ul className="list-disc pl-6 mt-1 space-y-1">
                  <li>For refunds below ₹2,00,000 or as preferred by Stakeholder;</li>
                  <li>Batch processing system with multiple settlement cycles;</li>
                  <li>Available 24×7 including holidays (as per RBI guidelines effective December 2019);</li>
                  <li>Charges: Typically ₹2-25 per transaction based on amount;</li>
                </ul>
              </li>
              <li>(iii) <strong>IMPS (Immediate Payment Service):</strong>
                <ul className="list-disc pl-6 mt-1 space-y-1">
                  <li>For urgent refunds up to ₹5,00,000;</li>
                  <li>Instant, 24×7 fund transfer;</li>
                  <li>Higher transaction charges: ₹5-15 per transaction;</li>
                  <li>Available subject to beneficiary bank's IMPS enablement;</li>
                </ul>
              </li>
              <li>(iv) <strong>UPI (Unified Payments Interface):</strong>
                <ul className="list-disc pl-6 mt-1 space-y-1">
                  <li>For small-value refunds up to ₹1,00,000;</li>
                  <li>Instant transfer to UPI-linked bank account;</li>
                  <li>Subject to beneficiary's UPI registration;</li>
                  <li>Minimal or no charges;</li>
                </ul>
              </li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Demand Draft/Pay Order (Exceptional Cases Only)</p>
            <p className="text-slate-600 mb-1">Available only where electronic transfer not feasible due to:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Beneficiary bank not connected to RTGS/NEFT network (rare for scheduled commercial banks);</li>
              <li>Technical issues preventing electronic transfer after multiple attempts;</li>
              <li>Stakeholder's specific request with justification;</li>
              <li>Foreign beneficiaries in jurisdictions with restricted banking access;</li>
            </ul>
            <p className="text-slate-600 mt-1 mb-1">Conditions:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Demand draft drawn on scheduled commercial bank;</li>
              <li>Issued in favor of Stakeholder as per KYC records;</li>
              <li>Crossed "Account Payee Only" for security;</li>
              <li>Dispatched through registered post/insured courier;</li>
              <li>Additional charges (DD issuance ₹50-100, courier ₹100-200) borne by Stakeholder;</li>
              <li>Processing time: Additional 5-7 Business Days;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Account Credit (Platform Wallet/Virtual Account)</p>
            <p className="text-slate-600 mb-1">For future adjustments or credits to Platform's internal wallet:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Available only with Stakeholder's explicit written consent;</li>
              <li>Credit reflects immediately in User Account;</li>
              <li>Can be utilized for future transactions on Platform;</li>
              <li>No expiry date unless specifically agreed;</li>
              <li>Cannot be withdrawn as cash; only usable for Platform services;</li>
              <li>GST implications: Credit note issued for tax purposes;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(d) Prohibited Modes</p>
            <p className="text-slate-600 mb-1">The following refund modes are strictly prohibited:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Cash refunds (violates PMLA and creates audit/security risks);</li>
              <li>Cryptocurrency or digital currency transfers;</li>
              <li>Third-party payment wallets not verified in KYC;</li>
              <li>Cheques (phased out due to security and processing issues);</li>
              <li>Barter or in-kind compensation (except as specifically agreed);</li>
              <li>Refund to accounts other than source account or KYC-verified accounts (except approved exceptions);</li>
            </ul>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">12.2 Beneficiary Account Validation</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Mandatory Validations</p>
            <p className="text-slate-600 mb-1">Before processing refund, the Platform shall verify:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>Account Ownership Verification:</strong> Account holder name matches Stakeholder's name as per KYC records; For company accounts: Matches registered company name; Penny drop test (₹1 deposit to verify account active and name match); Bank account verification API or manual confirmation from bank;</li>
              <li>(ii) <strong>Source Account Linkage:</strong> Primary rule: Refund to same account from which payment originated; Cross-verification with original payment transaction records; Exception approval required for refund to different account;</li>
              <li>(iii) <strong>IFSC Code Validation:</strong> Verification against RBI's IFSC database; Branch address and bank name confirmation; Inactive or invalid IFSC codes flagged for correction;</li>
              <li>(iv) <strong>Account Type Verification:</strong> Savings, current, NRO, NRE account type confirmation; Special handling for NRE/NRO accounts under FEMA; Corporate accounts verified against CoI/certificate of incorporation;</li>
              <li>(v) <strong>Blacklist and Sanctions Screening:</strong> Account not appearing in internal blacklist or fraud database; Not linked to OFAC, UN, or other sanctions lists; Not flagged in CIBIL/credit bureau negative databases;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Change of Bank Account Requests</p>
            <p className="text-slate-600 mb-1">Where Stakeholder requests refund to account different from source account:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>Documentation Requirements:</strong> Written request on Stakeholder's letterhead (for corporate) or signed application (for individuals); Cancelled cheque or bank statement of new account; Notarized declaration stating reasons for account change; Self-declaration that new account is owned by Stakeholder;</li>
              <li>(ii) <strong>Acceptable Reasons:</strong> Original account closed (with bank closure certificate); Original account frozen/attached by court order (with court order copy); Original account was error/fraud victim (with police complaint/FIR); Original payment made from third-party account in error (with third-party consent); Corporate restructuring, merger, or name change (with ROC documents); FEMA-related account type change (NRE to NRO or vice versa);</li>
              <li>(iii) <strong>Enhanced Verification:</strong> CCO approval mandatory; Personal discussion/video verification with Stakeholder; Additional PMLA/AML checks; Retention of detailed documentation for audit trail;</li>
              <li>(iv) <strong>Rejection Grounds:</strong> Suspicious circumstances indicating money laundering; Request for refund to third-party account (except approved scenarios); Inadequate documentation or justification; Inconsistencies in Stakeholder's representations;</li>
            </ul>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">12.3 Cross-Border and Foreign Currency Refunds</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) FEMA Compliance Framework</p>
            <p className="text-slate-600 mb-1">For refunds involving foreign currency or non-resident Stakeholders:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>Regulatory Approvals:</strong> All cross-border refunds subject to FEMA provisions; RBI approval required for transactions exceeding USD 250,000 or equivalent; Foreign investment refunds comply with FDI/FPI regulations; LRS (Liberalized Remittance Scheme) limits applicable for resident remitters;</li>
              <li>(ii) <strong>Authorized Dealer Bank:</strong> Refunds processed through Platform's authorized dealer bank; Form A2 filing for outward remittances above USD 25,000; FIRC (Foreign Inward Remittance Certificate) or e-FIRC for documentation; Swift codes, IBAN, correspondent bank details verified;</li>
              <li>(iii) <strong>Tax Compliance:</strong> TDS on interest component as per Section 195, Income Tax Act; Certificate from Chartered Accountant for certain remittances; Tax clearance certificate if required under IT Act; GST on services rendered outside India (export of services provisions);</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Exchange Rate Application</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>Applicable Rate:</strong> RBI reference rate or card rate of Platform's banker on date of refund processing; For forward contracts or hedging: Rate as per contract terms; Exchange rate fluctuation risk borne by Stakeholder;</li>
              <li>(ii) <strong>Currency Conversion Charges:</strong> Typical forex margin: 0.5% to 2% depending on currency; SWIFT/wire transfer charges: USD 10-50 or equivalent; Correspondent bank charges (if any) deducted from refund;</li>
              <li>(iii) <strong>Documentation:</strong> Foreign currency refund advice with exchange rate details; FIRC or equivalent for audit trail; Compliance with FEMA reporting requirements;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) NRI/NRE/NRO Accounts</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>NRE Account Refunds:</strong> Only repatriable amounts credited to NRE accounts; Original investment must have been made from NRE/FCNR accounts; Full repatriation permitted subject to documentation;</li>
              <li>(ii) <strong>NRO Account Refunds:</strong> Non-repatriable amounts credited to NRO accounts; Repatriation subject to USD 1 million per financial year limit; Certificate from Chartered Accountant for amounts exceeding limit;</li>
              <li>(iii) <strong>Resident Status Change:</strong> If Stakeholder's residential status changed: Updated FEMA declaration; Refund account type must match current residential status; RBI compliance certification where status changed mid-transaction;</li>
            </ul>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">12.4 Refund Processing and Reconciliation</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Disbursement Authorization:</strong> (i) Dual Authorization: Refund payment instruction requires two authorized signatories; Maker-checker system: One person initiates, another approves; IT system enforces dual authorization control; (ii) Payment Batch Processing: Refunds consolidated in daily payment batches; Batch reviewed by Finance team before submission to bank; High-value refunds (above ₹25 lakhs) processed individually; (iii) Banking System Integration: API integration with corporate internet banking platform; Bulk upload facility for multiple refunds; Real-time status tracking of payment instructions; Auto-reconciliation with bank statements;</li>
            <li>(b) <strong>Payment Confirmation and Notification:</strong> (i) Bank Confirmation: UTR (Unique Transaction Reference) number obtained from bank; Credit confirmation received from beneficiary bank; RTGS/NEFT message acknowledgment retained; (ii) Stakeholder Notification: Email with payment details (date, amount, UTR, mode); SMS alert upon successful credit; User Account dashboard updated with completed status; Formal refund completion certificate issued on request; (iii) Acknowledgment Request: Stakeholder requested to confirm receipt within 3 Business Days; Non-receipt to be reported immediately for investigation; Bank statement as proof of credit required if dispute arises;</li>
            <li>(c) <strong>Reconciliation Process:</strong> (i) Daily Reconciliation: Approved refunds vs. actual bank debits reconciled daily; Outstanding payment instructions tracked and followed up; Failed transactions identified and investigated; (ii) Monthly Reconciliation: Complete refund register reconciled with accounting books; Outstanding refunds aging analysis; Variances investigated and resolved; Report to CFO and internal audit; (iii) Audit Trail Maintenance: Complete trail from approval to bank credit maintained; Document management system with version control; Timestamps and authorized user logs recorded; Compliance with IT Act, 2000 electronic records requirements;</li>
          </ul>
        </div>
      </div>

      {/* Article 13 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <AlertTriangle className="text-indigo-600" size={20} /> ARTICLE 13: FAILED REFUND PROTOCOLS AND REMEDIAL MEASURES
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">13.1 Definition and Categories of Failed Refunds</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Failed Refund Defined</p>
            <p className="text-slate-600 mb-1">A refund shall be deemed "failed" when:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Payment instruction returns unexecuted with failure message from banking system;</li>
              <li>Beneficiary account unable to receive credit (account closed, frozen, invalid);</li>
              <li>Technical error in payment processing system prevents completion;</li>
              <li>Third-party intermediary (payment gateway, correspondent bank) rejects transaction;</li>
              <li>Refund amount credited is subsequently reversed or returned;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Categories of Failure</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>Technical Failures:</strong> System downtime or technical glitches; Network connectivity issues; Software bugs or processing errors; API integration failures;</li>
              <li>(ii) <strong>Beneficiary Account Issues:</strong> Account closed or dormant; Incorrect account number or IFSC code; Account frozen by bank or regulatory authority; Account type restrictions (e.g., PPF account cannot receive business credits); Beneficiary bank technical issues;</li>
              <li>(iii) <strong>Regulatory/Compliance Failures:</strong> AML/KYC compliance holds by beneficiary bank; FEMA violations flagged; Court attachment or garnishment orders; Regulatory sanctions or restrictions;</li>
              <li>(iv) <strong>Banking System Failures:</strong> RTGS/NEFT system downtime; Beneficiary bank not responding/offline; Clearing system failures; Settlement delays or errors;</li>
            </ul>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">13.2 Failure Detection and Notification</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Real-Time Monitoring:</strong> The Platform's refund management system: Monitors payment status in real-time through bank API integration; Receives instant alerts for failed transactions; Auto-flags accounts with repeat failures; Generates exception reports for investigation;</li>
            <li>(b) <strong>Stakeholder Notification:</strong> Upon refund failure: Immediate email and SMS notification to Stakeholder within 24 hours; Detailed failure reason based on bank's return message; Specific corrective action required from Stakeholder; Deadline for providing rectified details (typically 7 Business Days); Consequences of non-compliance (closure of refund request); Helpdesk contact for assistance;</li>
            <li>(c) <strong>Internal Escalation:</strong> Failed refunds escalated internally: To Refund Processing Officer's supervisor within 1 Business Day; To Finance Manager if failure due to technical/banking issues; To Compliance team if failure due to regulatory reasons; To senior management if high-value or repeat failures;</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">13.3 Remedial Action Framework</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Stakeholder-Rectifiable Failures</p>
            <p className="text-slate-600 mb-1">Where failure due to incorrect details provided by Stakeholder:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>Correction Process:</strong> Stakeholder submits rectified bank details within 7 Business Days; Fresh cancelled cheque or bank statement for new details; Declaration that revised details are accurate; Platform verifies new details through penny drop test;</li>
              <li>(ii) <strong>Re-Processing:</strong> Corrected refund reprocessed within 3 Business Days; No fresh approval required if amount unchanged; If multiple failures due to Stakeholder error: Re-processing charges (Article 9.5(d)) applied;</li>
              <li>(iii) <strong>Alternate Mode:</strong> If electronic transfer repeatedly fails: Demand draft option offered; Additional DD and courier charges (approximately ₹150-250) to be borne by Stakeholder; Extended processing time (5-7 Business Days) applicable;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Platform/Bank-Related Failures</p>
            <p className="text-slate-600 mb-1">Where failure not attributable to Stakeholder:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>Immediate Re-Initiation:</strong> Platform re-initiates payment immediately upon failure detection; No additional documentation or approval required; Multiple attempts through alternate banking channels;</li>
              <li>(ii) <strong>Alternate Banking Route:</strong> If Platform's primary banker unable to process: Alternate bank utilized; Multiple banking relationships maintained for redundancy; SWIFT transfers for international refunds if wire transfer fails;</li>
              <li>(iii) <strong>Zero-Cost to Stakeholder:</strong> No additional charges for re-processing; Interest on delayed refund continues to accrue (Article 9.8); Platform bears all bank charges and repeat transaction costs;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Regulatory/Compliance Hold</p>
            <p className="text-slate-600 mb-1">Where failure due to regulatory hold or compliance issue:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>Investigation and Resolution:</strong> Compliance team investigates reason for hold; Liaison with regulatory authorities if required; Stakeholder required to provide additional documentation/clarifications; Legal opinion obtained if complex regulatory issue;</li>
              <li>(ii) <strong>Conditional Processing:</strong> Refund processed upon obtaining regulatory clearance; May require NOC or approval from SEBI/RBI/other authority; Stakeholder responsible for cooperating in obtaining clearances; Timeline extended commensurate with regulatory process;</li>
              <li>(iii) <strong>Refusal if Non-Compliant:</strong> If refund would violate Applicable Law: Request rejected; Detailed reasoning provided to Stakeholder; Alternate compliant resolution explored (e.g., structured payout, escrow); Funds retained pending compliance or court/authority directions;</li>
            </ul>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">13.4 Unclaimed and Dormant Refunds</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Definition of Unclaimed Refund</p>
            <p className="text-slate-600 mb-1">A refund becomes "unclaimed" when:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Payment fails and Stakeholder does not respond to notifications within 30 days;</li>
              <li>Stakeholder cannot be contacted despite multiple attempts;</li>
              <li>Stakeholder explicitly refuses to accept refund;</li>
              <li>Demand draft issued but not encashed within 3 months;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Dormancy Treatment</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>Extended Outreach:</strong> Additional communication attempts: Registered post to all known addresses; Phone calls to all registered contact numbers; Email to all email addresses on record; Public notice on Platform's website (for high-value unclaimed refunds);</li>
              <li>(ii) <strong>Holding Period:</strong> Unclaimed refunds held in separate suspense account; Detailed register of unclaimed refunds maintained; Monthly review and follow-up attempts; Annual reporting to senior management and Board;</li>
              <li>(iii) <strong>Fund Transfer to Investor Protection Fund:</strong> After 3 (three) years of unclaimed status: Consideration of transfer to Investor Education and Protection Fund (IEPF) if applicable; As per Section 125 of Companies Act, 2013 (if Platform is a company); Compliance with SEBI guidelines on investor protection; Stakeholder retains right to claim from IEPF subsequently;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Stakeholder's Right to Claim</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>No Time Bar for Legitimate Claims:</strong> Stakeholder's right to claim unclaimed refund not extinguished by time (subject to limitation under law); Refund processed upon Stakeholder coming forward with valid credentials; KYC and identity re-verification required; Interest accrual stopped after transfer to suspense account;</li>
              <li>(ii) <strong>Claim Process:</strong> Application with fresh KYC documents; Proof of identity and ownership of original transaction; Explanation for prior non-responsiveness; Processing as fresh refund request with expedited timeline;</li>
              <li>(iii) <strong>Recovery from IEPF:</strong> If transferred to IEPF: Stakeholder to follow IEPF refund process; Platform assists with documentation and certification; IEPF processing time (typically 3-6 months) applicable;</li>
            </ul>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">13.5 Dispute Resolution for Failed Refunds</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Internal Grievance Mechanism</p>
            <p className="text-slate-600 mb-1">Stakeholder dissatisfied with failure resolution may:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Escalate to Grievance Redressal Officer (contact details on Platform website);</li>
              <li>File formal complaint through User Account grievance portal;</li>
              <li>Expect written response within 7 Business Days;</li>
              <li>Further escalation to Chief Compliance Officer if unsatisfied;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Banking Ombudsman</p>
            <p className="text-slate-600 mb-1">For bank-related issues:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Stakeholder may approach Banking Ombudsman as per RBI scheme;</li>
              <li>Platform to cooperate with Ombudsman inquiry;</li>
              <li>Ombudsman's decision binding on Platform if within jurisdiction;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Consumer Forum/Court</p>
            <p className="text-slate-600 mb-1">Stakeholder retains rights to approach:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Consumer Disputes Redressal Forum under Consumer Protection Act, 2019;</li>
              <li>Civil courts for breach of contract claims;</li>
              <li>Arbitration as per Article 15 of this Policy (covered in subsequent parts);</li>
            </ul>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">13.6 Technical Failures and System Resilience</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Business Continuity Planning:</strong> Platform maintains: Redundant banking relationships for payment processing; Backup payment gateways and APIs; Disaster recovery site for critical systems; Manual processing procedures for system downtime scenarios;</li>
            <li>(b) <strong>Service Level with Bankers:</strong> Platform's agreements with banker include: Committed uptime for payment systems (typically 99.5%); Prioritized processing for critical payments; Dedicated relationship manager for issue resolution; Real-time alerts for system issues;</li>
            <li>(c) <strong>Technology Upgrades:</strong> Regular investment in: Payment system automation and API upgrades; Cybersecurity measures to prevent fraud; Data analytics for predictive failure detection; User interface enhancements for Stakeholder experience;</li>
          </ul>
        </div>
      </div>

      {/* Article 14 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Users className="text-indigo-600" size={20} /> ARTICLE 14: SPECIAL DISBURSEMENT SCENARIOS
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">14.1 Refunds to Legal Heirs and Successors</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Applicability</p>
            <p className="text-slate-600 mb-1">Where Stakeholder (individual) dies before refund processed:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>Mandatory Documentation:</strong> Death certificate issued by competent municipal authority; Succession certificate issued by civil court; OR Letters of administration granted by court; OR Probated will naming legal heirs; Indemnity bond from legal heirs; Affidavit of legal heirs on stamp paper;</li>
              <li>(ii) <strong>Quantum-Based Requirements:</strong> Up to ₹2,00,000: Succession certificate may be waived with bank's legal heir certificate and indemnity; ₹2,00,001 to ₹15,00,000: Succession certificate or probated will mandatory; Above ₹15,00,000: Court-issued succession certificate mandatory;</li>
              <li>(iii) <strong>Verification:</strong> All legal heirs identified and verified; Consent of all legal heirs or court-approved distribution; Refund split as per succession certificate/will; Individual bank accounts verified for each heir;</li>
              <li>(iv) <strong>Processing Timeline:</strong> Extended by 30 days for legal heir documentation verification; Legal opinion obtained for complex succession issues; No interest during extended period if delay due to heir documentation;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Refunds to Corporate Successors</p>
            <p className="text-slate-600 mb-1">In case of corporate restructuring:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>Merger/Amalgamation:</strong> Certified copy of merger order by NCLT; Updated Certificate of Incorporation; Board resolution authorizing successor entity; Refund to successor entity's bank account;</li>
              <li>(ii) <strong>Liquidation/Insolvency:</strong> Liquidator's appointment order; Liquidator's claim and bank account details; Ranking of refund claim in liquidation waterfall; Coordination with IBC proceedings;</li>
            </ul>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">14.2 Refunds Under Court Orders</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Attachment and Garnishee Orders</p>
            <p className="text-slate-600 mb-1">If refund amount attached by court order:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>Compliance Mandate:</strong> Immediate freeze on refund processing; Compliance with court directions strictly; Refund credit to court-designated account; Intimation to Stakeholder about court order;</li>
              <li>(ii) <strong>Objection Process:</strong> Stakeholder may approach court to vacate attachment; Platform bound by court orders, not Stakeholder's objections; Refund released only upon court's direction;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Specific Performance or Decree</p>
            <p className="text-slate-600 mb-1">If court orders specific refund:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>Decree Execution:</strong> Court decree as sufficient authorization; Verification of decree authenticity; Refund processed as per decree terms; Court fee and execution costs as per court directions;</li>
              <li>(ii) <strong>Priority Processing:</strong> Court-ordered refunds given priority; Processed within timeline specified in court order; Non-compliance attracts contempt proceedings;</li>
            </ul>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">14.3 Partial Refunds and Installment Refunds</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Circumstances for Partial Refund</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Transaction partially executed;</li>
              <li>Services partially consumed;</li>
              <li>Stakeholder requests partial withdrawal;</li>
              <li>Phased project with milestone-wise cancellation;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Calculation:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Pro-rata basis for consumed vs. total services;</li>
              <li>Each partial refund subject to processing charges;</li>
              <li>Minimum threshold: ₹5,000 per installment;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Installment Refunds</p>
            <p className="text-slate-600 mb-1">For very high-value refunds (above ₹1 crore):</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Platform may propose installment refunds over 3-6 months;</li>
              <li>Requires Stakeholder's consent;</li>
              <li>Interest paid on outstanding installments;</li>
              <li>Structured payment schedule documented;</li>
              <li>Applicable where Platform's liquidity constraints;</li>
            </ul>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">14.4 Refunds Through Escrow Release</h4>
          <p className="text-slate-600 mb-1">Where original payment held in escrow:</p>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) Escrow agreement terms govern release;</li>
            <li>(b) Escrow agent processes refund independently;</li>
            <li>(c) Platform provides refund approval to escrow agent;</li>
            <li>(d) Escrow fees as per escrow agreement;</li>
            <li>(e) Timeline subject to escrow processing (typically 5-7 Business Days);</li>
          </ul>
        </div>
      </div>
    </section>
  );
}
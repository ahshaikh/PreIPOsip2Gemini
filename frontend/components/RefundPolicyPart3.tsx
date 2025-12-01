'use client';

import React from 'react';
import { Clock, ClipboardList } from 'lucide-react';

export default function RefundPolicyPart3() {
  return (
    <section id="part-3" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 3</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">TEMPORAL LIMITATIONS AND REFUND REQUEST PROCEDURES</h2>
      </div>

      {/* ARTICLE 6 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Clock className="text-indigo-600" size={20} /> ARTICLE 6: TEMPORAL LIMITATIONS AND TIME-BOUND REFUND RIGHTS
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">6.1 Limitation Periods for Refund Requests</h4>
          <p className="text-slate-600 mb-2">No refund request shall be entertained unless submitted within the following limitation periods:</p>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Investment Transactions:</strong> Within 90 (ninety) days from the date on which the refund-triggering event occurred or ought reasonably to have been discovered by the Stakeholder;</li>
            <li>(b) <strong>Advisory Services:</strong> Within 30 (thirty) days from the date of service delivery or scheduled delivery date;</li>
            <li>(c) <strong>Subscription Services:</strong> Within 15 (fifteen) days from the end of the billing cycle in which the refund ground arose;</li>
            <li>(d) <strong>Technical Errors:</strong> Within 7 (seven) Business Days from the date of erroneous transaction;</li>
            <li>(e) <strong>Statutory or Regulatory Grounds:</strong> Within the limitation period prescribed under Applicable Law or 180 (one hundred eighty) days from discovery, whichever is earlier.</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">6.2 Effect of Expiry of Limitation Period</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) Refund requests submitted after expiry of applicable limitation period shall be summarily rejected;</li>
            <li>(b) Time shall not be extended except:
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>By mutual written agreement between Platform and Stakeholder;</li>
                <li>By order of competent court or arbitral tribunal;</li>
                <li>Where delay was caused by Force Majeure Event affecting the Stakeholder;</li>
                <li>Where Platform's conduct prevented timely submission (equitable estoppel);</li>
              </ul>
            </li>
            <li>(c) The Platform may, in its sole and absolute discretion, consider time-barred requests on an ex-gratia basis without creating any precedent or obligation.</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">6.3 Processing Timelines</h4>
          <p className="text-slate-600 mb-2">The Platform commits to the following processing timelines, subject to satisfactory verification and compliance:</p>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Acknowledgment:</strong> Within 2 (two) Business Days of receipt of refund request;</li>
            <li>(b) <strong>Initial Assessment:</strong> Within 5 (five) Business Days of acknowledgment;</li>
            <li>(c) <strong>Decision Communication:</strong> Within 15 (fifteen) Business Days for simple cases; 30 (thirty) Business Days for complex cases requiring third-party verification;</li>
            <li>(d) <strong>Refund Disbursement:</strong> Within 7 (seven) Business Days of approval, subject to banking timelines and RTGS/NEFT processing schedules.</li>
          </ul>
        </div>
      </div>

      {/* ARTICLE 7 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <ClipboardList className="text-indigo-600" size={20} /> ARTICLE 7: REFUND REQUEST PROCEDURES
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">7.1 Mandatory Procedural Compliance</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-3">
            <li className="text-justify">
              (a) <strong>Exclusive Channel Requirement:</strong> All refund requests must be submitted exclusively through the designated channels specified by the Platform. Requests submitted through unauthorized channels, including but not limited to personal emails to employees, social media platforms, or verbal communications, shall not be recognized or processed;
            </li>
            <li className="text-justify">
              (b) <strong>Written Form Requirement:</strong> Every refund request must be in writing, whether submitted electronically through the User Account portal or physically through registered post/courier to the Platform's registered office address;
            </li>
            <li className="text-justify">
              (c) <strong>Prescribed Format:</strong> Refund requests must be submitted using the prescribed format available on the Platform's website under the "Refunds and Cancellations" section. Requests not conforming to the prescribed format may be rejected or returned for resubmission;
            </li>
            <li className="text-justify">
              (d) <strong>No Third-Party Submissions:</strong> Refund requests must be submitted by the Stakeholder personally or by a duly authorized representative holding a valid power of attorney, notarized and apostilled as per Applicable Law. The Platform reserves the right to reject requests submitted by unauthorized third parties.
            </li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">7.2 Online Refund Request Submission Process</h4>
          <p className="text-slate-600 mb-2">For requests submitted through the User Account portal:</p>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Login and Authentication</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Stakeholder must login to User Account using registered credentials;</li>
                <li>Two-factor authentication (2FA) must be completed where enabled;</li>
                <li>OTP verification may be required for high-value refund requests (exceeding ₹1,00,000);</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Navigation and Form Access</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Navigate to "My Transactions" or "My Account" section;</li>
                <li>Select the specific transaction for which refund is sought;</li>
                <li>Click on "Request Refund" or "Initiate Refund Process" button;</li>
                <li>Complete the online refund request form with all mandatory fields;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Mandatory Form Fields</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Transaction ID or Reference Number;</li>
                <li>Date of original transaction;</li>
                <li>Amount paid and amount claimed as refund;</li>
                <li>Category of refund (as per Article 4.2);</li>
                <li>Specific ground for refund with detailed description (minimum 100 words);</li>
                <li>Supporting documents upload (see Article 7.4);</li>
                <li>Bank account details for refund credit (must match KYC records);</li>
                <li>Declaration and undertaking as prescribed;</li>
                <li>Digital signature or electronic acceptance;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Submission and Acknowledgment</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Review all information for accuracy before submission;</li>
                <li>Submit the form electronically;</li>
                <li>System-generated acknowledgment with unique refund request number (RRN) will be displayed;</li>
                <li>Acknowledgment email will be sent to registered email address within 24 hours;</li>
                <li>SMS notification will be sent to registered mobile number;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(e) Tracking and Status Updates</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Use RRN to track refund request status online;</li>
                <li>Status updates will be provided at each stage: Received → Under Review → Verification → Approved/Rejected → Processed;</li>
                <li>Email and SMS notifications will be sent at critical milestones.</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">7.3 Physical/Offline Refund Request Submission Process</h4>
          <p className="text-slate-600 mb-2">For requests submitted through registered post/courier:</p>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Document Preparation</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Download prescribed refund request form from Platform's website;</li>
                <li>Complete form manually with clear, legible handwriting or typed content;</li>
                <li>Affix signature on each page and on designated signature fields;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Supporting Documentation</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Attach all supporting documents in original or notarized copies;</li>
                <li>Include self-attested copies of identity proof and address proof;</li>
                <li>Enclose cancelled cheque or bank statement for refund account verification;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Dispatch and Delivery</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Send documents through registered post with acknowledgment due or reputed courier service;</li>
                <li>Address to: "The Compliance Officer - Refunds, [Legal Entity Name], [Complete Registered Office Address]";</li>
                <li>Mark envelope as "REFUND REQUEST - URGENT";</li>
                <li>Retain proof of dispatch (courier receipt/post office receipt);</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Processing Timeline</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Physical requests will be processed within 3 (three) Business Days of receipt at registered office;</li>
                <li>Data entry and digitization will be completed within this period;</li>
                <li>Subsequent processing will follow online timelines;</li>
                <li>Acknowledgment will be sent via email and post within 5 (five) Business Days.</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">7.4 Mandatory Documentation Requirements</h4>
          <p className="text-slate-600 mb-2">All refund requests must be accompanied by the following documents, failing which the request shall be deemed incomplete and liable for rejection:</p>
          
          <div className="ml-4 space-y-6">
            
            {/* 7.4(a) */}
            <div>
              <p className="text-slate-700 font-semibold mb-2">(a) Universal/Common Documents (Required for All Requests)</p>
              
              <div className="ml-4 space-y-3">
                <div>
                  <p className="text-slate-700 italic">(i) Identity and KYC Documents</p>
                  <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                    <li>Copy of PAN Card (mandatory for all financial transactions);</li>
                    <li>Copy of Aadhaar Card or Passport or Voter ID or Driving License;</li>
                    <li>Recent passport-sized photograph (if not already on file);</li>
                    <li>Proof of registered email address and mobile number;</li>
                  </ul>
                </div>
                <div>
                  <p className="text-slate-700 italic">(ii) Transaction Documents</p>
                  <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                    <li>Original transaction receipt, invoice, or payment confirmation;</li>
                    <li>Transaction statement downloaded from User Account;</li>
                    <li>Bank statement showing debit of original payment;</li>
                    <li>Email confirmation of transaction from Platform (if available);</li>
                  </ul>
                </div>
                <div>
                  <p className="text-slate-700 italic">(iii) Bank Account Verification Documents</p>
                  <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                    <li>Cancelled cheque bearing Stakeholder's name and account details; OR</li>
                    <li>Bank statement (not older than 3 months) showing account holder name, account number, IFSC code, and bank name; OR</li>
                    <li>Bank passbook first page photocopy (self-attested);</li>
                  </ul>
                </div>
                <div>
                  <p className="text-slate-700 italic">(iv) Authorization Documents (if applicable)</p>
                  <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                    <li>Power of Attorney (PoA) if request submitted by authorized representative (must be notarized and on stamp paper of appropriate value);</li>
                    <li>Board Resolution if Stakeholder is a company;</li>
                    <li>Partnership Deed and authorization letter if Stakeholder is a partnership firm;</li>
                    <li>Trust Deed and trustee authorization if Stakeholder is a trust;</li>
                  </ul>
                </div>
              </div>
            </div>

            {/* 7.4(b) */}
            <div>
              <p className="text-slate-700 font-semibold mb-2">(b) Transaction-Specific Documents</p>
              
              <div className="ml-4 space-y-3">
                <div>
                  <p className="text-slate-700 italic">(i) For Pre-IPO Securities Subscription Refunds:</p>
                  <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                    <li>Copy of subscription form/application;</li>
                    <li>Term sheet or offer document;</li>
                    <li>Correspondence with Platform or issuer regarding the transaction;</li>
                    <li>Evidence of ground for refund (e.g., issuer's cancellation notice, regulatory order, etc.);</li>
                    <li>Demat account statement showing non-receipt of securities (if applicable);</li>
                  </ul>
                </div>
                <div>
                  <p className="text-slate-700 italic">(ii) For Unlisted Share Purchase Refunds:</p>
                  <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                    <li>Share Purchase Agreement or Memorandum of Understanding;</li>
                    <li>Seller's consent letter or declination notice (if seller withdrew);</li>
                    <li>Correspondence regarding transaction failure;</li>
                    <li>Evidence of share non-transfer or defective transfer;</li>
                    <li>Legal opinion or valuation report (if relying on misrepresentation/defect);</li>
                  </ul>
                </div>
                <div>
                  <p className="text-slate-700 italic">(iii) For Advisory Services Refunds:</p>
                  <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                    <li>Service agreement or engagement letter;</li>
                    <li>Correspondence showing service deficiency or non-delivery;</li>
                    <li>Evidence of scheduled appointments not honored;</li>
                    <li>Any deliverables received (reports, presentations) demonstrating material deficiency;</li>
                  </ul>
                </div>
                <div>
                  <p className="text-slate-700 italic">(iv) For Subscription/Membership Refunds:</p>
                  <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                    <li>Subscription agreement or Terms of Service acceptance;</li>
                    <li>Service usage logs (if claiming non-usage or under-usage);</li>
                    <li>Evidence of service quality issues or outages;</li>
                    <li>Correspondence with customer support regarding issues;</li>
                  </ul>
                </div>
                <div>
                  <p className="text-slate-700 italic">(v) For Technical Error Refunds:</p>
                  <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                    <li>Screenshot of error message or failed transaction screen;</li>
                    <li>Bank statement showing multiple debits for same transaction;</li>
                    <li>Transaction logs from User Account showing duplicate entries;</li>
                    <li>Any communication with payment gateway or customer support;</li>
                  </ul>
                </div>
              </div>
            </div>

            {/* 7.4(c) */}
            <div>
              <p className="text-slate-700 font-semibold mb-2">(c) Additional Documents for Specific Grounds</p>
              
              <div className="ml-4 space-y-3">
                <div>
                  <p className="text-slate-700 italic">(i) Force Majeure Claims:</p>
                  <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                    <li>Evidence of Force Majeure Event (news articles, government notifications, etc.);</li>
                    <li>Nexus demonstration between Force Majeure Event and transaction failure;</li>
                    <li>Documentary proof of impossibility of performance;</li>
                  </ul>
                </div>
                <div>
                  <p className="text-slate-700 italic">(ii) Regulatory Non-Compliance Claims:</p>
                  <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                    <li>Copy of SEBI/RBI/MCA order, circular, or notification;</li>
                    <li>Legal opinion on applicability of regulatory provision;</li>
                    <li>Evidence that transaction violates specific regulatory requirement;</li>
                  </ul>
                </div>
                <div>
                  <p className="text-slate-700 italic">(iii) Fraud or Misrepresentation Claims:</p>
                  <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                    <li>Detailed affidavit on stamp paper describing fraudulent conduct;</li>
                    <li>Documentary evidence of misrepresentation (false statements, altered documents, etc.);</li>
                    <li>Expert opinion or forensic report (if available);</li>
                    <li>Police complaint or FIR copy (if criminal complaint filed);</li>
                    <li>Legal notice issued to Platform and proof of service;</li>
                  </ul>
                </div>
                <div>
                  <p className="text-slate-700 italic">(iv) Medical Emergency/Compassionate Grounds:</p>
                  <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                    <li>Medical certificate from registered medical practitioner;</li>
                    <li>Hospital admission records or treatment bills (for serious illness);</li>
                    <li>Death certificate (in case of death of Stakeholder);</li>
                    <li>Succession certificate or legal heir certificate (if refund claimed by legal heirs);</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">7.5 Document Verification Standards</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Authenticity Verification:</p>
              <p className="text-slate-600 text-sm mb-1">The Platform reserves the right to verify the authenticity of all submitted documents through:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Physical verification of original documents;</li>
                <li>Digital signature verification for electronically signed documents;</li>
                <li>Third-party verification services for identity documents;</li>
                <li>Contacting issuing authorities (banks, government departments, etc.);</li>
                <li>Forensic document examination for high-value or suspicious claims;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Rejection for False/Forged Documents:</p>
              <p className="text-slate-600 text-sm mb-1">If any document is found to be false, forged, fabricated, or tampered with:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>The refund request shall be summarily rejected;</li>
                <li>The User Account may be suspended or terminated;</li>
                <li>The matter may be reported to law enforcement authorities;</li>
                <li>The Platform may initiate civil and criminal proceedings;</li>
                <li>The Stakeholder shall be liable for all costs, damages, and legal expenses;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Incomplete Documentation:</p>
              <p className="text-slate-600 text-sm mb-1">If documentation is incomplete:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>The Platform shall issue a deficiency memorandum within 5 (five) Business Days;</li>
                <li>The Stakeholder shall have 15 (fifteen) days to cure deficiencies;</li>
                <li>Failure to cure shall result in rejection of refund request;</li>
                <li>Fresh request may be submitted within limitation period with complete documentation.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
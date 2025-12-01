'use client';

import React from 'react';
import { Table, FileText, Percent, Calendar } from 'lucide-react';

export default function RefundPolicyPart12() {
  return (
    <section id="part-12" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 12</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">SCHEDULES AND ANNEXURES</h2>
      </div>

      {/* ARTICLE 40 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Table className="text-indigo-600" size={20} /> ARTICLE 40: SCHEDULES AND ANNEXURES
        </h3>

        {/* Schedule I */}
        <div className="mb-10">
          <h4 className="text-lg font-bold text-slate-700 mb-4 bg-slate-100 p-2 rounded">SCHEDULE I: FEE AND CHARGES STRUCTURE</h4>
          
          <div className="mb-6">
            <p className="text-slate-700 font-semibold mb-2">(A) Refund Processing Charges</p>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
              <table className="w-full text-sm text-left text-slate-600">
                <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-6 py-3">Refund Amount Range</th>
                    <th className="px-6 py-3">Processing Fee</th>
                  </tr>
                </thead>
                <tbody>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Up to ₹10,000</td><td className="px-6 py-4">₹500 or 5% of refund amount, whichever is lower</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">₹10,001 to ₹50,000</td><td className="px-6 py-4">₹1,000 or 3% of refund amount, whichever is lower</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">₹50,001 to ₹2,00,000</td><td className="px-6 py-4">₹2,500 or 2% of refund amount, whichever is lower</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">₹2,00,001 to ₹10,00,000</td><td className="px-6 py-4">₹5,000 or 1.5% of refund amount, whichever is lower</td></tr>
                  <tr className="bg-white"><td className="px-6 py-4">Above ₹10,00,000</td><td className="px-6 py-4">₹15,000 or 1% of refund amount, whichever is lower</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div className="mb-6">
            <p className="text-slate-700 font-semibold mb-2">(B) Payment Gateway and Transaction Charges</p>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
              <table className="w-full text-sm text-left text-slate-600">
                <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-6 py-3">Mode</th>
                    <th className="px-6 py-3">Charges (Indicative)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Credit Card</td><td className="px-6 py-4">1.8% - 2.5% + GST</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Debit Card</td><td className="px-6 py-4">1.0% - 1.5% + GST</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Net Banking</td><td className="px-6 py-4">₹5 - ₹20 per transaction + GST</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">UPI</td><td className="px-6 py-4">0% - 0.5% (subject to change)</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">NEFT</td><td className="px-6 py-4">₹2 - ₹25 based on amount</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">RTGS</td><td className="px-6 py-4">₹25 - ₹50 based on amount</td></tr>
                  <tr className="bg-white"><td className="px-6 py-4">International Wire Transfer</td><td className="px-6 py-4">$15 - $50 or equivalent + correspondent bank charges</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div className="mb-6">
            <p className="text-slate-700 font-semibold mb-2">(C) Third-Party Verification and Documentation Charges</p>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
              <table className="w-full text-sm text-left text-slate-600">
                <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-6 py-3">Service</th>
                    <th className="px-6 py-3">Charges</th>
                  </tr>
                </thead>
                <tbody>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">KYC Verification (per instance)</td><td className="px-6 py-4">₹100 - ₹500</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Document Apostille/Legalization</td><td className="px-6 py-4">Actual charges + ₹1,000 processing</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Certified Translation</td><td className="px-6 py-4">₹500 - ₹2,000 per document</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Legal Opinion</td><td className="px-6 py-4">₹10,000 - ₹50,000 (case-dependent)</td></tr>
                  <tr className="bg-white"><td className="px-6 py-4">Forensic Audit</td><td className="px-6 py-4">₹25,000 - ₹2,00,000 (case-dependent)</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div className="mb-6">
            <p className="text-slate-700 font-semibold mb-2">(D) Demand Draft and Courier Charges</p>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
              <table className="w-full text-sm text-left text-slate-600">
                <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-6 py-3">Service</th>
                    <th className="px-6 py-3">Charges</th>
                  </tr>
                </thead>
                <tbody>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Demand Draft Issuance</td><td className="px-6 py-4">₹50 - ₹100</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Domestic Courier</td><td className="px-6 py-4">₹100 - ₹300</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">International Courier</td><td className="px-6 py-4">₹1,500 - ₹5,000</td></tr>
                  <tr className="bg-white"><td className="px-6 py-4">Registered Post</td><td className="px-6 py-4">₹50 - ₹150</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div className="mb-6">
            <p className="text-slate-700 font-semibold mb-2">(E) Re-Processing Charges (for Failed Refunds)</p>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
              <table className="w-full text-sm text-left text-slate-600">
                <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-6 py-3">Attempt</th>
                    <th className="px-6 py-3">Charges</th>
                  </tr>
                </thead>
                <tbody>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">First Re-attempt</td><td className="px-6 py-4">No charge</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Second Re-attempt</td><td className="px-6 py-4">₹500</td></tr>
                  <tr className="bg-white"><td className="px-6 py-4">Third+ Re-attempts</td><td className="px-6 py-4">₹1,000 per attempt</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div className="mb-6">
            <p className="text-slate-700 font-semibold mb-2">(F) Expedited Processing Charges</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li><strong>Fast-Track Processing (3 Business Days):</strong> Additional 2% of refund amount or ₹5,000, whichever is higher</li>
              <li><strong>Super Fast-Track (24-48 hours):</strong> Additional 3% of refund amount or ₹10,000, whichever is higher (subject to availability and Platform discretion)</li>
            </ul>
            <p className="text-xs text-slate-500 mt-2 italic">
              Notes:<br/>
              1. All charges are exclusive of applicable GST unless specified otherwise.<br/>
              2. Charges are indicative and subject to change based on actual costs incurred.<br/>
              3. For high-value or complex cases, charges may be determined on case-by-case basis with prior intimation.<br/>
              4. Platform reserves right to revise charges with 30 days notice.
            </p>
          </div>
        </div>

        {/* Schedule II */}
        <div className="mb-10">
          <h4 className="text-lg font-bold text-slate-700 mb-4 bg-slate-100 p-2 rounded">SCHEDULE II: LIQUIDATED DAMAGES AND FORFEITURE SCHEDULE</h4>
          
          <div className="mb-6">
            <p className="text-slate-700 font-semibold mb-2">(A) Cancellation-Based Forfeitures</p>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
              <table className="w-full text-sm text-left text-slate-600">
                <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-6 py-3">Cancellation Timing (before scheduled completion)</th>
                    <th className="px-6 py-3">Forfeiture Percentage</th>
                  </tr>
                </thead>
                <tbody>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">More than 30 days</td><td className="px-6 py-4">5% of transaction value</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">15-30 days</td><td className="px-6 py-4">10% of transaction value</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">7-14 days</td><td className="px-6 py-4">15% of transaction value</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Less than 7 days</td><td className="px-6 py-4">20% of transaction value</td></tr>
                  <tr className="bg-white"><td className="px-6 py-4">After scheduled completion date</td><td className="px-6 py-4">25% of transaction value</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div className="mb-6">
            <p className="text-slate-700 font-semibold mb-2">(B) Service Consumption Forfeitures</p>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
              <table className="w-full text-sm text-left text-slate-600">
                <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-6 py-3">Subscription Service Cancellation Timing</th>
                    <th className="px-6 py-3">Minimum Retention</th>
                  </tr>
                </thead>
                <tbody>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Within 7 days (Cooling-Off Period)</td><td className="px-6 py-4">10% (if partially accessed)</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">8-30 days</td><td className="px-6 py-4">20% + pro-rata consumption</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">31-90 days</td><td className="px-6 py-4">30% + pro-rata consumption</td></tr>
                  <tr className="bg-white"><td className="px-6 py-4">After 90 days</td><td className="px-6 py-4">40% + pro-rata consumption</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div className="mb-6">
            <p className="text-slate-700 font-semibold mb-2">(C) Maximum Forfeiture Cap</p>
            <p className="text-slate-600 mb-1">Aggregate forfeitures and deductions shall not exceed:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>50% of transaction value for routine cancellations; OR</li>
              <li>75% of transaction value for cancellations involving Platform's additional costs or opportunity losses; OR</li>
              <li>100% forfeiture in cases of fraud, material misrepresentation, or breach of law.</li>
            </ul>
          </div>
        </div>

        {/* Schedule III */}
        <div className="mb-10">
          <h4 className="text-lg font-bold text-slate-700 mb-4 bg-slate-100 p-2 rounded">SCHEDULE III: INTEREST RATES</h4>
          
          <div className="mb-6">
            <p className="text-slate-700 font-semibold mb-2">(A) Interest on Delayed Refunds (Platform's Liability)</p>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
              <table className="w-full text-sm text-left text-slate-600">
                <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-6 py-3">Delay Period</th>
                    <th className="px-6 py-3">Interest Rate (per annum)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Up to 30 days beyond due date</td><td className="px-6 py-4">12%</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">31-60 days</td><td className="px-6 py-4">15%</td></tr>
                  <tr className="bg-white"><td className="px-6 py-4">Beyond 60 days</td><td className="px-6 py-4">18%</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div className="mb-6">
            <p className="text-slate-700 font-semibold mb-2">(B) Interest on Recovery from Stakeholder</p>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
              <table className="w-full text-sm text-left text-slate-600">
                <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-6 py-3">Recovery Type</th>
                    <th className="px-6 py-3">Interest Rate (per annum)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Excess refund mistakenly paid</td><td className="px-6 py-4">18%</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Amounts due from Stakeholder breach</td><td className="px-6 py-4">18%</td></tr>
                  <tr className="bg-white"><td className="px-6 py-4">Indemnification amounts</td><td className="px-6 py-4">15%</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div className="mb-6">
            <p className="text-slate-700 font-semibold mb-2">(C) Calculation Method</p>
            <p className="text-slate-600">Interest calculated on simple interest basis using formula:<br/>
            Interest = (Principal × Rate × Days) ÷ (365 × 100)<br/>
            Calculated from day following due date to date of actual payment (both days inclusive).</p>
          </div>
        </div>

        {/* Schedule IV */}
        <div className="mb-10">
          <h4 className="text-lg font-bold text-slate-700 mb-4 bg-slate-100 p-2 rounded">SCHEDULE IV: TIMELINES SUMMARY</h4>
          
          <div className="mb-6">
            <p className="text-slate-700 font-semibold mb-2">(A) Refund Request Processing Timelines</p>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
              <table className="w-full text-sm text-left text-slate-600">
                <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-6 py-3">Stage</th>
                    <th className="px-6 py-3">Timeline</th>
                  </tr>
                </thead>
                <tbody>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Acknowledgment</td><td className="px-6 py-4">2 Business Days</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Initial Assessment</td><td className="px-6 py-4">5 Business Days</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Simple Cases - Decision</td><td className="px-6 py-4">7 Business Days</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Standard Cases - Decision</td><td className="px-6 py-4">15 Business Days</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Complex Cases - Decision</td><td className="px-6 py-4">21 Business Days</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">High-Value Cases - Decision</td><td className="px-6 py-4">30 Business Days</td></tr>
                  <tr className="bg-white"><td className="px-6 py-4">Post-Approval Disbursement</td><td className="px-6 py-4">3-10 Business Days (value-dependent)</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div className="mb-6">
            <p className="text-slate-700 font-semibold mb-2">(B) Grievance Resolution Timelines</p>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
              <table className="w-full text-sm text-left text-slate-600">
                <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-6 py-3">Level</th>
                    <th className="px-6 py-3">Timeline</th>
                  </tr>
                </thead>
                <tbody>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Level 1 (GRO)</td><td className="px-6 py-4">20 Business Days</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Level 2 (Nodal Officer)</td><td className="px-6 py-4">15 Business Days from escalation</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Level 3 (CCO)</td><td className="px-6 py-4">15 Business Days from escalation</td></tr>
                  <tr className="bg-white"><td className="px-6 py-4">Total Maximum</td><td className="px-6 py-4">50 Business Days</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div className="mb-6">
            <p className="text-slate-700 font-semibold mb-2">(C) Documentation Submission Timelines</p>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
              <table className="w-full text-sm text-left text-slate-600">
                <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-6 py-3">Requirement</th>
                    <th className="px-6 py-3">Timeline</th>
                  </tr>
                </thead>
                <tbody>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Response to Deficiency Notice</td><td className="px-6 py-4">15 days</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Additional Documents (Platform Request)</td><td className="px-6 py-4">7 Business Days</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Updated KYC Documents</td><td className="px-6 py-4">30 days from change</td></tr>
                  <tr className="bg-white"><td className="px-6 py-4">Bank Account Change Documentation</td><td className="px-6 py-4">7 Business Days</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div className="mb-6">
            <p className="text-slate-700 font-semibold mb-2">(D) Limitation Periods for Refund Requests</p>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
              <table className="w-full text-sm text-left text-slate-600">
                <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-6 py-3">Transaction Type</th>
                    <th className="px-6 py-3">Limitation Period</th>
                  </tr>
                </thead>
                <tbody>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Investment Transactions</td><td className="px-6 py-4">90 days from refund-triggering event</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Advisory Services</td><td className="px-6 py-4">30 days from service delivery</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Subscription Services</td><td className="px-6 py-4">15 days from end of billing cycle</td></tr>
                  <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Technical Errors</td><td className="px-6 py-4">7 Business Days from transaction</td></tr>
                  <tr className="bg-white"><td className="px-6 py-4">Statutory/Regulatory Grounds</td><td className="px-6 py-4">180 days from discovery or as per law</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {/* Schedule V */}
        <div className="mb-10">
          <h4 className="text-lg font-bold text-slate-700 mb-4 bg-slate-100 p-2 rounded">SCHEDULE V: DEFINITIONS SUPPLEMENT</h4>
          <p className="text-slate-600 mb-2">Additional definitions for specialized terms:</p>
          <ul className="list-disc pl-6 text-slate-600 space-y-2">
            <li><strong>"Accredited Investor":</strong> An investor meeting criteria under applicable SEBI regulations for sophisticated/accredited investor status.</li>
            <li><strong>"Business Continuity Plan (BCP)":</strong> Platform's documented procedures for maintaining operations during disruptions.</li>
            <li><strong>"Chinese Wall":</strong> Information barriers preventing flow of confidential or price-sensitive information between departments.</li>
            <li><strong>"Cooling-Off Period":</strong> Time period during which Stakeholder may cancel without penalty (typically 7-15 days for specific services).</li>
            <li><strong>"Cyber Incident":</strong> Unauthorized access, data breach, malware attack, or other cybersecurity event.</li>
            <li><strong>"Disaster Recovery Plan (DRP)":</strong> Procedures for recovering systems and data after catastrophic failure.</li>
            <li><strong>"Executory Contract":</strong> Contract where obligations not yet fully performed by either party.</li>
            <li><strong>"Front-Running":</strong> Prohibited practice of trading ahead of client orders using non-public information.</li>
            <li><strong>"Institutional Client":</strong> Banks, insurance companies, mutual funds, pension funds, and other regulated financial institutions.</li>
            <li><strong>"Material Adverse Change":</strong> Significant negative change in financial condition, business, or legal status.</li>
            <li><strong>"Politically Exposed Person (PEP)":</strong> Individual entrusted with prominent public function (as defined under PMLA).</li>
            <li><strong>"Principal Capacity":</strong> Platform acting as buyer/seller in own account (vs. agent capacity).</li>
            <li><strong>"Reasonable Commercial Efforts":</strong> Level of effort and resources that prudent businessperson would expend under similar circumstances.</li>
            <li><strong>"Red Flags":</strong> Warning signs or indicators of suspicious activity requiring investigation.</li>
            <li><strong>"Regulatory Capital":</strong> Minimum capital required to be maintained under SEBI regulations.</li>
            <li><strong>"Subscriber":</strong> Person who subscribes for securities in primary issuance.</li>
            <li><strong>"Wilful Default":</strong> Deliberate non-payment despite capacity to pay (RBI definition).</li>
          </ul>
        </div>
      </div>
    </section>
  );
}
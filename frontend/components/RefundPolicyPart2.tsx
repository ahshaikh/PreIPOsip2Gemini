'use client';

import React from 'react';
import { Target, FileText } from 'lucide-react';

export default function RefundPolicyPart2() {
  return (
    <section id="part-2" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 2</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">SCOPE OF REFUND ELIGIBILITY AND TRANSACTION-SPECIFIC PROVISIONS</h2>
      </div>

      {/* ARTICLE 4 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Target className="text-indigo-600" size={20} /> ARTICLE 4: SCOPE OF REFUND ELIGIBILITY
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">4.1 General Principles of Refund Eligibility</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-3">
            <li className="text-justify">
              (a) <strong>Contractual Basis:</strong> The right to a refund is a contractual right that arises only in circumstances expressly provided for in this Policy, specific Transaction Documentation, or as mandated by Applicable Law. No implied, inherent, or automatic right to refund exists beyond these express provisions;
            </li>
            <li className="text-justify">
              (b) <strong>Good Faith Requirement:</strong> All refund requests must be made in good faith, with full disclosure of material facts, and without any intent to defraud, manipulate, or abuse the Platform's processes;
            </li>
            <li className="text-justify">
              (c) <strong>Completeness of Transaction:</strong> The eligibility for refund shall depend on the stage at which a transaction stands, the nature of consideration paid, and whether the transaction has been executed, partially executed, or remains executory;
            </li>
            <li className="text-justify">
              (d) <strong>Third-Party Dependencies:</strong> Where transactions involve third parties (issuers, sellers, depositories, intermediaries), refund eligibility may be contingent upon the policies, approvals, and actions of such third parties, over which the Platform exercises limited or no control.
            </li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">4.2 Categories of Refundable Consideration</h4>
          <p className="text-slate-600 mb-2">The following categories of Consideration may be eligible for refund subject to the specific conditions, limitations, and deductions set forth in subsequent Articles:</p>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Registration and Platform Access Fees</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>One-time registration fees paid for creating a User Account;</li>
                <li>Subscription fees for premium access, research reports, or enhanced services;</li>
                <li>Annual membership fees or recurring subscription charges;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Advisory and Consultation Fees</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Fees paid for investment advisory services;</li>
                <li>Charges for personalized portfolio recommendations;</li>
                <li>Consultation fees for pre-investment due diligence support;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Transaction Facilitation Fees</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Brokerage or commission paid to the Platform for intermediation services;</li>
                <li>Processing fees charged for facilitating Pre-IPO Securities transactions;</li>
                <li>Documentation charges, legal vetting fees, and administrative costs;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Subscription and Investment Amounts</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Monies paid toward subscription of Pre-IPO Securities where transaction has not been executed;</li>
                <li>Advance payments for purchase of Unlisted Shares pending completion;</li>
                <li>Deposit amounts held in escrow or trust pending transaction closure;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(e) Ancillary Service Charges</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Charges for market research, valuation reports, or information memoranda;</li>
                <li>Fees for legal documentation, compliance support, or regulatory filings;</li>
                <li>Technology usage fees, API access charges, or data subscription fees.</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">4.3 Categories of Non-Refundable Consideration</h4>
          <p className="text-slate-600 mb-2">The following categories of Consideration shall be expressly non-refundable under all circumstances, except where a refund is mandated by a judicial order, arbitral award, or specific statutory provision:</p>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Services Rendered and Consumed</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Fees for services that have been fully rendered, delivered, and consumed by the Stakeholder;</li>
                <li>Charges for research reports, information memoranda, or data that has been accessed, downloaded, or utilized;</li>
                <li>Consultation fees where advisory sessions have been conducted;</li>
                <li>Platform access fees for periods during which access has been provided and utilized;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Third-Party Costs Incurred</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Statutory charges, stamp duty, regulatory fees, and government levies that have been paid to third parties;</li>
                <li>Depository charges, NSDL/CDSL fees, and DIS charges;</li>
                <li>Legal fees paid to external counsel for transaction documentation;</li>
                <li>Escrow management fees paid to escrow agents;</li>
                <li>Payment gateway charges, bank transaction fees, and GST on services;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Completed Transactions</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Consideration for transactions that have been fully executed, settled, and delivered;</li>
                <li>Purchase price for securities where transfer has been completed and shares credited to demat account;</li>
                <li>Amounts for which the Stakeholder has received corresponding value, benefit, or consideration;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Penalty and Penal Charges</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Penalties imposed for breach of contract, violation of Platform policies, or non-compliance with transaction terms;</li>
                <li>Late payment charges, interest on delayed payments, or compensation for defaults;</li>
                <li>Charges for reversal of fraudulent transactions or chargebacks;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(e) Discretionary Services with Upfront Consumption</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Premium listing fees for issuers seeking visibility on the Platform;</li>
                <li>Advertisement charges, promotional campaign costs, or marketing fees;</li>
                <li>Event participation fees, conference registration charges, or networking forum access fees.</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">4.4 Conditional Refund Scenarios</h4>
          <p className="text-slate-600 mb-2">The following scenarios may attract refund eligibility subject to satisfaction of specified conditions:</p>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Transaction Failure Due to Platform Default</p>
              <p className="text-slate-600 text-sm mb-1">Where a transaction fails to materialize solely due to the Platform's breach of contractual obligations, negligence, or willful default, and provided that:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>The Stakeholder has fulfilled all obligations under Transaction Documentation;</li>
                <li>The failure is not attributable to Force Majeure Events, regulatory changes, or third-party defaults;</li>
                <li>The Stakeholder has provided written notice within the prescribed timeline;</li>
                <li>No alternate remedy or compensation has been accepted by the Stakeholder;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Regulatory Non-Compliance or Legal Invalidity</p>
              <p className="text-slate-600 text-sm mb-1">Where a transaction is rendered void, voidable, or unenforceable due to:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Non-compliance with SEBI regulations discovered post-facto;</li>
                <li>Failure to obtain requisite regulatory approvals or NOCs;</li>
                <li>Illegality under FEMA, Companies Act, or other Applicable Law;</li>
                <li>Misrepresentation or non-disclosure by the Platform regarding regulatory status;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Technical Failures and Processing Errors</p>
              <p className="text-slate-600">Where consideration has been debited multiple times due to technical glitches, payment gateway errors, or system failures, the excess amount shall be refunded subject to verification and reconciliation;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Cooling-Off Period Exercise</p>
              <p className="text-slate-600 text-sm mb-1">Where a Stakeholder exercises a right to cancel within a specified Cooling-Off Period (if applicable to specific services), subject to:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Timely submission of cancellation request;</li>
                <li>Non-utilization or partial utilization of services;</li>
                <li>Deduction of proportionate charges for services consumed;</li>
                <li>Compliance with cancellation procedures.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      {/* ARTICLE 5 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <FileText className="text-indigo-600" size={20} /> ARTICLE 5: TRANSACTION-SPECIFIC REFUND PROVISIONS
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-3">5.1 Pre-IPO Securities Subscription Transactions</h4>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold mb-1">(a) Pre-Allotment Stage</p>
              <p className="text-slate-600 mb-1">Where a Stakeholder has applied for subscription of Pre-IPO Securities and paid subscription amount, but allotment has not been made, the following provisions shall apply:</p>
              
              <div className="ml-4 mt-2">
                <p className="text-slate-700 italic">(i) Voluntary Withdrawal Before Acceptance: If the Stakeholder withdraws the application before the Platform or issuer formally accepts the subscription, refund shall be processed within 15 (fifteen) Business Days, subject to:</p>
                <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                  <li>Deduction of processing charges not exceeding 2% (two percent) of subscription amount;</li>
                  <li>Deduction of payment gateway charges actually incurred;</li>
                  <li>Deduction of any third-party costs already committed;</li>
                </ul>
              </div>

              <div className="ml-4 mt-2">
                <p className="text-slate-700 italic">(ii) Transaction Cancellation by Issuer: If the issuer cancels the transaction, declines allotment, or fails to complete the transaction due to reasons attributable to the issuer, full refund shall be processed within 21 (twenty-one) Business Days without deduction, except for:</p>
                <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                  <li>Actual costs incurred for KYC verification, documentation, and regulatory compliance;</li>
                  <li>Charges not exceeding ₹5,000 (Rupees Five Thousand) or 1% of subscription amount, whichever is lower;</li>
                </ul>
              </div>

              <div className="ml-4 mt-2">
                <p className="text-slate-700 italic">(iii) Regulatory Impediment: If allotment cannot be completed due to regulatory restrictions, SEBI directions, RBI circulars, or change in Applicable Law, refund shall be processed within 30 (thirty) Business Days, subject to:</p>
                <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                  <li>Retention of documented third-party costs;</li>
                  <li>Deduction of administrative charges not exceeding 1.5% of subscription amount;</li>
                </ul>
              </div>
            </div>

            <div>
              <p className="text-slate-700 font-semibold mb-1">(b) Post-Allotment, Pre-Transfer Stage</p>
              <p className="text-slate-600 mb-1">Where allotment has been made but securities have not been transferred to the Stakeholder's demat account:</p>
              
              <div className="ml-4 mt-2">
                <p className="text-slate-700 italic">(i) No Refund Unless Material Breach: Once allotment is made, no refund shall be available unless there is material breach, fraud, or misrepresentation by the Platform or issuer;</p>
              </div>

              <div className="ml-4 mt-2">
                <p className="text-slate-700 italic">(ii) Exception for Transfer Failure: If transfer fails due to Platform's or transfer agent's default after allotment, the Stakeholder may opt for:</p>
                <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                  <li>Completion of transfer with reasonable extension of time; or</li>
                  <li>Refund of subscription amount with interest @ 12% (twelve percent) per annum from date of allotment to date of refund, less any dividends or benefits received;</li>
                </ul>
              </div>
            </div>

            <div>
              <p className="text-slate-700 font-semibold mb-1">(c) Post-Transfer Stage</p>
              <p className="text-slate-600 mb-1">Once securities are credited to the Stakeholder's demat account, no refund shall be available. The Stakeholder's remedies shall be limited to:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Liquidation of securities through secondary market sale facilitated by the Platform;</li>
                <li>Invocation of representations and warranties under Transaction Documentation;</li>
                <li>Specific performance or damages as per contractual terms.</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-3">5.2 Unlisted Share Purchase Transactions</h4>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold mb-1">(a) Expression of Interest Stage</p>
              <p className="text-slate-600 mb-1">Where a Stakeholder has expressed interest in purchasing Unlisted Shares and paid a booking amount or earnest money:</p>
              
              <div className="ml-4 mt-2">
                <p className="text-slate-700 italic">(i) Refund of booking amount shall be available if the Stakeholder withdraws interest before execution of definitive purchase agreement, subject to:</p>
                <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                  <li>Forfeiture of 10% (ten percent) of booking amount as liquidated damages;</li>
                  <li>Deduction of actual costs incurred for valuation, due diligence, and documentation;</li>
                  <li>Written notice of withdrawal provided at least 5 (five) Business Days before scheduled transaction closure;</li>
                </ul>
              </div>

              <div className="ml-4 mt-2">
                <p className="text-slate-700 italic">(ii) If the seller declines to complete the transaction or shares are unavailable, full refund including booking amount shall be processed within 15 (fifteen) Business Days;</p>
              </div>
            </div>

            <div>
              <p className="text-slate-700 font-semibold mb-1">(b) Agreement Executed, Payment Made, Shares Not Transferred</p>
              
              <div className="ml-4 mt-2">
                <p className="text-slate-700 italic">(i) If the Stakeholder has paid the purchase price but shares are not transferred within the agreed timeline:</p>
                <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                  <li>The Stakeholder may issue notice requiring completion within 15 (fifteen) Business Days;</li>
                  <li>If completion does not occur, refund with interest @ 15% (fifteen percent) per annum shall be processed;</li>
                  <li>Platform shall not be liable for delays caused by depository issues, regulatory holds, or seller defaults beyond Platform's control;</li>
                </ul>
              </div>

              <div className="ml-4 mt-2">
                <p className="text-slate-700 italic">(ii) If shares are transferred but do not conform to specifications (wrong ISIN, disputed title, encumbrances), the Stakeholder may:</p>
                <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                  <li>Reject the transfer and demand refund within 7 (seven) Business Days of discovery;</li>
                  <li>Refund shall be processed within 30 (thirty) Business Days subject to return of shares to seller;</li>
                </ul>
              </div>
            </div>

            <div>
              <p className="text-slate-700 font-semibold mb-1">(c) Completed Transactions</p>
              <p className="text-slate-600 mb-1">Post-completion, no refund shall be available. Remedies limited to:</p>
              <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                <li>Warranty claims against seller (if the Platform is not the principal seller);</li>
                <li>Indemnity claims for breach of representations;</li>
                <li>Suit for rescission or damages in case of fraud or fundamental breach.</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-3">5.3 Advisory and Subscription Services</h4>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold mb-1">(a) Annual Subscription Services</p>
              
              <div className="ml-4 mt-2">
                <p className="text-slate-700 italic">(i) Cooling-Off Period: For annual subscription plans, a 7 (seven) day Cooling-Off Period from date of subscription shall apply, during which:</p>
                <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                  <li>Full refund available if services not accessed;</li>
                  <li>Pro-rata refund less 20% (twenty percent) administrative charge if partially accessed;</li>
                </ul>
              </div>

              <div className="ml-4 mt-2">
                <p className="text-slate-700 italic">(ii) Mid-Term Cancellation: After Cooling-Off Period, no refund for mid-term cancellation except:</p>
                <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                  <li>If Platform discontinues services or materially reduces service quality;</li>
                  <li>If Platform breaches service level agreements;</li>
                  <li>Refund shall be pro-rata for unutilized period less 30% (thirty percent) administrative and recovery charges;</li>
                </ul>
              </div>
            </div>

            <div>
              <p className="text-slate-700 font-semibold mb-1">(b) One-Time Advisory Services</p>
              
              <div className="ml-4 mt-2">
                <p className="text-slate-700 italic">(i) Refund available only if:</p>
                <ul className="list-disc pl-6 mt-1 text-slate-600 space-y-1">
                  <li>Advisory session not conducted within 30 (thirty) days of payment due to Platform's default;</li>
                  <li>Services rendered are materially deficient or non-compliant with agreed scope;</li>
                </ul>
              </div>

              <div className="ml-4 mt-2">
                <p className="text-slate-700 italic">(ii) Refund quantum: 75% (seventy-five percent) of fees paid, with 25% (twenty-five percent) retained for administrative costs;</p>
              </div>

              <div className="ml-4 mt-2">
                <p className="text-slate-700 italic">(iii) No refund if advice provided but Stakeholder dissatisfied with quality, outcome, or recommendations.</p>
              </div>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-3">5.4 Platform Access and Technology Fees</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Registration fees:</strong> Non-refundable once account activated and access provided;</li>
            <li>(b) <strong>Data subscription fees:</strong> Refundable on pro-rata basis if Platform terminates services, but not for Stakeholder-initiated cancellation;</li>
            <li>(c) <strong>API access charges:</strong> Non-refundable after API keys issued and authentication provided.</li>
          </ul>
        </div>
      </div>
    </section>
  );
}
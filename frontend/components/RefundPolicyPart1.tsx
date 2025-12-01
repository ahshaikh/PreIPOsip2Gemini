'use client';

import React from 'react';
import { BookOpen, Info, Scale, Landmark } from 'lucide-react';

export default function RefundPolicyPart1() {
  return (
    <section id="part-1" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 1</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">PRELIMINARY PROVISIONS, DEFINITIONS, AND REGULATORY FRAMEWORK</h2>
      </div>

      {/* ARTICLE 1 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <BookOpen className="text-indigo-600" size={20} /> ARTICLE 1: TITLE, COMMENCEMENT AND APPLICATION
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">1.1 Title and Commencement</h4>
          <p className="text-slate-600 mb-2 text-justify">
            This document shall be cited as the "Refund Policy of PreIPOSIP.com" (hereinafter referred to as "this Policy") and shall come into force with effect from the date of its publication on the Platform's website and shall remain in full force and effect until superseded, amended, or withdrawn in accordance with the provisions contained herein.
          </p>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">1.2 Application and Territorial Scope</h4>
          <p className="text-slate-600 mb-2">This Policy shall apply to:</p>
          <ul className="list-none pl-4 text-slate-600 space-y-3">
            <li className="text-justify">
              (a) All Users, Investors, Subscribers, Clients, and Counterparties (collectively referred to as "Stakeholders") who engage with the Platform for the purposes of discovering, evaluating, expressing interest in, or transacting in Pre-IPO Securities, Unlisted Shares, Private Equity instruments, or any other financial products or services offered through the Platform;
            </li>
            <li className="text-justify">
              (b) All transactions, subscriptions, investments, advisory engagements, intermediation services, and ancillary services facilitated, arranged, or executed through or by the Platform;
            </li>
            <li className="text-justify">
              (c) All refund requests, withdrawal applications, cancellation demands, and return of consideration scenarios arising out of or in connection with the use of the Platform's services;
            </li>
            <li className="text-justify">
              (d) All personnel, employees, directors, officers, agents, representatives, associates, and intermediaries acting on behalf of the Platform in relation to refund processing and client servicing.
            </li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">1.3 Binding Nature</h4>
          <p className="text-slate-600 mb-2 text-justify">
            This Policy constitutes a legally binding contract between the Platform and the Stakeholder. By registering on the Platform, accessing its services, making payments, expressing investment interest, or executing transactions, the Stakeholder expressly and irrevocably:
          </p>
          <ul className="list-none pl-4 text-slate-600 space-y-3">
            <li className="text-justify">
              (a) Acknowledges having read, understood, and agreed to be bound by the terms, conditions, limitations, exclusions, and procedures set forth in this Policy;
            </li>
            <li className="text-justify">
              (b) Accepts the jurisdiction, dispute resolution mechanisms, and liability limitations stipulated herein;
            </li>
            <li className="text-justify">
              (c) Waives any claim to ignorance, misunderstanding, or lack of informed consent regarding the refund terms and conditions;
            </li>
            <li className="text-justify">
              (d) Agrees that this Policy shall be read harmoniously with the Platform's Terms of Use, Privacy Policy, Risk Disclosure Document, and all other governing instruments.
            </li>
          </ul>
        </div>
      </div>

      {/* ARTICLE 2 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Info className="text-indigo-600" size={20} /> ARTICLE 2: DEFINITIONS AND INTERPRETATIONS
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-3">2.1 Definitions</h4>
          <p className="text-slate-600 mb-4">In this Policy, unless the context otherwise requires, the following terms shall have the meanings ascribed to them below:</p>
          
          <div className="bg-slate-50 rounded-xl border border-slate-200 p-6 space-y-6">
            
            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"Acceptance"</span>
              <span className="text-slate-600 text-sm text-justify">means the formal confirmation by the Platform that a refund request has been approved for processing in accordance with the terms of this Policy;</span>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"Applicable Law"</span>
              <span className="text-slate-600 text-sm text-justify">means and includes:</span>
              <ul className="list-disc pl-6 mt-2 text-slate-600 text-sm space-y-1">
                <li>The Companies Act, 2013 and all rules, regulations, and notifications issued thereunder;</li>
                <li>The Securities and Exchange Board of India Act, 1992;</li>
                <li>The Securities Contracts (Regulation) Act, 1956;</li>
                <li>The Depositories Act, 1996;</li>
                <li>The Prevention of Money Laundering Act, 2002 (PMLA) and rules framed thereunder;</li>
                <li>The Foreign Exchange Management Act, 1999 (FEMA);</li>
                <li>The Information Technology Act, 2000 and rules thereunder;</li>
                <li>The Consumer Protection Act, 2019;</li>
                <li>All SEBI Regulations including but not limited to SEBI (Issue of Capital and Disclosure Requirements) Regulations, 2018, SEBI (Substantial Acquisition of Shares and Takeovers) Regulations, 2011, SEBI (Prohibition of Insider Trading) Regulations, 2015, SEBI (Investment Advisers) Regulations, 2013, and SEBI (Research Analysts) Regulations, 2014;</li>
                <li>All circulars, guidelines, master directions, and clarifications issued by SEBI, Reserve Bank of India, Ministry of Corporate Affairs, Financial Intelligence Unit – India, and other competent regulatory authorities;</li>
                <li>All other applicable central and state legislation, subordinate legislation, and common law principles governing contracts, equity, restitution, and commercial transactions in India;</li>
              </ul>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"Business Day"</span>
              <span className="text-slate-600 text-sm text-justify">means any day other than Saturday, Sunday, or a public holiday as notified under the Negotiable Instruments Act, 1881, or a bank holiday declared by the Reserve Bank of India for the State of Maharashtra (Mumbai);</span>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"Cancellation"</span>
              <span className="text-slate-600 text-sm text-justify">means the termination, revocation, or annulment of a transaction, subscription, or service engagement by either the Stakeholder or the Platform in accordance with the terms specified in this Policy or in the specific transaction documentation;</span>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"Consideration"</span>
              <span className="text-slate-600 text-sm text-justify">means all monies, payments, subscription amounts, fees, charges, deposits, advances, or any form of valuable consideration paid or payable by a Stakeholder to the Platform or through the Platform to third parties in connection with any transaction or service;</span>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"Cooling-Off Period"</span>
              <span className="text-slate-600 text-sm text-justify">means the time period, if any, during which a Stakeholder may cancel a transaction or service without penalty or with reduced liability, as specifically provided in this Policy or mandated by Applicable Law;</span>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"Force Majeure Event"</span>
              <span className="text-slate-600 text-sm text-justify">means any event or circumstance beyond the reasonable control of the Platform including but not limited to acts of God, natural disasters, epidemics, pandemics, governmental actions, regulatory changes, strikes, civil commotion, war, terrorism, failure of technology infrastructure, banking system failures, or stock exchange closures;</span>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"Investment Transaction"</span>
              <span className="text-slate-600 text-sm text-justify">means any transaction involving the purchase, sale, subscription, transfer, or delivery of Pre-IPO Securities, Unlisted Shares, or other financial instruments facilitated through the Platform;</span>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"KYC"</span>
              <span className="text-slate-600 text-sm text-justify">means Know Your Customer verification procedures mandated under PMLA, SEBI regulations, and RBI directions;</span>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"Non-Refundable Charges"</span>
              <span className="text-slate-600 text-sm text-justify">means fees, charges, costs, and expenses that are expressly designated as non-refundable under this Policy or in specific transaction documentation;</span>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"Platform"</span>
              <span className="text-slate-600 text-sm text-justify">means the website preiposip.com, its mobile applications, APIs, technological infrastructure, and all associated digital interfaces, together with the legal entity operating the same;</span>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"Pre-IPO Securities"</span>
              <span className="text-slate-600 text-sm text-justify">means equity shares, preference shares, convertible instruments, warrants, or other securities of private limited companies or unlisted public companies that have not yet undertaken an Initial Public Offering (IPO) on a recognized stock exchange;</span>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"Processing Time"</span>
              <span className="text-slate-600 text-sm text-justify">means the period required by the Platform to verify, validate, approve, and execute a refund request, subject to the timelines specified in this Policy;</span>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"Refund"</span>
              <span className="text-slate-600 text-sm text-justify">means the return of Consideration to a Stakeholder in accordance with the terms, conditions, limitations, and procedures set forth in this Policy;</span>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"Refund Request"</span>
              <span className="text-slate-600 text-sm text-justify">means a formal written application submitted by a Stakeholder seeking the return of Consideration in accordance with the prescribed format and procedure;</span>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"SEBI"</span>
              <span className="text-slate-600 text-sm text-justify">means the Securities and Exchange Board of India established under the SEBI Act, 1992;</span>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"Stakeholder"</span>
              <span className="text-slate-600 text-sm text-justify">means any User, Investor, Client, Subscriber, Counterparty, or person dealing with or through the Platform;</span>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"Transaction Documentation"</span>
              <span className="text-slate-600 text-sm text-justify">means all agreements, term sheets, subscription forms, application forms, investment memoranda, disclosure documents, and other instruments executed in connection with a specific transaction;</span>
            </div>

            <div>
              <span className="block font-mono text-sm font-bold text-indigo-600 mb-1">"User Account"</span>
              <span className="text-slate-600 text-sm text-justify">means the registered account of a Stakeholder on the Platform through which transactions, communications, and refund requests are processed.</span>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">2.2 Interpretation</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-3">
            <li className="text-justify">
              (a) <strong>Headings:</strong> Headings and subheadings are for convenience only and shall not affect the interpretation of this Policy;
            </li>
            <li className="text-justify">
              (b) <strong>Singular and Plural:</strong> Words importing the singular shall include the plural and vice versa;
            </li>
            <li className="text-justify">
              (c) <strong>Gender:</strong> Words importing any gender shall include all genders;
            </li>
            <li className="text-justify">
              (d) <strong>Statutory References:</strong> References to any statute, regulation, or legal provision shall include references to such statute, regulation, or provision as amended, re-enacted, or replaced from time to time;
            </li>
            <li className="text-justify">
              (e) <strong>Inclusive Language:</strong> The words "include," "including," and "in particular" shall be construed as being by way of illustration or emphasis only and shall not be construed as limiting the generality of any preceding words;
            </li>
            <li className="text-justify">
              (f) <strong>Severability:</strong> If any provision of this Policy is held to be invalid, illegal, or unenforceable, the validity, legality, and enforceability of the remaining provisions shall not be affected or impaired;
            </li>
            <li className="text-justify">
              (g) <strong>Conflict Resolution:</strong> In the event of any conflict between this Policy and any specific Transaction Documentation, the specific Transaction Documentation shall prevail to the extent of such conflict, unless expressly stated otherwise;
            </li>
            <li className="text-justify">
              (h) <strong>Precedence of Law:</strong> Where Applicable Law imposes mandatory obligations that conflict with this Policy, Applicable Law shall prevail.
            </li>
          </ul>
        </div>
      </div>

      {/* ARTICLE 3 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Landmark className="text-indigo-600" size={20} /> ARTICLE 3: REGULATORY FRAMEWORK AND COMPLIANCE ARCHITECTURE
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">3.1 Regulatory Positioning</h4>
          <p className="text-slate-600 mb-2">The Platform acknowledges and represents that:</p>
          <ul className="list-none pl-4 text-slate-600 space-y-3">
            <li className="text-justify">
              (a) It operates within a highly regulated financial services ecosystem governed by multiple regulatory authorities including SEBI, RBI, MCA, and FIU-IND;
            </li>
            <li className="text-justify">
              (b) The facilitation of Pre-IPO Securities transactions attracts regulatory scrutiny under securities law, company law, anti-money laundering legislation, and investor protection frameworks;
            </li>
            <li className="text-justify">
              (c) This Policy has been drafted in conformity with:
              <ul className="list-disc pl-6 mt-1 space-y-1">
                <li>SEBI's investor protection guidelines;</li>
                <li>SEBI circulars on intermediaries and market infrastructure institutions;</li>
                <li>PMLA requirements regarding identification, verification, and record-keeping;</li>
                <li>The Consumer Protection Act's provisions on unfair trade practices and consumer rights;</li>
                <li>Contractual principles under the Indian Contract Act, 1872;</li>
              </ul>
            </li>
            <li className="text-justify">
              (d) The Platform reserves the right to amend this Policy to ensure continued compliance with evolving regulatory requirements.
            </li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">3.2 SEBI Compliance Imperatives</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-3">
            <li className="text-justify">
              (a) <strong>Disclosure Obligations:</strong> The Platform commits to providing clear, conspicuous, and comprehensive disclosure of refund terms at multiple touchpoints including registration, transaction initiation, and payment stages;
            </li>
            <li className="text-justify">
              (b) <strong>Fair Treatment:</strong> All refund requests shall be processed in a fair, transparent, and non-discriminatory manner in accordance with SEBI's principles of treating investors fairly;
            </li>
            <li className="text-justify">
              (c) <strong>Record Maintenance:</strong> All refund transactions shall be recorded, documented, and maintained for a minimum period of five years or such longer period as mandated by Applicable Law;
            </li>
            <li className="text-justify">
              (d) <strong>Audit Trail:</strong> The Platform shall maintain complete audit trails of all refund requests, approvals, rejections, and disbursements.
            </li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">3.3 PMLA and AML Compliance</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-3">
            <li className="text-justify">
              (a) All refunds shall be subject to mandatory verification against PMLA requirements to prevent money laundering and terrorist financing;
            </li>
            <li className="text-justify">
              (b) Refunds shall ordinarily be processed only to the same source account from which the original Consideration was received;
            </li>
            <li className="text-justify">
              (c) The Platform reserves the right to refuse refunds or delay processing where there are reasonable grounds to suspect money laundering, fraud, or violation of Applicable Law;
            </li>
            <li className="text-justify">
              (d) Suspicious transaction reports (STRs) shall be filed with FIU-IND where warranted by the circumstances.
            </li>
          </ul>
        </div>
      </div>
    </section>
  );
}
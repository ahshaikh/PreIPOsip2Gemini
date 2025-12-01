'use client';

import React from 'react';
import { FileText, Phone, Building, User } from 'lucide-react';

export default function RefundPolicyPart11() {
  return (
    <section id="part-11" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 11</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">CLOSING LEGAL CLAUSES AND CONTACT INFORMATION</h2>
      </div>

      {/* ARTICLE 38 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <FileText className="text-indigo-600" size={20} /> ARTICLE 38: CLOSING LEGAL CLAUSES
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">38.1 Counterparts and Manner of Acceptance</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Electronic Acceptance</p>
              <p className="text-slate-600 mb-1">This Policy is accepted by Stakeholder through:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Click-Wrap Acceptance:</strong> Clicking "I Accept" or "I Agree" button on Platform website/application; Electronic acceptance legally valid under Information Technology Act, 2000; Timestamp and IP address recorded;</li>
                <li>(ii) <strong>Continued Use:</strong> Deemed acceptance by continued use of Platform services; Submission of transactions or refund requests; Payment of fees or charges;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Multiple Counterparts</p>
              <p className="text-slate-600">This Policy may exist in multiple counterparts (electronic and physical), all constituting same agreement.</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Amendment Supersession</p>
              <p className="text-slate-600">Each amended version supersedes prior versions from effective date specified.</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">38.2 Execution and Binding Effect</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Effective Date:</strong> This Policy Version [●] is effective from [Date: DD/MM/YYYY].</li>
            <li>(b) <strong>Binding Nature:</strong> Upon acceptance, this Policy constitutes legally binding contract between Platform and Stakeholder, enforceable under Indian Contract Act, 1872 and other Applicable Laws.</li>
            <li>(c) <strong>Parties Bound:</strong> Binds: Platform and its successors and assigns; Stakeholder and permitted successors; Legal representatives and heirs (for individuals); Liquidators and administrators (for entities);</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">38.3 Interpretation and Construction</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Language</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) This Policy drafted in English language;</li>
                <li>(ii) If translated for convenience, English version prevails;</li>
                <li>(iii) Terms defined in Policy have meanings assigned throughout;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Statutory Interpretation</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) References to statutes include amendments and re-enactments;</li>
                <li>(ii) Statutory provisions incorporated as amended from time to time;</li>
                <li>(iii) If statute repealed and replaced, reference to successor provision;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Ambiguity Resolution</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Policy construed as whole, giving effect to all provisions;</li>
                <li>(ii) Specific provisions prevail over general;</li>
                <li>(iii) Harmonious construction preferred;</li>
                <li>(iv) Contra proferentem (against drafter) as last resort;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Business Day Adjustments</p>
              <p className="text-slate-600">Where timeline or event falls on non-Business Day: Automatically adjusted to next Business Day; No separate notice required; Applies throughout Policy unless specified otherwise;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">38.4 Disclaimer of Warranties</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>No Investment Advice:</strong> PLATFORM MAKES NO REPRESENTATION OR WARRANTY REGARDING: Investment merit or suitability of any security; Future performance or returns on investments; Success or timing of IPOs; Accuracy of issuer-provided information;</li>
            <li>(b) <strong>Service Availability:</strong> PLATFORM PROVIDES SERVICES "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING WARRANTIES OF MERCHANTABILITY, FITNESS FOR PARTICULAR PURPOSE, OR NON-INFRINGEMENT.</li>
            <li>(c) <strong>Third-Party Content:</strong> Platform not responsible for accuracy, completeness, or reliability of: Information provided by issuers; Valuations by third-party valuers; Legal opinions by external counsel; Market data from third-party providers;</li>
            <li>(d) <strong>Exclusion of Consequential Damages:</strong> TO MAXIMUM EXTENT PERMITTED BY LAW, PLATFORM SHALL NOT BE LIABLE FOR INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES ARISING FROM OR RELATED TO REFUNDS OR USE OF SERVICES.</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">38.5 Limitation of Actions</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Contractual Claims</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Any claim arising under this Policy must be brought within 3 (THREE) YEARS from date cause of action arose;</li>
                <li>(ii) Calculated from date: Refund request rejected; OR Refund paid but alleged deficiency; OR Breach of Policy provision occurred; OR Stakeholder discovered or ought reasonably to have discovered the claim;</li>
                <li>(iii) Claims not brought within limitation period are forever barred;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Statutory Limitation Periods</p>
              <p className="text-slate-600 mb-1">Where statute prescribes different limitation: Statutory period applies;</p>
              <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Consumer Protection Act claims: 2 years;</li>
                <li>Tort claims: 3 years (Limitation Act, 1963);</li>
                <li>Suit on contract: 3 years (Limitation Act, 1963);</li>
                <li>Arbitration applications: As per Arbitration Act;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Acknowledgment of Debt</p>
              <p className="text-slate-600">If Platform acknowledges liability or makes part payment: Limitation period restarts from date of acknowledgment/payment; Must be in writing signed by authorized officer;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">38.6 Governing Version</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Version Control:</strong> This is Version [●] of the Refund Policy.</li>
            <li>(b) <strong>Applicability:</strong> (i) For refund requests submitted on or after effective date: This version applies; (ii) For refund requests submitted before effective date: Version in effect at time of submission applies (unless Stakeholder opts into new version); (iii) For disputes relating to historical refunds: Version in effect at time of transaction applies;</li>
            <li>(c) <strong>Version Archive:</strong> Previous versions archived and available at: www.preiposip.com/policies/archive</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">38.7 Regulatory Overrides</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Supremacy of Law:</strong> If any provision conflicts with mandatory Applicable Law: Law prevails to extent of conflict; Provision modified to comply with law; Remaining provisions continue in force;</li>
            <li>(b) <strong>Regulatory Directions:</strong> If SEBI or other regulator issues directions affecting this Policy: Platform complies with regulatory directions; Policy deemed amended to extent necessary for compliance; Stakeholders notified of regulatory changes;</li>
            <li>(c) <strong>Judicial Pronouncements:</strong> If court of competent jurisdiction interprets provisions differently: Platform adopts judicial interpretation; Policy may be amended to clarify or align with judgment;</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">38.8 No Partnership or Joint Venture</h4>
          <p className="text-slate-600">This Policy does not create and shall not be construed to create: Partnership between Platform and Stakeholder; Joint venture or consortium; Principal-agent relationship (except as specifically provided); Employer-employee relationship; Franchisor-franchisee relationship; Each party is independent contractor.</p>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">38.9 Non-Exclusivity</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Platform Not Exclusive:</strong> Stakeholder may: Transact with other platforms or intermediaries; Invest through multiple channels; Seek services from competitors; No exclusivity obligation on Stakeholder.</li>
            <li>(b) <strong>Stakeholder Not Exclusive:</strong> Platform may: Provide services to other stakeholders; Deal in same securities with multiple clients; Operate non-exclusive business model;</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">38.10 Publicity and Use of Name</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Stakeholder Consent:</strong> Platform may not use Stakeholder's name, logo, or identifying information in: Marketing materials; Case studies or testimonials; Investor presentations; Website or promotional content; Without prior written consent, except: Aggregated anonymous statistics; Required regulatory disclosures; Legal proceedings or regulatory filings;</li>
            <li>(b) <strong>Platform's Proprietary Rights:</strong> Platform name, logo, trademarks, and brand are proprietary: Stakeholder may not use without authorization; No license granted except for accessing services;</li>
          </ul>
        </div>
      </div>

      {/* ARTICLE 39 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Building className="text-indigo-600" size={20} /> ARTICLE 39: CONTACT INFORMATION AND GRIEVANCE OFFICERS
        </h3>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          
          <div className="bg-slate-50 p-6 rounded-lg border border-slate-200">
            <h4 className="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2"><Building size={18}/> 39.1 Registered Office</h4>
            <div className="text-sm text-slate-600 space-y-2">
              <p>[Legal Entity Name]<br/>[Complete Registered Office Address]<br/>[City, State, PIN Code]</p>
              <p><strong>Phone:</strong> [Office Landline Number]</p>
              <p><strong>Email:</strong> info@preiposip.com</p>
              <p><strong>Website:</strong> www.preiposip.com</p>
              <p><strong>CIN:</strong> [●]</p>
              <p><strong>PAN:</strong> [●]</p>
              <p><strong>GSTIN:</strong> [●]</p>
              <p><strong>SEBI Registration:</strong> [●]</p>
            </div>
          </div>

          <div className="bg-slate-50 p-6 rounded-lg border border-slate-200">
            <h4 className="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2"><User size={18}/> 39.2 Principal Officer</h4>
            <div className="text-sm text-slate-600 space-y-2">
              <p><strong>Name:</strong> [Name]</p>
              <p><strong>Designation:</strong> Managing Director / Chief Executive Officer</p>
              <p><strong>Email:</strong> principalofficer@preiposip.com</p>
              <p><strong>Phone:</strong> [Direct Number]</p>
            </div>
          </div>

          <div className="bg-slate-50 p-6 rounded-lg border border-slate-200">
            <h4 className="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2"><User size={18}/> 39.3 Chief Compliance Officer</h4>
            <div className="text-sm text-slate-600 space-y-2">
              <p><strong>Name:</strong> [Name]</p>
              <p><strong>Designation:</strong> Chief Compliance Officer</p>
              <p><strong>Email:</strong> cco@preiposip.com / compliance@preiposip.com</p>
              <p><strong>Phone:</strong> [Direct Number]</p>
              <p className="mt-2 text-xs italic">Responsibilities: Overall regulatory compliance; Interface with SEBI and other regulators; Final internal escalation for grievances; Policy interpretation and guidance;</p>
            </div>
          </div>

          <div className="bg-slate-50 p-6 rounded-lg border border-slate-200">
            <h4 className="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2"><User size={18}/> 39.4 Grievance Redressal Officer</h4>
            <div className="text-sm text-slate-600 space-y-2">
              <p><strong>Name:</strong> [Name]</p>
              <p><strong>Designation:</strong> Grievance Redressal Officer - Refunds</p>
              <p><strong>Email:</strong> grievances.refunds@preiposip.com</p>
              <p><strong>Phone:</strong> [Dedicated Helpline]</p>
              <p><strong>Working Hours:</strong> 10:00 AM to 6:00 PM (Monday to Friday, excluding public holidays)</p>
              <p className="mt-2 text-xs italic">Responsibilities: Receipt and acknowledgment of refund-related grievances; Investigation and resolution; Communication with Stakeholders; Escalation to higher authorities if required;</p>
            </div>
          </div>

          <div className="bg-slate-50 p-6 rounded-lg border border-slate-200">
            <h4 className="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2"><User size={18}/> 39.5 Nodal Officer</h4>
            <div className="text-sm text-slate-600 space-y-2">
              <p><strong>Name:</strong> [Name]</p>
              <p><strong>Designation:</strong> Nodal Officer - Customer Grievances</p>
              <p><strong>Email:</strong> nodal.officer@preiposip.com</p>
              <p><strong>Phone:</strong> [Direct Number]</p>
              <p className="mt-2 text-xs italic">Responsibilities: Second-level escalation for unresolved grievances; Review and override of first-level decisions; Monthly reporting to senior management;</p>
            </div>
          </div>

          <div className="bg-slate-50 p-6 rounded-lg border border-slate-200">
            <h4 className="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2"><Phone size={18}/> 39.6 Investor Relations</h4>
            <div className="text-sm text-slate-600 space-y-2">
              <p><strong>Email:</strong> investor.relations@preiposip.com</p>
              <p><strong>Phone:</strong> [Helpline Number]</p>
              <p className="mt-2 text-xs italic">For queries regarding: Investment opportunities; Transaction status; General information;</p>
            </div>
          </div>

          <div className="bg-slate-50 p-6 rounded-lg border border-slate-200">
            <h4 className="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2"><Phone size={18}/> 39.7 Customer Support</h4>
            <div className="text-sm text-slate-600 space-y-2">
              <p><strong>Email:</strong> support@preiposip.com</p>
              <p><strong>Phone:</strong> [Customer Support Number]</p>
              <p><strong>Live Chat:</strong> Available on website during business hours</p>
              <p><strong>Working Hours:</strong> 9:00 AM to 7:00 PM (Monday to Saturday)</p>
              <p className="mt-2 text-xs italic">For: Technical support; Account access issues; General queries;</p>
            </div>
          </div>

          <div className="bg-slate-50 p-6 rounded-lg border border-slate-200">
            <h4 className="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2"><Building size={18}/> 39.8 Legal and Compliance Department</h4>
            <div className="text-sm text-slate-600 space-y-2">
              <p><strong>Email:</strong> legal@preiposip.com</p>
              <p><strong>Phone:</strong> [Legal Department Number]</p>
              <p className="mt-2 text-xs italic">For: Legal notices; Regulatory correspondence; Litigation matters; Compliance queries;</p>
            </div>
          </div>

          <div className="bg-slate-50 p-6 rounded-lg border border-slate-200">
            <h4 className="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2"><User size={18}/> 39.9 Data Protection Officer (DPO)</h4>
            <div className="text-sm text-slate-600 space-y-2">
              <p><strong>Name:</strong> [Name]</p>
              <p><strong>Designation:</strong> Data Protection Officer</p>
              <p><strong>Email:</strong> dpo@preiposip.com / privacy@preiposip.com</p>
              <p><strong>Phone:</strong> [Direct Number]</p>
              <p className="mt-2 text-xs italic">For: Privacy concerns; Data access requests; Data protection complaints; Consent management;</p>
            </div>
          </div>

          <div className="bg-slate-50 p-6 rounded-lg border border-slate-200">
            <h4 className="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2"><User size={18}/> 39.10 Designated Director (PMLA)</h4>
            <div className="text-sm text-slate-600 space-y-2">
              <p><strong>Name:</strong> [Name]</p>
              <p><strong>Designation:</strong> Designated Director for PMLA Compliance</p>
              <p><strong>Email:</strong> aml@preiposip.com</p>
              <p><strong>Phone:</strong> [Direct Number]</p>
              <p className="mt-2 text-xs italic">Responsibilities: AML/CFT compliance; Interface with FIU-IND and law enforcement; Suspicious transaction reporting;</p>
            </div>
          </div>

        </div>
      </div>
    </section>
  );
}
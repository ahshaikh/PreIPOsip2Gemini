'use client';

import React from 'react';
import { HelpCircle, Scale, Gavel, MapPin } from 'lucide-react';

export default function RefundPolicyPart7() {
  return (
    <section id="part-7" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 7</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">GRIEVANCE REDRESSAL MECHANISMS, DISPUTE RESOLUTION, ARBITRATION PROVISIONS, AND JURISDICTION</h2>
      </div>

      {/* ARTICLE 20 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <HelpCircle className="text-indigo-600" size={20} /> ARTICLE 20: GRIEVANCE REDRESSAL FRAMEWORK
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">20.1 Commitment to Fair Grievance Resolution</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Fundamental Principle:</strong> The Platform is committed to fair, transparent, and expeditious resolution of all grievances, complaints, and disputes raised by Stakeholders in relation to refund matters;</li>
            <li>(b) <strong>Accessibility:</strong> Grievance redressal mechanisms are designed to be accessible, user-friendly, and cost-effective for Stakeholders of all categories;</li>
            <li>(c) <strong>Non-Retaliation:</strong> Platform commits that no Stakeholder shall suffer adverse consequences, discrimination, or retaliation for raising genuine grievances in good faith;</li>
            <li>(d) <strong>Regulatory Alignment:</strong> The grievance redressal framework complies with: SEBI (Investment Advisers) Regulations, 2013; SEBI Circular on investor grievance redressal; Consumer Protection Act, 2019 requirements; Industry best practices and ISO standards for complaint management;</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">20.2 Designated Grievance Redressal Officers</h4>
          
          <div className="ml-4 space-y-4">
            <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
              <p className="text-slate-700 font-bold mb-2">(a) Primary Grievance Redressal Officer</p>
              <div className="text-sm text-slate-600 space-y-1">
                <p><strong>Name:</strong> [To be designated by Platform]</p>
                <p><strong>Designation:</strong> Grievance Redressal Officer - Refunds</p>
                <p><strong>Contact Details:</strong></p>
                <ul className="list-disc pl-6">
                  <li>Email: grievances.refunds@preiposip.com</li>
                  <li>Phone: [Dedicated Helpline Number with IVR]</li>
                  <li>Address: [Registered Office Address]</li>
                  <li>Working Hours: 10:00 AM to 6:00 PM (Monday to Friday, excluding public holidays)</li>
                </ul>
                <p><strong>Responsibilities:</strong> First point of contact for all refund-related grievances; Receipt, acknowledgment, and initial assessment of complaints; Coordination with relevant departments for investigation; Communication of resolution or escalation to higher levels; Maintenance of grievance register and periodic reporting;</p>
              </div>
            </div>

            <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
              <p className="text-slate-700 font-bold mb-2">(b) Nodal Officer (Second Level)</p>
              <div className="text-sm text-slate-600 space-y-1">
                <p><strong>Name:</strong> [Senior Management Designation]</p>
                <p><strong>Designation:</strong> Nodal Officer - Customer Grievances</p>
                <p><strong>Contact Details:</strong></p>
                <ul className="list-disc pl-6">
                  <li>Email: nodal.officer@preiposip.com</li>
                  <li>Phone: [Direct Line]</li>
                  <li>Address: [Registered Office Address]</li>
                </ul>
                <p><strong>Responsibilities:</strong> Review of grievances not resolved satisfactorily at first level; Oversight of grievance redressal process; Authority to modify decisions or provide ex-gratia relief; Interface with regulatory authorities on systemic grievances; Monthly reporting to senior management and Board;</p>
              </div>
            </div>

            <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
              <p className="text-slate-700 font-bold mb-2">(c) Chief Compliance Officer (Third Level)</p>
              <div className="text-sm text-slate-600 space-y-1">
                <p><strong>Name:</strong> [As per SEBI Registration]</p>
                <p><strong>Designation:</strong> Chief Compliance Officer</p>
                <p><strong>Contact Details:</strong></p>
                <ul className="list-disc pl-6">
                  <li>Email: cco@preiposip.com</li>
                  <li>Phone: [Direct Line]</li>
                  <li>Address: [Registered Office Address]</li>
                </ul>
                <p><strong>Responsibilities:</strong> Final internal escalation point before external dispute resolution; Review of policy application and interpretation issues; Authority to waive or modify policy provisions in exceptional cases; Regulatory compliance oversight; Reporting to Board and regulatory authorities;</p>
              </div>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">20.3 Grievance Submission Channels</h4>
          <p className="text-slate-600 mb-2">Stakeholders may submit grievances through the following channels:</p>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Online Grievance Portal</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Access:</strong> Available through User Account under "Grievances" or "Support" section;</li>
                <li>(ii) <strong>Process:</strong> Login to User Account with credentials; Navigate to Grievance Portal; Select category: "Refund Related Grievance"; Fill online grievance form with: Transaction/Refund Request Number; Nature of grievance (dropdown menu with categories); Detailed description (minimum 100 words); Upload supporting documents; Expected resolution or relief sought; Submit and receive auto-generated Grievance Number (GRN);</li>
                <li>(iii) <strong>Advantages:</strong> Real-time tracking of grievance status; Automated acknowledgments and updates; Complete audit trail and documentation; Secure and confidential;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Email</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Send detailed email to: grievances.refunds@preiposip.com</li>
                <li>(ii) Subject Line Format: "Grievance - [RRN/Transaction ID] - [Brief Issue]"</li>
                <li>(iii) Mandatory Content: Full name and contact details; User Account ID; Refund Request Number or Transaction ID; Date of original transaction and refund request; Detailed grievance description; Chronology of events; Previous communications with Platform; Relief sought; Attachments: Supporting documents;</li>
                <li>(iv) Acknowledgment: Within 2 Business Days via email with GRN;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Registered Post/Courier</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Address To: The Grievance Redressal Officer - Refunds, [Legal Entity Name], [Complete Registered Office Address with PIN Code]</li>
                <li>(ii) Format: Typed or clearly handwritten complaint letter containing all information specified for email submissions;</li>
                <li>(iii) Enclosures: Copies of relevant documents (not originals); Self-attested identity proof; Contact details for communication;</li>
                <li>(iv) Acknowledgment: Within 5 Business Days of receipt via email and post;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Telephone Helpline</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Grievance Helpline: [Dedicated Number with IVR]</li>
                <li>(ii) Operational Hours: 10:00 AM to 6:00 PM on Business Days;</li>
                <li>(iii) Process: IVR menu for grievance registration; Call routing to trained grievance officer; Verbal complaint recorded in system; Call reference number provided; Follow-up email sent to registered email address for confirmation;</li>
                <li>(iv) Limitations: Telephonic registration for preliminary logging only; Written submission required for formal processing; Call recordings maintained for 6 months;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(e) Physical Visit (By Appointment)</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Available at Platform's registered office by prior appointment;</li>
                <li>(ii) Appointment Booking: Through email or phone helpline at least 3 Business Days in advance;</li>
                <li>(iii) Documentation: Stakeholder must bring identity proof and relevant documents;</li>
                <li>(iv) Benefits: Personal hearing, face-to-face clarification, immediate documentation verification;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">20.4 Grievance Processing Timeline</h4>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold mb-2">(a) Standard Processing Timeline</p>
              <div className="overflow-x-auto mb-2 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                  <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                    <tr>
                      <th className="px-6 py-3">Stage</th>
                      <th className="px-6 py-3">Timeline</th>
                      <th className="px-6 py-3">Responsibility</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Acknowledgment</td><td className="px-6 py-4">2 Business Days from receipt</td><td className="px-6 py-4">Grievance Redressal Officer</td></tr>
                    <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Initial Assessment</td><td className="px-6 py-4">5 Business Days from acknowledgment</td><td className="px-6 py-4">Grievance Redressal Officer</td></tr>
                    <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Investigation & Fact-Finding</td><td className="px-6 py-4">10 Business Days from assessment</td><td className="px-6 py-4">Concerned Department + GRO</td></tr>
                    <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Resolution Communication</td><td className="px-6 py-4">3 Business Days from investigation completion</td><td className="px-6 py-4">Grievance Redressal Officer</td></tr>
                    <tr className="bg-white"><td className="px-6 py-4 font-bold">Total Target Timeline</td><td className="px-6 py-4 font-bold">20 Business Days</td><td className="px-6 py-4">-</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div>
              <p className="text-slate-700 font-semibold mb-2">(b) Complex Grievance Timeline</p>
              <p className="text-slate-600 mb-1">For grievances involving: Legal interpretation or external legal opinion; Third-party coordination (banks, issuers, regulators); Forensic investigation or audit; Senior management review or Board approval;</p>
              <p className="text-slate-600">Extended Timeline: Up to 45 Business Days with periodic updates to Stakeholder at 15-day intervals;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold mb-2">(c) Interim Relief</p>
              <p className="text-slate-600">Where appropriate and feasible, Platform may offer interim relief pending full resolution: Partial refund on account; Interest payment for acknowledged delays; Waiver of certain charges; Service credits or goodwill gestures; Acceptance of interim relief without prejudice to final resolution;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">20.5 Grievance Categories and Resolution Approach</h4>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Documentation-Related Grievances</p>
              <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li><strong>Common Issues:</strong> Deficiency memoranda disputed by Stakeholder; Documents allegedly not received or considered; Verification delays or repeated requests;</li>
                <li><strong>Resolution Approach:</strong> Review of submission records and delivery confirmations; Re-verification of documents if discrepancy identified; Clear communication of specific deficiencies; Extension of time for documentation if justified;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Calculation and Deduction Grievances</p>
              <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li><strong>Common Issues:</strong> Disagreement with refund calculation; Deductions claimed to be excessive or unjustified; Charges not previously disclosed; Interest calculation disputes;</li>
                <li><strong>Resolution Approach:</strong> Detailed breakup of calculation provided; Justification for each deduction with policy/agreement reference; Independent review by finance team if error suspected; Correction and supplementary payment if error confirmed;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Timeline and Delay Grievances</p>
              <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li><strong>Common Issues:</strong> Refund processing exceeding committed timeline; Lack of status updates; Unresponsive customer support;</li>
                <li><strong>Resolution Approach:</strong> Investigation of delay reasons; Expedited processing if delay unjustified; Interest compensation for delays attributable to Platform; Improved communication protocols;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Eligibility and Rejection Grievances</p>
              <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li><strong>Common Issues:</strong> Disagreement with rejection decision; Belief that policy misapplied or misinterpreted; New facts or evidence not previously considered;</li>
                <li><strong>Resolution Approach:</strong> Fresh review by independent officer not involved in original decision; Consideration of additional evidence or arguments; Legal or compliance team consultation if policy interpretation issue; Detailed reasoning for upholding or modifying decision;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(e) Service Quality Grievances</p>
              <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li><strong>Common Issues:</strong> Unprofessional behavior by Platform personnel; Misinformation or conflicting information provided; Communication gaps or unresponsive support;</li>
                <li><strong>Resolution Approach:</strong> Review of interaction records (emails, call recordings); Counseling or disciplinary action against erring personnel; Apology and assurance of improved service; Relationship manager assignment for high-value clients;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(f) Technical and System Grievances</p>
              <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li><strong>Common Issues:</strong> Portal malfunction preventing grievance submission; Failed payment attempts; Data display errors; Access issues with User Account;</li>
                <li><strong>Resolution Approach:</strong> IT team investigation and troubleshooting; Alternate submission channels facilitated; System corrections and patches; User training or guidance for proper usage;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">20.6 Grievance Resolution Outcomes</h4>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Possible Outcomes</p>
              <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(i) <strong>Grievance Upheld:</strong> Platform acknowledges error or deficiency; Corrective action taken (supplementary refund, interest payment, waiver of charges); Formal apology if service failure; Systemic improvements to prevent recurrence;</li>
                <li>(ii) <strong>Grievance Partially Upheld:</strong> Some aspects of grievance found valid, others not; Proportionate relief provided; Detailed reasoning for partial acceptance;</li>
                <li>(iii) <strong>Grievance Not Upheld:</strong> After thorough investigation, Platform's original position found correct; Detailed explanation of reasons; Reference to applicable policy provisions and facts; Information on further escalation options;</li>
                <li>(iv) <strong>Mutually Agreed Settlement:</strong> Through discussion, mutually acceptable resolution reached; May involve compromise by both parties; Documented in settlement agreement; Full and final settlement of grievance;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Communication of Resolution</p>
              <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(i) <strong>Mode:</strong> Email (primary) + SMS notification + User Account update + Registered post (for high-value);</li>
                <li>(ii) <strong>Content of Resolution Communication:</strong> GRN and Stakeholder details; Summary of grievance; Investigation findings; Decision with detailed reasoning; Actions taken or to be taken; Timeline for implementation (if applicable); Further escalation rights and process; Contact for clarifications;</li>
                <li>(iii) <strong>Confirmation:</strong> Stakeholder requested to acknowledge receipt and acceptance;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Implementation Monitoring</p>
              <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(i) If resolution involves corrective action (payment, process change): Timeline for implementation specified; Responsibility assigned; Follow-up to ensure completion; Stakeholder updated upon completion;</li>
                <li>(ii) Quality control review to verify satisfactory resolution;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">20.7 Escalation Matrix</h4>
          <p className="text-slate-600 mb-2">If Stakeholder dissatisfied with resolution at any level:</p>
          <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
            <table className="w-full text-sm text-left text-slate-600">
              <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="px-6 py-3">Level</th>
                  <th className="px-6 py-3">Authority</th>
                  <th className="px-6 py-3">Timeline for Resolution</th>
                  <th className="px-6 py-3">Next Escalation</th>
                </tr>
              </thead>
              <tbody>
                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Level 1</td><td className="px-6 py-4">Grievance Redressal Officer</td><td className="px-6 py-4">20 Business Days</td><td className="px-6 py-4">Nodal Officer</td></tr>
                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Level 2</td><td className="px-6 py-4">Nodal Officer</td><td className="px-6 py-4">15 Business Days from escalation</td><td className="px-6 py-4">Chief Compliance Officer</td></tr>
                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Level 3</td><td className="px-6 py-4">Chief Compliance Officer</td><td className="px-6 py-4">15 Business Days from escalation</td><td className="px-6 py-4">External Dispute Resolution</td></tr>
                <tr className="bg-white"><td className="px-6 py-4">External</td><td className="px-6 py-4">Ombudsman/Arbitration/Court</td><td className="px-6 py-4">As per applicable process</td><td className="px-6 py-4">-</td></tr>
              </tbody>
            </table>
          </div>
          <p className="text-slate-600">Total Internal Resolution Time: Maximum 50 Business Days across all internal levels;</p>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">20.8 Record Maintenance and Reporting</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Grievance Register</p>
              <p className="text-slate-600 mb-1">Platform maintains comprehensive grievance register containing:</p>
              <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>GRN and Stakeholder details; Date of receipt and acknowledgment; Category and nature of grievance; Officer assigned and investigation details; Timeline of actions taken; Resolution outcome and date; Stakeholder satisfaction (if feedback provided);</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Periodic Reporting</p>
              <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(i) <strong>Monthly Reports to Senior Management:</strong> Total grievances received, pending, resolved; Category-wise analysis; Average resolution time; Upheld vs. rejected ratio; Systemic issues identified; Process improvement recommendations;</li>
                <li>(ii) <strong>Quarterly Reports to Board of Directors:</strong> Comprehensive grievance analysis; Trends and patterns; High-value or sensitive grievances; Regulatory complaints or escalations; Compliance with grievance redressal norms;</li>
                <li>(iii) <strong>Annual Report on Grievances:</strong> Published on Platform's website; Year-wise statistics and analysis; Process improvements implemented; Demonstrates commitment to stakeholder protection;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Regulatory Reporting</p>
              <p className="text-slate-600 mb-1">As required under Applicable Law:</p>
              <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Monthly/quarterly grievance reports to SEBI; Reporting of unresolved grievances; SCORES (SEBI Complaints Redress System) integration; Cooperation with regulatory inquiries;</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      {/* ARTICLE 21 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Scale className="text-indigo-600" size={20} /> ARTICLE 21: EXTERNAL DISPUTE RESOLUTION MECHANISMS
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">21.1 Escalation to External Forums</h4>
          <p className="text-slate-600 mb-2">If internal grievance redressal does not result in satisfactory resolution, Stakeholder may pursue the following external mechanisms:</p>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) SEBI Complaints Redress System (SCORES)</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Applicability:</strong> For grievances involving securities market activities, intermediary conduct, or investor protection issues;</li>
                <li>(ii) <strong>Process:</strong> Online registration at scores.sebi.gov.in; Complaint tracked through unique reference number; Platform required to respond within 30 days; SEBI may facilitate resolution or take regulatory action;</li>
                <li>(iii) <strong>Advantages:</strong> No cost to investor; SEBI intervention carries regulatory weight; Platform motivated to resolve to avoid regulatory scrutiny;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Banking Ombudsman</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Applicability:</strong> For grievances related to: Refund payment failures due to bank issues; Unauthorized debits or credits; Non-adherence to banking codes;</li>
                <li>(ii) <strong>Eligibility:</strong> Complaint first raised with Platform; Platform's reply unsatisfactory or no reply within 30 days; Matter not pending in court/arbitration/consumer forum; Complaint within 1 year of Platform's reply or 1 year + 30 days if no reply;</li>
                <li>(iii) <strong>Jurisdiction:</strong> Based on Platform's banker's location;</li>
                <li>(iv) <strong>Process:</strong> As per Banking Ombudsman Scheme, 2006 (as amended);</li>
                <li>(v) <strong>Award:</strong> Binding on Platform if up to ₹20,00,000 (current limit, subject to change);</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Consumer Disputes Redressal Forums</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Applicability:</strong> Refund matters may qualify as "deficiency in service" under Consumer Protection Act, 2019;</li>
                <li>(ii) <strong>Three-Tier Structure:</strong> District Consumer Disputes Redressal Commission: Claims up to ₹1 crore; State Consumer Disputes Redressal Commission: Claims from ₹1 crore to ₹10 crore; National Consumer Disputes Redressal Commission: Claims above ₹10 crore;</li>
                <li>(iii) <strong>Procedure:</strong> Complaint filed personally or through authorized agent; Relatively simple, less formal procedure; Limited court fees; Matter decided within 3-5 months (ideally, but often longer);</li>
                <li>(iv) <strong>Relief:</strong> Compensation, refund, removal of deficiency, punitive damages possible;</li>
                <li>(v) <strong>Appeals:</strong> Hierarchical appeal structure up to Supreme Court;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Civil Courts</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Applicability:</strong> Breach of contract claims, specific performance, injunctions, declaratory relief;</li>
                <li>(ii) <strong>Jurisdiction:</strong> Territorial: As per Section 20, Code of Civil Procedure, 1908 (discussed in Article 23); Pecuniary: Based on claim value and court hierarchy;</li>
                <li>(iii) <strong>Procedure:</strong> Formal litigation process with pleadings, evidence, arguments;</li>
                <li>(iv) <strong>Timeline:</strong> Variable, typically 3-10 years depending on complexity and court backlog;</li>
                <li>(v) <strong>Costs:</strong> Court fees, lawyer fees, other litigation expenses;</li>
                <li>(vi) <strong>Appeals:</strong> Multiple appeal tiers up to High Court and Supreme Court;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">21.2 Mediation and Conciliation</h4>
          
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Voluntary Mediation</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Platform encourages mediation as cost-effective and expeditious alternative to litigation;</li>
                <li>(ii) <strong>Process:</strong> Either party proposes mediation; Mutually acceptable mediator appointed (may be retired judge, senior lawyer, industry expert); Mediation sessions conducted without prejudice; Mediator facilitates settlement but has no decision-making power; Settlement agreement, if reached, legally binding;</li>
                <li>(iii) <strong>Advantages:</strong> Confidential and private; Preserves business relationship; Flexible and creative solutions possible; Cost-effective and faster than litigation/arbitration;</li>
                <li>(iv) <strong>Cost Sharing:</strong> Mediator's fees typically shared equally unless otherwise agreed;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Conciliation under Arbitration and Conciliation Act, 1996</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Formal conciliation process as per Part III of Arbitration and Conciliation Act, 1996;</li>
                <li>(ii) Conciliator appointed by agreement or institutional body;</li>
                <li>(iii) Conciliation settlement has status of arbitral award under Section 73;</li>
                <li>(iv) Enforceable as decree of court;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Pre-Litigation Mediation</p>
              <p className="text-slate-600">Platform may make mediation mandatory before initiating arbitration for disputes below ₹25,00,000, providing: Stakeholder consents to mediation; Mediation conducted within 60 days; If mediation fails, arbitration/litigation rights unaffected;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">21.3 Industry Dispute Resolution Forums</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Self-Regulatory Organization (SRO) Mechanisms</p>
              <p className="text-slate-600">If Platform is member of any SRO or industry association: SRO's dispute resolution mechanism may be available; Typically involves conciliation or binding arbitration; Industry-specific expertise and faster resolution;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Institutional Arbitration</p>
              <p className="text-slate-600">For disputes where parties agree to institutional arbitration: Mumbai Centre for International Arbitration (MCIA); Indian Council of Arbitration (ICA); International Chamber of Commerce (ICC) - for international disputes; Singapore International Arbitration Centre (SIAC) - if agreed;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">21.4 Choice of Forum Considerations</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Factors for Stakeholder Consideration</p>
              <p className="text-slate-600 mb-1">When choosing dispute resolution forum, Stakeholders should consider:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Cost:</strong> Arbitration and courts involve significant costs; consumer forums and SCORES relatively inexpensive;</li>
                <li>(ii) <strong>Time:</strong> Mediation fastest; arbitration moderate; litigation slowest;</li>
                <li>(iii) <strong>Expertise:</strong> Arbitration allows choice of expert arbitrator; courts and consumer forums have generalist judges/members;</li>
                <li>(iv) <strong>Formality:</strong> Courts most formal; mediation least formal;</li>
                <li>(v) <strong>Confidentiality:</strong> Arbitration and mediation confidential; court proceedings public;</li>
                <li>(vi) <strong>Enforceability:</strong> Court decrees and arbitral awards enforceable; mediated settlements require embodiment in contract or court order;</li>
                <li>(vii) <strong>Finality:</strong> Arbitration awards have limited appeal rights; court judgments have extensive appeal hierarchy;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Platform's Preference</p>
              <p className="text-slate-600">Platform prefers arbitration for structured, expert-driven, and confidential dispute resolution, as detailed in Article 22;</p>
            </div>
          </div>
        </div>
      </div>

      {/* ARTICLE 22 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Gavel className="text-indigo-600" size={20} /> ARTICLE 22: ARBITRATION PROVISIONS
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">22.1 Arbitration Agreement</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Binding Arbitration Clause</p>
              <p className="text-slate-600 mb-1">Subject to Article 22.3 (exclusions), all disputes, differences, claims, or controversies arising out of or in connection with this Policy, including:</p>
              <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Interpretation or application of this Policy;</li>
                <li>Validity, enforceability, or breach of this Policy;</li>
                <li>Refund eligibility, calculation, processing, or payment;</li>
                <li>Any transaction or service giving rise to refund claim;</li>
                <li>Any aspect of relationship between Platform and Stakeholder;</li>
              </ul>
              <p className="text-slate-600 mt-1">Shall be resolved exclusively through binding arbitration in accordance with the Arbitration and Conciliation Act, 1996 (as amended), to the exclusion of the jurisdiction of civil courts, except as provided in the said Act.</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Survival of Arbitration Clause</p>
              <p className="text-slate-600">This arbitration agreement shall survive: Termination or expiry of Stakeholder's relationship with Platform; Completion, cancellation, or frustration of transactions; Invalidity of other provisions of this Policy;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Separability</p>
              <p className="text-slate-600">The arbitration clause is separable from other provisions of this Policy. Invalidity of the Policy or transaction does not affect validity of the arbitration agreement.</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">22.2 Arbitration Procedure</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Commencement of Arbitration</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Notice Requirement:</strong> Party seeking arbitration ("Claimant") shall serve written notice of arbitration on other party ("Respondent") containing: Statement of claim with facts and relief sought; Estimated quantum of claim; Nomination of arbitrator (if applicable); Relevant documents;</li>
                <li>(ii) <strong>Mandatory Pre-Arbitration Negotiation:</strong> Before invoking arbitration, parties shall attempt to resolve dispute through good faith negotiation for 30 days; Senior management of both sides shall participate in at least one meeting; If negotiation fails, either party may invoke arbitration;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Number and Appointment of Arbitrators</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>For Disputes up to ₹50,00,000 (Fifty Lakhs):</strong> Sole Arbitrator appointed by mutual agreement; If parties fail to agree within 30 days of notice: Arbitrator appointed by Mumbai Centre for International Arbitration (MCIA) or such other appointing authority as Platform may designate;</li>
                <li>(ii) <strong>For Disputes exceeding ₹50,00,000:</strong> Tribunal of Three Arbitrators: Each party appoints one arbitrator; Two appointed arbitrators appoint third (presiding) arbitrator; If any party fails to appoint or arbitrators fail to agree on presiding arbitrator: MCIA or designated authority makes appointment;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Qualifications of Arbitrators</p>
              <p className="text-slate-600 mb-1">Arbitrators shall:</p>
              <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Be independent and impartial (as per Section 12, Arbitration Act);</li>
                <li>Preferably have expertise in: Securities law and SEBI regulations; OR Commercial contracts and financial services; OR Corporate law and mergers & acquisitions;</li>
                <li>Be retired High Court/Supreme Court judges, Senior Advocates, or experienced chartered accountants/company secretaries;</li>
                <li>Disclose any circumstances likely to give rise to justifiable doubts about independence;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Seat and Venue of Arbitration</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Seat of Arbitration:</strong> Mumbai, Maharashtra, India; Determines supervisory jurisdiction of courts (Bombay High Court); Determines applicable procedural law (Indian Arbitration Act);</li>
                <li>(ii) <strong>Venue/Place of Hearings:</strong> Mumbai, unless tribunal decides otherwise for convenience; Virtual hearings permitted with consent of parties;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(e) Language</p>
              <p className="text-slate-600">Language of arbitration: English; All pleadings, evidence, and proceedings in English; If documents in other languages: Certified English translations required;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(f) Governing Law</p>
              <p className="text-slate-600">Substantive law governing this Policy and disputes: Laws of India; Procedural law governing arbitration: Arbitration and Conciliation Act, 1996 (as amended);</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(g) Arbitration Rules</p>
              <p className="text-slate-600">Arbitration conducted under: MCIA Arbitration Rules (current version at time of notice); OR If parties mutually agree: UNCITRAL Arbitration Rules or institutional rules of agreed arbitration institution;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(h) Pleadings and Evidence</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Timelines (indicative, subject to tribunal's discretion):</strong> Statement of Claim: Within 30 days of tribunal constitution; Statement of Defense (and Counterclaim, if any): Within 45 days of receiving Claim; Reply to Defense and Defense to Counterclaim: Within 30 days;</li>
                <li>(ii) <strong>Document Production:</strong> Parties exchange relevant documents with pleadings; Tribunal may order specific document production if relevant and material; No fishing expeditions permitted;</li>
                <li>(iii) <strong>Witness Evidence:</strong> Witness statements exchanged as per tribunal's procedural order; Cross-examination at oral hearings; Expert witnesses permitted with tribunal's leave;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(i) Oral Hearings</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Oral hearings held at tribunal's discretion or party's request;</li>
                <li>(ii) Typically final hearing after pleadings and document exchange;</li>
                <li>(iii) Arguments, witness examination, expert presentations;</li>
                <li>(iv) Hearing transcripts if requested by parties (cost shared);</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(j) Interim Relief</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Tribunal has power to grant interim measures under Section 17, Arbitration Act: Interim injunctions; Appointment of receiver or guardian; Interim payments; Security for costs; Preservation or inspection of property;</li>
                <li>(ii) Emergency arbitrator provisions (if institutional rules provide) available for urgent interim relief before tribunal constitution;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(k) Fast-Track Arbitration</p>
              <p className="text-slate-600">For disputes up to ₹25,00,000 and where both parties consent: Fast-track procedure under Section 29B, Arbitration Act; Award within 6 months of tribunal constitution; Simplified procedure: Limited pleadings, documentary evidence, minimal oral hearings; Sole arbitrator; Cost-effective and expeditious;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">22.3 Exclusions from Arbitration</h4>
          <p className="text-slate-600 mb-2">Notwithstanding Article 22.1, the following matters shall NOT be subject to arbitration and may be pursued in appropriate courts or forums:</p>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Urgent Interim Relief:</strong> Application to civil courts for urgent interim orders under Section 9, Arbitration Act before tribunal constituted or in exceptional circumstances;</li>
            <li>(b) <strong>Enforcement of Awards:</strong> Applications to enforce arbitral awards under Section 36, Arbitration Act;</li>
            <li>(c) <strong>Setting Aside of Awards:</strong> Applications under Section 34, Arbitration Act to set aside awards (limited grounds);</li>
            <li>(d) <strong>Small Claims:</strong> Claims below ₹2,00,000 may be pursued in consumer forums if Stakeholder prefers (option available to Stakeholder);</li>
            <li>(e) <strong>Criminal Matters:</strong> Fraud, forgery, cheating, or other criminal offenses not arbitrable; concurrent criminal proceedings possible;</li>
            <li>(f) <strong>Regulatory Proceedings:</strong> SEBI proceedings, RBI actions, or other regulatory enforcement not arbitrable; concurrent proceedings possible;</li>
            <li>(g) <strong>Statutory Appeals:</strong> Appeals from arbitral awards to High Court/Supreme Court as per Arbitration Act;</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">22.4 Arbitral Award</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Form and Content</p>
              <p className="text-slate-600">Award shall: Be in writing and signed by arbitrators; State reasons unless parties waive reasoned award; Specify date and seat of arbitration; Be final and binding on parties;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Remedies Available</p>
              <p className="text-slate-600">Tribunal may award: Refund of amounts as per Policy and law; Interest at rates specified in Policy or as tribunal determines; Damages (compensatory, not punitive); Specific performance of obligations; Costs of arbitration; Tribunal shall not award: Relief beyond what is claimed; Punitive or exemplary damages (unless statute permits); Relief contrary to public policy or Applicable Law;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Costs</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Arbitration Costs include:</strong> Arbitrator's fees and expenses; Institutional administrative fees (if applicable); Venue and logistical costs; Transcript and translation costs;</li>
                <li>(ii) <strong>Party Costs include:</strong> Legal fees and lawyer's charges; Expert witness fees; Travel and accommodation for hearings;</li>
                <li>(iii) <strong>Cost Allocation:</strong> Generally, costs follow the event (losing party pays); Tribunal has discretion to apportion costs based on: Extent of success of each party; Conduct of parties; Reasonableness of claims and defenses; Offers to settle;</li>
                <li>(iv) <strong>Security for Costs:</strong> Tribunal may order Claimant to provide security for Respondent's costs if Claimant resident outside India or financial instability;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Interest on Award Amount</p>
              <p className="text-slate-600">Tribunal may award interest on principal sum at such rate as deemed reasonable; Interest may be awarded for: Pre-reference period (before arbitration notice); Pendente lite (during arbitration); Post-award till payment (unless statute prohibits);</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(e) Timeline for Award</p>
              <p className="text-slate-600">Award should be rendered within 12 months of tribunal constitution (as per Section 29A, Arbitration Act); Extendable by 6 months with parties' consent; Further extension requires court approval; Failure to meet timeline may have fee implications for arbitrators;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">22.5 Enforcement of Arbitral Awards</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Domestic Awards</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Award deemed decree of civil court upon expiry of period for setting aside;</li>
                <li>(ii) Enforceable under Code of Civil Procedure, 1908 through executing court;</li>
                <li>(iii) If challenge under Section 34 pending: Execution stayed;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Foreign Awards</p>
              <p className="text-slate-600">If arbitration held outside India (exceptional cases): Enforcement under New York Convention (India signatory) or Geneva Convention; Application to appropriate court in India; Limited grounds for refusing enforcement;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Voluntary Compliance</p>
              <p className="text-slate-600">Platform commits to voluntarily comply with arbitral awards without requiring forced execution, subject to: Award not set aside or stayed by court; Award within tribunal's jurisdiction and not contrary to public policy; Reasonable time (30 days) allowed for compliance;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">22.6 Confidentiality of Arbitration</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Mandatory Confidentiality</p>
              <p className="text-slate-600">All aspects of arbitration confidential: Existence of arbitration; Pleadings and written submissions; Evidence and documents produced; Oral hearing proceedings; Interim orders and final award;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Exceptions to Confidentiality</p>
              <p className="text-slate-600">Disclosure permitted only: To extent necessary for enforcement of award; To professional advisors (lawyers, accountants) under duty of confidentiality; If required by law, regulation, or court order; To extent already in public domain; With written consent of both parties;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Consequences of Breach</p>
              <p className="text-slate-600">Breach of confidentiality: May be contempt of tribunal's procedural orders; Damages claim for breach of confidence; Adverse costs consequences;</p>
            </div>
          </div>
        </div>
      </div>

      {/* ARTICLE 23 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <MapPin className="text-indigo-600" size={20} /> ARTICLE 23: JURISDICTION AND GOVERNING LAW
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">23.1 Governing Law</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Substantive Law</p>
              <p className="text-slate-600 mb-1">This Policy and all disputes arising out of or in connection with it shall be governed by and construed in accordance with the laws of India, including:</p>
              <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Indian Contract Act, 1872;</li>
                <li>Specific Relief Act, 1963;</li>
                <li>Companies Act, 2013;</li>
                <li>Securities and Exchange Board of India Act, 1992 and regulations thereunder;</li>
                <li>Prevention of Money Laundering Act, 2002;</li>
                <li>Foreign Exchange Management Act, 1999;</li>
                <li>Consumer Protection Act, 2019;</li>
                <li>Information Technology Act, 2000;</li>
                <li>All other applicable Central and State legislation;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Exclusion of Conflict of Laws</p>
              <p className="text-slate-600">Conflict of laws principles that would apply law of any other jurisdiction are excluded;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Interpretation Principles</p>
              <p className="text-slate-600">Interpretation shall follow: Plain meaning rule; Purposive interpretation to effectuate intent; Harmonious construction of provisions; Contra proferentem (against Platform as drafter) only if ambiguity unresolvable;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">23.2 Jurisdiction of Courts</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Exclusive Jurisdiction Clause</p>
              <p className="text-slate-600">Subject to arbitration provisions in Article 22: For matters not subject to arbitration or for purposes of interim relief, enforcement, or statutory applications, the courts at Mumbai, Maharashtra, India shall have exclusive jurisdiction.</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Rationale for Mumbai Jurisdiction</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Platform's registered office located in Mumbai;</li>
                <li>(ii) Principal place of business in Mumbai;</li>
                <li>(iii) Banking relationships and financial operations centered in Mumbai;</li>
                <li>(iv) Compliance with SEBI (Mumbai-based regulator) and operational efficiency;</li>
                <li>(v) Availability of specialized commercial courts and expertise in financial matters;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Hierarchy of Courts</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Trial Court:</strong> City Civil Court, Mumbai (for suits of lower pecuniary jurisdiction); High Court of Bombay (for suits exceeding City Civil Court jurisdiction);</li>
                <li>(ii) <strong>Commercial Courts:</strong> Commercial Division of High Court of Bombay for commercial disputes exceeding ₹3 crores (as per Commercial Courts Act, 2015); Faster disposal and specialized procedures;</li>
                <li>(iii) <strong>Appellate Courts:</strong> High Court of Bombay (appellate jurisdiction); Supreme Court of India (appeals from High Court);</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Stakeholder's Voluntary Submission</p>
              <p className="text-slate-600 mb-1">By accepting this Policy, Stakeholder:</p>
              <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Voluntarily submits to jurisdiction of Mumbai courts;</li>
                <li>Waives objection to jurisdiction based on: Stakeholder's residence or place of business elsewhere; Inconvenience of Mumbai forum; Cause of action arising elsewhere;</li>
              </ul>
              <p className="text-slate-600 mt-1">Provided that: This does not prevent Stakeholder from pursuing statutory rights in consumer forums or regulatory forums where such rights cannot be contractually waived;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">23.3 Service of Legal Notices</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Addresses for Service</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Platform's Address:</strong> [Legal Entity Name], [Complete Registered Office Address], [City, State, PIN Code], Email: legal@preiposip.com</li>
                <li>(ii) <strong>Stakeholder's Address:</strong> Registered address in User Account; Registered email address; Registered mobile number for SMS;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Mode of Service</p>
              <p className="text-slate-600">Legal notices, arbitration notices, court summons, or other legal communications shall be validly served: By registered post with acknowledgment due; By courier with delivery confirmation; By email to registered email address; Through Platform's User Account messaging system; By personal service; As per court directions or arbitration rules;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Deemed Service</p>
              <p className="text-slate-600">Notice deemed served: For postal service: 7 days after posting; For courier: On delivery as per tracking; For email: 24 hours after sending (unless delivery failure notification received); For personal service: On date of acknowledgment;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(d) Change of Address</p>
              <p className="text-slate-600">Stakeholder responsible for updating registered address and email; Service at last registered address valid even if Stakeholder no longer at that address; Platform to update address within 10 days of written notification of change;</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
'use client';

import React from 'react';
import { PenTool, FileText, Scissors, Flag, Archive, Send, AlertTriangle, File } from 'lucide-react';

export default function RefundPolicyPart8() {
  return (
    <section id="part-8" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 8</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">AMENDMENT, BOILERPLATE, AND MISCELLANEOUS PROVISIONS</h2>
      </div>

      {/* ARTICLE 24 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <PenTool className="text-indigo-600" size={20} /> ARTICLE 24: AMENDMENT AND MODIFICATION OF POLICY
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">24.1 Platform's Right to Amend</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) General Amendment Power</p>
              <p className="text-slate-600">The Platform reserves the absolute and unfettered right to amend, modify, supplement, delete, or replace any provision of this Policy at any time and from time to time, at its sole discretion, without requirement of prior consent from Stakeholders, subject to compliance with Applicable Law and the notice requirements specified in this Article.</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Grounds for Amendment</p>
              <p className="text-slate-600 mb-1">Without limiting the generality of Article 24.1(a), amendments may be made for the following purposes:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Regulatory Compliance:</strong> Changes in SEBI regulations, circulars, or guidelines; New requirements under Companies Act, PMLA, FEMA, or other statutes; Directions from regulatory authorities requiring policy modifications; Alignment with industry standards or codes of conduct;</li>
                <li>(ii) <strong>Operational Requirements:</strong> Changes in banking arrangements or payment systems; Technology upgrades or system migrations; Organizational restructuring or business model changes; Service enhancements or product modifications;</li>
                <li>(iii) <strong>Risk Management:</strong> Mitigation of identified operational, financial, or legal risks; Fraud prevention and enhanced security measures; Anti-money laundering and compliance improvements; Lessons learned from disputes or grievances;</li>
                <li>(iv) <strong>Market Conditions:</strong> Changes in market practices or industry norms; Economic conditions affecting cost structures; Competitive dynamics requiring policy adjustments; Evolution of Pre-IPO market ecosystem;</li>
                <li>(v) <strong>Legal Developments:</strong> Judicial precedents or court rulings affecting policy interpretation; Changes in legal landscape or statutory framework; Clarifications or corrections of legal ambiguities;</li>
                <li>(vi) <strong>Stakeholder Feedback:</strong> Incorporating feedback from user experience; Addressing recurrent queries or concerns; Improving clarity and accessibility of policy;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">24.2 Amendment Procedure</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Internal Approval Process</p>
              <p className="text-slate-600 mb-1">Amendments shall be subject to the following internal governance:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Minor/Administrative Amendments</strong> (typographical corrections, contact detail updates, non-substantive clarifications): Approval by Chief Compliance Officer or designated officer; No material impact on Stakeholder rights or obligations;</li>
                <li>(ii) <strong>Moderate Amendments</strong> (procedural changes, timeline modifications, fee adjustments within 20%): Approval by Senior Management Committee (CFO, CCO, Legal Head); Documented rationale and impact assessment;</li>
                <li>(iii) <strong>Major/Material Amendments</strong> (substantive changes to refund eligibility, calculation methodology, liability provisions, dispute resolution): Approval by Board of Directors or authorized Board Committee; Comprehensive legal and compliance review; Regulatory consultation if required; Consider stakeholder impact and provide reasonable transition period;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Documentation of Amendments</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Version Control:</strong> Each amended Policy assigned version number and effective date; Format: "Version X.Y - Effective [Date]"; Major amendments increment X; minor amendments increment Y;</li>
                <li>(ii) <strong>Amendment Log:</strong> Summary of changes maintained in amendment register; Date of amendment, approval authority, nature of changes; Available on Platform website for transparency;</li>
                <li>(iii) <strong>Tracked Changes:</strong> For material amendments, marked-up version showing changes available on request; Facilitates understanding of specific modifications;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">24.3 Notice of Amendment</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Advance Notice Requirement</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Material Amendments:</strong> Minimum 30 (thirty) days advance notice to Stakeholders before effective date; Notice through multiple channels: Email, SMS, User Account notification, website prominent display; Detailed explanation of changes and impact;</li>
                <li>(ii) <strong>Non-Material Amendments:</strong> Minimum 15 (fifteen) days advance notice; Notice through website publication and User Account notification;</li>
                <li>(iii) <strong>Regulatory/Statutory Amendments:</strong> If amendment mandated by law or regulatory directive with immediate effect: Notice given promptly but may be effective immediately; Explanation that change is regulatory compliance requirement;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Mode of Notice</p>
              <p className="text-slate-600 mb-1">Notice of amendment shall be provided through:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Email Notification:</strong> To all registered email addresses of active Stakeholders; Subject line clearly indicating "Important Policy Amendment"; Summary of key changes with link to full amended Policy;</li>
                <li>(ii) <strong>SMS Alert:</strong> Brief notification of policy update; Instruction to check email and User Account for details;</li>
                <li>(iii) <strong>User Account Dashboard:</strong> Prominent banner or pop-up notification upon login; Mandatory acknowledgment for accessing services after effective date; Link to view amended Policy and summary of changes;</li>
                <li>(iv) <strong>Website Publication:</strong> Amended Policy published on Platform website (www.preiposip.com); Homepage banner announcing policy update; "Policy Updates" section maintaining archive of previous versions;</li>
                <li>(v) <strong>Registered Post (for high-value clients or material amendments):</strong> Physical communication sent to registered address; Particularly for institutional clients or relationship-managed accounts;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Content of Amendment Notice</p>
              <p className="text-slate-600 mb-1">Amendment notice shall contain:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Clear heading: "Amendment to Refund Policy - Action Required";</li>
                <li>(ii) Effective date of amendment;</li>
                <li>(iii) Summary of changes in plain language: What is changing; Why it is changing; How it affects Stakeholders; Any action required from Stakeholders;</li>
                <li>(iv) Link to or attachment of: Full amended Policy; Marked-up version showing changes (for material amendments); Comparative table of old vs. new provisions (if helpful);</li>
                <li>(v) Contact details for queries or clarifications;</li>
                <li>(vi) Statement of Stakeholder's rights: Right to object or terminate relationship if dissatisfied with amendments (subject to contractual lock-ins); Right to seek clarifications; Deemed acceptance if continue using services;</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">24.4 Stakeholder's Right to Object</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Right to Reject Material Amendments</p>
              <p className="text-slate-600 mb-1">For material amendments adversely affecting Stakeholder's rights:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Stakeholder may object in writing within 15 days of amendment notice;</li>
                <li>(ii) If objection relates to pending refund request: Stakeholder may elect to have refund request processed under old Policy version; Election must be made within objection period; Applies only to requests already submitted before amendment effective date;</li>
                <li>(iii) If objection relates to ongoing subscription or service: Stakeholder may terminate subscription without penalty; Pro-rata refund of unutilized subscription fee (less consumption charges); Termination effective from end of notice period;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Deemed Acceptance</p>
              <p className="text-slate-600">If Stakeholder: Does not object within 15 days of notice; OR Continues to use Platform's services after amendment effective date; OR Submits new transactions or refund requests after effective date; Stakeholder deemed to have accepted amended Policy unconditionally;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Grandfathering for Specific Transactions</p>
              <p className="text-slate-600">Where fairness and equity demand, Platform may grandfather existing transactions under old Policy version; Grandfathering decision at Platform's discretion, documented in amendment notice; Typically applicable for: Transactions with contractual terms incorporating specific Policy version; Long-term engagements or subscriptions; Cases where retrospective application would be manifestly unjust;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">24.5 Emergency Amendments</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Circumstances Permitting Emergency Amendments</p>
              <p className="text-slate-600 mb-1">In extraordinary circumstances requiring immediate policy changes:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Force Majeure Events:</strong> Pandemic, natural disaster, system failures requiring immediate operational changes;</li>
                <li>(ii) <strong>Regulatory Directives:</strong> SEBI/RBI orders requiring immediate compliance;</li>
                <li>(iii) <strong>Systemic Fraud or Security Threats:</strong> Discovery of vulnerability requiring immediate policy modification for stakeholder protection;</li>
                <li>(iv) <strong>Court Orders:</strong> Judicial directions requiring immediate implementation;</li>
                <li>(v) <strong>Payment System Changes:</strong> Banking partner changes or payment gateway modifications requiring urgent updates;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Emergency Amendment Procedure</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Immediate Effectiveness:</strong> Amendment may be made effective immediately or with shorter notice period (as low as 24 hours);</li>
                <li>(ii) <strong>Abbreviated Approval:</strong> Approval by available senior management (minimum CFO and CCO); Board ratification at next meeting;</li>
                <li>(iii) <strong>Urgent Communication:</strong> Notice through fastest available channels (email, SMS, website banner, User Account alert);</li>
                <li>(iv) <strong>Post-Implementation Notice:</strong> Detailed explanation and rationale provided as soon as practicable;</li>
                <li>(v) <strong>Review and Regularization:</strong> Emergency amendment reviewed within 30 days; regularized, modified, or withdrawn as appropriate;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Limited Scope</p>
              <p className="text-slate-600">Emergency amendments limited to addressing the specific exigency; broader policy review conducted subsequently if needed;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">24.6 Regulatory Approval of Amendments</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Pre-Approval Requirement</p>
              <p className="text-slate-600">If Platform is registered with SEBI as Investment Adviser, Research Analyst, or other regulated intermediary: Material policy amendments may require prior SEBI approval or intimation; Submission to SEBI with rationale and impact assessment; Effective date subject to regulatory clearance;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Post-Amendment Reporting</p>
              <p className="text-slate-600">Periodic reporting to SEBI of policy amendments as part of: Annual compliance reports; Quarterly regulatory filings; Responsive to specific regulatory inquiries;</p>
            </div>
          </div>
        </div>
      </div>

      {/* ARTICLE 25 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <FileText className="text-indigo-600" size={20} /> ARTICLE 25: ENTIRE AGREEMENT AND INTEGRATION
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">25.1 Entire Agreement Clause</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Comprehensive Integration</p>
              <p className="text-slate-600 mb-1">This Policy, together with: Platform's Terms of Use/Terms of Service; Privacy Policy; User Agreement; Specific Transaction Documentation (term sheets, subscription agreements, advisory engagement letters, etc.); Any other documents expressly incorporated by reference;</p>
              <p className="text-slate-600">Constitutes the entire agreement between the Platform and the Stakeholder with respect to refund matters, and supersedes all prior and contemporaneous: Agreements, understandings, or arrangements (whether written or oral); Representations, warranties, or commitments; Negotiations, discussions, or correspondence; Marketing materials, brochures, or promotional communications;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) No Oral Modifications</p>
              <p className="text-slate-600">No oral representations, statements, promises, or assurances made by Platform's employees, agents, or representatives shall modify, amend, or supplement this written Policy unless: Reduced to writing and signed by authorized officer of Platform (minimum designation: Manager or above); Expressly stated to be modification of this Policy; Incorporated as formal amendment following procedures in Article 24;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Parol Evidence Rule</p>
              <p className="text-slate-600">Consistent with Indian Evidence Act, 1872 (Section 92): When terms reduced to writing, external evidence not admissible to contradict, vary, add to, or subtract from written terms; Exceptions: Fraud, mistake, illegality, or ambiguity may be established through extrinsic evidence;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">25.2 Hierarchy of Documents</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) In Event of Conflict</p>
              <p className="text-slate-600">If conflict or inconsistency between this Policy and other documents: Order of Precedence (highest to lowest): Applicable Law (statutes, regulations - cannot be contracted away); Specific Transaction Documentation (if explicitly states it prevails over general Policy); This Refund Policy; Platform's Terms of Use/User Agreement; Privacy Policy and other ancillary policies; Marketing materials, FAQs, and informational content (lowest - not contractually binding);</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Reconciliation Principle</p>
              <p className="text-slate-600">Before applying hierarchy, attempt harmonious construction: Interpret documents together to give effect to all provisions; Specific provisions prevail over general provisions; Later-dated documents prevail over earlier (if same level in hierarchy);</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Ambiguity Resolution</p>
              <p className="text-slate-600">If ambiguity exists after applying above principles: Commercial reasonableness and industry practice considered; Purpose and intent of provisions examined; Contra proferentem (against drafter) as last resort;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">25.3 Incorporation by Reference</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Documents Incorporated</p>
              <p className="text-slate-600">The following are expressly incorporated by reference into this Policy: (i) Platform's current Terms of Use (available at www.preiposip.com/terms); (ii) Privacy Policy (available at www.preiposip.com/privacy); (iii) Risk Disclosure Document for Pre-IPO Investments; (iv) Know Your Customer (KYC) and Anti-Money Laundering Policy; (v) Specific transaction terms and conditions agreed in writing;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Effect of Incorporation</p>
              <p className="text-slate-600">Incorporated documents have same legal effect as if fully set out in this Policy;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Version Applicability</p>
              <p className="text-slate-600">Version of incorporated document in effect at time of transaction applies; Amendments to incorporated documents effective as per their own amendment procedures;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">25.4 Merger Clause Effect</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Prior Agreements Superseded</p>
              <p className="text-slate-600">Any prior refund policy, terms, or agreements relating to refunds are hereby superseded and replaced entirely by this Policy;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) No Reliance on Prior Communications</p>
              <p className="text-slate-600">Stakeholder acknowledges: Not relying on any prior representations, communications, or understandings not contained in this written Policy; Has had adequate opportunity to review, understand, and seek advice on this Policy; Enters into transactions governed by this Policy voluntarily and with full knowledge;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) No Agency or Partnership</p>
              <p className="text-slate-600">Nothing in this Policy creates or shall be deemed to create: Partnership, joint venture, or agency relationship between Platform and Stakeholder; Fiduciary relationship except as specifically provided by law; Employment relationship;</p>
            </div>
          </div>
        </div>
      </div>

      {/* ARTICLE 26 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Scissors className="text-indigo-600" size={20} /> ARTICLE 26: SEVERABILITY AND SAVINGS CLAUSE
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">26.1 Severability of Provisions</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) General Severability</p>
              <p className="text-slate-600 mb-1">If any provision, clause, phrase, or portion of this Policy is held or deemed to be: Invalid, illegal, unenforceable, or void; In violation of any law, statute, ordinance, or regulation; Contrary to public policy; By any court, tribunal, arbitrator, or competent authority of competent jurisdiction, then:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Such provision shall be severed and deleted from this Policy;</li>
                <li>(ii) The invalidity of such provision shall not affect the validity, legality, or enforceability of the remaining provisions;</li>
                <li>(iii) Remaining provisions shall continue in full force and effect as if invalid provision had never been included;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Reasonable Substitution</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) If severance of invalid provision would materially defeat the purpose of this Policy or create substantial imbalance: Platform may propose reasonable substitute provision; Substitute provision shall, to maximum extent possible, approximate the economic effect and intent of severed provision; Substitute provision subject to mutual agreement or, failing agreement, determination by arbitrator/court;</li>
                <li>(ii) If no reasonable substitute possible: Parties negotiate in good faith to modify Policy to achieve original intent through lawful means; Failing agreement within 30 days, either party may terminate affected transaction(s) without penalty;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Partial Invalidity</p>
              <p className="text-slate-600">If provision is invalid only with respect to certain applications, parties, or circumstances: Provision remains valid and enforceable for other applications, parties, or circumstances; Invalid application severed; valid applications continue;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">26.2 Blue Pencil Doctrine</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Judicial Modification</p>
              <p className="text-slate-600">To the extent permitted under applicable law: Courts or arbitrators authorized to modify overbroad or unreasonable provisions to make them enforceable; "Blue penciling" to narrow scope, reduce duration, or limit application; Modified provision enforceable as reformed;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Examples of Blue Penciling</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) If liability limitation held excessive: Reduce to maximum permissible limit;</li>
                <li>(ii) If geographical restriction overbroad: Narrow to reasonable area;</li>
                <li>(iii) If time period unreasonable: Reduce to reasonable duration;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Platform's Intent</p>
              <p className="text-slate-600">Platform expressly intends that if any provision susceptible to multiple interpretations: Interpretation making provision valid and enforceable be adopted; Provision be enforced to maximum extent permitted by law;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">26.3 Savings Provisions</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Rights and Obligations Accrued</p>
              <p className="text-slate-600">Invalidity or unenforceability of any provision shall not affect: Rights, obligations, or liabilities accrued prior to determination of invalidity; Completed transactions or refunds processed under provision before invalidation; Pending proceedings or claims initiated when provision was valid;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Statutory Rights Preserved</p>
              <p className="text-slate-600">Nothing in this Policy shall be construed to limit, waive, or exclude: Statutory rights that cannot be contractually waived under law; Consumer protection rights under Consumer Protection Act, 2019; Rights under SEBI investor protection regulations; Mandatory provisions of Companies Act, Contract Act, or other applicable statutes; If any provision attempts such limitation/waiver: Such provision void to that extent; statutory rights remain fully enforceable;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Continued Performance Pending Resolution</p>
              <p className="text-slate-600">Pending judicial or arbitral determination of validity: Parties continue to perform obligations under Policy (unless stayed by competent authority); Disputed provision applied tentatively without prejudice to rights; Adjustments made retroactively if provision ultimately held invalid;</p>
            </div>
          </div>
        </div>
      </div>

      {/* ARTICLE 27 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Flag className="text-indigo-600" size={20} /> ARTICLE 27: WAIVER PROVISIONS
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">27.1 No Waiver by Silence or Delay</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Express Waiver Required</p>
              <p className="text-slate-600">No failure, delay, or omission by Platform to: Exercise any right, power, or remedy under this Policy; Enforce any provision or term; Object to breach or default by Stakeholder; Insist upon strict performance; Shall constitute a waiver of such right, power, remedy, or provision, or preclude its later exercise or enforcement.</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Continuing Rights</p>
              <p className="text-slate-600">All rights and remedies available to Platform under this Policy, law, or equity are: Cumulative, not exclusive or alternative; Continuing rights that survive any breach or default; Exercisable repeatedly and as often as occasion arises;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Single or Partial Exercise</p>
              <p className="text-slate-600">Single or partial exercise of any right or remedy: Does not preclude further exercise of that right or remedy; Does not preclude exercise of any other right or remedy;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">27.2 Formal Waiver Requirements</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Writing Requirement</p>
              <p className="text-slate-600 mb-1">Any waiver of rights under this Policy must be:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>In Writing:</strong> No oral waiver valid or enforceable;</li>
                <li>(ii) <strong>Signed by Authorized Officer:</strong> Minimum designation Manager or above; for waivers exceeding ₹5,00,000: CFO or authorized director;</li>
                <li>(iii) <strong>Specific and Express:</strong> Clearly identify: Provision or right being waived; Transaction or circumstance to which waiver applies; Extent and duration of waiver; Any conditions attached to waiver;</li>
                <li>(iv) <strong>Delivered to Stakeholder:</strong> Email to registered email address or physical delivery;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Limited Scope of Waiver</p>
              <p className="text-slate-600 mb-1">Unless expressly stated otherwise:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Waiver applies only to specific instance and circumstance stated;</li>
                <li>(ii) Waiver does not extend to: Similar or related breaches or circumstances; Future breaches or defaults; Other provisions or rights;</li>
                <li>(iii) Waiver does not constitute: Amendment of Policy (must follow Article 24 procedures); Course of dealing or custom; Estoppel preventing future enforcement;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Conditional Waiver</p>
              <p className="text-slate-600">Platform may grant waiver subject to conditions: Stakeholder's performance of specified obligations; Payment of amounts or provision of security; Execution of supplementary agreements; Time limits for availing waiver; Failure to satisfy conditions renders waiver void ab initio;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">27.3 Waiver by Stakeholder</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Acceptance of Partial Performance</p>
              <p className="text-slate-600">If Stakeholder accepts: Partial or late refund payment; Refund with deductions Stakeholder previously disputed; Performance different from strict contractual obligation; Without written reservation of rights within 7 days: Stakeholder deemed to have waived right to object to such non-conformance for that specific instance;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Settlement and Release</p>
              <p className="text-slate-600">Execution of settlement agreement or release by Stakeholder constitutes waiver of claims covered by settlement, unless settlement expressly preserves certain rights;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Statutory Rights Non-Waivable</p>
              <p className="text-slate-600">Stakeholder cannot waive: Statutory rights under consumer protection or investor protection laws; Rights to approach regulatory authorities; Mandatory statutory remedies; Any purported waiver of non-waivable rights is void and unenforceable;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">27.4 Waiver Does Not Affect Subsequent Defaults</h4>
          <p className="text-slate-600">Waiver of breach does not preclude Platform from: Treating subsequent similar breach as fresh breach; Enforcing provision strictly in future; Terminating relationship for repeated breaches despite prior waivers; Considering pattern of breaches in exercising discretion;</p>
        </div>
      </div>

      {/* ARTICLE 28 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Archive className="text-indigo-600" size={20} /> ARTICLE 28: ASSIGNMENT AND TRANSFER
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">28.1 Platform's Right to Assign</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) General Assignment Right</p>
              <p className="text-slate-600 mb-1">Platform may assign, transfer, delegate, or otherwise dispose of its rights and obligations under this Policy:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>To Affiliates:</strong> Any holding company, subsidiary, or fellow subsidiary of Platform; Related entities under common control; No consent of Stakeholder required; Notice to Stakeholder within 30 days of assignment;</li>
                <li>(ii) <strong>Business Transfers:</strong> In connection with merger, amalgamation, or consolidation; Sale of business, assets, or division; Corporate restructuring or reorganization; No consent required; notice given;</li>
                <li>(iii) <strong>Outsourcing and Subcontracting:</strong> Platform may engage third-party service providers for refund processing, payment services, verification, etc.; Platform remains primarily liable for performance; Stakeholder data handled subject to confidentiality obligations;</li>
                <li>(iv) <strong>To Successor Entity:</strong> Assignment by operation of law; Platform dissolved, liquidated, or undergoing insolvency resolution; Successor entity assumes rights and obligations;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Stakeholder's Rights on Assignment</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) If assignment materially adversely affects Stakeholder's rights or increases obligations: Stakeholder may terminate relationship and seek refund of unutilized amounts; Option exercisable within 30 days of assignment notice;</li>
                <li>(ii) For routine business transfers or affiliate assignments: No termination right; assignment binding on Stakeholder;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Assignment Notice</p>
              <p className="text-slate-600">Platform shall provide notice of material assignments: Via email and User Account notification; Identifying assignee entity; Confirming continuity of services and obligations; Contact details of assignee for future correspondence;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">28.2 Stakeholder's Restrictions on Assignment</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) General Prohibition</p>
              <p className="text-slate-600">Stakeholder may NOT assign, transfer, delegate, or create any encumbrance over: Rights under this Policy; Refund claims or entitlements; User Account or login credentials; Contractual position in any transaction; Without prior written consent of Platform;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Permitted Assignments by Stakeholder</p>
              <p className="text-slate-600 mb-1">Platform may consent (in writing) to assignment in following cases:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Death of Stakeholder:</strong> Assignment to legal heirs upon submission of succession documentation (as per Article 14.1);</li>
                <li>(ii) <strong>Corporate Succession:</strong> Merger or amalgamation of corporate Stakeholder; Succession certified by Registrar of Companies; Successor entity assumes all obligations;</li>
                <li>(iii) <strong>Court-Ordered Assignment:</strong> Official liquidator, receiver, or administrator appointed; Court order directing assignment; Insolvency and Bankruptcy Code proceedings;</li>
                <li>(iv) <strong>Genuine Business Need:</strong> Stakeholder demonstrates legitimate business reason; Assignee satisfies Platform's KYC and eligibility criteria; Assignee accepts all terms and conditions; Platform approval at sole discretion, may be subject to conditions;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Void Assignments</p>
              <p className="text-slate-600">Assignment by Stakeholder without Platform consent is void and of no effect: Platform not bound by such purported assignment; Platform may continue dealing with original Stakeholder; Platform may terminate account for breach; No refund obligation to purported assignee;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">28.3 Change of Control</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Platform Change of Control</p>
              <p className="text-slate-600 mb-1">If Platform undergoes change of control (acquisition of majority shareholding or voting rights):</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) This Policy continues to bind new controller;</li>
                <li>(ii) If new controller materially changes business model or discontinues services: Stakeholders given reasonable notice (minimum 60 days); Option to terminate with pro-rata refund of subscriptions; Pending refund requests processed under existing terms;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Stakeholder Change of Control</p>
              <p className="text-slate-600 mb-1">For corporate/institutional Stakeholders:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) Change of control must be notified to Platform within 30 days;</li>
                <li>(ii) Platform may require fresh KYC and due diligence;</li>
                <li>(iii) Platform reserves right to terminate relationship if new controller unacceptable (blacklisted, high-risk, not meeting eligibility criteria);</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      {/* ARTICLE 29 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Send className="text-indigo-600" size={20} /> ARTICLE 29: NOTICES AND COMMUNICATIONS
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">29.1 Method of Giving Notices</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Acceptable Modes</p>
              <p className="text-slate-600 mb-1">All notices, communications, requests, or demands under this Policy shall be in writing and may be delivered by:</p>
              <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(i) <strong>Email:</strong> To registered email address; Read receipt or delivery confirmation recommended; Attachment in PDF format for formal notices;</li>
                <li>(ii) <strong>Registered Post/Speed Post with Acknowledgment Due:</strong> To registered address on record; Proof of posting and delivery retained;</li>
                <li>(iii) <strong>Courier (Reputed Service Provider):</strong> FedEx, DHL, Blue Dart, DTDC, etc.; POD (Proof of Delivery) obtained;</li>
                <li>(iv) <strong>Platform's User Account Messaging System:</strong> For routine communications and status updates; Notification sent to registered email when message posted;</li>
                <li>(v) <strong>Personal Service:</strong> Hand delivery with signed acknowledgment;</li>
              </ul>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Notice Addresses</p>
              <p className="text-slate-600"><strong>For Platform:</strong> Legal/Compliance Department, [Legal Entity Name], [Complete Registered Office Address], Email: legal@preiposip.com; compliance@preiposip.com</p>
              <p className="text-slate-600"><strong>For Stakeholder:</strong> Address provided during registration; Updated address as notified in writing; Email address registered in User Account;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Change of Address</p>
              <p className="text-slate-600">Either party may change address by giving 7 days written notice to other party; Until change notified, notices to old address deemed valid;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">29.2 Deemed Delivery</h4>
          <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
            <table className="w-full text-sm text-left text-slate-600">
              <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="px-6 py-3">Mode</th>
                  <th className="px-6 py-3">Deemed Delivery Time</th>
                </tr>
              </thead>
              <tbody>
                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Email</td><td className="px-6 py-4">24 hours after sending (unless delivery failure notification received)</td></tr>
                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Registered Post</td><td className="px-6 py-4">7 Business Days after posting (within India); 15 Business Days (international)</td></tr>
                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Courier</td><td className="px-6 py-4">On date of delivery as per POD</td></tr>
                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">User Account Message</td><td className="px-6 py-4">48 hours after posting + email notification sent</td></tr>
                <tr className="bg-white"><td className="px-6 py-4">Personal Service</td><td className="px-6 py-4">On date of acknowledgment</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">29.3 Language</h4>
          <p className="text-slate-600">All notices and communications shall be in English language. If translation provided for convenience, English version prevails in case of discrepancy.</p>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">29.4 Business Days</h4>
          <p className="text-slate-600">If deemed delivery falls on non-Business Day (Saturday, Sunday, public holiday), notice deemed delivered on next Business Day.</p>
        </div>
      </div>

      {/* ARTICLE 30 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <AlertTriangle className="text-indigo-600" size={20} /> ARTICLE 30: FORCE MAJEURE - EXPANDED PROVISIONS
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">30.1 Extended Force Majeure Definition</h4>
          <p className="text-slate-600 mb-2">(Supplementing Article 16) In addition to Force Majeure Events specified in Article 16.1, the following shall also constitute Force Majeure:</p>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Regulatory and Governmental Actions:</strong> Suspension or revocation of Platform's licenses or registrations; Regulatory directions prohibiting or restricting Platform's operations; Changes in foreign investment norms affecting transactions; Tax or GST regime changes causing operational disruption;</li>
            <li>(b) <strong>Financial System Events:</strong> Banking crisis or systemic financial instability; Moratorium on banking operations; Foreign exchange crisis or rupee inconvertibility; Payment system failures exceeding 72 hours;</li>
            <li>(c) <strong>Technology and Cyber Events:</strong> Widespread internet outage or telecom disruption; Cyber warfare or state-sponsored attacks; Critical software vulnerabilities requiring immediate shutdown; Failure of critical third-party service providers (AWS, Microsoft Azure, etc.);</li>
            <li>(d) <strong>Third-Party Dependencies:</strong> Stock exchange closures or trading halts exceeding 7 days; Depository (NSDL/CDSL) system failures; Failure of issuer companies or sellers to perform; Escrow bank failures or payment gateway shutdowns;</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">30.2 Platform's Force Majeure Obligations</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Notification</p>
              <p className="text-slate-600">Platform shall notify Stakeholders of Force Majeure Event: Within 48 hours of becoming aware (or as soon as communication possible); Through website notice, email broadcast, SMS alerts; Regular updates on status and expected resolution;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) Mitigation</p>
              <p className="text-slate-600">Platform shall: Implement business continuity and disaster recovery plans; Activate alternate systems, vendors, or processes; Prioritize critical operations including pending refund payments; Document mitigation efforts for transparency;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Resumption</p>
              <p className="text-slate-600">Upon cessation of Force Majeure Event: Operations resume within 5 Business Days (or as soon as reasonably practicable); Backlog processed on priority basis; Additional resources deployed if necessary to clear backlog;</p>
            </div>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">30.3 Stakeholder's Rights During Force Majeure</h4>
          <div className="ml-4 space-y-4">
            <div>
              <p className="text-slate-700 font-semibold">(a) Information Rights</p>
              <p className="text-slate-600">Stakeholder entitled to: Regular status updates on Force Majeure situation; Estimated timeline for resumption of services; Contact point for urgent queries;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(b) No Penalty on Stakeholder</p>
              <p className="text-slate-600">Stakeholder's obligations (documentation submission, fee payment) also suspended proportionately during Force Majeure period;</p>
            </div>
            <div>
              <p className="text-slate-700 font-semibold">(c) Termination for Prolonged Force Majeure</p>
              <p className="text-slate-600">If Force Majeure exceeds 90 days: Stakeholder may terminate pending transactions without penalty; Refund of payments made less only actual costs incurred by Platform; No forfeiture or liquidated damages during Force Majeure;</p>
            </div>
          </div>
        </div>
      </div>

      {/* ARTICLE 31 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <File className="text-indigo-600" size={20} /> ARTICLE 31: MISCELLANEOUS PROVISIONS
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">31.1 Headings</h4>
          <p className="text-slate-600">Headings, titles, and captions are for convenience and reference only and do not affect interpretation or construction of provisions.</p>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">31.2 Gender and Number</h4>
          <p className="text-slate-600">Words importing one gender include all genders; words in singular include plural and vice versa.</p>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">31.3 Time Periods</h4>
          <p className="text-slate-600">Time periods shall be calculated as follows: "Days" means calendar days unless specified as "Business Days"; Computation: Exclude first day, include last day; If last day is non-Business Day, extended to next Business Day; "Month" means calendar month; "Year" means 365 days or calendar year as context requires;</p>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">31.4 Currency</h4>
          <p className="text-slate-600">All amounts in this Policy are in Indian Rupees (INR/₹) unless otherwise specified.</p>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">31.5 Statutory References</h4>
          <p className="text-slate-600">References to statutes include: Amendments, re-enactments, or replacements; Subordinate legislation (rules, regulations, notifications); Successor or equivalent provisions;</p>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">31.6 Counterparts and Electronic Execution</h4>
          <p className="text-slate-600">(a) This Policy may be executed in multiple counterparts, each deemed an original, all together constituting one agreement; (b) Electronic execution and digital signatures valid and enforceable under Information Technology Act, 2000; (c) Stakeholder's acceptance through User Account interface (click-wrap) constitutes valid electronic acceptance;</p>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">31.7 Costs and Expenses</h4>
          <p className="text-slate-600">Unless otherwise specified: Each party bears its own costs of compliance with this Policy; Costs of dispute resolution as per Article 22.4(d); Costs of regulatory proceedings as per applicable law or regulatory directions;</p>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">31.8 Further Assurances</h4>
          <p className="text-slate-600">Each party shall execute and deliver such further documents and take such further actions as may be reasonably required to give effect to provisions of this Policy.</p>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">31.9 Third-Party Rights</h4>
          <p className="text-slate-600">(a) No third-party rights created by this Policy except: As expressly provided (e.g., indemnified parties under Article 18); As required by law (statutory beneficiaries); (b) Contracts (Rights of Third Parties) principles: This Policy not enforceable by third parties under contract law principles except as above;</p>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">31.10 Survival</h4>
          <p className="text-slate-600">The following provisions survive termination or expiry of relationship between Platform and Stakeholder: Confidentiality obligations; Indemnification provisions (Article 18); Liability limitations (Article 17); Dispute resolution and arbitration (Articles 20-22); Intellectual property rights; Any provisions that by their nature should survive;</p>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">31.11 Relationship of Parties</h4>
          <p className="text-slate-600">Nothing in this Policy creates: Partnership, joint venture, or agency; Employer-employee relationship; Franchisor-franchisee relationship; Fiduciary duty (except as imposed by law); Platform is independent contractor; Stakeholder is independent client.</p>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">31.12 Publicity and Confidentiality</h4>
          <p className="text-slate-600">(a) No Publicity: Neither party shall issue press releases or public announcements regarding refund matters without other party's prior written consent (except as required by law); (b) Confidential Information: Transaction details, refund amounts, disputes confidential; Protected from unauthorized disclosure; Exceptions: Disclosure required by law, regulation, or court order;</p>
        </div>
      </div>
    </section>
  );
}
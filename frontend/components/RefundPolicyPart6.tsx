'use client';

import React from 'react';
import { Ban, Zap, ShieldOff, FileWarning, Activity } from 'lucide-react';

export default function RefundPolicyPart6() {
  return (
    <section id="part-6" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 6</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">EXCEPTIONS, FORCE MAJEURE, LIABILITY LIMITATIONS, INDEMNIFICATION, AND RISK DISCLOSURES</h2>
      </div>

      {/* Article 15 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Ban className="text-indigo-600" size={20} /> ARTICLE 15: EXCEPTIONS TO REFUND ELIGIBILITY
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">15.1 Absolute Exclusions</h4>
          <p className="text-slate-600 mb-2">Notwithstanding any other provision of this Policy, no refund shall be available in the following circumstances:</p>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Completed Transactions with Full Performance</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Where securities have been allotted, transferred, and credited to Stakeholder's demat account, and the Stakeholder has: Accepted delivery of securities; Exercised ownership rights (voting, dividends, bonus shares); Sold or pledged the securities in secondary transactions; Held securities beyond 30 days without objection;</li>
              <li>(ii) Where advisory services have been fully rendered and: Stakeholder has acted upon advice provided; Reports, recommendations, or deliverables have been consumed; Consultation sessions completed as per engagement terms; No material deficiency or breach established within objection period;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Change of Mind or Investment Sentiment</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Stakeholder's unilateral decision to withdraw due to: Change in personal financial circumstances; Change in investment strategy or risk appetite; Better investment opportunities elsewhere; Market conditions or valuation concerns; Family or personal reasons unrelated to transaction defects;</li>
              <li>(ii) Clarification: Pre-IPO investments carry inherent risks. Market volatility, valuation changes, or subsequent poor performance of securities do not constitute grounds for refund once transaction executed;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Speculative or Tactical Cancellations</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Where circumstances indicate that Stakeholder: Applied for oversubscribed opportunity with intention to cancel if alternate placement obtained; Engaged in parallel negotiations with multiple sellers and seeks refund after alternative secured; Attempts to leverage cancellation threat for renegotiation of terms; Patterns of repeat applications and cancellations indicating abuse of process;</li>
              <li>(ii) Platform's Right: Platform may blacklist Stakeholders demonstrating pattern of speculative behavior and deny future services;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(d) Regulatory or Statutory Bars</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Where refund would violate: SEBI regulations on market manipulation or insider trading; PMLA provisions resulting in suspicious transaction; Court orders, attachment, or legal prohibitions; FEMA restrictions on capital account transactions; Tax evasion or money laundering concerns;</li>
              <li>(ii) Where Stakeholder is: Declared wilful defaulter by RBI; Subject to SEBI debarment or prohibition orders; Undergoing insolvency proceedings (refund subject to IBC moratorium); Listed in sanctions lists (OFAC, UN, EU, domestic);</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(e) Fraud, Misrepresentation, or Bad Faith by Stakeholder</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Where Stakeholder has: Provided false information or forged documents in KYC; Made material misrepresentations to induce transaction; Engaged in fraudulent conduct or attempted fraud; Violated Platform's terms of service or ethical standards; Breached representations and warranties in Transaction Documentation;</li>
              <li>(ii) Consequences: In addition to refund denial: User Account suspension or termination; Forfeiture of all payments made; Legal action for fraud and recovery of damages; Reporting to law enforcement and regulatory authorities; Negative reporting to credit bureaus and industry databases;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(f) Third-Party Beneficiary Transactions</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Where Stakeholder transacted as agent, trustee, or intermediary for third-party beneficiary: Refund not available to Stakeholder personally; Principal or beneficiary must claim refund directly; Agency documentation and principal's consent required; Prevents money laundering through layering arrangements;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(g) Limitation Period Expiry</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Refund requests submitted beyond limitation periods specified in Article 6.1;</li>
              <li>(ii) No exceptions unless: Delay caused by Force Majeure Event affecting Stakeholder; Platform's conduct prevented timely filing (equitable estoppel); Fraud or concealment by Platform (limitation starts from discovery); Court or tribunal order condoning delay;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(h) Prior Settlement or Release</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Where Stakeholder has: Executed full and final settlement agreement; Accepted alternate compensation or remedy; Signed release or waiver of refund claims; Received consideration for withdrawal of refund claim;</li>
              <li>(ii) Binding Nature: Such settlements binding unless proven to be result of fraud, coercion, or undue influence;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(i) Vexatious or Frivolous Claims</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Where refund request is demonstrably: Without any legal or factual basis; Filed to harass or pressurize Platform; Part of coordinated malicious campaign; Repeated despite prior rejections without new grounds;</li>
              <li>(ii) Platform's Rights: Summary rejection without detailed processing; Levy of vexatious litigation charges; Legal action for defamation or malicious prosecution; Restraint orders from courts;</li>
            </ul>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">15.2 Conditional Exceptions</h4>
          <p className="text-slate-600 mb-2">The following circumstances may result in refund denial unless Stakeholder satisfies additional conditions:</p>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Disputed Facts or Pending Investigation</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Where material facts are disputed: Refund processing suspended pending fact-finding; Independent investigation or expert opinion obtained; Stakeholder required to provide corroborative evidence; Timeline extended by 30-60 days for investigation;</li>
              <li>(ii) If investigation establishes Stakeholder's position: Full refund with interest;</li>
              <li>(iii) If investigation establishes Platform's position: Refund denied, investigation costs recovered;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Concurrent Litigation or Arbitration</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Where dispute is subject of pending litigation or arbitration: Refund processing stayed pending judicial/arbitral determination; Platform may deposit refund amount in court or escrow; Final refund subject to court/tribunal directions;</li>
              <li>(ii) Forum Shopping Prohibition: Stakeholder cannot simultaneously pursue: Refund request under this Policy; Civil suit for damages; Consumer complaint; Arbitration proceedings; Selection of one forum constitutes waiver of others (subject to statutory appeal rights);</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Pending Regulatory Proceedings</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) If transaction is subject of: SEBI investigation or inquiry; RBI examination or forensic audit; FIU-IND suspicious transaction review; Tax authority scrutiny or search proceedings;</li>
              <li>(ii) Refund decision deferred pending regulatory clearance;</li>
              <li>(iii) Platform may seek indemnity bond from Stakeholder before refund if subsequently demanded by regulators;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(d) Unclear or Insufficient Documentation</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Where Stakeholder's documentation is: Ambiguous or contradictory; Insufficient to establish eligibility; Lacking in critical evidentiary value;</li>
              <li>(ii) Opportunity provided to cure deficiencies;</li>
              <li>(iii) If deficiencies not cured within 30 days: Refund denied without prejudice to fresh application;</li>
            </ul>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">15.3 Partial Refund Scenarios</h4>
          <p className="text-slate-600 mb-2">Where full refund not justified but complete denial inequitable:</p>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Pro-Rata Refund</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) For partially consumed services or partially executed transactions;</li>
              <li>(ii) Calculated as per Article 9.4;</li>
              <li>(iii) Stakeholder may accept or reject partial refund;</li>
              <li>(iv) Rejection does not entitle to full refund but may pursue dispute resolution;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Refund with Enhanced Deductions</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Where Stakeholder's conduct contributed to transaction failure: Enhanced forfeiture (up to 40% of transaction value); Recovery of Platform's actual damages; Opportunity cost compensation;</li>
              <li>(ii) Stakeholder may challenge quantum through dispute resolution;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Conditional Refund</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Refund subject to conditions: Stakeholder signing confidentiality agreement; Non-disparagement clause; Waiver of certain claims; Return of proprietary information or materials;</li>
              <li>(ii) Stakeholder may choose to decline conditions and pursue full rights;</li>
            </ul>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">15.4 Hardship and Compassionate Grounds</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Exceptional Circumstances Consideration</p>
            <p className="text-slate-600 mb-1">Notwithstanding strict policy provisions, Platform may exercise discretion for refund in genuine hardship cases:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>Medical Emergencies:</strong> Life-threatening illness requiring urgent funds; Certified by qualified medical practitioner; Hospital bills and treatment cost evidence;</li>
              <li>(ii) <strong>Death in Family:</strong> Death of Stakeholder's immediate family member; Funeral expenses and financial hardship; Death certificate and relationship proof;</li>
              <li>(iii) <strong>Natural Calamities:</strong> Stakeholder affected by earthquake, flood, fire, etc.; Loss of livelihood or property; Government notification or disaster certificate;</li>
              <li>(iv) <strong>Involuntary Job Loss:</strong> Sudden unemployment due to retrenchment or company closure; Termination letter and financial hardship evidence; Genuine inability to continue with investment commitment;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Compassionate Refund Procedure</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>Application:</strong> Separate compassionate grounds application with supporting evidence;</li>
              <li>(ii) <strong>Review:</strong> Senior management review (CFO, CCO, Head-HR/CSR);</li>
              <li>(iii) <strong>Discretion:</strong> Pure discretion; no legal entitlement;</li>
              <li>(iv) <strong>Conditions:</strong> Typically reduced deductions (waiver of forfeitures); Processing on priority basis; May require undertaking not to create precedent;</li>
              <li>(v) <strong>Non-Precedential:</strong> Each case evaluated on merits; no precedent value;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Ex-Gratia Payments</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Even where no refund entitlement, Platform may offer ex-gratia payment as gesture of goodwill;</li>
              <li>(ii) <strong>Quantum:</strong> Platform's absolute discretion;</li>
              <li>(iii) Acceptance constitutes full and final settlement;</li>
              <li>(iv) No admission of liability or wrongdoing;</li>
            </ul>
          </div>
        </div>
      </div>

      {/* Article 16 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Zap className="text-indigo-600" size={20} /> ARTICLE 16: FORCE MAJEURE PROVISIONS
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">16.1 Definition of Force Majeure</h4>
          <p className="text-slate-600 mb-2">"Force Majeure Event" means any event, circumstance, or cause beyond the reasonable control of the Platform that prevents, hinders, or delays the Platform's performance of its obligations under this Policy, including but not limited to:</p>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Acts of God:</strong> Earthquakes, floods, tsunamis, cyclones, hurricanes, tornadoes; Lightning, storms, extreme weather conditions; Landslides, avalanches, volcanic eruptions; Epidemics, pandemics (including COVID-19 type situations); Plagues, outbreaks of infectious diseases;</li>
            <li>(b) <strong>Acts of Government or Regulatory Authorities:</strong> Change in law, regulation, or regulatory interpretation; Imposition of emergency, curfew, or lockdown; Nationalisation, expropriation, or confiscation; Demonetisation or currency restrictions; Capital controls or foreign exchange restrictions; SEBI/RBI circulars materially affecting transaction feasibility; Regulatory prohibition or moratorium on specific activities;</li>
            <li>(c) <strong>Political and Civil Unrest:</strong> War, invasion, hostilities, terrorist attacks; Civil war, rebellion, insurrection, revolution; Riots, civil commotion, strikes, labor disputes; Military or usurped power, martial law;</li>
            <li>(d) <strong>Technology and Infrastructure Failures:</strong> Failure of telecommunications networks or internet infrastructure; Cyber-attacks, hacking, ransomware attacks on critical systems; Electricity grid failures or prolonged power outages; Failure of stock exchange systems or depository systems; Banking system failures (RTGS/NEFT/payment systems down); Damage to data centers or IT infrastructure;</li>
            <li>(e) <strong>Market Disruptions:</strong> Suspension of trading on stock exchanges; Closure of banking operations or payment systems; Failure of clearing and settlement systems; Suspension of depository services;</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">16.2 Consequences of Force Majeure</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Suspension of Obligations</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Upon occurrence of Force Majeure Event: Platform's obligations under this Policy suspended to the extent prevented by Force Majeure; Timelines specified in this Policy automatically extended; No liability for delays or non-performance during Force Majeure period; Performance resumes within reasonable time after Force Majeure ceases;</li>
              <li>(ii) Stakeholder's Obligations: Stakeholder's obligations (documentation submission, payment of charges, etc.) also suspended proportionately;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Notice Requirement</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Platform shall notify Stakeholders of Force Majeure Event: Promptly after becoming aware of Force Majeure Event; Through website notice, email broadcast, or other feasible means; Describing nature of event, expected duration, impact on operations; Updates provided periodically if Force Majeure continues;</li>
              <li>(ii) Specific Transaction Impact: For pending refund requests, individual communication about likely delay;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Mitigation Efforts</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Platform shall use reasonable endeavors to: Mitigate impact of Force Majeure Event; Resume normal operations at earliest; Utilize alternate systems, processes, or service providers; Prioritize critical and time-sensitive refunds;</li>
              <li>(ii) No Absolute Obligation: Mitigation efforts best-efforts basis; no guarantee of continuity during Force Majeure;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(d) Proportionate Adjustment</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) If Force Majeure Event results in increased costs: Platform may recover reasonable incremental costs from Stakeholders; Transparent disclosure of cost increase rationale; Stakeholder option to withdraw if costs unreasonable;</li>
              <li>(ii) If Force Majeure Event results in savings: Benefits may be passed on to Stakeholders (Platform discretion);</li>
            </ul>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">16.3 Prolonged Force Majeure</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Extended Duration</p>
            <p className="text-slate-600 mb-1">If Force Majeure Event continues for more than 90 (ninety) days:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Either party may terminate pending transaction by written notice;</li>
              <li>(ii) Refund processed on best-efforts basis with following adjustments: Actual costs incurred until Force Majeure Event: Deducted; No liquidated damages or forfeitures; No interest on delayed refund; Extended processing timeline acceptable;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Impossibility of Performance</p>
            <p className="text-slate-600 mb-1">If Force Majeure Event makes performance permanently impossible:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Transaction deemed frustrated under Section 56, Indian Contract Act, 1872;</li>
              <li>(ii) Refund of amounts received less actual expenditure incurred;</li>
              <li>(iii) No damages or compensation by either party;</li>
              <li>(iv) Both parties discharged from further obligations;</li>
            </ul>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">16.4 Exclusions from Force Majeure</h4>
          <p className="text-slate-600 mb-2">The following shall NOT constitute Force Majeure:</p>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) Economic hardship, financial difficulties, or insolvency of Platform;</li>
            <li>(b) Increase in costs, expenses, or unfavorable market conditions;</li>
            <li>(c) Failure of Platform's contractors, vendors, or service providers (unless they are also affected by Force Majeure);</li>
            <li>(d) Strikes or labor disputes specific to Platform (not general industry-wide);</li>
            <li>(e) Negligence, willful misconduct, or breach by Platform or its personnel;</li>
            <li>(f) Events that were foreseeable and preventable through reasonable precautions;</li>
            <li>(g) Failure to obtain routine licenses, permits, or approvals due to Platform's own delay;</li>
            <li>(h) IT system failures preventable through standard backup and disaster recovery measures;</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">16.5 COVID-19 and Pandemic-Related Clarifications</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Pandemic as Force Majeure</p>
            <p className="text-slate-600 mb-1">Epidemics and pandemics (including COVID-19) qualify as Force Majeure Events to the extent they:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Result in government-imposed lockdowns or restrictions;</li>
              <li>Cause unavailability of personnel or closure of offices;</li>
              <li>Disrupt banking, depository, or regulatory operations;</li>
              <li>Make performance genuinely impossible or impracticable;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Adaptations and Workarounds</p>
            <p className="text-slate-600 mb-1">To the extent Platform can continue operations through:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Remote working arrangements;</li>
              <li>Digital processes and electronic documentation;</li>
              <li>Alternate service providers or channels;</li>
              <li>Such operations shall continue, and pandemic shall not excuse performance possible through reasonable adaptations;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Precedent Value</p>
            <p className="text-slate-600 mb-1">COVID-19 experience has established that:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Digital refund processing can continue during lockdowns;</li>
              <li>Electronic documentation and verification feasible;</li>
              <li>Banking operations largely unaffected (digital channels);</li>
              <li>Force Majeure claim viable only for genuinely impossible tasks (e.g., physical document collection where stakeholder in remote area without digital access);</li>
            </ul>
          </div>
        </div>
      </div>

      {/* Article 17 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <ShieldOff className="text-indigo-600" size={20} /> ARTICLE 17: LIABILITY LIMITATIONS AND DISCLAIMERS
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">17.1 Aggregate Liability Cap</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Maximum Liability</p>
            <p className="text-slate-600 mb-1">The Platform's total aggregate liability to any Stakeholder arising out of or in connection with refund matters under this Policy, whether in contract, tort (including negligence), breach of statutory duty, or otherwise, shall not exceed:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) For individual transactions: The amount of consideration paid by the Stakeholder for the specific transaction giving rise to the refund claim;</li>
              <li>(ii) For subscription services: The subscription fee paid by Stakeholder for the relevant subscription period;</li>
              <li>(iii) For advisory services: The advisory fee paid for the specific engagement;</li>
            </ul>
            <p className="text-slate-600 mt-2">Provided that: This liability cap shall not apply in cases of: Fraud or willful misconduct by Platform's directors or senior management; Criminal acts or gross negligence; Liability mandated by statute that cannot be contractually limited;</p>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Exclusion of Consequential Damages</p>
            <p className="text-slate-600 mb-1">The Platform shall not be liable for:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) <strong>Indirect or Consequential Losses:</strong> Loss of profit, revenue, or business opportunity; Loss of anticipated savings or benefits; Loss of goodwill or reputation; Business interruption or downtime; Wasted management or staff time;</li>
              <li>(ii) <strong>Special or Punitive Damages:</strong> Exemplary or punitive damages; Aggravated damages; Multiple or treble damages;</li>
              <li>(iii) <strong>Third-Party Claims:</strong> Losses arising from claims by third parties; Regulatory penalties or fines imposed on Stakeholder; Tax liabilities or assessments; Litigation costs in disputes with third parties;</li>
            </ul>
            <p className="text-slate-600 mt-1">Exception: Consequential damages limitation shall not apply where such damages directly and proximately result from Platform's fraud or willful default;</p>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Timing of Liability Assessment</p>
            <p className="text-slate-600 mb-1">Liability for delayed refunds calculated as of:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
              <li>Date when refund should have been processed as per Policy timelines; NOT</li>
              <li>Date when Stakeholder claims to have needed funds or suffered loss;</li>
            </ul>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">17.2 Service Availability and Performance Disclaimers</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) "As Is" Service Provision</p>
            <p className="text-slate-600 mb-1">The Platform provides refund processing services on an "AS IS" and "AS AVAILABLE" basis. Platform makes no warranties or representations regarding:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Uninterrupted or error-free service availability;</li>
              <li>(ii) Accuracy, completeness, or reliability of any information provided;</li>
              <li>(iii) Timeliness of refund processing beyond committed timelines;</li>
              <li>(iv) Availability of specific refund modes or banking channels;</li>
            </ul>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Third-Party Dependencies</p>
            <p className="text-slate-600 mb-1">Platform is dependent on third-party service providers including: Banking partners for payment processing; Payment gateways for transaction facilitation; NSDL/CDSL for depository services; IT infrastructure and cloud service providers; Regulatory authorities for approvals and clearances;</p>
            <p className="text-slate-600 mt-1">Disclaimer: Platform disclaims liability for third-party failures, delays, or errors beyond Platform's reasonable control;</p>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Technology and Cyber Risks</p>
            <p className="text-slate-600 mb-1">While Platform implements industry-standard cybersecurity measures:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
              <li>(i) Platform cannot guarantee absolute security against: Sophisticated cyber-attacks or zero-day vulnerabilities; Targeted hacking attempts by state or non-state actors; Social engineering attacks on Stakeholders;</li>
              <li>(ii) Platform's liability limited to direct losses resulting from Platform's failure to implement reasonable security measures;</li>
              <li>(iii) Stakeholder responsible for safeguarding own credentials, OTPs, and devices;</li>
            </ul>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">17.3 Regulatory and Legal Compliance Disclaimers</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Regulatory Changes</p>
            <p className="text-slate-600 mb-1">The Platform operates in a highly regulated environment. Regulatory changes may: Invalidate or alter refund eligibility; Impose additional compliance requirements; Delay or prevent refund processing; Require retrospective adjustments;</p>
            <p className="text-slate-600 mt-1">Disclaimer: Platform not liable for losses arising from regulatory changes or new regulatory interpretations;</p>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Tax Implications</p>
            <p className="text-slate-600 mb-1">Stakeholders are solely responsible for: Understanding tax implications of refunds received; Complying with income tax filing and disclosure requirements; Claiming TDS credits or seeking refunds from tax authorities; GST compliance on taxable services;</p>
            <p className="text-slate-600 mt-1">Disclaimer: Platform provides no tax advice. Stakeholders must consult qualified tax advisors;</p>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Legal Advice Disclaimer</p>
            <p className="text-slate-600 mb-1">Nothing in this Policy constitutes legal advice. This Policy is: A contractual document governing commercial relationship; Subject to interpretation based on specific facts; Not a substitute for independent legal counsel;</p>
            <p className="text-slate-600 mt-1">Advisory: Stakeholders with significant transactions or complex issues should consult lawyers specializing in securities law, company law, or contract law;</p>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">17.4 Investment and Financial Disclaimers</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) No Investment Advice</p>
            <p className="text-slate-600 mb-1">Refund processing services are administrative/operational functions. Platform does not: Advise on investment merit or suitability; Recommend whether to seek refund or continue with investment; Provide financial planning or wealth management advice; Guarantee returns or performance of securities;</p>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Risk of Pre-IPO Investments</p>
            <p className="text-slate-600 mb-1">Stakeholders acknowledge that Pre-IPO Securities investments carry inherent risks: Illiquidity and lack of ready market; Valuation uncertainty; Information asymmetry; Regulatory and compliance risks; Business and operational risks of issuer companies;</p>
            <p className="text-slate-600 mt-1">Clarification: Dissatisfaction with investment performance or post-transaction regret does not constitute grounds for refund;</p>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Market Risk</p>
            <p className="text-slate-600 mb-1">Stakeholders bear full market risk for investments: Decline in valuation post-transaction; Failure of issuer company; Delay or cancellation of anticipated IPO; Macroeconomic or sectoral downturns;</p>
            <p className="text-slate-600 mt-1">Platform's liability limited to contractual performance obligations, not investment outcomes;</p>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">17.5 Reasonable Care Standard</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Standard of Care Commitment</p>
            <p className="text-slate-600 mb-1">Platform commits to: Exercise reasonable care and diligence in refund processing; Implement industry-standard processes and controls; Act in good faith and with commercial reasonableness; Maintain competent and adequately trained personnel; Comply with Applicable Law and regulatory guidelines;</p>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) No Strict Liability</p>
            <p className="text-slate-600 mb-1">Platform's liability is fault-based, not strict: Stakeholder must prove breach of duty or negligence; Mere fact of loss or delay insufficient to establish liability; Contributory negligence by Stakeholder reduces Platform's liability proportionately;</p>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Best Efforts vs. Guaranteed Outcomes</p>
            <p className="text-slate-600 mb-1">Platform undertakes best efforts obligations, not guaranteed outcome obligations: Best efforts to process refunds within timelines (subject to Force Majeure, third-party dependencies); Best efforts to recover funds from third parties in case of disputes; Best efforts to mitigate damages and losses; No guarantee that every refund request will be approved or that specific outcomes will be achieved;</p>
          </div>
        </div>
      </div>

      {/* Article 18 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <FileWarning className="text-indigo-600" size={20} /> ARTICLE 18: INDEMNIFICATION PROVISIONS
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">18.1 Stakeholder's Indemnification Obligations</h4>
          <p className="text-slate-600 mb-2">The Stakeholder agrees to indemnify, defend, and hold harmless the Platform, its affiliates, directors, officers, employees, agents, contractors, and representatives (collectively "Indemnified Parties") from and against any and all:</p>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) Claims, demands, actions, suits, or proceedings;</li>
            <li>(b) Losses, damages, liabilities, costs, and expenses (including reasonable legal fees and disbursements);</li>
          </ul>
          <p className="text-slate-600 mt-2 mb-2">Arising out of or relating to:</p>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(i) <strong>Breach of Representations and Warranties:</strong> False or misleading information provided in refund request; Forged, fabricated, or tampered documents submitted; Misrepresentation of facts or concealment of material information; Breach of KYC declarations or anti-money laundering certifications;</li>
            <li>(ii) <strong>Violation of Laws:</strong> Stakeholder's violation of SEBI regulations, PMLA, FEMA, tax laws, or other Applicable Law; Use of Platform's services for unlawful purposes; Money laundering, terrorist financing, or fraud; Violation of sanctions or regulatory restrictions;</li>
            <li>(iii) <strong>Third-Party Claims:</strong> Claims by issuers, sellers, or counterparties arising from Stakeholder's breach; Claims by other stakeholders affected by Stakeholder's misconduct; Regulatory actions or penalties imposed due to Stakeholder's violations; Tax claims, assessments, or recovery proceedings against Platform arising from Stakeholder's transactions;</li>
            <li>(iv) <strong>Unauthorized Use:</strong> Unauthorized access to or use of another person's account; Identity theft or impersonation; Sharing of login credentials or security breaches;</li>
            <li>(v) <strong>Intellectual Property Infringement:</strong> If Stakeholder uploads or submits content infringing third-party IP rights;</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">18.2 Platform's Indemnification to Stakeholder</h4>
          <p className="text-slate-600 mb-2">The Platform agrees to indemnify the Stakeholder from direct losses arising out of:</p>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Platform's Breach:</strong> Material breach of this Policy or Transaction Documentation by Platform; Fraud or willful misconduct by Platform's personnel; Gross negligence in refund processing causing direct financial loss;</li>
            <li>(b) <strong>Data Breach:</strong> Unauthorized disclosure of Stakeholder's confidential information due to Platform's security breach; Identity theft resulting from Platform's failure to secure data;</li>
          </ul>
          <p className="text-slate-600 mt-2">Provided that: Stakeholder provides prompt notice of claim; Stakeholder cooperates in defense of claim; Platform has sole control of defense and settlement; Platform's liability subject to Article 17 limitations;</p>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">18.3 Indemnification Procedures</h4>
          
          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(a) Notice of Claim</p>
            <p className="text-slate-600 mb-1">Party seeking indemnification ("Indemnified Party") shall: Promptly notify indemnifying party ("Indemnifying Party") in writing of any claim; Provide reasonable details of claim including nature, quantum, and supporting documents; Failure to provide prompt notice may reduce indemnity to extent of prejudice;</p>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(b) Defense of Claim</p>
            <p className="text-slate-600 mb-1">Indemnifying Party shall have right to: Assume defense and control of claim with counsel of its choice; Settle claim on terms it deems appropriate; Require Indemnified Party's cooperation in defense; Indemnified Party may participate in defense with own counsel at own cost;</p>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(c) Settlement Restrictions</p>
            <p className="text-slate-600 mb-1">Indemnifying Party shall not settle claim without Indemnified Party's consent (not to be unreasonably withheld) if settlement: Imposes obligations on Indemnified Party; Requires admission of liability by Indemnified Party; Involves non-monetary remedies affecting Indemnified Party;</p>
          </div>

          <div className="ml-4 mb-4">
            <p className="text-slate-700 font-semibold mb-2">(d) Mitigation Obligation</p>
            <p className="text-slate-600 mb-1">Indemnified Party shall take reasonable steps to mitigate losses; Indemnifying Party not liable for losses that could have been avoided through reasonable mitigation;</p>
          </div>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">18.4 Exclusive Remedy Provision</h4>
          <p className="text-slate-600 mb-2">To the maximum extent permitted by law:</p>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) Indemnification under this Article 18 shall be Stakeholder's exclusive remedy for: Third-party claims covered by Platform's indemnity; Losses within scope of Platform's indemnification obligation;</li>
            <li>(b) This exclusivity does not apply to: Claims for specific performance or injunctive relief; Statutory remedies that cannot be contractually waived; Claims for fraud or criminal conduct;</li>
          </ul>
        </div>
      </div>

      {/* Article 19 */}
      <div className="subsection mb-10">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <Activity className="text-indigo-600" size={20} /> ARTICLE 19: RISK DISCLOSURES AND ACKNOWLEDGMENTS
        </h3>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">19.1 General Investment Risks</h4>
          <p className="text-slate-600 mb-2">Stakeholders acknowledge and accept the following risks associated with Pre-IPO and unlisted securities investments:</p>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Illiquidity Risk:</strong> Limited or no secondary market for unlisted securities; Difficulty in exiting investment before IPO or strategic sale; Potential inability to liquidate holdings in emergencies; Long holding periods (3-7 years or more) may be required;</li>
            <li>(b) <strong>Valuation Risk:</strong> Absence of market-discovered prices; Reliance on subjective valuation methodologies; Significant variance in valuations by different experts; Down-rounds and valuation corrections possible;</li>
            <li>(c) <strong>Information Risk:</strong> Limited public disclosure compared to listed companies; Information asymmetry between investors and promoters; Periodic financial reporting not mandated; Difficulty in conducting due diligence;</li>
            <li>(d) <strong>Business Risk:</strong> High failure rate of startups and early-stage companies; Execution risks in business plans; Competition, technological disruption, regulatory changes; Key person dependencies and management risks;</li>
            <li>(e) <strong>Regulatory Risk:</strong> Evolving regulatory framework for private markets; Possibility of retrospective regulatory changes; Compliance failures by issuer companies; SEBI restrictions on transfer, pricing, or disclosures;</li>
            <li>(f) <strong>IPO Risk:</strong> No guarantee that company will undertake IPO; IPO may be delayed, downsized, or cancelled; IPO pricing may be below private transaction valuation; Lock-in periods preventing immediate exit post-IPO;</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">19.2 Refund-Specific Risks</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Processing Delays:</strong> Refunds may take longer than estimated timelines; Verification and approval processes inherently time-consuming; Third-party dependencies may cause delays; Documentation deficiencies extend processing time;</li>
            <li>(b) <strong>Deduction Variability:</strong> Actual deductions may exceed estimates due to unforeseen costs; Third-party charges subject to change; Complex cases may involve higher processing charges; Liquidated damages and forfeitures can be substantial;</li>
            <li>(c) <strong>Eligibility Uncertainty:</strong> Refund eligibility determined on case-by-case basis; Policy interpretation may vary based on facts; Platform has discretion in borderline cases; Rejection possible despite stakeholder's subjective expectation;</li>
            <li>(d) <strong>Tax and Regulatory Implications:</strong> Refunds may have tax consequences (income, capital gains); TDS deducted on interest components; GST implications on services consumed; Regulatory reporting requirements may apply;</li>
            <li>(e) <strong>Currency and Exchange Rate Risk:</strong> For foreign currency transactions: Exchange rate fluctuations; Conversion charges and correspondent bank fees; FEMA compliance requirements and restrictions;</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">19.3 Technology and Operational Risks</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Cyber and Data Security Risks:</strong> Despite best security practices, no system is 100% secure; Possibility of hacking, data breaches, or cyber-attacks; Phishing and social engineering risks; Stakeholder responsible for safeguarding credentials;</li>
            <li>(b) <strong>System Availability Risks:</strong> Platform or banking systems may experience downtime; Technical glitches may disrupt refund processing; Payment gateway failures possible; Network connectivity issues may impact service;</li>
            <li>(c) <strong>Human Error Risks:</strong> Possibility of processing errors despite quality controls; Mistakes in calculation, data entry, or documentation; Communication gaps or misunderstandings; Platform implements controls but cannot eliminate all errors;</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">19.4 Legal and Dispute Risks</h4>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Litigation Risk:</strong> Refund disputes may result in costly and time-consuming litigation; No guarantee of favorable outcome; Legal proceedings may take years to resolve; Costs of legal proceedings may exceed refund amount;</li>
            <li>(b) <strong>Arbitration Considerations:</strong> Arbitration quicker than courts but still involves costs and time; Arbitral awards binding with limited appeal rights; Enforcement of awards may require court proceedings;</li>
            <li>(c) <strong>Regulatory Proceedings:</strong> SEBI or other regulatory actions may impact transactions; Regulatory investigations can freeze transactions pending resolution; Penalties or disgorgement orders may affect refund quantum;</li>
          </ul>
        </div>

        <div className="mb-6">
          <h4 className="text-lg font-bold text-slate-700 mb-2">19.5 Stakeholder Acknowledgments</h4>
          <p className="text-slate-600 mb-2">By submitting a refund request, the Stakeholder expressly acknowledges that:</p>
          <ul className="list-none pl-4 text-slate-600 space-y-2">
            <li>(a) <strong>Informed Decision:</strong> Stakeholder has read, understood, and accepted all risks disclosed in this Article 19 and throughout this Policy;</li>
            <li>(b) <strong>Professional Advice:</strong> Stakeholder has been advised to seek and, if deemed appropriate, has sought independent legal, financial, and tax advice before transacting;</li>
            <li>(c) <strong>No Guarantees:</strong> Platform has made no guarantees, assurances, or promises regarding: Approval of refund request; Quantum of refund; Timeline for refund processing; Investment outcomes or returns;</li>
            <li>(d) <strong>Voluntary Transaction:</strong> Original transaction and refund request are both voluntary decisions made by Stakeholder without coercion, undue influence, or misrepresentation;</li>
            <li>(e) <strong>Risk-Bearing Capacity:</strong> Stakeholder has adequate financial resources and risk-bearing capacity to absorb potential losses or delays;</li>
            <li>(f) <strong>Due Diligence:</strong> Stakeholder has conducted own due diligence and not relied solely on Platform's information or representations;</li>
            <li>(g) <strong>Binding Agreement:</strong> This Policy constitutes legally binding agreement and Stakeholder is bound by all its terms, conditions, limitations, and exclusions;</li>
          </ul>
        </div>
      </div>
    </section>
  );
}
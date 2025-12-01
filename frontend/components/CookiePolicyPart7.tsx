'use client';

import React from 'react';
import { AlertTriangle, UserX, HelpCircle, Globe, Scale } from 'lucide-react';

export default function CookiePolicyPart7() {
  return (
    <section id="part-4-continued" className="section mb-12">
      {/* 4.7 */}
      <div id="point-4-7" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <AlertTriangle className="text-indigo-600" size={20} /> 4.7 RIGHT TO OBJECT
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.7.1 Scope of Right:</h4>
            <p className="text-slate-600 mb-2">Users have the right to object to processing of Personal Information through cookies where:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Processing based on Legitimate Interests:</strong> User objects to processing justified on legitimate interests grounds;</li>
                <li>(b) <strong>Direct Marketing:</strong> User objects to use of data for marketing purposes;</li>
                <li>(c) <strong>Profiling:</strong> User objects to automated decision-making and profiling;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.7.2 Exercise Procedure:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Marketing Objection:</strong> Click "Unsubscribe" link in marketing emails; Adjust marketing preferences in account settings; Disable advertising cookies through preference center; Register with National Do Not Call (NDNC) Registry for SMS opt-out;</li>
                <li>(b) <strong>Legitimate Interest Processing Objection:</strong> Submit objection via email to dpo@preiposip.com; Explain grounds relating to User's particular situation; Platform must demonstrate compelling legitimate grounds to continue processing;</li>
                <li>(c) <strong>Profiling Objection:</strong> Request human review of automated decisions; Opt out of algorithmic recommendations; Disable personalization features;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.7.3 Platform Response:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Marketing Objection:</strong> Effect immediate cessation within 48 hours;</li>
                <li>(b) <strong>Legitimate Interest Objection:</strong> Acknowledge objection within 7 days; Conduct balancing test within 30 days; Either cease processing or demonstrate compelling legitimate grounds; Provide written justification for decision;</li>
                <li>(c) <strong>Profiling Objection:</strong> Provide alternative non-automated processes; Explain logic of automated decision-making; Enable manual override mechanisms;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.7.4 Consequence of Objection:</h4>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Relevant cookies are immediately disabled;</li>
                <li>Future processing for objected purposes ceases;</li>
                <li>Historical data may be retained for legal compliance;</li>
                <li>Platform functionality may be reduced if objection affects core services;</li>
            </ul>
        </div>
      </div>

      {/* 4.8 */}
      <div id="point-4-8" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4">4.8 RIGHT TO RESTRICT PROCESSING</h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.8.1 Scope of Right:</h4>
            <p className="text-slate-600 mb-2">Users have the right to obtain restriction of processing (i.e., storage only, no active use) where:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Accuracy Contested:</strong> User contests accuracy of data during verification period;</li>
                <li>(b) <strong>Unlawful Processing:</strong> Processing is unlawful but User opposes erasure and requests restriction instead;</li>
                <li>(c) <strong>Data No Longer Needed:</strong> Platform no longer needs data but User requires it for legal claims;</li>
                <li>(d) <strong>Objection Pending:</strong> User has objected to processing and verification of legitimate grounds is pending;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.8.2 Exercise Procedure:</h4>
            <p className="text-slate-600 mb-1">Submit restriction request to dpo@preiposip.com specifying:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Data subject to restriction;</li>
                <li>Grounds for restriction (accuracy dispute, unlawfulness, etc.);</li>
                <li>Preferred duration of restriction;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.8.3 Effect of Restriction:</h4>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Data is stored but not actively processed;</li>
                <li>Data may be processed only with User consent or for legal claims;</li>
                <li>Third parties are notified of restriction where applicable;</li>
                <li>User is informed before restriction is lifted;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.8.4 Platform Response Timeline:</h4>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Acknowledge restriction request within 7 days;</li>
                <li>Implement restriction within 15 days;</li>
                <li>Provide confirmation of restriction implementation;</li>
            </ul>
        </div>
      </div>

      {/* 4.9 */}
      <div id="point-4-9" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <UserX className="text-indigo-600" size={20} /> 4.9 RIGHT TO WITHDRAW CONSENT
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.9.1 Scope of Right:</h4>
            <p className="text-slate-600">Where processing is based on consent, Users have the absolute right to withdraw consent at any time, as easily as it was given.</p>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.9.2 Exercise Procedure:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Cookie Consent Withdrawal:</strong> Access cookie preference center via footer link; Toggle off consent for specific cookie categories; Select "Reject All Optional Cookies" option; Clear browser cookies manually;</li>
                <li>(b) <strong>Marketing Consent Withdrawal:</strong> Click "Unsubscribe" in any marketing email; Adjust preferences in account settings; Contact customer support;</li>
                <li>(c) <strong>Investment Advisory Consent Withdrawal:</strong> Terminate Investment Advisory Agreement in writing; Submit withdrawal notice to dpo@preiposip.com; Effect immediate cessation of advisory services;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.9.3 Effect of Withdrawal:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Prospective Effect:</strong> Withdrawal does not affect lawfulness of processing prior to withdrawal;</li>
                <li>(b) <strong>Immediate Cessation:</strong> Consent-based processing ceases immediately upon withdrawal;</li>
                <li>(c) <strong>Alternative Legal Bases:</strong> Platform may continue processing on other legal bases (contractual necessity, legal obligation, legitimate interests) where applicable;</li>
                <li>(d) <strong>Service Impact:</strong> Withdrawal may render certain Platform services unavailable;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.9.4 No Adverse Consequences:</h4>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>No penalty or adverse treatment for withdrawing consent;</li>
                <li>Continued access to services not dependent on withdrawn consent;</li>
                <li>Clear explanation of services affected by withdrawal;</li>
            </ul>
        </div>
      </div>

      {/* 4.10 */}
      <div id="point-4-10" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4">4.10 RIGHT TO HUMAN INTERVENTION IN AUTOMATED DECISIONS</h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.10.1 Scope of Right:</h4>
            <p className="text-slate-600">Users have the right not to be subject to decisions based solely on automated processing, including profiling, which produce legal effects or similarly significantly affect them.</p>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.10.2 Automated Decisions on Platform:</h4>
            <p className="text-slate-600 mb-1">The Platform employs automated processing for:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Investment Suitability Assessment:</strong> Algorithmic matching of investments to User profile;</li>
                <li>(b) <strong>Risk Scoring:</strong> Automated credit and fraud risk evaluation;</li>
                <li>(c) <strong>Personalization:</strong> Algorithmic content and investment opportunity recommendations;</li>
                <li>(d) <strong>AML Screening:</strong> Automated sanctions and PEP screening;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.10.3 Safeguards Implemented:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Human Oversight:</strong> All significant automated decisions are reviewed by qualified personnel;</li>
                <li>(b) <strong>Contestation Right:</strong> Users can challenge automated decisions and request human review;</li>
                <li>(c) <strong>Explanation:</strong> Users receive meaningful information about logic, significance, and consequences;</li>
                <li>(d) <strong>Override:</strong> Users can request manual assessment where automation produces unexpected results;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.10.4 Exercise Procedure:</h4>
            <p className="text-slate-600 mb-1">Submit request for human intervention to dpo@preiposip.com:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Specify automated decision being contested;</li>
                <li>Explain grounds for disagreement;</li>
                <li>Request manual review by qualified adviser;</li>
            </ul>
        </div>
      </div>

      {/* 4.11 */}
      <div id="point-4-11" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <HelpCircle className="text-indigo-600" size={20} /> 4.11 COMPLAINT AND GRIEVANCE REDRESSAL
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.11.1 Internal Grievance Officer:</h4>
            <p className="text-slate-600 mb-2">Pursuant to Rule 5(9) of IT Rules, 2011, the Platform has appointed a Grievance Officer:</p>
            <div className="bg-slate-50 p-6 rounded-lg border border-slate-200 text-sm text-slate-700 space-y-1">
                <p><strong>Name:</strong> [Grievance Officer Name]</p>
                <p><strong>Designation:</strong> Grievance Officer & Data Protection Officer</p>
                <p><strong>Email:</strong> dpo@preiposip.com</p>
                <p><strong>Phone:</strong> [Contact Number]</p>
                <p><strong>Address:</strong> [Office Address]</p>
                <p><strong>Working Hours:</strong> Monday to Friday, 10:00 AM to 6:00 PM IST</p>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.11.2 Complaint Submission Procedure:</h4>
            <p className="text-slate-600 mb-1">Users may file complaints regarding: Violation of privacy rights; Unlawful processing of Personal Information; Failure to respond to rights requests; Breach of this Cookie Policy; Data security incidents;</p>
            <p className="text-slate-600 font-semibold mt-2">Submission Methods:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) Online grievance form: [URL];</li>
                <li>(b) Email to dpo@preiposip.com;</li>
                <li>(c) Written letter to registered office address;</li>
                <li>(d) Phone during working hours;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.11.3 Complaint Response Timeline:</h4>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li><strong>Acknowledgment:</strong> Within 48 hours;</li>
                <li><strong>Initial Assessment:</strong> Within 7 days;</li>
                <li><strong>Investigation and Resolution:</strong> Within 30 days;</li>
                <li><strong>Final Response:</strong> Written explanation of resolution and actions taken;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.11.4 Escalation to Regulatory Authorities:</h4>
            <p className="text-slate-600 mb-2">If dissatisfied with Platform's resolution, Users may lodge complaints with:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Cyber Crime Coordination Centre (Cyber Cells):</strong> Online complaint at cybercrime.gov.in; Local police cyber crime cell;</li>
                <li>(b) <strong>Ministry of Electronics and Information Technology (MeitY):</strong> For grievances under IT Act and IT Rules; Address: Electronics Niketan, 6 CGO Complex, Lodhi Road, New Delhi - 110003;</li>
                <li>(c) <strong>Securities and Exchange Board of India (SEBI):</strong> For investment-related grievances; SCORES (SEBI Complaints Redress System): scores.gov.in; Email: complaints@sebi.gov.in; Address: SEBI Bhavan, Plot No. C4-A, 'G' Block, Bandra-Kurla Complex, Bandra (E), Mumbai - 400051;</li>
                <li>(d) <strong>Financial Intelligence Unit - India (FIU-IND):</strong> For PMLA-related concerns; Address: 6th Floor, Hotel Samrat, Chanakyapuri, New Delhi - 110021;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.11.5 Judicial Remedies:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Civil Courts:</strong> For compensation under Section 43A of IT Act (up to INR 5 crores);</li>
                <li>(b) <strong>Consumer Forums:</strong> Under Consumer Protection Act, 2019 for deficiency in service;</li>
                <li>(c) <strong>Arbitration:</strong> As per dispute resolution clause in Terms of Service;</li>
                <li>(d) <strong>Criminal Complaints:</strong> For offenses under Sections 43, 66, 72A of IT Act;</li>
            </ul>
        </div>
      </div>

      {/* 4.12 */}
      <div id="point-4-12" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Globe className="text-indigo-600" size={20} /> 4.12 SPECIAL RIGHTS FOR INTERNATIONAL USERS
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.12.1 European Economic Area (EEA) Users - GDPR Rights:</h4>
            <p className="text-slate-600 mb-1">In addition to rights above, EEA Users enjoy:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Enhanced data portability with direct transmission to another controller;</li>
                <li>Right to lodge complaint with local Data Protection Authority;</li>
                <li>Strict limitations on international data transfers;</li>
                <li>Right to receive breach notifications within 72 hours;</li>
            </ul>
            <p className="text-slate-600 mt-1"><strong>Contact for EU Representative:</strong> [EU Representative Details]</p>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.12.2 California Users - CCPA/CPRA Rights:</h4>
            <p className="text-slate-600 mb-1">California residents have additional rights including:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Right to know categories and specific pieces of Personal Information collected;</li>
                <li>Right to know if Personal Information is sold or shared and to whom;</li>
                <li>Right to opt-out of sale/sharing of Personal Information;</li>
                <li>Right to limit use of sensitive personal information;</li>
                <li>Right to non-discrimination for exercising CCPA rights;</li>
            </ul>
            <p className="text-slate-600 mt-1"><strong>Contact for California Privacy Inquiries:</strong> privacy-ca@preiposip.com</p>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">4.12.3 Other Jurisdictions:</h4>
            <p className="text-slate-600">Users from other jurisdictions should review supplementary privacy notices available at [URL] for jurisdiction-specific rights and procedures.</p>
        </div>
      </div>
    </section>
  );
}
'use client';

import React from 'react';
import { FileText, Users, Globe, Shield, Share2, FileCheck, AlertTriangle } from 'lucide-react';

export default function CookiePolicyPart10() {
  return (
    <section id="part-5-continued" className="section mb-12">
      {/* 5.3 */}
      <div id="point-5-3" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <FileText className="text-indigo-600" size={20} /> 5.3 DATA PROCESSING AGREEMENTS (DPA) FRAMEWORK
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.3.1 Mandatory Contractual Requirements:</h4>
            <p className="text-slate-600 mb-2">Every third-party service provider must execute a comprehensive Data Processing Agreement (DPA) containing:</p>
            
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Subject Matter and Duration:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Specific services to be provided;</li>
                        <li>Nature and purpose of processing;</li>
                        <li>Categories of Personal Information processed;</li>
                        <li>Duration of processing and data retention periods;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Obligations of Data Processor (Third Party):</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-2">
                        <li>(i) <strong>Processing Instructions:</strong> Process Personal Information only on documented instructions from Platform; Immediately inform Platform if instructions violate applicable law; Not process data for Processor's own purposes;</li>
                        <li>(ii) <strong>Confidentiality:</strong> Ensure personnel processing data are bound by confidentiality obligations; Maintain confidentiality even after termination of agreement; Implement need-to-know access controls;</li>
                        <li>(iii) <strong>Security Measures:</strong> Implement appropriate technical and organizational measures pursuant to Section 43A of IT Act; Encryption of data at rest and in transit (minimum AES-256 or equivalent); Regular security audits and penetration testing; Incident detection and response procedures; Business continuity and disaster recovery plans; Annual ISO 27001 or SOC 2 certification (or equivalent);</li>
                        <li>(iv) <strong>Sub-Processor Management:</strong> Obtain Platform's prior specific or general written authorization for sub-processors; Maintain updated list of authorized sub-processors accessible to Platform; Impose same data protection obligations on sub-processors; Remain fully liable for sub-processor's performance;</li>
                        <li>(v) <strong>Data Subject Rights:</strong> Assist Platform in responding to User rights requests; Provide technical and organizational measures to facilitate rights exercise; Respond to Platform requests within 5 business days;</li>
                        <li>(vi) <strong>Breach Notification:</strong> Notify Platform within 24 hours of becoming aware of a data breach; Provide detailed information: nature of breach, categories and volume of data affected, likely consequences, remedial measures; Cooperate fully in breach investigation and remediation; Provide notifications to Users as directed by Platform;</li>
                        <li>(vii) <strong>Audit and Inspection:</strong> Allow Platform or authorized auditors to conduct audits and inspections; Provide all information necessary to demonstrate compliance; Submit to audits at least annually or upon reasonable request;</li>
                        <li>(viii) <strong>Data Return and Deletion:</strong> Upon termination, return or delete all Personal Information as directed by Platform; Provide certification of deletion within 30 days of termination; Exception for data required to be retained by law (with restricted access);</li>
                        <li>(ix) <strong>Cross-Border Transfers:</strong> Implement appropriate safeguards for international data transfers; Execute Standard Contractual Clauses (SCCs) where applicable; Comply with local data localization requirements;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Platform's Obligations as Data Controller:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Provide clear, lawful processing instructions;</li>
                        <li>Ensure legal basis exists for processing;</li>
                        <li>Maintain oversight of Processor's activities;</li>
                        <li>Conduct due diligence before engagement and periodically thereafter;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(d) Liability and Indemnification:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-2">
                        <li>(i) <strong>Joint and Several Liability:</strong> Where Processor and Platform are both responsible under IT Act or other law, they are jointly and severally liable to Users;</li>
                        <li>(ii) <strong>Indemnification:</strong> Processor indemnifies Platform against: Claims arising from Processor's breach of DPA; Regulatory fines and penalties resulting from Processor's non-compliance; Costs of remediation and notification following Processor-caused breaches; Damage to Platform's reputation;</li>
                        <li>(iii) <strong>Limitation of Liability:</strong> Subject to mandatory statutory liability under Section 43A of IT Act (cannot be contractually limited);</li>
                        <li>(iv) <strong>Insurance:</strong> Processor maintains adequate cyber liability insurance (minimum INR 10 crores for major processors);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(e) Governing Law and Dispute Resolution:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Governed by Indian law;</li>
                        <li>Disputes resolved through arbitration in accordance with Arbitration and Conciliation Act, 1996;</li>
                        <li>Seat of arbitration: Mumbai, India;</li>
                        <li>Emergency interim relief available through Indian courts;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(f) Regulatory Compliance:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Processor warrants compliance with IT Act, IT Rules, PMLA, SEBI regulations, and other applicable laws;</li>
                        <li>Processor maintains all necessary registrations, licenses, and certifications;</li>
                        <li>Processor provides evidence of compliance upon request;</li>
                    </ul>
                </div>
            </div>
        </div>
      </div>

      {/* 5.4 */}
      <div id="point-5-4" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Users className="text-indigo-600" size={20} /> 5.4 SUB-PROCESSOR MANAGEMENT AND AUTHORIZATION
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.4.1 General Authorization Framework:</h4>
            <p className="text-slate-600 mb-2">The Platform maintains a publicly accessible Sub-Processor List at [URL], which includes:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) Name and contact details of each sub-processor;</li>
                <li>(b) Services provided by sub-processor;</li>
                <li>(c) Location of data processing;</li>
                <li>(d) Date of authorization;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.4.2 User Notification and Objection Rights:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-2">
                <li>(a) <strong>Prior Notice:</strong> Platform provides minimum 30 days' advance notice before adding new sub-processors;</li>
                <li>(b) <strong>Objection Period:</strong> Users may object to new sub-processors within 30 days of notice on reasonable grounds relating to data protection;</li>
                <li>(c) <strong>Resolution:</strong> If User objects: Platform assesses validity of objection; If objection is reasonable, Platform either: (i) does not engage sub-processor, or (ii) permits User to suspend services without penalty; If objection is unreasonable, Platform may proceed with engagement;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.4.3 Current Authorized Sub-Processors:</h4>
            <p className="text-slate-600 mb-2">(As of [Date])</p>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Primary Processor</th>
                            <th className="px-6 py-3">Sub-Processor</th>
                            <th className="px-6 py-3">Service</th>
                            <th className="px-6 py-3">Location</th>
                            <th className="px-6 py-3">Authorization Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4">Amazon Web Services</td>
                            <td className="px-6 py-4">Elastic Compute Cloud (EC2)</td>
                            <td className="px-6 py-4">Virtual server hosting</td>
                            <td className="px-6 py-4">India (Mumbai), Singapore</td>
                            <td className="px-6 py-4">[Date]</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4">Google Analytics</td>
                            <td className="px-6 py-4">Google Cloud Platform</td>
                            <td className="px-6 py-4">Data storage and processing</td>
                            <td className="px-6 py-4">United States</td>
                            <td className="px-6 py-4">[Date]</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4">Zendesk</td>
                            <td className="px-6 py-4">Amazon Web Services</td>
                            <td className="px-6 py-4">Support platform infrastructure</td>
                            <td className="px-6 py-4">United States</td>
                            <td className="px-6 py-4">[Date]</td>
                        </tr>
                        <tr className="bg-white">
                            <td className="px-6 py-4">Razorpay</td>
                            <td className="px-6 py-4">Multiple bank partners</td>
                            <td className="px-6 py-4">Payment settlement</td>
                            <td className="px-6 py-4">India</td>
                            <td className="px-6 py-4">[Date]</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.4.4 Sub-Processor Due Diligence:</h4>
            <p className="text-slate-600 mb-1">Before authorizing sub-processors, Platform verifies:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li>Security certifications (ISO 27001, SOC 2, PCI-DSS where applicable);</li>
                <li>Financial stability and business continuity plans;</li>
                <li>Compliance with applicable data protection laws;</li>
                <li>Absence of adverse regulatory actions or data breaches;</li>
                <li>Adequacy of insurance coverage;</li>
            </ul>
        </div>
      </div>

      {/* 5.5 */}
      <div id="point-5-5" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Globe className="text-indigo-600" size={20} /> 5.5 CROSS-BORDER DATA TRANSFER MECHANISMS
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.5.1 Legal Framework for International Transfers:</h4>
            <p className="text-slate-600 mb-2">While the IT Act, 2000 does not explicitly regulate cross-border data transfers, the Platform implements safeguards aligned with international best practices:</p>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Statutory Considerations:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) <strong>SEBI Regulations:</strong> Certain investment-related data may be subject to data localization requirements under SEBI guidelines;</li>
                        <li>(ii) <strong>RBI Directives:</strong> Payment and financial data subject to RBI's data localization norms (April 2018 circular);</li>
                        <li>(iii) <strong>Section 43A Compliance:</strong> Adequate security measures must be maintained regardless of data location;</li>
                        <li>(iv) <strong>PMLA Requirements:</strong> Critical KYC and transaction data retained in India for FIU-IND accessibility;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Sectoral Guidelines:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>RBI requires payment system data to be stored in India;</li>
                        <li>Insurance Regulatory and Development Authority (IRDAI) requires insurance data storage in India;</li>
                        <li>Telecom Regulatory Authority of India (TRAI) requires telecom data storage in India;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.5.2 Jurisdictions to Which Data May Be Transferred:</h4>
            <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                <table className="w-full text-sm text-left text-slate-600">
                    <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th className="px-6 py-3">Jurisdiction</th>
                            <th className="px-6 py-3">Adequacy Status</th>
                            <th className="px-6 py-3">Volume of Transfers</th>
                            <th className="px-6 py-3">Purpose</th>
                            <th className="px-6 py-3">Safeguards Applied</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4">United States</td>
                            <td className="px-6 py-4">No adequacy decision</td>
                            <td className="px-6 py-4">High volume</td>
                            <td className="px-6 py-4">Analytics, advertising, cloud services</td>
                            <td className="px-6 py-4">SCCs, Privacy Shield successor framework</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4">European Union</td>
                            <td className="px-6 py-4">Adequate under GDPR</td>
                            <td className="px-6 py-4">Low volume</td>
                            <td className="px-6 py-4">EEA user data processing</td>
                            <td className="px-6 py-4">GDPR Article 45, SCCs</td>
                        </tr>
                        <tr className="bg-white border-b border-slate-100">
                            <td className="px-6 py-4">Singapore</td>
                            <td className="px-6 py-4">No adequacy decision</td>
                            <td className="px-6 py-4">Medium volume</td>
                            <td className="px-6 py-4">Backup and disaster recovery</td>
                            <td className="px-6 py-4">SCCs, PDPA compliance</td>
                        </tr>
                        <tr className="bg-white">
                            <td className="px-6 py-4">United Kingdom</td>
                            <td className="px-6 py-4">Adequate under UK GDPR</td>
                            <td className="px-6 py-4">Low volume</td>
                            <td className="px-6 py-4">UK user data processing</td>
                            <td className="px-6 py-4">UK IDTA (International Data Transfer Agreement)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.5.3 Standard Contractual Clauses (SCCs):</h4>
            <p className="text-slate-600 mb-2">The Platform implements European Commission-approved Standard Contractual Clauses (Decision 2021/914) for transfers to third countries:</p>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Module Selection:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Module Two: Controller to Processor (for service providers);</li>
                        <li>Module Three: Processor to Processor (for sub-processors);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Additional Safeguards:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Encryption of data in transit (TLS 1.3 or higher) and at rest (AES-256);</li>
                        <li>Pseudonymization where technically feasible;</li>
                        <li>Regular audits of third-country processors;</li>
                        <li>Commitment to challenge unlawful government access requests;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Transfer Impact Assessment (TIA):</p>
                    <p className="text-slate-600 mb-1">For transfers to United States and other jurisdictions without adequacy decisions, Platform conducts TIA evaluating:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-2">
                        <li>(i) <strong>Laws in Destination Country:</strong> Government surveillance laws (e.g., US FISA Section 702); Data access powers of law enforcement and intelligence agencies; Adequacy of legal protections and oversight mechanisms; Existence of redress mechanisms for individuals;</li>
                        <li>(ii) <strong>Practical Experience:</strong> History of government access requests; Transparency reports from service providers; Litigation and enforcement actions;</li>
                        <li>(iii) <strong>Supplementary Measures:</strong> Technical measures: encryption, anonymization, data minimization; Organizational measures: access controls, staff training, contractual restrictions; Legal measures: challenge government requests, transparency commitments;</li>
                        <li>(iv) <strong>Conclusion:</strong> Platform determines whether combination of SCCs and supplementary measures provides essentially equivalent protection to Indian/EU standards;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.5.4 Data Localization Compliance:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Critical Data Stored in India:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>All KYC and identity verification data;</li>
                        <li>PMLA-regulated transaction records;</li>
                        <li>Payment system data (per RBI directive);</li>
                        <li>SEBI-regulated investment advisory records;</li>
                        <li>Core customer relationship data;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Permitted International Processing:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Analytics and aggregate statistical data (anonymized);</li>
                        <li>Marketing and advertising data (with consent);</li>
                        <li>Technical support and troubleshooting data (pseudonymized);</li>
                        <li>Backup and disaster recovery (encrypted);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Architecture:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Primary database servers located in AWS Mumbai region;</li>
                        <li>Real-time replication to AWS Singapore (encrypted);</li>
                        <li>Critical data not transferred to jurisdictions outside India/Singapore without User consent;</li>
                        <li>Daily backups retained in India for 90 days;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.5.5 Government Access and Data Requests:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Lawful Access Procedures:</p>
                    <p className="text-slate-600 mb-1">Where foreign governments request access to User data:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) Platform requires: Valid legal process (court order, subpoena, warrant); Specific identification of Users and data sought; Demonstration of legal authority and jurisdiction;</li>
                        <li>(ii) Platform conducts legal review to assess: Validity of request under Indian law; Conflict with Indian data protection obligations; Proportionality and necessity of disclosure;</li>
                        <li>(iii) Where permissible, Platform notifies affected Users before disclosure unless legally prohibited (gag order);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Indian Government Requests:</p>
                    <p className="text-slate-600 mb-1">Compliant with:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Section 69 of IT Act (lawful interception);</li>
                        <li>Section 91 of Code of Criminal Procedure, 1973 (summons to produce documents);</li>
                        <li>Section 69A of IT Act (blocking orders);</li>
                    </ul>
                    <p className="text-slate-600 mt-1">Platform maintains register of government requests and disclosures (where not prohibited).</p>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Transparency Reporting:</p>
                    <p className="text-slate-600 mb-1">Platform publishes annual Transparency Report disclosing:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Number of government data requests received;</li>
                        <li>Number of Users affected;</li>
                        <li>Types of data requested;</li>
                        <li>Number of requests complied with, rejected, or challenged;</li>
                        <li>Aggregate statistics without identifying specific cases;</li>
                    </ul>
                </div>
            </div>
        </div>
      </div>

      {/* 5.6 */}
      <div id="point-5-6" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Shield className="text-indigo-600" size={20} /> 5.6 THIRD-PARTY COOKIE PROVIDER OBLIGATIONS
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.6.1 Prohibited Activities:</h4>
            <p className="text-slate-600 mb-2">Third-party cookie providers are contractually prohibited from:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Unauthorized Purposes:</strong> Using Platform User data for purposes other than providing contracted services;</li>
                <li>(b) <strong>Data Sales:</strong> Selling, renting, or licensing User data to other parties;</li>
                <li>(c) <strong>Cross-Contextualization:</strong> Combining Platform User data with data from other sources to create enriched profiles without consent;</li>
                <li>(d) <strong>Unauthorized Sharing:</strong> Disclosing User data to affiliates, partners, or other third parties without Platform authorization;</li>
                <li>(e) <strong>Competitive Use:</strong> Using User data to compete with Platform or solicit Platform Users;</li>
                <li>(f) <strong>Excessive Retention:</strong> Retaining User data beyond periods specified in DPA;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.6.2 Security Requirements:</h4>
            <p className="text-slate-600 mb-2">Third parties must implement:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Access Controls:</strong> Role-based access control (RBAC); Multi-factor authentication (MFA) for privileged accounts; Regular access reviews and privilege de-escalation; Immediate revocation upon employment termination;</li>
                <li>(b) <strong>Data Encryption:</strong> At-rest encryption (AES-256 or stronger); In-transit encryption (TLS 1.3 or higher); End-to-end encryption for sensitive data; Secure key management systems;</li>
                <li>(c) <strong>Network Security:</strong> Firewall protection and network segmentation; Intrusion detection and prevention systems (IDS/IPS); DDoS mitigation services; Regular vulnerability scanning and penetration testing;</li>
                <li>(d) <strong>Logging and Monitoring:</strong> Comprehensive logging of data access and processing activities; Real-time security monitoring and alerting; Log retention for minimum 180 days; Secure log storage with integrity protection;</li>
                <li>(e) <strong>Incident Response:</strong> Written incident response plan; 24/7 security operations center (SOC) or equivalent; Annual incident response drills; Forensic investigation capabilities;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.6.3 Compliance Certifications:</h4>
            <p className="text-slate-600 mb-2">Major third-party providers must maintain:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>ISO/IEC 27001:2013</strong> - Information Security Management System;</li>
                <li>(b) <strong>ISO/IEC 27701:2019</strong> - Privacy Information Management (where processing SPDI);</li>
                <li>(c) <strong>SOC 2 Type II</strong> - Service Organization Control report covering security, availability, confidentiality;</li>
                <li>(d) <strong>PCI-DSS</strong> - Payment Card Industry Data Security Standard (for payment processors);</li>
                <li>(e) <strong>CERT-In Empanelment</strong> - For security audit providers;</li>
            </ul>
        </div>
      </div>

      {/* 5.7 */}
      <div id="point-5-7" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Share2 className="text-indigo-600" size={20} /> 5.7 USER CONTROL OVER THIRD-PARTY COOKIES
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.7.1 Granular Consent Management:</h4>
            <p className="text-slate-600 mb-2">Users exercise control through:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Initial Consent Banner:</strong> Separate toggles for each third-party category;</li>
                <li>(b) <strong>Cookie Preference Center:</strong> Individual selection of third-party providers: Google Analytics: ☐ Enable / ☑ Disable; Facebook Pixel: ☐ Enable / ☑ Disable; LinkedIn Insight: ☐ Enable / ☑ Disable;</li>
                <li>(c) <strong>Account Settings:</strong> Persistent preferences accessible anytime;</li>
                <li>(d) <strong>Browser Controls:</strong> Instructions provided for browser-level cookie blocking;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.7.2 Third-Party Opt-Out Tools:</h4>
            <p className="text-slate-600 mb-2">Platform provides links to industry opt-out mechanisms:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Network Advertising Initiative (NAI):</strong> http://optout.networkadvertising.org</li>
                <li>(b) <strong>Digital Advertising Alliance (DAA):</strong> http://optout.aboutads.info</li>
                <li>(c) <strong>European Interactive Digital Advertising Alliance (EDAA):</strong> http://www.youronlinechoices.eu</li>
                <li>(d) <strong>Google Ads Settings:</strong> https://adssettings.google.com</li>
                <li>(e) <strong>Facebook Ad Preferences:</strong> https://www.facebook.com/ads/preferences</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.7.3 Browser-Level Controls:</h4>
            <p className="text-slate-600 mb-2">Platform provides instructions for:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Google Chrome:</strong> Settings {'>'} Privacy and Security {'>'} Cookies and other site data</li>
                <li>(b) <strong>Mozilla Firefox:</strong> Options {'>'} Privacy & Security {'>'} Cookies and Site Data</li>
                <li>(c) <strong>Apple Safari:</strong> Preferences {'>'} Privacy {'>'} Manage Website Data</li>
                <li>(d) <strong>Microsoft Edge:</strong> Settings {'>'} Privacy, search, and services {'>'} Cookies and site permissions</li>
                <li>(e) <strong>Mobile Browsers:</strong> Device-specific instructions for iOS Safari and Android Chrome</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.7.4 Do Not Track (DNT) Signals:</h4>
            <p className="text-slate-600">Current Status: Platform acknowledges Do Not Track browser signals but does not currently implement automated response due to lack of industry standard. Platform is monitoring development of Global Privacy Control (GPC) standard for potential future implementation.</p>
        </div>
      </div>

      {/* 5.8 */}
      <div id="point-5-8" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <FileCheck className="text-indigo-600" size={20} /> 5.8 ACCOUNTABILITY AND AUDIT MECHANISMS
        </h3>
        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.8.1 Internal Audits:</h4>
            <p className="text-slate-600">Platform conducts: Quarterly Review of active third-party relationships and data flows; Semi-Annual Assessment of third-party security certifications and compliance status; Annual Comprehensive privacy audit including third-party data processing activities;</p>
        </div>
        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.8.2 External Audits:</h4>
            <p className="text-slate-600">Third-party processors undergo: Annual independent security audits by CERT-In empaneled auditors; On-demand audits triggered by: security incidents, regulatory changes, User complaints, change in risk profile;</p>
        </div>
        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.8.3 Audit Rights:</h4>
            <p className="text-slate-600">Platform reserves right to: Conduct on-site inspections of third-party facilities (with reasonable notice); Review third-party security logs and access records; Engage independent auditors to assess compliance; Terminate relationship immediately for material breaches;</p>
        </div>
        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.8.4 Audit Documentation:</h4>
            <p className="text-slate-600">All audits documented with: Audit scope, methodology, and findings; Identified gaps and remediation plans; Timeline for corrective actions; Follow-up verification of remediation; Documentation maintained for 7 years and available to SEBI, RBI, FIU-IND, and other regulators upon request.</p>
        </div>
      </div>

      {/* 5.9 */}
      <div id="point-5-9" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <AlertTriangle className="text-indigo-600" size={20} /> 5.9 TERMINATION AND TRANSITION
        </h3>
        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.9.1 Grounds for Termination:</h4>
            <p className="text-slate-600">Platform may terminate third-party relationships for: Material breach of DPA or security requirements; Insolvency, bankruptcy, or business failure; Loss of required certifications or licenses; Data breach attributable to third party; Change in ownership raising security concerns; Regulatory prohibition or adverse determination;</p>
        </div>
        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.9.2 Transition Period:</h4>
            <p className="text-slate-600 mb-1">Upon termination:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li><strong>30-60 days transition period</strong> for orderly data return;</li>
                <li><strong>Alternative provider onboarded</strong> before termination of critical services;</li>
                <li><strong>User data returned</strong> in structured, machine-readable format;</li>
                <li><strong>Certification of deletion</strong> within 30 days of data return;</li>
                <li><strong>Residual copies deleted</strong> from backup systems within 90 days (except legally required retention);</li>
            </ul>
        </div>
        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">5.9.3 User Notification:</h4>
            <p className="text-slate-600 mb-1">Where termination affects User services:</p>
            <ul className="list-disc pl-6 text-slate-600 space-y-1">
                <li><strong>60 days advance notice</strong> to Users;</li>
                <li><strong>Explanation of impact</strong> on User experience;</li>
                <li><strong>Alternative arrangements</strong> offered where applicable;</li>
                <li><strong>Opt-out opportunity</strong> if Users object to replacement provider;</li>
            </ul>
        </div>
      </div>
    </section>
  );
}
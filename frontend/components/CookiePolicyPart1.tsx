'use client';

import React from 'react';
import { BookOpen, Info, Globe, Scale } from 'lucide-react';

export default function CookiePolicyPart1() {
  return (
    <section id="part-1" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 1</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">PRELIMINARY PROVISIONS, DEFINITIONS, AND STATUTORY FRAMEWORK</h2>
      </div>

      {/* 1.1 */}
      <div id="point-1-1" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
          <BookOpen className="text-indigo-600" size={20} /> 1.1 DOCUMENT IDENTIFICATION AND LEGAL STATUS
        </h3>
        <p className="text-slate-600 mb-4 text-justify">
          This Cookie Policy ("<strong>Policy</strong>") constitutes a legally binding agreement between PreIPOSIP.com, operated by [Legal Entity Name], a company incorporated under the Companies Act, 2013, having its registered office at [Registered Address] (hereinafter referred to as "<strong>Company</strong>", "<strong>Platform</strong>", "<strong>we</strong>", "<strong>us</strong>", or "<strong>our</strong>"), and any natural or legal person who accesses, browses, or utilizes the Platform's services (hereinafter referred to as "<strong>User</strong>", "<strong>you</strong>", or "<strong>your</strong>").
        </p>
        
        <div className="bg-slate-50 p-6 rounded-lg border border-slate-200 text-sm font-mono text-slate-700 space-y-2">
          <p><strong>Effective Date:</strong> [Date]</p>
          <p><strong>Last Updated:</strong> [Date]</p>
          <p><strong>Version:</strong> 1.0</p>
          <p><strong>Document Classification:</strong> Tier-1 Institutional Grade Legal Instrument</p>
          <p><strong>Governing Framework:</strong> Information Technology Act, 2000; SEBI Regulations; Companies Act, 2013</p>
        </div>
      </div>

      {/* 1.2 */}
      <div id="point-1-2" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4">1.2 LEGAL NATURE AND ENFORCEABILITY</h3>
        
        <p className="text-slate-600 mb-2">1.2.1 This Policy constitutes an integral and inseparable component of the comprehensive contractual matrix governing the User's relationship with the Platform, which includes but is not limited to:</p>
        <ul className="list-none pl-4 text-slate-600 space-y-2 mb-4">
          <li>(a) The Terms of Service Agreement;</li>
          <li>(b) The Privacy Policy as mandated under Rule 4 of the Information Technology (Reasonable Security Practices and Procedures and Sensitive Personal Data or Information) Rules, 2011;</li>
          <li>(c) The Investment Risk Disclosure Statement pursuant to SEBI (Investment Advisers) Regulations, 2013;</li>
          <li>(d) The Know Your Customer (KYC) Documentation and Anti-Money Laundering (AML) Compliance Framework established under the Prevention of Money Laundering Act, 2002 and Rules thereunder;</li>
          <li>(e) Any supplementary agreements, addenda, or modifications executed between the Parties.</li>
        </ul>

        <p className="text-slate-600 mb-2 text-justify">1.2.2 By accessing or continuing to access the Platform, the User hereby acknowledges, accepts, and agrees to be legally bound by all provisions contained herein, and such acceptance shall be deemed to constitute informed consent under Section 43A of the Information Technology Act, 2000, and the rules made thereunder.</p>
        <p className="text-slate-600 text-justify">1.2.3 This Policy shall be construed as a contract for service under the Indian Contract Act, 1872, and shall be enforceable in accordance with its terms, subject to the exclusive jurisdiction of courts specified in Section 10 hereof.</p>
      </div>

      {/* 1.3 */}
      <div id="point-1-3" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Scale className="text-indigo-600" size={20} /> 1.3 REGULATORY AND STATUTORY FRAMEWORK
        </h3>
        <p className="text-slate-600 mb-4">This Policy has been formulated in strict compliance with, and derives its legal foundation from, the following legislative, regulatory, and jurisprudential sources:</p>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">1.3.1 PRIMARY LEGISLATION:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Information Technology Act, 2000 (as amended by the Information Technology (Amendment) Act, 2008), particularly:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Section 43A (Compensation for failure to protect data);</li>
                        <li>Section 72A (Punishment for disclosure of information in breach of lawful contract);</li>
                        <li>Section 79 (Exemption from liability of intermediary);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Companies Act, 2013, particularly:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Section 2(76) (Definition of Small Company - applicable for compliance thresholds);</li>
                        <li>Section 128 (Books of account);</li>
                        <li>Section 406 (Punishment for fraud);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Prevention of Money Laundering Act, 2002 (PMLA), particularly:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Section 12 (Obligation to furnish information);</li>
                        <li>Section 12A (Reporting obligations);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(d) Indian Contract Act, 1872, particularly:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Section 10 (What agreements are contracts);</li>
                        <li>Section 13 (Definition of consent);</li>
                        <li>Section 14 (Free consent);</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">1.3.2 SUBORDINATE LEGISLATION AND RULES:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Information Technology (Reasonable Security Practices and Procedures and Sensitive Personal Data or Information) Rules, 2011, particularly:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Rule 3 (Definition of sensitive personal data or information);</li>
                        <li>Rule 4 (Collection of information including sensitive personal data);</li>
                        <li>Rule 5 (Collection of information);</li>
                        <li>Rule 8 (Disclosure of information);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Prevention of Money Laundering (Maintenance of Records) Rules, 2005, particularly:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Rule 3 (Maintenance and preservation of records);</li>
                        <li>Rule 9 (Verification and maintenance of records);</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">1.3.3 SECURITIES AND EXCHANGE BOARD OF INDIA (SEBI) REGULATORY FRAMEWORK:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) SEBI (Investment Advisers) Regulations, 2013, particularly:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Regulation 15 (General obligations and responsibilities);</li>
                        <li>Regulation 16 (Maintenance of records);</li>
                        <li>Regulation 17 (Confidentiality);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) SEBI (Prohibition of Insider Trading) Regulations, 2015, particularly:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Regulation 3 (Prohibition on dealing, communicating or counselling on matters relating to insider trading);</li>
                        <li>Regulation 9 (Preservation of price sensitive information);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) SEBI (Issue of Capital and Disclosure Requirements) Regulations, 2018 (ICDR Regulations), particularly provisions relating to:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Pre-issue obligations;</li>
                        <li>Disclosure requirements;</li>
                        <li>Investor protection measures;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">1.3.4 INTERNATIONAL COMPLIANCE STANDARDS:</h4>
            <p className="text-slate-600 mb-2">While this Policy is primarily governed by Indian law, the Platform acknowledges and incorporates, to the extent applicable and not inconsistent with Indian law, the following international frameworks:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) General Data Protection Regulation (GDPR) (EU) 2016/679 - for European Economic Area (EEA) Users;</li>
                <li>(b) California Consumer Privacy Act (CCPA), 2018 - for California-based Users;</li>
                <li>(c) ISO/IEC 27001:2013 - Information Security Management Standards;</li>
                <li>(d) ISO/IEC 27701:2019 - Privacy Information Management Standards;</li>
            </ul>
        </div>
      </div>

      {/* 1.4 */}
      <div id="point-1-4" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4">1.4 COMPREHENSIVE DEFINITIONS</h3>
        <p className="text-slate-600 mb-4">For the purposes of this Policy, unless the context otherwise requires, the following terms shall have the meanings ascribed to them hereinbelow:</p>
        
        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">1.4.1 TECHNICAL DEFINITIONS:</h4>
            <div className="bg-slate-50 p-6 rounded-lg border border-slate-200 space-y-4">
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(a) "Cookie"</p>
                    <p className="text-slate-600 text-sm text-justify">means a small text file comprising alphanumeric identifiers, stored on the User's device (computer, mobile device, tablet, or any other electronic device capable of internet connectivity) by the User's web browser at the direction of the Platform's web server, which enables the Platform to recognize the User's device, store User preferences, facilitate session management, and collect information regarding the User's browsing behavior and interaction with the Platform;</p>
                </div>
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(b) "Session Cookie"</p>
                    <p className="text-slate-600 text-sm text-justify">(also referred to as "Transient Cookie" or "Per-Session Cookie") means a temporary cookie that is automatically deleted when the User closes their web browser or terminates the browsing session;</p>
                </div>
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(c) "Persistent Cookie"</p>
                    <p className="text-slate-600 text-sm text-justify">(also referred to as "Permanent Cookie" or "Stored Cookie") means a cookie that remains stored on the User's device for a predetermined period specified in the cookie's expiration parameter, or until manually deleted by the User;</p>
                </div>
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(d) "First-Party Cookie"</p>
                    <p className="text-slate-600 text-sm text-justify">means a cookie set directly by the Platform's domain (preiposip.com) and accessible only by that domain;</p>
                </div>
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(e) "Third-Party Cookie"</p>
                    <p className="text-slate-600 text-sm text-justify">means a cookie set by a domain other than the Platform's domain, typically by third-party service providers, analytics platforms, advertising networks, or other external entities integrated with the Platform;</p>
                </div>
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(f) "Tracking Technology"</p>
                    <p className="text-slate-600 text-sm text-justify">means any technology, mechanism, or methodology employed to collect, monitor, record, or analyze User behavior, including but not limited to cookies, web beacons, pixel tags, local storage objects, mobile device identifiers, fingerprinting technologies, and similar technologies;</p>
                </div>
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(g) "Web Beacon"</p>
                    <p className="text-slate-600 text-sm text-justify">(also referred to as "Pixel Tag", "Clear GIF", or "1×1 GIF") means a small graphic image, typically a transparent single-pixel image, embedded in web pages or emails, which enables the Platform to monitor page views, email opens, and User interactions;</p>
                </div>
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(h) "Local Storage"</p>
                    <p className="text-slate-600 text-sm text-justify">means a web browser feature that allows websites to store data persistently on a User's device, providing greater storage capacity than traditional cookies;</p>
                </div>
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(i) "Device Fingerprinting"</p>
                    <p className="text-slate-600 text-sm text-justify">means a technique of collecting and combining multiple device and browser characteristics (including but not limited to IP address, browser type and version, operating system, screen resolution, installed fonts, plugins, and hardware specifications) to create a unique identifier for tracking purposes;</p>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">1.4.2 REGULATORY AND COMPLIANCE DEFINITIONS:</h4>
            <div className="bg-slate-50 p-6 rounded-lg border border-slate-200 space-y-4">
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(j) "Personal Information" or "Personal Data"</p>
                    <p className="text-slate-600 text-sm text-justify">means any information that relates to a natural person, which, either directly or indirectly, in combination with other information available or likely to be available with the Platform, is capable of identifying such person, and includes but is not limited to:</p>
                    <ul className="list-disc pl-6 text-slate-600 text-sm">
                        <li>Name, age, gender, contact information;</li>
                        <li>Financial information;</li>
                        <li>Biometric information;</li>
                        <li>Passwords and security credentials;</li>
                        <li>Any detail relating to the above as provided to or received by the Platform;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(k) "Sensitive Personal Data or Information" (SPDI)</p>
                    <p className="text-slate-600 text-sm text-justify">means personal information as defined in Rule 3 of the IT Rules, 2011, including:</p>
                    <ul className="list-disc pl-6 text-slate-600 text-sm">
                        <li>Passwords and financial information such as bank account details, credit card details, debit card details, or other payment instrument details;</li>
                        <li>Physical, physiological, and mental health condition;</li>
                        <li>Sexual orientation;</li>
                        <li>Medical records and history;</li>
                        <li>Biometric information;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(l) "Processing"</p>
                    <p className="text-slate-600 text-sm text-justify">means any operation or set of operations performed on Personal Information or sets of Personal Information, whether or not by automated means, including collection, recording, organization, structuring, storage, adaptation, alteration, retrieval, consultation, use, disclosure by transmission, dissemination, alignment, combination, restriction, erasure, or destruction;</p>
                </div>
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(m) "Data Controller"</p>
                    <p className="text-slate-600 text-sm text-justify">means the natural or legal person which, alone or jointly with others, determines the purposes and means of Processing of Personal Information;</p>
                </div>
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(n) "Data Processor"</p>
                    <p className="text-slate-600 text-sm text-justify">means the natural or legal person which Processes Personal Information on behalf of the Data Controller;</p>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">1.4.3 BUSINESS AND TRANSACTIONAL DEFINITIONS:</h4>
            <div className="bg-slate-50 p-6 rounded-lg border border-slate-200 space-y-4">
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(o) "Pre-IPO Securities"</p>
                    <p className="text-slate-600 text-sm text-justify">means unlisted equity shares, convertible instruments, or other securities of private limited companies or unlisted public companies that are proposed to be or are in the process of making an initial public offering;</p>
                </div>
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(p) "Platform Services"</p>
                    <p className="text-slate-600 text-sm text-justify">means the suite of services provided by the Platform, including but not limited to:</p>
                    <ul className="list-disc pl-6 text-slate-600 text-sm">
                        <li>Facilitation of investment opportunities in Pre-IPO Securities;</li>
                        <li>Investment advisory services (subject to applicable SEBI registration);</li>
                        <li>Market research and analytics;</li>
                        <li>Portfolio management information;</li>
                        <li>Due diligence support services;</li>
                        <li>Transaction facilitation and documentation support;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(q) "Qualified Institutional Buyer" (QIB)</p>
                    <p className="text-slate-600 text-sm text-justify">shall have the meaning ascribed under Regulation 2(1)(ss) of the SEBI (ICDR) Regulations, 2018;</p>
                </div>
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(r) "Accredited Investor"</p>
                    <p className="text-slate-600 text-sm text-justify">means an investor satisfying criteria as may be specified by the Platform from time to time, based on net worth, income, investment experience, or professional credentials;</p>
                </div>
                <div>
                    <p className="text-indigo-600 font-mono font-bold mb-1">(s) "KYC Information"</p>
                    <p className="text-slate-600 text-sm text-justify">means information collected for the purpose of verifying the User's identity and complying with the Prevention of Money Laundering Act, 2002, and related regulations, including but not limited to officially valid documents as defined under Rule 2(1)(d) of the PML Rules, 2005;</p>
                </div>
            </div>
        </div>
      </div>

      {/* 1.5 */}
      <div id="point-1-5" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4">1.5 INTERPRETATION CLAUSE</h3>
        
        <p className="text-slate-600 mb-2 font-semibold">1.5.1 In this Policy, unless the context otherwise requires:</p>
        <ul className="list-none pl-4 text-slate-600 space-y-1 mb-4">
            <li>(a) Words denoting the singular shall include the plural and vice versa;</li>
            <li>(b) Words denoting any gender shall include all genders;</li>
            <li>(c) References to "persons" shall include natural persons, bodies corporate, unincorporated associations, partnerships, governments, and governmental agencies;</li>
            <li>(d) Headings and sub-headings are for convenience only and shall not affect the interpretation of this Policy;</li>
            <li>(e) References to statutory provisions shall include such provisions as amended, modified, re-enacted, or consolidated from time to time;</li>
            <li>(f) The words "including," "include," and "includes" shall be deemed to be followed by the phrase "without limitation";</li>
            <li>(g) Any reference to a document is a reference to that document as amended, varied, supplemented, or replaced from time to time;</li>
        </ul>

        <p className="text-slate-600 mb-2 font-semibold">1.5.2 In the event of any conflict or inconsistency between provisions of this Policy and any other agreement or policy governing the User's relationship with the Platform, the following order of precedence shall apply:</p>
        <ul className="list-none pl-4 text-slate-600 space-y-1">
            <li>(a) Applicable mandatory provisions of Indian law and SEBI regulations;</li>
            <li>(b) Express written agreements executed between the Parties;</li>
            <li>(c) Terms of Service;</li>
            <li>(d) This Cookie Policy;</li>
            <li>(e) Other supplementary policies and guidelines;</li>
        </ul>
      </div>

      {/* 1.6 */}
      <div id="point-1-6" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
             <Globe className="text-indigo-600" size={20} /> 1.6 SCOPE OF APPLICATION
        </h3>
        
        <p className="text-slate-600 mb-2">1.6.1 <strong>Territorial Scope:</strong> This Policy applies to all Users accessing the Platform from any jurisdiction, provided that:</p>
        <ul className="list-none pl-4 text-slate-600 space-y-1 mb-4">
            <li>(a) Users accessing the Platform from within the Republic of India shall be subject to Indian law in its entirety;</li>
            <li>(b) Users accessing the Platform from jurisdictions outside India shall be subject to this Policy to the extent not inconsistent with mandatory local laws;</li>
            <li>(c) Where compliance with local laws requires modifications to cookie practices, the Platform shall implement jurisdiction-specific measures as documented in supplementary disclosures;</li>
        </ul>

        <p className="text-slate-600 mb-2">1.6.2 <strong>Material Scope:</strong> This Policy governs:</p>
        <ul className="list-none pl-4 text-slate-600 space-y-1 mb-4">
            <li>(a) All cookies and tracking technologies deployed on the Platform's website, mobile applications, and related digital properties;</li>
            <li>(b) First-party and third-party cookies utilized for Platform operations;</li>
            <li>(c) Data collection, processing, storage, and sharing practices relating to cookie-derived information;</li>
            <li>(d) User rights and mechanisms for exercising control over cookie deployment;</li>
        </ul>

        <p className="text-slate-600 text-justify">
            1.6.3 <strong>Temporal Scope:</strong> This Policy shall remain in effect until superseded by a revised version, with such modifications taking effect upon publication on the Platform, subject to the notice requirements specified in Section 1.8 below.
        </p>
      </div>
    </section>
  );
}
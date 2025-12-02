// components/aml/AmlKycPart1.tsx
'use client';

import React from "react";

export default function AmlKycPart1() {
  return (
    <div>

      {/* HEADER BLOCK */}
      <section id="aml-kyc-header" className="section">
        <div className="section-header">
          <h1 className="section-title">AML & KYC POLICY DOCUMENT</h1>
          <p className="text-muted-foreground">
            For Preiposip.com — Anti-Money Laundering and Know Your Customer Policy Framework
          </p>
        </div>
      </section>

      {/* 1. PREAMBLE */}
      <section id="preamble" className="section">
        <div className="section-header">
          <span className="section-number">01</span>
          <h2 className="section-title">PREAMBLE AND DECLARATION OF INTENT</h2>
        </div>

        <h3>1.1 Constitutional Declaration</h3>
        <p>
          This Anti-Money Laundering and Know Your Customer Policy (hereinafter referred
          to as "the Policy" or "AML/KYC Policy") is adopted by Preiposip.com
          (hereinafter referred to as "the Platform", "the Company", "We", "Us", or "Our")
          in pursuance of its statutory obligations under the Prevention of Money
          Laundering Act, 2002 ("PMLA"), the Prevention of Money Laundering (Maintenance
          of Records) Rules, 2005 ("PML Rules"), and in consonance with the regulatory
          framework established by the Securities and Exchange Board of India ("SEBI"),
          the Ministry of Corporate Affairs ("MCA"), and the Financial Intelligence Unit –
          India ("FIU-IND").
        </p>

        <h3>1.2 Nature and Scope of Business Operations</h3>
        <p>
          The Platform operates as an intermediary facilitating the acquisition, sale,
          and transfer of unlisted equity shares, preference shares, debentures, and other
          securities of companies that have not yet undertaken an Initial Public Offering
          ("Pre-IPO Securities"). Given the inherent complexities, valuation challenges,
          and regulatory sensitivities associated with Pre-IPO markets, the Platform
          recognizes its heightened vulnerability to risks of money laundering, terrorist
          financing, and other financial crimes.
        </p>

        <h3>1.3 Commitment to Regulatory Compliance</h3>
        <ul className="legal-list">
          <li>(a) Maintaining the highest standards of financial integrity and transparency in all its operations;</li>
          <li>(b) Implementing a risk-based approach to customer due diligence commensurate with the nature and sophistication of Pre-IPO transactions;</li>
          <li>(c) Establishing robust mechanisms for detection, prevention, and reporting of suspicious transactions;</li>
          <li>(d) Ensuring full cooperation with all regulatory authorities, law enforcement agencies, and investigative bodies;</li>
          <li>(e) Fostering a culture of compliance throughout the organization through continuous training, monitoring, and review.</li>
        </ul>

        <h3>1.4 Legal Binding Effect</h3>
        <p>This Policy constitutes a binding contractual obligation upon:</p>
        <ul className="legal-list">
          <li>(a) All Users, Investors, Sellers, and Counterparties engaging with the Platform;</li>
          <li>(b) All employees, officers, directors, consultants, and agents of the Company;</li>
          <li>(c) All third-party service providers, intermediaries, and business associates engaged by the Platform.</li>
        </ul>

        <p>
          By accessing, registering upon, or transacting through the Platform, each User
          expressly agrees to be bound by this Policy and acknowledges that compliance
          with AML/KYC requirements is a condition precedent to the utilization of
          Platform services.
        </p>
      </section>

      {/* 2. STATUTORY & REGULATORY FRAMEWORK */}
      <section id="statutory-framework" className="section">
        <div className="section-header">
          <span className="section-number">02</span>
          <h2 className="section-title">STATUTORY AND REGULATORY FRAMEWORK</h2>
        </div>

        <h3>2.1 Primary Legislative Enactments</h3>
        <ul className="legal-list">
          <li>(a) Prevention of Money Laundering Act, 2002 and amendments, including Prevention of Money Laundering (Amendment) Act, 2012;</li>
          <li>(b) Prevention of Money Laundering (Maintenance of Records) Rules, 2005;</li>
          <li>(c) SEBI Act, 1992 and regulations thereunder;</li>
          <li>(d) Companies Act, 2013 regarding beneficial ownership & governance;</li>
          <li>(e) Foreign Exchange Management Act, 1999 (FEMA);</li>
          <li>(f) Income Tax Act, 1961 relating to PAN & reporting obligations;</li>
          <li>(g) Aadhaar Act, 2016 for authentication;</li>
          <li>(h) Information Technology Act, 2000 for digital signatures & e-records.</li>
        </ul>

        <h3>2.2 SEBI Regulatory Framework</h3>
        <ul className="legal-list">
          <li>(a) SEBI ICDR Regulations, 2018;</li>
          <li>(b) SEBI Takeover Regulations, 2011;</li>
          <li>(c) SEBI PIT Regulations, 2015;</li>
          <li>(d) SEBI LODR Regulations, 2015;</li>
          <li>(e) SEBI Circular on PML/CFT;</li>
          <li>(f) SEBI Master Circular on KYC norms & AML standards;</li>
          <li>(g) SEBI Beneficial Ownership & Client Asset Protection guidelines.</li>
        </ul>

        <h3>2.3 FIU-IND Directives</h3>
        <ul className="legal-list">
          <li>(a) STR filing requirements;</li>
          <li>(b) Cash Transaction Reports (CTR);</li>
          <li>(c) Cross-Border Wire Transfer Reports;</li>
          <li>(d) Counterfeit Currency Reports;</li>
          <li>(e) Typology reports & red-flag indicators.</li>
        </ul>

        <h3>2.4 Reserve Bank of India (RBI) Guidelines</h3>
        <ul className="legal-list">
          <li>(a) Master Direction on KYC 2016;</li>
          <li>(b) Guidelines on Risk-Based CDD;</li>
          <li>(c) Enhanced Due Diligence for High-Risk Customers & PEPs.</li>
        </ul>

        <h3>2.5 International Standards & Best Practices</h3>
        <ul className="legal-list">
          <li>(a) FATF Recommendations;</li>
          <li>(b) Wolfsberg AML Principles;</li>
          <li>(c) Basel CDD Guidelines;</li>
          <li>(d) Egmont Group standards;</li>
          <li>(e) UN Conventions against money laundering & terrorism financing.</li>
        </ul>
      </section>

      {/* 3. INTERPRETATION & DEFINITIONS */}
      <section id="interpretation" className="section">
        <div className="section-header">
          <span className="section-number">03</span>
          <h2 className="section-title">INTERPRETATION AND DEFINITIONS</h2>
        </div>

        <h3>3.1 Rules of Interpretation</h3>
        <ul className="legal-list">
          <li>(a) Singular includes plural and vice versa;</li>
          <li>(b) Gender includes all genders;</li>
          <li>(c) Statutory references include amendments and replacements;</li>
          <li>(d) Headings do not affect interpretation;</li>
          <li>(e) Applicable law prevails over this Policy;</li>
          <li>(f) Defined terms are capitalized and binding.</li>
        </ul>

        <h3>3.2 Fundamental Definitions</h3>
        <div className="def-grid" aria-hidden>
          <div className="def-row">
            <div className="def-term">Account</div>
            <div className="def-desc">
              The digital account created by a User for accessing services, conducting transactions, and maintaining records.
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Beneficial Owner</div>
            <div className="def-desc">
              Natural person(s) ultimately owning or controlling a transaction or entity as per Companies Act & PML Rules.
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Beneficial Ownership Declaration</div>
            <div className="def-desc">
              Declaration in Form BEN-2 or analogous declaration required by the Platform.
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Client Due Diligence (CDD)</div>
            <div className="def-desc">
              Process of identifying, verifying Customers, understanding business purpose,
              and monitoring transactions for consistency with Customer profile.
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Competent Authority</div>
            <div className="def-desc">
              Any governmental, regulatory, investigative, or judicial authority having jurisdiction.
            </div>
          </div>
        </div>

        {/* Definitions continued */}
        <div className="def-grid" aria-hidden>
          <div className="def-row">
            <div className="def-term">Designated Director</div>
            <div className="def-desc">
              Director appointed under PMLA responsible for overall AML/KYC compliance.
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Enhanced Due Diligence (EDD)</div>
            <div className="def-desc">
              Heightened scrutiny for High-Risk Customers, PEPs, and unusual transactions.
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">High-Risk Customer</div>
            <div className="def-desc">
              Customer assessed as presenting elevated ML/TF risks.
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">KYC Records</div>
            <div className="def-desc">
              All documents, data, and correspondence obtained during identification, verification & monitoring.
            </div>
          </div>
        </div>

        {/* Continue definitions exactly */}
        <div className="def-grid" aria-hidden>
          <div className="def-row">
            <div className="def-term">Money Laundering</div>
            <div className="def-desc">
              Meaning as per Section 3 of PMLA including concealment, possession, acquisition or use of proceeds of crime.
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Officially Valid Document</div>
            <div className="def-desc">
              Passport, Driving License, PAN, Voter ID, Aadhaar, NREGA Job Card, NPR letter.
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">PAN</div>
            <div className="def-desc">Permanent Account Number issued by Income Tax Department.</div>
          </div>

          <div className="def-row">
            <div className="def-term">PEP</div>
            <div className="def-desc">
              Politically Exposed Person including senior officials, politicians, executives of SOEs & their families.
            </div>
          </div>
        </div>

        <div className="def-grid" aria-hidden>
          <div className="def-row">
            <div className="def-term">Pre-IPO Securities</div>
            <div className="def-desc">
              Unlisted securities of companies that have not undertaken an IPO.
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Principal Officer</div>
            <div className="def-desc">
              Officer designated to file STRs and ensure AML/KYC compliance.
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Proceeds of Crime</div>
            <div className="def-desc">
              Property derived directly or indirectly from criminal activity.
            </div>
          </div>
        </div>
      </section>

      {/* 4. APPLICABILITY & SCOPE */}
      <section id="applicability" className="section">
        <div className="section-header">
          <span className="section-number">04</span>
          <h2 className="section-title">APPLICABILITY AND SCOPE</h2>
        </div>

        <h3>4.1 Universal Applicability</h3>
        <ul className="legal-list">
          <li>(a) All existing/prospective Customers;</li>
          <li>(b) All Company Personnel;</li>
          <li>(c) All third-party intermediaries & agents;</li>
          <li>(d) All transactions through the Platform;</li>
          <li>(e) All current & future products/services.</li>
        </ul>

        <h3>4.2 Territorial Scope</h3>
        <ul className="legal-list">
          <li>(a) All India operations;</li>
          <li>(b) Transactions with Indian residents/entities;</li>
          <li>(c) Cross-border transactions involving India;</li>
          <li>(d) Offshore operations subject to host jurisdiction laws.</li>
        </ul>

        <h3>4.3 Temporal Scope</h3>
        <ul className="legal-list">
          <li>(a) Effective immediately upon Board adoption;</li>
          <li>(b) Applies to all future transactions;</li>
          <li>(c) Requires retrospective compliance for existing Customers;</li>
          <li>(d) Remains in force until superseded.</li>
        </ul>

        <h3>4.4 Exclusions & Exceptions</h3>
        <ul className="legal-list">
          <li>(a) Only where permitted under law;</li>
          <li>(b) With joint written approval of Designated Director & Principal Officer;</li>
          <li>(c) After documented risk assessment;</li>
          <li>(d) Reported to Board within 15 days.</li>
        </ul>

        <h3>4.5 Hierarchy & Precedence</h3>
        <ul className="legal-list">
          <li>(a) Statutory law prevails;</li>
          <li>(b) SEBI regulations prevail over internal rules;</li>
          <li>(c) More stringent provisions prevail;</li>
          <li>(d) This Policy overrides conflicting internal policies.</li>
        </ul>
      </section>

      {/* 5. GOVERNANCE STRUCTURE */}
      <section id="governance" className="section">
        <div className="section-header">
          <span className="section-number">05</span>
          <h2 className="section-title">GOVERNANCE AND ORGANIZATIONAL STRUCTURE</h2>
        </div>

        <h3>5.1 Board of Directors' Responsibility</h3>
        <ul className="legal-list">
          <li>(a) Approving & reviewing this Policy;</li>
          <li>(b) Ensuring adequate resources for AML/KYC compliance;</li>
          <li>(c) Appointing Designated Director & Principal Officer;</li>
          <li>(d) Reviewing compliance & audit findings;</li>
          <li>(e) Establishing culture of compliance.</li>
        </ul>

        <h3>5.2 Designated Director</h3>
        <h4>5.2.1 Appointment and Tenure</h4>
        <ul className="legal-list">
          <li>(a) Must be a Board director;</li>
          <li>(b) Tenure minimum two years;</li>
          <li>(c) Must have AML/KYC expertise;</li>
          <li>(d) Cannot improperly delegate responsibilities.</li>
        </ul>

        <h4>5.2.2 Duties & Responsibilities</h4>
        <ul className="legal-list">
          <li>(a) Ensure compliance with PMLA & this Policy;</li>
          <li>(b) Supervise Principal Officer & compliance team;</li>
          <li>(c) Review high-value/suspicious transactions;</li>
          <li>(d) Liaise with regulators & enforcement agencies;</li>
          <li>(e) Present quarterly reports to Board;</li>
          <li>(f) Approve AML/KYC procedures & controls;</li>
          <li>(g) Ensure training of Personnel.</li>
        </ul>

        <h3>5.3 Principal Officer</h3>
        <h4>5.3.1 Appointment & Qualifications</h4>
        <ul className="legal-list">
          <li>(a) Senior management employee reporting to Designated Director;</li>
          <li>(b) Qualified in law/finance/compliance;</li>
          <li>(c) Minimum 5 years experience;</li>
          <li>(d) No conflict-of-interest roles.</li>
        </ul>

        <h4>5.3.2 Core Responsibilities</h4>
        <ul className="legal-list">
          <li>(a) Central point for AML/KYC matters;</li>
          <li>(b) Daily transaction monitoring;</li>
          <li>(c) Filing STRs timely;</li>
          <li>(d) Maintain PMLA-mandated records;</li>
          <li>(e) Respond to regulatory authority queries;</li>
          <li>(f) Conduct risk assessments;</li>
          <li>(g) Ensure KYC updates;</li>
          <li>(h) Coordinate audits;</li>
          <li>(i) Maintain STR confidentiality;</li>
          <li>(j) Develop red-flag indicators.</li>
        </ul>

        <h3>5.4 Compliance Department Structure</h3>
        <ul className="legal-list">
          <li>(a) KYC Team;</li>
          <li>(b) Transaction Monitoring Team;</li>
          <li>(c) Reporting Team;</li>
          <li>(d) Training & Awareness Team;</li>
          <li>(e) Internal Audit Team.</li>
        </ul>

      </section>
    </div>
  );
}

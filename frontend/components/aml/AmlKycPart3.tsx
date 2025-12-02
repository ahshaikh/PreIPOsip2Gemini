// components/aml/AmlKycPart3.tsx
'use client';

import React from "react";

export default function AmlKycPart3() {
  return (
    <div>

      {/* 6.4 CUSTOMER DUE DILIGENCE */}
      <section id="customer-due-diligence" className="section">
        <div className="section-header">
          <span className="section-number">06.4</span>
          <h2 className="section-title">CUSTOMER DUE DILIGENCE (CDD) - STANDARD PROCEDURES</h2>
        </div>

        <h3>6.4.1 Purpose and Scope of CDD</h3>
        <p>Customer Due Diligence extends beyond mere identification and involves:</p>

        <ul className="legal-list">
          <li>(a) Understanding the nature and purpose of the business relationship;</li>
          <li>(b) Obtaining information on the intended nature and volume of transactions;</li>
          <li>(c) Assessing the legitimacy and source of funds/wealth;</li>
          <li>(d) Determining the risk profile of the Customer;</li>
          <li>(e) Establishing a baseline for ongoing monitoring;</li>
          <li>(f) Ensuring economic rationale for the relationship and transactions.</li>
        </ul>

        <h3>6.4.2 Timing of CDD</h3>
        <p>CDD shall be conducted:</p>

        <ul className="legal-list">
          <li>(a) Before establishing business relationship;</li>
          <li>(b) For occasional transactions above INR 10,00,000 (or foreign currency equivalent);</li>
          <li>(c) When there is suspicion of money laundering or terrorist financing;</li>
          <li>(d) When doubts arise about previously obtained customer data;</li>
          <li>(e) Periodically for existing customers per Section 6.6.</li>
        </ul>

        <h3>6.4.3 Standard CDD Questionnaire</h3>

        <h4>(a) For Individual Customers:</h4>

        <h5>Financial Profile:</h5>
        <ul className="legal-list">
          <li>Annual gross income bracket</li>
          <li>Net worth</li>
          <li>Existing investment portfolio</li>
          <li>Outstanding liabilities</li>
          <li>Bank relationships</li>
        </ul>

        <h5>Transaction Profile:</h5>
        <ul className="legal-list">
          <li>Expected transaction frequency</li>
          <li>Expected value range</li>
          <li>Expected annual cumulative value</li>
          <li>Types of Pre-IPO Securities of interest</li>
          <li>Investment horizon</li>
        </ul>

        <h5>Source of Funds Declaration:</h5>
        <ul className="legal-list">
          <li>Primary source: Salary / Business / Inheritance / Gift / Sale of assets / Loan / Other</li>
          <li>Business income → nature & years of operation</li>
          <li>Inheritance/gift → donor/deceased details</li>
          <li>Sale proceeds → asset type & documentation</li>
          <li>Loan → lender details & purpose</li>
          <li>Documentary evidence required</li>
        </ul>

        <h5>Source of Wealth Statement:</h5>
        <ul className="legal-list">
          <li>Wealth accumulation history</li>
          <li>Major assets with acquisition timeline</li>
          <li>Career/business progression explaining wealth</li>
          <li>Inherited wealth details</li>
        </ul>

        <h5>Purpose of Account:</h5>
        <ul className="legal-list">
          <li>Investment objectives</li>
          <li>Experience with unlisted securities</li>
          <li>Risk appetite assessment</li>
          <li>Expected holding period</li>
        </ul>

        <h5>Related Parties:</h5>
        <ul className="legal-list">
          <li>Family members using Platform</li>
          <li>Business associates using Platform</li>
          <li>Connections to issuer companies</li>
        </ul>

        <h4>(b) For Body Corporate:</h4>

        <h5>Business Profile:</h5>
        <ul className="legal-list">
          <li>Nature of business</li>
          <li>Years of operation</li>
          <li>Major customers & suppliers</li>
          <li>Geographical markets</li>
          <li>Employee strength</li>
          <li>Credit rating (if any)</li>
        </ul>

        <h5>Financial Standing:</h5>
        <ul className="legal-list">
          <li>Paid-up capital</li>
          <li>Borrowings</li>
          <li>Debt–equity ratio</li>
          <li>Liquidity position</li>
          <li>Bank credit limits</li>
        </ul>

        <h5>Transaction Profile:</h5>
        <ul className="legal-list">
          <li>Purpose of investment</li>
          <li>Company investment policy</li>
          <li>Board-approved limits</li>
          <li>Expected volume & frequency</li>
        </ul>

        <h5>Source of Funds:</h5>
        <ul className="legal-list">
          <li>Internal accruals</li>
          <li>Borrowings with lender consent</li>
          <li>Capital infusion</li>
          <li>Sale of assets/investments</li>
          <li>Documentary proof</li>
        </ul>

        <h5>Regulatory Compliance:</h5>
        <ul className="legal-list">
          <li>Regulated entity status</li>
          <li>Sectoral compliance</li>
          <li>Past penalties</li>
          <li>Audit qualifications</li>
        </ul>

        <h5>Related Party Transactions:</h5>
        <ul className="legal-list">
          <li>Common directors/shareholders with issuers</li>
          <li>Group affiliations</li>
          <li>Business relationships with issuers</li>
        </ul>

        <h3>6.4.4 CDD Assessment and Risk Categorization</h3>

        <ul className="legal-list">
          <li>(a) Assess completeness & consistency;</li>
          <li>(b) Verify information independently where possible;</li>
          <li>(c) Assign risk rating (Low/Medium/High);</li>
          <li>(d) Determine additional diligence based on rating;</li>
          <li>(e) Obtain necessary approvals:
            <ul>
              <li>Medium Risk → Principal Officer</li>
              <li>High Risk → Designated Director</li>
            </ul>
          </li>
        </ul>

        <h3>6.4.5 Adverse Media and Sanctions Screening</h3>

        <h4>(a) Adverse Media Screening:</h4>
        <ul className="legal-list">
          <li>Public database searches</li>
          <li>Media archive searches</li>
          <li>Litigation database review</li>
          <li>Materiality assessment</li>
          <li>Escalation for high-risk findings</li>
        </ul>

        <h4>(b) Sanctions and Watchlist Screening:</h4>
        <ul className="legal-list">
          <li>UNSC list</li>
          <li>OFAC SDN list</li>
          <li>EU sanctions list</li>
          <li>MHA banned organizations</li>
          <li>RBI/SEBI debarred list</li>
          <li>False positives documented</li>
          <li>True matches → rejection + report to FIU-IND</li>
        </ul>

        <h4>(c) Screening Tools:</h4>
        <ul className="legal-list">
          <li>Automated screening using commercial databases</li>
          <li>Manual screening as secondary</li>
          <li>Annual (or more frequent) re-screening</li>
        </ul>

        <h3>6.4.6 IP Address and Device Fingerprinting</h3>

        <ul className="legal-list">
          <li>(a) Capture IP address</li>
          <li>(b) Device fingerprinting</li>
          <li>(c) Flag shared IP/devices</li>
          <li>(d) Geolocation checks</li>
          <li>(e) Flag VPN/proxy usage</li>
          <li>(f) Retain logs for audit</li>
        </ul>

        <h3>6.4.7 Negative Consent and Declining Customers</h3>
        <p>The Platform may decline Customers where:</p>

        <ul className="legal-list">
          <li>(a) Information incomplete;</li>
          <li>(b) Information inconsistent;</li>
          <li>(c) Source of funds unsatisfactory;</li>
          <li>(d) Adverse media present;</li>
          <li>(e) High-risk beyond Platform appetite;</li>
          <li>(f) Suspicious circumstances exist;</li>
          <li>(g) Regulatory restrictions apply;</li>
          <li>(h) Service provision violates law.</li>
        </ul>

        <p>Additional provisions:</p>

        <ul className="legal-list">
          <li>No tipping-off</li>
          <li>No appeal except mistaken identity</li>
          <li>Incident recorded internally</li>
          <li>STR filed where required</li>
        </ul>

        {/* 6.5 ENHANCED DUE DILIGENCE */}
        <h2 className="section-title">6.5 Enhanced Due Diligence (EDD) - High-Risk Customers</h2>

        <h3>6.5.1 Triggering Factors for EDD</h3>
        <ul className="legal-list">
          <li>(a) PEP or related persons;</li>
          <li>(b) High-risk jurisdictions;</li>
          <li>(c) High-risk business activities;</li>
          <li>(d) Large or unusual transactions;</li>
          <li>(e) Complex or opaque corporate structures;</li>
          <li>(f) Adverse media findings;</li>
          <li>(g) Non-face-to-face customers;</li>
          <li>(h) Introduced customers;</li>
          <li>(i) Trusts/foundations with opaque ownership;</li>
          <li>(j) Cash-intensive operations;</li>
          <li>(k) Reluctance to provide information.</li>
        </ul>

        <h3>6.5.2 EDD Measures – Individual Customers</h3>

        <h4>(a) Senior Management Approval:</h4>
        <ul className="legal-list">
          <li>Designated Director approval required</li>
          <li>Justification documented</li>
          <li>Periodic senior review</li>
        </ul>

        <h4>(b) Enhanced Documentation:</h4>
        <ul className="legal-list">
          <li>Certified copies of all documents</li>
          <li>Multiple address proofs</li>
          <li>Bank reference letter</li>
          <li>Employer/business verification</li>
          <li>Wealth and tax documents including:
            <ul>
              <li>3 years ITR</li>
              <li>Form 26AS</li>
              <li>12 months bank statements</li>
              <li>Investment statements</li>
              <li>Property documents</li>
              <li>Gift deeds / wills</li>
            </ul>
          </li>
        </ul>

        <h4>(c) Source of Funds Deep Dive:</h4>
        <ul className="legal-list">
          <li>Independent verification</li>
          <li>Fund-flow trail</li>
          <li>Business financials (if applicable)</li>
          <li>Salary proofs</li>
          <li>Sale documents</li>
          <li>Investigate inconsistencies</li>
        </ul>

        <h4>(d) In-Person Verification:</h4>
        <ul className="legal-list">
          <li>Mandatory physical meeting</li>
          <li>Video recording</li>
          <li>Premises verification</li>
        </ul>

        <h4>(e) Enhanced Background Checks:</h4>
        <ul className="legal-list">
          <li>Internet research</li>
          <li>Social media review</li>
          <li>Employment verification</li>
          <li>Reference checks</li>
          <li>Court record review</li>
          <li>Credit bureau (with consent)</li>
        </ul>

        <h4>(f) Purpose and Nature of Relationship:</h4>
        <ul className="legal-list">
          <li>Detailed purpose analysis</li>
          <li>Risk suitability checks</li>
          <li>Consistency verification</li>
        </ul>

        <h4>(g) Ongoing Monitoring:</h4>
        <ul className="legal-list">
          <li>Enhanced monitoring frequency</li>
          <li>Quarterly review</li>
          <li>Annual KYC refresh</li>
        </ul>

        {/* Continue PEP section */}
        <h3>6.5.3 EDD Measures – Politically Exposed Persons (PEPs)</h3>

        <h4>(a) PEP Identification:</h4>
        <ul className="legal-list">
          <li>Self-declaration</li>
          <li>Database screening</li>
          <li>Classification: Foreign/Domestic/International PEP</li>
          <li>Family members & close associates identification</li>
        </ul>

        <h4>(b) Additional Documentation:</h4>
        <ul className="legal-list">
          <li>Declaration of public positions held</li>
          <li>Business interests and directorships</li>
          <li>Family members details</li>
          <li>Asset declarations</li>
        </ul>

        <h4>(c) Source of Wealth Scrutiny:</h4>
        <ul className="legal-list">
          <li>Wealth timeline analysis</li>
          <li>Legitimacy verification</li>
          <li>Red flags thoroughly examined</li>
        </ul>

        <h4>(d) Ongoing Monitoring:</h4>
        <ul className="legal-list">
          <li>Continuous monitoring</li>
          <li>Review for conflicts/corruption risks</li>
          <li>News monitoring</li>
          <li>Quarterly review</li>
        </ul>

        <h4>(e) Approval:</h4>
        <ul className="legal-list">
          <li>Designated Director approval</li>
          <li>Annual re-approval</li>
        </ul>

        {/* 6.5.4 Body Corporate EDD */}
        <h3>6.5.4 EDD Measures – Body Corporate</h3>

        <h4>(a) Corporate Structure Analysis:</h4>
        <ul className="legal-list">
          <li>Organizational chart</li>
          <li>Rationale for structure</li>
          <li>Ownership tracing</li>
          <li>Offshore entities justification</li>
        </ul>

        <h4>(b) Beneficial Ownership Deep Dive:</h4>
        <ul className="legal-list">
          <li>Tracing to natural persons</li>
          <li>Voting & control rights</li>
          <li>Nominee arrangements</li>
          <li>Independent verification</li>
        </ul>

        <h4>(c) Business Verification:</h4>
        <ul className="legal-list">
          <li>Premises verification</li>
          <li>GST & turnover verification</li>
          <li>Supplier/customer confirmations</li>
          <li>Industry validation</li>
        </ul>

        <h4>(d) Financial Deep Dive:</h4>
        <ul className="legal-list">
          <li>3-year audited financials</li>
          <li>12-month bank statements</li>
          <li>GST returns</li>
          <li>Tax returns</li>
          <li>Anomaly investigation</li>
        </ul>

        <h4>(e) Related Party Scrutiny:</h4>
        <ul className="legal-list">
          <li>Identify all related parties</li>
          <li>Review RPT disclosures</li>
          <li>Conflict of interest evaluation</li>
        </ul>

        <h4>(f) Director and Promoter Due Diligence:</h4>
        <ul className="legal-list">
          <li>KYC of all directors</li>
          <li>Adverse media screening</li>
          <li>DIN status verification</li>
          <li>Promoter background checks</li>
        </ul>

        <h4>(g) Regulatory Standing:</h4>
        <ul className="legal-list">
          <li>Compliance status</li>
          <li>Default/penalty checks</li>
          <li>License verification</li>
        </ul>

        {/* 6.5.5 High-Risk Jurisdictions */}
        <h3>6.5.5 EDD Measures – High-Risk Jurisdictions</h3>

        <ul className="legal-list">
          <li>(a) Enhanced scrutiny of source of funds;</li>
          <li>(b) Business rationale evaluation;</li>
          <li>(c) Ensure funds are not from sanctioned entities;</li>
          <li>(d) FEMA compliance;</li>
          <li>(e) Senior management approval;</li>
          <li>(f) Continuous FATF update monitoring.</li>
        </ul>

        <h4>Current High-Risk Jurisdictions:</h4>
        <ul className="legal-list">
          <li>North Korea</li>
          <li>Iran</li>
          <li>Myanmar</li>
          <li>Any FATF countermeasure jurisdiction</li>
        </ul>

        <h4>Grey List (Enhanced Monitoring):</h4>
        <p>Platform maintains updated list as per FATF public statements.</p>

        {/* 6.5.6 Documentation */}
        <h3>6.5.6 EDD Documentation and Record-Keeping</h3>

        <ul className="legal-list">
          <li>(a) Dedicated EDD file per high-risk customer;</li>
          <li>(b) Retention of all assessments & approvals;</li>
          <li>(c) Risk documentation rationale;</li>
          <li>(d) Senior management sign-off;</li>
          <li>(e) Monitoring reports;</li>
          <li>(f) STR documentation;</li>
          <li>(g) 10-year retention.</li>
        </ul>

        {/* 6.6 PERIODIC UPDATION */}
        <h2 className="section-title">6.6 Periodic Updation and Ongoing Due Diligence</h2>

        <h3>6.6.1 Frequency of KYC Refresh</h3>

        <h4>(a) Low-Risk Customers:</h4>
        <ul className="legal-list">
          <li>Individuals: every 10 years</li>
          <li>Body corporate: every 3 years</li>
        </ul>

        <h4>(b) Medium-Risk Customers:</h4>
        <ul className="legal-list">
          <li>Individuals: every 8 years</li>
          <li>Body corporate: every 2 years</li>
        </ul>

        <h4>(c) High-Risk Customers:</h4>
        <ul className="legal-list">
          <li>Individuals: every 2 years</li>
          <li>Body corporate: annually</li>
        </ul>

        <h4>(d) PEPs:</h4>
        <ul className="legal-list">
          <li>Annual refresh</li>
        </ul>

        <h4>(e) Trigger-Based Updates:</h4>
        <ul className="legal-list">
          <li>Changes in contact details</li>
          <li>Changes in beneficial ownership</li>
          <li>Change in transaction pattern</li>
          <li>Regulatory changes</li>
        </ul>

        <h3>6.6.2 KYC Update Process</h3>

        <h4>(a) Proactive Outreach:</h4>
        <ul className="legal-list">
          <li>Contact 60 days before due date</li>
          <li>Email/SMS/notification</li>
        </ul>

        <h4>(b) Documentation:</h4>
        <ul className="legal-list">
          <li>Updated identity/address proofs</li>
          <li>Updated financial profile</li>
          <li>Beneficial ownership reconfirmation</li>
          <li>Updated SoF/SoW</li>
          <li>Self-declaration</li>
        </ul>

        <h4>(c) Verification:</h4>
        <ul className="legal-list">
          <li>Document verification</li>
          <li>PEP & media rescreening</li>
          <li>Risk profile reassessment</li>
        </ul>

        <h4>(d) Non-Compliance Consequences:</h4>
        <ul className="legal-list">
          <li>Reminder</li>
          <li>30 days → restrictions</li>
          <li>60 days → suspension</li>
          <li>90 days → closure proceedings</li>
        </ul>

        <h3>6.6.3 Ongoing Monitoring</h3>

        <ul className="legal-list">
          <li>(a) Real-time transaction monitoring</li>
          <li>(b) Quarterly review (High-Risk & PEP)</li>
          <li>(c) Annual review (Medium-Risk)</li>
          <li>(d) Regular sanctions/PEP rescreening</li>
          <li>(e) Negative news monitoring</li>
          <li>(f) Pattern analysis</li>
          <li>(g) Investigation of anomalies</li>
        </ul>

      </section>

    </div>
  );
}

// components/aml/AmlKycPart2.tsx
'use client';

import React from "react";

export default function AmlKycPart2() {
  return (
    <div>

      {/* 6. CUSTOMER IDENTIFICATION PROGRAM (CIP) */}
      <section id="customer-identification-program" className="section">
        <div className="section-header">
          <span className="section-number">06</span>
          <h2 className="section-title">CUSTOMER IDENTIFICATION PROGRAM (CIP)</h2>
        </div>

        <h3>6.1 Foundational Principles of Customer Identification</h3>

        <h4>6.1.1 Mandatory Nature</h4>
        <p>
          Customer identification is an absolute precondition to establishing any business
          relationship with the Platform. No User shall be permitted to:
        </p>

        <ul className="legal-list">
          <li>(a) Register an Account on the Platform;</li>
          <li>(b) Browse or access securities listings or transaction interfaces;</li>
          <li>(c) Initiate, execute, or complete any Transaction;</li>
          <li>(d) Transfer funds to or through the Platform;</li>
          <li>(e) Communicate transaction intent or execute binding agreements;</li>
        </ul>

        <p>
          without first completing the Customer Identification Program to the entire satisfaction
          of the Principal Officer.
        </p>

        <h4>6.1.2 Risk-Based Differentiation</h4>
        <p>The intensity, depth, and frequency of customer identification measures shall be calibrated according to:</p>

        <ul className="legal-list">
          <li>(a) Customer category (individual, body corporate, trust, unincorporated association);</li>
          <li>(b) Risk profile (low-risk, medium-risk, high-risk as determined by risk assessment matrix);</li>
          <li>(c) Nature and value of anticipated transactions;</li>
          <li>(d) Geographical location and jurisdiction of Customer;</li>
          <li>(e) Products and services to be utilized;</li>
          <li>(f) Delivery channels employed (direct digital onboarding, assisted onboarding, etc.).</li>
        </ul>

        <h4>6.1.3 Non-Discrimination Principle</h4>
        <p>The Platform shall not discriminate on the basis of:</p>

        <ul className="legal-list">
          <li>(a) Religion, caste, race, sex, gender identity, or place of birth;</li>
          <li>(b) Disability or medical condition;</li>
          <li>(c) Economic status or nature of occupation;</li>
        </ul>

        <p>
          provided that legitimate risk-based differentiation for compliance purposes shall not
          constitute discrimination.
        </p>

        {/* 6.2 Individual Natural Persons */}
        <h3>6.2 Customer Identification – Individual Natural Persons</h3>

        <h4>6.2.1 Mandatory Information Collection</h4>

        <h5>(a) Personal Identification Data:</h5>
        <ul className="legal-list">
          <li>Full legal name as appearing on Officially Valid Document (OVD)</li>
          <li>Date of birth</li>
          <li>Father's/Spouse's name</li>
          <li>Gender</li>
          <li>Nationality and citizenship status</li>
          <li>Residential status under FEMA (Resident Indian, NRI, PIO, OCI, Foreign National)</li>
        </ul>

        <h5>(b) Contact Information:</h5>
        <ul className="legal-list">
          <li>Current residential address with proof</li>
          <li>Permanent address (if different)</li>
          <li>Correspondence address (if different)</li>
          <li>At least one Indian mobile number (for residents)</li>
          <li>Email address</li>
          <li>Landline number (optional)</li>
        </ul>

        <h5>(c) Financial and Tax Information:</h5>
        <ul className="legal-list">
          <li>PAN — MANDATORY for all Customers</li>
          <li>Aadhaar (voluntary but recommended)</li>
          <li>Bank account details (bank, branch, IFSC, account no., type)</li>
          <li>Annual income range</li>
          <li>Net worth declaration (if transactions exceed INR 25,00,000)</li>
          <li>Source of funds and source of wealth</li>
        </ul>

        <h5>(d) Occupational Information:</h5>
        <ul className="legal-list">
          <li>Occupation/profession</li>
          <li>Employer/business details</li>
          <li>Nature of business/industry</li>
          <li>Designation and years of service</li>
          <li>Official contact details</li>
        </ul>

        <h5>(e) Purpose of Account:</h5>
        <ul className="legal-list">
          <li>Intended nature/frequency of transactions</li>
          <li>Expected annual volume</li>
          <li>Investment objectives</li>
          <li>Experience with unlisted securities</li>
        </ul>

        <h4>6.2.2 Document Collection and Verification</h4>

        <h5>(a) Identity Proof – ANY ONE OVD:</h5>
        <ul className="legal-list">
          <li>Passport (all pages, valid or expired up to 3 years)</li>
          <li>Driving License</li>
          <li>PAN Card or e-PAN</li>
          <li>Voter ID Card</li>
          <li>Aadhaar (masked number preferred)</li>
          <li>NREGA Job Card</li>
          <li>Letter from National Population Register</li>
        </ul>

        <h5>Document Quality Standards:</h5>
        <ul className="legal-list">
          <li>Clear, legible, unaltered copies</li>
          <li>All corners must be visible</li>
          <li>Color copies preferred</li>
          <li>No laminated documents unless verified</li>
          <li>Expired documents acceptable in limited cases</li>
        </ul>

        <h5>(b) Address Proof – ANY ONE of the following:</h5>
        <ul className="legal-list">
          <li>Passport, Driving License, Aadhaar, Voter ID (if showing current address)</li>
          <li>Utility bills (not older than 3 months)</li>
          <li>Bank statement (not older than 3 months)</li>
          <li>Registered rent agreement</li>
          <li>Employer letter</li>
          <li>Property documents</li>
          <li>Municipal tax receipt (≤1 year old)</li>
          <li>Pension documents with address</li>
        </ul>

        <h5>Special Provision for Address Mismatch:</h5>
        <ul className="legal-list">
          <li>Recent proof of current address</li>
          <li>Self-declaration for change of address</li>
          <li>Previous address proof if staying &lt; 3 months at current address</li>
        </ul>

        <h5>(c) Photograph:</h5>
        <ul className="legal-list">
          <li>Recent passport-style photograph</li>
          <li>Clear facial features</li>
          <li>White/light background</li>
          <li>Live-capture required for digital onboarding</li>
        </ul>

        <h5>(d) PAN Card – MANDATORY:</h5>
        <ul className="legal-list">
          <li>PAN physical copy or e-PAN</li>
          <li>PAN verification through ITD API</li>
          <li>PAN–Aadhaar linking compliance</li>
          <li>Transactions halted if PAN fails authentication</li>
        </ul>

        <h5>(e) Aadhaar (Voluntary Authentication):</h5>
        <ul className="legal-list">
          <li>Offline Aadhaar XML with masked number</li>
          <li>DigiLocker Aadhaar fetch</li>
          <li>VID accepted</li>
          <li>OTP-based authentication</li>
          <li>Biometric optional for enhanced verification</li>
        </ul>

        {/* 6.2.3 e-KYC */}
        <h4>6.2.3 Electronic/Digital Verification (e-KYC)</h4>

        <h5>(a) Video-based Customer Identification Process (V-CIP):</h5>
        <ul className="legal-list">
          <li>Live video call with KYC official</li>
          <li>Geo-tagging</li>
          <li>Real-time document display</li>
          <li>Liveness detection</li>
          <li>Recording stored for 10 years</li>
          <li>Live photograph captured</li>
        </ul>

        <h5>(b) Aadhaar-based e-KYC:</h5>
        <ul className="legal-list">
          <li>OTP authentication</li>
          <li>UIDAI demographic verification</li>
          <li>Biometric optional</li>
          <li>Timestamp and audit trail maintained</li>
        </ul>

        <h5>(c) DigiLocker Integration:</h5>
        <ul className="legal-list">
          <li>Fetch OVDs with customer authorization</li>
          <li>Validate authenticity</li>
          <li>Maintain audit trail</li>
        </ul>

        <h5>(d) CKYCR Integration:</h5>
        <ul className="legal-list">
          <li>Search by PAN/Aadhaar</li>
          <li>Download historical KYC</li>
          <li>Validate and update as needed</li>
        </ul>

        {/* 6.2.4 IPV */}
        <h4>6.2.4 In-Person Verification (IPV)</h4>

        <ul className="legal-list">
          <li>(a) Physical meeting with authorized official</li>
          <li>(b) Verification of originals</li>
          <li>(c) Match live appearance with documents</li>
          <li>(d) Biometric capture if required</li>
          <li>(e) IPV certificate of date/time/location</li>
          <li>(f) Photographic evidence retained</li>
        </ul>

        {/* 6.2.5 Special Categories */}
        <h4>6.2.5 Special Categories – Individual Customers</h4>

        <h5>(a) Non-Resident Indians (NRIs):</h5>
        <ul className="legal-list">
          <li>Overseas address proof</li>
          <li>Visa/work permit/residence permit</li>
          <li>OCI/PIO card</li>
          <li>FEMA declarations</li>
          <li>LRS compliance</li>
        </ul>

        <h5>(b) Politically Exposed Persons (PEPs):</h5>
        <ul className="legal-list">
          <li>Self-declaration</li>
          <li>Screening against PEP databases</li>
          <li>Verification of public positions</li>
          <li>Family and close associate identification</li>
          <li>EDD documentation</li>
        </ul>

        <h5>(c) Minors:</h5>
        <ul className="legal-list">
          <li>Operated by guardian</li>
          <li>Birth certificate</li>
          <li>Guardian complete KYC</li>
          <li>Indemnity and restrictions</li>
          <li>Fresh KYC at majority</li>
        </ul>

        {/* 6.3 Body Corporate and Legal Entities */}
        <h3>6.3 Customer Identification – Body Corporate and Legal Entities</h3>

        <h4>6.3.1 Companies (Private and Public Limited)</h4>

        <h5>(a) Corporate Identity Documents – ALL required:</h5>
        <ul className="legal-list">
          <li>Certificate of Incorporation (RoC)</li>
          <li>MoA and AoA – certified copies</li>
          <li>CIN verification</li>
          <li>PAN (mandatory)</li>
          <li>GSTIN (if applicable)</li>
          <li>Board Resolution (within 90 days)</li>
          <li>List of Directors (DIR-12 / MGT-7)</li>
          <li>DIN for all directors</li>
          <li>Shareholding pattern</li>
          <li>Audited financials (2 years or projected)</li>
        </ul>

        <h5>(b) Authorized Signatories:</h5>
        <ul className="legal-list">
          <li>Complete individual KYC</li>
          <li>Specimen signatures</li>
          <li>Certified Board Resolution / POA</li>
        </ul>

        <h5>(c) Beneficial Ownership – CRITICAL:</h5>

        <h6>Step 1: Identify SBOs</h6>
        <ul className="legal-list">
          <li>Individuals holding ≥10% shares/voting/dividend rights</li>
          <li>Those exercising significant influence/control</li>
        </ul>

        <h6>Step 2: Documentation</h6>
        <ul className="legal-list">
          <li>Form BEN-2 declaration</li>
          <li>Ownership structure chart</li>
          <li>Individual KYC of SBOs</li>
        </ul>

        <h6>Step 3: Ultimate Beneficial Owners (UBOs)</h6>
        <ul className="legal-list">
          <li>Trace ownership chain to natural persons</li>
          <li>Document indirect holdings & control rights</li>
        </ul>

        <h6>Step 4: Corporate Structure Verification</h6>
        <ul className="legal-list">
          <li>Parent/subsidiary/associate charts</li>
          <li>Offshore entities with jurisdiction details</li>
          <li>Nominee shareholders → full BO declaration</li>
        </ul>

        <h5>(d) Office Address Verification:</h5>
        <ul className="legal-list">
          <li>Utility bills ≤3 months old</li>
          <li>Rent agreement/property documents</li>
          <li>RoC registered office certificate</li>
        </ul>

        <h5>(e) Source of Funds:</h5>
        <ul className="legal-list">
          <li>6-month bank statements</li>
          <li>Loan documents (if applicable)</li>
          <li>Promoter contribution source evidence</li>
        </ul>

        {/* 6.3.2 Partnerships */}
        <h4>6.3.2 Partnership Firms</h4>

        <h5>(a) Partnership Documentation:</h5>
        <ul className="legal-list">
          <li>Registered Partnership Deed</li>
          <li>PAN (mandatory)</li>
          <li>GSTIN (if applicable)</li>
          <li>List of partners & profit-sharing</li>
          <li>Resolution/consent for account opening</li>
          <li>Bank statement (6 months)</li>
          <li>Specimen signatures</li>
        </ul>

        <h5>(b) Beneficial Ownership:</h5>
        <ul className="legal-list">
          <li>Partners with ≥15% share → full KYC</li>
          <li>No partner ≥15% → senior managing partner</li>
        </ul>

        <h5>(c) Address Proof:</h5>
        <ul className="legal-list">
          <li>Utility bills</li>
          <li>Rent agreement</li>
          <li>Shop/establishment license</li>
        </ul>

        {/* 6.3.3 LLPs */}
        <h4>6.3.3 Limited Liability Partnerships (LLPs)</h4>

        <h5>(a) LLP Documentation:</h5>
        <ul className="legal-list">
          <li>Certificate of Incorporation with LLPIN</li>
          <li>LLP Agreement (certified)</li>
          <li>PAN (mandatory)</li>
          <li>GSTIN (if applicable)</li>
          <li>List of designated partners (Form 4)</li>
          <li>DPIN for designated partners</li>
          <li>Authorization resolution</li>
          <li>Annual Return – Form 11</li>
        </ul>

        <h5>(b) Beneficial Ownership:</h5>
        <ul className="legal-list">
          <li>Partners with ≥15% contribution → full KYC</li>
          <li>All designated partners → full KYC</li>
          <li>No partner ≥15% → senior managing official</li>
        </ul>

        {/* 6.3.4 Trusts */}
        <h4>6.3.4 Trusts (Private and Public)</h4>

        <h5>(a) Trust Documentation:</h5>
        <ul className="legal-list">
          <li>Registered Trust Deed</li>
          <li>Registration Certificate</li>
          <li>Section 12A/80G registration (if applicable)</li>
          <li>PAN (mandatory)</li>
          <li>List of trustees with KYC</li>
          <li>List of settlors & beneficiaries</li>
          <li>Trustee resolution/POA</li>
          <li>Audited accounts (for public trusts)</li>
        </ul>

        <h5>(b) Beneficial Ownership:</h5>
        <ul className="legal-list">
          <li>Private trusts → settlor, trustees, beneficiaries</li>
          <li>Public trusts → trustees</li>
          <li>Complex trusts → natural persons exercising control</li>
        </ul>

        <h5>(c) Address Proof:</h5>
        <ul className="legal-list">
          <li>Utility bills</li>
          <li>Property documents</li>
        </ul>

        {/* 6.3.5 Unincorporated Associations */}
        <h4>6.3.5 Unincorporated Associations and Bodies of Individuals</h4>

        <h5>(a) Documentation:</h5>
        <ul className="legal-list">
          <li>Constitution/Bye-laws</li>
          <li>Registration certificate</li>
          <li>List of managing committee</li>
          <li>KYC of President, Secretary, Treasurer</li>
          <li>Resolution for account opening</li>
          <li>PAN (mandatory)</li>
        </ul>

        <h5>(b) Beneficial Ownership:</h5>
        <ul className="legal-list">
          <li>Key office bearers deemed beneficial owners</li>
        </ul>

        {/* 6.3.6 SPVs & Pooled Vehicles */}
        <h4>6.3.6 Special Purpose Vehicles (SPVs) & Pooled Investment Vehicles</h4>

        <h5>(a) Entity-Level Documentation:</h5>
        <ul className="legal-list">
          <li>All applicable Company/LLP/Trust documents</li>
          <li>SEBI registration certificate (if AIF/VCF)</li>
          <li>Pvt Placement Memorandum</li>
          <li>Subscription agreements</li>
        </ul>

        <h5>(b) Investor-Level Documentation:</h5>
        <ul className="legal-list">
          <li>List of investors with percentage holdings</li>
          <li>KYC for investors with ≥10% holdings</li>
          <li>Minimum KYC for others</li>
        </ul>

        <h5>(c) Fund Manager/Sponsor Documentation:</h5>
        <ul className="legal-list">
          <li>Identity of fund manager</li>
          <li>Full KYC of management company</li>
          <li>Beneficial ownership of manager entity</li>
        </ul>

        <h5>(d) Source of Funds:</h5>
        <ul className="legal-list">
          <li>Bank statements</li>
          <li>Proof of investor contributions</li>
          <li>Investment confirmations</li>
        </ul>

      </section>
    </div>
  );
}

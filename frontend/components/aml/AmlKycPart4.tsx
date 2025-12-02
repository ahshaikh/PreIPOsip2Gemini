// components/aml/AmlKycPart4.tsx
'use client';

import React from "react";

export default function AmlKycPart4() {
  return (
    <div>

      {/* 7. RISK-BASED APPROACH */}
      <section id="risk-based-approach" className="section">
        <div className="section-header">
          <span className="section-number">07</span>
          <h2 className="section-title">RISK-BASED APPROACH AND RISK ASSESSMENT FRAMEWORK</h2>
        </div>

        <h3>7.1 Foundational Principles of Risk-Based Approach</h3>

        <h4>7.1.1 Philosophy and Regulatory Mandate</h4>
        <p>The Platform adopts a Risk-Based Approach (RBA) to AML/KYC compliance as mandated by:</p>

        <ul className="legal-list">
          <li>(a) Rule 9 of the Prevention of Money Laundering (Maintenance of Records) Rules, 2005;</li>
          <li>(b) FATF Recommendation 1 requiring risk-based allocation of resources and proportionate measures;</li>
          <li>(c) SEBI circulars on risk-based customer due diligence.</li>
        </ul>

        <p>The RBA recognizes that:</p>

        <ul className="legal-list">
          <li>Not all Customers/products/services/transactions/delivery channels present the same level of ML/TF risk;</li>
          <li>Resources must be allocated efficiently with enhanced measures where required;</li>
          <li>A calibrated, proportionate response is more effective than uniform controls;</li>
          <li>Continuous risk assessment and adaptation is essential.</li>
        </ul>

        <h4>7.1.2 Multi-Dimensional Risk Assessment</h4>
        <p>The Platform evaluates risk across four primary dimensions:</p>

        <ul className="legal-list">
          <li>(a) Customer Risk;</li>
          <li>(b) Product/Service Risk;</li>
          <li>(c) Transaction Risk;</li>
          <li>(d) Delivery Channel Risk.</li>
        </ul>

        <p>
          The composite risk rating integrates scores from all dimensions to derive an overall
          Customer Risk Profile.
        </p>

        {/* 7.2 CUSTOMER RISK ASSESSMENT */}
        <h3>7.2 Customer Risk Assessment Matrix</h3>

        <h4>7.2.1 Customer Risk Parameters – Individual Customers</h4>
        <p>Each individual Customer shall be assessed against the following parameters:</p>

        <div className="def-grid">
          <div className="def-row">
            <div className="def-term">Occupation / Source of Income</div>
            <div className="def-desc">
              <strong>Low:</strong> Salaried; Govt employee; Pensioner<br />
              <strong>Medium:</strong> Professionals; Small business owners<br />
              <strong>High:</strong> Cash-intensive business; Forex; Precious metals; Real estate brokers;
              Unregulated services; PEP
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Annual Income</div>
            <div className="def-desc">
              <strong>Low:</strong> &lt; ₹10 lakh<br />
              <strong>Medium:</strong> ₹10–50 lakh<br />
              <strong>High:</strong> &gt; ₹50 lakh (enhanced verification required)
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Geographic Location</div>
            <div className="def-desc">
              <strong>Low:</strong> Metro/Tier-1<br />
              <strong>Medium:</strong> Tier-2/3<br />
              <strong>High:</strong> Border areas; Naxal/terror-affected; High-risk international
              jurisdictions
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Nationality / Residence</div>
            <div className="def-desc">
              <strong>Low:</strong> Indian residents<br />
              <strong>Medium:</strong> NRIs from low-risk jurisdictions<br />
              <strong>High:</strong> Foreign nationals; NRIs from high-risk jurisdictions; Multi-citizenship
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Nature of Relationship</div>
            <div className="def-desc">
              <strong>Low:</strong> Direct onboarding; long-term intent<br />
              <strong>Medium:</strong> Introduced by intermediary<br />
              <strong>High:</strong> Walk-in; speculative; reluctant information sharing
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Customer Behavior</div>
            <div className="def-desc">
              <strong>Low:</strong> Cooperative; transparent<br />
              <strong>Medium:</strong> Minimal info; hesitant<br />
              <strong>High:</strong> Evasive; incomplete; avoids compliance
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">PEP Status</div>
            <div className="def-desc">
              <strong>Low:</strong> Non-PEP<br />
              <strong>Medium:</strong> Family of minor political figures<br />
              <strong>High:</strong> PEP or close associate/immediate family
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Adverse Information</div>
            <div className="def-desc">
              <strong>Low:</strong> No adverse info<br />
              <strong>Medium:</strong> Civil litigation<br />
              <strong>High:</strong> Criminal cases; fraud; sanctions matches
            </div>
          </div>
        </div>

        <h4>7.2.2 Customer Risk Parameters – Body Corporate</h4>

        <div className="def-grid">
          <div className="def-row">
            <div className="def-term">Nature of Business</div>
            <div className="def-desc">
              <strong>Low:</strong> Listed; Govt; Regulated entities<br />
              <strong>Medium:</strong> Regulated sectors; trading; services<br />
              <strong>High:</strong> Cash-intensive; real estate; money changers; crypto; shell companies
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Corporate Structure</div>
            <div className="def-desc">
              <strong>Low:</strong> Simple; transparent<br />
              <strong>Medium:</strong> Group structure<br />
              <strong>High:</strong> Multi-layered; offshore entities; nominee shareholders
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Years of Operation</div>
            <div className="def-desc">
              <strong>Low:</strong> &gt;10 yrs<br />
              <strong>Medium:</strong> 3–10 yrs<br />
              <strong>High:</strong> &lt;3 yrs; newly incorporated
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Financial Standing</div>
            <div className="def-desc">
              <strong>Low:</strong> Consistent, profitable<br />
              <strong>Medium:</strong> Moderate<br />
              <strong>High:</strong> Losses; inconsistent turnover
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Ownership / Management</div>
            <div className="def-desc">
              <strong>Low:</strong> Established promoters<br />
              <strong>Medium:</strong> First-gen entrepreneurs<br />
              <strong>High:</strong> Frequent changes; undisclosed owners; PEP involvement
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Regulatory Compliance</div>
            <div className="def-desc">
              <strong>Low:</strong> Clean record<br />
              <strong>Medium:</strong> Minor delays<br />
              <strong>High:</strong> Penalties; defaults; adverse orders
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Geographic Operations</div>
            <div className="def-desc">
              <strong>Low:</strong> Metro/tier-1<br />
              <strong>Medium:</strong> Pan-India<br />
              <strong>High:</strong> High-risk areas; cross-border with high-risk jurisdiction
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Purpose of Investment</div>
            <div className="def-desc">
              <strong>Low:</strong> Strategic<br />
              <strong>Medium:</strong> Diversification<br />
              <strong>High:</strong> Unclear; unrelated to business
            </div>
          </div>
        </div>

        <h4>7.2.3 Risk Scoring Methodology</h4>

        <h5>(a) Weighted Scoring System</h5>
        <p>Each parameter is assigned scores:</p>

        <ul className="legal-list">
          <li>Low Risk = 1 point</li>
          <li>Medium Risk = 2 points</li>
          <li>High Risk = 3 points</li>
        </ul>

        <p>Weights differ for individuals and corporate Customers.</p>

        <h5>(b) Composite Score Calculation</h5>
        <p><strong>Composite Score = Σ (Parameter Score × Parameter Weight)</strong></p>

        <h5>(c) Risk Categorization</h5>
        <ul className="legal-list">
          <li>Low Risk: 1.0 – 1.5</li>
          <li>Medium Risk: 1.51 – 2.3</li>
          <li>High Risk: 2.31 – 3.0</li>
        </ul>

        <h5>(d) Override Provisions</h5>
        <p>A Customer is automatically High Risk if ANY of the following apply:</p>

        <ul className="legal-list">
          <li>PEP or close associate;</li>
          <li>Adverse media on financial crimes;</li>
          <li>Sanctions match;</li>
          <li>High-risk jurisdiction;</li>
          <li>Fraud/economic offences;</li>
          <li>Cash-intensive business;</li>
          <li>Non-face-to-face with inadequate verification;</li>
          <li>Complex corporate structure;</li>
          <li>Unable/unwilling to provide SoF/SoW;</li>
          <li>Transaction pattern inconsistent with profile.</li>
        </ul>

        {/* 7.3 PRODUCT & SERVICE RISK */}
        <h3>7.3 Product and Service Risk Assessment</h3>

        <h4>7.3.1 Product Risk Classification</h4>

        <h5>(a) High-Risk Products/Services</h5>
        <ul className="legal-list">
          <li>Secondary market unlisted securities ≥ ₹50,00,000</li>
          <li>Sellers who are individuals</li>
          <li>Companies &lt;3 years old</li>
          <li>High-risk sectors (real estate, commodities, etc.)</li>
          <li>Fractional/pooled structures</li>
          <li>Complex securities (unusual conversion terms)</li>
          <li>Broker/intermediary only involvement</li>
          <li>Off-platform settlement</li>
        </ul>

        <h5>(b) Medium-Risk Products/Services</h5>
        <ul className="legal-list">
          <li>₹10,00,000 – ₹50,00,000 unlisted purchases</li>
          <li>Established companies 3–10 years</li>
          <li>Standard equity</li>
          <li>Institutional/known sellers</li>
          <li>Research/advisory</li>
          <li>Escrow services</li>
        </ul>

        <h5>(c) Low-Risk Products/Services</h5>
        <ul className="legal-list">
          <li>Browsing listings only</li>
          <li>Purchases &lt; ₹10,00,000</li>
          <li>Companies &gt;10 years</li>
          <li>Institutional sellers</li>
          <li>Transparent terms</li>
        </ul>

        <h4>7.3.2 Service Delivery Channel Risk</h4>

        <div className="def-grid">
          <div className="def-row">
            <div className="def-term">Fully Digital Onboarding</div>
            <div className="def-desc">
              Medium-High risk — No physical presence; identity theft risk<br />
              Controls: MFA, liveness detection
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">In-Person Onboarding</div>
            <div className="def-desc">
              Low risk — Originals verified
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Business Correspondent/Sub-broker</div>
            <div className="def-desc">
              High risk — Third-party risk<br />
              Controls: Enhanced verification; audits
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Assisted Digital</div>
            <div className="def-desc">
              Medium risk — Hybrid model
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Offline Documentation</div>
            <div className="def-desc">
              Medium risk — Authenticity verification easier
            </div>
          </div>
        </div>

        {/* 7.4 TRANSACTION RISK */}
        <h3>7.4 Transaction Risk Assessment</h3>

        <h4>7.4.1 Transaction Risk Parameters</h4>

        <div className="def-grid">
          <div className="def-row">
            <div className="def-term">Transaction Value</div>
            <div className="def-desc">
              <strong>Low:</strong> &lt; ₹5,00,000<br />
              <strong>Medium:</strong> ₹5,00,000–50,00,000<br />
              <strong>High:</strong> &gt; ₹50,00,000
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Frequency</div>
            <div className="def-desc">
              <strong>Low:</strong> Occasional<br />
              <strong>Medium:</strong> Monthly<br />
              <strong>High:</strong> Weekly or spikes
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Transaction Type</div>
            <div className="def-desc">
              <strong>Low:</strong> Simple equity<br />
              <strong>Medium:</strong> Preference shares<br />
              <strong>High:</strong> Complex/off-market
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Counterparty</div>
            <div className="def-desc">
              <strong>Low:</strong> Institutional<br />
              <strong>Medium:</strong> Verified individual<br />
              <strong>High:</strong> Unknown/offshore
            </div>
          </div>

          <div className="def-row">
            <div className="def-term">Payment Mode</div>
            <div className="def-desc">
              <strong>Low:</strong> Direct bank transfer<br />
              <strong>Medium:</strong> Third-party/instalments<br />
              <strong>High:</strong> Cash/crypto
            </div>
          </div>
        </div>

        <h4>7.4.2 Transaction Risk Scoring</h4>
        <ul className="legal-list">
          <li>Green (Low): 0–30 → Auto-process</li>
          <li>Yellow (Medium): 31–60 → Enhanced review</li>
          <li>Red (High): 61–100 → Hold + investigation</li>
        </ul>

        {/* 7.4.3 AUTOMATED MONITORING RULES */}
        <h3>7.4.3 Automated Transaction Monitoring Rules</h3>

        <h4>(a) Threshold-Based Alerts</h4>
        <ul className="legal-list">
          <li>Single ≥ ₹10,00,000</li>
          <li>Monthly ≥ ₹25,00,000</li>
          <li>Annual ≥ ₹1,00,00,000</li>
          <li>Cash ≥ ₹50,000</li>
        </ul>

        <h4>(b) Pattern-Based Alerts</h4>
        <ul className="legal-list">
          <li>Rapid succession (3+ in 7 days)</li>
          <li>Just-below-threshold patterns</li>
          <li>Round-number transactions</li>
          <li>Immediate post-onboarding activity</li>
          <li>Dormant → active suddenly</li>
        </ul>

        <h4>(c) Behavioral Alerts</h4>
        <ul className="legal-list">
          <li>10x historical average</li>
          <li>Geolocation mismatch</li>
          <li>Unusual timing</li>
          <li>Profile inconsistency</li>
        </ul>

        <h4>(d) Counterparty Alerts</h4>
        <ul className="legal-list">
          <li>PEP involvement</li>
          <li>High-risk jurisdiction</li>
          <li>Newly onboarded counterparties</li>
          <li>Multiple counterparties</li>
        </ul>

        <h4>(e) Fund Flow Alerts</h4>
        <ul className="legal-list">
          <li>Third-party payments</li>
          <li>New bank account (&lt;90 days)</li>
          <li>Immediate withdrawal</li>
          <li>Circular trading</li>
        </ul>

        <h4>(f) High-Value Transactions</h4>
        <ul className="legal-list">
          <li>&gt; ₹50,00,000 → Red flag</li>
          <li>Mandatory review & investigation</li>
        </ul>

        <h4>(g) Structuring Detection</h4>
        <ul className="legal-list">
          <li>Multiple transactions between ₹9,00,000–₹9,99,000</li>
          <li>≥3 in 30 days → Red flag</li>
        </ul>

        <h4>(h) Cross-Border Transactions</h4>
        <ul className="legal-list">
          <li>FEMA compliance</li>
          <li>High-risk jurisdiction → Red flag</li>
        </ul>

        <h4>(i) IP & Geolocation Anomalies</h4>
        <ul className="legal-list">
          <li>VPN/proxy → Hold</li>
          <li>Foreign IP → EDD</li>
        </ul>

        <h4>(j) Valuation Discrepancies</h4>
        <ul className="legal-list">
          <li>Deviation &gt;30% → Yellow</li>
          <li>Deviation &gt;100% → Red + valuation review</li>
        </ul>

        <h4>(k) PEP Transactions</h4>
        <ul className="legal-list">
          <li>Designated Director alert</li>
          <li>Mandatory EDD</li>
        </ul>

        <h4>(l) Third-Party Payments</h4>
        <ul className="legal-list">
          <li>Red flag unless authenticated</li>
        </ul>

        <h4>(m) Cash Transactions</h4>
        <ul className="legal-list">
          <li>No cash &gt; ₹50,000</li>
          <li>CTR if monthly cash ≥ ₹10,00,000</li>
        </ul>

        <h4>(n) Transaction Structure Complexity</h4>
        <p>Complex transactions → Yellow/Red based on nature.</p>

      </section>

      {/* 8. TRANSACTION MONITORING SYSTEM */}
      <section id="transaction-monitoring-system" className="section">
        <div className="section-header">
          <span className="section-number">08</span>
          <h2 className="section-title">TRANSACTION MONITORING SYSTEMS AND PROCEDURES</h2>
        </div>

        <h3>8.1 Transaction Monitoring Framework</h3>

        <h4>8.1.1 Objectives of Transaction Monitoring</h4>
        <ul className="legal-list">
          <li>Detect suspicious patterns</li>
          <li>Identify inconsistencies</li>
          <li>Flag ML/TF risks</li>
          <li>Timely STR filing</li>
          <li>Deterrent effect</li>
          <li>Maintain audit trail</li>
        </ul>

        <h4>8.1.2 Multi-Layered Monitoring Approach</h4>

        <h5>(a) Real-Time Automated Monitoring</h5>
        <ul className="legal-list">
          <li>Rule-based engine</li>
          <li>Immediate alerts</li>
          <li>Auto-hold on high-risk</li>
          <li>24x7 operation</li>
        </ul>

        <h5>(b) Near-Real-Time Human Review</h5>
        <ul className="legal-list">
          <li>Review alerts</li>
          <li>Investigate flagged items</li>
          <li>Escalate as required</li>
        </ul>

        <h5>(c) Periodic Pattern Analysis</h5>
        <ul className="legal-list">
          <li>Weekly patterns</li>
          <li>Monthly behavior</li>
          <li>Quarterly trends</li>
          <li>Annual AML audit</li>
        </ul>

        <h5>(d) Intelligence-Led Monitoring</h5>
        <ul className="legal-list">
          <li>FIU-IND advisories</li>
          <li>External intelligence</li>
          <li>Typology-based monitoring</li>
        </ul>

        <h3>8.2 Automated Transaction Monitoring System - Technical Specifications</h3>

        <h4>8.2.1 System Capabilities</h4>
        <ul className="legal-list">
          <li>Real-time processing</li>
          <li>Configurable rule engine</li>
          <li>Alert creation with scoring</li>
          <li>Case management tools</li>
          <li>MIS and STR/CTR reporting</li>
          <li>Integration with KYC & Core systems</li>
          <li>Scalable infrastructure</li>
          <li>Immutable audit logs</li>
          <li>Security & encryption</li>
        </ul>

        <h3>8.2.2 Core Monitoring Rules – Pre-IPO Specific</h3>

        <h4>(a) High-Value Single Transaction</h4>
        <ul className="legal-list">
          <li>≥ ₹10,00,000 → Yellow; review in 24 hrs</li>
          <li>≥ ₹50,00,000 → Red; hold + investigation</li>
        </ul>

        <h4>(b) Cumulative Monthly Threshold</h4>
        <ul className="legal-list">
          <li>≥ ₹25,00,000 → Yellow</li>
          <li>≥ ₹1,00,00,000 → Red</li>
        </ul>

        <h4>(c) Cumulative Annual Threshold</h4>
        <ul className="legal-list">
          <li>≥ ₹1,00,00,000 → Mandatory review</li>
          <li>≥ ₹5,00,00,000 → Full forensic review</li>
        </ul>

        <h4>(d) Structuring Detection</h4>
        <ul className="legal-list">
          <li>3+ transactions between ₹9,00,000–9,99,000 → Red</li>
        </ul>

        <h4>(e) Rapid Transaction Velocity</h4>
        <ul className="legal-list">
          <li>3+ transactions in 7 days → Yellow</li>
          <li>5+ in 7 days → Red</li>
        </ul>

        <h4>(f) Immediate Post-Onboarding Transaction</h4>
        <ul className="legal-list">
          <li>Within 48 hours → Yellow</li>
          <li>High-value within 48 hours → Red</li>
        </ul>

        <h4>(g) Dormant Account Activation</h4>
        <ul className="legal-list">
          <li>Sudden activity → Yellow</li>
          <li>High-value → Red</li>
        </ul>

        <h4>(h) Profile Mismatch</h4>
        <ul className="legal-list">
          <li>&gt;200% of budget → Yellow</li>
          <li>&gt;500% or exceeds income → Red</li>
        </ul>

        <h4>(i) Round Number Transactions</h4>
        <ul className="legal-list">
          <li>3+ round-number → Yellow</li>
        </ul>

        <h4>(j) Seller-Buyer Patterns</h4>
        <ul className="legal-list">
          <li>Seller trades with 5+ buyers → Yellow</li>
          <li>Buyer trades with 5+ sellers → Yellow</li>
        </ul>

        <h4>(k) Quick Flip</h4>
        <ul className="legal-list">
          <li>Buy & sell within 90 days → Yellow</li>
          <li>Within 30 days → Red</li>
        </ul>

        <h4>(l) Cross-Border Transactions</h4>
        <ul className="legal-list">
          <li>FEMA compliance check</li>
          <li>High-risk jurisdiction → Red</li>
        </ul>

        <h4>(m) Third-Party Payment</h4>
        <ul className="legal-list">
          <li>Red unless justified/documented</li>
        </ul>

        <h4>(n) Cash Transactions</h4>
        <ul className="legal-list">
          <li>≥ ₹50,000 → Yellow</li>
          <li>≥ ₹2,00,000 → Red</li>
          <li>CTR if ≥ ₹10,00,000/month</li>
        </ul>

        <h4>(o) Recently Opened Bank Account</h4>
        <ul className="legal-list">
          <li>&lt;90 days old → Yellow</li>
        </ul>

        <h4>(p) IP & Geolocation Anomaly</h4>
        <ul className="legal-list">
          <li>Different from profile → Yellow</li>
          <li>Foreign/VPN → Red</li>
        </ul>

        <h4>(q) Valuation Discrepancy</h4>
        <ul className="legal-list">
          <li>&gt;30% deviation → Yellow</li>
          <li>&gt;100% deviation → Red</li>
        </ul>

        <h4>(r) PEP Transactions</h4>
        <ul className="legal-list">
          <li>Immediate alert; EDD mandatory</li>
        </ul>

        <h4>(s) Adverse Media</h4>
        <ul className="legal-list">
          <li>Red; hold until cleared</li>
        </ul>

        <h4>(t) Complex Structures</h4>
        <p>Multiple-step or unusual transactions → Yellow or Red based on assessment.</p>

      </section>
    </div>
  );
}

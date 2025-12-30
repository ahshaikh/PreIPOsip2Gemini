<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LegalAgreement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LegalAgreementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $effectiveDate = $now->format('Y-m-d');

        $this->command->info('Seeding Legal Agreements with HTML content...');

        // 1. Terms of Service
        LegalAgreement::updateOrCreate(
            ['type' => 'terms_of_service'],
            [
                'title' => 'Terms of Service',
                'description' => 'Comprehensive terms and conditions governing your use of PreIPO SIP platform and services.',
                'content' => $this->getTermsOfServiceContent(),
                'version' => '1.2.0',
                'status' => 'active',
                'effective_date' => $effectiveDate,
                'require_signature' => true,
                'is_template' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // 2. Privacy Policy
        LegalAgreement::updateOrCreate(
            ['type' => 'privacy_policy'],
            [
                'title' => 'Privacy Policy',
                'description' => 'How we collect, use, protect, and handle your personal information.',
                'content' => $this->getPrivacyPolicyContent(),
                'version' => '1.1.0',
                'status' => 'active',
                'effective_date' => $effectiveDate,
                'require_signature' => true,
                'is_template' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // 3. Cookie Policy
        LegalAgreement::updateOrCreate(
            ['type' => 'cookie_policy'],
            [
                'title' => 'Cookie Policy',
                'description' => 'Information about how we use cookies and similar tracking technologies.',
                'content' => $this->getCookiePolicyContent(),
                'version' => '1.0.0',
                'status' => 'active',
                'effective_date' => $effectiveDate,
                'require_signature' => false,
                'is_template' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // 4. Refund Policy
        LegalAgreement::updateOrCreate(
            ['type' => 'refund_policy'],
            [
                'title' => 'Refund Policy',
                'description' => 'Terms and conditions regarding refunds, cancellations, and payment processing.',
                'content' => $this->getRefundPolicyContent(),
                'version' => '1.0.0',
                'status' => 'active',
                'effective_date' => $effectiveDate,
                'require_signature' => true,
                'is_template' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // 5. AML & KYC Policy
        LegalAgreement::updateOrCreate(
            ['type' => 'aml_kyc_policy'], // Matches your standard key
            [
                'title' => 'Anti-Money Laundering & KYC Policy',
                'description' => 'Our Anti-Money Laundering and Know Your Customer compliance procedures.',
                'content' => $this->getAMLKYCPolicyContent(),
                'version' => '1.1.0',
                'status' => 'active',
                'effective_date' => $effectiveDate,
                'require_signature' => true,
                'is_template' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // 6. Risk Disclosure
        LegalAgreement::updateOrCreate(
            ['type' => 'risk_disclosure'], 
            [
                'title' => 'Risk Disclosure Document',
                'description' => 'Critical information regarding the risks associated with unlisted investments.',
                'content' => $this->getRiskDisclosureContent(),
                'version' => '1.0.0',
                'status' => 'active',
                'effective_date' => $effectiveDate,
                'require_signature' => true,
                'is_template' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $this->command->info('✓ All 6 Legal Agreements seeded successfully!');
    }

    // --- Content Generators (HTML Structure preserved from your codebase) ---

    private function getTermsOfServiceContent(): string
    {
        return <<<'HTML'
<div class="legal-document">
    <h1>Terms of Service</h1>
    <p class="effective-date"><strong>Effective Date:</strong> January 15, 2025</p>
    <p class="last-updated"><strong>Last Updated:</strong> January 15, 2025</p>

    <section>
        <h2>1. Introduction and Acceptance</h2>
        <p>Welcome to PreIPO SIP, a product of Pre IPO SIP Private Limited (hereinafter referred to as "PreIPO SIP," "we," "us," or "our"). These Terms of Service ("Terms") constitute a legally binding agreement between you ("User," "Investor," or "you") and PreIPO SIP governing your access to and use of our platform, services, and associated features.</p>
        <p>By creating an account, accessing our platform, or using any of our services, you acknowledge that you have read, understood, and agree to be bound by these Terms, along with our Privacy Policy, Cookie Policy, AML & KYC Policy, and all other policies referenced herein.</p>
        <p><strong>If you do not agree to these Terms, you must not access or use our platform or services.</strong></p>
    </section>

    <section>
        <h2>2. About PreIPO SIP</h2>
        <h3>2.1 Company Information</h3>
        <ul>
            <li><strong>Company Name:</strong> Pre IPO SIP Private Limited</li>
            <li><strong>SEBI Registration No:</strong> INZ000421765</li>
            <li><strong>CIN:</strong> U65990MH2025OPC194372</li>
            <li><strong>GSTIN:</strong> 27AABCP1234Q1Z7</li>
            <li><strong>Registered Office:</strong> Office No. 14, 2nd Floor, Crystal Business Park, Near Golden Nest Road, Mira Bhayandar (East), Thane, Maharashtra – 401107, India</li>
        </ul>

        <h3>2.2 Our Services</h3>
        <p>PreIPO SIP operates a SEBI-compliant digital platform that facilitates investment opportunities in unlisted securities, pre-IPO shares, and private equity instruments. Our services include:</p>
        <ul>
            <li>Access to curated investment opportunities in unlisted and pre-IPO companies</li>
            <li>Systematic Investment Plan (SIP) options for private market investments</li>
            <li>Portfolio management and tracking tools</li>
            <li>Investment research, analytics, and insights</li>
            <li>KYC verification and compliance services</li>
            <li>Transaction facilitation and settlement services</li>
        </ul>
    </section>

    <section>
        <h2>3. Eligibility and Account Registration</h2>
        <h3>3.1 Eligibility Criteria</h3>
        <p>To use our platform and services, you must:</p>
        <ul>
            <li>Be at least 18 years of age</li>
            <li>Be a resident of India with a valid PAN (Permanent Account Number)</li>
            <li>Have legal capacity to enter into binding contracts under Indian law</li>
            <li>Complete our KYC (Know Your Customer) verification process</li>
            <li>Possess a valid bank account in your name for transactions</li>
            <li>Not be prohibited from investing in securities under any applicable law or regulation</li>
        </ul>

        <h3>3.2 Account Registration</h3>
        <p>Account creation requires you to provide accurate, complete, and current information, including:</p>
        <ul>
            <li>Full legal name as per official documents</li>
            <li>Valid email address and mobile number</li>
            <li>PAN card details</li>
            <li>Bank account information</li>
            <li>Address proof and identity verification documents</li>
        </ul>

        <h3>3.3 Account Security</h3>
        <p>You are responsible for:</p>
        <ul>
            <li>Maintaining the confidentiality of your account credentials</li>
            <li>All activities that occur under your account</li>
            <li>Immediately notifying us of any unauthorized access or security breach</li>
            <li>Ensuring your contact information remains current and accurate</li>
        </ul>
    </section>

    <section>
        <h2>4. Investment Services and Transactions</h2>
        <h3>4.1 Nature of Investments</h3>
        <p>PreIPO SIP facilitates investments in unlisted securities, which are:</p>
        <ul>
            <li>Not traded on recognized stock exchanges</li>
            <li>Subject to higher risks including liquidity risk, valuation risk, and market risk</li>
            <li>Illiquid and may require extended holding periods</li>
            <li>Subject to regulatory restrictions on transfer and sale</li>
        </ul>

        <h3>4.2 Investment Process</h3>
        <ol>
            <li><strong>Research & Selection:</strong> Browse available investment opportunities on our platform</li>
            <li><strong>Investment Decision:</strong> Make informed decisions based on provided information and your own research</li>
            <li><strong>Order Placement:</strong> Submit investment orders through our platform</li>
            <li><strong>Payment:</strong> Complete payment through approved payment methods</li>
            <li><strong>Confirmation:</strong> Receive confirmation of investment execution</li>
            <li><strong>Documentation:</strong> Receive relevant investment documentation and certificates</li>
        </ol>

        <h3>4.3 SIP (Systematic Investment Plan)</h3>
        <p>For SIP investments:</p>
        <ul>
            <li>You authorize recurring debits from your bank account on specified dates</li>
            <li>SIPs can be paused, modified, or cancelled subject to applicable terms</li>
            <li>Minimum and maximum SIP amounts apply as specified for each opportunity</li>
            <li>SIP execution is subject to availability of shares and regulatory compliance</li>
        </ul>

        <h3>4.4 Pricing and Valuation</h3>
        <ul>
            <li>Share prices are determined by issuers, sellers, or market conditions</li>
            <li>Prices may change without prior notice based on market dynamics</li>
            <li>Historical valuations do not guarantee future prices</li>
            <li>We do not guarantee price discovery or valuation accuracy</li>
        </ul>
    </section>

    <section>
        <h2>5. Fees, Charges, and Payments</h2>
        <h3>5.1 Platform Fees</h3>
        <p>PreIPO SIP may charge fees for:</p>
        <ul>
            <li>Platform access and subscription fees</li>
            <li>Transaction processing fees</li>
            <li>Account maintenance charges</li>
            <li>Premium features and services</li>
        </ul>

        <h3>5.2 Payment Terms</h3>
        <ul>
            <li>All fees are quoted in Indian Rupees (INR)</li>
            <li>Fees are non-refundable unless explicitly stated</li>
            <li>Payment must be made through approved methods only</li>
            <li>We reserve the right to modify fees with 30 days' notice</li>
        </ul>

        <h3>5.3 Taxes</h3>
        <p>You are responsible for all applicable taxes, including:</p>
        <ul>
            <li>Goods and Services Tax (GST) on platform fees</li>
            <li>Capital gains tax on investment profits</li>
            <li>Tax deductions at source (TDS) as applicable</li>
            <li>Any other taxes mandated by Indian tax authorities</li>
        </ul>
    </section>

    <section>
        <h2>6. Compliance and Regulatory Obligations</h2>
        <h3>6.1 SEBI Compliance</h3>
        <p>PreIPO SIP operates in compliance with SEBI regulations, including:</p>
        <ul>
            <li>SEBI (Stock Brokers) Regulations, 2018</li>
            <li>SEBI (Issue of Capital and Disclosure Requirements) Regulations, 2018</li>
            <li>SEBI (Substantial Acquisition of Shares and Takeovers) Regulations, 2011</li>
            <li>All other applicable SEBI guidelines and circulars</li>
        </ul>

        <h3>6.2 KYC and AML Compliance</h3>
        <p>All users must comply with:</p>
        <ul>
            <li>Prevention of Money Laundering Act, 2002 (PMLA)</li>
            <li>SEBI KYC registration requirements</li>
            <li>Our internal AML & KYC policies</li>
            <li>Document verification and due diligence requirements</li>
        </ul>

        <h3>6.3 Ongoing Compliance</h3>
        <p>Users must:</p>
        <ul>
            <li>Update KYC information when changes occur</li>
            <li>Respond promptly to verification requests</li>
            <li>Comply with investment limits and restrictions</li>
            <li>Report suspicious activities or transactions</li>
        </ul>
    </section>

    <section>
        <h2>7. Risk Disclosure</h2>
        <p><strong>Investment in unlisted securities involves substantial risks. You must carefully read our complete Risk Disclosure Document before investing.</strong></p>

        <h3>7.1 Key Investment Risks</h3>
        <ul>
            <li><strong>Market Risk:</strong> Value of investments may fluctuate significantly</li>
            <li><strong>Liquidity Risk:</strong> Unlisted shares are difficult to sell and may have no active market</li>
            <li><strong>Valuation Risk:</strong> Fair market value is difficult to determine for unlisted securities</li>
            <li><strong>Company Risk:</strong> Investee companies may fail, leading to total loss of capital</li>
            <li><strong>Regulatory Risk:</strong> Changes in laws may adversely affect investments</li>
            <li><strong>Lock-in Risk:</strong> Investments may be subject to lock-in periods</li>
            <li><strong>Information Risk:</strong> Limited public information available for unlisted companies</li>
            <li><strong>Dilution Risk:</strong> Future fundraising may dilute your shareholding</li>
        </ul>

        <h3>7.2 No Guarantee of Returns</h3>
        <p>We do not guarantee:</p>
        <ul>
            <li>Returns on investments</li>
            <li>Capital preservation</li>
            <li>Liquidity or exit opportunities</li>
            <li>Future IPO listing of investee companies</li>
            <li>Buyback opportunities</li>
        </ul>
    </section>

    <section>
        <h2>8. Intellectual Property Rights</h2>
        <h3>8.1 Our Intellectual Property</h3>
        <p>All content, features, and functionality on our platform, including but not limited to:</p>
        <ul>
            <li>Text, graphics, logos, icons, images</li>
            <li>Software, code, and algorithms</li>
            <li>Trademarks, service marks, and trade names</li>
            <li>Data compilation and organization</li>
        </ul>
        <p>are owned by PreIPO SIP and protected by Indian and international intellectual property laws.</p>

        <h3>8.2 Limited License</h3>
        <p>We grant you a limited, non-exclusive, non-transferable, revocable license to access and use our platform solely for personal, non-commercial investment purposes.</p>

        <h3>8.3 Restrictions</h3>
        <p>You may not:</p>
        <ul>
            <li>Copy, modify, or distribute our content without permission</li>
            <li>Reverse engineer or decompile any platform software</li>
            <li>Use automated systems (bots, scrapers) to access our platform</li>
            <li>Remove or alter copyright notices or proprietary markings</li>
        </ul>
    </section>

    <section>
        <h2>9. Prohibited Activities</h2>
        <p>You agree not to:</p>
        <ul>
            <li>Violate any applicable laws, regulations, or these Terms</li>
            <li>Provide false, misleading, or fraudulent information</li>
            <li>Impersonate any person or entity</li>
            <li>Engage in market manipulation or insider trading</li>
            <li>Use the platform for money laundering or terrorist financing</li>
            <li>Interfere with platform security or functionality</li>
            <li>Transmit viruses, malware, or harmful code</li>
            <li>Harass, abuse, or harm other users or our staff</li>
            <li>Access other users' accounts without authorization</li>
            <li>Use the platform for any illegal or unauthorized purpose</li>
        </ul>
    </section>

    <section>
        <h2>10. Limitation of Liability</h2>
        <h3>10.1 No Liability for Investment Losses</h3>
        <p>PreIPO SIP is not liable for:</p>
        <ul>
            <li>Investment losses or poor performance of investee companies</li>
            <li>Failure of companies to list on stock exchanges</li>
            <li>Inability to sell or liquidate investments</li>
            <li>Changes in valuation of unlisted securities</li>
        </ul>

        <h3>10.2 Platform Availability</h3>
        <p>We do not guarantee:</p>
        <ul>
            <li>Uninterrupted or error-free platform operation</li>
            <li>Availability of specific investment opportunities</li>
            <li>Accuracy of all information provided by third parties</li>
        </ul>

        <h3>10.3 Maximum Liability</h3>
        <p>To the maximum extent permitted by law, our total liability for any claims arising from your use of our services shall not exceed the fees you paid to us in the 12 months preceding the claim.</p>
    </section>

    <section>
        <h2>11. Indemnification</h2>
        <p>You agree to indemnify, defend, and hold harmless PreIPO SIP, its affiliates, officers, directors, employees, and agents from any claims, losses, damages, liabilities, and expenses (including legal fees) arising from:</p>
        <ul>
            <li>Your violation of these Terms</li>
            <li>Your violation of any applicable laws or regulations</li>
            <li>Your investment decisions</li>
            <li>Your breach of any representations or warranties</li>
            <li>Infringement of third-party intellectual property rights</li>
        </ul>
    </section>

    <section>
        <h2>12. Termination</h2>
        <h3>12.1 Termination by You</h3>
        <p>You may terminate your account at any time by:</p>
        <ul>
            <li>Submitting a termination request through our platform</li>
            <li>Liquidating all active investments (subject to availability)</li>
            <li>Settling all pending transactions and fees</li>
        </ul>

        <h3>12.2 Termination by Us</h3>
        <p>We may suspend or terminate your account immediately if:</p>
        <ul>
            <li>You violate these Terms or any applicable laws</li>
            <li>You engage in fraudulent or suspicious activities</li>
            <li>Your account shows signs of unauthorized access</li>
            <li>You fail KYC verification or compliance checks</li>
            <li>We are required to do so by law or regulation</li>
        </ul>

        <h3>12.3 Effect of Termination</h3>
        <p>Upon termination:</p>
        <ul>
            <li>Your access to the platform will cease</li>
            <li>Existing investments will continue to be held</li>
            <li>You remain liable for all obligations incurred before termination</li>
            <li>Provisions that should survive termination will continue to apply</li>
        </ul>
    </section>

    <section>
        <h2>13. Dispute Resolution and Governing Law</h2>
        <h3>13.1 Governing Law</h3>
        <p>These Terms are governed by the laws of India. All disputes shall be subject to the exclusive jurisdiction of courts in Mumbai, Maharashtra.</p>

        <h3>13.2 Arbitration</h3>
        <p>Any dispute arising from these Terms shall first be attempted to be resolved through good faith negotiations. If unresolved within 30 days, disputes may be referred to arbitration under the Arbitration and Conciliation Act, 1996.</p>

        <h3>13.3 SEBI Grievance Redressal</h3>
        <p>Investors may also file complaints through SEBI's SCORES (SEBI Complaints Redress System) portal at www.scores.gov.in.</p>
    </section>

    <section>
        <h2>14. Amendments to Terms</h2>
        <p>We reserve the right to modify these Terms at any time. Changes will be effective upon posting to our platform. We will notify you of material changes via email or platform notification.</p>

        <p>Your continued use of the platform after changes constitutes acceptance of the modified Terms. If you do not agree to the changes, you must discontinue use of our services.</p>
    </section>

    <section>
        <h2>15. General Provisions</h2>
        <h3>15.1 Entire Agreement</h3>
        <p>These Terms, together with our Privacy Policy and other referenced policies, constitute the entire agreement between you and PreIPO SIP.</p>

        <h3>15.2 Severability</h3>
        <p>If any provision of these Terms is found to be unenforceable, the remaining provisions will continue in full force and effect.</p>

        <h3>15.3 Waiver</h3>
        <p>Our failure to enforce any right or provision shall not constitute a waiver of such right or provision.</p>

        <h3>15.4 Assignment</h3>
        <p>You may not assign these Terms without our prior written consent. We may assign our rights and obligations without restriction.</p>

        <h3>15.5 Force Majeure</h3>
        <p>We shall not be liable for any failure to perform due to events beyond our reasonable control, including natural disasters, acts of government, wars, or technical failures.</p>
    </section>

    <section>
        <h2>16. Contact Information</h2>
        <p>For questions, concerns, or complaints regarding these Terms, please contact us:</p>
        <ul>
            <li><strong>Email:</strong> legal@preiposip.com</li>
            <li><strong>Support Email:</strong> support@preiposip.com</li>
            <li><strong>Address:</strong> PreIPO SIP Private Limited, Office No. 14, 2nd Floor, Crystal Business Park, Near Golden Nest Road, Mira Bhayandar (East), Thane, Maharashtra – 401107, India</li>
            <li><strong>Phone:</strong> +91-22-XXXX-XXXX</li>
        </ul>
    </section>

    <section>
        <h2>17. Acknowledgment</h2>
        <p>By using our platform, you acknowledge that:</p>
        <ul>
            <li>You have read and understood these Terms</li>
            <li>You have reviewed our Risk Disclosure Document</li>
            <li>You understand the risks of investing in unlisted securities</li>
            <li>You are making independent investment decisions</li>
            <li>You have sought professional advice if needed</li>
            <li>You agree to be bound by these Terms</li>
        </ul>
    </section>

    <hr>
    <p class="footer-note"><em>Last Updated: January 15, 2025 | Version 1.2.0</em></p>
</div>
HTML;
    }

    private function getAMLKYCPolicyContent(): string
    {
        return <<<'HTML'
<div class="legal-document">
    <h1>Anti-Money Laundering & Know Your Customer Policy</h1>
    <p class="effective-date"><strong>Effective Date:</strong> January 15, 2025</p>
    <p class="last-updated"><strong>Last Updated:</strong> January 15, 2025</p>

    <section>
        <h2>1. Introduction</h2>
        <p>Pre IPO SIP Private Limited ("PreIPO SIP," "we," "us," or "our") is committed to preventing money laundering, terrorist financing, and other financial crimes. This Anti-Money Laundering and Know Your Customer Policy ("AML & KYC Policy") establishes our framework for compliance with applicable laws and regulations.</p>

        <h3>1.1 Regulatory Framework</h3>
        <p>This policy complies with:</p>
        <ul>
            <li>Prevention of Money Laundering Act, 2002 (PMLA)</li>
            <li>Prevention of Money Laundering (Maintenance of Records) Rules, 2005</li>
            <li>SEBI (Know Your Client) Registration Regulations</li>
            <li>SEBI circular on KYC requirements for securities market participants</li>
            <li>Financial Action Task Force (FATF) recommendations</li>
            <li>Reserve Bank of India (RBI) guidelines on AML/CFT</li>
        </ul>

        <h3>1.2 Company Information</h3>
        <ul>
            <li><strong>Company Name:</strong> Pre IPO SIP Private Limited</li>
            <li><strong>SEBI Registration No:</strong> INZ000421765</li>
            <li><strong>CIN:</strong> U65990MH2025OPC194372</li>
            <li><strong>Registered Office:</strong> Office No. 14, 2nd Floor, Crystal Business Park, Near Golden Nest Road, Mira Bhayandar (East), Thane, Maharashtra – 401107, India</li>
        </ul>
    </section>

    <section>
        <h2>2. Objectives</h2>
        <p>The primary objectives of this AML & KYC Policy are to:</p>
        <ul>
            <li>Prevent money laundering and terrorist financing activities</li>
            <li>Establish customer identity verification procedures</li>
            <li>Monitor and report suspicious transactions</li>
            <li>Maintain comprehensive customer records</li>
            <li>Ensure compliance with all applicable laws and regulations</li>
            <li>Protect the integrity of financial markets</li>
            <li>Safeguard PreIPO SIP from being used for illicit purposes</li>
        </ul>
    </section>

    <section>
        <h2>3. Know Your Customer (KYC) Requirements</h2>
        <h3>3.1 Customer Acceptance Policy</h3>
        <p>We conduct thorough due diligence before establishing a customer relationship. We reserve the right to reject any customer account based on risk assessment.</p>

        <h4>3.1.1 Risk Categories</h4>
        <p>Customers are classified into risk categories:</p>
        <ul>
            <li><strong>Low Risk:</strong> Salaried employees, government employees, regulated entities</li>
            <li><strong>Medium Risk:</strong> Self-employed professionals, small business owners</li>
            <li><strong>High Risk:</strong> Politically Exposed Persons (PEPs), high net worth individuals, cash-intensive businesses</li>
        </ul>

        <h3>3.2 Mandatory KYC Documents</h3>
        <h4>3.2.1 For Individual Customers</h4>
        <p>All individual customers must provide:</p>
        <ul>
            <li><strong>Identity Proof (mandatory):</strong>
                <ul>
                    <li>PAN Card (Permanent Account Number) - Mandatory</li>
                    <li>Aadhaar Card</li>
                    <li>Passport</li>
                    <li>Voter ID Card</li>
                    <li>Driving License</li>
                </ul>
            </li>
            <li><strong>Address Proof (any one):</strong>
                <ul>
                    <li>Aadhaar Card</li>
                    <li>Passport</li>
                    <li>Voter ID Card</li>
                    <li>Driving License</li>
                    <li>Utility bills (electricity, water, gas) not older than 3 months</li>
                    <li>Bank account statement not older than 3 months</li>
                </ul>
            </li>
            <li><strong>Bank Account Details:</strong>
                <ul>
                    <li>Cancelled cheque or bank statement showing account number and IFSC code</li>
                    <li>Account must be in the customer's name</li>
                </ul>
            </li>
            <li><strong>Photograph:</strong> Recent passport-size photograph</li>
            <li><strong>Signature:</strong> Digital signature or scanned signature</li>
        </ul>

        <h4>3.2.2 For Corporate/Entity Customers</h4>
        <ul>
            <li>Certificate of Incorporation</li>
            <li>Memorandum and Articles of Association</li>
            <li>Board Resolution for account opening</li>
            <li>List of Directors with KYC documents</li>
            <li>PAN of the company</li>
            <li>GST Registration Certificate</li>
            <li>Authorized signatory details and KYC</li>
            <li>Beneficial ownership details (25% or more shareholding)</li>
        </ul>

        <h3>3.3 Politically Exposed Persons (PEPs)</h3>
        <p>Special enhanced due diligence is required for PEPs, including:</p>
        <ul>
            <li>Senior politicians and government officials</li>
            <li>Judges of High Courts and Supreme Court</li>
            <li>Senior military officers</li>
            <li>Senior executives of state-owned corporations</li>
            <li>Family members and close associates of PEPs</li>
        </ul>

        <h4>Enhanced Due Diligence for PEPs:</h4>
        <ul>
            <li>Source of wealth documentation</li>
            <li>Source of funds for investments</li>
            <li>Senior management approval for account opening</li>
            <li>Continuous enhanced monitoring of transactions</li>
            <li>Annual review and re-verification</li>
        </ul>

        <h3>3.4 KYC Verification Process</h3>
        <ol>
            <li><strong>Document Submission:</strong> Customer uploads required documents through secure platform</li>
            <li><strong>Document Verification:</strong> Our compliance team verifies authenticity using:
                <ul>
                    <li>Government databases (DigiLocker, UIDAI for Aadhaar)</li>
                    <li>PAN verification through Income Tax Department</li>
                    <li>Bank account verification through penny drop</li>
                </ul>
            </li>
            <li><strong>Video KYC (if applicable):</strong> Live video verification in accordance with SEBI guidelines</li>
            <li><strong>Risk Assessment:</strong> Customer assigned to appropriate risk category</li>
            <li><strong>Approval/Rejection:</strong> Account approved or rejected with reasons</li>
            <li><strong>Ongoing Monitoring:</strong> Continuous monitoring of customer transactions and profile</li>
        </ol>

        <h3>3.5 KYC Update and Re-verification</h3>
        <p>KYC information must be updated periodically:</p>
        <ul>
            <li><strong>Low Risk Customers:</strong> Once every 10 years</li>
            <li><strong>Medium Risk Customers:</strong> Once every 8 years</li>
            <li><strong>High Risk Customers:</strong> Once every 2 years or more frequently</li>
            <li><strong>Trigger-based Updates:</strong> When customer information changes (address, phone, etc.)</li>
        </ul>
    </section>

    <section>
        <h2>4. Customer Due Diligence (CDD)</h2>
        <h3>4.1 Standard CDD Measures</h3>
        <p>For all customers, we perform:</p>
        <ul>
            <li>Identity verification using reliable independent sources</li>
            <li>Verification of registered address</li>
            <li>Understanding nature and purpose of business relationship</li>
            <li>Obtaining information on intended nature of transactions</li>
            <li>Source of funds verification</li>
        </ul>

        <h3>4.2 Enhanced Due Diligence (EDD)</h3>
        <p>Enhanced measures for high-risk customers include:</p>
        <ul>
            <li>Additional identity verification documents</li>
            <li>Source of wealth documentation</li>
            <li>Purpose of investment detailed explanation</li>
            <li>References from existing banks/financial institutions</li>
            <li>Senior management approval</li>
            <li>More frequent monitoring and review</li>
        </ul>

        <h3>4.3 Simplified Due Diligence</h3>
        <p>For low-risk customers, simplified measures may include:</p>
        <ul>
            <li>Reduced frequency of updates</li>
            <li>Lower thresholds for transaction monitoring</li>
        </ul>
        <p><strong>Note:</strong> Simplified due diligence is applied judiciously and only after proper risk assessment.</p>
    </section>

    <section>
        <h2>5. Transaction Monitoring</h2>
        <h3>5.1 Ongoing Monitoring</h3>
        <p>We continuously monitor all customer transactions for:</p>
        <ul>
            <li>Unusual transaction patterns</li>
            <li>Transactions inconsistent with customer profile</li>
            <li>Large or frequent cash transactions</li>
            <li>Structured transactions to avoid reporting thresholds</li>
            <li>Transactions with high-risk jurisdictions</li>
            <li>Rapid movement of funds</li>
        </ul>

        <h3>5.2 Red Flags and Suspicious Activity</h3>
        <p>We watch for indicators including:</p>
        <ul>
            <li>Transactions not commensurate with customer's financial profile</li>
            <li>Frequent changes in customer information</li>
            <li>Reluctance to provide information or documentation</li>
            <li>Use of multiple accounts without clear business purpose</li>
            <li>Transactions involving unrelated third parties</li>
            <li>High-value transactions in dormant accounts</li>
            <li>Unusual cross-border transactions</li>
            <li>Requests for exceptions to policies</li>
        </ul>

        <h3>5.3 Threshold-Based Monitoring</h3>
        <p>Special scrutiny for transactions:</p>
        <ul>
            <li>Single transactions exceeding ₹10 lakhs</li>
            <li>Series of connected transactions exceeding ₹10 lakhs</li>
            <li>Monthly investment exceeding typical customer patterns</li>
            <li>Cash transactions (which we generally do not accept)</li>
        </ul>
    </section>

    <section>
        <h2>6. Suspicious Transaction Reporting</h2>
        <h3>6.1 Reporting to FIU-IND</h3>
        <p>We file reports with the Financial Intelligence Unit - India (FIU-IND) for:</p>
        <ul>
            <li><strong>Suspicious Transaction Reports (STR):</strong> When we suspect money laundering or terrorist financing</li>
            <li><strong>Cash Transaction Reports (CTR):</strong> Cash transactions exceeding prescribed thresholds (if applicable)</li>
            <li><strong>Cross-Border Wire Transfer Reports:</strong> International transactions exceeding thresholds</li>
        </ul>

        <h3>6.2 Timeline for Reporting</h3>
        <ul>
            <li>STR to be filed within 7 days of identifying suspicious activity</li>
            <li>CTR to be filed within 15 days of month-end</li>
            <li>Non-disclosure to customers about STR filing (tipping off is prohibited)</li>
        </ul>

        <h3>6.3 Internal Escalation Process</h3>
        <ol>
            <li>Suspicious activity identified by any team member</li>
            <li>Reported to Compliance Officer within 24 hours</li>
            <li>Compliance Officer reviews and investigates</li>
            <li>Decision on filing STR made within 3 days</li>
            <li>STR filed with FIU-IND if criteria met</li>
            <li>Documentation maintained for audit trail</li>
        </ol>
    </section>

    <section>
        <h2>7. Record Keeping</h2>
        <h3>7.1 Record Retention Policy</h3>
        <p>We maintain records for the following periods:</p>
        <ul>
            <li><strong>KYC Documents:</strong> 10 years after account closure</li>
            <li><strong>Transaction Records:</strong> 10 years after transaction completion</li>
            <li><strong>Correspondence:</strong> 10 years after communication</li>
            <li><strong>STR Reports:</strong> 10 years from date of filing</li>
            <li><strong>Investigation Records:</strong> 10 years from conclusion</li>
        </ul>

        <h3>7.2 Record Storage</h3>
        <p>Records are maintained in:</p>
        <ul>
            <li>Encrypted digital format with secure backups</li>
            <li>Restricted access based on role and need-to-know</li>
            <li>Audit trail for all access and modifications</li>
            <li>Compliance with data protection regulations</li>
        </ul>

        <h3>7.3 Record Availability</h3>
        <p>Records are made available to:</p>
        <ul>
            <li>SEBI and other regulatory authorities upon request</li>
            <li>Law enforcement agencies with proper authorization</li>
            <li>Internal and external auditors</li>
            <li>FIU-IND for investigation purposes</li>
        </ul>
    </section>

    <section>
        <h2>8. Compliance Structure</h2>
        <h3>8.1 Principal Officer</h3>
        <p>We have designated a Principal Officer responsible for:</p>
        <ul>
            <li>Overall AML/CFT compliance</li>
            <li>Filing STR and CTR reports</li>
            <li>Interface with FIU-IND and regulatory authorities</li>
            <li>Ensuring policy implementation</li>
            <li>Staff training and awareness</li>
        </ul>

        <h3>8.2 Compliance Team</h3>
        <p>Dedicated compliance team handles:</p>
        <ul>
            <li>KYC verification and approval</li>
            <li>Transaction monitoring and review</li>
            <li>Investigation of suspicious activities</li>
            <li>Regulatory reporting</li>
            <li>Record maintenance</li>
        </ul>

        <h3>8.3 Internal Audit</h3>
        <p>Regular internal audits are conducted to:</p>
        <ul>
            <li>Verify compliance with AML/KYC policies</li>
            <li>Test effectiveness of controls</li>
            <li>Identify gaps and recommend improvements</li>
            <li>Ensure regulatory requirements are met</li>
        </ul>
    </section>

    <section>
        <h2>9. Employee Training</h2>
        <h3>9.1 Training Program</h3>
        <p>All employees undergo mandatory training on:</p>
        <ul>
            <li>AML/CFT laws and regulations</li>
            <li>KYC procedures and documentation requirements</li>
            <li>Red flags and suspicious activity identification</li>
            <li>Reporting procedures and obligations</li>
            <li>Consequences of non-compliance</li>
        </ul>

        <h3>9.2 Training Frequency</h3>
        <ul>
            <li><strong>New Employees:</strong> Training within 30 days of joining</li>
            <li><strong>All Staff:</strong> Annual refresher training</li>
            <li><strong>Compliance Team:</strong> Quarterly specialized training</li>
            <li><strong>Updates:</strong> Ad-hoc training when regulations change</li>
        </ul>
    </section>

    <section>
        <h2>10. Risk Assessment</h2>
        <h3>10.1 Enterprise-Wide Risk Assessment</h3>
        <p>We conduct annual risk assessments covering:</p>
        <ul>
            <li>Customer risk (type of customers, geographic location)</li>
            <li>Product/Service risk (investment products offered)</li>
            <li>Channel risk (online platform, payment methods)</li>
            <li>Geographic risk (jurisdictions of operation)</li>
        </ul>

        <h3>10.2 Risk Mitigation</h3>
        <p>Based on risk assessment, we implement:</p>
        <ul>
            <li>Enhanced controls for high-risk areas</li>
            <li>Additional monitoring and reporting</li>
            <li>Restriction of services to high-risk categories</li>
            <li>Enhanced due diligence procedures</li>
        </ul>
    </section>

    <section>
        <h2>11. Sanctions and Restrictive Measures</h2>
        <h3>11.1 Sanctions Screening</h3>
        <p>We screen all customers and transactions against:</p>
        <ul>
            <li>United Nations Security Council sanctions lists</li>
            <li>Financial Action Task Force (FATF) high-risk jurisdictions</li>
            <li>Government of India sanctions lists</li>
            <li>Terrorist watch lists</li>
        </ul>

        <h3>11.2 Prohibited Jurisdictions</h3>
        <p>We do not facilitate transactions with:</p>
        <ul>
            <li>FATF blacklisted countries</li>
            <li>Countries subject to UN sanctions</li>
            <li>High-risk jurisdictions identified by Indian government</li>
        </ul>
    </section>

    <section>
        <h2>12. Technology and Automation</h2>
        <h3>12.1 AML Software Systems</h3>
        <p>We utilize technology for:</p>
        <ul>
            <li>Automated KYC verification (Aadhaar, PAN, bank account)</li>
            <li>Real-time transaction monitoring</li>
            <li>Sanctions screening</li>
            <li>Pattern recognition and anomaly detection</li>
            <li>Report generation and filing</li>
        </ul>

        <h3>12.2 Data Security</h3>
        <p>Customer data is protected through:</p>
        <ul>
            <li>End-to-end encryption</li>
            <li>Secure data storage with regular backups</li>
            <li>Access controls and authentication</li>
            <li>Regular security audits and penetration testing</li>
        </ul>
    </section>

    <section>
        <h2>13. Customer Obligations</h2>
        <p>Customers are required to:</p>
        <ul>
            <li>Provide accurate and complete KYC information</li>
            <li>Update information when changes occur</li>
            <li>Respond to verification requests within stipulated timeframes</li>
            <li>Cooperate with additional due diligence if required</li>
            <li>Use the platform only for legitimate investment purposes</li>
            <li>Not engage in money laundering or terrorist financing</li>
            <li>Ensure funds used for investment are from legitimate sources</li>
        </ul>
    </section>

    <section>
        <h2>14. Consequences of Non-Compliance</h2>
        <h3>14.1 For Customers</h3>
        <p>Failure to comply with KYC requirements may result in:</p>
        <ul>
            <li>Account opening rejection</li>
            <li>Suspension or freezing of account</li>
            <li>Restriction of transactions</li>
            <li>Account closure</li>
            <li>Reporting to law enforcement agencies</li>
        </ul>

        <h3>14.2 For PreIPO SIP</h3>
        <p>Non-compliance with AML/KYC regulations may result in:</p>
        <ul>
            <li>SEBI penalties and enforcement actions</li>
            <li>Suspension or cancellation of SEBI registration</li>
            <li>Criminal prosecution under PMLA</li>
            <li>Reputational damage</li>
            <li>Financial losses</li>
        </ul>
    </section>

    <section>
        <h2>15. Policy Review and Updates</h2>
        <p>This AML & KYC Policy is reviewed and updated:</p>
        <ul>
            <li>Annually as a matter of course</li>
            <li>When regulations change</li>
            <li>After significant incidents or audit findings</li>
            <li>Based on evolving risk landscape</li>
        </ul>
        <p>Updates are communicated to all stakeholders and published on our platform.</p>
    </section>

    <section>
        <h2>16. Contact Information</h2>
        <h3>16.1 Principal Officer Details</h3>
        <ul>
            <li><strong>Name:</strong> [To be designated]</li>
            <li><strong>Email:</strong> compliance@preiposip.com</li>
            <li><strong>Phone:</strong> +91-22-XXXX-XXXX</li>
        </ul>

        <h3>16.2 General Compliance Queries</h3>
        <ul>
            <li><strong>Email:</strong> aml-kyc@preiposip.com</li>
            <li><strong>Support Email:</strong> support@preiposip.com</li>
            <li><strong>Address:</strong> PreIPO SIP Private Limited, Office No. 14, 2nd Floor, Crystal Business Park, Near Golden Nest Road, Mira Bhayandar (East), Thane, Maharashtra – 401107, India</li>
        </ul>
    </section>

    <section>
        <h2>17. Regulatory References</h2>
        <ul>
            <li>Prevention of Money Laundering Act, 2002</li>
            <li>Prevention of Money Laundering (Maintenance of Records) Rules, 2005</li>
            <li>SEBI circular SEBI/HO/MIRSD/DOP/CIR/P/2018/73 dated April 20, 2018</li>
            <li>Master Circular on KYC (Know Your Client) / AML (Anti-Money Laundering) Standards / CFT (Combating Financing of Terrorism) / Obligations</li>
            <li>FATF Recommendations on combating money laundering and terrorist financing</li>
            <li>RBI Master Direction on Know Your Customer (KYC) Direction, 2016</li>
        </ul>
    </section>

    <hr>
    <p class="footer-note"><strong>Declaration:</strong> PreIPO SIP is committed to the highest standards of AML compliance and financial integrity. This policy reflects our dedication to preventing financial crime and protecting the Indian financial system.</p>
    <p class="footer-note"><em>Last Updated: January 15, 2025 | Version 1.0.0</em></p>
</div>
HTML;
    }

    private function getPrivacyPolicyContent(): string
    {
        return <<<'HTML'
<div class="legal-document">
    <h1>Privacy Policy</h1>
    <p class="effective-date"><strong>Effective Date:</strong> January 15, 2025</p>
    <p class="last-updated"><strong>Last Updated:</strong> January 15, 2025</p>

    <section>
        <h2>1. Overview</h2>
        <p>At PreIPO SIP, we take your privacy seriously. This Privacy Policy explains how Pre IPO SIP Private Limited ("we," "us," or "our") collects, uses, discloses, and safeguards your information when you visit our website or use our services. Please read this privacy policy carefully. If you do not agree with the terms of this privacy policy, please do not access the site.</p>
    </section>

    <section>
        <h2>2. Information We Collect</h2>
        <h3>2.1 Personal Data</h3>
        <p>We collect personally identifiable information that you voluntarily provide to us when registering on the platform, expressing interest in obtaining information about us or our products and services, or otherwise contacting us. This includes:</p>
        <ul>
            <li><strong>Identity Data:</strong> Name, date of birth, PAN card details, Aadhaar number, photographs.</li>
            <li><strong>Contact Data:</strong> Email address, mobile number, residential address.</li>
            <li><strong>Financial Data:</strong> Bank account details, payment instrument details, investment history.</li>
        </ul>

        <h3>2.2 Derivative Data</h3>
        <p>Information our servers automatically collect when you access the Site, such as your IP address, your browser type, your operating system, your access times, and the pages you have viewed directly before and after accessing the Site.</p>
    </section>

    <section>
        <h2>3. Use of Your Information</h2>
        <p>Having accurate information about you permits us to provide you with a smooth, efficient, and customized experience. Specifically, we may use information collected about you via the Site to:</p>
        <ul>
            <li>Create and manage your account.</li>
            <li>Process your transactions and investments.</li>
            <li>Verify your identity in compliance with KYC/AML regulations.</li>
            <li>Email you regarding your account or order status.</li>
            <li>Fulfill and manage purchases, orders, payments, and other transactions related to the Site.</li>
            <li>Prevent fraudulent transactions, monitor against theft, and protect against criminal activity.</li>
        </ul>
    </section>

    <section>
        <h2>4. Disclosure of Your Information</h2>
        <p>We may share information we have collected about you in certain situations. Your information may be disclosed as follows:</p>
        
        <h3>4.1 By Law or to Protect Rights</h3>
        <p>If we believe the release of information about you is necessary to respond to legal process, to investigate or remedy potential violations of our policies, or to protect the rights, property, and safety of others, we may share your information as permitted or required by any applicable law, rule, or regulation.</p>
        
        <h3>4.2 Third-Party Service Providers</h3>
        <p>We may share your information with third parties that perform services for us or on our behalf, including payment processing, data analysis, email delivery, hosting services, customer service, and marketing assistance.</p>
    </section>

    <section>
        <h2>5. Security of Your Information</h2>
        <p>We use administrative, technical, and physical security measures to help protect your personal information. While we have taken reasonable steps to secure the personal information you provide to us, please be aware that despite our efforts, no security measures are perfect or impenetrable, and no method of data transmission can be guaranteed against any interception or other type of misuse.</p>
    </section>

    <section>
        <h2>6. Policy for Children</h2>
        <p>We do not knowingly solicit information from or market to children under the age of 18. If you become aware of any data we have collected from children under age 18, please contact us using the contact information provided below.</p>
    </section>

    <section>
        <h2>7. Contact Us</h2>
        <p>If you have questions or comments about this Privacy Policy, please contact us at:</p>
        <ul>
            <li><strong>Email:</strong> legal@preiposip.com</li>
            <li><strong>Address:</strong> PreIPO SIP Private Limited, Office No. 14, 2nd Floor, Crystal Business Park, Near Golden Nest Road, Mira Bhayandar (East), Thane, Maharashtra – 401107, India</li>
        </ul>
    </section>

    <hr>
    <p class="footer-note"><em>Last Updated: January 15, 2025 | Version 1.0.0</em></p>
</div>
HTML;
    }

    private function getCookiePolicyContent(): string
    {
        return <<<'HTML'
<div class="legal-document">
    <h1>Cookie Policy</h1>
    <p class="effective-date"><strong>Effective Date:</strong> January 15, 2025</p>
    <p class="last-updated"><strong>Last Updated:</strong> January 15, 2025</p>

    <section>
        <h2>1. Introduction</h2>
        <p>This Cookie Policy explains how PreIPO SIP ("we", "us", or "our") uses cookies and similar technologies to recognize you when you visit our website. It explains what these technologies are and why we use them, as well as your rights to control our use of them.</p>
    </section>

    <section>
        <h2>2. What are Cookies?</h2>
        <p>Cookies are small data files that are placed on your computer or mobile device when you visit a website. Cookies are widely used by website owners in order to make their websites work, or to work more efficiently, as well as to provide reporting information.</p>
    </section>

    <section>
        <h2>3. Why We Use Cookies</h2>
        <p>We use first-party and third-party cookies for several reasons. Some cookies are required for technical reasons in order for our Website to operate, and we refer to these as "essential" or "strictly necessary" cookies. Other cookies also enable us to track and target the interests of our users to enhance the experience on our Online Properties.</p>
    </section>

    <section>
        <h2>4. Types of Cookies Used</h2>
        <ul>
            <li><strong>Essential Cookies:</strong> These cookies are strictly necessary to provide you with services available through our Website and to use some of its features, such as access to secure areas.</li>
            <li><strong>Performance and Functionality Cookies:</strong> These cookies are used to enhance the performance and functionality of our Website but are non-essential to their use. However, without these cookies, certain functionality (like videos) may become unavailable.</li>
            <li><strong>Analytics and Customization Cookies:</strong> These cookies collect information that is used either in aggregate form to help us understand how our Website is being used or how effective our marketing campaigns are, or to help us customize our Website for you.</li>
        </ul>
    </section>

    <section>
        <h2>5. Managing Cookies</h2>
        <p>You have the right to decide whether to accept or reject cookies. You can exercise your cookie rights by setting your browser controls to accept or refuse cookies. If you choose to reject cookies, you may still use our website though your access to some functionality and areas of our website may be restricted.</p>
    </section>

    <section>
        <h2>6. Updates to This Policy</h2>
        <p>We may update this Cookie Policy from time to time in order to reflect, for example, changes to the cookies we use or for other operational, legal, or regulatory reasons. Please therefore re-visit this Cookie Policy regularly to stay informed about our use of cookies and related technologies.</p>
    </section>

    <hr>
    <p class="footer-note"><em>Last Updated: January 15, 2025 | Version 1.0.0</em></p>
</div>
HTML;
    }

    private function getRefundPolicyContent(): string
    {
        return <<<'HTML'
<div class="legal-document">
    <h1>Refund Policy</h1>
    <p class="effective-date"><strong>Effective Date:</strong> January 15, 2025</p>
    <p class="last-updated"><strong>Last Updated:</strong> January 15, 2025</p>

    <section>
        <h2>1. General Principle</h2>
        <p>PreIPO SIP facilitates investments in financial securities. Due to the nature of financial markets and regulatory requirements, investments once executed are generally non-refundable. However, we have specific policies to handle failed transactions, errors, and exceptional circumstances.</p>
    </section>

    <section>
        <h2>2. Failed Transactions</h2>
        <p>If a payment amount has been deducted from your bank account but the transaction status is shown as 'failed' or 'pending' on our platform, please do not worry. The amount will be automatically refunded to your source bank account within <strong>5-7 working days</strong>, depending on your bank's policy.</p>
    </section>

    <section>
        <h2>3. Cancellations</h2>
        <h3>3.1 Before Execution</h3>
        <p>Investment orders placed on the platform can be cancelled only before the order has been processed or executed. Once the order is in the 'Processing' or 'Completed' state, it cannot be cancelled.</p>
        
        <h3>3.2 After Execution</h3>
        <p>Once shares or units have been allocated to you, the transaction is final and cannot be cancelled or refunded. You may, however, choose to exit the investment by selling the securities, subject to liquidity and applicable laws.</p>
    </section>

    <section>
        <h2>4. Subscription Fees</h2>
        <p>Any subscription fees paid for premium platform access are non-refundable. If you cancel your subscription, you will continue to have access until the end of your current billing period, but no refund will be issued for the remaining period.</p>
    </section>

    <section>
        <h2>5. Dispute Resolution</h2>
        <p>If you believe an error has occurred in transaction processing, please contact our support team immediately at <a href="mailto:support@preiposip.com">support@preiposip.com</a> with the transaction details. We will investigate and resolve the issue within 7 business days.</p>
    </section>

    <hr>
    <p class="footer-note"><em>Last Updated: January 15, 2025 | Version 1.0.0</em></p>
</div>
HTML;
    }

    private function getRiskDisclosureContent(): string
    {
        return <<<'HTML'
<div class="legal-document">
    <h1>Risk Disclosure Document</h1>
    <p class="effective-date"><strong>Effective Date:</strong> January 15, 2025</p>
    <p class="last-updated"><strong>Last Updated:</strong> January 15, 2025</p>

    <section>
        <h2>1. Introduction</h2>
        <p>Investing in unlisted securities, pre-IPO shares, and private equity involves a high degree of risk. This document outlines the key risks associated with such investments. You should carefully consider these risks before making any investment decision.</p>
    </section>

    <section>
        <h2>2. Market and Liquidity Risks</h2>
        <h3>2.1 Liquidity Risk</h3>
        <p>Unlisted securities are not traded on recognized stock exchanges. There is no guarantee that a liquid market will develop for these securities. You may not be able to sell your shares quickly or at the price you desire. You should be prepared to hold these investments for an indefinite period.</p>

        <h3>2.2 Valuation Risk</h3>
        <p>The price of unlisted securities is not determined by market forces on a public exchange. Valuations are often based on internal estimates, last round funding prices, or third-party reports, which may not reflect the true realizable value of the asset.</p>
    </section>

    <section>
        <h2>3. Company and Business Risks</h2>
        <h3>3.1 Early-Stage Risk</h3>
        <p>Many unlisted companies are in early or growth stages. They may face significant challenges, including lack of profitability, uncertain business models, and intense competition. There is a risk of total capital loss if the company fails.</p>

        <h3>3.2 Information Asymmetry</h3>
        <p>Unlike public companies, unlisted companies are not required to disclose as much information. You may have access to less data regarding the company's financial health, operations, and management than you would for a publicly traded company.</p>
    </section>

    <section>
        <h2>4. Regulatory Risks</h2>
        <p>The regulatory environment for unlisted investments is subject to change. Changes in laws, taxation policies, or SEBI regulations could adversely affect the value of your investment or your ability to exit.</p>
    </section>

    <section>
        <h2>5. No Guarantee of Returns</h2>
        <p>PreIPO SIP does not guarantee any returns on investment. Past performance of any company or sector is not indicative of future results. You may lose some or all of your invested capital.</p>
    </section>

    <section>
        <h2>6. Acknowledgment</h2>
        <p>By proceeding with investments on PreIPO SIP, you acknowledge that you have read this Risk Disclosure Document, understood the risks involved, and are making investment decisions based on your own judgment and risk appetite.</p>
    </section>

    <hr>
    <p class="footer-note"><em>Last Updated: January 15, 2025 | Version 1.0.0</em></p>
</div>
HTML;
    }
}
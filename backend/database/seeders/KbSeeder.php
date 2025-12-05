<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KbCategory;
use App\Models\KbArticle;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class KbSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();

        DB::table('kb_article_views')->truncate();
        DB::table('article_feedbacks')->truncate();
        KbArticle::truncate();
        KbCategory::truncate();

        Schema::enableForeignKeyConstraints();

        $admin = User::role('admin')->first() ?? User::first();

        // -------------------------
        // UPDATED STRUCTURE (PART 1)
        // -------------------------
        $structure = [

            // -----------------------------------
            // CATEGORY: Getting Started (icon: book)
            // -----------------------------------
            [
                'name' => 'Getting Started',
                'icon' => 'book',
                'articles' => [
                    [
                        'title' => 'What is PreIPOsip?',
                        'summary' => 'Overview of PreIPOsip and what we offer.',
                        'last_updated' => '2025-11-20',
                        'content' => "PreIPOsip is a specialised fintech platform that helps retail and accredited investors participate in pre-IPO opportunities and manage recurring investments through SIP-like structures. This article explains our mission, product pillars (pre-IPO access, SIP automation, portfolio tracking), and the value we deliver: curated deal flow, compliance-first onboarding, and transparent fees.\n\nWhy it matters: pre-IPO opportunities historically had high barriers; PreIPOsip lowers them with standardized documentation, eligibility checks, and fractional allocation models. Read on for quick-start steps, eligibility rules, and links to deeper articles."
                    ],
                    [
                        'title' => 'How to create an account',
                        'summary' => 'Step-by-step sign up guide.',
                        'last_updated' => '2025-11-25',
                        'content' => "Creating an account on PreIPOsip is fast and secure. This article walks you through required information (email, phone, PAN/KYC for India, or national ID for other supported countries), how to choose between an individual or business account, and verification timelines.\n\nBest practices: use a personal email, keep documents handy, and follow the selfie/photo guidance for faster approval. If you encounter issues, contact support and include screenshots and error messages; our team usually responds within one business day."
                    ],
                    [
                        'title' => 'Supported countries & regions',
                        'summary' => 'Where PreIPOsip operates.',
                        'last_updated' => '2025-10-01',
                        'content' => "PreIPOsip currently supports users in India and select international jurisdictions where we maintain compliant offering structures. This article lists supported countries, regional restrictions, and how tax and regulatory differences affect product availability.\n\nIf your country is not listed: subscribe to our waiting list. Institutional or high-net-worth investors can request early access by contacting enterprise@preiposip.com."
                    ],
                    [
                        'title' => 'System requirements',
                        'summary' => 'App and browser requirements.',
                        'last_updated' => '2025-09-02',
                        'content' => "PreIPOsip supports modern browsers (latest stable Chrome, Firefox, Safari) and mobile apps on iOS and Android. Minimum recommended: iOS 14+, Android 10+, and JavaScript enabled. For best experience, keep your app updated.\n\nTroubleshooting tips: clear cache or reinstall if you see layout issues, and ensure you have a stable internet connection for trade and KYC flows."
                    ],
                    [
                        'title' => 'Tour of the dashboard',
                        'summary' => 'Understanding the main screens.',
                        'last_updated' => '2025-11-05',
                        'content' => "The dashboard is your control center: portfolio summary, upcoming pre-IPO offerings, active SIPs, and alerts. This article breaks down each panel, how to filter holdings, read allocation statuses, and configure widgets.\n\nPro tips: pin frequently used panels, enable push alerts for allocation announcements, and use the performance chart to view realized/unrealized gains over time."
                    ],
                    [
                        'title' => 'Glossary: common terms',
                        'summary' => 'Definitions of terms used across the app.',
                        'last_updated' => '2025-11-15',
                        'content' => "This glossary explains terms you will see across PreIPOsip: Pre-IPO, Allocation, Lock-in, SIP (Systematic Investment Plan), Minimum Bid, KYC, UPI Mandate, and more. Each entry includes a plain-language definition and an example.\n\nWhy it's useful: fintech and capital markets use specialized vocabulary. Familiarity reduces mistakes during onboarding and bidding."
                    ],
                    [
                        'title' => 'How to read allocation results',
                        'summary' => 'Understanding allocation statements and next steps.',
                        'last_updated' => '2025-11-12',
                        'content' => "After an offering closes, the allocation statement explains how many shares were allotted to you and why. This article explains prorata vs lottery allocation, settlement timelines, and how to view allocations in your account.\n\nNext steps: check blocking or ledger entries, review tax implications, and set alerts for listing/lockup expiry dates."
                    ],
                    [
                        'title' => 'How to use pre-IPO filters & alerts',
                        'summary' => 'Customizing deal discovery to your needs.',
                        'last_updated' => '2025-11-28',
                        'content' => "Use filters to sort pre-IPO deals by sector, minimum investment, stage, or geography. Alerts notify you about upcoming bids or eligibility changes. This article shows how to save searches, set allocation thresholds, and receive push/email notifications.\n\nRecommendation: enable high-priority alerts for deals you want to actively bid on and use research notes to assess risk."
                    ],
                ]
            ],

            // ----------------------------------------
            // CATEGORY: Account & Profile (icon: user)
            // ----------------------------------------
            [
                'name' => 'Account & Profile',
                'icon' => 'user',
                'articles' => [
                    [
                        'title' => 'Verify your email and phone',
                        'summary' => 'How to verify and troubleshoot verification.',
                        'last_updated' => '2025-11-28',
                        'content' => "Verification prevents fraud and speeds support. This article covers validating your email and phone via OTP, dealing with SMS delays, and common pitfalls (blocked SMS, corporate email filters). It also explains how to update contact information.\n\nIf OTPs fail: try using a different network, request voice OTP, or use support to manually verify your account."
                    ],
                    [
                        'title' => 'Change password / reset PIN',
                        'summary' => 'Resetting credentials safely.',
                        'last_updated' => '2025-10-20',
                        'content' => "This article walks through resetting your password securely, enabling 2FA, and best practices for PINs. For password resets, you’ll receive an email link valid for a short time. Use unique passwords and a password manager.\n\nIf locked out: follow the account recovery flow and be ready to provide KYC details to prove identity."
                    ],
                    [
                        'title' => 'Update personal details',
                        'summary' => 'Edit name, address, contact.',
                        'last_updated' => '2025-11-11',
                        'content' => "Keep your personal details up to date to avoid compliance issues. This article explains fields you can edit yourself, which require re-verification (e.g., name change), and how to upload supporting documents.\n\nLegal name changes require certified documents—contact support for assistance with manual checks."
                    ],
                    [
                        'title' => 'Close or deactivate my account',
                        'summary' => 'How to close your account and consequences.',
                        'last_updated' => '2025-09-30',
                        'content' => "Before account closure, withdraw funds, sell holdings, and cancel active SIPs. This article explains the deactivation flow, how long records are kept for compliance, and steps to re-open an account.\n\nNote: some regulatory records must be retained; account deletion may be limited to inactive personal information while transactional records are archived securely."
                    ],
                    [
                        'title' => 'Manage notification preferences',
                        'summary' => 'Switch email/SMS/push notifications on/off.',
                        'last_updated' => '2025-11-01',
                        'content' => "Control alerts for bids, allocations, transfers, and security events. This guide shows how to configure notification channels and set quiet hours. We recommend enabling security and payment alerts at minimum.\n\nTip: for high-volume traders, consider email digests instead of real-time alerts."
                    ],
                    [
                        'title' => 'Invite friends / referral program',
                        'summary' => 'How referrals work and rewards.',
                        'last_updated' => '2025-10-05',
                        'content' => "Learn how to invite friends, track referral status, and redeem rewards. This article lists eligibility, reward caps, and how credits are applied to accounts. Referral programs are subject to terms and may change during promotions."
                    ],
                    [
                        'title' => 'Add team members / business sub-accounts',
                        'summary' => 'Manage access for business accounts.',
                        'last_updated' => '2025-08-22',
                        'content' => "Business accounts can create sub-users with role-based permissions. This article covers roles (viewer, trader, admin), audit logs, and how to revoke access. Use least-privilege principles to reduce operational risk."
                    ],
                    [
                        'title' => 'Account activity logs & export',
                        'summary' => 'View and export your activity history.',
                        'last_updated' => '2025-11-10',
                        'content' => "Access complete activity logs including logins, trades, deposits, and support interactions. This article explains filtering, CSV export, and how to request extended history for tax or audit purposes."
                    ],
                ]
            ],

            // -------------------------------------------------
            // CATEGORY: KYC & Verification (icon: id-card)
            // -------------------------------------------------
            [
                'name' => 'KYC & Verification',
                'icon' => 'id-card',
                'articles' => [
                    [
                        'title' => 'What documents are required for KYC',
                        'summary' => 'List of acceptable documents.',
                        'last_updated' => '2025-11-29',
                        'content' => "We require government-issued ID (PAN/Aadhaar for India), proof of address, and a clear selfie for identity verification. This article lists acceptable formats, image quality tips, and exceptions for business accounts.\n\nPro tip: upload high-resolution scans; avoid photocopies and photos with filters. For businesses, provide incorporation docs and authorized signatory IDs."
                    ],
                    [
                        'title' => 'My KYC was rejected — next steps',
                        'summary' => 'Why KYC fails and how to fix.',
                        'last_updated' => '2025-11-30',
                        'content' => "KYC rejections are commonly due to unclear images, mismatched names, or expired IDs. This article explains how to interpret rejection reasons, steps to resubmit, and how long re-verification takes.\n\nIf repeated rejections occur, contact support with originals or notarized copies for manual review."
                    ],
                    [
                        'title' => 'How long does KYC take?',
                        'summary' => 'Typical verification timeframes.',
                        'last_updated' => '2025-11-18',
                        'content' => "Automated checks typically complete within minutes; manual reviews may take 24–72 hours. This article details timelines for normal and peak periods and explains factors that may delay verification, such as public holidays or regulatory checks."
                    ],
                    [
                        'title' => 'Re-verification / updating documents',
                        'summary' => 'When and how to update KYC info.',
                        'last_updated' => '2025-11-10',
                        'content' => "Update KYC when details change—address, name, or significant account ownership changes. This article covers the re-verification workflow, expected downtime for certain operations, and how to expedite business KYC."
                    ],
                    [
                        'title' => 'KYC for business accounts',
                        'summary' => 'Requirements for entities and businesses.',
                        'last_updated' => '2025-08-15',
                        'content' => "Business KYC requires registration documents, director/beneficial owner IDs, and proof of operational address. This article lists specific documents by jurisdiction and common pitfalls when onboarding entities for pre-IPO participation."
                    ],
                    [
                        'title' => 'Photo & selfie tips for approval',
                        'summary' => 'How to capture acceptable photos.',
                        'last_updated' => '2025-11-12',
                        'content' => "Good lighting, plain background, and a clear, front-facing selfie reduce rejection risk. This article includes step-by-step photo tips and examples of acceptable and unacceptable images to speed approvals."
                    ],
                    [
                        'title' => 'KYC for minors & guardianship accounts',
                        'summary' => 'Opening accounts for minors.',
                        'last_updated' => '2025-07-20',
                        'content' => "Accounts for minors require guardian documentation and additional consent forms. This article explains legal considerations, custodial account flows, and limitations on certain investment products for minors."
                    ],
                    [
                        'title' => 'Privacy & how we store KYC data',
                        'summary' => 'Data handling and retention policies.',
                        'last_updated' => '2025-11-05',
                        'content' => "We store KYC data in encrypted storage and retain records as required by law. This article explains encryption practices, access controls, retention periods, and how users can request data access or deletion requests within legal constraints."
                    ],
                ]
            ],

            // --------------------------------------------------------
            // CATEGORY: Payments & Transfers (icon: rupee)
            // --------------------------------------------------------
            [
                'name' => 'Payments & Transfers',
                'icon' => 'rupee',
                'articles' => [
                    [
                        'title' => 'Deposit funds (bank transfer)',
                        'summary' => 'How to add money via bank transfer.',
                        'last_updated' => '2025-11-21',
                        'content' => "To deposit funds, use the Deposit flow and follow on-screen instructions to generate a reference. Bank transfers are preferred for large amounts — ensure you include the reference code so the deposit is auto-matched.\n\nSettlement times vary by bank. If your deposit is not matched automatically, provide bank transaction screenshots to support for faster reconciliation."
                    ],
                    [
                        'title' => 'Deposit via UPI / instant pay',
                        'summary' => 'Using UPI and instant payment rails.',
                        'last_updated' => '2025-11-22',
                        'content' => "UPI payments are instant and commonly used for small to medium deposits. This article explains setting a UPI VPA, linking UPI to your account, and troubleshooting failed UPI mandates. For recurring SIP top-ups, UPI auto-debit can be configured where supported."
                    ],
                    [
                        'title' => 'Failed or pending deposit',
                        'summary' => 'Troubleshooting failed deposits.',
                        'last_updated' => '2025-11-26',
                        'content' => "Failed deposits can result from wrong reference, bank reversals, or limits. This article shows how to locate transaction IDs, collect bank statements, and submit reconciliation requests. For time-sensitive bids, contact support immediately with proof of payment."
                    ],
                    [
                        'title' => 'Withdraw funds to your bank',
                        'summary' => 'Withdrawal limits and steps.',
                        'last_updated' => '2025-11-23',
                        'content' => "Withdrawals are initiated from the Withdraw page and typically process in 1–3 business days. This article explains withdrawal limits, verification steps, and scenarios causing delays (bank holidays, verification holds). Keep your bank details updated to avoid rejections."
                    ],
                    [
                        'title' => 'Payment limits & fees',
                        'summary' => 'Daily/monthly limits and applicable fees.',
                        'last_updated' => '2025-11-02',
                        'content' => "Limits depend on verification tier. This article lists per-transaction and daily limits for each tier and explains fee structures for deposits, withdrawals, and platform charges. See the fee schedule for the latest rates."
                    ],
                    [
                        'title' => 'Add or change bank account',
                        'summary' => 'How to link and verify bank accounts.',
                        'last_updated' => '2025-10-30',
                        'content' => "To add a bank account, submit account details and complete small-amount verification when required. This article explains micro-deposit verification and the documentation required to change bank accounts securely."
                    ],
                    [
                        'title' => 'UPI mandates & recurring payments',
                        'summary' => 'Setting up recurring top-ups and mandates.',
                        'last_updated' => '2025-09-28',
                        'content' => "UPI mandates allow recurring debits for SIPs. This article covers the mandate setup flow, customer consent requirements, and how to cancel or modify mandates. It also addresses mandate failure modes and retries."
                    ],
                    [
                        'title' => 'International transfers & FX handling',
                        'summary' => 'Cross-border deposits and currency conversion.',
                        'last_updated' => '2025-08-30',
                        'content' => "We support select international transfers. This article explains intermediary bank fees, FX rates, and how to minimize charges. Cross-border settlement may take several business days and require additional KYC for compliance."
                    ],
                ]
            ],

            // ----------------------------------------------------
            // CATEGORY: Investments (icon: trending-up)
            // ----------------------------------------------------
            [
                'name' => 'Investments',
                'icon' => 'trending-up',
                'articles' => [
                    [
                        'title' => 'How IPO allocation works',
                        'summary' => 'Understanding allocation and bidding.',
                        'last_updated' => '2025-11-14',
                        'content' => "IPO and pre-IPO allocations depend on demand, lot size, and allocation policy. This article explains institutional vs retail allocation, pro-rata distribution, lottery mechanisms, and how our fractional allocation model increases accessibility.\n\nLearn how to place bids, set limit prices where applicable, and read allocation notices."
                    ],
                    [
                        'title' => 'Start a SIP for pre-IPO investments',
                        'summary' => 'Setting up recurring investments.',
                        'last_updated' => '2025-11-13',
                        'content' => "Our SIP-like product automates funding for recurring allocations in curated deals. This article explains scheduling, contribution sizes, how funds are reserved per bidding cycle, and how to pause or stop a SIP.\n\nNote: SIP contributions are used for bids and do not guarantee allocation; they increase participation across cycles."
                    ],
                    [
                        'title' => 'Order types and execution',
                        'summary' => 'Market, limit, and conditional orders.',
                        'last_updated' => '2025-09-11',
                        'content' => "Understand order types supported for secondary trading and listed follow-ons. Market orders execute at the best available price; limit orders set a maximum/minimum price. This article details execution priority, partial fills, and cancellation rules."
                    ],
                    [
                        'title' => 'Portfolio breakdown & performance',
                        'summary' => 'Where to see holdings and P&L.',
                        'last_updated' => '2025-10-18',
                        'content' => "This article shows how to read your portfolio dashboard: allocation by asset class, cost basis, unrealized vs realized P&L, and time-weighted returns. It also explains export formats for tax reporting."
                    ],
                    [
                        'title' => 'Risk & suitability assessment',
                        'summary' => 'How we assess product suitability for users.',
                        'last_updated' => '2025-07-01',
                        'content' => "Regulatory frameworks require suitability checks. This article covers the questionnaire we use to gauge risk tolerance, investment horizon, and experience, and how results influence recommended product access."
                    ],
                    [
                        'title' => 'Tax & reporting for investments',
                        'summary' => 'How taxes are handled and statements.',
                        'last_updated' => '2025-11-01',
                        'content' => "We provide downloadable tax statements and transaction reports. This article explains capital gains treatment, tax deduction at source where applicable, and how to find tax-ready reports for your filings."
                    ],
                    [
                        'title' => 'Secondary market vs primary allocations',
                        'summary' => 'Differences between primary and secondary trades.',
                        'last_updated' => '2025-10-12',
                        'content' => "Primary allocations (pre-IPO/IPO) and secondary transactions (after listing) have different liquidity and fee profiles. This article compares timing, lock-up restrictions, and tax implications of each."
                    ],
                    [
                        'title' => 'Syndicate & lead investor participation',
                        'summary' => 'How syndicates and lead investors affect deals.',
                        'last_updated' => '2025-09-05',
                        'content' => "Some pre-IPO rounds include lead investors or syndicates that negotiate terms. This article explains how syndicates work, benefits to participants, and any allocation priority that may exist."
                    ],
                    [
                        'title' => 'Due diligence & research notes',
                        'summary' => 'How we vet offerings and present research.',
                        'last_updated' => '2025-11-20',
                        'content' => "We publish research notes explaining business model, market size, management background, and key risks for each curated deal. This article describes our due diligence checklist and how to interpret risk scores."
                    ],
                    [
                        'title' => 'Lock-up periods & transfer restrictions',
                        'summary' => 'Understanding lock-ups after listing.',
                        'last_updated' => '2025-11-06',
                        'content' => "Many pre-IPO allocations carry lock-up periods during which shares cannot be sold. This article explains typical durations, exceptions, and how lock-ups affect liquidity planning."
                    ],
                ]
            ],
        
        // -----------------------------------------
        // CATEGORY: Security & Fraud (icon: shield)
        // -----------------------------------------
        [
            'name' => 'Security & Fraud',
            'icon' => 'shield',
            'articles' => [
                [
                    'title' => 'How we protect your funds',
                    'summary' => 'Security architecture overview.',
                    'last_updated' => '2025-11-06',
                    'content' => "We protect customer funds using a combination of segregated trust accounts, AML controls, encryption, and access controls. This article explains the separation between custody partners and platform operations and how customer funds are handled during bidding and settlement."
                ],
                [
                    'title' => 'Report fraud or suspicious activity',
                    'summary' => 'How to report and what information we need.',
                    'last_updated' => '2025-11-27',
                    'content' => "If you see unauthorized trades, suspicious emails, or unknown withdrawals, report immediately through the Report Fraud form or contact support. Provide timestamps, transaction IDs, and screenshots to help our investigators."
                ],
                [
                    'title' => 'Two-factor authentication (2FA)',
                    'summary' => 'Enable and recover 2FA.',
                    'last_updated' => '2025-10-12',
                    'content' => "Enable 2FA via SMS or authenticator apps for stronger protection. This article covers how to set up, rotate devices, and recover access if you lose your 2FA device. Recovery requires ID verification for security."
                ],
                [
                    'title' => 'Device & session management',
                    'summary' => 'Sign out sessions and review devices.',
                    'last_updated' => '2025-10-02',
                    'content' => "Review active sessions and revoke access to lost devices. This article explains how sessions are displayed, IP/device metadata, and recommended actions after suspicious access."
                ],
                [
                    'title' => 'Phishing protection tips',
                    'summary' => 'How to avoid phishing and suspicious links.',
                    'last_updated' => '2025-11-03',
                    'content' => "Phishing attempts often mimic official communications. This guide shows how to recognize phishing emails, verify sender authenticity, and report attempts. Remember: we never ask for passwords or 2FA codes by email."
                ],
                [
                    'title' => 'What happens if my account is compromised?',
                    'summary' => 'Immediate steps and support flow.',
                    'last_updated' => '2025-11-16',
                    'content' => "If compromised, immediately freeze your account, change passwords, and contact support. Our incident team will audit transactions, reverse unauthorized moves where possible, and assist with law enforcement if needed."
                ],
                [
                    'title' => 'Security best practices for investors',
                    'summary' => 'How to reduce risk as a user.',
                    'last_updated' => '2025-11-01',
                    'content' => "Use unique passwords, enable 2FA, be cautious with public Wi-Fi, and verify URLs before entering credentials. This article provides a checklist to reduce the likelihood of fraud."
                ],
                [
                    'title' => 'Data encryption & privacy controls',
                    'summary' => 'How we encrypt and let you control your data.',
                    'last_updated' => '2025-11-02',
                    'content' => "We use TLS in transit and AES-256 at rest. This article explains user controls—data export, consent revocation, and how to request deletion subject to regulatory retention requirements."
                ],
            ]
        ],

        // ---------------------------------------------
        // CATEGORY: Fees & Charges (icon: receipt)
        // ---------------------------------------------
        [
            'name' => 'Fees & Charges',
            'icon' => 'receipt',
            'articles' => [
                [
                    'title' => 'Fee schedule overview',
                    'summary' => 'All fees in one place.',
                    'last_updated' => '2025-11-08',
                    'content' => "This article lists platform fees, transaction fees, custody charges, and any performance or success fees for certain offerings. It explains how fees are applied and where to find historical fee invoices for reconciliation and tax reporting."
                ],
                [
                    'title' => 'Taxes & GST on services',
                    'summary' => 'How taxes are applied.',
                    'last_updated' => '2025-10-25',
                    'content' => "Taxes depend on jurisdiction and product type. This article outlines how GST/VAT and other tax components are applied, and advises consulting a tax professional for personal tax obligations."
                ],
                [
                    'title' => 'How refunds are processed',
                    'summary' => 'Refund timing and methods.',
                    'last_updated' => '2025-09-20',
                    'content' => "Refunds are processed back to the originating payment method and may take 3–10 business days depending on banks. This article explains exceptions and timelines for chargebacks or disputed bids."
                ],
                [
                    'title' => 'Discounts, promos, and rebates',
                    'summary' => 'Promotional pricing rules.',
                    'last_updated' => '2025-11-07',
                    'content' => "Promotions may apply to fees or offer credits. This article explains eligibility, stacking rules, and how promotional credits appear on the account. Promotions are time-limited and governed by specific terms."
                ],
                [
                    'title' => 'Transparency report & fee calculator',
                    'summary' => 'Estimate costs for trades and allocations.',
                    'last_updated' => '2025-10-01',
                    'content' => "Use our fee calculator to estimate net proceeds and costs for participating in deals. This article explains inputs—allocation size, expected lock-up, and estimated fees—to help users make informed decisions."
                ],
            ]
        ],

        // ---------------------------------------------------
        // CATEGORY: Legal & Compliance (icon: scale)
        // ---------------------------------------------------
        [
            'name' => 'Legal & Compliance',
            'icon' => 'scale',
            'articles' => [
                [
                    'title' => 'Terms & Conditions (summary)',
                    'summary' => 'Plain-language summary of T&C.',
                    'last_updated' => '2025-06-01',
                    'content' => "This plain-language summary highlights key points from our Terms & Conditions: user eligibility, account responsibilities, dispute resolution, and limitation of liability. The full legal T&C remains the definitive document for legal matters."
                ],
                [
                    'title' => 'Privacy Policy (summary)',
                    'summary' => 'Data handling & user rights.',
                    'last_updated' => '2025-06-01',
                    'content' => "Our Privacy Policy explains what we collect, why, and how users can exercise rights (access, correction, portability). This article summarizes data types collected and how we use data to provide services and comply with regulations."
                ],
                [
                    'title' => 'Grievance redressal process',
                    'summary' => 'How to file a complaint and escalation matrix.',
                    'last_updated' => '2025-08-10',
                    'content' => "If you have a complaint, follow the grievance process: file via Support > File a complaint, receive an acknowledgement, and follow escalation steps (manager review, regulator complaint) if unresolved. This article lists contact points and expected SLAs."
                ],
                [
                    'title' => 'Regulatory disclosures & licenses',
                    'summary' => 'Licenses, registrations, and disclaimers.',
                    'last_updated' => '2025-05-15',
                    'content' => "We disclose licenses and regulatory registrations relevant to the jurisdictions in which we operate. This article lists current licenses, regulatory partners, and important disclaimers about investment risks and non-guaranteed returns."
                ],
                [
                    'title' => 'Investor agreements & docs',
                    'summary' => 'Where to find legal documentation for allocations.',
                    'last_updated' => '2025-11-01',
                    'content' => "Allocated deals include investor agreements and term sheets. This article explains typical documents provided post-allocation, how to sign electronically, and where copies are archived in your account."
                ],
                [
                    'title' => 'KYC-related legal obligations',
                    'summary' => 'Why we collect certain information.',
                    'last_updated' => '2025-07-10',
                    'content' => "KYC is legally required to prevent money laundering and fraud. This article outlines obligations for both users and the platform, and explains when enhanced due diligence may be triggered."
                ],
            ]
        ],

        // ------------------------------------------------
        // CATEGORY: Troubleshooting (icon: alert)
        // ------------------------------------------------
        [
            'name' => 'Troubleshooting',
            'icon' => 'alert',
            'articles' => [
                [
                    'title' => 'App crashes or freezes',
                    'summary' => 'Steps to resolve app instability.',
                    'last_updated' => '2025-11-09',
                    'content' => "If the app crashes, try clearing cache, updating the app, and restarting the device. This article provides step-by-step diagnostics, how to collect logs, and when to escalate to engineering. Include OS version and reproduction steps for faster support."
                ],
                [
                    'title' => 'Unable to login (locked out)',
                    'summary' => 'Recovering access and unlocking accounts.',
                    'last_updated' => '2025-11-19',
                    'content' => "Account lockouts can occur after multiple failed logins. Use the 'Forgot password' flow or contact support with KYC details for manual unlocking. This article details required identity proofs and expected SLAs."
                ],
                [
                    'title' => 'Receiving incorrect balances',
                    'summary' => 'Why balances might be delayed and how to refresh.',
                    'last_updated' => '2025-11-17',
                    'content' => "Balance mismatches often occur due to unsettled trades or pending deposits. This article explains how to refresh data, where to find ledger entries, and how to raise a reconciliation request with supporting bank/reference IDs."
                ],
                [
                    'title' => 'Transaction confirmation not received',
                    'summary' => 'What to check when confirmations are missing.',
                    'last_updated' => '2025-11-24',
                    'content' => "If confirmations are missing, check spam folders, SMS history, and notification preferences. This article guides how to manually retrieve transaction receipts and provides troubleshooting steps for common email delivery issues."
                ],
                [
                    'title' => 'Error codes: meanings & fixes',
                    'summary' => 'Common error codes and what they mean.',
                    'last_updated' => '2025-11-04',
                    'content' => "This article lists common platform error codes, short explanations, and actionable fixes. When contacting support, include the error code and timestamp to expedite resolution."
                ],
                [
                    'title' => 'How to contact support (chat, email, phone)',
                    'summary' => 'Contact options and expected SLAs.',
                    'last_updated' => '2025-11-29',
                    'content' => "Support channels include in-app chat, email, and phone for critical incidents. This article lists operating hours, SLA expectations, and what to include in tickets (screenshots, transaction IDs) for faster handling."
                ],
                [
                    'title' => 'Fixing KYC upload failures',
                    'summary' => 'Troubleshoot upload and file format issues.',
                    'last_updated' => '2025-11-30',
                    'content' => "If KYC uploads fail, check file size, format (JPEG/PNG/PDF), and image clarity. This troubleshooting guide walks through compressing images safely and alternatives for manual review."
                ],
                [
                    'title' => 'App notifications not arriving',
                    'summary' => 'Push and email issues.',
                    'last_updated' => '2025-11-12',
                    'content' => "If notifications aren’t arriving, verify app permissions, battery optimizations, and notification settings. This article includes platform-specific steps for Android and iOS and how to test notifications."
                ],
                [
                    'title' => 'Payment reconciliation checklist',
                    'summary' => 'What to provide for payment disputes.',
                    'last_updated' => '2025-11-26',
                    'content' => "To reconcile payments, include payment reference, bank UTR, screenshots, timestamp, and originating account. This article provides a downloadable checklist to speed dispute resolution."
                ],
                [
                    'title' => 'How to escalate an unresolved issue',
                    'summary' => 'Escalation path and timelines.',
                    'last_updated' => '2025-10-15',
                    'content' => "If support doesn’t resolve your issue, follow the escalation path: senior support → operations manager → regulatory grievance. This article lists contact points, expected timelines, and necessary documentation for escalations."
                ],
            ]
        ],

        // ---------------------------------------------
        // CATEGORY: Resources (icon: library)
        // ---------------------------------------------
        [
            'name' => 'Resources',
            'icon' => 'library',
            'articles' => [
                [
                    'title' => 'Investment basics: pre-IPO explained',
                    'summary' => 'Fundamentals for new investors.',
                    'last_updated' => '2025-11-02',
                    'content' => "Pre-IPO investing involves buying equity before a company lists publicly. This primer explains valuation, dilution, lock-ups, and why pre-IPO investments can be high-risk but potentially high-reward. Understand diversification and allocation sizing before participating."
                ],
                [
                    'title' => 'How to build a diversified pre-IPO portfolio',
                    'summary' => 'Practical portfolio construction guidance.',
                    'last_updated' => '2025-10-05',
                    'content' => "Diversify across sectors, stages, and ticket sizes. This article provides allocation examples, risk management techniques, and how to use SIPs to average into opportunities while preserving liquidity for secondary markets."
                ],
                [
                    'title' => 'Tax-efficient strategies for allocations',
                    'summary' => 'Tax-aware investing tips.',
                    'last_updated' => '2025-09-18',
                    'content' => "Tax treatment varies by instrument and holding period. This article outlines basic strategies to manage tax impact, like harvesting losses and planning exits around tax-year timing. Consult your tax advisor for personalized advice."
                ],
                [
                    'title' => 'Reading an investor memo',
                    'summary' => 'How to digest research notes and term sheets.',
                    'last_updated' => '2025-11-11',
                    'content' => "Investor memos summarize business models, KPIs, and risks. This guide explains sections to prioritize and red flags to watch for—such as inconsistent metrics or unclear use of proceeds."
                ],
                [
                    'title' => 'Webinars & events: calendar',
                    'summary' => 'Where to find upcoming educational events.',
                    'last_updated' => '2025-11-14',
                    'content' => "We host regular webinars covering upcoming deals, market trends, and regulatory changes. This article links to the events calendar and recordings for on-demand viewing."
                ],
                [
                    'title' => 'Glossary deep dive: financial terms',
                    'summary' => 'Extended glossary for advanced investors.',
                    'last_updated' => '2025-11-15',
                    'content' => "An extended glossary covering term-sheet clauses, dilution mechanics, liquidation preferences, and cap table concepts—designed for investors who want to read legal and financial documents with confidence."
                ],
            ]
        ],

        // -----------------------------------------
        // CATEGORY: Support (icon: life-ring)
        // -----------------------------------------
        [
            'name' => 'Support',
            'icon' => 'life-ring',
            'articles' => [
                [
                    'title' => 'Support hours and expected response times',
                    'summary' => 'When support is available.',
                    'last_updated' => '2025-11-29',
                    'content' => "Support operates Mon-Fri 9am–9pm IST. Critical issues get expedited handling. This article explains expected response times and how to mark tickets as urgent for operational incidents."
                ],
                [
                    'title' => 'Preparing a support ticket: what we need',
                    'summary' => 'How to write an effective ticket.',
                    'last_updated' => '2025-11-29',
                    'content' => "Include screenshots, steps to reproduce, device info, and transaction references. This article provides a template to copy when contacting support to reduce back-and-forth."
                ],
                [
                    'title' => 'Service Level Agreements (SLA)',
                    'summary' => 'SLAs for different issue types.',
                    'last_updated' => '2025-10-01',
                    'content' => "This article describes our SLAs for account access, deposits, fraudulent activity, and regulatory enquiries—what we commit to and exceptions during peak times."
                ],
                [
                    'title' => 'Enterprise & institutional onboarding',
                    'summary' => 'Onboarding for larger clients.',
                    'last_updated' => '2025-08-01',
                    'content' => "Institutional onboarding includes contract negotiation, enhanced due diligence, and dedicated support. This article describes the process, required documents, and timelines for enterprise customers."
                ],
            ]
        ]
    ]; // <-- FIXED HERE

    // ----------------------------------------------
    // INSERT CATEGORIES + ARTICLES INTO THE DATABASE
    // ----------------------------------------------
    $order = 0;

    foreach ($structure as $catData) {
        $category = KbCategory::create([
            'name' => $catData['name'],
            'slug' => Str::slug($catData['name']),
            'icon' => $catData['icon'],
            'description' => "Help articles regarding {$catData['name']}",
            'display_order' => $order++,
            'is_active' => true,
        ]);

        foreach ($catData['articles'] as $artData) {
            KbArticle::create([
                'kb_category_id' => $category->id,
                'author_id' => $admin->id ?? 1,
                'title' => $artData['title'],
                'slug' => Str::slug($artData['title']),
                'summary' => $artData['summary'],   // <-- RESTORED REAL VALUE
                'last_updated' => $artData['last_updated'], // <-- RESTORED REAL VALUE
                'content' => $artData['content'],
                'status' => 'published',
                'published_at' => now(),
                'views' => rand(25, 200),
            ]);
        }
    }
}
}

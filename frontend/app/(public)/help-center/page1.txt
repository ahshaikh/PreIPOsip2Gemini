"use client";

import React, { useMemo, useState } from "react";

type Article = {
  id: number;
  category: string;
  title: string;
  summary: string;
  lastUpdated: string;
  content: string;
};

const ARTICLES: Article[] = [
  // Getting Started (1-8)
  {
    id: 1,
    category: "Getting Started",
    title: "What is PreIPOsip?",
    summary: "Overview of PreIPOsip and what we offer.",
    lastUpdated: "2025-11-20",
    content: `PreIPOsip is a specialised fintech platform that helps retail and accredited investors participate in pre-IPO opportunities and manage recurring investments through SIP-like structures. This article explains our mission, product pillars (pre-IPO access, SIP automation, portfolio tracking), and the value we deliver: curated deal flow, compliance-first onboarding, and transparent fees.

Why it matters: pre-IPO opportunities historically had high barriers; PreIPOsip lowers them with standardized documentation, eligibility checks, and fractional allocation models. Read on for quick-start steps, eligibility rules, and links to deeper articles.`,
  },
  {
    id: 2,
    category: "Getting Started",
    title: "How to create an account",
    summary: "Step-by-step sign up guide.",
    lastUpdated: "2025-11-25",
    content: `Creating an account on PreIPOsip is fast and secure. This article walks you through required information (email, phone, PAN/KYC for India, or national ID for other supported countries), how to choose between an individual or business account, and verification timelines.

Best practices: use a personal email, keep documents handy, and follow the selfie/photo guidance for faster approval. If you encounter issues, contact support and include screenshots and error messages; our team usually responds within one business day.`,
  },
  {
    id: 3,
    category: "Getting Started",
    title: "Supported countries & regions",
    summary: "Where PreIPOsip operates.",
    lastUpdated: "2025-10-01",
    content: `PreIPOsip currently supports users in India and select international jurisdictions where we maintain compliant offering structures. This article lists supported countries, regional restrictions, and how tax and regulatory differences affect product availability.

If your country is not listed: subscribe to our waiting list. Institutional or high-net-worth investors can request early access by contacting enterprise@preiposip.com.`,
  },
  {
    id: 4,
    category: "Getting Started",
    title: "System requirements",
    summary: "App and browser requirements.",
    lastUpdated: "2025-09-02",
    content: `PreIPOsip supports modern browsers (latest stable Chrome, Firefox, Safari) and mobile apps on iOS and Android. Minimum recommended: iOS 14+, Android 10+, and JavaScript enabled. For best experience, keep your app updated.

Troubleshooting tips: clear cache or reinstall if you see layout issues, and ensure you have a stable internet connection for trade and KYC flows.`,
  },
  {
    id: 5,
    category: "Getting Started",
    title: "Tour of the dashboard",
    summary: "Understanding the main screens.",
    lastUpdated: "2025-11-05",
    content: `The dashboard is your control center: portfolio summary, upcoming pre-IPO offerings, active SIPs, and alerts. This article breaks down each panel, how to filter holdings, read allocation statuses, and configure widgets.

Pro tips: pin frequently used panels, enable push alerts for allocation announcements, and use the performance chart to view realized/unrealized gains over time.`,
  },
  {
    id: 6,
    category: "Getting Started",
    title: "Glossary: common terms",
    summary: "Definitions of terms used across the app.",
    lastUpdated: "2025-11-15",
    content: `This glossary explains terms you will see across PreIPOsip: Pre-IPO, Allocation, Lock-in, SIP (Systematic Investment Plan), Minimum Bid, KYC, UPI Mandate, and more. Each entry includes a plain-language definition and an example.

Why it's useful: fintech and capital markets use specialized vocabulary. Familiarity reduces mistakes during onboarding and bidding.`,
  },
  {
    id: 7,
    category: "Getting Started",
    title: "How to read allocation results",
    summary: "Understanding allocation statements and next steps.",
    lastUpdated: "2025-11-12",
    content: `After an offering closes, the allocation statement explains how many shares were allotted to you and why. This article explains prorata vs lottery allocation, settlement timelines, and how to view allocations in your account.

Next steps: check blocking or ledger entries, review tax implications, and set alerts for listing/lockup expiry dates.`,
  },
  {
    id: 8,
    category: "Getting Started",
    title: "How to use pre-IPO filters & alerts",
    summary: "Customizing deal discovery to your needs.",
    lastUpdated: "2025-11-28",
    content: `Use filters to sort pre-IPO deals by sector, minimum investment, stage, or geography. Alerts notify you about upcoming bids or eligibility changes. This article shows how to save searches, set allocation thresholds, and receive push/email notifications.

Recommendation: enable high-priority alerts for deals you want to actively bid on and use research notes to assess risk.`,
  },

  // Account & Profile (9-16)
  {
    id: 9,
    category: "Account & Profile",
    title: "Verify your email and phone",
    summary: "How to verify and troubleshoot verification.",
    lastUpdated: "2025-11-28",
    content: `Verification prevents fraud and speeds support. This article covers validating your email and phone via OTP, dealing with SMS delays, and common pitfalls (blocked SMS, corporate email filters). It also explains how to update contact information.

If OTPs fail: try using a different network, request voice OTP, or use support to manually verify your account.`,
  },
  {
    id: 10,
    category: "Account & Profile",
    title: "Change password / reset PIN",
    summary: "Resetting credentials safely.",
    lastUpdated: "2025-10-20",
    content: `This article walks through resetting your password securely, enabling 2FA, and best practices for PINs. For password resets, you’ll receive an email link valid for a short time. Use unique passwords and a password manager.

If locked out: follow the account recovery flow and be ready to provide KYC details to prove identity.`,
  },
  {
    id: 11,
    category: "Account & Profile",
    title: "Update personal details",
    summary: "Edit name, address, contact.",
    lastUpdated: "2025-11-11",
    content: `Keep your personal details up to date to avoid compliance issues. This article explains fields you can edit yourself, which require re-verification (e.g., name change), and how to upload supporting documents.

Legal name changes require certified documents—contact support for assistance with manual checks.`,
  },
  {
    id: 12,
    category: "Account & Profile",
    title: "Close or deactivate my account",
    summary: "How to close your account and consequences.",
    lastUpdated: "2025-09-30",
    content: `Before account closure, withdraw funds, sell holdings, and cancel active SIPs. This article explains the deactivation flow, how long records are kept for compliance, and steps to re-open an account.

Note: some regulatory records must be retained; account deletion may be limited to inactive personal information while transactional records are archived securely.`,
  },
  {
    id: 13,
    category: "Account & Profile",
    title: "Manage notification preferences",
    summary: "Switch email/SMS/push notifications on/off.",
    lastUpdated: "2025-11-01",
    content: `Control alerts for bids, allocations, transfers, and security events. This guide shows how to configure notification channels and set quiet hours. We recommend enabling security and payment alerts at minimum.

Tip: for high-volume traders, consider email digests instead of real-time alerts.`,
  },
  {
    id: 14,
    category: "Account & Profile",
    title: "Invite friends / referral program",
    summary: "How referrals work and rewards.",
    lastUpdated: "2025-10-05",
    content: `Learn how to invite friends, track referral status, and redeem rewards. This article lists eligibility, reward caps, and how credits are applied to accounts. Referral programs are subject to terms and may change during promotions.`,
  },
  {
    id: 15,
    category: "Account & Profile",
    title: "Add team members / business sub-accounts",
    summary: "Manage access for business accounts.",
    lastUpdated: "2025-08-22",
    content: `Business accounts can create sub-users with role-based permissions. This article covers roles (viewer, trader, admin), audit logs, and how to revoke access. Use least-privilege principles to reduce operational risk.`,
  },
  {
    id: 16,
    category: "Account & Profile",
    title: "Account activity logs & export",
    summary: "View and export your activity history.",
    lastUpdated: "2025-11-10",
    content: `Access complete activity logs including logins, trades, deposits, and support interactions. This article explains filtering, CSV export, and how to request extended history for tax or audit purposes.`,
  },

  // KYC & Verification (17-24)
  {
    id: 17,
    category: "KYC & Verification",
    title: "What documents are required for KYC",
    summary: "List of acceptable documents.",
    lastUpdated: "2025-11-29",
    content: `We require government-issued ID (PAN/Aadhaar for India), proof of address, and a clear selfie for identity verification. This article lists acceptable formats, image quality tips, and exceptions for business accounts.

Pro tip: upload high-resolution scans; avoid photocopies and photos with filters. For businesses, provide incorporation docs and authorized signatory IDs.`,
  },
  {
    id: 18,
    category: "KYC & Verification",
    title: "My KYC was rejected — next steps",
    summary: "Why KYC fails and how to fix.",
    lastUpdated: "2025-11-30",
    content: `KYC rejections are commonly due to unclear images, mismatched names, or expired IDs. This article explains how to interpret rejection reasons, steps to resubmit, and how long re-verification takes.

If repeated rejections occur, contact support with originals or notarized copies for manual review.`,
  },
  {
    id: 19,
    category: "KYC & Verification",
    title: "How long does KYC take?",
    summary: "Typical verification timeframes.",
    lastUpdated: "2025-11-18",
    content: `Automated checks typically complete within minutes; manual reviews may take 24–72 hours. This article details timelines for normal and peak periods and explains factors that may delay verification, such as public holidays or regulatory checks.`,
  },
  {
    id: 20,
    category: "KYC & Verification",
    title: "Re-verification / updating documents",
    summary: "When and how to update KYC info.",
    lastUpdated: "2025-11-10",
    content: `Update KYC when details change—address, name, or significant account ownership changes. This article covers the re-verification workflow, expected downtime for certain operations, and how to expedite business KYC.`,
  },
  {
    id: 21,
    category: "KYC & Verification",
    title: "KYC for business accounts",
    summary: "Requirements for entities and businesses.",
    lastUpdated: "2025-08-15",
    content: `Business KYC requires registration documents, director/beneficial owner IDs, and proof of operational address. This article lists specific documents by jurisdiction and common pitfalls when onboarding entities for pre-IPO participation.`,
  },
  {
    id: 22,
    category: "KYC & Verification",
    title: "Photo & selfie tips for approval",
    summary: "How to capture acceptable photos.",
    lastUpdated: "2025-11-12",
    content: `Good lighting, plain background, and a clear, front-facing selfie reduce rejection risk. This article includes step-by-step photo tips and examples of acceptable and unacceptable images to speed approvals.`,
  },
  {
    id: 23,
    category: "KYC & Verification",
    title: "KYC for minors & guardianship accounts",
    summary: "Opening accounts for minors.",
    lastUpdated: "2025-07-20",
    content: `Accounts for minors require guardian documentation and additional consent forms. This article explains legal considerations, custodial account flows, and limitations on certain investment products for minors.`,
  },
  {
    id: 24,
    category: "KYC & Verification",
    title: "Privacy & how we store KYC data",
    summary: "Data handling and retention policies.",
    lastUpdated: "2025-11-05",
    content: `We store KYC data in encrypted storage and retain records as required by law. This article explains encryption practices, access controls, retention periods, and how users can request data access or deletion requests within legal constraints.`,
  },

  // Payments & Transfers (25-32)
  {
    id: 25,
    category: "Payments & Transfers",
    title: "Deposit funds (bank transfer)",
    summary: "How to add money via bank transfer.",
    lastUpdated: "2025-11-21",
    content: `To deposit funds, use the Deposit flow and follow on-screen instructions to generate a reference. Bank transfers are preferred for large amounts — ensure you include the reference code so the deposit is auto-matched.

Settlement times vary by bank. If your deposit is not matched automatically, provide bank transaction screenshots to support for faster reconciliation.`,
  },
  {
    id: 26,
    category: "Payments & Transfers",
    title: "Deposit via UPI / instant pay",
    summary: "Using UPI and instant payment rails.",
    lastUpdated: "2025-11-22",
    content: `UPI payments are instant and commonly used for small to medium deposits. This article explains setting a UPI VPA, linking UPI to your account, and troubleshooting failed UPI mandates. For recurring SIP top-ups, UPI auto-debit can be configured where supported.`,
  },
  {
    id: 27,
    category: "Payments & Transfers",
    title: "Failed or pending deposit",
    summary: "Troubleshooting failed deposits.",
    lastUpdated: "2025-11-26",
    content: `Failed deposits can result from wrong reference, bank reversals, or limits. This article shows how to locate transaction IDs, collect bank statements, and submit reconciliation requests. For time-sensitive bids, contact support immediately with proof of payment.`,
  },
  {
    id: 28,
    category: "Payments & Transfers",
    title: "Withdraw funds to your bank",
    summary: "Withdrawal limits and steps.",
    lastUpdated: "2025-11-23",
    content: `Withdrawals are initiated from the Withdraw page and typically process in 1–3 business days. This article explains withdrawal limits, verification steps, and scenarios causing delays (bank holidays, verification holds). Keep your bank details updated to avoid rejections.`,
  },
  {
    id: 29,
    category: "Payments & Transfers",
    title: "Payment limits & fees",
    summary: "Daily/monthly limits and applicable fees.",
    lastUpdated: "2025-11-02",
    content: `Limits depend on verification tier. This article lists per-transaction and daily limits for each tier and explains fee structures for deposits, withdrawals, and platform charges. See the fee schedule for the latest rates.`,
  },
  {
    id: 30,
    category: "Payments & Transfers",
    title: "Add or change bank account",
    summary: "How to link and verify bank accounts.",
    lastUpdated: "2025-10-30",
    content: `To add a bank account, submit account details and complete small-amount verification when required. This article explains micro-deposit verification and the documentation required to change bank accounts securely.`,
  },
  {
    id: 31,
    category: "Payments & Transfers",
    title: "UPI mandates & recurring payments",
    summary: "Setting up recurring top-ups and mandates.",
    lastUpdated: "2025-09-28",
    content: `UPI mandates allow recurring debits for SIPs. This article covers the mandate setup flow, customer consent requirements, and how to cancel or modify mandates. It also addresses mandate failure modes and retries.`,
  },
  {
    id: 32,
    category: "Payments & Transfers",
    title: "International transfers & FX handling",
    summary: "Cross-border deposits and currency conversion.",
    lastUpdated: "2025-08-30",
    content: `We support select international transfers. This article explains intermediary bank fees, FX rates, and how to minimize charges. Cross-border settlement may take several business days and require additional KYC for compliance.`,
  },

  // Investments (33-41) — Part 1 includes up to id 40
  {
    id: 33,
    category: "Investments",
    title: "How IPO allocation works",
    summary: "Understanding allocation and bidding.",
    lastUpdated: "2025-11-14",
    content: `IPO and pre-IPO allocations depend on demand, lot size, and allocation policy. This article explains institutional vs retail allocation, pro-rata distribution, lottery mechanisms, and how our fractional allocation model increases accessibility.

Learn how to place bids, set limit prices where applicable, and read allocation notices.`,
  },
  {
    id: 34,
    category: "Investments",
    title: "Start a SIP for pre-IPO investments",
    summary: "Setting up recurring investments.",
    lastUpdated: "2025-11-13",
    content: `Our SIP-like product automates funding for recurring allocations in curated deals. This article explains scheduling, contribution sizes, how funds are reserved per bidding cycle, and how to pause or stop a SIP.

Note: SIP contributions are used for bids and do not guarantee allocation; they increase participation across cycles.`,
  },
  {
    id: 35,
    category: "Investments",
    title: "Order types and execution",
    summary: "Market, limit, and conditional orders.",
    lastUpdated: "2025-09-11",
    content: `Understand order types supported for secondary trading and listed follow-ons. Market orders execute at the best available price; limit orders set a maximum/minimum price. This article details execution priority, partial fills, and cancellation rules.`,
  },
  {
    id: 36,
    category: "Investments",
    title: "Portfolio breakdown & performance",
    summary: "Where to see holdings and P&L.",
    lastUpdated: "2025-10-18",
    content: `This article shows how to read your portfolio dashboard: allocation by asset class, cost basis, unrealized vs realized P&L, and time-weighted returns. It also explains export formats for tax reporting.`,
  },
  {
    id: 37,
    category: "Investments",
    title: "Risk & suitability assessment",
    summary: "How we assess product suitability for users.",
    lastUpdated: "2025-07-01",
    content: `Regulatory frameworks require suitability checks. This article covers the questionnaire we use to gauge risk tolerance, investment horizon, and experience, and how results influence recommended product access.`,
  },
  {
    id: 38,
    category: "Investments",
    title: "Tax & reporting for investments",
    summary: "How taxes are handled and statements.",
    lastUpdated: "2025-11-01",
    content: `We provide downloadable tax statements and transaction reports. This article explains capital gains treatment, tax deduction at source where applicable, and how to find tax-ready reports for your filings.`,
  },
  {
    id: 39,
    category: "Investments",
    title: "Secondary market vs primary allocations",
    summary: "Differences between primary and secondary trades.",
    lastUpdated: "2025-10-12",
    content: `Primary allocations (pre-IPO/IPO) and secondary transactions (after listing) have different liquidity and fee profiles. This article compares timing, lock-up restrictions, and tax implications of each.`,
  },
  {
    id: 40,
    category: "Investments",
    title: "Syndicate & lead investor participation",
    summary: "How syndicates and lead investors affect deals.",
    lastUpdated: "2025-09-05",
    content: `Some pre-IPO rounds include lead investors or syndicates that negotiate terms. This article explains how syndicates work, benefits to participants, and any allocation priority that may exist.`,
  }
];
// Part 2 continues ARTICLES (41-81) and the HelpCenter component + default export

// ARTICLES continued (41-81)
const MORE_ARTICLES: Article[] = [
  {
    id: 41,
    category: "Investments",
    title: "Due diligence & research notes",
    summary: "How we vet offerings and present research.",
    lastUpdated: "2025-11-20",
    content: `We publish research notes explaining business model, market size, management background, and key risks for each curated deal. This article describes our due diligence checklist and how to interpret risk scores.`,
  },
  {
    id: 42,
    category: "Investments",
    title: "Lock-up periods & transfer restrictions",
    summary: "Understanding lock-ups after listing.",
    lastUpdated: "2025-11-06",
    content: `Many pre-IPO allocations carry lock-up periods during which shares cannot be sold. This article explains typical durations, exceptions, and how lock-ups affect liquidity planning.`,
  },

  // Security & Fraud (43-50)
  {
    id: 43,
    category: "Security & Fraud",
    title: "How we protect your funds",
    summary: "Security architecture overview.",
    lastUpdated: "2025-11-06",
    content: `We protect customer funds using a combination of segregated trust accounts, AML controls, encryption, and access controls. This article explains the separation between custody partners and platform operations and how customer funds are handled during bidding and settlement.`,
  },
  {
    id: 44,
    category: "Security & Fraud",
    title: "Report fraud or suspicious activity",
    summary: "How to report and what information we need.",
    lastUpdated: "2025-11-27",
    content: `If you see unauthorized trades, suspicious emails, or unknown withdrawals, report immediately through the Report Fraud form or contact support. Provide timestamps, transaction IDs, and screenshots to help our investigators.`,
  },
  {
    id: 45,
    category: "Security & Fraud",
    title: "Two-factor authentication (2FA)",
    summary: "Enable and recover 2FA.",
    lastUpdated: "2025-10-12",
    content: `Enable 2FA via SMS or authenticator apps for stronger protection. This article covers how to set up, rotate devices, and recover access if you lose your 2FA device. Recovery requires ID verification for security.`,
  },
  {
    id: 46,
    category: "Security & Fraud",
    title: "Device & session management",
    summary: "Sign out sessions and review devices.",
    lastUpdated: "2025-10-02",
    content: `Review active sessions and revoke access to lost devices. This article explains how sessions are displayed, IP/device metadata, and recommended actions after suspicious access.`,
  },
  {
    id: 47,
    category: "Security & Fraud",
    title: "Phishing protection tips",
    summary: "How to avoid phishing and suspicious links.",
    lastUpdated: "2025-11-03",
    content: `Phishing attempts often mimic official communications. This guide shows how to recognize phishing emails, verify sender authenticity, and report attempts. Remember: we never ask for passwords or 2FA codes by email.`,
  },
  {
    id: 48,
    category: "Security & Fraud",
    title: "What happens if my account is compromised?",
    summary: "Immediate steps and support flow.",
    lastUpdated: "2025-11-16",
    content: `If compromised, immediately freeze your account, change passwords, and contact support. Our incident team will audit transactions, reverse unauthorized moves where possible, and assist with law enforcement if needed.`,
  },
  {
    id: 49,
    category: "Security & Fraud",
    title: "Security best practices for investors",
    summary: "How to reduce risk as a user.",
    lastUpdated: "2025-11-01",
    content: `Use unique passwords, enable 2FA, be cautious with public Wi-Fi, and verify URLs before entering credentials. This article provides a checklist to reduce the likelihood of fraud.`,
  },
  {
    id: 50,
    category: "Security & Fraud",
    title: "Data encryption & privacy controls",
    summary: "How we encrypt and let you control your data.",
    lastUpdated: "2025-11-02",
    content: `We use TLS in transit and AES-256 at rest. This article explains user controls—data export, consent revocation, and how to request deletion subject to regulatory retention requirements.`,
  },

  // Fees & Charges (51-55)
  {
    id: 51,
    category: "Fees & Charges",
    title: "Fee schedule overview",
    summary: "All fees in one place.",
    lastUpdated: "2025-11-08",
    content: `This article lists platform fees, transaction fees, custody charges, and any performance or success fees for certain offerings. It explains how fees are applied and where to find historical fee invoices for reconciliation and tax reporting.`,
  },
  {
    id: 52,
    category: "Fees & Charges",
    title: "Taxes & GST on services",
    summary: "How taxes are applied.",
    lastUpdated: "2025-10-25",
    content: `Taxes depend on jurisdiction and product type. This article outlines how GST/VAT and other tax components are applied, and advises consulting a tax professional for personal tax obligations.`,
  },
  {
    id: 53,
    category: "Fees & Charges",
    title: "How refunds are processed",
    summary: "Refund timing and methods.",
    lastUpdated: "2025-09-20",
    content: `Refunds are processed back to the originating payment method and may take 3–10 business days depending on banks. This article explains exceptions and timelines for chargebacks or disputed bids.`,
  },
  {
    id: 54,
    category: "Fees & Charges",
    title: "Discounts, promos, and rebates",
    summary: "Promotional pricing rules.",
    lastUpdated: "2025-11-07",
    content: `Promotions may apply to fees or offer credits. This article explains eligibility, stacking rules, and how promotional credits appear on the account. Promotions are time-limited and governed by specific terms.`,
  },
  {
    id: 55,
    category: "Fees & Charges",
    title: "Transparency report & fee calculator",
    summary: "Estimate costs for trades and allocations.",
    lastUpdated: "2025-10-01",
    content: `Use our fee calculator to estimate net proceeds and costs for participating in deals. This article explains inputs—allocation size, expected lock-up, and estimated fees—to help users make informed decisions.`,
  },

  // Legal & Compliance (56-61)
  {
    id: 56,
    category: "Legal & Compliance",
    title: "Terms & Conditions (summary)",
    summary: "Plain-language summary of T&C.",
    lastUpdated: "2025-06-01",
    content: `This plain-language summary highlights key points from our Terms & Conditions: user eligibility, account responsibilities, dispute resolution, and limitation of liability. The full legal T&C remains the definitive document for legal matters.`,
  },
  {
    id: 57,
    category: "Legal & Compliance",
    title: "Privacy Policy (summary)",
    summary: "Data handling & user rights.",
    lastUpdated: "2025-06-01",
    content: `Our Privacy Policy explains what we collect, why, and how users can exercise rights (access, correction, portability). This article summarizes data types collected and how we use data to provide services and comply with regulations.`,
  },
  {
    id: 58,
    category: "Legal & Compliance",
    title: "Grievance redressal process",
    summary: "How to file a complaint and escalation matrix.",
    lastUpdated: "2025-08-10",
    content: `If you have a complaint, follow the grievance process: file via Support > File a complaint, receive an acknowledgement, and follow escalation steps (manager review, regulator complaint) if unresolved. This article lists contact points and expected SLAs.`,
  },
  {
    id: 59,
    category: "Legal & Compliance",
    title: "Regulatory disclosures & licenses",
    summary: "Licenses, registrations, and disclaimers.",
    lastUpdated: "2025-05-15",
    content: `We disclose licenses and regulatory registrations relevant to the jurisdictions in which we operate. This article lists current licenses, regulatory partners, and important disclaimers about investment risks and non-guaranteed returns.`,
  },
  {
    id: 60,
    category: "Legal & Compliance",
    title: "Investor agreements & docs",
    summary: "Where to find legal documentation for allocations.",
    lastUpdated: "2025-11-01",
    content: `Allocated deals include investor agreements and term sheets. This article explains typical documents provided post-allocation, how to sign electronically, and where copies are archived in your account.`,
  },
  {
    id: 61,
    category: "Legal & Compliance",
    title: "KYC-related legal obligations",
    summary: "Why we collect certain information.",
    lastUpdated: "2025-07-10",
    content: `KYC is legally required to prevent money laundering and fraud. This article outlines obligations for both users and the platform, and explains when enhanced due diligence may be triggered.`,
  },

  // Troubleshooting (62-71)
  {
    id: 62,
    category: "Troubleshooting",
    title: "App crashes or freezes",
    summary: "Steps to resolve app instability.",
    lastUpdated: "2025-11-09",
    content: `If the app crashes, try clearing cache, updating the app, and restarting the device. This article provides step-by-step diagnostics, how to collect logs, and when to escalate to engineering. Include OS version and reproduction steps for faster support.`,
  },
  {
    id: 63,
    category: "Troubleshooting",
    title: "Unable to login (locked out)",
    summary: "Recovering access and unlocking accounts.",
    lastUpdated: "2025-11-19",
    content: `Account lockouts can occur after multiple failed logins. Use the 'Forgot password' flow or contact support with KYC details for manual unlocking. This article details required identity proofs and expected SLAs.`,
  },
  {
    id: 64,
    category: "Troubleshooting",
    title: "Receiving incorrect balances",
    summary: "Why balances might be delayed and how to refresh.",
    lastUpdated: "2025-11-17",
    content: `Balance mismatches often occur due to unsettled trades or pending deposits. This article explains how to refresh data, where to find ledger entries, and how to raise a reconciliation request with supporting bank/reference IDs.`,
  },
  {
    id: 65,
    category: "Troubleshooting",
    title: "Transaction confirmation not received",
    summary: "What to check when confirmations are missing.",
    lastUpdated: "2025-11-24",
    content: `If confirmations are missing, check spam folders, SMS history, and notification preferences. This article guides how to manually retrieve transaction receipts and provides troubleshooting steps for common email delivery issues.`,
  },
  {
    id: 66,
    category: "Troubleshooting",
    title: "Error codes: meanings & fixes",
    summary: "Common error codes and what they mean.",
    lastUpdated: "2025-11-04",
    content: `This article lists common platform error codes, short explanations, and actionable fixes. When contacting support, include the error code and timestamp to expedite resolution.`,
  },
  {
    id: 67,
    category: "Troubleshooting",
    title: "How to contact support (chat, email, phone)",
    summary: "Contact options and expected SLAs.",
    lastUpdated: "2025-11-29",
    content: `Support channels include in-app chat, email, and phone for critical incidents. This article lists operating hours, SLA expectations, and what to include in tickets (screenshots, transaction IDs) for faster handling.`,
  },
  {
    id: 68,
    category: "Troubleshooting",
    title: "Fixing KYC upload failures",
    summary: "Troubleshoot upload and file format issues.",
    lastUpdated: "2025-11-30",
    content: `If KYC uploads fail, check file size, format (JPEG/PNG/PDF), and image clarity. This troubleshooting guide walks through compressing images safely and alternatives for manual review.`,
  },
  {
    id: 69,
    category: "Troubleshooting",
    title: "App notifications not arriving",
    summary: "Push and email issues.",
    lastUpdated: "2025-11-12",
    content: `If notifications aren’t arriving, verify app permissions, battery optimizations, and notification settings. This article includes platform-specific steps for Android and iOS and how to test notifications.`,
  },
  {
    id: 70,
    category: "Troubleshooting",
    title: "Payment reconciliation checklist",
    summary: "What to provide for payment disputes.",
    lastUpdated: "2025-11-26",
    content: `To reconcile payments, include payment reference, bank UTR, screenshots, timestamp, and originating account. This article provides a downloadable checklist to speed dispute resolution.`,
  },
  {
    id: 71,
    category: "Troubleshooting",
    title: "How to escalate an unresolved issue",
    summary: "Escalation path and timelines.",
    lastUpdated: "2025-10-15",
    content: `If support doesn’t resolve your issue, follow the escalation path: senior support → operations manager → regulatory grievance. This article lists contact points, expected timelines, and necessary documentation for escalations.`,
  },

  // Resources (72-77)
  {
    id: 72,
    category: "Resources",
    title: "Investment basics: pre-IPO explained",
    summary: "Fundamentals for new investors.",
    lastUpdated: "2025-11-02",
    content: `Pre-IPO investing involves buying equity before a company lists publicly. This primer explains valuation, dilution, lock-ups, and why pre-IPO investments can be high-risk but potentially high-reward. Understand diversification and allocation sizing before participating.`,
  },
  {
    id: 73,
    category: "Resources",
    title: "How to build a diversified pre-IPO portfolio",
    summary: "Practical portfolio construction guidance.",
    lastUpdated: "2025-10-05",
    content: `Diversify across sectors, stages, and ticket sizes. This article provides allocation examples, risk management techniques, and how to use SIPs to average into opportunities while preserving liquidity for secondary markets.`,
  },
  {
    id: 74,
    category: "Resources",
    title: "Tax-efficient strategies for allocations",
    summary: "Tax-aware investing tips.",
    lastUpdated: "2025-09-18",
    content: `Tax treatment varies by instrument and holding period. This article outlines basic strategies to manage tax impact, like harvesting losses and planning exits around tax-year timing. Consult your tax advisor for personalized advice.`,
  },
  {
    id: 75,
    category: "Resources",
    title: "Reading an investor memo",
    summary: "How to digest research notes and term sheets.",
    lastUpdated: "2025-11-11",
    content: `Investor memos summarize business models, KPIs, and risks. This guide explains sections to prioritize and red flags to watch for—such as inconsistent metrics or unclear use of proceeds.`,
  },
  {
    id: 76,
    category: "Resources",
    title: "Webinars & events: calendar",
    summary: "Where to find upcoming educational events.",
    lastUpdated: "2025-11-14",
    content: `We host regular webinars covering upcoming deals, market trends, and regulatory changes. This article links to the events calendar and recordings for on-demand viewing.`,
  },
  {
    id: 77,
    category: "Resources",
    title: "Glossary deep dive: financial terms",
    summary: "Extended glossary for advanced investors.",
    lastUpdated: "2025-11-15",
    content: `An extended glossary covering term-sheet clauses, dilution mechanics, liquidation preferences, and cap table concepts—designed for investors who want to read legal and financial documents with confidence.`,
  },

  // Support (78-81)
  {
    id: 78,
    category: "Support",
    title: "Support hours and expected response times",
    summary: "When support is available.",
    lastUpdated: "2025-11-29",
    content: `Support operates Mon-Fri 9am–9pm IST. Critical issues get expedited handling. This article explains expected response times and how to mark tickets as urgent for operational incidents.`,
  },
  {
    id: 79,
    category: "Support",
    title: "Preparing a support ticket: what we need",
    summary: "How to write an effective ticket.",
    lastUpdated: "2025-11-29",
    content: `Include screenshots, steps to reproduce, device info, and transaction references. This article provides a template to copy when contacting support to reduce back-and-forth.`,
  },
  {
    id: 80,
    category: "Support",
    title: "Service Level Agreements (SLA)",
    summary: "SLAs for different issue types.",
    lastUpdated: "2025-10-01",
    content: `This article describes our SLAs for account access, deposits, fraudulent activity, and regulatory enquiries—what we commit to and exceptions during peak times.`,
  },
  {
    id: 81,
    category: "Support",
    title: "Enterprise & institutional onboarding",
    summary: "Onboarding for larger clients.",
    lastUpdated: "2025-08-01",
    content: `Institutional onboarding includes contract negotiation, enhanced due diligence, and dedicated support. This article describes the process, required documents, and timelines for enterprise customers.`,
  }
];

// Merge arrays into one canonical list
const ALL_ARTICLES: Article[] = [...ARTICLES, ...MORE_ARTICLES];

// HelpCenter component
export default function HelpCenterPage(): JSX.Element {
  const [query, setQuery] = useState<string>("");
  const [activeCategory, setActiveCategory] = useState<string>("All");
  const [selectedArticle, setSelectedArticle] = useState<Article | null>(null);
  const [sortBy, setSortBy] = useState<string>("relevance");

  const categories = useMemo(() => {
    const cats = Array.from(new Set(ALL_ARTICLES.map((a) => a.category)));
    return ["All", ...cats];
  }, []);

  const filtered = useMemo(() => {
    let list = ALL_ARTICLES.slice();
    if (activeCategory !== "All") {
      list = list.filter((a) => a.category === activeCategory);
    }
    if (query.trim()) {
      const q = query.toLowerCase();
      list = list.filter((a) =>
        (a.title + " " + a.summary + " " + a.content).toLowerCase().includes(q)
      );
    }
    if (sortBy === "latest") {
      list.sort((x, y) => +new Date(y.lastUpdated) - +new Date(x.lastUpdated));
    }
    return list;
  }, [query, activeCategory, sortBy]);

  const onCopyContent = async (text: string) => {
    try {
      if (navigator.clipboard) {
        await navigator.clipboard.writeText(text);
        alert("Content copied to clipboard.");
      } else {
        alert("Clipboard API not supported in this browser.");
      }
    } catch (err) {
      console.error(err);
      alert("Failed to copy content.");
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-6xl mx-auto bg-white rounded-2xl shadow-md overflow-hidden">
        <div className="md:flex">
          {/* Sidebar */}
          <aside className="md:w-72 border-r p-6 bg-white">
            <h2 className="text-xl font-semibold mb-4">Help Center</h2>
            <p className="text-sm text-gray-600 mb-4">
              Find answers about PreIPOsip — account, KYC, deposits, IPOs, and more.
            </p>

            <div className="mb-4">
              <input
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Search help articles..."
                className="w-full border rounded px-3 py-2 text-sm"
              />
            </div>

            <div className="space-y-2">
              {categories.map((cat) => (
                <button
                  key={cat}
                  onClick={() => {
                    setActiveCategory(cat);
                    setSelectedArticle(null);
                  }}
                  className={`w-full text-left px-3 py-2 rounded ${
                    activeCategory === cat
                      ? "bg-blue-50 ring-1 ring-blue-200"
                      : "hover:bg-gray-50"
                  }`}
                >
                  <div className="flex justify-between items-center">
                    <span className="text-sm">{cat}</span>
                    <span className="text-xs text-gray-400">
                      {cat === "All"
                        ? ALL_ARTICLES.length
                        : ALL_ARTICLES.filter((a) => a.category === cat).length}
                    </span>
                  </div>
                </button>
              ))}
            </div>

            <div className="mt-6">
              <h3 className="text-sm font-medium text-gray-700">Still need help?</h3>
              <p className="text-xs text-gray-500 mt-1">
                Contact our support team via chat or email. Response SLA: within 24 hours
                (business days).
              </p>
              <div className="mt-3 flex gap-2">
                <button
                  onClick={() => alert("Open in-app chat (wire up in your app).")}
                  className="flex-1 bg-blue-600 text-white px-3 py-2 rounded text-sm"
                >
                  Chat
                </button>
                <button
                  onClick={() => (window.location.href = "mailto:support@preiposip.com")}
                  className="flex-1 border rounded px-3 py-2 text-sm"
                >
                  Email
                </button>
              </div>
            </div>

            <div className="mt-6 text-xs text-gray-400">
              <div>Last updated: Nov 29, 2025</div>
              <div className="mt-2">Legal & Compliance • Fees • Security</div>
            </div>
          </aside>

          {/* Main content */}
          <main className="flex-1 p-6">
            <div className="flex items-center justify-between mb-6">
              <div>
                <h1 className="text-2xl font-bold">Search results</h1>
                <p className="text-sm text-gray-500">
                  {filtered.length} articles — category: {activeCategory}
                </p>
              </div>
              <div className="flex items-center gap-3">
                <label className="text-sm text-gray-600">Sort</label>
                <select
                  value={sortBy}
                  onChange={(e) => setSortBy(e.target.value)}
                  className="border rounded px-2 py-1 text-sm"
                >
                  <option value="relevance">Relevance</option>
                  <option value="latest">Latest updated</option>
                </select>
              </div>
            </div>

            <div className="grid md:grid-cols-3 gap-6">
              <div className="md:col-span-2">
                {/* Article list */}
                <div className="space-y-4">
                  {filtered.map((article) => (
                    <div
                      key={article.id}
                      className="p-4 border rounded hover:shadow-sm cursor-pointer bg-white"
                      onClick={() => setSelectedArticle(article)}
                    >
                      <div className="flex justify-between">
                        <h3 className="font-medium">{article.title}</h3>
                        <span className="text-xs text-gray-400">{article.lastUpdated}</span>
                      </div>
                      <p className="text-sm text-gray-600 mt-2">{article.summary}</p>
                      <div className="mt-3 text-xs text-blue-600">Read article →</div>
                    </div>
                  ))}
                </div>
              </div>

              <div className="md:col-span-1">
                {/* Right column: selected article preview or category highlights */}
                <div className="p-4 bg-gray-50 rounded border">
                  {selectedArticle ? (
                    <div>
                      <h3 className="font-semibold text-lg">{selectedArticle.title}</h3>
                      <div className="text-xs text-gray-400 mt-1">
                        Last updated: {selectedArticle.lastUpdated} • Category: {selectedArticle.category}
                      </div>
                      <p className="mt-3 text-sm text-gray-700">{selectedArticle.summary}</p>
                      <div className="mt-4">
                        <button
                          onClick={() => window.print()}
                          className="px-3 py-2 rounded border text-sm"
                        >
                          Print / Save
                        </button>
                        <button
                          onClick={() => onCopyContent(selectedArticle.content)}
                          className="ml-2 px-3 py-2 rounded bg-blue-600 text-white text-sm"
                        >
                          Copy content
                        </button>
                      </div>
                    </div>
                  ) : (
                    <div>
                      <h4 className="font-semibold">Popular articles</h4>
                      <ul className="mt-3 space-y-2 text-sm">
                        {ALL_ARTICLES.slice(0, 6).map((a) => (
                          <li key={a.id} className="flex justify-between">
                            <button onClick={() => setSelectedArticle(a)} className="text-left">
                              {a.title}
                            </button>
                            <span className="text-xs text-gray-400">{a.category}</span>
                          </li>
                        ))}
                      </ul>
                      <div className="mt-4 text-xs text-gray-500">
                        Tip: Use the search box to jump to exact topics like "KYC" or "withdrawal".
                      </div>
                    </div>
                  )}
                </div>

                <div className="mt-4 p-4 border rounded bg-white">
                  <h5 className="font-medium">Contact support</h5>
                  <p className="text-xs text-gray-500 mt-1">
                    If an article doesn't solve your problem, open a ticket and attach screenshots.
                  </p>
                  <div className="mt-3 flex gap-2">
                    <button
                      onClick={() => alert("Create ticket flow (wire up in your app).")}
                      className="flex-1 border rounded px-3 py-2 text-sm"
                    >
                      Create ticket
                    </button>
                    <button
                      onClick={() => alert("Call support (wire up number).")}
                      className="flex-1 bg-green-600 text-white rounded px-3 py-2 text-sm"
                    >
                      Call
                    </button>
                  </div>
                </div>
              </div>
            </div>

            {/* Article detail area */}
            {selectedArticle && (
              <div className="mt-8 p-6 border rounded bg-white">
                <div className="flex justify-between items-start">
                  <div>
                    <h2 className="text-xl font-bold">{selectedArticle.title}</h2>
                    <div className="text-sm text-gray-500">
                      Category: {selectedArticle.category} • Last updated: {selectedArticle.lastUpdated}
                    </div>
                  </div>
                  <div className="space-x-2">
                    <button
                      onClick={() => setSelectedArticle(null)}
                      className="text-sm px-3 py-1 border rounded"
                    >
                      Close
                    </button>
                  </div>
                </div>

                <div className="mt-4 prose prose-sm max-w-none">
                  <p>{selectedArticle.content}</p>

                  <h4>Quick steps</h4>
                  <ol>
                    <li>Read summary</li>
                    <li>Follow the steps</li>
                    <li>Contact support with logs if unresolved</li>
                  </ol>

                  <h4>Related articles</h4>
                  <ul>
                    {ALL_ARTICLES.filter((a) => a.category === selectedArticle.category && a.id !== selectedArticle.id)
                      .slice(0, 4)
                      .map((a) => (
                        <li key={a.id}>
                          <button onClick={() => setSelectedArticle(a)} className="text-blue-600 text-sm">
                            {a.title}
                          </button>
                        </li>
                      ))}
                  </ul>
                </div>
              </div>
            )}
          </main>
        </div>
      </div>

      <footer className="max-w-6xl mx-auto mt-6 text-center text-xs text-gray-400">
        © {new Date().getFullYear()} PreIPOsip • Help Center • Built with ❤️
      </footer>
    </div>
  );
}

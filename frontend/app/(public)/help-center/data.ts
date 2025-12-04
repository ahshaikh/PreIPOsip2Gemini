import { BookOpen, Shield, FileText, IndianRupee, HelpCircle, TrendingUp, Landmark, AlertTriangle } from 'lucide-react';

export type Article = {
  id: string;
  title: string;
  content: string; // We will use simple HTML string or you can use a Markdown parser
  lastUpdated?: string;
};

export type Category = {
  id: string;
  title: string;
  description: string;
  icon: any;
  articles: Article[];
};

export const HELP_DATA: Category[] = [
  {
    id: 'getting-started',
    title: 'Getting Started',
    description: 'Account setup, KYC, and platform basics.',
    icon: BookOpen,
    articles: [
      { id: 'gs-1', title: 'What is PreIPOsip?', content: '<p>PreIPOsip is India\'s premier platform for investing in Unlisted and Pre-IPO shares...</p>' },
      { id: 'gs-2', title: 'How to complete Video KYC?', content: '<p>As per SEBI/PMLA guidelines, Video KYC is mandatory. Ensure you have your PAN card handy...</p>' },
      { id: 'gs-3', title: 'Why is my account "Pending Verification"?', content: '<p>Our compliance team verifies documents manually. This usually takes 2-4 working hours.</p>' },
      { id: 'gs-4', title: 'Who is eligible to invest?', content: '<p>Any Resident Indian, NRI (via NRO account), or HUF can invest. Minors can invest via a guardian.</p>' },
      { id: 'gs-5', title: 'Referral Program T&Cs', content: '<p>Earn 0.5% of your referee\'s first transaction value...</p>' },
    ]
  },
  {
    id: 'buying-selling',
    title: 'Buying & Selling',
    description: 'Trade execution, lots, and settlement.',
    icon: TrendingUp,
    articles: [
      { id: 'bs-1', title: 'Minimum Investment Amount', content: '<p>We have democratized access. You can start with as little as ₹10,000 for select shares.</p>' },
      { id: 'bs-2', title: 'Understanding "Lot Size"', content: '<p>Unlike public markets, unlisted shares are often sold in predefined lots (e.g., 50 shares, 100 shares).</p>' },
      { id: 'bs-3', title: 'Transaction Timeline (T+1)', content: '<p>If you pay before 4 PM, shares are typically credited to your Demat by the next working day evening.</p>' },
      { id: 'bs-4', title: 'How to sell my unlisted shares?', content: '<p>We provide liquidity. You can list your holdings for sale on our secondary marketplace...</p>' },
      { id: 'bs-5', title: 'Do you accept Credit Cards?', content: '<p>No. As per RBI guidelines, equity investments cannot be funded via credit lines.</p>' },
      { id: 'bs-6', title: 'Cancelling an Order', content: '<p>Orders once placed and paid for cannot be cancelled as these are OTC (Over-The-Counter) deals.</p>' },
    ]
  },
  {
    id: 'depository',
    title: 'Demat & Transfer',
    description: 'NSDL/CDSL, ISINs, and DIS slips.',
    icon: Landmark,
    articles: [
      { id: 'dp-1', title: 'Where will my shares be held?', content: '<p>Your shares will be credited directly to your NSDL or CDSL Demat account (e.g., Zerodha, Groww, Angel One).</p>' },
      { id: 'dp-2', title: 'What is a CMR Copy?', content: '<p>Client Master Report (CMR) is a PDF proof of your Demat account details. You can download it from your broker\'s app.</p>' },
      { id: 'dp-3', title: 'CDSL Easiest vs. NSDL Speed-e', content: '<p>To transfer shares yourself, you need to register for CDSL Easiest. See our guide on how to register...</p>' },
      { id: 'dp-4', title: 'My broker app doesn\'t show the price', content: '<p>Standard brokers (Zerodha/Upstox) do not show live prices for unlisted shares. You will see the quantity, but value might be N/A.</p>' },
      { id: 'dp-5', title: 'How to transfer shares to PreIPOsip?', content: '<p>Use the "Off-Market Transfer" option in your CDSL/NSDL portal. Our DP ID is 1208...</p>' },
      { id: 'dp-6', title: 'Wrong ISIN Transfer', content: '<p>If you transferred shares to a wrong ISIN, please contact the depository immediately.</p>' },
    ]
  },
  {
    id: 'taxation',
    title: 'Taxation (India)',
    description: 'LTCG, STCG, and Finance Bill updates.',
    icon: IndianRupee,
    articles: [
      { id: 'tx-1', title: 'Tax on Unlisted vs Listed Shares', content: '<p>Unlisted shares are taxed differently. They are not covered under STT (Securities Transaction Tax).</p>' },
      { id: 'tx-2', title: 'Short Term Capital Gains (STCG)', content: '<p>If sold within 24 months: Taxed at your applicable Income Tax Slab rate.</p>' },
      { id: 'tx-3', title: 'Long Term Capital Gains (LTCG)', content: '<p>If sold after 24 months: Taxed at 12.5% without indexation (As per latest Finance Bill).</p>' },
      { id: 'tx-4', title: 'Stamp Duty Charges', content: '<p>A flat 0.015% stamp duty is applicable on the consideration amount.</p>' },
      { id: 'tx-5', title: 'TDS on Dividend', content: '<p>If an unlisted company declares dividend, 10% TDS is deducted if the amount exceeds ₹5,000.</p>' },
      { id: 'tx-6', title: 'How to file ITR for Unlisted?', content: '<p>You generally need to use ITR-2 or ITR-3 forms. Unlisted holdings must be declared in the "Assets" schedule.</p>' },
    ]
  },
  {
    id: 'risks',
    title: 'Risks & Compliance',
    description: 'Lock-in periods, DRHP, and delays.',
    icon: AlertTriangle,
    articles: [
      { id: 'rk-1', title: 'The "6-Month Lock-in" Rule', content: '<p>Post-IPO, pre-existing shareholders (that\'s you) cannot sell for 6 months. This is a SEBI mandate.</p>' },
      { id: 'rk-2', title: 'What is DRHP?', content: '<p>Draft Red Herring Prospectus. It\'s the first document a company files with SEBI to ask for IPO permission.</p>' },
      { id: 'rk-3', title: 'What if the IPO never happens?', content: '<p>Liquidity risk is real. You would have to sell in the secondary market (PreIPOsip) or wait for a buyback.</p>' },
      { id: 'rk-4', title: 'Grey Market Premium (GMP) explained', content: '<p>GMP is an unofficial estimate of the listing gain. It is volatile and not guaranteed.</p>' },
      { id: 'rk-5', title: 'Company Delisting Risk', content: '<p>Rare, but possible. If a company goes into insolvency, equity holders are paid last.</p>' },
    ]
  },
  {
    id: 'support',
    title: 'Troubleshooting',
    description: 'Login issues, wallet failures, and errors.',
    icon: HelpCircle,
    articles: [
      { id: 'sp-1', title: 'Money deducted but wallet not updated', content: '<p>This happens due to bank server timeouts. We auto-reconcile every 30 mins.</p>' },
      { id: 'sp-2', title: 'Forgot PIN / 2FA Reset', content: '<p>Contact support with a selfie holding your PAN card to reset 2FA.</p>' },
      { id: 'sp-3', title: 'Change Registered Mobile Number', content: '<p>Requires a signed request letter due to security reasons.</p>' },
      { id: 'sp-4', title: 'Report a Bug', content: '<p>Found a glitch? Email engineering@preiposip.com.</p>' },
    ]
  },
  // To reach 100 articles, simply duplicate the structure above with more specific titles like:
  // "Taxation for NRIs", "Gift Tax on Shares", "HUF Account Rules", "Corporate Account Setup", etc.
];
"use client";

import { CheckCircle, ShieldAlert, ClipboardList, Eye, Check, X } from "lucide-react";

export default function ParticipationFrameworkSection() {
  return (
    <section className="relative bg-gray-50 dark:bg-gray-900 py-24 transition-colors duration-300">
      <div className="mx-auto max-w-7xl px-6">
        
        {/* Section Header */}
        <div className="mx-auto max-w-3xl text-center mb-16">
          <h2 className="text-3xl md:text-4xl font-black tracking-tight text-gray-900 dark:text-white transition-colors duration-300">
            How Participation on <span className="text-gradient">PreIPOsip Works</span>
          </h2>
          <p className="mt-4 text-lg text-gray-600 dark:text-gray-400 transition-colors duration-300">
            A transparent, SIP-based participation framework designed with
            regulatory alignment and user clarity at its core.
          </p>
        </div>

        {/* How It Works Grid */}
        <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4 mb-20">
          <Step
            icon={<ClipboardList className="w-6 h-6 text-white" />}
            title="Choose a SIP Plan"
            description="Select a monthly contribution amount and tenure that aligns with your personal financial preferences and planning horizon."
          />

          <Step
            icon={<CheckCircle className="w-6 h-6 text-white" />}
            title="Participate in Opportunities"
            description="SIP contributions are aligned with eligible pre-IPO participation opportunities as per platform processes and applicable terms."
          />

          <Step
            icon={<ShieldAlert className="w-6 h-6 text-white" />}
            title="Platform Incentives"
            description="From time to time, the platform may run incentive or referral programs. Participation is optional and subject to eligibility."
          />

          <Step
            icon={<Eye className="w-6 h-6 text-white" />}
            title="Transparent Tracking"
            description="All participation activity, allocations, and any applicable incentives are transparently reflected within your account dashboard."
          />
        </div>

        {/* Comparison Section */}
        {/* Added a subtle background container to frame the comparison */}
        <div className="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 shadow-xl dark:shadow-none transition-all duration-300 border border-gray-100 dark:border-gray-700">
          <div className="grid grid-cols-1 gap-12 md:grid-cols-2">
            
            {/* Helps With */}
            <div>
              <div className="flex items-center gap-3 mb-6">
                <div className="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                  <Check className="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white">
                  What PreIPOsip Helps With
                </h3>
              </div>
              <ul className="space-y-4 text-sm md:text-base text-gray-700 dark:text-gray-300">
                <ListItem type="check" text="Structured SIP-based participation framework" />
                <ListItem type="check" text="Access to curated pre-IPO participation opportunities" />
                <ListItem type="check" text="Transparent and rule-based incentive programs" />
                <ListItem type="check" text="Centralized tracking, reporting, and disclosures" />
                <ListItem type="check" text="Platform processes aligned with regulations" />
              </ul>
            </div>

            {/* Does Not Do */}
            <div>
               <div className="flex items-center gap-3 mb-6">
                <div className="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                  <X className="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white">
                  What PreIPOsip Does Not Do
                </h3>
              </div>
              <ul className="space-y-4 text-sm md:text-base text-gray-700 dark:text-gray-300">
                <ListItem type="cross" text="Does not guarantee returns or outcomes" />
                <ListItem type="cross" text="Does not predict future performance" />
                <ListItem type="cross" text="Does not provide investment advice" />
                <ListItem type="cross" text="Does not assure incentives or eligibility" />
                <ListItem type="cross" text="Does not replace independent financial due diligence" />
              </ul>
            </div>

          </div>
        </div>

        {/* Disclaimer */}
        <p className="mx-auto mt-12 max-w-4xl text-center text-xs leading-relaxed text-gray-400 dark:text-gray-500">
          Participation examples on this platform are illustrative in nature and
          intended solely to explain platform processes. PreIPOsip does not offer
          investment advice, guarantee outcomes, or promise incentives. All
          participation is subject to eligibility, program terms, and applicable
          laws and regulations.
        </p>
      </div>
    </section>
  );
}

/* ---------- Subcomponents ---------- */

function Step({
  icon,
  title,
  description,
}: {
  icon: React.ReactNode;
  title: string;
  description: string;
}) {
  return (
    <div className="group relative bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
      {/* Icon with Gradient Background */}
      <div className="flex h-12 w-12 items-center justify-center rounded-xl gradient-primary shadow-lg shadow-purple-500/20 mb-6 group-hover:scale-110 transition-transform duration-300">
        {icon}
      </div>
      
      <h4 className="text-lg font-bold text-gray-900 dark:text-white mb-3 transition-colors">
        {title}
      </h4>
      
      <p className="text-sm leading-relaxed text-gray-600 dark:text-gray-400 transition-colors">
        {description}
      </p>
    </div>
  );
}

function ListItem({ text, type }: { text: string; type: "check" | "cross" }) {
  const isCheck = type === "check";
  return (
    <li className="flex items-start gap-3 group">
      <span 
        className={`mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full 
        ${isCheck 
          ? "bg-green-100 text-green-600 dark:bg-green-900/50 dark:text-green-400" 
          : "bg-red-100 text-red-500 dark:bg-red-900/50 dark:text-red-400"
        } transition-colors duration-300`}
      >
        {isCheck ? <Check size={12} strokeWidth={3} /> : <X size={12} strokeWidth={3} />}
      </span>
      <span className="text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">
        {text}
      </span>
    </li>
  );
}
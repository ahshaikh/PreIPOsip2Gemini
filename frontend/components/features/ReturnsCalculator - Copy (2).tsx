"use client";

import { CheckCircle, ShieldAlert, ClipboardList, Eye } from "lucide-react";

export default function ParticipationFrameworkSection() {
  return (
    <section className="relative bg-white py-20">
      <div className="mx-auto max-w-7xl px-6">
        {/* Section Header */}
        <div className="mx-auto max-w-3xl text-center">
          <h2 className="text-3xl font-semibold tracking-tight text-gray-900">
            How Participation on PreIPOsip Works
          </h2>
          <p className="mt-4 text-base text-gray-600">
            A transparent, SIP-based participation framework designed with
            regulatory alignment and user clarity at its core.
          </p>
        </div>

        {/* How It Works */}
        <div className="mt-16 grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
          <Step
            icon={<ClipboardList />}
            title="Choose a SIP Plan"
            description="Select a monthly contribution amount and tenure that aligns with your personal financial preferences and planning horizon."
          />

          <Step
            icon={<CheckCircle />}
            title="Participate in Eligible Opportunities"
            description="SIP contributions are aligned with eligible pre-IPO participation opportunities as per platform processes and applicable terms."
          />

          <Step
            icon={<ShieldAlert />}
            title="Platform Incentives (If Applicable)"
            description="From time to time, the platform may run incentive or referral programs. Participation is optional and subject to eligibility and program terms."
          />

          <Step
            icon={<Eye />}
            title="Transparent Tracking & Reporting"
            description="All participation activity, allocations, and any applicable incentives are transparently reflected within your account dashboard."
          />
        </div>

        {/* Divider */}
        <div className="my-20 h-px w-full bg-gray-200" />

        {/* What This Is / Is Not */}
        <div className="grid grid-cols-1 gap-12 md:grid-cols-2">
          {/* Helps With */}
          <div>
            <h3 className="text-lg font-semibold text-gray-900">
              What PreIPOsip Helps With
            </h3>
            <ul className="mt-6 space-y-3 text-sm text-gray-700">
              <ListItem text="Structured SIP-based participation framework" />
              <ListItem text="Access to curated pre-IPO participation opportunities" />
              <ListItem text="Transparent and rule-based incentive programs (when applicable)" />
              <ListItem text="Centralized tracking, reporting, and disclosures" />
              <ListItem text="Platform processes aligned with prevailing regulatory considerations" />
            </ul>
          </div>

          {/* Does Not Do */}
          <div>
            <h3 className="text-lg font-semibold text-gray-900">
              What PreIPOsip Does Not Do
            </h3>
            <ul className="mt-6 space-y-3 text-sm text-gray-700">
              <ListItem text="Does not guarantee returns or outcomes" />
              <ListItem text="Does not predict future performance" />
              <ListItem text="Does not provide investment advice" />
              <ListItem text="Does not assure incentives, rewards, or eligibility" />
              <ListItem text="Does not replace independent financial due diligence" />
            </ul>
          </div>
        </div>

        {/* Disclaimer */}
        <p className="mx-auto mt-16 max-w-4xl text-center text-xs leading-relaxed text-gray-500">
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
    <div className="rounded-2xl border border-gray-200 p-6 transition hover:border-gray-300">
      <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 text-gray-700">
        {icon}
      </div>
      <h4 className="mt-6 text-base font-semibold text-gray-900">{title}</h4>
      <p className="mt-3 text-sm leading-relaxed text-gray-600">
        {description}
      </p>
    </div>
  );
}

function ListItem({ text }: { text: string }) {
  return (
    <li className="flex items-start gap-3">
      <span className="mt-1 h-1.5 w-1.5 rounded-full bg-gray-500" />
      <span>{text}</span>
    </li>
  );
}

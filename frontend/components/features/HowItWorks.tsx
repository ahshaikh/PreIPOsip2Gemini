// V-PHASE4-1730-108 (Created) | V-FINAL-1730-314 (Dynamic)
import Link from "next/link";

export default function HowItWorks() {
  const steps = [
    {
      number: "1",
      title: "Choose Your Plan",
      description: "Select a monthly SIP amount between ₹1,000 and ₹25,000. All plans offer access to curated pre-IPO opportunities with transparent, zero-fee pricing.",
    },
    {
      number: "2",
      title: "Complete KYC",
      description: "Complete a quick KYC using Aadhaar, PAN, and your Demat account. The process is secure and follows applicable SEBI guidelines.",
    },
    {
      number: "3",
      title: "Start Investing",
      description: "Invest regularly through a SIP-based approach and track your investments in selected pre-IPO opportunities from a single dashboard.",
    },
  ];

  return (
    <section id="how-it-works" className="py-20 bg-white">
      <div className="max-w-6xl mx-auto px-4">
        <div className="text-center mb-16">
          <h2 className="text-4xl font-black text-gray-900 mb-4">
            How It <span className="text-gradient">Works</span>
          </h2>
          <p className="text-xl text-gray-600">Get started in 3 simple steps</p>
        </div>

        <div className="grid md:grid-cols-3 gap-8">
          {steps.map((s, i) => (
            <div key={i} className="text-center">
              <div className="w-20 h-20 gradient-primary rounded-full flex items-center justify-center text-white text-3xl font-bold mx-auto mb-6">
                {s.number}
              </div>
              <h3 className="text-xl font-bold text-gray-900 mb-3">{s.title}</h3>
              <p className="text-gray-600">{s.description}</p>
            </div>
          ))}
        </div>

        <div className="text-center mt-12">
<Link href="/signup">
  <button className="gradient-primary text-white px-12 py-4 rounded-xl font-bold text-lg hover:shadow-2xl transition">
    Get Started Free →
  </button>
</Link>

        </div>
      </div>
    </section>
  );
}

// V-PHASE4-1730-108 (Created) | V-FINAL-1730-314 (Dynamic)
import Link from "next/link";

export default function HowItWorks() {
  const steps = [
    {
      number: "1",
      title: "Choose Your Plan",
      description: "Select from ₹1,000 to ₹25,000 monthly SIP. All plans come with 5-20% guaranteed bonuses.",
    },
    {
      number: "2",
      title: "Complete KYC",
      description: "Quick 5-minute KYC with Aadhaar, PAN & Demat account. 100% secure & SEBI compliant.",
    },
    {
      number: "3",
      title: "Start Earning",
      description: "Invest monthly, earn bonuses automatically, and watch your portfolio grow with India's future unicorns!",
    },
  ];

  return (
    <section id="how-it-works" className="py-20 bg-white">
      <div className="max-w-6xl mx-auto px-4">
        <div className="text-center mb-16">
          <h2 className="text-4xl font-black text-gray-900 mb-4">
            How It <span className="text-gradient">Works</span>
          </h2>
          <p className="text-xl text-gray-600">Start investing in 3 simple steps</p>
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

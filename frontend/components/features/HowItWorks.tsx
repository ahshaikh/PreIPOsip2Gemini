// V-PHASE4-1730-108 (Created) | V-FINAL-1730-314 (Dynamic - Theme Aware)
import Link from "next/link";

type HowItWorksProps = {
  data?: any;
};

export default function HowItWorks({ data }: HowItWorksProps) {
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
    <section 
      id="how-it-works" 
      // UPDATED: Background transitions from white to dark gray
      className="py-20 bg-white dark:bg-gray-900 transition-colors duration-300"
    >
      <div className="max-w-6xl mx-auto px-4">
        
        <div className="text-center mb-16">
          {/* UPDATED: Heading text color */}
          <h2 className="text-4xl font-black text-gray-900 dark:text-white mb-4 transition-colors duration-300">
            How It <span className="text-gradient">Works</span>
          </h2>
          {/* UPDATED: Subheading text color */}
          <p className="text-xl text-gray-600 dark:text-gray-400 transition-colors duration-300">
            Get started in 3 simple steps
          </p>
        </div>

        <div className="grid md:grid-cols-3 gap-8">
          {steps.map((s, i) => (
            <div key={i} className="text-center group">
              {/* Note: The gradient-primary circle looks good on both backgrounds, 
                  but I added a subtle shadow that helps it pop in dark mode */}
              <div className="w-20 h-20 gradient-primary rounded-full flex items-center justify-center text-white text-3xl font-bold mx-auto mb-6 shadow-lg shadow-purple-500/20">
                {s.number}
              </div>
              
              {/* UPDATED: Step Title */}
              <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3 transition-colors duration-300">
                {s.title}
              </h3>
              
              {/* UPDATED: Step Description */}
              <p className="text-gray-600 dark:text-gray-400 transition-colors duration-300">
                {s.description}
              </p>
            </div>
          ))}
        </div>

        <div className="text-center mt-12">
          <Link href="/signup">
            <button className="gradient-primary text-white px-12 py-4 rounded-xl font-bold text-lg hover:shadow-2xl hover:scale-105 transition-all duration-300">
              Get Started Free →
            </button>
          </Link>
        </div>

      </div>
    </section>
  );
}
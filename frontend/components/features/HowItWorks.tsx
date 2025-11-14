// V-FINAL-1730-314 (Dynamic)
'use client';

import Link from 'next/link';

export function HowItWorks({ data }: { data?: any }) {
  const title = data?.title || "How It Works";
  const steps = data?.steps || [
    { title: '1. Sign Up', desc: 'Create account in 2 mins' },
    { title: '2. Choose Plan', desc: 'Select your investment' },
    { title: '3. Earn', desc: 'Watch portfolio grow' }
  ];
  const cta = data?.cta || "Get Started Free";

  return (
    <section id="how-it-works" className="py-20 bg-white">
        <div className="max-w-6xl mx-auto px-4">
            <div className="text-center mb-16">
                <h2 className="text-4xl font-black text-gray-900 mb-4">{title}</h2>
            </div>

            <div className="grid md:grid-cols-3 gap-8 relative">
                <div className="hidden md:block absolute top-10 left-[16%] right-[16%] h-1 bg-gradient-to-r from-indigo-200 via-purple-200 to-pink-200 -z-10"></div>

                {steps.map((step: any, i: number) => (
                    <div key={i} className="text-center bg-white p-4">
                        <div className="w-20 h-20 gradient-primary rounded-full flex items-center justify-center text-white text-3xl font-bold mx-auto mb-6 shadow-lg shadow-indigo-200">
                            {i + 1}
                        </div>
                        <h3 className="text-xl font-bold text-gray-900 mb-3">{step.title}</h3>
                        <p className="text-gray-600 leading-relaxed">{step.desc}</p>
                    </div>
                ))}
            </div>

            <div className="text-center mt-12">
                <Link href="/signup">
                    <button className="gradient-primary text-white px-12 py-4 rounded-xl font-bold text-lg hover:shadow-2xl transition transform hover:scale-105">
                        {cta} →
                    </button>
                </Link>
            </div>
        </div>
    </section>
  );
}
'use client';
// pages/index.tsx (Next.js App Router)
import { motion } from 'framer-motion';
import Image from 'next/image';
import Logo from '@/public/PreIPOsip.png';

export default function Home() {
  return (
    <main className="min-h-screen bg-gradient-to-br from-[#0F0F0F] to-[#1A1A1A] text-[#EAEAEA] font-plexMono">
      <div className="absolute inset-0 bg-[url('/grid.svg')] opacity-10 pointer-events-none" />

      <header className="flex items-center justify-between px-8 py-6">
        <Image src={Logo} alt="PreIPOsip Logo" width={48} height={48} />
        <nav className="space-x-6 text-sm font-clashGrotesk">
          <a href="#how" className="hover:text-[#00BFFF]">How It Works</a>
          <a href="#deals" className="hover:text-[#00BFFF]">Live Deals</a>
          <a href="#compliance" className="hover:text-[#00BFFF]">Compliance</a>
        </nav>
      </header>

      <section className="px-8 py-20">
        <motion.div
          initial="hidden"
          animate="visible"
          variants={{
            hidden: { opacity: 0, y: 20 },
            visible: { opacity: 1, y: 0, transition: { staggerChildren: 0.2 } },
          }}
          className="max-w-4xl mx-auto text-center"
        >
          <motion.h1 className="text-5xl font-clashGrotesk font-bold mb-6 text-[#00BFFF]">
            Invest in Tomorrow’s Giants
          </motion.h1>
          <motion.p className="text-lg text-[#CCCCCC] mb-8">
            PreIPOsip gives accredited investors access to vetted pre-IPO opportunities with full compliance and transparency.
          </motion.p>
          <motion.a
            href="#deals"
            className="inline-block px-6 py-3 bg-[#D4AF37] text-black font-semibold rounded hover:bg-[#c49e2f] transition"
          >
            Browse Live Deals
          </motion.a>
        </motion.div>
      </section>

      <section id="how" className="px-8 py-16 bg-[#121212]">
        <div className="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
          {['Discover', 'Verify', 'Invest'].map((step, i) => (
            <motion.div
              key={step}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: i * 0.3 }}
              className="p-6 bg-[#1F1F1F] rounded shadow hover:shadow-lg transition"
            >
              <h3 className="text-xl font-clashGrotesk text-[#00BFFF] mb-2">{step}</h3>
              <p className="text-sm text-[#AAAAAA]">
                {step === 'Discover' && 'Explore curated pre-IPO opportunities from vetted startups.'}
                {step === 'Verify' && 'Complete KYC/AML and accreditation checks seamlessly.'}
                {step === 'Invest' && 'Commit funds with full compliance and transparent reporting.'}
              </p>
            </motion.div>
          ))}
        </div>
      </section>

      <footer className="px-8 py-12 text-center text-xs text-[#666]">
        &copy; {new Date().getFullYear()} PreIPOsip. All rights reserved.
      </footer>
    </main>
  );
}
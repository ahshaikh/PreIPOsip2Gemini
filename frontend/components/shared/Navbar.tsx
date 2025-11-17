"use client";

import { usePathname } from "next/navigation";
import Link from "next/link";
import { useState } from "react";

export default function Navbar() {
  const [open, setOpen] = useState(false);
  const pathname = usePathname();

  const isHome = pathname === "/";

  const scrollTo = (id: string) => {
    if (!isHome) return (window.location.href = `/#${id}`);
    const el = document.getElementById(id);
    if (el) el.scrollIntoView({ behavior: "smooth" });
  };

  return (
    <nav className="fixed w-full bg-white/95 backdrop-blur-sm shadow-sm z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">

          {/* LOGO */}
          <div
            className="gradient-primary text-white px-4 py-2 rounded-lg font-bold text-xl cursor-pointer"
            onClick={() => {
              if (isHome) window.scrollTo({ top: 0, behavior: "smooth" });
              else window.location.href = "/";
            }}
          >
            PreIPO SIP
          </div>

          {/* Desktop menu */}
          <div className="hidden md:flex items-center space-x-8">

            <button onClick={() => scrollTo("features")} className="text-gray-700 hover:text-purple-600">
              Features
            </button>

            <button onClick={() => scrollTo("plans")} className="text-gray-700 hover:text-purple-600">
              Plans
            </button>

            <button onClick={() => scrollTo("how-it-works")} className="text-gray-700 hover:text-purple-600">
              How It Works
            </button>

            <button onClick={() => scrollTo("calculator")} className="text-gray-700 hover:text-purple-600">
              Calculator
            </button>
          </div>

          {/* CTA */}
          <div className="flex items-center space-x-4">
            <Link href="/login" className="text-gray-700 hover:text-purple-600 font-semibold">
              Login
            </Link>

            <Link href="/signup">
              <button className="gradient-primary text-white px-6 py-2 rounded-lg font-semibold hover:shadow-lg transition">
                Start Free →
              </button>
            </Link>
          </div>
        </div>
      </div>
    </nav>
  );
}

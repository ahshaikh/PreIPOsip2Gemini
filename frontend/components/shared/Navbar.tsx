// V-PHASE4-1730-103 (Created)
"use client";

import { usePathname } from "next/navigation";
import Link from "next/link";
import { useState, useRef, useEffect } from "react";
import { cn } from "@/lib/utils";

export default function Navbar() {
  const [open, setOpen] = useState(false);
  const [hidden, setHidden] = useState(false);
  const pathname = usePathname();

  // Track scroll without re-rendering a million times
  const lastScrollRef = useRef(0);

  const isHome = pathname === "/";

  const scrollTo = (id: string) => {
    if (!isHome) return (window.location.href = `/#${id}`);
    const el = document.getElementById(id);
    if (el) el.scrollIntoView({ behavior: "smooth" });
  };

  // --- SCROLL HIDE LOGIC ---
  useEffect(() => {
    const handler = () => {
      if (open) return; // don't hide when mobile menu opened

      const current = window.scrollY;
      const last = lastScrollRef.current;

      if (current < 10) {
        setHidden(false);
      } else {
        const goingDown = current > last;
        setHidden(goingDown);
      }

      lastScrollRef.current = current;
    };

    window.addEventListener("scroll", handler, { passive: true });
    return () => window.removeEventListener("scroll", handler);
  }, [open]);
  // --------------------------

  return (
    <nav
      className={cn(
        "fixed w-full bg-white/95 backdrop-blur-sm shadow-sm z-50 transition-transform duration-300",
        hidden ? "-translate-y-full" : "translate-y-0"
      )}
    >
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

          {/* Desktop Menu */}
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

          {/* Mobile menu toggle */}
          <button
            className="md:hidden ml-4 flex flex-col gap-1"
            onClick={() => setOpen(!open)}
          >
            <span className="w-6 h-0.5 bg-black" />
            <span className="w-6 h-0.5 bg-black" />
            <span className="w-6 h-0.5 bg-black" />
          </button>
        </div>
      </div>

      {/* Mobile Dropdown */}
      {open && (
        <div className="md:hidden bg-white border-t px-4 pb-4 space-y-4 animate-in fade-in slide-in-from-top-1">
          <button onClick={() => scrollTo("features")} className="block text-gray-700">Features</button>
          <button onClick={() => scrollTo("plans")} className="block text-gray-700">Plans</button>
          <button onClick={() => scrollTo("how-it-works")} className="block text-gray-700">How It Works</button>
          <button onClick={() => scrollTo("calculator")} className="block text-gray-700">Calculator</button>
          <Link href="/login" className="block text-gray-700">Login</Link>
        </div>
      )}
    </nav>
  );
}

'use client';

import React, { useEffect, useState } from "react";
import { Menu } from "lucide-react";

type TocItem = {
  id: string;
  text: string;
  tagName: string;
  level: number;
};

export default function DocumentTOC({
  containerId = "document-root",
  className = "",
}: {
  containerId?: string;
  className?: string;
}) {
  const [items, setItems] = useState<TocItem[]>([]);
  const [activeId, setActiveId] = useState<string | null>(null);
  const [openMobile, setOpenMobile] = useState(false);

  useEffect(() => {
    const root = document.getElementById(containerId);
    if (!root) return;

    // find headings h1-h4
    const headings = Array.from(root.querySelectorAll("h1, h2")) as HTMLElement[];

    const toc: TocItem[] = headings.map((h) => {
      // ensure id
      if (!h.id) {
        const safe = h.textContent?.toLowerCase().replace(/\s+/g, "-").replace(/[^\w-]/g, "") || "";
        let uid = safe || `heading-${Math.random().toString(36).slice(2, 9)}`;
        // avoid duplicate ids
        let i = 1;
        while (document.getElementById(uid)) {
          uid = `${safe}-${i++}`;
        }
        h.id = uid;
      }
      return {
        id: h.id,
        text: h.textContent || "",
        tagName: h.tagName,
        level: parseInt(h.tagName.replace("H", ""), 10),
      };
    });

    setItems(toc);

    // scrollspy
    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries
          .filter((e) => e.isIntersecting)
          .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
        if (visible) setActiveId(visible.target.id);
      },
      { root: null, rootMargin: "0px 0px -60% 0px", threshold: [0, 0.1, 0.5, 1] }
    );

    headings.forEach((h) => observer.observe(h));

    return () => observer.disconnect();
  }, [containerId]);

  const handleClick = (id: string) => {
    const el = document.getElementById(id);
    if (!el) return;
    setOpenMobile(false);
    el.scrollIntoView({ behavior: "smooth", block: "start" });
    // offset for sticky header (if any)
    window.scrollBy(0, -16);
  };

  // Sidebar UI
  return (
    <>
      {/* Mobile toggle */}
      <div className="md:hidden fixed bottom-6 left-4 z-50">
        <button
          className="bg-secondary/80 dark:bg-secondary/70 text-white p-3 rounded-full shadow-lg"
          onClick={() => setOpenMobile((s) => !s)}
          aria-label="Toggle document table of contents"
        >
          <Menu className="h-5 w-5" />
        </button>
      </div>

      {/* Desktop sidebar */}
      <aside
        className={`hidden md:block sticky top-20 max-h-[calc(100vh-96px)] overflow-auto pr-4 ${className}`}
        style={{ minWidth: 240 }}
      >
        <div className="bg-muted/40 dark:bg-muted/40 rounded-md p-4 border">
          <div className="text-sm font-semibold mb-3">On this page</div>
          <nav className="space-y-1">
            {items.map((it) => (
              <div
                key={it.id}
                className={`cursor-pointer text-sm leading-snug transition-all ${
                  activeId === it.id ? "text-primary font-medium" : "text-muted-foreground"
                }`}
                style={{ marginLeft: (it.level - 1) * 12 }}
                onClick={() => handleClick(it.id)}
              >
                {it.text}
              </div>
            ))}
          </nav>
        </div>
      </aside>

      {/* Mobile drawer */}
      {openMobile && (
        <div className="md:hidden fixed inset-0 z-50">
          <div
            className="absolute inset-0 bg-black/40"
            onClick={() => setOpenMobile(false)}
          />
          <div className="absolute left-0 top-0 bottom-0 w-72 bg-background p-4 overflow-auto border-r shadow-xl">
            <div className="flex items-center justify-between mb-3">
              <div className="font-semibold">Contents</div>
              <button className="text-sm text-muted-foreground" onClick={() => setOpenMobile(false)}>Close</button>
            </div>
            <nav className="space-y-2">
              {items.map((it) => (
                <div
                  key={it.id}
                  onClick={() => handleClick(it.id)}
                  className={`cursor-pointer text-sm ${activeId === it.id ? "text-primary font-medium" : "text-muted-foreground"}`}
                  style={{ marginLeft: (it.level - 1) * 10 }}
                >
                  {it.text}
                </div>
              ))}
            </nav>
          </div>
        </div>
      )}
    </>
  );
}

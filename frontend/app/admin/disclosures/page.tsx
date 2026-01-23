"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";

export default function DisclosuresPage() {
  const router = useRouter();

  useEffect(() => {
    // Redirect to pending disclosures by default
    router.push("/admin/disclosures/pending");
  }, [router]);

  return null;
}

"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Loader2, FileText, Building2, Clock } from "lucide-react";
import { toast } from "sonner";
import api from "@/lib/api";
import Link from "next/link";

interface DisclosureSubmission {
  id: number;
  company: {
    id: number;
    name: string;
    lifecycle_state: string;
  };
  module: {
    id: number;
    code: string;
    name: string;
    tier: number;
  };
  status: string;
  submitted_at: string;
  review_started_at: string | null;
  completion_percentage: number;
  clarifications: {
    total: number;
    pending: number;
    answered: number;
  };
  can_approve: boolean;
  priority: string;
}

export default function PendingDisclosuresPage() {
  const [loading, setLoading] = useState(true);
  const [disclosures, setDisclosures] = useState<DisclosureSubmission[]>([]);

  useEffect(() => {
    loadPendingDisclosures();
  }, []);

  async function loadPendingDisclosures() {
    try {
      setLoading(true);
      const response = await api.get("/admin/disclosures/pending");

      // FIX: Backend returns { status: 'success', data: [...] } not { success: true }
      if (response.data.status === 'success') {
        setDisclosures(response.data.data || []);
      } else {
        toast.error("Failed to load pending disclosures");
      }
    } catch (error: any) {
      console.error("Failed to load disclosures:", error);
      toast.error("Failed to load pending disclosures");
    } finally {
      setLoading(false);
    }
  }

  const getTierBadgeColor = (tier: number) => {
    switch (tier) {
      case 1:
        return "bg-blue-100 text-blue-700 border-blue-300";
      case 2:
        return "bg-green-100 text-green-700 border-green-300";
      case 3:
        return "bg-purple-100 text-purple-700 border-purple-300";
      default:
        return "bg-gray-100 text-gray-700 border-gray-300";
    }
  };

  if (loading) {
    return (
      <div className="container mx-auto py-8">
        <div className="flex items-center justify-center min-h-[400px]">
          <Loader2 className="w-8 h-8 animate-spin text-purple-600" />
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto py-8">
      <div className="mb-6">
        <h1 className="text-3xl font-bold">Pending Disclosures</h1>
        <p className="text-gray-600 dark:text-gray-400 mt-2">
          Review and approve company disclosure submissions to enable tier progression
        </p>
      </div>

      {disclosures.length === 0 ? (
        <Card>
          <CardContent className="py-12 text-center">
            <FileText className="w-12 h-12 mx-auto text-gray-400 mb-4" />
            <h3 className="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">
              No Pending Disclosures
            </h3>
            <p className="text-gray-500">
              All disclosure submissions have been reviewed.
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-4">
          {disclosures.map((disclosure) => (
            <Card key={disclosure.id} className="hover:shadow-md transition-shadow">
              <CardContent className="p-6">
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <div className="flex items-center gap-3 mb-2">
                      <Building2 className="w-5 h-5 text-gray-400" />
                      <h3 className="text-lg font-semibold">{disclosure.company.name}</h3>
                      <Badge className={getTierBadgeColor(disclosure.module.tier)}>
                        Tier {disclosure.module.tier}
                      </Badge>
                    </div>

                    <div className="ml-8 space-y-1">
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        <span className="font-medium">Module:</span> {disclosure.module.name} ({disclosure.module.code})
                      </p>
                      <div className="flex items-center gap-2 text-sm text-gray-500">
                        <Clock className="w-4 h-4" />
                        <span>
                          Submitted: {new Date(disclosure.submitted_at).toLocaleDateString()}
                        </span>
                      </div>
                    </div>
                  </div>

                  <div className="flex gap-2">
                    <Link href={`/admin/disclosures/${disclosure.id}`}>
                      <Button size="sm">
                        <FileText className="w-4 h-4 mr-2" />
                        Review
                      </Button>
                    </Link>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      <div className="mt-8 p-4 bg-blue-50 dark:bg-blue-950/30 rounded-lg border border-blue-200 dark:border-blue-800">
        <h4 className="font-semibold text-blue-900 dark:text-blue-100 mb-2">
          About Tier Approvals
        </h4>
        <ul className="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
          <li><strong>Tier 1:</strong> Basic profile → Company visible on platform (no buying)</li>
          <li><strong>Tier 2:</strong> Financial disclosures → Enables investment buying</li>
          <li><strong>Tier 3:</strong> Full transparency → Trust badge and maximum visibility</li>
        </ul>
      </div>
    </div>
  );
}

'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import {
  FileText, Shield, Cookie, RefreshCw, Scale, Lock,
  AlertTriangle, FileWarning, CheckCircle, ArrowRight, Calendar, History
} from "lucide-react";

const LEGAL_DOCUMENTS = [
  {
    type: 'terms_of_service',
    title: 'Terms of Service',
    description: 'The rules and regulations for using our platform.',
    icon: FileText,
    route: '/terms',
    color: 'text-blue-500',
  },
  {
    type: 'privacy_policy',
    title: 'Privacy Policy',
    description: 'How we collect, use, and protect your personal information.',
    icon: Lock,
    route: '/privacy-policy',
    color: 'text-green-500',
  },
  {
    type: 'cookie_policy',
    title: 'Cookie Policy',
    description: 'Information about cookies and tracking technologies we use.',
    icon: Cookie,
    route: '/cookie-policy',
    color: 'text-yellow-500',
  },
  {
    type: 'investment_disclaimer',
    title: 'SEBI Regulations',
    description: 'Important information about investment risks and SEBI regulations & Compliance.',
    icon: Scale,
    route: '/sebi-regulations',
    color: 'text-red-500',
  },
  {
    type: 'refund_policy',
    title: 'Refund Policy',
    description: 'Terms and conditions for requesting refunds from PreIPOsip.com',
    icon: RefreshCw,
    route: '/refund-policy',
    color: 'text-purple-500',
  },
  {
    type: 'risk_disclosure',
    title: 'Risk Disclosure',
    description: 'Pre-IPO investments carry significant risks. Please read this disclosure carefully.',
    icon: FileWarning,
    route: '/risk-disclosure',
    color: 'text-purple-700',
  },
];

export default function LegalPage() {
  const router = useRouter();

  // Fetch all legal documents status
  const { data: documents, isLoading } = useQuery({
    queryKey: ['allLegalDocuments'],
    queryFn: async () => {
      const response = await api.get('/app/(public)/');
      return response.data;
    },
  });

  return (
    <div className="min-h-screen bg-gradient-to-b from-background to-muted/20 py-12">
      <div className="max-w-6xl mx-auto px-4">
        {/* Header */}
        <div className="text-center mb-12">
          <div className="flex items-center justify-center mb-4">
            <Shield className="h-12 w-12 text-primary" />
          </div>
          <h1 className="text-4xl font-bold mb-4">Legal Documents</h1>
          <p className="text-xl text-muted-foreground max-w-2xl mx-auto">
            Review our legal policies and agreements that govern your use of PreIPO SIP platform.
          </p>
        </div>

        {/* Document Cards */}
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
          {LEGAL_DOCUMENTS.map((doc) => {
            const Icon = doc.icon;
            const documentData = documents?.find((d: any) => d.type === doc.type);

            return (
              <Card
                key={doc.type}
                className="hover:shadow-lg transition-shadow cursor-pointer group"
                onClick={() => router.push(doc.route)}
              >
                <CardHeader>
                  <div className="flex items-start justify-between mb-2">
                    <Icon className={`h-8 w-8 ${doc.color}`} />
                    {documentData?.status === 'active' && (
                      <Badge variant="default" className="bg-green-500">
                        <CheckCircle className="mr-1 h-3 w-3" /> Active
                      </Badge>
                    )}
                  </div>
                  <CardTitle className="group-hover:text-primary transition-colors">
                    {doc.title}
                  </CardTitle>
                  <CardDescription>{doc.description}</CardDescription>
                </CardHeader>
                <CardContent>
                  {documentData ? (
                    <div className="space-y-2 text-sm text-muted-foreground mb-4">
                      <div className="flex items-center gap-2">
                        <History className="h-4 w-4" />
                        <span>Version {documentData.version}</span>
                      </div>
                      {documentData.updated_at && (
                        <div className="flex items-center gap-2">
                          <Calendar className="h-4 w-4" />
                          <span>Updated {new Date(documentData.updated_at).toLocaleDateString()}</span>
                        </div>
                      )}
                    </div>
                  ) : (
                    <div className="text-sm text-muted-foreground mb-4">
                      {isLoading ? 'Loading...' : 'Document not available'}
                    </div>
                  )}
                  <Button variant="outline" className="w-full group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
                    Read Document
                    <ArrowRight className="ml-2 h-4 w-4" />
                  </Button>
                </CardContent>
              </Card>
            );
          })}
        </div>

        {/* Information Card */}
        <Card className="border-primary/50">
          <CardContent className="pt-6">
            <div className="flex items-start gap-4">
              <AlertTriangle className="h-6 w-6 text-primary flex-shrink-0 mt-1" />
              <div>
                <h3 className="font-semibold mb-2">Important Notice:</h3>
                <p className="text-sm text-muted-foreground">
                  These legal documents are regularly updated to comply with regulations and improve our services.
                  We recommend reviewing them periodically. Significant changes will be communicated to you via
                  email or platform notifications.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Version Control Notice */}
        <div className="mt-8 text-center">
          <Card className="bg-muted/30 border-dashed">
            <CardContent className="pt-6">
              <History className="h-8 w-8 text-muted-foreground mx-auto mb-3" />
              <h3 className="font-semibold mb-2">Version Controlled Documents</h3>
              <p className="text-sm text-muted-foreground">
                All our legal documents are version controlled with full audit trails. You can view
                version history and changes for transparency and compliance.
              </p>
            </CardContent>
          </Card>
        </div>

        {/* Contact Section */}
        <div className="mt-8 text-center text-sm text-muted-foreground">
          <p>
            Have questions about our legal policies?{' '}
            <a href="/contact" className="text-primary hover:underline font-medium">
              Contact our legal team
            </a>
          </p>
        </div>
      </div>
    </div>
  );
}

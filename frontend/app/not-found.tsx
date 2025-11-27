'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import Link from "next/link";
import {
  TrendingDown, Home, Search, ArrowLeft, BarChart3,
  AlertCircle, HelpCircle, FileX
} from "lucide-react";

export default function NotFound() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-background to-muted/20 flex items-center justify-center p-4">
      <div className="max-w-4xl w-full">
        {/* Main Content */}
        <div className="text-center mb-12">
          {/* Animated 404 with Stock Ticker Style */}
          <div className="mb-8">
            <div className="inline-block">
              <div className="flex items-center gap-4 text-8xl font-bold">
                <span className="bg-gradient-to-r from-red-500 to-orange-500 bg-clip-text text-transparent">
                  4
                </span>
                <div className="relative">
                  <TrendingDown className="h-24 w-24 text-red-500 animate-pulse" />
                  <span className="absolute -top-2 -right-2 text-xl text-red-500 font-bold">
                    0
                  </span>
                </div>
                <span className="bg-gradient-to-r from-red-500 to-orange-500 bg-clip-text text-transparent">
                  4
                </span>
              </div>
            </div>
          </div>

          {/* Error Message */}
          <h1 className="text-4xl font-bold mb-4">Investment Not Found</h1>
          <p className="text-xl text-muted-foreground mb-2">
            This opportunity has delisted from our platform
          </p>
          <p className="text-muted-foreground mb-8">
            The page you're looking for doesn't exist or may have been moved.
            Don't worry, there are plenty of other investment opportunities available!
          </p>

          {/* Stock Market Style Error Info */}
          <Card className="max-w-md mx-auto mb-8 border-red-500/50 bg-red-500/5">
            <CardContent className="pt-6">
              <div className="flex items-start gap-3">
                <AlertCircle className="h-5 w-5 text-red-500 flex-shrink-0 mt-0.5" />
                <div className="text-left">
                  <p className="font-semibold mb-1">Error Code: HTTP 404</p>
                  <p className="text-sm text-muted-foreground">
                    Status: Page Not Found • Time: {new Date().toLocaleTimeString()}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Action Buttons */}
          <div className="flex flex-col sm:flex-row gap-4 justify-center mb-12">
            <Button size="lg" asChild className="gap-2">
              <Link href="/">
                <Home className="h-4 w-4" />
                Back to Home
              </Link>
            </Button>
            <Button size="lg" variant="outline" asChild className="gap-2">
              <Link href="/explore">
                <Search className="h-4 w-4" />
                Explore Investments
              </Link>
            </Button>
            <Button size="lg" variant="outline" onClick={() => window.history.back()} className="gap-2">
              <ArrowLeft className="h-4 w-4" />
              Go Back
            </Button>
          </div>
        </div>

        {/* Helpful Links */}
        <div className="grid md:grid-cols-3 gap-6">
          <Card className="hover:shadow-lg transition-shadow">
            <CardContent className="pt-6 text-center">
              <BarChart3 className="h-10 w-10 mx-auto mb-3 text-primary" />
              <h3 className="font-semibold mb-2">Explore Listings</h3>
              <p className="text-sm text-muted-foreground mb-4">
                Browse available pre-IPO investment opportunities
              </p>
              <Button variant="outline" size="sm" asChild>
                <Link href="/explore">View Listings</Link>
              </Button>
            </CardContent>
          </Card>

          <Card className="hover:shadow-lg transition-shadow">
            <CardContent className="pt-6 text-center">
              <HelpCircle className="h-10 w-10 mx-auto mb-3 text-primary" />
              <h3 className="font-semibold mb-2">Help Center</h3>
              <p className="text-sm text-muted-foreground mb-4">
                Find answers to common questions
              </p>
              <Button variant="outline" size="sm" asChild>
                <Link href="/help">Get Help</Link>
              </Button>
            </CardContent>
          </Card>

          <Card className="hover:shadow-lg transition-shadow">
            <CardContent className="pt-6 text-center">
              <FileX className="h-10 w-10 mx-auto mb-3 text-primary" />
              <h3 className="font-semibold mb-2">Contact Support</h3>
              <p className="text-sm text-muted-foreground mb-4">
                Need assistance? We're here to help
              </p>
              <Button variant="outline" size="sm" asChild>
                <Link href="/contact">Contact Us</Link>
              </Button>
            </CardContent>
          </Card>
        </div>

        {/* Fun Financial Quote */}
        <div className="mt-12 text-center">
          <Card className="bg-gradient-to-r from-primary/5 to-primary/10">
            <CardContent className="pt-6">
              <p className="text-sm italic text-muted-foreground">
                "The stock market is filled with individuals who know the price of everything, but the value of nothing."
              </p>
              <p className="text-xs text-muted-foreground mt-2">— Philip Fisher</p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}

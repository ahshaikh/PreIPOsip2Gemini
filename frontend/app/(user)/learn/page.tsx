'use client';

import { useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  BookOpen,
  Video,
  FileText,
  TrendingUp,
  Shield,
  Lightbulb,
  Play,
  Download,
  ExternalLink,
  Clock,
  Award
} from "lucide-react";

export default function LearnPage() {
  const [selectedCategory, setSelectedCategory] = useState("getting-started");

  const categories = [
    { id: "getting-started", label: "Getting Started", icon: Lightbulb },
    { id: "investing-basics", label: "Investing Basics", icon: TrendingUp },
    { id: "pre-ipo-guide", label: "Pre-IPO Guide", icon: BookOpen },
    { id: "risk-management", label: "Risk Management", icon: Shield },
  ];

  const learningContent = {
    "getting-started": [
      {
        id: 1,
        title: "Welcome to PreIPO SIP",
        type: "video",
        duration: "5 min",
        description: "Learn the basics of how PreIPO SIP works and how to get started with your first investment.",
        thumbnail: "/placeholders/learn-1.jpg",
        completed: false,
      },
      {
        id: 2,
        title: "Setting Up Your Account",
        type: "article",
        duration: "3 min read",
        description: "Step-by-step guide to complete your profile, KYC verification, and wallet setup.",
        completed: false,
      },
      {
        id: 3,
        title: "Understanding Investment Plans",
        type: "video",
        duration: "8 min",
        description: "Deep dive into our investment plans and how to choose the right one for you.",
        thumbnail: "/placeholders/learn-2.jpg",
        completed: false,
      },
    ],
    "investing-basics": [
      {
        id: 4,
        title: "What is SIP Investing?",
        type: "article",
        duration: "5 min read",
        description: "Learn about Systematic Investment Plans and why they're effective for long-term wealth building.",
        completed: false,
      },
      {
        id: 5,
        title: "Diversification Strategies",
        type: "video",
        duration: "10 min",
        description: "How to build a diversified investment portfolio to minimize risk and maximize returns.",
        thumbnail: "/placeholders/learn-3.jpg",
        completed: false,
      },
      {
        id: 6,
        title: "Understanding Returns & ROI",
        type: "article",
        duration: "6 min read",
        description: "Calculate and understand your investment returns, including bonuses and profit sharing.",
        completed: false,
      },
    ],
    "pre-ipo-guide": [
      {
        id: 7,
        title: "What are Pre-IPO Investments?",
        type: "video",
        duration: "12 min",
        description: "Complete guide to pre-IPO investing and how it differs from public market investments.",
        thumbnail: "/placeholders/learn-4.jpg",
        completed: false,
      },
      {
        id: 8,
        title: "Evaluating Pre-IPO Companies",
        type: "article",
        duration: "8 min read",
        description: "Key metrics and factors to consider when evaluating pre-IPO investment opportunities.",
        completed: false,
      },
      {
        id: 9,
        title: "Exit Strategies for Pre-IPO Investments",
        type: "video",
        duration: "15 min",
        description: "Understanding liquidity options and exit strategies for your pre-IPO investments.",
        thumbnail: "/placeholders/learn-5.jpg",
        completed: false,
      },
    ],
    "risk-management": [
      {
        id: 10,
        title: "Understanding Investment Risks",
        type: "article",
        duration: "7 min read",
        description: "Comprehensive guide to the risks involved in pre-IPO investing and how to mitigate them.",
        completed: false,
      },
      {
        id: 11,
        title: "Portfolio Risk Management",
        type: "video",
        duration: "10 min",
        description: "Strategies to manage and balance risk across your investment portfolio.",
        thumbnail: "/placeholders/learn-6.jpg",
        completed: false,
      },
      {
        id: 12,
        title: "When to Exit an Investment",
        type: "article",
        duration: "5 min read",
        description: "Signs and strategies for knowing when to exit or hold your investments.",
        completed: false,
      },
    ],
  };

  return (
    <div className="container mx-auto py-8 px-4">
      <div className="mb-8">
        <h1 className="text-4xl font-bold mb-2">Learning Center</h1>
        <p className="text-muted-foreground text-lg">
          Master pre-IPO investing with our comprehensive guides, videos, and resources
        </p>
      </div>

      {/* Progress Card */}
      <Card className="mb-8 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20">
        <CardContent className="pt-6">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <div className="p-3 bg-purple-600 rounded-lg">
                <Award className="h-6 w-6 text-white" />
              </div>
              <div>
                <h3 className="font-semibold text-lg">Your Learning Progress</h3>
                <p className="text-sm text-muted-foreground">0 of 12 lessons completed</p>
              </div>
            </div>
            <div className="flex items-center gap-2">
              <div className="text-right mr-4">
                <div className="text-2xl font-bold text-purple-600">0%</div>
                <div className="text-xs text-muted-foreground">Complete</div>
              </div>
            </div>
          </div>
          <div className="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full mt-4">
            <div className="h-full bg-gradient-to-r from-purple-600 to-blue-600 rounded-full" style={{ width: "0%" }}></div>
          </div>
        </CardContent>
      </Card>

      {/* Category Tabs */}
      <Tabs value={selectedCategory} onValueChange={setSelectedCategory} className="mb-8">
        <TabsList className="grid grid-cols-2 lg:grid-cols-4 mb-8">
          {categories.map((category) => {
            const Icon = category.icon;
            return (
              <TabsTrigger key={category.id} value={category.id} className="flex items-center gap-2">
                <Icon className="h-4 w-4" />
                <span className="hidden sm:inline">{category.label}</span>
              </TabsTrigger>
            );
          })}
        </TabsList>

        {categories.map((category) => (
          <TabsContent key={category.id} value={category.id}>
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              {learningContent[category.id as keyof typeof learningContent].map((item) => (
                <Card key={item.id} className="group hover:shadow-lg transition-shadow">
                  <CardHeader>
                    <div className="flex items-start justify-between mb-3">
                      <Badge variant={item.type === "video" ? "default" : "secondary"} className="flex items-center gap-1">
                        {item.type === "video" ? <Video className="h-3 w-3" /> : <FileText className="h-3 w-3" />}
                        {item.type === "video" ? "Video" : "Article"}
                      </Badge>
                      <div className="flex items-center gap-1 text-xs text-muted-foreground">
                        <Clock className="h-3 w-3" />
                        {item.duration}
                      </div>
                    </div>

                    {item.type === "video" && (
                      <div className="relative w-full h-40 bg-gradient-to-br from-purple-100 to-blue-100 dark:from-purple-900/30 dark:to-blue-900/30 rounded-lg mb-4 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <Play className="h-12 w-12 text-purple-600 dark:text-purple-400" />
                      </div>
                    )}

                    <CardTitle className="text-lg mb-2">{item.title}</CardTitle>
                    <CardDescription>{item.description}</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <Button className="w-full" variant={item.completed ? "outline" : "default"}>
                      {item.completed ? (
                        <>
                          <Award className="h-4 w-4 mr-2" />
                          Completed
                        </>
                      ) : (
                        <>
                          {item.type === "video" ? <Play className="h-4 w-4 mr-2" /> : <BookOpen className="h-4 w-4 mr-2" />}
                          Start Learning
                        </>
                      )}
                    </Button>
                  </CardContent>
                </Card>
              ))}
            </div>
          </TabsContent>
        ))}
      </Tabs>

      {/* Additional Resources */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Download className="h-5 w-5" />
            Additional Resources
          </CardTitle>
          <CardDescription>Download guides, checklists, and templates to help you invest smarter</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            {[
              { title: "Investment Checklist", size: "PDF, 2MB", icon: FileText },
              { title: "Pre-IPO Evaluation Template", size: "Excel, 1.5MB", icon: FileText },
              { title: "Risk Assessment Guide", size: "PDF, 3MB", icon: FileText },
            ].map((resource, i) => {
              const Icon = resource.icon;
              return (
                <Button key={i} variant="outline" className="justify-start h-auto py-4">
                  <div className="flex items-center gap-3 w-full">
                    <Icon className="h-5 w-5 text-muted-foreground" />
                    <div className="flex-1 text-left">
                      <div className="font-medium">{resource.title}</div>
                      <div className="text-xs text-muted-foreground">{resource.size}</div>
                    </div>
                    <Download className="h-4 w-4 text-muted-foreground" />
                  </div>
                </Button>
              );
            })}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

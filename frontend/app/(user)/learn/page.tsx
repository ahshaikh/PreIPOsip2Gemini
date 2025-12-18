// [AUDIT FIX] Connected Learning Center to Laravel Backend
// Issue: Learning Center used hardcoded content, no progress tracking, no CMS
// Fix: Replaced all mock data with Laravel API client (lib/api.ts)
// Changed: Hardcoded arrays → /user/learning-center/* (Laravel backend)
// Impact: Admins can manage tutorials, users can track progress, downloadable resources
// Date: December 18, 2025
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
  Award,
  CheckCircle2
} from "lucide-react";
import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api";
import { toast } from "sonner";

interface Tutorial {
  id: number;
  title: string;
  description: string;
  category: string;
  type: 'video' | 'article';
  duration: string;
  thumbnail: string | null;
  difficulty: string;
  completed: boolean;
  progress_percentage: number;
  started_at: string | null;
  completed_at: string | null;
}

interface Category {
  id: string;
  name: string;
  slug: string;
  total_tutorials: number;
  started: number;
  completed: number;
  completion_percentage: number;
}

interface Resource {
  id: number;
  title: string;
  description: string;
  type: string;
  size: string;
  category: string;
  download_url: string;
  downloads_count: number;
}

interface OverallProgress {
  total_tutorials: number;
  completed: number;
  in_progress: number;
  not_started: number;
  completion_percentage: number;
  total_time_minutes: number;
  recent_activity: any[];
  category_progress: any[];
}

export default function LearnPage() {
  const [selectedCategory, setSelectedCategory] = useState<string>("all");

  // Fetch categories - [AUDIT FIX] Connected to Laravel backend
  const { data: categories, isLoading: categoriesLoading } = useQuery<Category[]>({
    queryKey: ["learning-categories"],
    queryFn: async () => {
      const response = await api.get("/user/learning-center/categories");
      return response.data;
    }
  });

  // Fetch tutorials (with optional category filter) - [AUDIT FIX] Connected to Laravel backend
  const { data: tutorials, isLoading: tutorialsLoading } = useQuery<Tutorial[]>({
    queryKey: ["learning-tutorials", selectedCategory],
    queryFn: async () => {
      const params = new URLSearchParams();
      if (selectedCategory && selectedCategory !== "all") {
        params.append("category", selectedCategory);
      }
      const response = await api.get(`/user/learning-center/tutorials?${params}`);
      return response.data;
    }
  });

  // Fetch overall progress - [AUDIT FIX] Connected to Laravel backend
  const { data: progress, isLoading: progressLoading } = useQuery<OverallProgress>({
    queryKey: ["learning-progress"],
    queryFn: async () => {
      const response = await api.get("/user/learning-center/progress");
      return response.data;
    }
  });

  // Fetch downloadable resources - [AUDIT FIX] Connected to Laravel backend
  const { data: resources, isLoading: resourcesLoading } = useQuery<Resource[]>({
    queryKey: ["learning-resources"],
    queryFn: async () => {
      const response = await api.get("/user/learning-center/resources");
      return response.data;
    }
  });

  // Handle tutorial start
  const handleStartTutorial = async (tutorialId: number) => {
    try {
      await api.post(`/user/learning-center/tutorials/${tutorialId}/start`);
      toast.success("Tutorial started!");
      // Redirect to tutorial detail page or open modal
      // For now, just show success message
    } catch (error) {
      toast.error("Failed to start tutorial");
    }
  };

  // Map category slugs to icons
  const getCategoryIcon = (slug: string) => {
    const iconMap: Record<string, any> = {
      "getting-started": Lightbulb,
      "investing-basics": TrendingUp,
      "pre-ipo-guide": BookOpen,
      "risk-management": Shield,
    };
    return iconMap[slug] || BookOpen;
  };

  // Filter tutorials by selected category
  const filteredTutorials = selectedCategory === "all"
    ? tutorials
    : tutorials?.filter(t => t.category === selectedCategory);

  const isLoading = categoriesLoading || tutorialsLoading || progressLoading;

  if (isLoading) {
    return (
      <div className="container mx-auto py-8 px-4">
        <div className="flex items-center justify-center h-64">
          <div className="text-lg">Loading learning center...</div>
        </div>
      </div>
    );
  }

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
                <p className="text-sm text-muted-foreground">
                  {progress?.completed || 0} of {progress?.total_tutorials || 0} lessons completed
                </p>
              </div>
            </div>
            <div className="flex items-center gap-2">
              <div className="text-right mr-4">
                <div className="text-2xl font-bold text-purple-600">
                  {progress?.completion_percentage || 0}%
                </div>
                <div className="text-xs text-muted-foreground">Complete</div>
              </div>
            </div>
          </div>
          <div className="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full mt-4">
            <div
              className="h-full bg-gradient-to-r from-purple-600 to-blue-600 rounded-full transition-all"
              style={{ width: `${progress?.completion_percentage || 0}%` }}
            ></div>
          </div>
        </CardContent>
      </Card>

      {/* Category Tabs */}
      <Tabs value={selectedCategory} onValueChange={setSelectedCategory} className="mb-8">
        <TabsList className="grid grid-cols-2 lg:grid-cols-5 mb-8">
          <TabsTrigger value="all" className="flex items-center gap-2">
            <BookOpen className="h-4 w-4" />
            <span className="hidden sm:inline">All ({progress?.total_tutorials || 0})</span>
            <span className="sm:hidden">All</span>
          </TabsTrigger>
          {categories?.slice(0, 4).map((category) => {
            const Icon = getCategoryIcon(category.slug);
            return (
              <TabsTrigger key={category.id} value={category.slug} className="flex items-center gap-2">
                <Icon className="h-4 w-4" />
                <span className="hidden sm:inline">{category.name} ({category.total_tutorials})</span>
                <span className="sm:hidden">{category.name.split(' ')[0]}</span>
              </TabsTrigger>
            );
          })}
        </TabsList>

        <TabsContent value={selectedCategory}>
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredTutorials?.map((item) => (
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
                      {item.thumbnail ? (
                        <img src={item.thumbnail} alt={item.title} className="w-full h-full object-cover rounded-lg" />
                      ) : (
                        <Play className="h-12 w-12 text-purple-600 dark:text-purple-400" />
                      )}
                    </div>
                  )}

                  <CardTitle className="text-lg mb-2">{item.title}</CardTitle>
                  <CardDescription>{item.description}</CardDescription>
                </CardHeader>
                <CardContent>
                  {item.completed ? (
                    <Button className="w-full" variant="outline">
                      <CheckCircle2 className="h-4 w-4 mr-2 text-green-600" />
                      Completed
                    </Button>
                  ) : item.progress_percentage > 0 ? (
                    <div className="space-y-2">
                      <div className="flex justify-between text-sm">
                        <span>In Progress</span>
                        <span className="font-medium">{item.progress_percentage}%</span>
                      </div>
                      <div className="w-full h-2 bg-gray-200 rounded-full">
                        <div
                          className="h-full bg-blue-600 rounded-full transition-all"
                          style={{ width: `${item.progress_percentage}%` }}
                        ></div>
                      </div>
                      <Button className="w-full mt-2" onClick={() => handleStartTutorial(item.id)}>
                        Continue Learning
                      </Button>
                    </div>
                  ) : (
                    <Button className="w-full" onClick={() => handleStartTutorial(item.id)}>
                      {item.type === "video" ? <Play className="h-4 w-4 mr-2" /> : <BookOpen className="h-4 w-4 mr-2" />}
                      Start Learning
                    </Button>
                  )}
                </CardContent>
              </Card>
            ))}
          </div>

          {filteredTutorials?.length === 0 && (
            <div className="text-center py-12">
              <BookOpen className="w-16 h-16 mx-auto text-muted-foreground mb-4" />
              <h3 className="text-lg font-semibold mb-2">No Tutorials Yet</h3>
              <p className="text-muted-foreground">
                {selectedCategory === "all"
                  ? "No tutorials available at the moment."
                  : "No tutorials in this category yet."}
              </p>
            </div>
          )}
        </TabsContent>
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
          {resourcesLoading ? (
            <div className="flex items-center justify-center h-32">
              <div className="text-sm text-muted-foreground">Loading resources...</div>
            </div>
          ) : resources && resources.length > 0 ? (
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
              {resources.map((resource) => (
                <Button
                  key={resource.id}
                  variant="outline"
                  className="justify-start h-auto py-4"
                  asChild
                >
                  <a href={resource.download_url} download>
                    <div className="flex items-center gap-3 w-full">
                      <FileText className="h-5 w-5 text-muted-foreground flex-shrink-0" />
                      <div className="flex-1 text-left">
                        <div className="font-medium">{resource.title}</div>
                        <div className="text-xs text-muted-foreground">{resource.size} • {resource.downloads_count} downloads</div>
                      </div>
                      <Download className="h-4 w-4 text-muted-foreground flex-shrink-0" />
                    </div>
                  </a>
                </Button>
              ))}
            </div>
          ) : (
            <div className="text-center py-8 text-muted-foreground">
              No resources available yet.
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

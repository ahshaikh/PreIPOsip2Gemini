// FILE: C:\PreIPO\frontend\app\(public)\blog\page.tsx
// (This is the MAIN list of articles)

'use client';

import { useState, useMemo } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import Link from "next/link";
import {
  TrendingUp, BookOpen, Calendar, Clock, ArrowRight,
  Newspaper, Tag, User, Search
} from "lucide-react";
import { Input } from "@/components/ui/input";

export default function BlogPage() {
  const [selectedCategory, setSelectedCategory] = useState('All');
  const [searchTerm, setSearchTerm] = useState('');

  // 1. Fetch Data from API
  const { data: apiResponse, isLoading, error } = useQuery({
    queryKey: ['publicBlog'],
    queryFn: async () => {
        const response = await api.get('/public/blog');
        console.log("🔥 API DATA:", response); 
        return response.data;
    },
  });

  // 2. Process API Data
  const enrichedPosts = useMemo(() => {
    let rawApiPosts = [];
    if (apiResponse) {
        if (Array.isArray(apiResponse)) {
            rawApiPosts = apiResponse;
        } else if (apiResponse.data && Array.isArray(apiResponse.data)) {
            rawApiPosts = apiResponse.data;
        } else if (apiResponse.posts && Array.isArray(apiResponse.posts)) {
            rawApiPosts = apiResponse.posts;
        }
    }

    return rawApiPosts.map((post: any) => {
      const words = post.content ? post.content.split(/\s+/).length : 0;
      const readTime = Math.ceil(words / 200) + ' min read';

      return {
        ...post,
        id: post.id || Math.random(),
        title: post.title || 'Untitled',
        slug: post.slug,
        excerpt: post.excerpt || (post.content ? post.content.substring(0, 150) + '...' : 'Read more...'),
        category: post.blog_category?.name || post.category || 'General',
        author: post.author?.username || post.author || 'PreIPO Team',
        readTime: post.readTime || readTime,
        created_at: post.published_at || post.created_at,
        image: post.featured_image,
      };
    });
  }, [apiResponse]);

  const categories = useMemo(() => {
    const uniqueCategories = new Set(enrichedPosts.map((p: any) => p.category));
    if (uniqueCategories.size === 0) return ['All'];
    return ['All', ...Array.from(uniqueCategories)];
  }, [enrichedPosts]);

  const filteredPosts = enrichedPosts.filter((post: any) => {
    const matchesCategory = selectedCategory === 'All' || post.category === selectedCategory;
    const matchesSearch = post.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                          post.excerpt?.toLowerCase().includes(searchTerm.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  const formatDate = (dateString: string) => {
    if (!dateString) return '';
    const d = new Date(dateString);
    if(isNaN(d.getTime())) return 'Recently';
    return d.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-background to-muted/20 flex items-center justify-center">
        <div className="text-center">
          <BookOpen className="h-12 w-12 mx-auto mb-4 text-primary animate-pulse" />
          <p className="text-muted-foreground">Loading articles...</p>
        </div>
      </div>
    );
  }

  const featuredPost = filteredPosts[0];
  const gridPosts = filteredPosts.slice(1);

  return (
    <div className="min-h-screen bg-gradient-to-b from-background to-muted/20">
      <div className="bg-gradient-to-r from-primary/10 to-primary/5 py-20">
        <div className="max-w-7xl mx-auto px-4">
          <div className="text-center mb-8">
            <Badge variant="secondary" className="mb-4">
              <Newspaper className="h-3 w-3 mr-1" />
              Blog & Insights
            </Badge>
            <h1 className="text-5xl font-bold mb-4">Investment Insights & Analysis</h1>
            <p className="text-xl text-muted-foreground max-w-3xl mx-auto mb-8">
              Expert perspectives on pre-IPO investments, market trends, and strategies.
            </p>

            <div className="max-w-xl mx-auto">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search articles..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-10 bg-white dark:bg-gray-900"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 py-16">
        {enrichedPosts.length > 0 && (
            <div className="mb-12">
            <div className="flex items-center gap-2 mb-4">
                <Tag className="h-4 w-4" />
                <h3 className="font-semibold">Categories</h3>
            </div>
            <div className="flex flex-wrap gap-2">
                {categories.map((category: any) => (
                <Button
                    key={category}
                    variant={selectedCategory === category ? "default" : "outline"}
                    size="sm"
                    onClick={() => setSelectedCategory(category)}
                >
                    {category}
                </Button>
                ))}
            </div>
            </div>
        )}

        {featuredPost && (
          <div className="mb-12">
            <div className="flex items-center gap-2 mb-6">
              <TrendingUp className="h-5 w-5 text-primary" />
              <h2 className="text-2xl font-bold">Featured Article</h2>
            </div>
            <Card className="hover:shadow-xl transition-shadow overflow-hidden border-none shadow-md">
              <div className="md:flex">
                <div className="md:w-1/2 bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center p-12 min-h-[300px]">
                    {featuredPost.image ? (
                         <img src={featuredPost.image} alt={featuredPost.title} className="max-h-64 object-cover rounded shadow-sm" />
                    ) : (
                         <BookOpen className="h-32 w-32 text-primary/50" />
                    )}
                </div>
                <div className="md:w-1/2 p-8 flex flex-col justify-center">
                  <Badge className="w-fit mb-3">{featuredPost.category}</Badge>
                  <h3 className="text-3xl font-bold mb-4 leading-tight">{featuredPost.title}</h3>
                  <p className="text-muted-foreground mb-6 line-clamp-3 text-lg">{featuredPost.excerpt}</p>
                  <div className="flex items-center gap-4 text-sm text-muted-foreground mb-6">
                    <div className="flex items-center gap-1">
                      <User className="h-4 w-4" />
                      <span>{featuredPost.author}</span>
                    </div>
                    <div className="flex items-center gap-1">
                      <Calendar className="h-4 w-4" />
                      <span>{formatDate(featuredPost.created_at)}</span>
                    </div>
                    <div className="flex items-center gap-1">
                      <Clock className="h-4 w-4" />
                      <span>{featuredPost.readTime}</span>
                    </div>
                  </div>
                  <Button asChild size="lg" className="w-fit">
                    <Link href={"/blog/" + featuredPost.slug}>
                      Read Full Article
                      <ArrowRight className="h-4 w-4 ml-2" />
                    </Link>
                  </Button>
                </div>
              </div>
            </Card>
          </div>
        )}

        <div className="mb-12">
          {gridPosts.length > 0 && (
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold">
                {selectedCategory === 'All' ? 'Latest Articles' : selectedCategory}
                </h2>
                <p className="text-muted-foreground">
                {filteredPosts.length - 1} {filteredPosts.length - 1 === 1 ? 'article' : 'articles'}
                </p>
            </div>
          )}

          {filteredPosts.length > 0 ? (
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              {gridPosts.map((post: any) => (
                <Card key={post.id} className="hover:shadow-lg transition-all hover:-translate-y-1 flex flex-col overflow-hidden border-gray-200 dark:border-gray-800">
                  <div className="h-48 bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center overflow-hidden">
                    {post.image ? (
                        <img src={post.image} alt={post.title} className="w-full h-full object-cover transition-transform hover:scale-105" />
                    ) : (
                        <BookOpen className="h-16 w-16 text-primary/50" />
                    )}
                  </div>
                  <CardHeader className="flex-1 pb-2">
                    <Badge variant="outline" className="w-fit mb-2 bg-blue-50 text-blue-700 border-blue-100">{post.category}</Badge>
                    <CardTitle className="line-clamp-2 text-xl leading-snug">{post.title}</CardTitle>
                    <CardDescription className="line-clamp-3 mt-2">{post.excerpt}</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="flex items-center gap-4 text-xs text-muted-foreground mb-4 pt-2 border-t border-gray-100 dark:border-gray-800 mt-2">
                      <div className="flex items-center gap-1">
                        <Calendar className="h-3 w-3" />
                        <span>{formatDate(post.created_at)}</span>
                      </div>
                      <div className="flex items-center gap-1">
                        <Clock className="h-3 w-3" />
                        <span>{post.readTime}</span>
                      </div>
                    </div>
                    <Button variant="outline" className="w-full" asChild>
                      <Link href={"/blog/" + post.slug}>
                        Read Article
                        <ArrowRight className="h-4 w-4 ml-2" />
                      </Link>
                    </Button>
                  </CardContent>
                </Card>
              ))}
            </div>
          ) : (
            <Card className="py-12">
              <CardContent className="text-center">
                <Newspaper className="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
                <h3 className="text-xl font-semibold mb-2">No articles found</h3>
                <p className="text-muted-foreground mb-4">
                  {searchTerm ? "Try adjusting your search terms" : "Check back later for new insights!"}
                </p>
                {searchTerm && (
                    <Button variant="outline" onClick={() => { setSearchTerm(''); setSelectedCategory('All'); }}>
                    Clear Filters
                    </Button>
                )}
              </CardContent>
            </Card>
          )}
        </div>

        <Card className="bg-gradient-to-r from-primary/5 to-primary/10 border-primary/20">
          <CardContent className="pt-8 text-center">
            <Newspaper className="h-12 w-12 mx-auto mb-4 text-primary" />
            <h3 className="text-2xl font-bold mb-2">Stay Updated</h3>
            <p className="text-muted-foreground mb-6 max-w-2xl mx-auto">
              Subscribe to our newsletter for weekly insights.
            </p>
            <div className="flex gap-2 max-w-md mx-auto">
              <Input placeholder="Enter your email" type="email" className="bg-white dark:bg-gray-900" />
              <Button>Subscribe</Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
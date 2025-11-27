// V-FINAL-1730-190 - Enhanced with rich content
'use client';

import { useState } from 'react';
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

// Fallback/Sample blog posts when API returns no data
const samplePosts = [
  {
    id: 'sample-1',
    title: 'Understanding Pre-IPO Investments: A Comprehensive Guide',
    slug: 'understanding-pre-ipo-investments',
    excerpt: 'Learn everything about pre-IPO investments, from basics to advanced strategies. Discover how to evaluate opportunities and maximize returns.',
    content: 'Pre-IPO investments offer unique opportunities for retail investors to participate in high-growth companies before they go public...',
    category: 'Investment Education',
    readTime: '8 min read',
    author: 'Investment Team',
    created_at: '2024-11-20T10:00:00Z',
    image: '/api/placeholder/600/400',
  },
  {
    id: 'sample-2',
    title: 'Top 5 Pre-IPO Companies to Watch in 2025',
    slug: 'top-pre-ipo-companies-2025',
    excerpt: 'Explore the most promising pre-IPO companies expected to list in 2025. Analysis of valuations, growth potential, and market opportunities.',
    content: 'As we look ahead to 2025, several exciting companies are preparing for their IPO debuts...',
    category: 'Market Analysis',
    readTime: '6 min read',
    author: 'Research Team',
    created_at: '2024-11-18T14:30:00Z',
    image: '/api/placeholder/600/400',
  },
  {
    id: 'sample-3',
    title: 'How to Build a Diversified Pre-IPO Portfolio',
    slug: 'building-diversified-pre-ipo-portfolio',
    excerpt: 'Expert strategies for creating a balanced pre-IPO investment portfolio. Risk management and sector allocation tips.',
    content: 'Diversification is key to managing risk in pre-IPO investments. Here is how to build a robust portfolio...',
    category: 'Portfolio Strategy',
    readTime: '7 min read',
    author: 'Advisory Team',
    created_at: '2024-11-15T09:15:00Z',
    image: '/api/placeholder/600/400',
  },
  {
    id: 'sample-4',
    title: 'SEBI Regulations: What Pre-IPO Investors Need to Know',
    slug: 'sebi-regulations-pre-ipo-investors',
    excerpt: 'A detailed overview of SEBI regulations affecting pre-IPO investments. Stay compliant and informed.',
    content: 'Understanding SEBI regulations is crucial for all pre-IPO investors. This guide covers key compliance requirements...',
    category: 'Regulatory',
    readTime: '10 min read',
    author: 'Legal Team',
    created_at: '2024-11-12T11:45:00Z',
    image: '/api/placeholder/600/400',
  },
  {
    id: 'sample-5',
    title: 'Success Stories: IPO Gains from Pre-IPO Investments',
    slug: 'ipo-success-stories',
    excerpt: 'Real case studies of investors who profited from pre-IPO investments. Learn from their strategies and decisions.',
    content: 'Success in pre-IPO investing comes from careful research and patience. These case studies illustrate winning strategies...',
    category: 'Case Studies',
    readTime: '9 min read',
    author: 'Editorial Team',
    created_at: '2024-11-10T16:20:00Z',
    image: '/api/placeholder/600/400',
  },
  {
    id: 'sample-6',
    title: 'Tax Implications of Pre-IPO Investments in India',
    slug: 'tax-implications-pre-ipo-investments',
    excerpt: 'Complete tax guide for pre-IPO investors. Capital gains, holding periods, and tax optimization strategies.',
    content: 'Navigate the tax landscape of pre-IPO investments with this comprehensive guide covering all aspects of taxation...',
    category: 'Tax & Finance',
    readTime: '12 min read',
    author: 'Tax Advisory',
    created_at: '2024-11-08T13:00:00Z',
    image: '/api/placeholder/600/400',
  },
];

const categories = ['All', 'Investment Education', 'Market Analysis', 'Portfolio Strategy', 'Regulatory', 'Case Studies', 'Tax & Finance'];

export default function BlogPage() {
  const [selectedCategory, setSelectedCategory] = useState('All');
  const [searchTerm, setSearchTerm] = useState('');

  const { data: apiPosts, isLoading } = useQuery({
    queryKey: ['publicBlog'],
    queryFn: async () => (await api.get('/public/blog')).data,
  });

  // Use API posts if available, otherwise fallback to sample posts
  const posts = (apiPosts && apiPosts.length > 0) ? apiPosts : samplePosts;

  // Add default properties to API posts if they don't exist
  const enrichedPosts = posts.map((post: any) => ({
    ...post,
    excerpt: post.excerpt || post.content?.substring(0, 150) + '...' || 'Read this insightful article...',
    category: post.category || 'General',
    readTime: post.readTime || '5 min read',
    author: post.author || 'PreIPO SIP Team',
  }));

  // Filter posts
  const filteredPosts = enrichedPosts.filter((post: any) => {
    const matchesCategory = selectedCategory === 'All' || post.category === selectedCategory;
    const matchesSearch = post.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         post.excerpt?.toLowerCase().includes(searchTerm.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-background to-muted/20 flex items-center justify-center">
        <div className="text-center">
          <BookOpen className="h-12 w-12 mx-auto mb-4 text-primary animate-pulse" />
          <p className="text-muted-foreground">Loading insights...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-b from-background to-muted/20">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-primary/10 to-primary/5 py-20">
        <div className="max-w-7xl mx-auto px-4">
          <div className="text-center mb-8">
            <Badge variant="secondary" className="mb-4">
              <Newspaper className="h-3 w-3 mr-1" />
              Blog & Insights
            </Badge>
            <h1 className="text-5xl font-bold mb-4">Investment Insights & Analysis</h1>
            <p className="text-xl text-muted-foreground max-w-3xl mx-auto mb-8">
              Expert perspectives on pre-IPO investments, market trends, and strategies
              to help you make informed investment decisions.
            </p>

            {/* Search Bar */}
            <div className="max-w-xl mx-auto">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search articles..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-10"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 py-16">
        {/* Categories */}
        <div className="mb-12">
          <div className="flex items-center gap-2 mb-4">
            <Tag className="h-4 w-4" />
            <h3 className="font-semibold">Categories</h3>
          </div>
          <div className="flex flex-wrap gap-2">
            {categories.map((category) => (
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

        {/* Featured Post (Latest) */}
        {filteredPosts.length > 0 && (
          <div className="mb-12">
            <div className="flex items-center gap-2 mb-6">
              <TrendingUp className="h-5 w-5 text-primary" />
              <h2 className="text-2xl font-bold">Featured Article</h2>
            </div>
            <Card className="hover:shadow-xl transition-shadow">
              <div className="md:flex">
                <div className="md:w-1/2 bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center p-12">
                  <BookOpen className="h-32 w-32 text-primary/50" />
                </div>
                <div className="md:w-1/2 p-8">
                  <Badge className="mb-3">{filteredPosts[0].category}</Badge>
                  <h3 className="text-3xl font-bold mb-4">{filteredPosts[0].title}</h3>
                  <p className="text-muted-foreground mb-6">{filteredPosts[0].excerpt}</p>
                  <div className="flex items-center gap-4 text-sm text-muted-foreground mb-6">
                    <div className="flex items-center gap-1">
                      <User className="h-4 w-4" />
                      <span>{filteredPosts[0].author}</span>
                    </div>
                    <div className="flex items-center gap-1">
                      <Calendar className="h-4 w-4" />
                      <span>{new Date(filteredPosts[0].created_at).toLocaleDateString()}</span>
                    </div>
                    <div className="flex items-center gap-1">
                      <Clock className="h-4 w-4" />
                      <span>{filteredPosts[0].readTime}</span>
                    </div>
                  </div>
                  <Button asChild>
                    <Link href={`/blog/${filteredPosts[0].slug}`}>
                      Read Full Article
                      <ArrowRight className="h-4 w-4 ml-2" />
                    </Link>
                  </Button>
                </div>
              </div>
            </Card>
          </div>
        )}

        {/* All Posts Grid */}
        <div className="mb-12">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-2xl font-bold">
              {selectedCategory === 'All' ? 'All Articles' : selectedCategory}
            </h2>
            <p className="text-muted-foreground">
              {filteredPosts.length} {filteredPosts.length === 1 ? 'article' : 'articles'}
            </p>
          </div>

          {filteredPosts.length > 0 ? (
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              {filteredPosts.slice(1).map((post: any) => (
                <Card key={post.id} className="hover:shadow-lg transition-shadow flex flex-col">
                  <div className="h-48 bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center">
                    <BookOpen className="h-16 w-16 text-primary/50" />
                  </div>
                  <CardHeader className="flex-1">
                    <Badge variant="outline" className="w-fit mb-2">{post.category}</Badge>
                    <CardTitle className="line-clamp-2">{post.title}</CardTitle>
                    <CardDescription className="line-clamp-3">{post.excerpt}</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="flex items-center gap-4 text-xs text-muted-foreground mb-4">
                      <div className="flex items-center gap-1">
                        <Calendar className="h-3 w-3" />
                        <span>{new Date(post.created_at).toLocaleDateString()}</span>
                      </div>
                      <div className="flex items-center gap-1">
                        <Clock className="h-3 w-3" />
                        <span>{post.readTime}</span>
                      </div>
                    </div>
                    <Button variant="outline" className="w-full" asChild>
                      <Link href={`/blog/${post.slug}`}>
                        Read More
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
                  Try adjusting your filters or search terms
                </p>
                <Button
                  variant="outline"
                  onClick={() => {
                    setSearchTerm('');
                    setSelectedCategory('All');
                  }}
                >
                  Clear Filters
                </Button>
              </CardContent>
            </Card>
          )}
        </div>

        {/* Newsletter CTA */}
        <Card className="bg-gradient-to-r from-primary/5 to-primary/10">
          <CardContent className="pt-8 text-center">
            <Newspaper className="h-12 w-12 mx-auto mb-4 text-primary" />
            <h3 className="text-2xl font-bold mb-2">Stay Updated</h3>
            <p className="text-muted-foreground mb-6 max-w-2xl mx-auto">
              Subscribe to our newsletter for weekly insights, market analysis,
              and exclusive pre-IPO investment opportunities.
            </p>
            <div className="flex gap-2 max-w-md mx-auto">
              <Input placeholder="Enter your email" type="email" />
              <Button>Subscribe</Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

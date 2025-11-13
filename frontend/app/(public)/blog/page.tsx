// V-FINAL-1730-190
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import Link from "next/link";

export default function BlogPage() {
  const { data: posts, isLoading } = useQuery({
    queryKey: ['publicBlog'],
    queryFn: async () => (await api.get('/public/blog')).data,
  });

  if (isLoading) return <div className="container py-20">Loading...</div>;

  return (
    <div className="container py-20">
      <h1 className="text-4xl font-bold text-center mb-12">Latest Insights</h1>
      <div className="grid md:grid-cols-3 gap-6">
        {posts?.map((post: any) => (
          <Card key={post.id}>
            <CardHeader>
              <CardTitle>{post.title}</CardTitle>
              <CardDescription>{new Date(post.created_at).toLocaleDateString()}</CardDescription>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-muted-foreground mb-4 line-clamp-3">
                {post.content}
              </p>
              <Button variant="outline" asChild>
                <Link href={`/blog/${post.slug}`}>Read More</Link>
              </Button>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
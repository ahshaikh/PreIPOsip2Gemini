// V-FINAL-1730-191
'use client';

import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useParams } from "next/navigation";

export default function BlogPostPage() {
  const { slug } = useParams();
  const { data: post, isLoading } = useQuery({
    queryKey: ['blogPost', slug],
    queryFn: async () => (await api.get(`/public/blog/${slug}`)).data,
  });

  if (isLoading) return <div className="container py-20">Loading...</div>;

  return (
    <div className="container py-20 max-w-3xl">
      <h1 className="text-4xl font-bold mb-4">{post.title}</h1>
      <p className="text-muted-foreground mb-8">Published on {new Date(post.created_at).toLocaleDateString()}</p>
      <div className="prose max-w-none whitespace-pre-wrap">
        {post.content}
      </div>
    </div>
  );
}
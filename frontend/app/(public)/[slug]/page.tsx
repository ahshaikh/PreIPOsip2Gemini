// V-POLISH-1730-181
'use client';

import { notFound, useParams } from "next/navigation";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useEffect } from "react";
import { toast } from "sonner";

export default function DynamicPage() {
  const params = useParams();
  const slug = params.slug as string;

  const { data: page, isLoading, error } = useQuery({
    queryKey: ['publicPage', slug],
    queryFn: async () => {
      try {
        const { data } = await api.get(`/page/${slug}`);
        return data;
      } catch (e) {
        throw new Error('Page not found');
      }
    },
    enabled: !!slug,
    retry: false,
  });

  useEffect(() => {
    if (error) {
      toast.error("Page not found");
      notFound();
    }
  }, [error]);

  if (isLoading) return <div className="container py-20 text-center">Loading...</div>;
  if (!page) return notFound();

  // Handle simple string content
  if (typeof page.content === 'string') {
    return (
      <div className="container py-20 max-w-3xl">
        <h1 className="text-4xl font-bold mb-8">{page.title}</h1>
        <div className="prose max-w-none" dangerouslySetInnerHTML={{ __html: page.content }} />
      </div>
    );
  }
  
  // Handle JSON content from our CMS
  if (typeof page.content === 'object' && page.content.sections) {
    return (
      <div className="container py-20 max-w-3xl">
        <h1 className="text-4xl font-bold mb-8">{page.title}</h1>
        <div className="space-y-6">
          {page.content.sections.map((section: any, index: number) => (
            <div key={index} className="prose max-w-none">
              {section.title && <h2 className="text-2xl font-bold">{section.title}</h2>}
              {section.text && <p>{section.text}</p>}
              {/* This can be expanded to render other block types */}
            </div>
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="container py-20">
      <h1 className="text-4xl font-bold mb-8">{page.title}</h1>
      <p className="text-muted-foreground">This page has not been configured yet.</p>
    </div>
  );
}
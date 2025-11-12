// V-POLISH-1730-181
'use client';

import { notFound } from "next/navigation";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useParams } from "next/navigation";

export default function DynamicPage() {
  const params = useParams();
  const slug = params.slug as string;

  const { data: page, isLoading, error } = useQuery({
    queryKey: ['page', slug],
    queryFn: async () => {
      try {
        const { data } = await api.get(`/page/${slug}`);
        return data;
      } catch (e) {
        return null;
      }
    },
    retry: false
  });

  if (isLoading) return <div className="container py-20">Loading...</div>;
  
  if (error || !page) {
    // In a real app, you might redirect to 404
    return <div className="container py-20 text-center">Page Not Found</div>;
  }

  // Handle JSON content or plain text
  let contentHtml = '';
  if (typeof page.content === 'string') {
    contentHtml = page.content;
  } else if (page.content && page.content.sections) {
    // Basic JSON renderer for the CMS structure
    // You can expand this to render fancy Hero/Feature blocks based on type
    contentHtml = page.content.sections.map((s: any) => `
      <div class="mb-8">
        ${s.title ? `<h2 class="text-2xl font-bold mb-4">${s.title}</h2>` : ''}
        <div class="prose max-w-none">${s.text || ''}</div>
      </div>
    `).join('');
  }

  return (
    <div className="container py-20">
      <h1 className="text-4xl font-bold mb-8">{page.title}</h1>
      <div className="prose max-w-none" dangerouslySetInnerHTML={{ __html: contentHtml }} />
    </div>
  );
}
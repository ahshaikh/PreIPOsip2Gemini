// V-POLISH-1730-181 (Created) | V-FINAL-1730-371 (Block Renderer)
'use client';

import { notFound, useParams } from "next/navigation";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useEffect } from "react";
import { toast } from "sonner";

/**
 * This is the Block Renderer. It takes a block object and
 * returns the appropriate React/HTML component.
 */
function RenderBlock({ block }: { block: any }) {
    switch (block.type) {
        case 'heading':
            return <h2 className="text-3xl font-bold mt-8 mb-4">{block.text}</h2>;
        
        case 'text':
            return <p className="text-lg text-gray-700 leading-relaxed mb-4 whitespace-pre-wrap">{block.content}</p>;
            
        case 'image':
            return (
                <div className="my-6">
                    <img src={block.src} alt={block.alt || 'Page image'} className="rounded-lg shadow-md" />
                    {block.alt && <p className="text-center text-sm text-muted-foreground italic mt-2">{block.alt}</p>}
                </div>
            );
            
        default:
            return null;
    }
}


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
        return null;
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

  // New Block Rendering Logic
  const contentBlocks = page.content || [];

  return (
    <div className="container py-20 max-w-3xl prose">
      <h1 className="text-5xl font-black mb-8">{page.title}</h1>
      
      {contentBlocks.length > 0 ? (
        contentBlocks.map((block: any) => (
            <RenderBlock key={block.id} block={block} />
        ))
      ) : (
        <p className="text-muted-foreground">This page has not been configured yet.</p>
      )}
    </div>
  );
}
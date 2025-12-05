'use client';

import React from 'react';
import { useParams } from 'next/navigation';
import { useQuery } from '@tanstack/react-query';
import api from '@/lib/api';
import ArticleForm from '@/components/admin/ArticleForm';
import { Loader2 } from 'lucide-react';

export default function EditArticlePage() {
  const { id } = useParams();

  const { data: article, isLoading, isError } = useQuery({
    queryKey: ['kb-article', id],
    queryFn: async () => {
      const { data } = await api.get(`/admin/kb-articles/${id}`);
      return data;
    },
    enabled: !!id,
  });

  if (isLoading) {
    return (
      <div className="flex h-[50vh] items-center justify-center">
        <Loader2 className="w-8 h-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (isError) {
    return <div className="p-8 text-red-500">Failed to load article.</div>;
  }

  return (
    <div className="p-8">
      <ArticleForm initialData={article} isEditing={true} />
    </div>
  );
}
'use client';

import React, { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/lib/api';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Lightbulb,
  BookOpen,
  AlertTriangle,
  Copy,
  ExternalLink,
  Sparkles,
  TrendingUp
} from 'lucide-react';
import Link from 'next/link';

interface AISuggestionsPanelProps {
  subject: string;
  description: string;
  onCategoryChange?: (category: string) => void;
  onPriorityChange?: (priority: string) => void;
}

export default function AISuggestionsPanel({
  subject,
  description,
  onCategoryChange,
  onPriorityChange
}: AISuggestionsPanelProps) {
  const [debouncedSubject, setDebouncedSubject] = useState(subject);
  const [debouncedDescription, setDebouncedDescription] = useState(description);

  // Debounce the inputs to avoid too many API calls
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSubject(subject);
      setDebouncedDescription(description);
    }, 800); // Wait 800ms after user stops typing

    return () => clearTimeout(timer);
  }, [subject, description]);

  // Only query when we have enough text
  const hasEnoughText = debouncedSubject.length > 10 || debouncedDescription.length > 20;

  // Comprehensive AI analysis
  const { data: aiAnalysis, isLoading } = useQuery({
    queryKey: ['ai-analysis', debouncedSubject, debouncedDescription],
    queryFn: async () => {
      const response = await api.post('/user/support/ai/analyze', {
        subject: debouncedSubject,
        description: debouncedDescription
      });
      return response.data;
    },
    enabled: hasEnoughText,
    staleTime: 30000, // Cache for 30 seconds
  });

  // Auto-apply suggested category and priority
  useEffect(() => {
    if (aiAnalysis?.analysis) {
      if (onCategoryChange && aiAnalysis.analysis.suggested_category) {
        onCategoryChange(aiAnalysis.analysis.suggested_category);
      }
      if (onPriorityChange && aiAnalysis.analysis.suggested_priority) {
        onPriorityChange(aiAnalysis.analysis.suggested_priority);
      }
    }
  }, [aiAnalysis, onCategoryChange, onPriorityChange]);

  if (!hasEnoughText) {
    return (
      <Card className="border-dashed">
        <CardHeader>
          <div className="flex items-center gap-2">
            <Sparkles className="w-5 h-5 text-purple-500" />
            <CardTitle className="text-base">AI-Powered Assistance</CardTitle>
          </div>
          <CardDescription>
            Start typing your issue to get instant help suggestions and relevant articles.
          </CardDescription>
        </CardHeader>
      </Card>
    );
  }

  if (isLoading) {
    return (
      <Card>
        <CardContent className="pt-6">
          <div className="flex items-center justify-center py-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
            <span className="ml-3 text-sm text-muted-foreground">Analyzing your issue...</span>
          </div>
        </CardContent>
      </Card>
    );
  }

  if (!aiAnalysis) return null;

  const { analysis, suggestions, duplicates } = aiAnalysis;

  return (
    <div className="space-y-4">
      {/* AI Analysis Summary */}
      <Card className="border-purple-200 bg-purple-50/50 dark:bg-purple-900/10">
        <CardHeader className="pb-3">
          <div className="flex items-center gap-2">
            <Sparkles className="w-5 h-5 text-purple-600" />
            <CardTitle className="text-base">AI Analysis</CardTitle>
          </div>
        </CardHeader>
        <CardContent className="space-y-2">
          <div className="flex flex-wrap gap-2">
            {analysis.suggested_category && (
              <Badge variant="outline" className="gap-1">
                <span className="text-xs text-muted-foreground">Category:</span>
                <span className="font-medium capitalize">{analysis.suggested_category}</span>
              </Badge>
            )}
            {analysis.suggested_priority && (
              <Badge
                variant={
                  analysis.suggested_priority === 'high'
                    ? 'destructive'
                    : analysis.suggested_priority === 'medium'
                    ? 'default'
                    : 'secondary'
                }
                className="gap-1"
              >
                <TrendingUp className="w-3 h-3" />
                <span className="capitalize">{analysis.suggested_priority} Priority</span>
              </Badge>
            )}
            {analysis.sentiment && analysis.sentiment !== 'neutral' && (
              <Badge variant="outline" className="capitalize">
                {analysis.sentiment} Tone
              </Badge>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Duplicate Warning */}
      {analysis.has_duplicates && duplicates && duplicates.length > 0 && (
        <Alert variant="destructive" className="border-orange-200 bg-orange-50 dark:bg-orange-900/10">
          <AlertTriangle className="h-4 w-4 text-orange-600" />
          <AlertDescription className="ml-2">
            <p className="font-medium text-orange-900 dark:text-orange-100 mb-2">
              We found {analysis.duplicate_count} similar ticket(s) you created recently:
            </p>
            <div className="space-y-2">
              {duplicates.map((dup: any) => (
                <div
                  key={dup.id}
                  className="flex items-center justify-between p-2 bg-white dark:bg-slate-800 rounded border"
                >
                  <div className="flex-1">
                    <p className="text-sm font-medium text-slate-900 dark:text-white">
                      #{dup.ticket_id}: {dup.subject}
                    </p>
                    <p className="text-xs text-muted-foreground">
                      {dup.similarity_score} similar • Status: {dup.status}
                    </p>
                  </div>
                  <Link href={dup.url}>
                    <Button variant="ghost" size="sm">
                      <ExternalLink className="w-4 h-4" />
                    </Button>
                  </Link>
                </div>
              ))}
            </div>
            <p className="text-sm text-muted-foreground mt-2">
              💡 Consider checking these tickets before creating a new one.
            </p>
          </AlertDescription>
        </Alert>
      )}

      {/* Suggested Articles */}
      {analysis.has_suggested_articles && suggestions && suggestions.length > 0 && (
        <Card>
          <CardHeader>
            <div className="flex items-center gap-2">
              <Lightbulb className="w-5 h-5 text-yellow-600" />
              <CardTitle className="text-base">These articles might help</CardTitle>
            </div>
            <CardDescription>
              {suggestions.length} relevant article{suggestions.length > 1 ? 's' : ''} found that may answer your question
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {suggestions.map((article: any) => (
                <div
                  key={article.id}
                  className="flex items-start gap-3 p-3 rounded-lg border hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group"
                >
                  <BookOpen className="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" />
                  <div className="flex-1 min-w-0">
                    <Link href={article.url} className="block">
                      <h4 className="font-medium text-sm text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400">
                        {article.title}
                      </h4>
                      {article.summary && (
                        <p className="text-xs text-muted-foreground mt-1 line-clamp-2">
                          {article.summary}
                        </p>
                      )}
                    </Link>
                  </div>
                  <Link href={article.url}>
                    <Button variant="ghost" size="sm" className="opacity-0 group-hover:opacity-100 transition-opacity">
                      <ExternalLink className="w-4 h-4" />
                    </Button>
                  </Link>
                </div>
              ))}
            </div>
            <div className="mt-4 pt-4 border-t">
              <p className="text-xs text-center text-muted-foreground">
                ✨ Found what you need? You can close this form and continue on your own!
              </p>
            </div>
          </CardContent>
        </Card>
      )}

      {/* No Suggestions */}
      {!analysis.has_suggested_articles && !analysis.has_duplicates && (
        <Card className="border-dashed">
          <CardContent className="pt-6 text-center">
            <div className="flex flex-col items-center gap-2 py-4">
              <BookOpen className="w-8 h-8 text-muted-foreground" />
              <p className="text-sm text-muted-foreground">
                No similar articles or tickets found.
              </p>
              <p className="text-xs text-muted-foreground">
                Our support team will help you directly!
              </p>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}

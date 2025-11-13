// V-FINAL-1730-187
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Trash2 } from "lucide-react";

export default function FaqSettingsPage() {
  const queryClient = useQueryClient();
  const [question, setQuestion] = useState('');
  const [answer, setAnswer] = useState('');

  const { data: faqs, isLoading } = useQuery({
    queryKey: ['adminFaqs'],
    queryFn: async () => (await api.get('/admin/faqs')).data,
  });

  const createMutation = useMutation({
    mutationFn: (data: any) => api.post('/admin/faqs', data),
    onSuccess: () => {
      toast.success("FAQ Added");
      queryClient.invalidateQueries({ queryKey: ['adminFaqs'] });
      setQuestion(''); setAnswer('');
    }
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/faqs/${id}`),
    onSuccess: () => {
      toast.success("FAQ Deleted");
      queryClient.invalidateQueries({ queryKey: ['adminFaqs'] });
    }
  });

  return (
    <div className="grid md:grid-cols-2 gap-6">
      <Card>
        <CardHeader><CardTitle>Add FAQ</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label>Question</Label>
            <Input value={question} onChange={e => setQuestion(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label>Answer</Label>
            <Textarea value={answer} onChange={e => setAnswer(e.target.value)} />
          </div>
          <Button onClick={() => createMutation.mutate({ question, answer })} disabled={createMutation.isPending}>
            Add FAQ
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>Existing FAQs</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          {isLoading ? <p>Loading...</p> : faqs?.map((faq: any) => (
            <div key={faq.id} className="border p-4 rounded flex justify-between items-start">
              <div>
                <p className="font-semibold">{faq.question}</p>
                <p className="text-sm text-muted-foreground">{faq.answer}</p>
              </div>
              <Button variant="destructive" size="sm" onClick={() => deleteMutation.mutate(faq.id)}>
                <Trash2 className="h-4 w-4" />
              </Button>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  );
}
// V-REMEDIATE-1730-170
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Mail, Save } from "lucide-react";
import { useState } from "react";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

export default function NotificationSettingsPage() {
  const queryClient = useQueryClient();
  const [selectedTemplateId, setSelectedTemplateId] = useState<string>("");
  
  // Local edit state
  const [subject, setSubject] = useState("");
  const [body, setBody] = useState("");

  const { data: templates, isLoading } = useQuery({
    queryKey: ['emailTemplates'],
    queryFn: async () => (await api.get('/admin/email-templates')).data,
  });

  const mutation = useMutation({
    mutationFn: (data: any) => api.put(`/admin/email-templates/${selectedTemplateId}`, data),
    onSuccess: () => {
      toast.success("Template Updated!");
      queryClient.invalidateQueries({ queryKey: ['emailTemplates'] });
    },
    onError: (e: any) => toast.error("Save Failed", { description: e.response?.data?.message })
  });

  const handleSelect = (id: string) => {
    setSelectedTemplateId(id);
    const tmpl = templates.find((t: any) => t.id.toString() === id);
    if (tmpl) {
      setSubject(tmpl.subject);
      setBody(tmpl.body);
    }
  };

  const handleSave = () => {
    if (!selectedTemplateId) return;
    mutation.mutate({ subject, body });
  };

  if (isLoading) return <div>Loading templates...</div>;

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">Notification Manager</h1>
          <p className="text-muted-foreground">Edit automated email templates.</p>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {/* Sidebar List */}
        <Card className="md:col-span-1">
          <CardHeader>
            <CardTitle>Templates</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            <Select onValueChange={handleSelect} value={selectedTemplateId}>
              <SelectTrigger>
                <SelectValue placeholder="Select a template" />
              </SelectTrigger>
              <SelectContent>
                {templates?.map((t: any) => (
                  <SelectItem key={t.id} value={t.id.toString()}>
                    {t.name} ({t.slug})
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            
            <div className="pt-4 text-sm text-muted-foreground">
              <p>Available Variables:</p>
              <ul className="list-disc pl-4 mt-2 space-y-1">
                <li>{`{{name}}`} - User's Name</li>
                <li>{`{{otp}}`} - OTP Code</li>
                <li>{`{{amount}}`} - Transaction Amount</li>
                <li>{`{{link}}`} - Action Link</li>
              </ul>
            </div>
          </CardContent>
        </Card>

        {/* Editor */}
        <Card className="md:col-span-2">
          <CardHeader className="flex flex-row justify-between">
            <CardTitle>Edit Template</CardTitle>
            <Button onClick={handleSave} disabled={!selectedTemplateId || mutation.isPending}>
              <Save className="mr-2 h-4 w-4" /> Save Changes
            </Button>
          </CardHeader>
          <CardContent className="space-y-4">
            {!selectedTemplateId ? (
              <div className="text-center py-12 text-muted-foreground">
                <Mail className="h-12 w-12 mx-auto mb-4 opacity-20" />
                Select a template to edit
              </div>
            ) : (
              <>
                <div className="space-y-2">
                  <Label>Email Subject</Label>
                  <Input value={subject} onChange={(e) => setSubject(e.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label>Email Body (HTML)</Label>
                  <Textarea 
                    value={body} 
                    onChange={(e) => setBody(e.target.value)} 
                    rows={15}
                    className="font-mono" 
                  />
                </div>
              </>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
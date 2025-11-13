// V-FINAL-1730-247
'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { toast } from "sonner";
import api from "@/lib/api";
import { useMutation } from "@tanstack/react-query";
import { useState } from "react";

export default function ThemeSettingsPage() {
  const [color, setColor] = useState('');
  const [logo, setLogo] = useState<File | null>(null);
  const [robots, setRobots] = useState('User-agent: *\nDisallow: /admin');

  const themeMutation = useMutation({
    mutationFn: (formData: FormData) => api.post('/theme/update', formData),
    onSuccess: () => toast.success("Theme Updated")
  });

  const seoMutation = useMutation({
    mutationFn: (data: any) => api.post('/seo/update', data),
    onSuccess: () => toast.success("SEO Settings Updated")
  });

  const handleThemeSubmit = () => {
    const fd = new FormData();
    if (color) fd.append('primary_color', color);
    if (logo) fd.append('logo', logo);
    themeMutation.mutate(fd);
  };

  const handleSeoSubmit = () => {
    seoMutation.mutate({ robots_txt: robots });
  };

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Theme & SEO</h1>

      <div className="grid md:grid-cols-2 gap-6">
        <Card>
            <CardHeader><CardTitle>Brand Customization</CardTitle></CardHeader>
            <CardContent className="space-y-4">
                <div className="space-y-2">
                    <Label>Primary Brand Color (Hex)</Label>
                    <div className="flex gap-2">
                        <Input type="color" className="w-12 p-1 h-10" value={color} onChange={e => setColor(e.target.value)} />
                        <Input value={color} onChange={e => setColor(e.target.value)} placeholder="#000000" />
                    </div>
                </div>
                <div className="space-y-2">
                    <Label>Upload Logo</Label>
                    <Input type="file" onChange={e => setLogo(e.target.files?.[0] || null)} />
                </div>
                <Button onClick={handleThemeSubmit} disabled={themeMutation.isPending}>Save Theme</Button>
            </CardContent>
        </Card>

        <Card>
            <CardHeader><CardTitle>SEO Manager</CardTitle></CardHeader>
            <CardContent className="space-y-4">
                <div className="space-y-2">
                    <Label>robots.txt content</Label>
                    <Textarea rows={6} value={robots} onChange={e => setRobots(e.target.value)} className="font-mono" />
                </div>
                <Button onClick={handleSeoSubmit} disabled={seoMutation.isPending}>Save SEO Settings</Button>
            </CardContent>
        </Card>
      </div>
    </div>
  );
}
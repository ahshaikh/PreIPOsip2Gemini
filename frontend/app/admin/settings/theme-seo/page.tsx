// V-FINAL-1730-521 (Created)
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { toast } from "sonner";
import api from "@/lib/api";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useState, useEffect } from "react";
import { Save, Palette, Search } from "lucide-react";

export default function ThemeSeoPage() {
  const [logo, setLogo] = useState<File | null>(null);
  const [favicon, setFavicon] = useState<File | null>(null);
  const [primaryColor, setPrimaryColor] = useState('');
  const [robotsTxt, setRobotsTxt] = useState('');
  const [metaSuffix, setMetaSuffix] = useState('');

  // Fetch existing settings
  const { data } = useQuery({
      queryKey: ['globalSettings'],
      queryFn: async () => (await api.get('/global-settings')).data,
  });

  useEffect(() => {
      if (data) {
          setPrimaryColor(data.theme?.primary_color || '#667eea');
          setMetaSuffix(data.theme?.meta_title_suffix || '| PreIPO SIP');
          // Fetching robots.txt would require a separate, authenticated endpoint
      }
  }, [data]);

  const themeMutation = useMutation({
    mutationFn: (formData: FormData) => api.post('/admin/theme/update', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    }),
    onSuccess: () => toast.success("Theme Updated")
  });

  const seoMutation = useMutation({
    mutationFn: (data: any) => api.post('/admin/seo/update', data),
    onSuccess: () => toast.success("SEO Settings Updated")
  });

  const handleThemeSubmit = () => {
    const fd = new FormData();
    if (logo) fd.append('logo', logo);
    if (favicon) fd.append('favicon', favicon);
    fd.append('primary_color', primaryColor);
    themeMutation.mutate(fd);
  };

  const handleSeoSubmit = () => {
    seoMutation.mutate({ robots_txt: robotsTxt, meta_title_suffix: metaSuffix });
  };

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Theme & SEO</h1>

      <div className="grid md:grid-cols-2 gap-6">
        <Card>
            <CardHeader><CardTitle className="flex items-center"><Palette className="mr-2 h-5 w-5"/> Brand Customization</CardTitle></CardHeader>
            <CardContent className="space-y-4">
                <div className="space-y-2">
                    <Label>Primary Brand Color</Label>
                    <div className="flex gap-2">
                        <Input type="color" className="w-12 p-1 h-10" value={primaryColor} onChange={e => setPrimaryColor(e.target.value)} />
                        <Input value={primaryColor} onChange={e => setPrimaryColor(e.target.value)} placeholder="#667eea" />
                    </div>
                </div>
                <div className="space-y-2">
                    <Label>Upload Logo (PNG)</Label>
                    <Input type="file" accept="image/png" onChange={e => setLogo(e.target.files?.[0] || null)} />
                </div>
                <div className="space-y-2">
                    <Label>Upload Favicon (.ico)</Label>
                    <Input type="file" accept="image/x-icon" onChange={e => setFavicon(e.target.files?.[0] || null)} />
                </div>
                <Button onClick={handleThemeSubmit} disabled={themeMutation.isPending}>
                  <Save className="mr-2 h-4 w-4" />
                  {themeMutation.isPending ? "Saving..." : "Save Theme"}
                </Button>
            </CardContent>
        </Card>

        <Card>
            <CardHeader><CardTitle className="flex items-center"><Search className="mr-2 h-5 w-5"/> SEO Manager</CardTitle></CardHeader>
            <CardContent className="space-y-4">
                <div className="space-y-2">
                    <Label>Meta Title Suffix</Label>
                    <Input value={metaSuffix} onChange={e => setMetaSuffix(e.target.value)} placeholder="| PreIPO SIP" />
                </div>
                <div className="space-y-2">
                    <Label>robots.txt</Label>
                    <Textarea rows={8} value={robotsTxt} onChange={e => setRobotsTxt(e.target.value)} className="font-mono" placeholder="User-agent: * ..." />
                </div>
                <Button onClick={handleSeoSubmit} disabled={seoMutation.isPending}>
                  <Save className="mr-2 h-4 w-4" />
                  {seoMutation.isPending ? "Saving..." : "Save SEO"}
                </Button>
            </CardContent>
        </Card>
      </div>
    </div>
  );
}
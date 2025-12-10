// V-FINAL-1730-521 (Created) | V-CMS-ENHANCEMENT-015 (Enhanced with typography & colors)
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "sonner";
import api from "@/lib/api";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useState, useEffect } from "react";
import { Save, Palette, Search, Type } from "lucide-react";

export default function ThemeSeoPage() {
  const [logo, setLogo] = useState<File | null>(null);
  const [favicon, setFavicon] = useState<File | null>(null);
  const [primaryColor, setPrimaryColor] = useState('');
  const [secondaryColor, setSecondaryColor] = useState('');
  const [accentColor, setAccentColor] = useState('');
  const [fontFamily, setFontFamily] = useState('');
  const [headingFont, setHeadingFont] = useState('');
  const [fontSize, setFontSize] = useState('');
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
          setSecondaryColor(data.theme?.secondary_color || '#4338ca');
          setAccentColor(data.theme?.accent_color || '#10b981');
          setFontFamily(data.theme?.font_family || 'Inter');
          setHeadingFont(data.theme?.heading_font || 'Poppins');
          setFontSize(data.theme?.font_size || 'medium');
          setMetaSuffix(data.theme?.meta_title_suffix || '| PreIPO SIP');
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
    fd.append('secondary_color', secondaryColor);
    fd.append('accent_color', accentColor);
    fd.append('font_family', fontFamily);
    fd.append('heading_font', headingFont);
    fd.append('font_size', fontSize);
    themeMutation.mutate(fd);
  };

  const handleSeoSubmit = () => {
    seoMutation.mutate({ robots_txt: robotsTxt, meta_title_suffix: metaSuffix });
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Theme & SEO</h1>
        <p className="text-muted-foreground mt-1">Customize your site's appearance and SEO settings</p>
      </div>

      <Tabs defaultValue="branding" className="space-y-4">
        <TabsList>
          <TabsTrigger value="branding">Branding</TabsTrigger>
          <TabsTrigger value="colors">Colors</TabsTrigger>
          <TabsTrigger value="typography">Typography</TabsTrigger>
          <TabsTrigger value="seo">SEO</TabsTrigger>
        </TabsList>

        <TabsContent value="branding">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center">
                <Palette className="mr-2 h-5 w-5"/> Logo & Branding
              </CardTitle>
              <CardDescription>Upload your logo and favicon</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Upload Logo (PNG/SVG)</Label>
                <Input type="file" accept="image/png,image/svg+xml" onChange={e => setLogo(e.target.files?.[0] || null)} />
                <p className="text-xs text-muted-foreground">Recommended: 200x50px transparent PNG</p>
              </div>
              <div className="space-y-2">
                <Label>Upload Favicon (.ico)</Label>
                <Input type="file" accept="image/x-icon,image/png" onChange={e => setFavicon(e.target.files?.[0] || null)} />
                <p className="text-xs text-muted-foreground">Recommended: 32x32px ICO or PNG</p>
              </div>
              <Button onClick={handleThemeSubmit} disabled={themeMutation.isPending}>
                <Save className="mr-2 h-4 w-4" />
                {themeMutation.isPending ? "Saving..." : "Save Branding"}
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="colors">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center">
                <Palette className="mr-2 h-5 w-5"/> Color Scheme
              </CardTitle>
              <CardDescription>Customize your brand colors</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Primary Color</Label>
                <div className="flex gap-2">
                  <Input type="color" className="w-14 p-1 h-10" value={primaryColor} onChange={e => setPrimaryColor(e.target.value)} />
                  <Input value={primaryColor} onChange={e => setPrimaryColor(e.target.value)} placeholder="#667eea" />
                </div>
                <p className="text-xs text-muted-foreground">Main brand color for buttons and highlights</p>
              </div>
              <div className="space-y-2">
                <Label>Secondary Color</Label>
                <div className="flex gap-2">
                  <Input type="color" className="w-14 p-1 h-10" value={secondaryColor} onChange={e => setSecondaryColor(e.target.value)} />
                  <Input value={secondaryColor} onChange={e => setSecondaryColor(e.target.value)} placeholder="#4338ca" />
                </div>
                <p className="text-xs text-muted-foreground">Secondary actions and borders</p>
              </div>
              <div className="space-y-2">
                <Label>Accent Color</Label>
                <div className="flex gap-2">
                  <Input type="color" className="w-14 p-1 h-10" value={accentColor} onChange={e => setAccentColor(e.target.value)} />
                  <Input value={accentColor} onChange={e => setAccentColor(e.target.value)} placeholder="#10b981" />
                </div>
                <p className="text-xs text-muted-foreground">Success messages and highlights</p>
              </div>
              <Button onClick={handleThemeSubmit} disabled={themeMutation.isPending}>
                <Save className="mr-2 h-4 w-4" />
                {themeMutation.isPending ? "Saving..." : "Save Colors"}
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="typography">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center">
                <Type className="mr-2 h-5 w-5"/> Typography
              </CardTitle>
              <CardDescription>Customize fonts and text sizes</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Body Font Family</Label>
                <Select value={fontFamily} onValueChange={setFontFamily}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Inter">Inter (Modern Sans)</SelectItem>
                    <SelectItem value="Roboto">Roboto</SelectItem>
                    <SelectItem value="Open Sans">Open Sans</SelectItem>
                    <SelectItem value="Lato">Lato</SelectItem>
                    <SelectItem value="Poppins">Poppins</SelectItem>
                    <SelectItem value="Montserrat">Montserrat</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Heading Font Family</Label>
                <Select value={headingFont} onValueChange={setHeadingFont}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Poppins">Poppins (Bold & Modern)</SelectItem>
                    <SelectItem value="Montserrat">Montserrat</SelectItem>
                    <SelectItem value="Playfair Display">Playfair Display</SelectItem>
                    <SelectItem value="Raleway">Raleway</SelectItem>
                    <SelectItem value="Work Sans">Work Sans</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Base Font Size</Label>
                <Select value={fontSize} onValueChange={setFontSize}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="small">Small (14px)</SelectItem>
                    <SelectItem value="medium">Medium (16px)</SelectItem>
                    <SelectItem value="large">Large (18px)</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <Button onClick={handleThemeSubmit} disabled={themeMutation.isPending}>
                <Save className="mr-2 h-4 w-4" />
                {themeMutation.isPending ? "Saving..." : "Save Typography"}
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="seo">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center">
                <Search className="mr-2 h-5 w-5"/> SEO Manager
              </CardTitle>
              <CardDescription>Configure SEO and search engine settings</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Meta Title Suffix</Label>
                <Input value={metaSuffix} onChange={e => setMetaSuffix(e.target.value)} placeholder="| PreIPO SIP" />
                <p className="text-xs text-muted-foreground">Appended to all page titles</p>
              </div>
              <div className="space-y-2">
                <Label>robots.txt</Label>
                <Textarea
                  rows={8}
                  value={robotsTxt}
                  onChange={e => setRobotsTxt(e.target.value)}
                  className="font-mono text-xs"
                  placeholder="User-agent: *&#10;Disallow: /admin/&#10;Allow: /"
                />
                <p className="text-xs text-muted-foreground">Controls search engine crawling</p>
              </div>
              <Button onClick={handleSeoSubmit} disabled={seoMutation.isPending}>
                <Save className="mr-2 h-4 w-4" />
                {seoMutation.isPending ? "Saving..." : "Save SEO Settings"}
              </Button>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
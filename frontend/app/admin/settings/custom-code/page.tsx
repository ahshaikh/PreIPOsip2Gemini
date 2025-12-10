// V-CMS-ENHANCEMENT-016 | Custom CSS/JS Code Editor
// Created: 2025-12-10 | Purpose: Allow admins to add custom CSS and JavaScript

'use client';

import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Switch } from '@/components/ui/switch';
import { Save, Code, AlertTriangle } from 'lucide-react';
import { toast } from 'sonner';
import api from '@/lib/api';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

interface CustomCodeConfig {
  custom_css_enabled: boolean;
  custom_css: string;
  custom_js_enabled: boolean;
  custom_js: string;
  custom_head_html: string;
  custom_footer_html: string;
}

const defaultConfig: CustomCodeConfig = {
  custom_css_enabled: false,
  custom_css: '',
  custom_js_enabled: false,
  custom_js: '',
  custom_head_html: '',
  custom_footer_html: '',
};

export default function CustomCodePage() {
  const queryClient = useQueryClient();
  const [config, setConfig] = useState<CustomCodeConfig>(defaultConfig);

  // Fetch existing custom code settings
  const { data, isLoading } = useQuery({
    queryKey: ['customCodeConfig'],
    queryFn: async () => {
      const res = await api.get('/admin/custom-code');
      return res.data;
    },
  });

  useEffect(() => {
    if (data?.config) {
      setConfig(data.config);
    }
  }, [data]);

  // Save mutation
  const saveMutation = useMutation({
    mutationFn: (configData: CustomCodeConfig) =>
      api.post('/admin/custom-code', { config: configData }),
    onSuccess: () => {
      toast.success('Custom code settings saved successfully');
      queryClient.invalidateQueries({ queryKey: ['customCodeConfig'] });
    },
    onError: () => {
      toast.error('Failed to save custom code settings');
    },
  });

  const handleSave = () => {
    saveMutation.mutate(config);
  };

  const updateConfig = (field: keyof CustomCodeConfig, value: any) => {
    setConfig((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  if (isLoading) {
    return <div className="p-8">Loading...</div>;
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">Custom Code Editor</h1>
          <p className="text-muted-foreground mt-1">
            Add custom CSS, JavaScript, and HTML to your site
          </p>
        </div>
        <Button onClick={handleSave} disabled={saveMutation.isPending}>
          <Save className="mr-2 h-4 w-4" />
          {saveMutation.isPending ? 'Saving...' : 'Save All Changes'}
        </Button>
      </div>

      <Alert>
        <AlertTriangle className="h-4 w-4" />
        <AlertDescription>
          <strong>Warning:</strong> Custom code can affect your site's functionality and security.
          Only add code from trusted sources. Ensure proper testing before enabling in production.
        </AlertDescription>
      </Alert>

      <Tabs defaultValue="css" className="space-y-4">
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="css">Custom CSS</TabsTrigger>
          <TabsTrigger value="js">Custom JavaScript</TabsTrigger>
          <TabsTrigger value="head">Head HTML</TabsTrigger>
          <TabsTrigger value="footer">Footer HTML</TabsTrigger>
        </TabsList>

        {/* Custom CSS */}
        <TabsContent value="css">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="flex items-center">
                    <Code className="mr-2 h-5 w-5" />
                    Custom CSS
                  </CardTitle>
                  <CardDescription>
                    Add custom styles to override or extend your theme
                  </CardDescription>
                </div>
                <div className="flex items-center gap-2">
                  <Label>Enabled</Label>
                  <Switch
                    checked={config.custom_css_enabled}
                    onCheckedChange={(checked) => updateConfig('custom_css_enabled', checked)}
                  />
                </div>
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>CSS Code</Label>
                <Textarea
                  value={config.custom_css}
                  onChange={(e) => updateConfig('custom_css', e.target.value)}
                  rows={20}
                  className="font-mono text-sm"
                  placeholder={`/* Add your custom CSS here */
.custom-button {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 8px;
  padding: 12px 24px;
}

/* Override theme colors */
:root {
  --primary: 220 70% 50%;
}`}
                />
                <p className="text-xs text-muted-foreground">
                  CSS will be injected into the &lt;head&gt; section when enabled
                </p>
              </div>

              <Alert>
                <AlertDescription>
                  <strong>Tips:</strong>
                  <ul className="list-disc list-inside mt-2 space-y-1 text-sm">
                    <li>Use specific selectors to avoid unintended overrides</li>
                    <li>Test thoroughly on all pages before enabling</li>
                    <li>Consider using CSS variables for consistency</li>
                    <li>Avoid !important unless absolutely necessary</li>
                  </ul>
                </AlertDescription>
              </Alert>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Custom JavaScript */}
        <TabsContent value="js">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="flex items-center">
                    <Code className="mr-2 h-5 w-5" />
                    Custom JavaScript
                  </CardTitle>
                  <CardDescription>
                    Add custom scripts for analytics, tracking, or interactive features
                  </CardDescription>
                </div>
                <div className="flex items-center gap-2">
                  <Label>Enabled</Label>
                  <Switch
                    checked={config.custom_js_enabled}
                    onCheckedChange={(checked) => updateConfig('custom_js_enabled', checked)}
                  />
                </div>
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>JavaScript Code</Label>
                <Textarea
                  value={config.custom_js}
                  onChange={(e) => updateConfig('custom_js', e.target.value)}
                  rows={20}
                  className="font-mono text-sm"
                  placeholder={`// Add your custom JavaScript here
(function() {
  console.log('Custom JS loaded');

  // Example: Add click tracking
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('track-click')) {
      // Your tracking logic here
    }
  });

  // Example: Custom initialization
  window.addEventListener('load', function() {
    // Your initialization code
  });
})();`}
                />
                <p className="text-xs text-muted-foreground">
                  JavaScript will be injected before the closing &lt;/body&gt; tag when enabled
                </p>
              </div>

              <Alert variant="destructive">
                <AlertTriangle className="h-4 w-4" />
                <AlertDescription>
                  <strong>Security Warning:</strong>
                  <ul className="list-disc list-inside mt-2 space-y-1 text-sm">
                    <li>Never add untrusted JavaScript code</li>
                    <li>Avoid inline scripts that could expose sensitive data</li>
                    <li>Use HTTPS for any external script sources</li>
                    <li>Be cautious with third-party tracking scripts</li>
                  </ul>
                </AlertDescription>
              </Alert>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Custom Head HTML */}
        <TabsContent value="head">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center">
                <Code className="mr-2 h-5 w-5" />
                Custom Head HTML
              </CardTitle>
              <CardDescription>
                Add custom HTML to the &lt;head&gt; section (meta tags, scripts, etc.)
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Head HTML</Label>
                <Textarea
                  value={config.custom_head_html}
                  onChange={(e) => updateConfig('custom_head_html', e.target.value)}
                  rows={15}
                  className="font-mono text-sm"
                  placeholder={`<!-- Add custom head HTML here -->
<!-- Example: Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'GA_MEASUREMENT_ID');
</script>

<!-- Example: Custom meta tags -->
<meta name="custom-meta" content="value">`}
                />
                <p className="text-xs text-muted-foreground">
                  Common uses: Analytics, verification tags, custom meta tags, external stylesheets
                </p>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Custom Footer HTML */}
        <TabsContent value="footer">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center">
                <Code className="mr-2 h-5 w-5" />
                Custom Footer HTML
              </CardTitle>
              <CardDescription>
                Add custom HTML before the closing &lt;/body&gt; tag
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Footer HTML</Label>
                <Textarea
                  value={config.custom_footer_html}
                  onChange={(e) => updateConfig('custom_footer_html', e.target.value)}
                  rows={15}
                  className="font-mono text-sm"
                  placeholder={`<!-- Add custom footer HTML here -->
<!-- Example: Chat widget -->
<script>
  // Your chat widget code
</script>

<!-- Example: Cookie consent banner -->
<div id="cookie-consent">
  <!-- Your consent banner HTML -->
</div>`}
                />
                <p className="text-xs text-muted-foreground">
                  Common uses: Chat widgets, cookie consent, conversion pixels, exit-intent popups
                </p>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}

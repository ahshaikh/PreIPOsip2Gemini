// V-FINAL-1730-203 | V-USER-MATERIALS-DOWNLOAD
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import {
  Download, Image, Video, FileText, Search, Filter, Eye,
  Calendar, FolderOpen, Sparkles, Share2, ExternalLink, Loader2
} from "lucide-react";

// Material categories
const MATERIAL_CATEGORIES = [
  { value: 'all', label: 'All Categories' },
  { value: 'banners', label: 'Banners & Images' },
  { value: 'videos', label: 'Videos' },
  { value: 'documents', label: 'Documents & PDFs' },
  { value: 'social', label: 'Social Media Posts' },
  { value: 'presentations', label: 'Presentations' },
];

// Material types
const MATERIAL_TYPES = [
  { value: 'all', label: 'All Types' },
  { value: 'image', label: 'Images' },
  { value: 'video', label: 'Videos' },
  { value: 'document', label: 'Documents' },
];

export default function MaterialsPage() {
  const [activeCategory, setActiveCategory] = useState('all');
  const [filterType, setFilterType] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [downloading, setDownloading] = useState<number | null>(null);

  // Fetch promotional materials
  const { data: materials, isLoading } = useQuery({
    queryKey: ['userPromotionalMaterials'],
    queryFn: async () => (await api.get('/user/promotional-materials')).data,
  });

  // Fetch download stats
  const { data: stats } = useQuery({
    queryKey: ['materialStats'],
    queryFn: async () => (await api.get('/user/promotional-materials/stats')).data,
  });

  // Filter materials
  const filteredMaterials = materials?.filter((material: any) => {
    const matchesCategory = activeCategory === 'all' || material.category === activeCategory;
    const matchesType = filterType === 'all' || material.type === filterType;
    const matchesSearch = searchQuery === '' ||
      material.title?.toLowerCase().includes(searchQuery.toLowerCase()) ||
      material.description?.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesCategory && matchesType && matchesSearch;
  });

  // Download handler
  const handleDownload = async (material: any) => {
    setDownloading(material.id);
    try {
      // Track download
      await api.post(`/user/promotional-materials/${material.id}/download`);

      // Open download
      const link = document.createElement('a');
      link.href = material.file_url;
      link.download = material.file_name || material.title;
      link.target = '_blank';
      document.body.appendChild(link);
      link.click();
      link.remove();

      toast.success("Download Started", { description: material.title });
    } catch (error) {
      toast.error("Download Failed");
    } finally {
      setDownloading(null);
    }
  };

  // Get icon based on type
  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'image': return Image;
      case 'video': return Video;
      case 'document': return FileText;
      default: return FolderOpen;
    }
  };

  // Format file size
  const formatFileSize = (bytes: number) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Promotional Materials</h1>
          <p className="text-muted-foreground">Download banners, videos, and documents to promote PreIPO SIP.</p>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="border-l-4 border-l-blue-500">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium">Total Materials</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{materials?.length || 0}</div>
          </CardContent>
        </Card>

        <Card className="border-l-4 border-l-green-500">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium">Images</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {materials?.filter((m: any) => m.type === 'image').length || 0}
            </div>
          </CardContent>
        </Card>

        <Card className="border-l-4 border-l-purple-500">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium">Videos</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {materials?.filter((m: any) => m.type === 'video').length || 0}
            </div>
          </CardContent>
        </Card>

        <Card className="border-l-4 border-l-orange-500">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium">Your Downloads</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_downloads || 0}</div>
          </CardContent>
        </Card>
      </div>

      {/* Search and Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-wrap items-center gap-4">
            <div className="relative flex-1 min-w-64">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search materials..."
                value={searchQuery}
                onChange={e => setSearchQuery(e.target.value)}
                className="pl-10"
              />
            </div>
            <Select value={filterType} onValueChange={setFilterType}>
              <SelectTrigger className="w-40">
                <Filter className="h-4 w-4 mr-2" />
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {MATERIAL_TYPES.map(type => (
                  <SelectItem key={type.value} value={type.value}>{type.label}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Category Tabs */}
      <Tabs value={activeCategory} onValueChange={setActiveCategory}>
        <TabsList className="flex-wrap">
          {MATERIAL_CATEGORIES.map(cat => (
            <TabsTrigger key={cat.value} value={cat.value}>{cat.label}</TabsTrigger>
          ))}
        </TabsList>

        <TabsContent value={activeCategory} className="mt-6">
          {isLoading ? (
            <div className="text-center py-12 text-muted-foreground">
              <Loader2 className="h-8 w-8 animate-spin mx-auto mb-4" />
              <p>Loading materials...</p>
            </div>
          ) : filteredMaterials?.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground">
              <FolderOpen className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p className="text-lg font-medium">No Materials Found</p>
              <p className="text-sm">
                {searchQuery ? 'Try different search terms.' : 'Materials will appear here when available.'}
              </p>
            </div>
          ) : (
            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
              {filteredMaterials?.map((material: any) => {
                const TypeIcon = getTypeIcon(material.type);
                return (
                  <Card key={material.id} className="overflow-hidden group">
                    {/* Preview */}
                    <div className="aspect-video bg-muted relative">
                      {material.type === 'image' && material.thumbnail_url ? (
                        <img
                          src={material.thumbnail_url}
                          alt={material.title}
                          className="object-cover w-full h-full"
                        />
                      ) : (
                        <div className="flex items-center justify-center h-full">
                          <TypeIcon className="h-16 w-16 text-muted-foreground/30" />
                        </div>
                      )}

                      {/* Overlay on hover */}
                      <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                        {material.preview_url && (
                          <Button size="sm" variant="secondary" asChild>
                            <a href={material.preview_url} target="_blank" rel="noopener">
                              <Eye className="h-4 w-4 mr-1" /> Preview
                            </a>
                          </Button>
                        )}
                        <Button
                          size="sm"
                          onClick={() => handleDownload(material)}
                          disabled={downloading === material.id}
                        >
                          {downloading === material.id ? (
                            <Loader2 className="h-4 w-4 animate-spin mr-1" />
                          ) : (
                            <Download className="h-4 w-4 mr-1" />
                          )}
                          Download
                        </Button>
                      </div>
                    </div>

                    {/* Content */}
                    <CardContent className="p-4">
                      <div className="flex items-start justify-between gap-2">
                        <div className="flex-1 min-w-0">
                          <h3 className="font-medium truncate">{material.title}</h3>
                          <p className="text-sm text-muted-foreground line-clamp-2">{material.description}</p>
                        </div>
                        <Badge variant="outline" className="shrink-0">
                          <TypeIcon className="h-3 w-3 mr-1" />
                          {material.type}
                        </Badge>
                      </div>

                      <div className="flex items-center justify-between mt-4 text-xs text-muted-foreground">
                        <div className="flex items-center gap-4">
                          {material.file_size && (
                            <span>{formatFileSize(material.file_size)}</span>
                          )}
                          {material.dimensions && (
                            <span>{material.dimensions}</span>
                          )}
                        </div>
                        <div className="flex items-center gap-1">
                          <Download className="h-3 w-3" />
                          {material.download_count || 0}
                        </div>
                      </div>

                      {/* Download Button */}
                      <Button
                        className="w-full mt-4"
                        onClick={() => handleDownload(material)}
                        disabled={downloading === material.id}
                      >
                        {downloading === material.id ? (
                          <>
                            <Loader2 className="h-4 w-4 animate-spin mr-2" />
                            Downloading...
                          </>
                        ) : (
                          <>
                            <Download className="h-4 w-4 mr-2" />
                            Download
                          </>
                        )}
                      </Button>
                    </CardContent>
                  </Card>
                );
              })}
            </div>
          )}
        </TabsContent>
      </Tabs>

      {/* Tips */}
      <Card className="bg-gradient-to-r from-blue-500/10 to-purple-500/10 border-blue-500/20">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Sparkles className="h-5 w-5 text-blue-500" /> Tips for Using Materials
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid md:grid-cols-3 gap-4">
            <div className="p-4 bg-background/50 rounded-lg border">
              <Share2 className="h-5 w-5 text-blue-500 mb-2" />
              <h4 className="font-medium text-sm">Social Media</h4>
              <p className="text-xs text-muted-foreground">
                Use banners and images for your social media posts to attract more attention.
              </p>
            </div>
            <div className="p-4 bg-background/50 rounded-lg border">
              <Video className="h-5 w-5 text-purple-500 mb-2" />
              <h4 className="font-medium text-sm">WhatsApp Status</h4>
              <p className="text-xs text-muted-foreground">
                Share videos and images on your WhatsApp status to reach your contacts.
              </p>
            </div>
            <div className="p-4 bg-background/50 rounded-lg border">
              <FileText className="h-5 w-5 text-green-500 mb-2" />
              <h4 className="font-medium text-sm">Presentations</h4>
              <p className="text-xs text-muted-foreground">
                Use our presentation materials when explaining PreIPO SIP to potential investors.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

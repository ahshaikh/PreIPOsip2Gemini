// V-FINAL-1730-204 | V-ADMIN-PROMOTIONAL-MATERIALS
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState, useRef } from "react";
import {
  Plus, Upload, Image, Video, FileText, Trash2, Edit, Eye,
  Download, Search, Filter, FolderOpen, Calendar, MoreHorizontal,
  CheckCircle, XCircle, Loader2
} from "lucide-react";

// Material categories
const MATERIAL_CATEGORIES = [
  { value: 'banners', label: 'Banners & Images' },
  { value: 'videos', label: 'Videos' },
  { value: 'documents', label: 'Documents & PDFs' },
  { value: 'social', label: 'Social Media Posts' },
  { value: 'presentations', label: 'Presentations' },
];

// Material types
const MATERIAL_TYPES = [
  { value: 'image', label: 'Image', accept: 'image/*', icon: Image },
  { value: 'video', label: 'Video', accept: 'video/*', icon: Video },
  { value: 'document', label: 'Document', accept: '.pdf,.doc,.docx,.ppt,.pptx', icon: FileText },
];

export default function PromotionalMaterialsPage() {
  const queryClient = useQueryClient();
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingMaterial, setEditingMaterial] = useState<any>(null);
  const [deleteConfirmMaterial, setDeleteConfirmMaterial] = useState<any>(null);
  const [filterCategory, setFilterCategory] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');

  // Form state
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [category, setCategory] = useState('banners');
  const [materialType, setMaterialType] = useState('image');
  const [isActive, setIsActive] = useState(true);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [uploading, setUploading] = useState(false);

  // Fetch materials
  const { data: materials, isLoading } = useQuery({
    queryKey: ['adminPromotionalMaterials'],
    queryFn: async () => (await api.get('/admin/promotional-materials')).data,
  });

  // Fetch stats
  const { data: stats } = useQuery({
    queryKey: ['promotionalMaterialsStats'],
    queryFn: async () => (await api.get('/admin/promotional-materials/stats')).data,
  });

  const resetForm = () => {
    setTitle('');
    setDescription('');
    setCategory('banners');
    setMaterialType('image');
    setIsActive(true);
    setSelectedFile(null);
    setEditingMaterial(null);
  };

  const handleEdit = (material: any) => {
    setEditingMaterial(material);
    setTitle(material.title);
    setDescription(material.description || '');
    setCategory(material.category);
    setMaterialType(material.type);
    setIsActive(material.is_active !== false);
    setIsDialogOpen(true);
  };

  // Upload/Update mutation
  const mutation = useMutation({
    mutationFn: async (formData: FormData) => {
      if (editingMaterial) {
        return api.put(`/admin/promotional-materials/${editingMaterial.id}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });
      }
      return api.post('/admin/promotional-materials', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    },
    onSuccess: () => {
      toast.success(editingMaterial ? "Material Updated" : "Material Uploaded");
      queryClient.invalidateQueries({ queryKey: ['adminPromotionalMaterials'] });
      queryClient.invalidateQueries({ queryKey: ['promotionalMaterialsStats'] });
      setIsDialogOpen(false);
      resetForm();
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  // Delete mutation
  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/promotional-materials/${id}`),
    onSuccess: () => {
      toast.success("Material Deleted");
      queryClient.invalidateQueries({ queryKey: ['adminPromotionalMaterials'] });
      setDeleteConfirmMaterial(null);
    }
  });

  // Toggle active mutation
  const toggleActiveMutation = useMutation({
    mutationFn: (material: any) => api.put(`/admin/promotional-materials/${material.id}`, { is_active: !material.is_active }),
    onSuccess: () => {
      toast.success("Status Updated");
      queryClient.invalidateQueries({ queryKey: ['adminPromotionalMaterials'] });
    }
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingMaterial && !selectedFile) {
      toast.error("Please select a file");
      return;
    }

    setUploading(true);
    const formData = new FormData();
    formData.append('title', title);
    formData.append('description', description);
    formData.append('category', category);
    formData.append('type', materialType);
    formData.append('is_active', isActive.toString());

    if (selectedFile) {
      formData.append('file', selectedFile);
    }

    try {
      await mutation.mutateAsync(formData);
    } finally {
      setUploading(false);
    }
  };

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setSelectedFile(file);
    }
  };

  // Filter materials
  const filteredMaterials = materials?.filter((material: any) => {
    const matchesCategory = filterCategory === 'all' || material.category === filterCategory;
    const matchesSearch = searchQuery === '' ||
      material.title?.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  // Get type icon
  const getTypeIcon = (type: string) => {
    const typeObj = MATERIAL_TYPES.find(t => t.value === type);
    return typeObj?.icon || FileText;
  };

  // Format file size
  const formatFileSize = (bytes: number) => {
    if (!bytes) return 'N/A';
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
          <p className="text-muted-foreground">Upload and manage promotional content for users to download.</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if(!open) resetForm(); }}>
          <Button onClick={() => setIsDialogOpen(true)}>
            <Plus className="mr-2 h-4 w-4" /> Upload Material
          </Button>
          <DialogContent className="max-w-lg">
            <DialogHeader>
              <DialogTitle>{editingMaterial ? 'Edit Material' : 'Upload New Material'}</DialogTitle>
              <DialogDescription>
                {editingMaterial ? 'Update the material details.' : 'Upload images, videos, or documents for users to download.'}
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Category</Label>
                  <Select value={category} onValueChange={setCategory}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      {MATERIAL_CATEGORIES.map(cat => (
                        <SelectItem key={cat.value} value={cat.value}>{cat.label}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Type</Label>
                  <Select value={materialType} onValueChange={setMaterialType}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      {MATERIAL_TYPES.map(type => (
                        <SelectItem key={type.value} value={type.value}>
                          <div className="flex items-center gap-2">
                            <type.icon className="h-4 w-4" />
                            {type.label}
                          </div>
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="space-y-2">
                <Label>Title</Label>
                <Input
                  value={title}
                  onChange={e => setTitle(e.target.value)}
                  placeholder="e.g., Investment Banner - Blue Theme"
                  required
                />
              </div>

              <div className="space-y-2">
                <Label>Description</Label>
                <Textarea
                  value={description}
                  onChange={e => setDescription(e.target.value)}
                  placeholder="Brief description of this material..."
                  rows={3}
                />
              </div>

              <div className="space-y-2">
                <Label>File {editingMaterial && '(Leave empty to keep existing)'}</Label>
                <div className="border-2 border-dashed rounded-lg p-6 text-center">
                  {selectedFile ? (
                    <div className="space-y-2">
                      <CheckCircle className="h-8 w-8 text-green-500 mx-auto" />
                      <p className="font-medium">{selectedFile.name}</p>
                      <p className="text-sm text-muted-foreground">
                        {formatFileSize(selectedFile.size)}
                      </p>
                      <Button type="button" variant="outline" size="sm" onClick={() => setSelectedFile(null)}>
                        Remove
                      </Button>
                    </div>
                  ) : (
                    <div className="space-y-2">
                      <Upload className="h-8 w-8 mx-auto text-muted-foreground" />
                      <p className="text-sm text-muted-foreground">
                        Click to select or drag and drop
                      </p>
                      <Button type="button" variant="outline" size="sm" onClick={() => fileInputRef.current?.click()}>
                        Select File
                      </Button>
                    </div>
                  )}
                  <input
                    ref={fileInputRef}
                    type="file"
                    className="hidden"
                    accept={MATERIAL_TYPES.find(t => t.value === materialType)?.accept}
                    onChange={handleFileSelect}
                  />
                </div>
              </div>

              <div className="flex items-center justify-between p-4 bg-muted/50 rounded-lg">
                <div>
                  <Label>Active</Label>
                  <p className="text-xs text-muted-foreground">Make available for users to download</p>
                </div>
                <Switch checked={isActive} onCheckedChange={setIsActive} />
              </div>

              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>Cancel</Button>
                <Button type="submit" disabled={uploading || mutation.isPending}>
                  {uploading || mutation.isPending ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      {editingMaterial ? 'Updating...' : 'Uploading...'}
                    </>
                  ) : (
                    editingMaterial ? 'Update Material' : 'Upload Material'
                  )}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Materials</CardTitle>
            <FolderOpen className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_materials || 0}</div>
            <p className="text-xs text-muted-foreground">{stats?.active_materials || 0} active</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Downloads</CardTitle>
            <Download className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_downloads || 0}</div>
            <p className="text-xs text-muted-foreground">All time</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">This Month</CardTitle>
            <Calendar className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.downloads_this_month || 0}</div>
            <p className="text-xs text-muted-foreground">downloads</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Storage Used</CardTitle>
            <Upload className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatFileSize(stats?.total_storage || 0)}</div>
            <p className="text-xs text-muted-foreground">total</p>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex items-center gap-4">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search materials..."
                value={searchQuery}
                onChange={e => setSearchQuery(e.target.value)}
                className="pl-10"
              />
            </div>
            <Select value={filterCategory} onValueChange={setFilterCategory}>
              <SelectTrigger className="w-48">
                <Filter className="h-4 w-4 mr-2" />
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Categories</SelectItem>
                {MATERIAL_CATEGORIES.map(cat => (
                  <SelectItem key={cat.value} value={cat.value}>{cat.label}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Materials Table */}
      <Card>
        <CardHeader>
          <CardTitle>All Materials</CardTitle>
          <CardDescription>Manage uploaded promotional content</CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Material</TableHead>
                <TableHead>Category</TableHead>
                <TableHead>Type</TableHead>
                <TableHead className="text-center">Downloads</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Uploaded</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {isLoading ? (
                <TableRow>
                  <TableCell colSpan={7} className="text-center">Loading...</TableCell>
                </TableRow>
              ) : filteredMaterials?.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={7} className="text-center text-muted-foreground py-8">
                    No materials found
                  </TableCell>
                </TableRow>
              ) : (
                filteredMaterials?.map((material: any) => {
                  const TypeIcon = getTypeIcon(material.type);
                  return (
                    <TableRow key={material.id}>
                      <TableCell>
                        <div className="flex items-center gap-3">
                          <div className="h-10 w-10 rounded bg-muted flex items-center justify-center overflow-hidden">
                            {material.thumbnail_url ? (
                              <img src={material.thumbnail_url} alt="" className="object-cover w-full h-full" />
                            ) : (
                              <TypeIcon className="h-5 w-5 text-muted-foreground" />
                            )}
                          </div>
                          <div>
                            <p className="font-medium">{material.title}</p>
                            <p className="text-xs text-muted-foreground">{formatFileSize(material.file_size)}</p>
                          </div>
                        </div>
                      </TableCell>
                      <TableCell>
                        <Badge variant="outline">
                          {MATERIAL_CATEGORIES.find(c => c.value === material.category)?.label || material.category}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <TypeIcon className="h-4 w-4 text-muted-foreground" />
                          {material.type}
                        </div>
                      </TableCell>
                      <TableCell className="text-center">{material.download_count || 0}</TableCell>
                      <TableCell>
                        <Badge variant={material.is_active ? 'default' : 'secondary'}>
                          {material.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                      </TableCell>
                      <TableCell>{new Date(material.created_at).toLocaleDateString()}</TableCell>
                      <TableCell>
                        <div className="flex items-center gap-1">
                          {material.file_url && (
                            <Button variant="ghost" size="sm" asChild>
                              <a href={material.file_url} target="_blank" rel="noopener">
                                <Eye className="h-4 w-4" />
                              </a>
                            </Button>
                          )}
                          <Button variant="ghost" size="sm" onClick={() => handleEdit(material)}>
                            <Edit className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => toggleActiveMutation.mutate(material)}
                          >
                            {material.is_active ? (
                              <XCircle className="h-4 w-4 text-red-500" />
                            ) : (
                              <CheckCircle className="h-4 w-4 text-green-500" />
                            )}
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setDeleteConfirmMaterial(material)}
                            className="text-destructive hover:text-destructive"
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  );
                })
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      {/* Delete Confirmation */}
      <AlertDialog open={!!deleteConfirmMaterial} onOpenChange={() => setDeleteConfirmMaterial(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Material</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete "{deleteConfirmMaterial?.title}"? This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => deleteMutation.mutate(deleteConfirmMaterial.id)}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              {deleteMutation.isPending ? "Deleting..." : "Delete"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}

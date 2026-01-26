'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import companyApi from '@/lib/companyApi';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { Plus, Edit, Trash2, Upload, User, Star, Linkedin, Twitter } from 'lucide-react';
import Image from 'next/image';

// FIX: Get backend URL from environment or fallback to localhost
// Remove /api/v1 suffix as we only need the base server URL for storage URLs
// Using a more robust method to strip the suffix
const getBackendURL = () => {
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';
  return apiUrl.endsWith('/api/v1') ? apiUrl.slice(0, -7) : apiUrl.replace('/api/v1', '');
};
const BACKEND_URL = getBackendURL();

export default function TeamMembersPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingMember, setEditingMember] = useState<any>(null);
  const [photoFile, setPhotoFile] = useState<File | null>(null);
  const [photoPreview, setPhotoPreview] = useState<string>('');

  const [formData, setFormData] = useState({
    name: '',
    designation: '',
    bio: '',
    linkedin_url: '',
    twitter_url: '',
    display_order: 0,
    is_key_member: false,
  });

  const { data: teamMembers, isLoading } = useQuery({
    queryKey: ['team-members'],
    queryFn: async () => {
      const response = await companyApi.get('/team-members');
      return response.data;
    },
  });

  const saveMutation = useMutation({
    mutationFn: async (data: FormData) => {
      if (editingMember) {
        return companyApi.post(`/team-members/${editingMember.id}?_method=PUT`, data, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      } else {
        return companyApi.post('/team-members', data, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['team-members'] });
      queryClient.invalidateQueries({ queryKey: ['company-dashboard'] });
      setIsDialogOpen(false);
      resetForm();
      toast.success(editingMember ? 'Team member updated' : 'Team member added');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Operation failed');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: number) => companyApi.delete(`/team-members/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['team-members'] });
      queryClient.invalidateQueries({ queryKey: ['company-dashboard'] });
      toast.success('Team member removed');
    },
  });

  const handlePhotoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setPhotoFile(file);
      const reader = new FileReader();
      reader.onloadend = () => {
        setPhotoPreview(reader.result as string);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    const data = new FormData();
    data.append('name', formData.name);
    data.append('designation', formData.designation);
    data.append('bio', formData.bio);
    data.append('linkedin_url', formData.linkedin_url);
    data.append('twitter_url', formData.twitter_url);
    data.append('display_order', formData.display_order.toString());
    data.append('is_key_member', formData.is_key_member ? '1' : '0');

    if (photoFile) {
      data.append('photo', photoFile);
    }

    saveMutation.mutate(data);
  };

  const handleEdit = (member: any) => {
    setEditingMember(member);
    setFormData({
      name: member.name,
      designation: member.designation,
      bio: member.bio || '',
      linkedin_url: member.linkedin_url || '',
      twitter_url: member.twitter_url || '',
      display_order: member.display_order || 0,
      is_key_member: member.is_key_member,
    });
    // FIX: Construct proper URL for existing photo
    if (member.photo_path) {
      setPhotoPreview(`${BACKEND_URL}/storage/${member.photo_path}`);
    }
    setIsDialogOpen(true);
  };

  const resetForm = () => {
    setEditingMember(null);
    setPhotoFile(null);
    setPhotoPreview('');
    setFormData({
      name: '',
      designation: '',
      bio: '',
      linkedin_url: '',
      twitter_url: '',
      display_order: 0,
      is_key_member: false,
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Team Members</h1>
          <p className="text-muted-foreground mt-2">
            Showcase your leadership team and key personnel to investors
          </p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => {
          setIsDialogOpen(open);
          if (!open) resetForm();
        }}>
          <DialogTrigger asChild>
            <Button onClick={resetForm}>
              <Plus className="mr-2 h-4 w-4" /> Add Team Member
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{editingMember ? 'Edit Team Member' : 'Add Team Member'}</DialogTitle>
              <DialogDescription>
                Add leadership team and key personnel information
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label>Profile Photo</Label>
                <div className="flex items-center gap-4">
                  <div className="w-20 h-20 border-2 border-dashed rounded-full flex items-center justify-center bg-gray-50 dark:bg-gray-900 overflow-hidden">
                    {photoPreview ? (
                      // FIX: Display photo preview with proper error handling
                      // Use unoptimized for localhost URLs to bypass Next.js image proxy
                      <Image
                        src={photoPreview}
                        alt="Preview"
                        width={80}
                        height={80}
                        className="object-cover"
                        unoptimized
                      />
                    ) : (
                      <User className="h-8 w-8 text-muted-foreground" />
                    )}
                  </div>
                  <Input
                    type="file"
                    accept="image/jpeg,image/png,image/jpg"
                    onChange={handlePhotoChange}
                    className="flex-1"
                    disabled={saveMutation.isPending}
                  />
                </div>
                <p className="text-xs text-muted-foreground">Upload a professional photo (PNG, JPG - Max 2MB)</p>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="name">Full Name *</Label>
                  <Input
                    id="name"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="designation">Designation *</Label>
                  <Input
                    id="designation"
                    placeholder="CEO, CTO, Founder"
                    value={formData.designation}
                    onChange={(e) => setFormData({ ...formData, designation: e.target.value })}
                    required
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="bio">Biography</Label>
                <Textarea
                  id="bio"
                  rows={4}
                  placeholder="Brief professional background and expertise..."
                  value={formData.bio}
                  onChange={(e) => setFormData({ ...formData, bio: e.target.value })}
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="linkedin_url">LinkedIn Profile</Label>
                  <Input
                    id="linkedin_url"
                    type="url"
                    placeholder="https://linkedin.com/in/..."
                    value={formData.linkedin_url}
                    onChange={(e) => setFormData({ ...formData, linkedin_url: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="twitter_url">Twitter/X Profile</Label>
                  <Input
                    id="twitter_url"
                    type="url"
                    placeholder="https://twitter.com/..."
                    value={formData.twitter_url}
                    onChange={(e) => setFormData({ ...formData, twitter_url: e.target.value })}
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="display_order">Display Order</Label>
                  <Input
                    id="display_order"
                    type="number"
                    min="0"
                    value={formData.display_order}
                    onChange={(e) => setFormData({ ...formData, display_order: parseInt(e.target.value) })}
                  />
                  <p className="text-xs text-muted-foreground">Lower numbers appear first</p>
                </div>
                <div className="flex items-end pb-2">
                  <div className="flex items-center space-x-2">
                    <input
                      type="checkbox"
                      id="is_key_member"
                      checked={formData.is_key_member}
                      onChange={(e) => setFormData({ ...formData, is_key_member: e.target.checked })}
                      className="rounded"
                    />
                    <Label htmlFor="is_key_member" className="cursor-pointer">
                      Mark as Key Member
                    </Label>
                  </div>
                </div>
              </div>

              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>
                  Cancel
                </Button>
                <Button type="submit" disabled={saveMutation.isPending}>
                  {saveMutation.isPending ? 'Saving...' : editingMember ? 'Update' : 'Add Member'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Your Team</CardTitle>
          <CardDescription>
            {teamMembers?.data?.length || 0} team members added
          </CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8">Loading team members...</div>
          ) : teamMembers?.data?.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground">
              <User className="h-16 w-16 mx-auto mb-4 text-muted-foreground/50" />
              <p className="text-lg font-medium mb-2">No team members added yet</p>
              <p className="text-sm mb-4">Showcase your leadership team to build investor confidence</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {teamMembers?.data?.map((member: any) => (
                <Card key={member.id} className="overflow-hidden">
                  <CardContent className="p-6">
                    <div className="flex flex-col items-center text-center">
                      <div className="relative mb-4">
                        <div className="w-24 h-24 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                          {member.photo_path ? (
                            // FIX: Construct proper URL for team member photo
                            // Backend stores path as "team-photos/company_id/filename.png"
                            // Storage is accessible at "{BACKEND_URL}/storage/team-photos/..."
                            // Use unoptimized to bypass Next.js image proxy for localhost
                            <Image
                              src={`${BACKEND_URL}/storage/${member.photo_path}`}
                              alt={member.name}
                              width={96}
                              height={96}
                              className="object-cover"
                              unoptimized
                              onError={(e) => {
                                console.error('Failed to load team member photo:', member.photo_path);
                                // FIX: Fallback to placeholder on error
                                e.currentTarget.style.display = 'none';
                              }}
                            />
                          ) : (
                            <User className="h-12 w-12 text-muted-foreground" />
                          )}
                        </div>
                        {member.is_key_member && (
                          <div className="absolute -top-1 -right-1 bg-yellow-400 rounded-full p-1">
                            <Star className="h-4 w-4 text-white fill-white" />
                          </div>
                        )}
                      </div>
                      <h3 className="font-semibold text-lg">{member.name}</h3>
                      <p className="text-sm text-muted-foreground mb-3">{member.designation}</p>
                      {member.is_key_member && (
                        <Badge className="mb-3 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100">
                          Key Member
                        </Badge>
                      )}
                      {member.bio && (
                        <p className="text-sm text-muted-foreground line-clamp-3 mb-4">{member.bio}</p>
                      )}
                      <div className="flex items-center gap-2 mb-4">
                        {member.linkedin_url && (
                          <a href={member.linkedin_url} target="_blank" rel="noopener noreferrer">
                            <Button variant="outline" size="sm">
                              <Linkedin className="h-4 w-4" />
                            </Button>
                          </a>
                        )}
                        {member.twitter_url && (
                          <a href={member.twitter_url} target="_blank" rel="noopener noreferrer">
                            <Button variant="outline" size="sm">
                              <Twitter className="h-4 w-4" />
                            </Button>
                          </a>
                        )}
                      </div>
                      <div className="flex gap-2 w-full">
                        <Button
                          variant="outline"
                          size="sm"
                          className="flex-1"
                          onClick={() => handleEdit(member)}
                        >
                          <Edit className="h-4 w-4 mr-1" /> Edit
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => deleteMutation.mutate(member.id)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      <Card className="bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800">
        <CardContent className="pt-6">
          <div className="flex items-start gap-3">
            <Star className="h-5 w-5 text-blue-600 mt-0.5" />
            <div>
              <h3 className="font-semibold text-blue-900 dark:text-blue-100 mb-1">
                Why Add Your Team?
              </h3>
              <ul className="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                <li>• Builds credibility and trust with potential investors</li>
                <li>• Showcases leadership experience and expertise</li>
                <li>• Helps investors understand your company's capabilities</li>
                <li>• Mark key members to highlight C-suite executives</li>
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

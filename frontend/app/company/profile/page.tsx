'use client';

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useState, useEffect } from 'react';
import companyApi from '@/lib/companyApi';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { Upload, Save, Building2, AlertCircle } from 'lucide-react';
import Image from 'next/image';

// FIX: Get backend URL from environment or fallback to localhost
// Remove /api/v1 suffix as we only need the base server URL for storage URLs
// Using a more robust method to strip the suffix
const getBackendURL = () => {
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';
  return apiUrl.endsWith('/api/v1') ? apiUrl.slice(0, -7) : apiUrl.replace('/api/v1', '');
};
const BACKEND_URL = getBackendURL();

// FIX: Add TypeScript interface for type safety
interface CompanyFormData {
  name: string;
  description: string;
  website: string;
  sector: string;
  founded_year: string;
  headquarters: string;
  ceo_name: string;
  latest_valuation: string;
  funding_stage: string;
  total_funding: string;
  linkedin_url: string;
  twitter_url: string;
  facebook_url: string;
}

export default function CompanyProfilePage() {
  const queryClient = useQueryClient();
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const [logoPreview, setLogoPreview] = useState<string>('');
  const [imageError, setImageError] = useState<boolean>(false);

  const { data: profileData, isLoading } = useQuery({
    queryKey: ['company-profile'],
    queryFn: async () => {
      const response = await companyApi.get('/profile');
      return response.data;
    },
  });

  const company = profileData?.company;

  // FIX: Use proper TypeScript typing for form data
  const [formData, setFormData] = useState<CompanyFormData>({
    name: '',
    description: '',
    website: '',
    sector: '',
    founded_year: '',
    headquarters: '',
    ceo_name: '',
    latest_valuation: '',
    funding_stage: '',
    total_funding: '',
    linkedin_url: '',
    twitter_url: '',
    facebook_url: '',
  });

  // FIX: Changed from useState to useEffect - useState doesn't support dependency arrays!
  // This was causing form data to never update when profile data loaded
  useEffect(() => {
    if (company) {
      setFormData({
        name: company.name || '',
        description: company.description || '',
        website: company.website || '',
        sector: company.sector || '',
        founded_year: company.founded_year || '',
        headquarters: company.headquarters || '',
        ceo_name: company.ceo_name || '',
        // FIX: Convert numeric values to strings for input fields, handle null/undefined
        latest_valuation: company.latest_valuation?.toString() || '',
        funding_stage: company.funding_stage || '',
        total_funding: company.total_funding?.toString() || '',
        linkedin_url: company.linkedin_url || '',
        twitter_url: company.twitter_url || '',
        facebook_url: company.facebook_url || '',
      });
      // FIX: Reset image error state when new company data loads
      setImageError(false);
    }
  }, [company]);

  const updateProfileMutation = useMutation({
    mutationFn: async (data: CompanyFormData) => {
      // FIX: Convert string values to proper types for backend
      const payload = {
        ...data,
        // FIX: Convert numeric fields to numbers if they have values
        latest_valuation: data.latest_valuation ? parseFloat(data.latest_valuation) : null,
        total_funding: data.total_funding ? parseFloat(data.total_funding) : null,
      };
      return companyApi.put('/company-profile/update', payload);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['company-profile'] });
      queryClient.invalidateQueries({ queryKey: ['company-dashboard'] });
      toast.success('Profile updated successfully');
    },
    onError: (error: any) => {
      // FIX: More detailed error handling
      const errorMessage = error.response?.data?.message || 'Failed to update profile';
      const errors = error.response?.data?.errors;

      if (errors) {
        // FIX: Show first validation error
        const firstError = Object.values(errors)[0];
        toast.error(Array.isArray(firstError) ? firstError[0] : errorMessage);
      } else {
        toast.error(errorMessage);
      }

      console.error('Profile update error:', error.response?.data);
    },
  });

  const uploadLogoMutation = useMutation({
    mutationFn: async (file: File) => {
      const formData = new FormData();
      formData.append('logo', file);
      return companyApi.post('/company-profile/upload-logo', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    },
    onSuccess: () => {
      // FIX: Reset local state first
      setLogoFile(null);
      setLogoPreview('');
      setImageError(false);

      // FIX: Invalidate queries to refresh data
      queryClient.invalidateQueries({ queryKey: ['company-profile'] });
      queryClient.invalidateQueries({ queryKey: ['company-dashboard'] });

      toast.success('Logo uploaded successfully');
    },
    onError: (error: any) => {
      // FIX: More detailed error handling
      const errorMessage = error.response?.data?.message || 'Failed to upload logo';
      const errors = error.response?.data?.errors;

      if (errors) {
        const firstError = Object.values(errors)[0];
        toast.error(Array.isArray(firstError) ? firstError[0] : errorMessage);
      } else {
        toast.error(errorMessage);
      }

      console.error('Logo upload error:', error.response?.data);
    },
  });

  const handleLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      // FIX: Validate file size (max 2MB as per backend validation)
      if (file.size > 2048 * 1024) {
        toast.error('Logo file size must be less than 2MB');
        return;
      }

      // FIX: Validate file type
      const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/svg+xml'];
      if (!allowedTypes.includes(file.type)) {
        toast.error('Only JPEG, PNG, JPG, and SVG images are allowed');
        return;
      }

      setLogoFile(file);
      setImageError(false); // Reset error state when new file is selected
      const reader = new FileReader();
      reader.onloadend = () => {
        setLogoPreview(reader.result as string);
      };
      reader.onerror = () => {
        toast.error('Failed to read file');
      };
      reader.readAsDataURL(file);
    }
  };

  const handleLogoUpload = () => {
    if (!logoFile) {
      toast.error('Please select a logo file first');
      return;
    }
    uploadLogoMutation.mutate(logoFile);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    // FIX: Validate required fields before submission
    if (!formData.name?.trim()) {
      toast.error('Company name is required');
      return;
    }

    if (!formData.sector?.trim()) {
      toast.error('Industry sector is required');
      return;
    }

    // FIX: Validate founded year if provided
    if (formData.founded_year && formData.founded_year.trim()) {
      const year = parseInt(formData.founded_year);
      const currentYear = new Date().getFullYear();

      if (isNaN(year) || year < 1800 || year > currentYear + 1) {
        toast.error(`Founded year must be between 1800 and ${currentYear + 1}`);
        return;
      }

      if (formData.founded_year.length !== 4) {
        toast.error('Founded year must be a 4-digit year');
        return;
      }
    }

    // FIX: Validate numeric fields if provided
    if (formData.latest_valuation && formData.latest_valuation.trim()) {
      const valuation = parseFloat(formData.latest_valuation);
      if (isNaN(valuation) || valuation < 0) {
        toast.error('Latest valuation must be a positive number');
        return;
      }
    }

    if (formData.total_funding && formData.total_funding.trim()) {
      const funding = parseFloat(formData.total_funding);
      if (isNaN(funding) || funding < 0) {
        toast.error('Total funding must be a positive number');
        return;
      }
    }

    // FIX: Validate URL fields if provided
    const urlFields = [
      { name: 'Website', value: formData.website },
      { name: 'LinkedIn URL', value: formData.linkedin_url },
      { name: 'Twitter URL', value: formData.twitter_url },
      { name: 'Facebook URL', value: formData.facebook_url },
    ];

    for (const field of urlFields) {
      if (field.value && field.value.trim()) {
        try {
          new URL(field.value);
        } catch {
          toast.error(`${field.name} is not a valid URL`);
          return;
        }
      }
    }

    updateProfileMutation.mutate(formData);
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-lg">Loading profile...</div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Company Profile</h1>
        <p className="text-muted-foreground mt-2">
          Manage your company information and make it stand out to investors
        </p>
      </div>

      {/* Logo Upload */}
      <Card>
        <CardHeader>
          <CardTitle>Company Logo</CardTitle>
          <CardDescription>Upload your company logo (PNG, JPG, SVG - Max 2MB)</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex items-center gap-6">
            <div className="w-24 h-24 border-2 border-dashed rounded-lg flex items-center justify-center bg-gray-50 dark:bg-gray-900 overflow-hidden">
              {logoPreview ? (
                <Image src={logoPreview} alt="Logo preview" width={80} height={80} className="object-contain" />
              ) : company?.logo ? (
                <Image src={`${process.env.NEXT_PUBLIC_API_URL}/storage/${company.logo}`}
                  alt="Company logo"
                  width={80}
                  height={80}
                  className="object-contain"
                />
              ) : company?.logo && !imageError ? (
                // FIX: Construct proper URL for existing logo
                // Backend stores path as "company-logos/filename.png"
                // Storage is accessible at "{BACKEND_URL}/storage/company-logos/filename.png"
                <Image
                  src={`${BACKEND_URL}/storage/${company.logo}`}
                  alt="Company logo"
                  width={80}
                  height={80}
                  className="object-contain"
                  onError={() => {
                    console.error('Failed to load logo:', `${BACKEND_URL}/storage/${company.logo}`);
                    setImageError(true);
                  }}
                />
              ) : imageError ? (
                // FIX: Show error state when image fails to load
                <div className="flex flex-col items-center justify-center text-center p-2">
                  <AlertCircle className="h-8 w-8 text-destructive mb-1" />
                  <span className="text-xs text-muted-foreground">Failed to load logo</span>
                </div>
              ) : (
                // FIX: Show placeholder when no logo exists
                <Building2 className="h-10 w-10 text-muted-foreground" />
              )}
            </div>
            <div className="flex-1">
              <Input
                type="file"
                accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                onChange={handleLogoChange}
                className="mb-2"
                disabled={uploadLogoMutation.isPending}
              />
              {logoFile && (
                <Button
                  onClick={handleLogoUpload}
                  disabled={uploadLogoMutation.isPending}
                  size="sm"
                >
                  <Upload className="mr-2 h-4 w-4" />
                  {uploadLogoMutation.isPending ? 'Uploading...' : 'Upload Logo'}
                </Button>
              )}
              {company?.logo && !logoFile && (
                <p className="text-xs text-muted-foreground mt-2">
                  Current logo: {company.logo.split('/').pop()}
                </p>
              )}
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Company Information Form */}
      <form onSubmit={handleSubmit}>
        <Card>
          <CardHeader>
            <CardTitle>Basic Information</CardTitle>
            <CardDescription>Essential details about your company</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="name">Company Name *</Label>
                <Input
                  id="name"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="sector">Industry Sector *</Label>
                <Input
                  id="sector"
                  value={formData.sector}
                  onChange={(e) => setFormData({ ...formData, sector: e.target.value })}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="website">Website</Label>
                <Input
                  id="website"
                  type="url"
                  value={formData.website}
                  onChange={(e) => setFormData({ ...formData, website: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="founded_year">Founded Year</Label>
                <Input
                  id="founded_year"
                  placeholder="2020"
                  value={formData.founded_year}
                  onChange={(e) => setFormData({ ...formData, founded_year: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="headquarters">Headquarters</Label>
                <Input
                  id="headquarters"
                  placeholder="City, Country"
                  value={formData.headquarters}
                  onChange={(e) => setFormData({ ...formData, headquarters: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="ceo_name">CEO/Founder Name</Label>
                <Input
                  id="ceo_name"
                  value={formData.ceo_name}
                  onChange={(e) => setFormData({ ...formData, ceo_name: e.target.value })}
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="description">Company Description</Label>
              <Textarea
                id="description"
                rows={5}
                placeholder="Describe your company, products, services, and mission..."
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              />
            </div>
          </CardContent>
        </Card>

        <Card className="mt-6">
          <CardHeader>
            <CardTitle>Financial Information</CardTitle>
            <CardDescription>Valuation and funding details</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="space-y-2">
                <Label htmlFor="latest_valuation">Latest Valuation (₹)</Label>
                <Input
                  id="latest_valuation"
                  type="number"
                  placeholder="10000000"
                  value={formData.latest_valuation}
                  onChange={(e) => setFormData({ ...formData, latest_valuation: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="funding_stage">Funding Stage</Label>
                <Input
                  id="funding_stage"
                  placeholder="Series A, Series B, Pre-IPO"
                  value={formData.funding_stage}
                  onChange={(e) => setFormData({ ...formData, funding_stage: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="total_funding">Total Funding Raised (₹)</Label>
                <Input
                  id="total_funding"
                  type="number"
                  placeholder="5000000"
                  value={formData.total_funding}
                  onChange={(e) => setFormData({ ...formData, total_funding: e.target.value })}
                />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="mt-6">
          <CardHeader>
            <CardTitle>Social Media Links</CardTitle>
            <CardDescription>Connect your social media profiles</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="space-y-2">
                <Label htmlFor="linkedin_url">LinkedIn URL</Label>
                <Input
                  id="linkedin_url"
                  type="url"
                  placeholder="https://linkedin.com/company/..."
                  value={formData.linkedin_url}
                  onChange={(e) => setFormData({ ...formData, linkedin_url: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="twitter_url">Twitter/X URL</Label>
                <Input
                  id="twitter_url"
                  type="url"
                  placeholder="https://twitter.com/..."
                  value={formData.twitter_url}
                  onChange={(e) => setFormData({ ...formData, twitter_url: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="facebook_url">Facebook URL</Label>
                <Input
                  id="facebook_url"
                  type="url"
                  placeholder="https://facebook.com/..."
                  value={formData.facebook_url}
                  onChange={(e) => setFormData({ ...formData, facebook_url: e.target.value })}
                />
              </div>
            </div>
          </CardContent>
        </Card>

        <div className="flex justify-end mt-6">
          <Button
            type="submit"
            disabled={updateProfileMutation.isPending}
            size="lg"
          >
            <Save className="mr-2 h-4 w-4" />
            {updateProfileMutation.isPending ? 'Saving...' : 'Save Profile'}
          </Button>
        </div>
      </form>
    </div>
  );
}

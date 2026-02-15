'use client';

import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useRouter } from 'next/navigation';
import companyApi from '@/lib/companyApi';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { toast } from 'sonner';
import {
  Plus,
  Edit,
  Ban,
  RotateCcw,
  Users,
  Shield,
  UserCheck,
  UserX,
  MoreHorizontal,
  Search,
  AlertTriangle,
  Mail,
  UserPlus,
  Loader2,
  ShieldAlert,
  Briefcase,
  Calculator,
  Megaphone,
  Eye,
} from 'lucide-react';

// Type definitions
interface CompanyUser {
  id: number;
  contact_person_name: string;
  email: string;
  contact_person_designation: string | null;
  phone: string | null;
  status: 'active' | 'pending' | 'invited' | 'suspended' | 'deactivated';
  is_current_user: boolean;
  is_admin: boolean;
  role_name: string;
  created_at: string;
  roles: { name: string }[];
}

interface Role {
  name: string;
  display_name: string;
  description: string;
  is_admin: boolean;
}

interface Statistics {
  total_users: number;
  active_users: number;
  pending_users: number;
  suspended_users: number;
  admin_users: number;
  quota_limit: number | null;
  quota_used: number;
}

interface FormData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  designation: string;
  phone: string;
  role: string;
}

const initialFormData: FormData = {
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  designation: '',
  phone: '',
  role: '', // No default - must be explicitly selected
};

// Role icons for visual clarity
const roleIcons: Record<string, any> = {
  company_admin: Shield,
  company_legal: Briefcase,
  company_finance: Calculator,
  company_marketing: Megaphone,
  company_viewer: Eye,
};

export default function CompanyUsersPage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const [searchQuery, setSearchQuery] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');
  const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
  const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
  const [editingUser, setEditingUser] = useState<CompanyUser | null>(null);
  const [formData, setFormData] = useState<FormData>(initialFormData);
  const [confirmDialog, setConfirmDialog] = useState<{
    type: 'suspend' | 'reactivate' | 'resend';
    user: CompanyUser;
  } | null>(null);
  const [isAdmin, setIsAdmin] = useState<boolean | null>(null);
  const [currentUser, setCurrentUser] = useState<any>(null);

  // Check admin access on mount
  useEffect(() => {
    if (typeof window !== 'undefined') {
      try {
        const userData = localStorage.getItem('company_user');
        if (userData) {
          const user = JSON.parse(userData);
          setCurrentUser(user);
          const hasAdminRole = user.roles?.some(
            (role: { name: string }) => role.name === 'company_admin'
          );
          setIsAdmin(hasAdminRole);

          // Redirect non-admins
          if (!hasAdminRole) {
            toast.error('Access denied. Admin privileges required.');
            router.push('/company/dashboard');
          }
        } else {
          router.push('/company/login');
        }
      } catch (e) {
        console.error('Error parsing company user data:', e);
        router.push('/company/login');
      }
    }
  }, [router]);

  // Fetch company users
  const { data: usersData, isLoading: usersLoading } = useQuery({
    queryKey: ['company-users', searchQuery, filterStatus],
    queryFn: async () => {
      const params = new URLSearchParams();
      if (searchQuery) params.append('search', searchQuery);
      if (filterStatus !== 'all') params.append('status', filterStatus);
      const response = await companyApi.get(`/users?${params.toString()}`);
      return response.data;
    },
    enabled: isAdmin === true,
  });

  // Fetch statistics
  const { data: statsData } = useQuery({
    queryKey: ['company-users-stats'],
    queryFn: async () => {
      const response = await companyApi.get('/users/statistics');
      return response.data;
    },
    enabled: isAdmin === true,
  });

  // Fetch available roles
  const { data: rolesData } = useQuery({
    queryKey: ['company-user-roles'],
    queryFn: async () => {
      const response = await companyApi.get('/users/roles');
      return response.data;
    },
    enabled: isAdmin === true,
  });

  const users: CompanyUser[] = usersData?.data || [];
  const stats: Statistics | null = statsData?.data || null;
  const roles: Role[] = rolesData?.data || [];

  // Calculate if this is the only admin
  const adminCount = users.filter(u => u.is_admin).length;
  const isOnlyAdmin = (user: CompanyUser) => user.is_admin && adminCount === 1;

  // Check if this is first-login state (only 1 user = the admin themselves)
  const isFirstLoginState = users.length === 1 && users[0]?.is_current_user;

  // Create user mutation
  const createUserMutation = useMutation({
    mutationFn: async (data: FormData) => {
      return companyApi.post('/users', data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['company-users'] });
      queryClient.invalidateQueries({ queryKey: ['company-users-stats'] });
      setIsCreateDialogOpen(false);
      setFormData(initialFormData);
      toast.success('User created successfully. They can now log in with their credentials.');
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Failed to create user';
      const errors = error.response?.data?.errors;
      if (errors) {
        Object.values(errors).flat().forEach((err: any) => toast.error(err));
      } else {
        toast.error(message);
      }
    },
  });

  // Update user mutation
  const updateUserMutation = useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<FormData> }) => {
      return companyApi.put(`/users/${id}`, data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['company-users'] });
      queryClient.invalidateQueries({ queryKey: ['company-users-stats'] });
      setIsEditDialogOpen(false);
      setEditingUser(null);
      toast.success('User updated successfully');
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Failed to update user';
      toast.error(message);
    },
  });

  // Suspend user mutation
  const suspendUserMutation = useMutation({
    mutationFn: async (id: number) => {
      return companyApi.delete(`/users/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['company-users'] });
      queryClient.invalidateQueries({ queryKey: ['company-users-stats'] });
      setConfirmDialog(null);
      toast.success('User has been suspended');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to suspend user');
    },
  });

  // Reactivate user mutation
  const reactivateUserMutation = useMutation({
    mutationFn: async (id: number) => {
      return companyApi.post(`/users/${id}/reactivate`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['company-users'] });
      queryClient.invalidateQueries({ queryKey: ['company-users-stats'] });
      setConfirmDialog(null);
      toast.success('User has been reactivated');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to reactivate user');
    },
  });

  // Resend invitation mutation (for pending users)
  const resendInvitationMutation = useMutation({
    mutationFn: async (id: number) => {
      return companyApi.post(`/users/${id}/resend-invitation`);
    },
    onSuccess: () => {
      setConfirmDialog(null);
      toast.success('Invitation resent successfully');
    },
    onError: (error: any) => {
      // If endpoint doesn't exist, show helpful message
      if (error.response?.status === 404) {
        toast.info('User credentials were set during creation. They can log in directly.');
      } else {
        toast.error(error.response?.data?.message || 'Failed to resend invitation');
      }
      setConfirmDialog(null);
    },
  });

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    // Validate role is selected
    if (!formData.role) {
      toast.error('Please select a role for the new user');
      return;
    }

    createUserMutation.mutate(formData);
  };

  const handleEditSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingUser) return;

    // Prevent removing own admin role
    if (editingUser.is_current_user && editingUser.is_admin && formData.role !== 'company_admin') {
      toast.error('You cannot remove your own admin role');
      return;
    }

    // Prevent removing last admin
    if (isOnlyAdmin(editingUser) && formData.role !== 'company_admin') {
      toast.error('Cannot remove the last admin. Assign another admin first.');
      return;
    }

    const updateData: Partial<FormData> = {
      name: formData.name,
      designation: formData.designation,
      phone: formData.phone,
      role: formData.role,
    };

    // Only include email if changed
    if (formData.email !== editingUser.email) {
      updateData.email = formData.email;
    }

    updateUserMutation.mutate({ id: editingUser.id, data: updateData });
  };

  const handleEdit = (user: CompanyUser) => {
    setEditingUser(user);
    setFormData({
      name: user.contact_person_name,
      email: user.email,
      password: '',
      password_confirmation: '',
      designation: user.contact_person_designation || '',
      phone: user.phone || '',
      role: user.role_name,
    });
    setIsEditDialogOpen(true);
  };

  const getStatusBadge = (status: string) => {
    const variants: Record<string, { class: string; label: string }> = {
      active: {
        class: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100',
        label: 'Active'
      },
      pending: {
        class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
        label: 'Invited'
      },
      invited: {
        class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
        label: 'Invited'
      },
      suspended: {
        class: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100',
        label: 'Suspended'
      },
      deactivated: {
        class: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100',
        label: 'Deactivated'
      },
    };
    return variants[status] || variants.pending;
  };

  const getRoleBadge = (roleName: string, isAdmin: boolean) => {
    if (isAdmin) {
      return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100';
    }
    const roleColors: Record<string, string> = {
      company_legal: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100',
      company_finance: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100',
      company_marketing: 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-100',
      company_viewer: 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-100',
    };
    return roleColors[roleName] || 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100';
  };

  const getRoleDisplayName = (roleName: string) => {
    const role = roles.find((r) => r.name === roleName);
    if (role?.display_name) return role.display_name;

    // Fallback display names
    const displayNames: Record<string, string> = {
      company_admin: 'Admin',
      company_legal: 'Legal',
      company_finance: 'Finance',
      company_marketing: 'Marketing',
      company_viewer: 'Viewer',
    };
    return displayNames[roleName] || roleName.replace('company_', '').replace('_', ' ');
  };

  // Loading state while checking admin access
  if (isAdmin === null) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  // Non-admin users are redirected, but show nothing in case redirect is slow
  if (!isAdmin) {
    return null;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">User Management</h1>
          <p className="text-muted-foreground mt-2">
            Manage your company's team members and their access levels
          </p>
        </div>
        <Button onClick={() => setIsCreateDialogOpen(true)}>
          <UserPlus className="mr-2 h-4 w-4" /> Add Team Member
        </Button>
      </div>

      {/* First-Login Team Setup Banner */}
      {isFirstLoginState && (
        <Alert className="bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800">
          <Users className="h-5 w-5 text-blue-600" />
          <AlertTitle className="text-blue-900 dark:text-blue-100">
            Set up your company team
          </AlertTitle>
          <AlertDescription className="text-blue-800 dark:text-blue-200">
            <p className="mb-3">
              You're the first admin for your company. Add team members to delegate onboarding tasks
              and collaborate on your company profile.
            </p>
            <div className="flex flex-wrap gap-2 mb-4">
              <Badge variant="outline" className="bg-white dark:bg-gray-800">
                <Briefcase className="h-3 w-3 mr-1" /> Legal - Upload compliance docs
              </Badge>
              <Badge variant="outline" className="bg-white dark:bg-gray-800">
                <Calculator className="h-3 w-3 mr-1" /> Finance - Manage financials
              </Badge>
              <Badge variant="outline" className="bg-white dark:bg-gray-800">
                <Megaphone className="h-3 w-3 mr-1" /> Marketing - Brand & content
              </Badge>
              <Badge variant="outline" className="bg-white dark:bg-gray-800">
                <Eye className="h-3 w-3 mr-1" /> Viewer - Read-only access
              </Badge>
            </div>
            <Button onClick={() => setIsCreateDialogOpen(true)} size="sm">
              <UserPlus className="mr-2 h-4 w-4" /> Add Your First Team Member
            </Button>
          </AlertDescription>
        </Alert>
      )}

      {/* Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Total Users</p>
                <p className="text-2xl font-bold mt-1">{stats?.total_users || 0}</p>
              </div>
              <Users className="h-8 w-8 text-blue-600" />
            </div>
            {stats?.quota_limit && (
              <p className="text-xs text-muted-foreground mt-2">
                {stats.quota_used} / {stats.quota_limit} quota used
              </p>
            )}
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Active Users</p>
                <p className="text-2xl font-bold mt-1">{stats?.active_users || 0}</p>
              </div>
              <UserCheck className="h-8 w-8 text-green-600" />
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Suspended</p>
                <p className="text-2xl font-bold mt-1">{stats?.suspended_users || 0}</p>
              </div>
              <UserX className="h-8 w-8 text-red-600" />
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Admins</p>
                <p className="text-2xl font-bold mt-1">{stats?.admin_users || 0}</p>
              </div>
              <Shield className="h-8 w-8 text-purple-600" />
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Pending</p>
                <p className="text-2xl font-bold mt-1">{stats?.pending_users || 0}</p>
              </div>
              <Mail className="h-8 w-8 text-yellow-600" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Search and Filter */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-col md:flex-row gap-4">
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search by name, email, or designation..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10"
              />
            </div>
            <Select value={filterStatus} onValueChange={setFilterStatus}>
              <SelectTrigger className="w-full md:w-[180px]">
                <SelectValue placeholder="Filter by status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Status</SelectItem>
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="suspended">Suspended</SelectItem>
                <SelectItem value="pending">Invited</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Users Table */}
      <Card>
        <CardHeader>
          <CardTitle>Team Members</CardTitle>
          <CardDescription>{users.length} users found</CardDescription>
        </CardHeader>
        <CardContent>
          {usersLoading ? (
            <div className="flex items-center justify-center py-8">
              <Loader2 className="h-6 w-6 animate-spin mr-2" />
              Loading users...
            </div>
          ) : users.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground">
              <Users className="h-16 w-16 mx-auto mb-4 text-muted-foreground/50" />
              <p className="text-lg font-medium mb-2">No users found</p>
              <p className="text-sm mb-4">Add team members to manage your company together</p>
              <Button onClick={() => setIsCreateDialogOpen(true)}>
                <UserPlus className="mr-2 h-4 w-4" /> Add First User
              </Button>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Role</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Joined</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {users.map((user) => {
                  const RoleIcon = roleIcons[user.role_name] || Users;
                  return (
                    <TableRow key={user.id}>
                      <TableCell>
                        <div className="flex items-center gap-3">
                          <div className="h-9 w-9 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                            <RoleIcon className="h-4 w-4 text-gray-600 dark:text-gray-400" />
                          </div>
                          <div>
                            <p className="font-medium">
                              {user.contact_person_name}
                              {user.is_current_user && (
                                <span className="ml-2 text-xs text-muted-foreground">(You)</span>
                              )}
                            </p>
                            {user.contact_person_designation && (
                              <p className="text-sm text-muted-foreground">
                                {user.contact_person_designation}
                              </p>
                            )}
                          </div>
                        </div>
                      </TableCell>
                      <TableCell className="text-muted-foreground">{user.email}</TableCell>
                      <TableCell>
                        <Badge className={getRoleBadge(user.role_name, user.is_admin)}>
                          {getRoleDisplayName(user.role_name)}
                        </Badge>
                        {isOnlyAdmin(user) && (
                          <Badge variant="outline" className="ml-1 text-xs">
                            Only Admin
                          </Badge>
                        )}
                      </TableCell>
                      <TableCell>
                        <Badge className={getStatusBadge(user.status).class}>
                          {getStatusBadge(user.status).label}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-muted-foreground">
                        {new Date(user.created_at).toLocaleDateString('en-US', {
                          month: 'short',
                          day: 'numeric',
                          year: 'numeric',
                        })}
                      </TableCell>
                      <TableCell className="text-right">
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="sm">
                              <MoreHorizontal className="h-4 w-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem onClick={() => handleEdit(user)}>
                              <Edit className="mr-2 h-4 w-4" />
                              Edit User
                            </DropdownMenuItem>

                            {/* Resend invitation for pending users */}
                            {(user.status === 'pending' || user.status === 'invited') && (
                              <DropdownMenuItem
                                onClick={() => setConfirmDialog({ type: 'resend', user })}
                              >
                                <Mail className="mr-2 h-4 w-4" />
                                Resend Invitation
                              </DropdownMenuItem>
                            )}

                            <DropdownMenuSeparator />

                            {/* Suspend - not for self, not for only admin */}
                            {user.status === 'active' && !user.is_current_user && !isOnlyAdmin(user) && (
                              <DropdownMenuItem
                                className="text-red-600"
                                onClick={() => setConfirmDialog({ type: 'suspend', user })}
                              >
                                <Ban className="mr-2 h-4 w-4" />
                                Suspend User
                              </DropdownMenuItem>
                            )}

                            {/* Reactivate suspended users */}
                            {user.status === 'suspended' && (
                              <DropdownMenuItem
                                className="text-green-600"
                                onClick={() => setConfirmDialog({ type: 'reactivate', user })}
                              >
                                <RotateCcw className="mr-2 h-4 w-4" />
                                Reactivate User
                              </DropdownMenuItem>
                            )}

                            {/* Show why actions are disabled */}
                            {user.is_current_user && user.status === 'active' && (
                              <DropdownMenuItem disabled className="text-muted-foreground">
                                <ShieldAlert className="mr-2 h-4 w-4" />
                                Cannot suspend yourself
                              </DropdownMenuItem>
                            )}
                            {isOnlyAdmin(user) && !user.is_current_user && (
                              <DropdownMenuItem disabled className="text-muted-foreground">
                                <ShieldAlert className="mr-2 h-4 w-4" />
                                Last admin protected
                              </DropdownMenuItem>
                            )}
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* Create User Dialog */}
      <Dialog open={isCreateDialogOpen} onOpenChange={setIsCreateDialogOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>Add New Team Member</DialogTitle>
            <DialogDescription>
              Create an account for a new team member. They will be able to log in immediately with
              the credentials you set.
            </DialogDescription>
          </DialogHeader>
          <form onSubmit={handleCreateSubmit} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="create-name">Full Name *</Label>
              <Input
                id="create-name"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="create-email">Email Address *</Label>
              <Input
                id="create-email"
                type="email"
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                required
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="create-password">Password *</Label>
                <Input
                  id="create-password"
                  type="password"
                  value={formData.password}
                  onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                  required
                  minLength={8}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="create-password-confirm">Confirm Password *</Label>
                <Input
                  id="create-password-confirm"
                  type="password"
                  value={formData.password_confirmation}
                  onChange={(e) =>
                    setFormData({ ...formData, password_confirmation: e.target.value })
                  }
                  required
                />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="create-designation">Designation</Label>
                <Input
                  id="create-designation"
                  placeholder="e.g., Finance Manager"
                  value={formData.designation}
                  onChange={(e) => setFormData({ ...formData, designation: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="create-phone">Phone</Label>
                <Input
                  id="create-phone"
                  type="tel"
                  value={formData.phone}
                  onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="create-role">Role * (Required)</Label>
              <Select
                value={formData.role}
                onValueChange={(value) => setFormData({ ...formData, role: value })}
              >
                <SelectTrigger className={!formData.role ? 'border-orange-300' : ''}>
                  <SelectValue placeholder="Select a role - required" />
                </SelectTrigger>
                <SelectContent>
                  {roles.map((role) => {
                    const RoleIcon = roleIcons[role.name] || Users;
                    return (
                      <SelectItem key={role.name} value={role.name}>
                        <div className="flex items-center gap-2">
                          <RoleIcon className="h-4 w-4" />
                          <span>
                            {role.display_name}
                            {role.is_admin && (
                              <Badge className="ml-2 text-xs" variant="outline">
                                Full Access
                              </Badge>
                            )}
                          </span>
                        </div>
                      </SelectItem>
                    );
                  })}
                </SelectContent>
              </Select>
              {!formData.role && (
                <p className="text-xs text-orange-600">Please select a role for this user</p>
              )}
              {formData.role && (
                <p className="text-xs text-muted-foreground">
                  {roles.find((r) => r.name === formData.role)?.description}
                </p>
              )}
            </div>
            {formData.role === 'company_admin' && (
              <Alert variant="default" className="bg-yellow-50 dark:bg-yellow-950 border-yellow-200">
                <AlertTriangle className="h-4 w-4 text-yellow-600" />
                <AlertDescription className="text-yellow-800 dark:text-yellow-200">
                  Admin users have full access to all company features including user management,
                  financial data, and the ability to manage other admins.
                </AlertDescription>
              </Alert>
            )}
            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                onClick={() => {
                  setIsCreateDialogOpen(false);
                  setFormData(initialFormData);
                }}
              >
                Cancel
              </Button>
              <Button type="submit" disabled={createUserMutation.isPending || !formData.role}>
                {createUserMutation.isPending ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Creating...
                  </>
                ) : (
                  'Create User'
                )}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Edit User Dialog */}
      <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>Edit User</DialogTitle>
            <DialogDescription>
              Update user details and permissions. Role changes take effect immediately.
            </DialogDescription>
          </DialogHeader>
          <form onSubmit={handleEditSubmit} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="edit-name">Full Name *</Label>
              <Input
                id="edit-name"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="edit-email">Email Address *</Label>
              <Input
                id="edit-email"
                type="email"
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                required
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="edit-designation">Designation</Label>
                <Input
                  id="edit-designation"
                  placeholder="e.g., Finance Manager"
                  value={formData.designation}
                  onChange={(e) => setFormData({ ...formData, designation: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="edit-phone">Phone</Label>
                <Input
                  id="edit-phone"
                  type="tel"
                  value={formData.phone}
                  onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="edit-role">Role *</Label>
              <Select
                value={formData.role}
                onValueChange={(value) => setFormData({ ...formData, role: value })}
                disabled={editingUser?.is_current_user && editingUser?.is_admin}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select a role" />
                </SelectTrigger>
                <SelectContent>
                  {roles.map((role) => {
                    const RoleIcon = roleIcons[role.name] || Users;
                    return (
                      <SelectItem key={role.name} value={role.name}>
                        <div className="flex items-center gap-2">
                          <RoleIcon className="h-4 w-4" />
                          <span>
                            {role.display_name}
                            {role.is_admin && (
                              <Badge className="ml-2 text-xs" variant="outline">
                                Full Access
                              </Badge>
                            )}
                          </span>
                        </div>
                      </SelectItem>
                    );
                  })}
                </SelectContent>
              </Select>
              {formData.role && (
                <p className="text-xs text-muted-foreground">
                  {roles.find((r) => r.name === formData.role)?.description}
                </p>
              )}
            </div>

            {/* Warning: Can't remove own admin role */}
            {editingUser?.is_current_user && editingUser?.is_admin && (
              <Alert variant="default" className="bg-blue-50 dark:bg-blue-950 border-blue-200">
                <Shield className="h-4 w-4 text-blue-600" />
                <AlertDescription className="text-blue-800 dark:text-blue-200">
                  You cannot change your own admin role. Ask another admin to modify your role if
                  needed.
                </AlertDescription>
              </Alert>
            )}

            {/* Warning: Last admin protection */}
            {editingUser && isOnlyAdmin(editingUser) && formData.role !== 'company_admin' && (
              <Alert variant="destructive">
                <ShieldAlert className="h-4 w-4" />
                <AlertDescription>
                  This is the only admin. You must assign another admin before changing this role.
                </AlertDescription>
              </Alert>
            )}

            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                onClick={() => {
                  setIsEditDialogOpen(false);
                  setEditingUser(null);
                }}
              >
                Cancel
              </Button>
              <Button
                type="submit"
                disabled={
                  updateUserMutation.isPending ||
                  !!(editingUser && isOnlyAdmin(editingUser) && formData.role !== 'company_admin')
                }
              >
                {updateUserMutation.isPending ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Saving...
                  </>
                ) : (
                  'Save Changes'
                )}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Confirm Suspend/Reactivate/Resend Dialog */}
      <Dialog open={!!confirmDialog} onOpenChange={(open) => !open && setConfirmDialog(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {confirmDialog?.type === 'suspend' && 'Suspend User'}
              {confirmDialog?.type === 'reactivate' && 'Reactivate User'}
              {confirmDialog?.type === 'resend' && 'Resend Invitation'}
            </DialogTitle>
            <DialogDescription>
              {confirmDialog?.type === 'suspend' &&
                'Suspending this user will revoke their access to the company portal. They will not be able to log in until reactivated.'}
              {confirmDialog?.type === 'reactivate' &&
                'Reactivating this user will restore their access to the company portal with their previous role.'}
              {confirmDialog?.type === 'resend' &&
                'This will send the user their login credentials again. Use this if they haven\'t received or have lost their initial credentials.'}
            </DialogDescription>
          </DialogHeader>
          <div className="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
            <p className="font-medium">{confirmDialog?.user.contact_person_name}</p>
            <p className="text-sm text-muted-foreground">{confirmDialog?.user.email}</p>
            <div className="flex gap-2 mt-2">
              <Badge className={getRoleBadge(confirmDialog?.user.role_name || '', confirmDialog?.user.is_admin || false)}>
                {getRoleDisplayName(confirmDialog?.user.role_name || '')}
              </Badge>
              <Badge className={getStatusBadge(confirmDialog?.user.status || 'pending').class}>
                {getStatusBadge(confirmDialog?.user.status || 'pending').label}
              </Badge>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setConfirmDialog(null)}>
              Cancel
            </Button>
            <Button
              variant={confirmDialog?.type === 'suspend' ? 'destructive' : 'default'}
              onClick={() => {
                if (confirmDialog?.type === 'suspend') {
                  suspendUserMutation.mutate(confirmDialog.user.id);
                } else if (confirmDialog?.type === 'reactivate') {
                  reactivateUserMutation.mutate(confirmDialog.user.id);
                } else if (confirmDialog?.type === 'resend') {
                  resendInvitationMutation.mutate(confirmDialog.user.id);
                }
              }}
              disabled={
                suspendUserMutation.isPending ||
                reactivateUserMutation.isPending ||
                resendInvitationMutation.isPending
              }
            >
              {(suspendUserMutation.isPending ||
                reactivateUserMutation.isPending ||
                resendInvitationMutation.isPending) && (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              )}
              {confirmDialog?.type === 'suspend' && 'Suspend User'}
              {confirmDialog?.type === 'reactivate' && 'Reactivate User'}
              {confirmDialog?.type === 'resend' && 'Resend Invitation'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Role Guide Card */}
      <Card className="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950 dark:to-indigo-950 border-blue-200 dark:border-blue-800">
        <CardContent className="pt-6">
          <div className="flex items-start gap-3">
            <Shield className="h-5 w-5 text-blue-600 mt-0.5" />
            <div className="flex-1">
              <h3 className="font-semibold text-blue-900 dark:text-blue-100 mb-3">
                Understanding Roles & Permissions
              </h3>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {roles.map((role) => {
                  const RoleIcon = roleIcons[role.name] || Users;
                  return (
                    <div
                      key={role.name}
                      className="bg-white dark:bg-gray-900 p-3 rounded-lg border border-blue-100 dark:border-blue-800"
                    >
                      <div className="flex items-center gap-2 mb-1">
                        <RoleIcon className="h-4 w-4 text-blue-600" />
                        <span className="font-medium text-blue-900 dark:text-blue-100">
                          {role.display_name}
                        </span>
                        {role.is_admin && (
                          <Badge variant="secondary" className="text-xs">
                            Full Access
                          </Badge>
                        )}
                      </div>
                      <p className="text-xs text-blue-700 dark:text-blue-300">{role.description}</p>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

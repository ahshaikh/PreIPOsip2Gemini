'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
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
} from 'lucide-react';

// Type definitions
interface CompanyUser {
  id: number;
  contact_person_name: string;
  email: string;
  contact_person_designation: string | null;
  phone: string | null;
  status: 'active' | 'pending' | 'suspended';
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
  role: 'company_viewer', // Default to least-privilege role
};

export default function CompanyUsersPage() {
  const queryClient = useQueryClient();
  const [searchQuery, setSearchQuery] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');
  const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
  const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
  const [editingUser, setEditingUser] = useState<CompanyUser | null>(null);
  const [formData, setFormData] = useState<FormData>(initialFormData);
  const [confirmDialog, setConfirmDialog] = useState<{
    type: 'suspend' | 'reactivate';
    user: CompanyUser;
  } | null>(null);

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
  });

  // Fetch statistics
  const { data: statsData } = useQuery({
    queryKey: ['company-users-stats'],
    queryFn: async () => {
      const response = await companyApi.get('/users/statistics');
      return response.data;
    },
  });

  // Fetch available roles
  const { data: rolesData } = useQuery({
    queryKey: ['company-user-roles'],
    queryFn: async () => {
      const response = await companyApi.get('/users/roles');
      return response.data;
    },
  });

  const users: CompanyUser[] = usersData?.data || [];
  const stats: Statistics | null = statsData?.data || null;
  const roles: Role[] = rolesData?.data || [];

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
      toast.success('User created successfully');
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

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    createUserMutation.mutate(formData);
  };

  const handleEditSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingUser) return;

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
    const variants: Record<string, { class: string }> = {
      active: { class: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' },
      pending: { class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100' },
      suspended: { class: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100' },
    };
    return variants[status] || variants.pending;
  };

  const getRoleBadge = (roleName: string, isAdmin: boolean) => {
    if (isAdmin) {
      return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100';
    }
    return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100';
  };

  const getRoleDisplayName = (roleName: string) => {
    const role = roles.find((r) => r.name === roleName);
    return role?.display_name || roleName.replace('company_', '').replace('_', ' ');
  };

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
          <Plus className="mr-2 h-4 w-4" /> Add User
        </Button>
      </div>

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
              <AlertTriangle className="h-8 w-8 text-yellow-600" />
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
                <SelectItem value="pending">Pending</SelectItem>
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
            <div className="text-center py-8">Loading users...</div>
          ) : users.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground">
              <Users className="h-16 w-16 mx-auto mb-4 text-muted-foreground/50" />
              <p className="text-lg font-medium mb-2">No users found</p>
              <p className="text-sm mb-4">Add team members to manage your company together</p>
              <Button onClick={() => setIsCreateDialogOpen(true)}>
                <Plus className="mr-2 h-4 w-4" /> Add First User
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
                {users.map((user) => (
                  <TableRow key={user.id}>
                    <TableCell>
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
                    </TableCell>
                    <TableCell>{user.email}</TableCell>
                    <TableCell>
                      <Badge className={getRoleBadge(user.role_name, user.is_admin)}>
                        {getRoleDisplayName(user.role_name)}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <Badge className={getStatusBadge(user.status).class}>
                        {user.status.charAt(0).toUpperCase() + user.status.slice(1)}
                      </Badge>
                    </TableCell>
                    <TableCell>
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
                          <DropdownMenuSeparator />
                          {user.status === 'active' && !user.is_current_user && (
                            <DropdownMenuItem
                              className="text-red-600"
                              onClick={() => setConfirmDialog({ type: 'suspend', user })}
                            >
                              <Ban className="mr-2 h-4 w-4" />
                              Suspend User
                            </DropdownMenuItem>
                          )}
                          {user.status === 'suspended' && (
                            <DropdownMenuItem
                              className="text-green-600"
                              onClick={() => setConfirmDialog({ type: 'reactivate', user })}
                            >
                              <RotateCcw className="mr-2 h-4 w-4" />
                              Reactivate User
                            </DropdownMenuItem>
                          )}
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* Create User Dialog */}
      <Dialog open={isCreateDialogOpen} onOpenChange={setIsCreateDialogOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>Add New User</DialogTitle>
            <DialogDescription>
              Create a new user account for your team. They will be able to log in immediately.
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
              <Label htmlFor="create-role">Role *</Label>
              <Select
                value={formData.role}
                onValueChange={(value) => setFormData({ ...formData, role: value })}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select a role" />
                </SelectTrigger>
                <SelectContent>
                  {roles.map((role) => (
                    <SelectItem key={role.name} value={role.name}>
                      <div className="flex flex-col">
                        <span>
                          {role.display_name}
                          {role.is_admin && (
                            <Badge className="ml-2 text-xs" variant="outline">
                              Admin
                            </Badge>
                          )}
                        </span>
                      </div>
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {formData.role && (
                <p className="text-xs text-muted-foreground">
                  {roles.find((r) => r.name === formData.role)?.description}
                </p>
              )}
            </div>
            {formData.role === 'company_admin' && (
              <div className="p-3 bg-yellow-50 dark:bg-yellow-950 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <p className="text-sm text-yellow-800 dark:text-yellow-200 flex items-center gap-2">
                  <AlertTriangle className="h-4 w-4" />
                  Admin users have full access to all company features including user management.
                </p>
              </div>
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
              <Button type="submit" disabled={createUserMutation.isPending}>
                {createUserMutation.isPending ? 'Creating...' : 'Create User'}
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
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select a role" />
                </SelectTrigger>
                <SelectContent>
                  {roles.map((role) => (
                    <SelectItem key={role.name} value={role.name}>
                      <div className="flex flex-col">
                        <span>
                          {role.display_name}
                          {role.is_admin && (
                            <Badge className="ml-2 text-xs" variant="outline">
                              Admin
                            </Badge>
                          )}
                        </span>
                      </div>
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {formData.role && (
                <p className="text-xs text-muted-foreground">
                  {roles.find((r) => r.name === formData.role)?.description}
                </p>
              )}
            </div>
            {editingUser?.is_current_user &&
              editingUser.is_admin &&
              formData.role !== 'company_admin' && (
                <div className="p-3 bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 rounded-lg">
                  <p className="text-sm text-red-800 dark:text-red-200 flex items-center gap-2">
                    <AlertTriangle className="h-4 w-4" />
                    You cannot remove your own admin role. Ask another admin to change your role.
                  </p>
                </div>
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
                  (editingUser?.is_current_user &&
                    editingUser.is_admin &&
                    formData.role !== 'company_admin')
                }
              >
                {updateUserMutation.isPending ? 'Saving...' : 'Save Changes'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Confirm Suspend/Reactivate Dialog */}
      <Dialog open={!!confirmDialog} onOpenChange={(open) => !open && setConfirmDialog(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {confirmDialog?.type === 'suspend' ? 'Suspend User' : 'Reactivate User'}
            </DialogTitle>
            <DialogDescription>
              {confirmDialog?.type === 'suspend'
                ? 'Suspending this user will revoke their access to the company portal. They will not be able to log in until reactivated.'
                : 'Reactivating this user will restore their access to the company portal with their previous role.'}
            </DialogDescription>
          </DialogHeader>
          <div className="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
            <p className="font-medium">{confirmDialog?.user.contact_person_name}</p>
            <p className="text-sm text-muted-foreground">{confirmDialog?.user.email}</p>
            <Badge className={getRoleBadge(confirmDialog?.user.role_name || '', false)}>
              {getRoleDisplayName(confirmDialog?.user.role_name || '')}
            </Badge>
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
                }
              }}
              disabled={suspendUserMutation.isPending || reactivateUserMutation.isPending}
            >
              {suspendUserMutation.isPending || reactivateUserMutation.isPending
                ? 'Processing...'
                : confirmDialog?.type === 'suspend'
                  ? 'Suspend User'
                  : 'Reactivate User'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Role Guide Card */}
      <Card className="bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800">
        <CardContent className="pt-6">
          <div className="flex items-start gap-3">
            <Shield className="h-5 w-5 text-blue-600 mt-0.5" />
            <div>
              <h3 className="font-semibold text-blue-900 dark:text-blue-100 mb-2">
                Understanding Roles
              </h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800 dark:text-blue-200">
                {roles.map((role) => (
                  <div key={role.name} className="flex flex-col">
                    <span className="font-medium">
                      {role.display_name}
                      {role.is_admin && ' (Full Access)'}
                    </span>
                    <span className="text-xs opacity-80">{role.description}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

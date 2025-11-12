// V-POLISH-1730-178
'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useRouter, useSearchParams } from "next/navigation";
import { PaginationControls } from "@/components/shared/PaginationControls";
import { SearchInput } from "@/components/shared/SearchInput";

export default function UsersPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  
  const page = searchParams.get('page') || '1';
  const search = searchParams.get('search') || '';

  const { data, isLoading } = useQuery({
    queryKey: ['adminUsers', page, search],
    queryFn: async () => (await api.get(`/admin/users?page=${page}&search=${search}`)).data,
  });

  if (isLoading) return <div>Loading users...</div>;

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">User Management</h1>
        <SearchInput placeholder="Search by name, email or mobile..." />
      </div>

      <Card>
        <CardHeader>
          <CardTitle>All Users</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>User ID</TableHead>
                <TableHead>Username</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>KYC Status</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Joined</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data?.data.map((user: any) => (
                <TableRow key={user.id}>
                  <TableCell>{user.id}</TableCell>
                  <TableCell>{user.username}</TableCell>
                  <TableCell>{user.email}</TableCell>
                  <TableCell>
                    <span className={`px-2 py-1 rounded-full text-xs font-semibold ${
                      user.kyc?.status === 'verified' ? 'bg-green-100 text-green-800' :
                      user.kyc?.status === 'submitted' ? 'bg-yellow-100 text-yellow-800' :
                      'bg-gray-100 text-gray-800'
                    }`}>
                      {user.kyc?.status || 'pending'}
                    </span>
                  </TableCell>
                  <TableCell>
                    <span className={`px-2 py-1 rounded-full text-xs font-semibold ${
                      user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    }`}>
                      {user.status}
                    </span>
                  </TableCell>
                  <TableCell>{new Date(user.created_at).toLocaleDateString()}</TableCell>
                  <TableCell>
                    <Button variant="outline" size="sm" onClick={() => router.push(`/admin/users/${user.id}`)}>
                      Manage
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          
          {/* The Pagination Component */}
          {data && <PaginationControls meta={data} links={data} />}
          
        </CardContent>
      </Card>
    </div>
  );
}
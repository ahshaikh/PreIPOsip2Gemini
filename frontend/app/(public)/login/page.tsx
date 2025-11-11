// V-PHASE4-1730-111
'use client';

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useToast } from "@/components/ui/use-toast";
import api from "@/lib/api";
import { useRouter } from "next/navigation";
import { useState } from "react";

export default function LoginPage() {
  const router = useRouter();
  const { toast } = useToast();
  const [login, setLogin] = useState('');
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);

    try {
      const response = await api.post('/login', { login, password });
      localStorage.setItem('auth_token', response.data.token);
      
      // We need to set the token on the axios instance for future requests
      api.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;

      toast({
        title: "Login Successful",
        description: "Welcome back!",
      });
      router.push('/dashboard');
    } catch (error: any) {
      toast({
        title: "Login Failed",
        description: error.response?.data?.message || "An error occurred.",
        variant: "destructive",
      });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="container max-w-sm py-20">
      <h1 className="text-3xl font-bold text-center mb-8">Login to your Account</h1>
      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="space-y-2">
          <Label htmlFor="login">Email, Username, or Mobile</Label>
          <Input 
            id="login" 
            type="text" 
            placeholder="Your login" 
            required 
            value={login}
            onChange={(e) => setLogin(e.target.value)}
          />
        </div>
        <div className="space-y-2">
          <Label htmlFor="password">Password</Label>
          <Input 
            id="password" 
            type="password" 
            required 
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />
        </div>
        <Button type="submit" className="w-full" disabled={isLoading}>
          {isLoading ? "Logging in..." : "Login"}
        </Button>
      </form>
    </div>
  );
}
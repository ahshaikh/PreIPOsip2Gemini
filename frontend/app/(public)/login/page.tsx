// V-PHASE4-1730-111 (FINAL REVISION)
'use client';

// --- IMPORTS ---
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner"; // We import `toast` directly from `sonner` (the new toast library)
import api from "@/lib/api"; // Our central API client from `/frontend/lib/api.ts`
import { useRouter } from "next/navigation";
import { useState } from "react";

// --- COMPONENT DEFINITION ---
export default function LoginPage() {
  // --- HOOKS ---
  const router = useRouter(); // Next.js hook for changing pages

  // --- STATE ---
  // We hold the form inputs in React's state
  const [login, setLogin] = useState(''); // For email/username/mobile
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false); // To disable the button while loading

  // --- HANDLER FUNCTION ---
  /**
   * This function is called when the user submits the form.
   */
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault(); // Prevents the browser from doing a full page reload
    setIsLoading(true); // Disable the button

    try {
      // 1. Send the login data to our Laravel backend
      const response = await api.post('/login', { login, password });

      // 2. If login is successful, we get a token back. We MUST store it.
      const token = response.data.token;
      
      // We store the token in two places:
      // a) localStorage: So the user stays logged in if they refresh the page.
      localStorage.setItem('auth_token', token);
      
      // b) API Client Defaults: So all future API calls in *this session* are authenticated.
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

      // 3. THIS IS THE "SMART REDIRECT" FIX
      // We check the user object that the API sent back.
      const user = response.data.user;

      // We look at the 'roles' array to see if they are an admin.
      const isAdmin = user.roles && user.roles.some(
        (role: any) => role.name === 'admin' || role.name === 'super-admin'
      );

      if (isAdmin) {
        // If they are an admin, send them to the admin panel
        toast.success("Admin Login Successful", {
          description: "Redirecting to Command Center...",
        });
        router.push('/admin/dashboard');
      } else {
        // If they are a normal user, send them to the user dashboard
        toast.success("Login Successful", {
          description: "Welcome back!",
        });
        router.push('/dashboard');
      }
      // --- END OF FIX ---

    } catch (error: any) {
      // 4. If the API returns an error (401, 404, 500, etc.), show it.
      toast.error("Login Failed", {
        description: error.response?.data?.message || "An error occurred.",
      });
    } finally {
      // 5. No matter what, re-enable the button after the attempt
      setIsLoading(false);
    }
  };

  // --- JSX RENDER ---
  return (
    <div className="container max-w-sm py-20">
      <h1 className="text-3xl font-bold text-center mb-8">Login to your Account</h1>
      
      {/* The form calls our handleSubmit function */}
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
        
        {/* The button is disabled while the API call is in progress */}
        <Button type="submit" className="w-full" disabled={isLoading}>
          {isLoading ? "Logging in..." : "Login"}
        </Button>
      </form>
    </div>
  );
}
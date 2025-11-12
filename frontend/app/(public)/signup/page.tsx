// V-PHASE4-1730-112 (REVISED)
'use client';

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner"; // <-- IMPORT FROM SONNER
import api from "@/lib/api";
import { useRouter } from "next/navigation";
import { useState } from "react";

export default function SignupPage() {
  const router = useRouter();
  // const { toast } = useToast(); // <-- REMOVED
  const [formData, setFormData] = useState({
    username: '',
    email: '',
    mobile: '',
    password: '',
    password_confirmation: '',
    referral_code: '',
  });
  const [isLoading, setIsLoading] = useState(false);
  const [errors, setErrors] = useState<any>({});

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData({ ...formData, [e.target.id]: e.target.value });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setErrors({});

    try {
      const response = await api.post('/register', formData);
      toast.success("Registration Successful", { // <-- REVISED
        description: "Please check your email/SMS to verify your account.",
      });
      // Redirect to a verification page
      router.push(`/verify?user_id=${response.data.user_id}`);
    } catch (error: any) {
      if (error.response?.status === 422) {
        setErrors(error.response.data.errors);
      } else {
        toast.error("Registration Failed", { // <-- REVISED
          description: error.response?.data?.message || "An error occurred.",
        });
      }
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="container max-w-sm py-20">
      <h1 className="text-3xl font-bold text-center mb-8">Create your Free Account</h1>
      <form onSubmit={handleSubmit} className="space-y-4">
        {/* Username */}
        <div className="space-y-2">
          <Label htmlFor="username">Username</Label>
          <Input id="username" value={formData.username} onChange={handleChange} required />
          {errors.username && <p className="text-sm text-destructive">{errors.username[0]}</p>}
        </div>
        {/* Email */}
        <div className="space-y-2">
          <Label htmlFor="email">Email</Label>
          <Input id="email" type="email" value={formData.email} onChange={handleChange} required />
          {errors.email && <p className="text-sm text-destructive">{errors.email[0]}</p>}
        </div>
        {/* Mobile */}
        <div className="space-y-2">
          <Label htmlFor="mobile">Mobile Number</Label>
          <Input id="mobile" value={formData.mobile} onChange={handleChange} required />
          {/* THE ORIGINAL <Fp> TYPO IS HERE, NOW FIXED TO </p> */}
          {errors.mobile && <p className="text-sm text-destructive">{errors.mobile[0]}</p>}
        </div>
        {/* Password */}
        <div className="space-y-2">
          <Label htmlFor="password">Password</Label>
          <Input id="password" type="password" value={formData.password} onChange={handleChange} required />
          {errors.password && <p className="text-sm text-destructive">{errors.password[0]}</p>}
        </div>
        {/* Confirm Password */}
        <div className="space-y-2">
          <Label htmlFor="password_confirmation">Confirm Password</Label>
          <Input id="password_confirmation" type="password" value={formData.password_confirmation} onChange={handleChange} required />
        </div>
        {/* Referral Code */}
        <div className="space-y-2">
          <Label htmlFor="referral_code">Referral Code (Optional)</Label>
          <Input id="referral_code" value={formData.referral_code} onChange={handleChange} />
          {errors.referral_code && <p className="text-sm text-destructive">{errors.referral_code[0]}</p>}
        </div>
        
        <Button type="submit" className="w-full" disabled={isLoading}>
          {isLoading ? "Creating Account..." : "Sign Up"}
        </Button>
      </form>
    </div>
  );
}
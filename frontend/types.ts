// V-PHASE4-1730-100
// A simplified type definition for our Plan data
export interface PlanFeature {
  id: number;
  feature_text: string;
}

export interface PlanConfig {
  id: number;
  config_key: string;
  value: any; // e.g., { "rate": 0.5, "start_month": 4 }
}

export interface Plan {
  id: number;
  name: string;
  slug: string;
  monthly_amount: number;
  description: string;
  is_featured: boolean;
  features: PlanFeature[];
  configs: PlanConfig[];
}

export interface User {
  id: number;
  username: string;
  email: string;
  profile: UserProfile;
  kyc: UserKyc;
}

export interface UserProfile {
  first_name: string | null;
  last_name: string | null;
  // ... other profile fields
}

export interface UserKyc {
  status: 'pending' | 'submitted' | 'verified' | 'rejected';
  rejection_reason: string | null;
  // ... other kyc fields
}
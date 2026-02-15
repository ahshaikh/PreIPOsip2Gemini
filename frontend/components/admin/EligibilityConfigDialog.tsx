// V-PHASE2-ELIGIBILITY-1208 (Created)
'use client';

import { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ShieldCheck, Globe, IndianRupee, Users, AlertTriangle, Plus, X } from 'lucide-react';

interface EligibilityConfig {
  min_age?: number;
  max_age?: number;
  kyc_required?: boolean;
  countries_allowed?: string[];
  countries_blocked?: string[];
  min_monthly_income?: number;
  employment_required?: boolean;
  require_pan?: boolean;
  require_bank_account?: boolean;
}

interface EligibilityConfigDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  planName: string;
  eligibilityConfig: EligibilityConfig;
  onSave: (config: EligibilityConfig) => void;
  isSaving: boolean;
}

export function EligibilityConfigDialog({
  open,
  onOpenChange,
  planName,
  eligibilityConfig,
  onSave,
  isSaving
}: EligibilityConfigDialogProps) {
  // Age restrictions
  const [minAge, setMinAge] = useState<string>('18');
  const [maxAge, setMaxAge] = useState<string>('');

  // KYC & Documents
  const [kycRequired, setKycRequired] = useState(true);
  const [requirePan, setRequirePan] = useState(true);
  const [requireBankAccount, setRequireBankAccount] = useState(true);

  // Country restrictions
  const [useWhitelist, setUseWhitelist] = useState(true);
  const [countriesAllowed, setCountriesAllowed] = useState<string[]>(['IN']);
  const [countriesBlocked, setCountriesBlocked] = useState<string[]>([]);
  const [newCountry, setNewCountry] = useState('');

  // Income requirements
  const [minMonthlyIncome, setMinMonthlyIncome] = useState<string>('');
  const [employmentRequired, setEmploymentRequired] = useState(false);

  // Load existing config
  useEffect(() => {
    if (open) {
      setMinAge(eligibilityConfig.min_age?.toString() || '18');
      setMaxAge(eligibilityConfig.max_age?.toString() || '');
      setKycRequired(eligibilityConfig.kyc_required ?? true);
      setRequirePan(eligibilityConfig.require_pan ?? true);
      setRequireBankAccount(eligibilityConfig.require_bank_account ?? true);
      setCountriesAllowed(eligibilityConfig.countries_allowed || ['IN']);
      setCountriesBlocked(eligibilityConfig.countries_blocked || []);
      setUseWhitelist(!!(eligibilityConfig.countries_allowed && eligibilityConfig.countries_allowed.length > 0));
      setMinMonthlyIncome(eligibilityConfig.min_monthly_income?.toString() || '');
      setEmploymentRequired(eligibilityConfig.employment_required ?? false);
    }
  }, [open, eligibilityConfig]);

  const handleSave = () => {
    const config: EligibilityConfig = {
      min_age: minAge ? parseInt(minAge) : undefined,
      max_age: maxAge ? parseInt(maxAge) : undefined,
      kyc_required: kycRequired,
      require_pan: requirePan,
      require_bank_account: requireBankAccount,
      countries_allowed: useWhitelist ? countriesAllowed : [],
      countries_blocked: useWhitelist ? [] : countriesBlocked,
      min_monthly_income: minMonthlyIncome ? parseFloat(minMonthlyIncome) : undefined,
      employment_required: employmentRequired,
    };
    onSave(config);
  };

  const addCountry = (list: 'allowed' | 'blocked') => {
    const trimmed = newCountry.trim().toUpperCase();
    if (!trimmed || trimmed.length !== 2) return;

    if (list === 'allowed' && !countriesAllowed.includes(trimmed)) {
      setCountriesAllowed([...countriesAllowed, trimmed]);
    } else if (list === 'blocked' && !countriesBlocked.includes(trimmed)) {
      setCountriesBlocked([...countriesBlocked, trimmed]);
    }
    setNewCountry('');
  };

  const removeCountry = (list: 'allowed' | 'blocked', country: string) => {
    if (list === 'allowed') {
      setCountriesAllowed(countriesAllowed.filter(c => c !== country));
    } else {
      setCountriesBlocked(countriesBlocked.filter(c => c !== country));
    }
  };

  const ageErrors = [];
  if (minAge && maxAge && parseInt(minAge) > parseInt(maxAge)) {
    ageErrors.push('Minimum age cannot be greater than maximum age');
  }
  if (minAge && parseInt(minAge) < 18) {
    ageErrors.push('Minimum age must be at least 18 (legal requirement)');
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <ShieldCheck className="h-5 w-5" />
            Eligibility Rules - {planName}
          </DialogTitle>
          <DialogDescription>
            Configure who can subscribe to this plan. Stricter rules help manage risk and compliance.
          </DialogDescription>
        </DialogHeader>

        <Tabs defaultValue="age" className="w-full">
          <TabsList className="grid w-full grid-cols-4">
            <TabsTrigger value="age">Age & KYC</TabsTrigger>
            <TabsTrigger value="geography">Geography</TabsTrigger>
            <TabsTrigger value="income">Income</TabsTrigger>
            <TabsTrigger value="summary">Summary</TabsTrigger>
          </TabsList>

          {/* Age & KYC Tab */}
          <TabsContent value="age" className="space-y-6">
            <div className="space-y-4">
              <h4 className="font-semibold flex items-center gap-2">
                <Users className="h-4 w-4" /> Age Restrictions
              </h4>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Minimum Age</Label>
                  <Input
                    type="number"
                    min="18"
                    max="100"
                    value={minAge}
                    onChange={(e) => setMinAge(e.target.value)}
                    placeholder="18"
                  />
                  <p className="text-xs text-muted-foreground">Must be at least 18 (legal requirement)</p>
                </div>
                <div className="space-y-2">
                  <Label>Maximum Age (Optional)</Label>
                  <Input
                    type="number"
                    min={minAge || '18'}
                    max="100"
                    value={maxAge}
                    onChange={(e) => setMaxAge(e.target.value)}
                    placeholder="No limit"
                  />
                  <p className="text-xs text-muted-foreground">Leave empty for no upper limit</p>
                </div>
              </div>
              {ageErrors.length > 0 && (
                <Alert variant="destructive">
                  <AlertTriangle className="h-4 w-4" />
                  <AlertDescription>
                    <ul className="list-disc list-inside">
                      {ageErrors.map((error, i) => <li key={i}>{error}</li>)}
                    </ul>
                  </AlertDescription>
                </Alert>
              )}
            </div>

            <div className="space-y-4">
              <h4 className="font-semibold">KYC & Document Requirements</h4>
              <div className="space-y-3">
                <div className="flex items-center justify-between p-3 border rounded-lg">
                  <div>
                    <Label>KYC Verification Required</Label>
                    <p className="text-xs text-muted-foreground">User must complete KYC before subscribing</p>
                  </div>
                  <Switch checked={kycRequired} onCheckedChange={setKycRequired} />
                </div>
                <div className="flex items-center justify-between p-3 border rounded-lg">
                  <div>
                    <Label>PAN Card Required</Label>
                    <p className="text-xs text-muted-foreground">Valid PAN must be provided</p>
                  </div>
                  <Switch checked={requirePan} onCheckedChange={setRequirePan} />
                </div>
                <div className="flex items-center justify-between p-3 border rounded-lg">
                  <div>
                    <Label>Bank Account Required</Label>
                    <p className="text-xs text-muted-foreground">Verified bank account must be linked</p>
                  </div>
                  <Switch checked={requireBankAccount} onCheckedChange={setRequireBankAccount} />
                </div>
              </div>
            </div>
          </TabsContent>

          {/* Geography Tab */}
          <TabsContent value="geography" className="space-y-6">
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <h4 className="font-semibold flex items-center gap-2">
                  <Globe className="h-4 w-4" /> Country Restrictions
                </h4>
                <div className="flex items-center gap-2">
                  <Button
                    type="button"
                    variant={useWhitelist ? "default" : "outline"}
                    size="sm"
                    onClick={() => setUseWhitelist(true)}
                  >
                    Whitelist
                  </Button>
                  <Button
                    type="button"
                    variant={!useWhitelist ? "default" : "outline"}
                    size="sm"
                    onClick={() => setUseWhitelist(false)}
                  >
                    Blacklist
                  </Button>
                </div>
              </div>

              {useWhitelist ? (
                <div className="space-y-3">
                  <p className="text-sm text-muted-foreground">
                    Only users from these countries can subscribe:
                  </p>
                  <div className="flex gap-2">
                    <Input
                      value={newCountry}
                      onChange={(e) => setNewCountry(e.target.value)}
                      placeholder="Country code (e.g., IN, US, GB)"
                      maxLength={2}
                      className="uppercase"
                    />
                    <Button type="button" onClick={() => addCountry('allowed')}>
                      <Plus className="h-4 w-4 mr-1" /> Add
                    </Button>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    {countriesAllowed.map((country) => (
                      <Badge key={country} variant="secondary" className="px-3 py-1">
                        {country}
                        <button
                          type="button"
                          onClick={() => removeCountry('allowed', country)}
                          className="ml-2 hover:text-destructive"
                        >
                          <X className="h-3 w-3" />
                        </button>
                      </Badge>
                    ))}
                    {countriesAllowed.length === 0 && (
                      <p className="text-xs text-muted-foreground">No countries whitelisted. Add at least one.</p>
                    )}
                  </div>
                </div>
              ) : (
                <div className="space-y-3">
                  <p className="text-sm text-muted-foreground">
                    Users from these countries cannot subscribe:
                  </p>
                  <div className="flex gap-2">
                    <Input
                      value={newCountry}
                      onChange={(e) => setNewCountry(e.target.value)}
                      placeholder="Country code (e.g., PK, CN)"
                      maxLength={2}
                      className="uppercase"
                    />
                    <Button type="button" onClick={() => addCountry('blocked')}>
                      <Plus className="h-4 w-4 mr-1" /> Add
                    </Button>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    {countriesBlocked.map((country) => (
                      <Badge key={country} variant="destructive" className="px-3 py-1">
                        {country}
                        <button
                          type="button"
                          onClick={() => removeCountry('blocked', country)}
                          className="ml-2 hover:text-white"
                        >
                          <X className="h-3 w-3" />
                        </button>
                      </Badge>
                    ))}
                    {countriesBlocked.length === 0 && (
                      <p className="text-xs text-muted-foreground">No countries blocked. All countries allowed.</p>
                    )}
                  </div>
                </div>
              )}
            </div>
          </TabsContent>

          {/* Income Tab */}
          <TabsContent value="income" className="space-y-6">
            <div className="space-y-4">
              <h4 className="font-semibold flex items-center gap-2">
                <IndianRupee className="h-4 w-4" /> Income Requirements
              </h4>
              <p className="text-sm text-muted-foreground">
                Optional: Set income requirements to ensure users can afford the plan
              </p>

              <div className="space-y-4">
                <div className="space-y-2">
                  <Label>Minimum Monthly Income (₹)</Label>
                  <Input
                    type="number"
                    min="0"
                    step="1000"
                    value={minMonthlyIncome}
                    onChange={(e) => setMinMonthlyIncome(e.target.value)}
                    placeholder="Leave empty for no requirement"
                  />
                  <p className="text-xs text-muted-foreground">
                    Users must declare monthly income above this amount
                  </p>
                </div>

                <div className="flex items-center justify-between p-3 border rounded-lg">
                  <div>
                    <Label>Employment Required</Label>
                    <p className="text-xs text-muted-foreground">User must have employed status</p>
                  </div>
                  <Switch checked={employmentRequired} onCheckedChange={setEmploymentRequired} />
                </div>
              </div>

              <Alert>
                <AlertDescription>
                  <strong>Note:</strong> Income requirements are self-declared. For stricter verification,
                  consider implementing income proof validation or third-party verification services.
                </AlertDescription>
              </Alert>
            </div>
          </TabsContent>

          {/* Summary Tab */}
          <TabsContent value="summary" className="space-y-4">
            <h4 className="font-semibold">Eligibility Rules Summary</h4>

            <div className="space-y-3">
              <div className="p-4 border rounded-lg space-y-2">
                <h5 className="font-medium flex items-center gap-2">
                  <Users className="h-4 w-4" /> Age Requirements
                </h5>
                <p className="text-sm">
                  {minAge && maxAge ? (
                    <>Users must be between {minAge} and {maxAge} years old</>
                  ) : minAge ? (
                    <>Users must be at least {minAge} years old</>
                  ) : (
                    <>No age restrictions</>
                  )}
                </p>
              </div>

              <div className="p-4 border rounded-lg space-y-2">
                <h5 className="font-medium flex items-center gap-2">
                  <ShieldCheck className="h-4 w-4" /> KYC & Documents
                </h5>
                <ul className="text-sm space-y-1">
                  <li>• KYC Verification: {kycRequired ? '✓ Required' : '✗ Not Required'}</li>
                  <li>• PAN Card: {requirePan ? '✓ Required' : '✗ Not Required'}</li>
                  <li>• Bank Account: {requireBankAccount ? '✓ Required' : '✗ Not Required'}</li>
                </ul>
              </div>

              <div className="p-4 border rounded-lg space-y-2">
                <h5 className="font-medium flex items-center gap-2">
                  <Globe className="h-4 w-4" /> Geography
                </h5>
                <p className="text-sm">
                  {useWhitelist ? (
                    <>Allowed countries: {countriesAllowed.join(', ') || 'None'}</>
                  ) : (
                    <>Blocked countries: {countriesBlocked.join(', ') || 'None'}</>
                  )}
                </p>
              </div>

              <div className="p-4 border rounded-lg space-y-2">
                <h5 className="font-medium flex items-center gap-2">
                  <IndianRupee className="h-4 w-4" /> Income Requirements
                </h5>
                <ul className="text-sm space-y-1">
                  {minMonthlyIncome ? (
                    <li>• Minimum monthly income: ₹{parseFloat(minMonthlyIncome).toLocaleString('en-IN')}</li>
                  ) : (
                    <li>• No income requirement</li>
                  )}
                  <li>• Employment: {employmentRequired ? 'Required' : 'Not required'}</li>
                </ul>
              </div>
            </div>
          </TabsContent>
        </Tabs>

        <div className="flex gap-2 pt-4">
          <Button variant="outline" onClick={() => onOpenChange(false)} className="flex-1" disabled={isSaving}>
            Cancel
          </Button>
          <Button onClick={handleSave} className="flex-1" disabled={isSaving || ageErrors.length > 0}>
            {isSaving ? 'Saving...' : 'Save Eligibility Rules'}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}

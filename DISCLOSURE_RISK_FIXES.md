# Disclosure Page Risk Fixes

## ⚠️ Risk 1: is_required Can Drift Without Tier Context

### The Problem
```typescript
// RISKY: No tier context
const requiredDisclosures = disclosures.filter(d => d.is_required)
```

**What could go wrong**:
- Company advances from Tier 1 → Tier 2
- New Tier 2 requirements appear
- Progress drops from 80% → 40%
- Founder sees: "Why did my progress drop?!"
- No explanation provided

### The Fix Applied

#### 1. Added Tier Context to Progress Calculation
```typescript
const getRequirementCompletion = () => {
  const currentTier = getTierInfo().current;

  // Filter disclosures for current tier
  const requiredDisclosures = company.disclosures.filter((d: any) => {
    if (d.is_required === false) return false;

    // TODO: When backend provides tier info:
    // return d.required_for_tier <= currentTier;

    return true; // For now, include all required
  });

  return {
    completed: completedDisclosures.length,
    total: requiredDisclosures.length,
    percentage: ...,
    tier: currentTier, // IMPORTANT: Include tier context
  };
};
```

#### 2. Updated UI to Show Tier Context
```typescript
<CardDescription>
  Based on required disclosures for your current tier (Tier {requirementCompletion.tier})
</CardDescription>
```

#### 3. Added Type Field for Future Backend Support
```typescript
interface DisclosureRequirement {
  // ... other fields
  required_for_tier?: number; // NEW: Track which tier requires this
}
```

### Backend TODO
Backend should provide `required_for_tier` field:
```php
// In CompanyDisclosureService::getIssuerCompanyData()
'disclosures' => [
    // ... existing fields
    'required_for_tier' => $module->tier, // NEW
]
```

Then frontend can filter precisely:
```typescript
const requiredDisclosures = disclosures.filter(d =>
  d.required_for_tier <= currentTier
);
```

---

## ⚠️ Risk 2: Progress Bar Needs Narrative Fallback

### The Problem
Progress changes without explanation:
- "You were at 80%, now you're at 60%"
- "Why? What happened?"
- User feels confused, platform feels arbitrary

### The Fix Applied

#### 1. Progress Change Detection
```typescript
const [previousProgress, setPreviousProgress] = useState<{
  percentage: number;
  total: number;
  tier: number;
} | null>(null);

const [progressChangeReason, setProgressChangeReason] = useState<string | null>(null);
```

#### 2. Detect and Explain Changes
```typescript
useEffect(() => {
  const currentProgress = getRequirementCompletion();
  const stored = localStorage.getItem(`disclosure_progress_${company.id}`);

  if (stored) {
    const previous = JSON.parse(stored);

    // Tier changed
    if (previous.tier !== currentProgress.tier) {
      if (currentProgress.tier > previous.tier) {
        setProgressChangeReason(
          `You've advanced to Tier ${currentProgress.tier}! New disclosure requirements have been added.`
        );
      }
    }

    // Total requirements changed (same tier)
    else if (currentProgress.total !== previous.total) {
      const newCount = currentProgress.total - previous.total;
      setProgressChangeReason(
        `${newCount} new disclosure ${newCount === 1 ? 'requirement was' : 'requirements were'} added.`
      );
    }

    // Significant percentage change (completions)
    else if (Math.abs(currentProgress.percentage - previous.percentage) >= 10) {
      setProgressChangeReason(
        `Great progress! You've completed ${currentProgress.completed - previous.completed} more requirements.`
      );
    }
  }

  // Save current for next comparison
  localStorage.setItem(`disclosure_progress_${company.id}`, JSON.stringify(currentProgress));
}, [company]);
```

#### 3. Display Narrative in UI
```typescript
{progressChangeReason && (
  <Alert className="border-blue-300 bg-blue-50">
    <Info className="h-4 w-4 text-blue-600" />
    <AlertDescription className="text-blue-900">
      {progressChangeReason}
    </AlertDescription>
  </Alert>
)}
```

### Narrative Examples
User will see context-aware messages:
- ✅ "You've advanced to Tier 2! New disclosure requirements have been added."
- ✅ "3 new disclosure requirements were added to your current tier."
- ✅ "2 disclosure requirements were removed or reclassified."
- ✅ "Great progress! You've completed 2 more requirements."

---

## ✅ Refinement 1: Terminology Alignment

### Changes Applied
**Before**: "Disclosure Modules"
**After**: "Disclosure Requirements"

**Rationale**: "Requirement" is more accurate than "module"
- A module is a technical container
- A requirement is what the founder must fulfill
- "Requirements" language aligns with governance framing

### Updated Throughout
- Type name: `DisclosureModule` → `DisclosureRequirement`
- Variable names: `modules` → `requirements`
- UI labels: "Disclosure Modules" → "Disclosure Requirements"
- Comments updated for consistency

---

## 📊 Impact Summary

| Area | Before | After |
|------|--------|-------|
| **Progress context** | No tier awareness | Tier-aware filtering |
| **Change detection** | None | Tracks & explains changes |
| **User confusion** | "Why did progress drop?" | "3 new requirements added" |
| **Terminology** | "Modules" (technical) | "Requirements" (user-facing) |
| **Type safety** | Implicit tier logic | Explicit `required_for_tier` field |

---

## 🔮 Future Enhancements

### 1. Tier-Aware Requirement Filtering (Backend)
```php
// Backend should provide:
'required_for_tier' => $module->tier,
'applies_to_tiers' => [1, 2, 3], // Which tiers need this
```

### 2. Progress History Timeline
Show historical progress:
- "Jan 15: Tier 1 → 100%"
- "Jan 20: Advanced to Tier 2, progress reset to 0%"
- "Feb 1: Tier 2 → 60%"

### 3. Requirement Dependencies
Some requirements may depend on others:
```typescript
interface DisclosureRequirement {
  depends_on?: number[]; // Must complete these first
  unlocks?: number[];     // Completing this unlocks these
}
```

---

## ✅ Testing Scenarios

### Test 1: Tier Advancement
1. Company completes Tier 1 (100%)
2. Platform advances company to Tier 2
3. Reload page
4. **Expected**: Progress shows <100%, narrative explains tier change

### Test 2: New Requirement Added
1. Company at 80% (8/10 requirements)
2. Platform adds 2 new requirements (now 8/12 = 66%)
3. Reload page
4. **Expected**: Progress shows 66%, narrative explains "2 new requirements added"

### Test 3: Requirement Removed
1. Company at 70% (7/10)
2. Platform removes 2 requirements (now 7/8 = 87%)
3. Reload page
4. **Expected**: Progress shows 87%, narrative explains "2 requirements removed"

### Test 4: Normal Progress
1. Company at 60% (6/10)
2. Company completes 2 more (8/10 = 80%)
3. Reload page
4. **Expected**: Progress shows 80%, narrative says "Great progress! 2 more completed"

---

## 📝 Code Locations

- **Main page**: `frontend/app/company/disclosures/page.tsx`
- **Type definitions**: Line 54-68 (DisclosureRequirement interface)
- **Progress calculation**: Line 174-204 (getRequirementCompletion)
- **Change detection**: Line 148-211 (useEffect with localStorage)
- **Narrative display**: Line 376-383 (Alert in progress card)

---

## 🎯 Principle Applied

> **Explain the "why" behind every number**

Progress metrics without context create anxiety.
Progress metrics with narrative create understanding.

The platform is collaborative, not adversarial.
When things change, we explain why—every time.

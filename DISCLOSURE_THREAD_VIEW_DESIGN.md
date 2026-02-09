# Disclosure Thread Detail View - Design Documentation

## Overview
Timeline-style disclosure thread view at `/company/disclosures/[id]` modeled after GitHub PR review interface.

---

## Design Principles

### 1. Timeline as Audit Trail
✅ Append-only history (immutable)
✅ Clear chronological order
✅ Every action timestamped
✅ Actor attribution (company vs platform)

### 2. Respectful Language
✅ "Action Requested" not "Rejected"
✅ "Clarification Requested" not "Needs Fixing"
✅ Professional, collaborative tone
✅ Platform as partner, not adversary

### 3. Transparency & Immutability
✅ All entries permanent (no edits/deletes)
✅ Status changes explicitly shown
✅ Document trail preserved
✅ Complete audit history

---

## Page Structure

### Header Section
**Components**:
- Back button to requirements list
- Requirement name (large, prominent)
- Current status badge
- Requirement description

**Purpose**: Orient the user immediately

---

### Alert Banner (Conditional)
**Shows when**: Status is "clarification_required" AND user can respond

**Message**:
> "Action Requested - The platform has requested additional information for this disclosure. Please review the request below and provide a response."

**Color**: Amber (attention without alarm)

---

### Timeline Card
**Main Content Area**

#### Timeline Entry Structure
Each entry contains:
1. **Actor Avatar**
   - Company: Blue background, building icon
   - Platform: Purple background, shield icon

2. **Actor Name & Role**
   - Name in bold
   - Badge: "Company" or "Platform"
   - Timestamp (relative: "2 days ago")

3. **Event Type Badge**
   - Submitted (blue)
   - Requested Clarification (amber)
   - Responded (green)
   - Approved (green)
   - Status Changed (gray)

4. **Event Content**
   - Message text (if applicable)
   - Status change details (if applicable)
   - Attached documents (if any)

5. **Document Attachments**
   - File icon + filename
   - File size
   - Download link
   - Hover effect

#### Timeline Event Types

| Type | Actor | Icon | Description |
|------|-------|------|-------------|
| `submission` | Company | Upload | Initial submission or update |
| `clarification` | Platform | MessageSquare | Request for more info |
| `response` | Company | MessageSquare | Response to clarification |
| `approval` | Platform | CheckCircle | Disclosure approved |
| `status_change` | Platform | None | Status transition logged |

---

### Reply Interface (Conditional)
**Shows when**: `can_respond === true`

#### Two Modes

**Mode 1: Collapsed (default)**
- Single button: "Write Response"
- Description text
- Minimal space

**Mode 2: Expanded (after click)**
- Large textarea for response
- File upload input (multiple files)
- Uploaded files list with sizes
- Info alert: "All entries are permanent and cannot be edited or deleted"
- Action buttons: "Submit Response" + "Cancel"

#### Validation
- Submit disabled if textarea empty
- Submit shows "Submitting..." during API call
- Success toast on completion
- Thread reloads after submission

---

### Cannot Respond Notice (Conditional)
**Shows when**: `can_respond === false`

**Messages**:
- If approved: "This disclosure has been approved and is now locked. Contact platform support if you need to make changes."
- Otherwise: "You cannot respond at this time. Platform restrictions may be in effect."

---

## Timeline Entry Examples

### Example 1: Initial Submission
```
[Building Icon] John Smith • Company • 2 days ago
[Blue Badge: Submitted]

Initial submission of board composition details including all current directors.

📄 Attached Documents:
   board-composition.pdf (245 KB)
```

### Example 2: Clarification Request
```
[Shield Icon] Platform Review Team • Platform • 1 day ago
[Amber Badge: Requested Clarification]

Thank you for the submission. We need additional clarification on the
independence criteria for two directors. Could you provide details on any
business relationships they may have with the company?
```

### Example 3: Status Change
```
[Shield Icon] System • Platform • 1 day ago
[Gray Badge: Status Changed]

Status changed from under_review to clarification_required
```

### Example 4: Company Response
```
[Building Icon] John Smith • Company • 2 hours ago
[Green Badge: Responded]

Thank you for the review. I've provided additional documentation showing
that both directors meet independence criteria under our governance policy.

📄 Attached Documents:
   independence-verification.pdf (180 KB)
   governance-policy.pdf (95 KB)
```

### Example 5: Approval
```
[Shield Icon] Platform Review Team • Platform • 30 minutes ago
[Green Badge: Approved]

Thank you for the clarification. This disclosure has been reviewed and approved.
All requirements for this section are now satisfied.
```

---

## UX Details

### Visual Hierarchy
1. **Status badge** - Most prominent (top right)
2. **Alert banner** - Action needed (if applicable)
3. **Timeline** - Chronological audit trail
4. **Reply interface** - Clear call-to-action

### Color System
- **Company actions**: Blue (#3B82F6)
- **Platform actions**: Purple (#9333EA)
- **Clarifications**: Amber (#F59E0B)
- **Approvals**: Green (#10B981)
- **Neutral/System**: Gray (#6B7280)

### Spacing
- Timeline entries: 24px vertical gap
- Separator lines between entries
- Card padding: 24px
- Avatar size: 40px

### Typography
- Entry names: font-semibold
- Timestamps: text-sm, text-gray-500
- Messages: text-sm, whitespace-pre-wrap
- File names: text-sm, font-medium

---

## Interaction States

### Loading State
- Centered spinner
- Blue color
- Minimal text

### Error State
- Destructive alert
- Clear error message
- No data displayed

### Empty Timeline
(Shouldn't happen - disclosures always have initial submission)

### Reply Mode States
1. **Collapsed**: Single button
2. **Editing**: Textarea + file upload visible
3. **Submitting**: Button disabled, shows "Submitting..."
4. **Success**: Toast notification, mode collapsed, timeline reloads

---

## Technical Implementation

### Data Structure
```typescript
interface TimelineEvent {
  id: number;
  type: 'submission' | 'clarification' | 'response' | 'approval' | 'status_change';
  actor: 'company' | 'platform';
  actor_name: string;
  timestamp: string;
  message?: string;
  documents?: Document[];
  status_change?: {
    from: string;
    to: string;
  };
}

interface DisclosureThread {
  id: number;
  requirement_name: string;
  requirement_description?: string;
  current_status: string;
  can_respond: boolean;
  timeline: TimelineEvent[];
  created_at: string;
  updated_at: string;
}
```

### API Endpoints Needed

```
GET  /api/v1/company/disclosures/:id
     → Returns DisclosureThread with timeline

POST /api/v1/company/disclosures/:id/respond
     Body: { message: string, documents: File[] }
     → Adds response entry to timeline
```

### State Management
```typescript
const [thread, setThread] = useState<DisclosureThread | null>(null);
const [replyMode, setReplyMode] = useState(false);
const [replyText, setReplyText] = useState("");
const [uploadedFiles, setUploadedFiles] = useState<File[]>([]);
const [submitting, setSubmitting] = useState(false);
```

---

## Backend Requirements

### Timeline Data
Backend must return:
1. Complete chronological history
2. Actor information for each entry
3. Document URLs (using storage proxy)
4. Status change tracking
5. Timestamps in ISO 8601 format

### Permissions
Backend must enforce:
- `can_respond` based on platform restrictions
- `can_respond` false when approved (locked)
- Document access control
- Immutability (no edit/delete endpoints)

### Document Storage
- Documents must be stored immutably
- URLs must use storage proxy: `/api/storage/...`
- File size limits enforced
- Allowed file types enforced

---

## Accessibility

✅ Semantic HTML structure
✅ ARIA labels on interactive elements
✅ Keyboard navigation support
✅ Color not sole indicator (icons + text)
✅ Focus states on all interactive elements
✅ Screen reader friendly timeline

---

## Mobile Considerations

### Responsive Design
- Stack timeline entries vertically
- Avatar size: 32px on mobile
- Single-column layout
- Touch-friendly tap targets (min 44px)
- File upload adapts to mobile input

### Performance
- Lazy load timeline entries if >20
- Paginate very long threads
- Optimize document preview loading

---

## Future Enhancements

### Phase 2
1. **Real-time updates** - Timeline updates without refresh when platform responds
2. **Notifications** - Email/push when clarification requested
3. **Draft responses** - Auto-save reply drafts
4. **Inline document preview** - Preview PDFs in timeline

### Phase 3
1. **Thread references** - Link to related disclosures
2. **Version comparison** - Diff view between submissions
3. **Export thread** - Download complete audit trail as PDF
4. **Search/filter** - Find specific entries in long threads

---

## Testing Scenarios

### Scenario 1: View Approved Disclosure
1. Navigate to approved disclosure
2. See complete timeline ending in approval
3. See "Cannot respond" notice
4. No reply interface shown

### Scenario 2: Respond to Clarification
1. Navigate to disclosure with clarification_required
2. See amber alert banner
3. Click "Write Response"
4. Type response text
5. Upload supporting documents
6. Submit successfully
7. See toast notification
8. Timeline updates with new entry

### Scenario 3: View Long Thread
1. Navigate to disclosure with many entries
2. Scroll through complete timeline
3. All entries visible in chronological order
4. Documents downloadable
5. Status changes clearly marked

### Scenario 4: Platform Restriction Active
1. Navigate to any disclosure
2. Company is suspended/frozen
3. See "Cannot respond" notice
4. Reply interface disabled/hidden
5. Clear explanation provided

---

## Language Examples

### Good (Collaborative)
✅ "Action Requested"
✅ "Requested Clarification"
✅ "Thank you for the submission"
✅ "Could you provide..."
✅ "Additional information needed"

### Bad (Adversarial)
❌ "Rejected"
❌ "Failed Review"
❌ "You must fix"
❌ "Incomplete"
❌ "Not acceptable"

---

## File Location
`frontend/app/company/disclosures/[id]/page.tsx`

## Dependencies
- `date-fns` for relative timestamps
- shadcn/ui components
- Next.js 16 App Router
- React 19

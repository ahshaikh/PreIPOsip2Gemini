# Live Chat Module - Architectural TODO

**Document Version:** 1.0
**Created:** 2025-12-17
**Related Audit:** Module 14 - Live Chat & AI
**Priority:** CRITICAL & HIGH

---

## V-AUDIT-MODULE14-TODO: Critical & High Priority Architectural Issues

This document outlines the remaining CRITICAL and HIGH priority architectural issues identified in the Module 14 audit that require significant infrastructure changes and cannot be resolved with simple code fixes.

---

## 1. CRITICAL: Frontend/Backend Disconnect

### Issue Description
The Frontend `LiveChatWidget.tsx` completely ignores the dedicated Live Chat backend architecture. Instead of calling the proper Live Chat API endpoints (`/api/v1/live-chat/...`), it incorrectly calls the Support Ticket API (`/user/support-tickets`).

### Impact
- **Dead Code:** The entire Live Chat backend (Controllers, Models, Routing logic, ChatAgentStatus) is unused
- **Missing Features:** Agent availability checks, routing logic, and specialized chat features are bypassed
- **False Implementation:** The frontend creates a "Chat-like" UI but uses the ticket system underneath
- **User Confusion:** Users think they're chatting live but are actually creating support tickets

### Evidence
**Frontend Widget (`LiveChatWidget.tsx`):**
- Line 37: `const { data: messages } = useQuery([`user/support-tickets/${ticketId}`]);`
- Line 72: `mutation = useMutation(...) => axios.post(\`user/support-tickets\`);`
- Line 75: `axios.post(\`user/support-tickets/${ticketId}/reply\`);`

**Backend Live Chat API (UNUSED):**
- `POST /api/v1/live-chat/sessions` - Start new chat session
- `POST /api/v1/live-chat/sessions/{sessionCode}/messages` - Send message
- `GET /api/v1/live-chat/availability` - Check agent availability
- `GET /api/v1/live-chat/active-session` - Get active session

### Required Fix
**Option A: Connect Frontend to Live Chat Backend (Recommended)**
1. Rewrite `LiveChatWidget.tsx` to consume `/api/v1/live-chat/...` endpoints
2. Implement proper session management using `session_code`
3. Add agent availability checking before starting chat
4. Update UI to reflect actual chat features (agent assignment, online status)
5. Test end-to-end flow: Start session → Agent accepts → Exchange messages → Close session

**Option B: Remove Live Chat Backend (If Ticket-Based is Intentional)**
1. Delete unused Live Chat backend code:
   - `app/Http/Controllers/Api/User/LiveChatController.php`
   - `app/Http/Controllers/Api/Admin/LiveChatController.php`
   - `app/Models/LiveChatSession.php`
   - `app/Models/LiveChatMessage.php`
   - `app/Models/ChatAgentStatus.php`
2. Update routes to remove `/api/v1/live-chat/...` endpoints
3. Rename frontend component to `SupportTicketWidget.tsx` for clarity
4. Update user-facing language to reflect ticket-based support (not "live chat")

### Priority
**CRITICAL** - This must be resolved before launching the Live Chat feature to production.

---

## 2. HIGH: Replace Polling with WebSockets

### Issue Description
The Frontend uses `refetchInterval: 5000` (5 seconds) to poll for new messages, simulating real-time updates.

### Impact
- **Massive Load:** 1,000 online users = 12,000 HTTP requests per minute to the server
- **Database Stress:** Each poll queries the database regardless of whether messages exist
- **Network Waste:** 99% of polls return no new data but consume bandwidth
- **Poor UX:** 5-second delay before seeing new messages (not truly "live")
- **Scalability Limit:** System cannot scale beyond ~2,000 concurrent users

### Required Fix
Implement WebSocket-based real-time communication using one of:

**Option A: Laravel Reverb (Recommended - Official Laravel Solution)**
```bash
composer require laravel/reverb
php artisan reverb:install
```

**Option B: Pusher (Managed Service)**
```bash
composer require pusher/pusher-php-server
```

**Option C: Soketi (Self-Hosted Pusher Alternative)**
```bash
npm install -g @soketi/soketi
```

### Implementation Steps
1. **Backend Setup:**
   - Install Laravel Broadcasting package
   - Configure broadcast driver (reverb/pusher/soketi)
   - Create broadcast events:
     - `MessageSent` - When agent/user sends message
     - `AgentAssigned` - When agent accepts chat
     - `SessionClosed` - When chat is closed
   - Add `ShouldBroadcast` interface to events
   - Configure broadcast channels with authorization

2. **Frontend Setup:**
   - Install `laravel-echo` and `pusher-js` (or `soketi-js`)
   - Configure Echo client to connect to WebSocket server
   - Subscribe to private channel: `live-chat.{sessionCode}`
   - Listen for events: `.listen('MessageSent', callback)`
   - Remove `refetchInterval` polling

3. **Testing:**
   - Test message delivery latency (should be <100ms)
   - Test connection handling (reconnect on disconnect)
   - Load test with 1,000+ concurrent connections
   - Verify authorization (users can only join their own chats)

### Expected Performance Improvement
- **Request Reduction:** 12,000/min → ~50/min (95%+ reduction)
- **Database Load:** Near-zero idle queries
- **Real-Time:** <100ms message delivery vs 5-second polling
- **Scalability:** Support 10,000+ concurrent users

### Priority
**HIGH** - Required for "Live Chat" to function properly. Current polling is acceptable for low traffic (<100 users) but not scalable.

---

## 3. MEDIUM: Full-Text Search for AI Service

### Issue Description
`SupportAIService::suggestArticles()` uses multiple `OR LIKE` queries for keyword matching:
```php
->where('title', 'LIKE', "%{$keyword}%")
->orWhere('content', 'LIKE', "%{$keyword}%")
```

### Impact
- **Slow Searches:** 10-20 `OR` clauses can cause slow queries on large datasets
- **Poor Relevance:** Simple substring matching doesn't understand semantics
- **No Ranking:** Results not sorted by relevance
- **Inefficient:** Full table scans even with indexes on large text fields

### Recommended Fix

**Option A: MySQL Full-Text Search**
```sql
ALTER TABLE help_articles ADD FULLTEXT INDEX ft_title_content (title, content);
```
```php
$articles = HelpArticle::whereRaw(
    'MATCH(title, content) AGAINST(? IN NATURAL LANGUAGE MODE)',
    [$searchQuery]
)->get();
```

**Option B: Meilisearch (Recommended)**
```bash
composer require meilisearch/meilisearch-php
composer require laravel/scout
```
- Instant search (<50ms)
- Typo tolerance
- Relevance ranking
- Highlighting
- Faceted search

**Option C: OpenAI Embeddings (Semantic Search)**
- Store article embeddings in `pgvector`
- Use cosine similarity for semantic matching
- Understands intent (e.g., "bill" matches "invoice")

### Priority
**MEDIUM** - Current approach works for small datasets. Upgrade when article count exceeds 1,000 or search becomes noticeably slow.

---

## 4. Additional Recommendations

### A. Add Typing Indicators
Use WebSocket events to show "Agent is typing..." indicator.

### B. Add File Upload Support
- Implement file upload in chat messages
- Store files in S3/cloudinary
- Validate file types and sizes
- Generate thumbnails for images

### C. Add Chat Transcripts Export
- Generate PDF transcripts
- Send transcript via email after chat closes
- Store transcripts for compliance

### D. Add Chat Analytics
- Average response time per agent
- Customer satisfaction scores
- Peak hours analysis
- Topic classification

---

## Timeline Recommendations

| Priority | Item | Effort | Timeline |
|----------|------|--------|----------|
| **CRITICAL** | Frontend/Backend Connection | 2-3 days | Before launch |
| **HIGH** | WebSocket Implementation | 3-5 days | Week 1-2 |
| **MEDIUM** | Full-Text Search | 1-2 days | Month 1-2 |
| **LOW** | Additional Features | 1 week | Month 2-3 |

---

## References

- **Audit Report:** `audit/Module14.txt`
- **Audit Score:** 4.5/10 (Critical issues present)
- **Laravel Broadcasting Docs:** https://laravel.com/docs/broadcasting
- **Laravel Reverb Docs:** https://reverb.laravel.com
- **Meilisearch Laravel Docs:** https://www.meilisearch.com/docs/learn/getting_started/quick_start

---

## Notes for Developers

- ✅ **Module 14 Completed Fixes (2025-12-17):**
  - HIGH: Rate limiting on sendMessage (30 messages/minute)
  - MEDIUM: Race condition fix in acceptSession (lockForUpdate)
  - LOW: Secure session codes (Str::random vs uniqid)

- ❌ **Not Yet Fixed (Architectural):**
  - CRITICAL: Frontend/Backend disconnect
  - HIGH: WebSocket implementation
  - MEDIUM: Full-Text Search upgrade

**These architectural issues require dedicated implementation time and infrastructure setup.**

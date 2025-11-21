7. Maintenance & Support Guide
A. How to Add New Files/Modules (e.g., a new "Blog" module)

Backend (Laravel):

php artisan make:model Blog -mcr: This creates the BlogPost model, 2025..._create_blog_posts_table.php migration, and BlogPostController.

Edit the migration file to add fields (title, slug, content, author_id, status).

php artisan migrate

Add the new API routes in routes/api.php (e.g., Route::apiResource('/admin/blog', BlogController::class)).

Implement the controller logic.

Frontend (Next.js):

Create a new admin page: /frontend/app/(admin)/settings/blog/page.tsx.

Build a React component to fetch, create, and edit posts using the new API endpoints.

Add a link to the new page in /frontend/components/shared/AdminNav.tsx.

B. How to Update Navigation / Menu Items

This is easy, as we built it to be configurable .


Log in to the Admin Panel.

Navigate to Settings -> CMS (or a dedicated "Menus" section).

You will see a UI to manage the "Header Navigation" and "Footer Navigation" menus.

You can add, edit, re-order, or delete links (Label, URL) directly.

Click Save. The website header and footer will update immediately (due to caching being cleared).

C. Testing Checklist (Functional/UI)

Before any major deployment, run through this manual checklist:

[ ] Admin: Can you log in as an Admin?

[ ] Admin: Can you disable "User Registration" via the toggle?

[CSS] Public: Is the homepage layout correct on both desktop and mobile?

[ ] Public: Can a new user not register (while toggle is off)?

[ ] Admin: Enable "User Registration."

[ ] Public: Can a new user register and verify their account?

[ ] User: Can the new user submit the KYC form?

[ ] Admin: Does the KYC submission appear in the /admin/kyc-queue?

[ ] Admin: Can you Approve the KYC?

[ ] User: Does the dashboard now show "Verified"?

[ ] User: Can the user subscribe to a plan and complete a (test) payment?

[ ] User: Does the /portfolio page show the new investment?

[ ] User: Does the /bonuses page show the 10% bonus?

[ ] User: Can the user request a withdrawal?

[ ] Admin: Can you approve, process, and complete the withdrawal with a UTR?

[ ] User: Does the wallet balance and transaction history look correct?
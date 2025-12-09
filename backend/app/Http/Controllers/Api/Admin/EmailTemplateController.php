<?php
// V-PHASE2-1730-060
// Enhanced with variables system, testing, and analytics

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailTemplateController extends Controller
{
    /**
     * Get all email templates with statistics
     */
    public function index(Request $request)
    {
        $query = EmailTemplate::query();

        // Search by name or slug
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $templates = $query->get();

        // Add statistics if requested
        if ($request->boolean('include_stats')) {
            $templates->each(function ($template) {
                $template->stats = [
                    'total_sent' => EmailLog::where('template_slug', $template->slug)->count(),
                    'sent_last_30_days' => EmailLog::where('template_slug', $template->slug)
                        ->recent(30)->count(),
                    'open_rate' => $this->calculateOpenRate($template->slug),
                    'click_rate' => $this->calculateClickRate($template->slug),
                ];
            });
        }

        return response()->json($templates);
    }

    /**
     * Create new email template
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:email_templates',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
            'variables' => 'nullable|array',
            'variables.*.name' => 'required|string',
            'variables.*.description' => 'nullable|string',
            'variables.*.example' => 'nullable|string',
        ]);

        // Auto-generate slug if not provided
        if (!isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Extract variables from body if not provided
        if (!isset($validated['variables'])) {
            $validated['variables'] = $this->extractVariablesFromBody($validated['body']);
        }

        $template = EmailTemplate::create($validated);
        return response()->json($template, 201);
    }

    /**
     * Get single email template
     */
    public function show(EmailTemplate $emailTemplate)
    {
        return response()->json($emailTemplate);
    }

    /**
     * Update email template
     */
    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'subject' => 'sometimes|string|max:500',
            'body' => 'sometimes|string',
            'variables' => 'nullable|array',
            'variables.*.name' => 'required|string',
            'variables.*.description' => 'nullable|string',
            'variables.*.example' => 'nullable|string',
        ]);

        // Re-extract variables if body is updated
        if (isset($validated['body']) && !isset($validated['variables'])) {
            $validated['variables'] = $this->extractVariablesFromBody($validated['body']);
        }

        $emailTemplate->update($validated);
        return response()->json($emailTemplate);
    }

    /**
     * Delete email template
     */
    public function destroy(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();
        return response()->json(['message' => 'Template deleted successfully']);
    }

    /**
     * Get template variables
     */
    public function variables(EmailTemplate $emailTemplate)
    {
        $extractedVars = $this->extractVariablesFromBody($emailTemplate->body);

        return response()->json([
            'defined_variables' => $emailTemplate->variables ?? [],
            'extracted_variables' => $extractedVars,
            'usage_count' => count($extractedVars),
        ]);
    }

    /**
     * Preview template with sample data
     */
    public function preview(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'sample_data' => 'nullable|array',
        ]);

        $sampleData = $validated['sample_data'] ?? $this->getDefaultSampleData($emailTemplate);

        $subject = $this->replaceVariables($emailTemplate->subject, $sampleData);
        $body = $this->replaceVariables($emailTemplate->body, $sampleData);

        return response()->json([
            'subject' => $subject,
            'body' => $body,
            'sample_data' => $sampleData,
        ]);
    }

    /**
     * Send test email
     */
    public function sendTest(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'test_email' => 'required|email',
            'sample_data' => 'nullable|array',
        ]);

        $sampleData = $validated['sample_data'] ?? $this->getDefaultSampleData($emailTemplate);

        $subject = $this->replaceVariables($emailTemplate->subject, $sampleData);
        $body = $this->replaceVariables($emailTemplate->body, $sampleData);

        try {
            Mail::html($body, function ($message) use ($validated, $subject) {
                $message->to($validated['test_email'])
                    ->subject('[TEST] ' . $subject);
            });

            return response()->json([
                'message' => 'Test email sent successfully to ' . $validated['test_email'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send test email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate template
     */
    public function duplicate(EmailTemplate $emailTemplate)
    {
        $newTemplate = $emailTemplate->replicate();
        $newTemplate->name = $emailTemplate->name . ' (Copy)';
        $newTemplate->slug = $emailTemplate->slug . '-copy-' . time();
        $newTemplate->save();

        return response()->json($newTemplate, 201);
    }

    /**
     * Get template analytics
     */
    public function analytics(EmailTemplate $emailTemplate, Request $request)
    {
        $days = $request->input('days', 30);

        $logs = EmailLog::where('template_slug', $emailTemplate->slug)
            ->recent($days);

        $totalSent = $logs->count();
        $delivered = $logs->clone()->delivered()->count();
        $opened = $logs->clone()->opened()->count();
        $clicked = $logs->clone()->clicked()->count();
        $bounced = $logs->clone()->bounced()->count();
        $failed = $logs->clone()->failed()->count();

        return response()->json([
            'template' => $emailTemplate->only(['id', 'name', 'slug']),
            'period_days' => $days,
            'stats' => [
                'total_sent' => $totalSent,
                'delivered' => $delivered,
                'opened' => $opened,
                'clicked' => $clicked,
                'bounced' => $bounced,
                'failed' => $failed,
            ],
            'rates' => [
                'delivery_rate' => $totalSent > 0 ? round(($delivered / $totalSent) * 100, 2) : 0,
                'open_rate' => $delivered > 0 ? round(($opened / $delivered) * 100, 2) : 0,
                'click_rate' => $opened > 0 ? round(($clicked / $opened) * 100, 2) : 0,
                'bounce_rate' => $totalSent > 0 ? round(($bounced / $totalSent) * 100, 2) : 0,
                'failure_rate' => $totalSent > 0 ? round(($failed / $totalSent) * 100, 2) : 0,
            ],
            'recent_logs' => $logs->clone()->latest()->limit(10)->get(),
        ]);
    }

    /**
     * Helper: Extract variables from email body
     */
    private function extractVariablesFromBody($body)
    {
        // Match {{variable_name}} pattern
        preg_match_all('/\{\{(\w+)\}\}/', $body, $matches);

        $variables = [];
        if (!empty($matches[1])) {
            foreach (array_unique($matches[1]) as $varName) {
                $variables[] = [
                    'name' => $varName,
                    'description' => '',
                    'example' => '',
                ];
            }
        }

        return $variables;
    }

    /**
     * Helper: Replace variables in text
     */
    private function replaceVariables($text, $data)
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{{' . $key . '}}', $value, $text);
        }
        return $text;
    }

    /**
     * Helper: Get default sample data for template
     */
    private function getDefaultSampleData($template)
    {
        $sampleData = [];

        if ($template->variables) {
            foreach ($template->variables as $variable) {
                $sampleData[$variable['name']] = $variable['example'] ?? 'Sample ' . $variable['name'];
            }
        }

        // Common default variables
        $defaults = [
            'user_name' => 'John Doe',
            'app_name' => config('app.name', 'PreIPOSip'),
            'app_url' => config('app.url', 'https://preiposip.com'),
            'support_email' => setting('support_email', 'support@preiposip.com'),
            'year' => date('Y'),
        ];

        return array_merge($defaults, $sampleData);
    }

    /**
     * Helper: Calculate open rate for a template
     */
    private function calculateOpenRate($templateSlug, $days = 30)
    {
        $sent = EmailLog::where('template_slug', $templateSlug)->recent($days)->count();
        if ($sent === 0) return 0;

        $opened = EmailLog::where('template_slug', $templateSlug)->recent($days)->opened()->count();
        return round(($opened / $sent) * 100, 2);
    }

    /**
     * Helper: Calculate click rate for a template
     */
    private function calculateClickRate($templateSlug, $days = 30)
    {
        $opened = EmailLog::where('template_slug', $templateSlug)->recent($days)->opened()->count();
        if ($opened === 0) return 0;

        $clicked = EmailLog::where('template_slug', $templateSlug)->recent($days)->clicked()->count();
        return round(($clicked / $opened) * 100, 2);
    }
}

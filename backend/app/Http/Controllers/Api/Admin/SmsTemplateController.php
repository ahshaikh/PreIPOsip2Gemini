<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsTemplate;
use App\Models\SmsLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SmsTemplateController extends Controller
{
    /**
     * Get all SMS templates with statistics
     */
    public function index(Request $request)
    {
        $query = SmsTemplate::query();

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
                    'total_sent' => SmsLog::where('template_slug', $template->slug)->count(),
                    'sent_last_30_days' => SmsLog::where('template_slug', $template->slug)
                        ->recent(30)->count(),
                    'delivery_rate' => $this->calculateDeliveryRate($template->slug),
                    'character_count' => mb_strlen($template->body),
                ];
            });
        }

        return response()->json($templates);
    }

    /**
     * Create new SMS template
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:sms_templates',
            'body' => 'required|string|max:' . setting('sms_max_length', 160),
            'dlt_template_id' => 'nullable|string|max:255',
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

        // Validate character count after variable replacement
        $sampleData = $this->getDefaultSampleData($validated['variables'] ?? []);
        $processedBody = $this->replaceVariables($validated['body'], $sampleData);
        $characterCount = mb_strlen($processedBody);

        if ($characterCount > setting('sms_max_length', 160)) {
            return response()->json([
                'message' => 'SMS template exceeds maximum character limit after variable replacement',
                'character_count' => $characterCount,
                'max_length' => setting('sms_max_length', 160),
            ], 422);
        }

        $template = SmsTemplate::create($validated);
        return response()->json($template, 201);
    }

    /**
     * Get single SMS template
     */
    public function show(SmsTemplate $smsTemplate)
    {
        $smsTemplate->character_count = mb_strlen($smsTemplate->body);
        return response()->json($smsTemplate);
    }

    /**
     * Update SMS template
     */
    public function update(Request $request, SmsTemplate $smsTemplate)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'body' => 'sometimes|string|max:' . setting('sms_max_length', 160),
            'dlt_template_id' => 'nullable|string|max:255',
            'variables' => 'nullable|array',
            'variables.*.name' => 'required|string',
            'variables.*.description' => 'nullable|string',
            'variables.*.example' => 'nullable|string',
        ]);

        // Re-extract variables if body is updated
        if (isset($validated['body']) && !isset($validated['variables'])) {
            $validated['variables'] = $this->extractVariablesFromBody($validated['body']);
        }

        // Validate character count after variable replacement
        if (isset($validated['body'])) {
            $sampleData = $this->getDefaultSampleData($validated['variables'] ?? $smsTemplate->variables ?? []);
            $processedBody = $this->replaceVariables($validated['body'], $sampleData);
            $characterCount = mb_strlen($processedBody);

            if ($characterCount > setting('sms_max_length', 160)) {
                return response()->json([
                    'message' => 'SMS template exceeds maximum character limit after variable replacement',
                    'character_count' => $characterCount,
                    'max_length' => setting('sms_max_length', 160),
                ], 422);
            }
        }

        $smsTemplate->update($validated);
        return response()->json($smsTemplate);
    }

    /**
     * Delete SMS template
     */
    public function destroy(SmsTemplate $smsTemplate)
    {
        $smsTemplate->delete();
        return response()->json(['message' => 'SMS template deleted successfully']);
    }

    /**
     * Get template variables
     */
    public function variables(SmsTemplate $smsTemplate)
    {
        $extractedVars = $this->extractVariablesFromBody($smsTemplate->body);

        return response()->json([
            'defined_variables' => $smsTemplate->variables ?? [],
            'extracted_variables' => $extractedVars,
            'usage_count' => count($extractedVars),
        ]);
    }

    /**
     * Preview template with sample data
     */
    public function preview(Request $request, SmsTemplate $smsTemplate)
    {
        $validated = $request->validate([
            'sample_data' => 'nullable|array',
        ]);

        $sampleData = $validated['sample_data'] ?? $this->getDefaultSampleData($smsTemplate->variables ?? []);

        $body = $this->replaceVariables($smsTemplate->body, $sampleData);
        $characterCount = mb_strlen($body);
        $maxLength = setting('sms_max_length', 160);

        return response()->json([
            'body' => $body,
            'sample_data' => $sampleData,
            'character_count' => $characterCount,
            'max_length' => $maxLength,
            'segments' => ceil($characterCount / $maxLength),
            'within_limit' => $characterCount <= $maxLength,
        ]);
    }

    /**
     * Send test SMS
     */
    public function sendTest(Request $request, SmsTemplate $smsTemplate)
    {
        $validated = $request->validate([
            'test_mobile' => 'required|string|regex:/^[0-9]{10,15}$/',
            'sample_data' => 'nullable|array',
        ]);

        $sampleData = $validated['sample_data'] ?? $this->getDefaultSampleData($smsTemplate->variables ?? []);

        $body = $this->replaceVariables($smsTemplate->body, $sampleData);

        try {
            // Use the SMS service to send test SMS
            $provider = setting('sms_provider', 'log');

            if ($provider === 'log') {
                // Log mode - just log to database
                SmsLog::create([
                    'sms_template_id' => $smsTemplate->id,
                    'recipient_mobile' => $validated['test_mobile'],
                    'recipient_name' => 'Test User',
                    'template_slug' => $smsTemplate->slug,
                    'dlt_template_id' => $smsTemplate->dlt_template_id,
                    'message' => '[TEST] ' . $body,
                    'status' => 'sent',
                    'provider' => 'log',
                    'sent_at' => now(),
                ]);

                return response()->json([
                    'message' => 'Test SMS logged successfully (log mode)',
                    'mobile' => $validated['test_mobile'],
                    'body' => '[TEST] ' . $body,
                ]);
            }

            // For actual SMS providers, integrate here
            // This would use the NotificationService or SMS provider specific logic

            return response()->json([
                'message' => 'Test SMS functionality not fully implemented for provider: ' . $provider,
            ], 501);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send test SMS',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate template
     */
    public function duplicate(SmsTemplate $smsTemplate)
    {
        $newTemplate = $smsTemplate->replicate();
        $newTemplate->name = $smsTemplate->name . ' (Copy)';
        $newTemplate->slug = $smsTemplate->slug . '-copy-' . time();
        $newTemplate->save();

        return response()->json($newTemplate, 201);
    }

    /**
     * Get template analytics
     */
    public function analytics(SmsTemplate $smsTemplate, Request $request)
    {
        $days = $request->input('days', 30);

        $logs = SmsLog::where('template_slug', $smsTemplate->slug)
            ->recent($days);

        $totalSent = $logs->count();
        $delivered = $logs->clone()->delivered()->count();
        $failed = $logs->clone()->failed()->count();

        return response()->json([
            'template' => $smsTemplate->only(['id', 'name', 'slug']),
            'period_days' => $days,
            'stats' => [
                'total_sent' => $totalSent,
                'delivered' => $delivered,
                'failed' => $failed,
            ],
            'rates' => [
                'delivery_rate' => $totalSent > 0 ? round(($delivered / $totalSent) * 100, 2) : 0,
                'failure_rate' => $totalSent > 0 ? round(($failed / $totalSent) * 100, 2) : 0,
            ],
            'costs' => [
                'total_credits_used' => $logs->clone()->sum('credits_used') ?? 0,
                'avg_credits_per_sms' => $totalSent > 0 ? round($logs->clone()->avg('credits_used') ?? 0, 2) : 0,
            ],
            'recent_logs' => $logs->clone()->latest()->limit(10)->get(),
        ]);
    }

    /**
     * Validate character count
     */
    public function validateCharacterCount(Request $request)
    {
        $validated = $request->validate([
            'body' => 'required|string',
            'sample_data' => 'nullable|array',
        ]);

        $sampleData = $validated['sample_data'] ?? [];
        $processedBody = $this->replaceVariables($validated['body'], $sampleData);
        $characterCount = mb_strlen($processedBody);
        $maxLength = setting('sms_max_length', 160);

        return response()->json([
            'original_body' => $validated['body'],
            'processed_body' => $processedBody,
            'character_count' => $characterCount,
            'max_length' => $maxLength,
            'segments' => ceil($characterCount / $maxLength),
            'within_limit' => $characterCount <= $maxLength,
            'remaining_characters' => $maxLength - $characterCount,
        ]);
    }

    /**
     * Helper: Extract variables from SMS body
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
    private function getDefaultSampleData($variables)
    {
        $sampleData = [];

        if ($variables) {
            foreach ($variables as $variable) {
                $sampleData[$variable['name']] = $variable['example'] ?? 'Sample';
            }
        }

        // Common default variables
        $defaults = [
            'user_name' => 'John',
            'app_name' => 'PreIPOSip',
            'otp' => '123456',
            'amount' => '1000',
        ];

        return array_merge($defaults, $sampleData);
    }

    /**
     * Helper: Calculate delivery rate for a template
     */
    private function calculateDeliveryRate($templateSlug, $days = 30)
    {
        $sent = SmsLog::where('template_slug', $templateSlug)->recent($days)->count();
        if ($sent === 0) return 0;

        $delivered = SmsLog::where('template_slug', $templateSlug)->recent($days)->delivered()->count();
        return round(($delivered / $sent) * 100, 2);
    }
}

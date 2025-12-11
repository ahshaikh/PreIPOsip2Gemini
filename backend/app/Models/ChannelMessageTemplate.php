<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelMessageTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'template_key',
        'template_content',
        'variables',
        'subject',
        'is_active',
    ];

    protected $casts = [
        'channel_id' => 'integer',
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the communication channel
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(CommunicationChannel::class);
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for filtering by template key
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('template_key', $key);
    }

    /**
     * Scope for filtering by channel type
     */
    public function scopeByChannelType($query, string $channelType)
    {
        return $query->whereHas('channel', function ($q) use ($channelType) {
            $q->where('channel_type', $channelType);
        });
    }

    /**
     * Get template for specific channel and key
     */
    public static function getTemplate(string $channelType, string $templateKey): ?self
    {
        return self::byChannelType($channelType)
            ->byKey($templateKey)
            ->active()
            ->first();
    }

    /**
     * Render template with variables
     *
     * @param array $data Key-value pairs to replace {{variable}} placeholders
     * @return string
     */
    public function render(array $data): string
    {
        $content = $this->template_content;

        // Replace all {{variable}} placeholders
        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content = str_replace($placeholder, $value, $content);
        }

        // Remove any unreplaced placeholders (optional)
        $content = preg_replace('/\{\{[^}]+\}\}/', '', $content);

        return $content;
    }

    /**
     * Get list of required variables for this template
     */
    public function getRequiredVariables(): array
    {
        return $this->variables ?? [];
    }

    /**
     * Validate that all required variables are provided
     */
    public function validateVariables(array $data): bool
    {
        $required = $this->getRequiredVariables();

        foreach ($required as $variable) {
            if (!isset($data[$variable])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract variables from template content
     * Finds all {{variable}} patterns
     */
    public function extractVariablesFromContent(): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $this->template_content, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Common template keys used across channels
     */
    public static function getCommonTemplateKeys(): array
    {
        return [
            // Support tickets
            'ticket_created',
            'ticket_updated',
            'ticket_resolved',
            'ticket_closed',
            'ticket_assigned',

            // User actions
            'user_registered',
            'user_verified',
            'password_reset',

            // Transactions
            'payment_received',
            'payment_failed',
            'withdrawal_approved',
            'withdrawal_rejected',

            // KYC
            'kyc_submitted',
            'kyc_approved',
            'kyc_rejected',

            // General
            'welcome_message',
            'auto_reply',
            'out_of_hours',
        ];
    }

    /**
     * Format template data for admin management
     */
    public function toAdminFormat(): array
    {
        return [
            'id' => $this->id,
            'channelType' => $this->channel->channel_type,
            'channelName' => $this->channel->name,
            'templateKey' => $this->template_key,
            'subject' => $this->subject,
            'content' => $this->template_content,
            'variables' => $this->getRequiredVariables(),
            'extractedVariables' => $this->extractVariablesFromContent(),
            'isActive' => $this->is_active,
        ];
    }
}

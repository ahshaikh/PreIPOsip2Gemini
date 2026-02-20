<?php
/**
 * V-AUDIT-FIX-2026: Insufficient Inventory Exception
 *
 * Thrown when an allocation request exceeds available inventory.
 * This is a HARD BLOCK - allocations MUST NOT proceed.
 *
 * RESPONSE PROTOCOL:
 * 1. REJECT the allocation immediately
 * 2. Log to financial audit channel
 * 3. Return user-friendly error message
 * 4. NO inventory mutations may occur
 *
 * HTTP RESPONSE: 422 Unprocessable Entity
 * LOG CHANNEL: financial_contract (allocation events)
 *
 * V-AUDIT-FIX-2026-REFACTOR: Supports nullable Product for global inventory shortages.
 */

namespace App\Exceptions;

use Exception;
use App\Models\Product;

class InsufficientInventoryException extends Exception
{
    protected $code = 422;

    protected ?int $productId;
    protected ?string $productName;
    protected float $requested;
    protected float $available;
    protected string $allocationSource;
    protected bool $isGlobalShortage;

    public function __construct(
        ?Product $product,
        float $requested,
        float $available,
        string $allocationSource = 'unknown'
    ) {
        $this->productId = $product?->id;
        $this->productName = $product?->name;
        $this->requested = $requested;
        $this->available = $available;
        $this->allocationSource = $allocationSource;
        $this->isGlobalShortage = ($product === null);

        $shortfall = $requested - $available;

        if ($product !== null) {
            $message = "[INSUFFICIENT INVENTORY] Product #{$product->id} ({$product->name}): " .
                "Requested ₹{$requested}, Available ₹{$available}, Shortfall ₹{$shortfall}. " .
                "Source: {$allocationSource}";
        } else {
            $message = "[INSUFFICIENT GLOBAL INVENTORY] " .
                "Requested ₹{$requested}, Available ₹{$available}, Shortfall ₹{$shortfall}. " .
                "Source: {$allocationSource}";
        }

        parent::__construct($message);
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function isGlobalShortage(): bool
    {
        return $this->isGlobalShortage;
    }

    public function getRequested(): float
    {
        return $this->requested;
    }

    public function getAvailable(): float
    {
        return $this->available;
    }

    public function getShortfall(): float
    {
        return $this->requested - $this->available;
    }

    public function getAllocationSource(): string
    {
        return $this->allocationSource;
    }

    /**
     * Return structured context for audit logging.
     */
    public function reportContext(): array
    {
        return [
            'exception_type' => 'InsufficientInventoryException',
            'alert_level' => 'HIGH',
            'is_global_shortage' => $this->isGlobalShortage,
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'requested_amount' => $this->requested,
            'available_amount' => $this->available,
            'shortfall' => $this->getShortfall(),
            'allocation_source' => $this->allocationSource,
            'action_taken' => 'Allocation REJECTED - insufficient inventory',
            'financial_impact' => 'No inventory mutation occurred',
        ];
    }

    /**
     * Get user-friendly error message for API response.
     */
    public function getUserMessage(): string
    {
        return 'Insufficient inventory available for this product. Please try a smaller amount or contact support.';
    }

    /**
     * Static factory for consistent creation.
     */
    public static function forProduct(
        Product $product,
        float $requested,
        float $available,
        string $source = 'investment'
    ): self {
        return new self($product, $requested, $available, $source);
    }

    /**
     * V-AUDIT-FIX-2026: Static factory for global inventory shortage.
     * Used by legacy allocation when total platform inventory is insufficient.
     *
     * V-AUDIT-FIX-2026-REFACTOR: Now uses nullable Product instead of stub model.
     */
    public static function forGlobalInventory(
        float $requested,
        float $available,
        string $source = 'legacy_allocation'
    ): self {
        return new self(null, $requested, $available, $source);
    }
}

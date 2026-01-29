<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ProductStateService
{
    public function transitionTo(Product $product, ProductStatus $newStatus): Product
    {
        $this->ensureTransitionIsAllowed($product, $newStatus);
        $product->status = $newStatus;
        $product->save();

        return $product;
    }

    public function submit(Product $product): Product
    {
        return $this->transitionTo($product, ProductStatus::SUBMITTED);
    }

    public function approve(Product $product): Product
    {
        return $this->transitionTo($product, ProductStatus::APPROVED);
    }

    public function reject(Product $product): Product
    {
        return $this->transitionTo($product, ProductStatus::REJECTED);
    }

    public function lock(Product $product): Product
    {
        return $this->transitionTo($product, ProductStatus::LOCKED);
    }
    
    private function ensureTransitionIsAllowed(Product $product, ProductStatus $newStatus): void
    {
        $originalStatus = $product->status;

        if ($originalStatus === $newStatus) {
            return;
        }

        $allowedTransitions = [
            ProductStatus::DRAFT->value => [ProductStatus::SUBMITTED->value],
            ProductStatus::SUBMITTED->value => [ProductStatus::APPROVED->value, ProductStatus::REJECTED->value, ProductStatus::DRAFT->value],
            ProductStatus::APPROVED->value => [ProductStatus::LOCKED->value],
            ProductStatus::REJECTED->value => [ProductStatus::DRAFT->value],
            ProductStatus::LOCKED->value => [],
        ];

        if (!in_array($newStatus->value, $allowedTransitions[$originalStatus->value] ?? [])) {
            throw new \RuntimeException("Illegal state transition from '{$originalStatus->value}' to '{$newStatus->value}'.");
        }
    }
}

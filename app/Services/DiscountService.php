<?php

namespace App\Services;

use App\Models\Order;
use InvalidArgumentException;

class DiscountService
{
    public function validateDiscount(Order $order, string $type, float $value): array
    {
        $errors = [];
        
        if (!$order->canApplyDiscount()) {
            $errors[] = __('messages.discount_invalid_status');
        }
        
        if ($value <= 0) {
            $errors[] = __('messages.discount_validation_positive');
        }
        
        if ($type === 'fixed' && $value > $order->sum_price) {
            $errors[] = __('messages.discount_validation_exceeds_subtotal');
        }
        
        if ($type === 'percentage' && $value > 100) {
            $errors[] = __('messages.discount_validation_exceeds_100_percent');
        }
        
        return $errors;
    }
    
    public function calculateDiscountAmount(Order $order, string $type, float $value): float
    {
        if ($type === 'fixed') {
            return round($value, 2);
        }
        
        if ($type === 'percentage') {
            return round($order->sum_price * ($value / 100), 2);
        }
        
        return 0;
    }
    
    public function calculateNewTotal(Order $order, float $discountAmount): float
    {
        $discountedSubtotal = $order->sum_price - $discountAmount;
        
        $taxRate = $order->sum_price > 0 ? ($order->sum_price - ($order->sum_price - $order->discount_amount)) / $order->sum_price : 0;
        $newTax = round($discountedSubtotal * $taxRate, 2);
        
        return round($discountedSubtotal + $newTax, 2);
    }
    
    public function applyDiscount(Order $order, string $type, float $value, int $userId): Order
    {
        $errors = $this->validateDiscount($order, $type, $value);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(', ', $errors));
        }
        
        $discountAmount = $this->calculateDiscountAmount($order, $type, $value);
        $newTotal = $this->calculateNewTotal($order, $discountAmount);
        
        $order->update([
            'discount_type' => $type,
            'discount_value' => $value,
            'discount_amount' => $discountAmount,
            'discount_applied_by' => $userId,
            'discount_applied_at' => now(),
        ]);
        
        return $order->fresh();
    }
    
    public function removeDiscount(Order $order): Order
    {
        if (!$order->canApplyDiscount()) {
            throw new InvalidArgumentException('Cannot remove discount from this order status');
        }
        
        $order->update([
            'discount_type' => null,
            'discount_value' => null,
            'discount_amount' => null,
            'discount_applied_by' => null,
            'discount_applied_at' => null,
        ]);
        
        return $order->fresh();
    }
}

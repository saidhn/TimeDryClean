<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\DiscountService;
use App\Http\Requests\ApplyDiscountRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DiscountController extends Controller
{
    public function __construct(private DiscountService $discountService)
    {
        $this->middleware('auth');
    }

    public function apply(ApplyDiscountRequest $request, Order $order): JsonResponse
    {
        try {
            $updatedOrder = $this->discountService->applyDiscount(
                $order,
                $request->discount_type,
                $request->discount_value,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => __('messages.discount_applied'),
                'data' => [
                    'order' => $updatedOrder->load('discountAppliedBy')
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function remove(Order $order): JsonResponse
    {
        try {
            if (!$order->hasDiscount()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.discount_no_discount_found')
                ], 404);
            }

            $updatedOrder = $this->discountService->removeDiscount($order);

            return response()->json([
                'success' => true,
                'message' => __('messages.discount_removed'),
                'data' => [
                    'order' => $updatedOrder
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function validate(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0.01',
        ]);

        $errors = $this->discountService->validateDiscount(
            $order,
            $request->discount_type,
            $request->discount_value
        );

        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.discount_validation_failed'),
                'data' => [
                    'valid' => false,
                    'errors' => $errors
                ]
            ]);
        }

        $discountAmount = $this->discountService->calculateDiscountAmount(
            $order,
            $request->discount_type,
            $request->discount_value
        );

        $newTotal = $this->discountService->calculateNewTotal($order, $discountAmount);

        return response()->json([
            'success' => true,
            'message' => __('messages.discount_valid'),
            'data' => [
                'valid' => true,
                'discount_amount' => number_format($discountAmount, 2),
                'discounted_subtotal' => number_format($order->sum_price - $discountAmount, 2),
                'new_total' => number_format($newTotal, 2),
                'savings' => number_format($discountAmount, 2)
            ]
        ]);
    }
}

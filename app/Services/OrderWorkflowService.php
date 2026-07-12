<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderWorkflowService
{
    public function transition(Order $order, string $toStatus, string $actorType, int $actorId, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $toStatus, $actorType, $actorId, $note) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $locked->status;

            if (!OrderStatus::canTransition($fromStatus, $toStatus)) {
                throw new InvalidOrderTransitionException($fromStatus, $toStatus);
            }

            $locked->update(['status' => $toStatus]);

            if ($toStatus === OrderStatus::CANCELLED && $locked->is_paid && !$locked->refunded_at) {
                if ($locked->payment_method === 'points') {
                    User::adjustPoints($locked->user_id, (float) $locked->points_used);
                } else {
                    User::adjustBalance($locked->user_id, (float) $locked->sum_price);
                }
                $locked->update(['refunded_at' => now()]);
            }

            OrderStatusHistory::create([
                'order_id' => $locked->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by_type' => $actorType,
                'changed_by_id' => $actorId,
                'note' => $note,
            ]);

            return $locked;
        });
    }
}

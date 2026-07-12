<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Jobs\SendTransactionNotificationJob;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderWorkflowService
{
    public function transition(Order $order, string $toStatus, string $actorType, int $actorId, ?string $note = null): Order
    {
        // Captured inside the transaction below, then used after it commits to
        // queue the refund notification — a slow/hanging queue connection must
        // never hold this transaction's row lock open, mirroring the
        // post-DB::commit() dispatch pattern already used in OrdersController.
        $refundNotification = null;

        $locked = DB::transaction(function () use ($order, $toStatus, $actorType, $actorId, $note, &$refundNotification) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $locked->status;

            if (!OrderStatus::canTransition($fromStatus, $toStatus)) {
                throw new InvalidOrderTransitionException($fromStatus, $toStatus);
            }

            $locked->update(['status' => $toStatus]);

            if ($toStatus === OrderStatus::CANCELLED && $locked->is_paid && !$locked->refunded_at) {
                if ($locked->payment_method === 'points') {
                    $refundedUser = User::adjustPoints($locked->user_id, (float) $locked->points_used);
                    $refundBalance = $refundedUser->points_balance;
                } else {
                    $refundedUser = User::adjustBalance($locked->user_id, (float) $locked->sum_price);
                    $refundBalance = $refundedUser->balance;
                }
                $locked->update(['refunded_at' => now()]);

                $refundNotification = ['user_id' => $locked->user_id, 'balance' => $refundBalance];
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

        if ($refundNotification !== null) {
            SendTransactionNotificationJob::dispatch(
                $refundNotification['user_id'],
                'order_deleted_balance',
                ['balance' => $refundNotification['balance']]
            );
        }

        return $locked;
    }
}

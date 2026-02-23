<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'order_placed_balance',
                'description' => 'Sent when a new order is created (balance deducted)',
                'message_ar' => 'تم تأكيد طلبك بنجاح! رصيدك الجديد هو: :balance',
                'message_en' => 'Your order has been placed successfully! Your new balance is: :balance',
            ],
            [
                'key' => 'order_update_balance',
                'description' => 'Sent when an order is updated (balance adjusted)',
                'message_ar' => 'تم تحديث طلبك. رصيدك الجديد هو: :balance',
                'message_en' => 'Your order was updated. Your new balance is: :balance',
            ],
            [
                'key' => 'order_deleted_balance',
                'description' => 'Sent when an order is cancelled (balance refunded)',
                'message_ar' => 'تم إلغاء طلبك. تم استرداد الرصيد. الرصيد الجديد: :balance',
                'message_en' => 'Your order was cancelled. Your balance has been refunded. New balance: :balance',
            ],
            [
                'key' => 'subscription_balance_added',
                'description' => 'Sent when subscription benefit is added to balance',
                'message_ar' => 'تمت إضافة مكافأة الاشتراك! رصيدك الجديد: :balance',
                'message_en' => 'Subscription benefit added! Your new balance is: :balance',
            ],
            [
                'key' => 'driver_delivery_completed',
                'description' => 'Sent to driver when delivery is completed (fee added)',
                'message_ar' => 'تم التوصيل. تمت إضافة رسوم التوصيل :amount د.ك لرصيدك. الرصيد الجديد: :balance',
                'message_en' => 'Delivery completed. Delivery fee :amount KWD added to your balance. New balance: :balance',
            ],
            [
                'key' => 'payment_completed',
                'description' => 'Sent when a payment is successfully received (balance added)',
                'message_ar' => 'تم استلام دفعة بمبلغ :amount د.ك. رصيدك الجديد: :balance',
                'message_en' => 'Payment of :amount KWD received. Your new balance is: :balance',
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }
}

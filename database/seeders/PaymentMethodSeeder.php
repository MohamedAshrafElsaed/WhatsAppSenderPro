<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name_en' => 'Paymob Credit Card',
                'name_ar' => 'بطاقة ائتمان باي موب',
                'slug' => 'paymob_card',
                'gateway' => 'paymob',
                'is_active' => false, // Inactive until Paymob integration
                'config' => json_encode([
                    'type' => 'card',
                    'integration_id' => null,
                ]),
                'sort_order' => 1,
                'icon' => 'credit-card',
            ],
            [
                'name_en' => 'Paymob Mobile Wallet',
                'name_ar' => 'محفظة موبايل باي موب',
                'slug' => 'paymob_wallet',
                'gateway' => 'paymob',
                'is_active' => false, // Inactive until Paymob integration
                'config' => json_encode([
                    'type' => 'wallet',
                    'integration_id' => null,
                ]),
                'sort_order' => 2,
                'icon' => 'wallet',
            ],
            [
                'name_en' => 'Manual Bank Transfer',
                'name_ar' => 'تحويل بنكي يدوي',
                'slug' => 'manual_bank',
                'gateway' => 'manual',
                'is_active' => true, // Active for testing
                'config' => json_encode([
                    'bank_name' => 'Test Bank',
                    'account_number' => '1234567890',
                    'account_name' => 'ConvertedIn',
                    'instructions_en' => 'Please transfer to the account above and email proof of payment.',
                    'instructions_ar' => 'يرجى التحويل إلى الحساب أعلاه وإرسال إثبات الدفع عبر البريد الإلكتروني.',
                ]),
                'sort_order' => 3,
                'icon' => 'building-bank',
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::updateOrCreate(
                ['slug' => $method['slug']],
                $method
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name_en' => 'Basic Plan',
                'name_ar' => 'الخطة الأساسية',
                'slug' => 'basic',
                'price' => 49.00,
                'billing_cycle' => 'monthly',
                'features' => json_encode([
                    'messages_per_month' => 500,
                    'contacts_validation_per_month' => 500,
                    'connected_numbers' => 1,
                    'message_templates' => 1,
                    'media_sending' => false,
                    'group_import' => false,
                    'campaign_scheduling' => false,
                    'drip_campaigns' => false,
                    'api_access' => false,
                    'multi_user' => false,
                    'analytics_level' => 'basic',
                    'support_level' => 'standard',
                    'google_sheets' => false,
                    'contact_tagging' => false,
                    'inbox_view' => false,
                    'webhooks' => false,
                    'teams_roles' => false,
                ]),
                'limits' => json_encode([
                    'messages_per_month' => 500,
                    'contacts_validation_per_month' => 500,
                    'connected_numbers' => 1,
                    'message_templates' => 1,
                ]),
                'sort_order' => 1,
                'is_active' => true,
                'is_popular' => false,
                'is_best_value' => false,
                'color' => 'silver',
            ],
            [
                'name_en' => 'Pro Plan',
                'name_ar' => 'الخطة الاحترافية',
                'slug' => 'pro',
                'price' => 79.00,
                'billing_cycle' => 'monthly',
                'features' => json_encode([
                    'messages_per_month' => 5000,
                    'contacts_validation_per_month' => 5000,
                    'connected_numbers' => 2,
                    'message_templates' => 10,
                    'media_sending' => true,
                    'group_import' => true,
                    'campaign_scheduling' => true,
                    'drip_campaigns' => false,
                    'api_access' => false,
                    'multi_user' => false,
                    'analytics_level' => 'advanced',
                    'support_level' => 'priority',
                    'google_sheets' => true,
                    'contact_tagging' => true,
                    'inbox_view' => false,
                    'webhooks' => false,
                    'teams_roles' => false,
                ]),
                'limits' => json_encode([
                    'messages_per_month' => 5000,
                    'contacts_validation_per_month' => 5000,
                    'connected_numbers' => 2,
                    'message_templates' => 10,
                ]),
                'sort_order' => 2,
                'is_active' => true,
                'is_popular' => true,
                'is_best_value' => false,
                'color' => 'platinum',
            ],
            [
                'name_en' => 'Golden Plan',
                'name_ar' => 'الخطة الذهبية',
                'slug' => 'golden',
                'price' => 100.00,
                'billing_cycle' => 'monthly',
                'features' => json_encode([
                    'messages_per_month' => 'unlimited',
                    'contacts_validation_per_month' => 'unlimited',
                    'connected_numbers' => 5,
                    'message_templates' => 'unlimited',
                    'media_sending' => true,
                    'group_import' => true,
                    'campaign_scheduling' => true,
                    'drip_campaigns' => true,
                    'api_access' => true,
                    'multi_user' => true,
                    'analytics_level' => 'premium',
                    'support_level' => 'premium',
                    'google_sheets' => true,
                    'contact_tagging' => true,
                    'inbox_view' => true,
                    'webhooks' => true,
                    'teams_roles' => true,
                ]),
                'limits' => json_encode([
                    'messages_per_month' => 20000, // Cap at 20K for "unlimited"
                    'contacts_validation_per_month' => 'unlimited',
                    'connected_numbers' => 5,
                    'message_templates' => 'unlimited',
                ]),
                'sort_order' => 3,
                'is_active' => true,
                'is_popular' => false,
                'is_best_value' => true,
                'color' => 'gold',
            ],
        ];

        foreach ($packages as $package) {
            Package::updateOrCreate(
                ['slug' => $package['slug']],
                $package
            );
        }
    }
}

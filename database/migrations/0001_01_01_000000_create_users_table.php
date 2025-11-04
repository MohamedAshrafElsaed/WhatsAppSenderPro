<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Countries table (referenced by users)
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('iso_code', 2)->unique();
            $table->string('iso3_code', 3)->unique();
            $table->string('phone_code', 10);
            $table->string('name_en');
            $table->string('name_ar');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('iso_code');
            $table->index('is_active');
        });

        // Industries table (referenced by users)
        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name_en');
            $table->string('name_ar');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('slug');
            $table->index('is_active');
            $table->index('sort_order');
        });

        // Users table (optimized)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->unique();
            $table->string('mobile_number', 20);
            $table->foreignId('country_id')->constrained('countries');
            $table->foreignId('industry_id')->nullable()->constrained('industries');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('mobile_verified_at')->nullable();
            $table->string('password');
            $table->string('locale', 5)->default('en');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('first_name');
            $table->index('last_name');
            $table->index('mobile_number');
            $table->index('email');
            $table->index(['country_id', 'mobile_number']);
            $table->unique(['country_id', 'mobile_number']);
        });

        // User devices table (for device tracking with Cloudflare data)
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('device_id', 100)->nullable(); // Unique device identifier

            // Device information (use jenssegers/agent package)
            $table->string('device_type', 50)->nullable(); // desktop, tablet, mobile
            $table->string('platform', 50)->nullable(); // Windows, iOS, Android, etc.
            $table->string('platform_version', 50)->nullable();
            $table->string('browser', 50)->nullable(); // Chrome, Safari, Firefox
            $table->string('browser_version', 50)->nullable();
            $table->string('device_name')->nullable(); // iPhone 14 Pro, Galaxy S23, etc.
            $table->boolean('is_robot')->default(false);

            // Location data from Cloudflare headers
            $table->string('ip_address', 45);
            $table->string('country_code', 2)->nullable(); // CF-IPCountry
            $table->string('city', 100)->nullable(); // From CF headers or IP lookup
            $table->string('region', 100)->nullable(); // State/Province
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('timezone', 50)->nullable();
            $table->string('isp', 100)->nullable(); // Internet Service Provider
            $table->string('connection_type', 50)->nullable(); // mobile, broadband, etc.

            // Cloudflare specific
            $table->string('cf_ray', 50)->nullable(); // CF-Ray header
            $table->string('cf_connecting_ip', 45)->nullable();
            $table->boolean('cf_is_tor')->default(false);
            $table->string('cf_threat_score', 10)->nullable();

            // Session tracking
            $table->text('user_agent');
            $table->string('accept_language', 100)->nullable();
            $table->timestamp('last_seen_at');
            $table->boolean('is_trusted')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('user_id');
            $table->index('device_id');
            $table->index('device_type');
            $table->index('platform');
            $table->index('country_code');
            $table->index('city');
            $table->index('last_seen_at');
            $table->index(['user_id', 'device_id']);
            $table->index(['user_id', 'last_seen_at']);
        });

        // Password reset tokens (optimized)
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();

            $table->primary('email');
            $table->index('token');
        });

        // Sessions (optimized)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity');

            $table->index('user_id');
            $table->index('last_activity');
        });

        // Insert default data
        $this->seedDefaultData();
    }

    /**
     * Seed default countries and industries
     */
    private function seedDefaultData(): void
    {
        // Insert countries (focused on Middle East and common countries)
        $countries = [
            ['iso_code' => 'EG', 'iso3_code' => 'EGY', 'phone_code' => '20', 'name_en' => 'Egypt', 'name_ar' => 'مصر'],
            ['iso_code' => 'SA', 'iso3_code' => 'SAU', 'phone_code' => '966', 'name_en' => 'Saudi Arabia', 'name_ar' => 'السعودية'],
            ['iso_code' => 'AE', 'iso3_code' => 'ARE', 'phone_code' => '971', 'name_en' => 'United Arab Emirates', 'name_ar' => 'الإمارات'],
            ['iso_code' => 'KW', 'iso3_code' => 'KWT', 'phone_code' => '965', 'name_en' => 'Kuwait', 'name_ar' => 'الكويت'],
            ['iso_code' => 'QA', 'iso3_code' => 'QAT', 'phone_code' => '974', 'name_en' => 'Qatar', 'name_ar' => 'قطر'],
            ['iso_code' => 'BH', 'iso3_code' => 'BHR', 'phone_code' => '973', 'name_en' => 'Bahrain', 'name_ar' => 'البحرين'],
            ['iso_code' => 'OM', 'iso3_code' => 'OMN', 'phone_code' => '968', 'name_en' => 'Oman', 'name_ar' => 'عمان'],
            ['iso_code' => 'JO', 'iso3_code' => 'JOR', 'phone_code' => '962', 'name_en' => 'Jordan', 'name_ar' => 'الأردن'],
            ['iso_code' => 'LB', 'iso3_code' => 'LBN', 'phone_code' => '961', 'name_en' => 'Lebanon', 'name_ar' => 'لبنان'],
            ['iso_code' => 'IQ', 'iso3_code' => 'IRQ', 'phone_code' => '964', 'name_en' => 'Iraq', 'name_ar' => 'العراق'],
            ['iso_code' => 'SY', 'iso3_code' => 'SYR', 'phone_code' => '963', 'name_en' => 'Syria', 'name_ar' => 'سوريا'],
            ['iso_code' => 'PS', 'iso3_code' => 'PSE', 'phone_code' => '970', 'name_en' => 'Palestine', 'name_ar' => 'فلسطين'],
            ['iso_code' => 'YE', 'iso3_code' => 'YEM', 'phone_code' => '967', 'name_en' => 'Yemen', 'name_ar' => 'اليمن'],
            ['iso_code' => 'LY', 'iso3_code' => 'LBY', 'phone_code' => '218', 'name_en' => 'Libya', 'name_ar' => 'ليبيا'],
            ['iso_code' => 'TN', 'iso3_code' => 'TUN', 'phone_code' => '216', 'name_en' => 'Tunisia', 'name_ar' => 'تونس'],
            ['iso_code' => 'DZ', 'iso3_code' => 'DZA', 'phone_code' => '213', 'name_en' => 'Algeria', 'name_ar' => 'الجزائر'],
            ['iso_code' => 'MA', 'iso3_code' => 'MAR', 'phone_code' => '212', 'name_en' => 'Morocco', 'name_ar' => 'المغرب'],
            ['iso_code' => 'SD', 'iso3_code' => 'SDN', 'phone_code' => '249', 'name_en' => 'Sudan', 'name_ar' => 'السودان'],
            ['iso_code' => 'US', 'iso3_code' => 'USA', 'phone_code' => '1', 'name_en' => 'United States', 'name_ar' => 'الولايات المتحدة'],
            ['iso_code' => 'GB', 'iso3_code' => 'GBR', 'phone_code' => '44', 'name_en' => 'United Kingdom', 'name_ar' => 'المملكة المتحدة'],
            ['iso_code' => 'FR', 'iso3_code' => 'FRA', 'phone_code' => '33', 'name_en' => 'France', 'name_ar' => 'فرنسا'],
            ['iso_code' => 'DE', 'iso3_code' => 'DEU', 'phone_code' => '49', 'name_en' => 'Germany', 'name_ar' => 'ألمانيا'],
            ['iso_code' => 'IT', 'iso3_code' => 'ITA', 'phone_code' => '39', 'name_en' => 'Italy', 'name_ar' => 'إيطاليا'],
            ['iso_code' => 'ES', 'iso3_code' => 'ESP', 'phone_code' => '34', 'name_en' => 'Spain', 'name_ar' => 'إسبانيا'],
            ['iso_code' => 'TR', 'iso3_code' => 'TUR', 'phone_code' => '90', 'name_en' => 'Turkey', 'name_ar' => 'تركيا'],
            ['iso_code' => 'IN', 'iso3_code' => 'IND', 'phone_code' => '91', 'name_en' => 'India', 'name_ar' => 'الهند'],
            ['iso_code' => 'CN', 'iso3_code' => 'CHN', 'phone_code' => '86', 'name_en' => 'China', 'name_ar' => 'الصين'],
            ['iso_code' => 'JP', 'iso3_code' => 'JPN', 'phone_code' => '81', 'name_en' => 'Japan', 'name_ar' => 'اليابان'],
        ];

        foreach ($countries as &$country) {
            $country['created_at'] = now();
            $country['updated_at'] = now();
        }

        DB::table('countries')->insert($countries);

        // Insert industries
        $industries = [
            ['slug' => 'real-estate', 'name_en' => 'Real Estate', 'name_ar' => 'العقارات', 'sort_order' => 1],
            ['slug' => 'automotive', 'name_en' => 'Automotive', 'name_ar' => 'السيارات', 'sort_order' => 2],
            ['slug' => 'education', 'name_en' => 'Education', 'name_ar' => 'التعليم', 'sort_order' => 3],
            ['slug' => 'healthcare', 'name_en' => 'Healthcare', 'name_ar' => 'الرعاية الصحية', 'sort_order' => 4],
            ['slug' => 'retail', 'name_en' => 'Retail', 'name_ar' => 'التجزئة', 'sort_order' => 5],
            ['slug' => 'e-commerce', 'name_en' => 'E-commerce', 'name_ar' => 'التجارة الإلكترونية', 'sort_order' => 6],
            ['slug' => 'finance', 'name_en' => 'Finance & Banking', 'name_ar' => 'المالية والمصرفية', 'sort_order' => 7],
            ['slug' => 'insurance', 'name_en' => 'Insurance', 'name_ar' => 'التأمين', 'sort_order' => 8],
            ['slug' => 'technology', 'name_en' => 'Technology', 'name_ar' => 'التكنولوجيا', 'sort_order' => 9],
            ['slug' => 'telecommunications', 'name_en' => 'Telecommunications', 'name_ar' => 'الاتصالات', 'sort_order' => 10],
            ['slug' => 'hospitality', 'name_en' => 'Hospitality & Tourism', 'name_ar' => 'الضيافة والسياحة', 'sort_order' => 11],
            ['slug' => 'food-beverage', 'name_en' => 'Food & Beverage', 'name_ar' => 'الأغذية والمشروبات', 'sort_order' => 12],
            ['slug' => 'manufacturing', 'name_en' => 'Manufacturing', 'name_ar' => 'التصنيع', 'sort_order' => 13],
            ['slug' => 'construction', 'name_en' => 'Construction', 'name_ar' => 'البناء والتشييد', 'sort_order' => 14],
            ['slug' => 'energy', 'name_en' => 'Energy & Utilities', 'name_ar' => 'الطاقة والمرافق', 'sort_order' => 15],
            ['slug' => 'logistics', 'name_en' => 'Logistics & Transportation', 'name_ar' => 'اللوجستيات والنقل', 'sort_order' => 16],
            ['slug' => 'media', 'name_en' => 'Media & Entertainment', 'name_ar' => 'الإعلام والترفيه', 'sort_order' => 17],
            ['slug' => 'consulting', 'name_en' => 'Consulting & Services', 'name_ar' => 'الاستشارات والخدمات', 'sort_order' => 18],
            ['slug' => 'government', 'name_en' => 'Government', 'name_ar' => 'الحكومة', 'sort_order' => 19],
            ['slug' => 'non-profit', 'name_en' => 'Non-Profit', 'name_ar' => 'غير الربحية', 'sort_order' => 20],
            ['slug' => 'agriculture', 'name_en' => 'Agriculture', 'name_ar' => 'الزراعة', 'sort_order' => 21],
            ['slug' => 'pharmaceuticals', 'name_en' => 'Pharmaceuticals', 'name_ar' => 'الأدوية', 'sort_order' => 22],
            ['slug' => 'beauty-cosmetics', 'name_en' => 'Beauty & Cosmetics', 'name_ar' => 'الجمال ومستحضرات التجميل', 'sort_order' => 23],
            ['slug' => 'sports-fitness', 'name_en' => 'Sports & Fitness', 'name_ar' => 'الرياضة واللياقة البدنية', 'sort_order' => 24],
            ['slug' => 'other', 'name_en' => 'Other', 'name_ar' => 'أخرى', 'sort_order' => 100],
        ];

        foreach ($industries as &$industry) {
            $industry['created_at'] = now();
            $industry['updated_at'] = now();
        }

        DB::table('industries')->insert($industries);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('user_devices');
        Schema::dropIfExists('users');
        Schema::dropIfExists('industries');
        Schema::dropIfExists('countries');
    }
};

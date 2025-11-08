import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

// ==================== User & Authentication ====================

export interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    mobile_number: string;
    country_id: number;
    industry_id: number;
    locale: string;
    email_verified_at: string | null;
    mobile_verified_at: string | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;

    // Computed attributes (when available)
    full_name?: string;
    formatted_mobile?: string;
    subscription_status?: 'none' | 'active' | 'trial' | 'expired' | 'cancelled';

    // Relationships (optional - when loaded)
    country?: Country;
    industry?: Industry;
    devices?: UserDevice[];
    subscription?: UserSubscription;
    subscriptions?: UserSubscription[];
    transactions?: Transaction[];
    usage?: UserUsage;
}

export interface Auth {
    user: User;
}

// ==================== Location & Demographics ====================

export interface Country {
    id: number;
    iso_code: string;
    iso3_code: string;
    phone_code: string;
    name_en: string;
    name_ar: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;

    // Computed attribute (when using locale)
    name?: string;
}

export interface Industry {
    id: number;
    slug: string;
    name_en: string;
    name_ar: string;
    is_active: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;

    // Computed attribute (when using locale)
    name?: string;
}

// ==================== Subscriptions & Packages ====================

export interface Package {
    id: number;
    name_en: string;
    name_ar: string;
    slug: string;
    price: number;
    billing_cycle: string;
    features: Record<string, any>;
    limits: Record<string, any>;
    sort_order: number;
    is_active: boolean;
    is_popular: boolean;
    is_best_value: boolean;
    color: string;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;

    // Computed attributes
    name?: string;
    formatted_price?: string;
    is_current?: boolean;
}

export interface UserSubscription {
    id: number;
    user_id: number;
    package_id: number;
    status: 'active' | 'trial' | 'expired' | 'cancelled';
    trial_ends_at: string | null;
    starts_at: string;
    ends_at: string;
    cancelled_at: string | null;
    auto_renew: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;

    // Relationships (when loaded)
    user?: User;
    package?: Package;
}

export interface SubscriptionSummary {
    has_subscription: boolean;
    status: 'none' | 'active' | 'trial' | 'expired' | 'cancelled';
    package?: string;
    days_remaining?: number;
    ends_at?: string;
    is_trial?: boolean;
    usage?: {
        messages_sent: number;
        messages_limit: number | string;
        contacts_validated: number;
        contacts_limit: number | string;
    };
}

// ==================== Usage Tracking ====================

export interface UserUsage {
    id: number;
    user_id: number;
    period_start: string;
    period_end: string;
    messages_sent: number;
    contacts_validated: number;
    connected_numbers_count: number;
    templates_created: number;
    created_at: string;
    updated_at: string;

    // Relationships (when loaded)
    user?: User;
}

// ==================== Payments & Transactions ====================

export interface PaymentMethod {
    id: number;
    name_en: string;
    name_ar: string;
    slug: string;
    gateway: string;
    is_active: boolean;
    config: Record<string, any>;
    sort_order: number;
    icon: string | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;

    // Computed attribute (when using locale)
    name?: string;
}

export interface Transaction {
    id: number;
    user_id: number;
    package_id: number;
    payment_method_id: number | null;
    transaction_id: string;
    amount: number;
    currency: string;
    status: 'pending' | 'completed' | 'failed' | 'refunded';
    payment_gateway: string;
    gateway_response: Record<string, any> | null;
    paid_at: string | null;
    refunded_at: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;

    // Relationships (when loaded)
    user?: User;
    package?: Package;
    payment_method?: PaymentMethod;
}

// ==================== Device Management ====================

export interface UserDevice {
    id: number;
    user_id: number;
    device_id: string;
    device_type: 'mobile' | 'desktop' | 'tablet';
    platform: string;
    platform_version: string | null;
    browser: string;
    browser_version: string | null;
    device_name: string | null;
    is_robot: boolean;
    ip_address: string;
    country_code: string | null;
    city: string | null;
    region: string | null;
    postal_code: string | null;
    latitude: number | null;
    longitude: number | null;
    timezone: string | null;
    isp: string | null;
    connection_type: string | null;
    cf_ray: string | null;
    cf_connecting_ip: string | null;
    cf_is_tor: boolean;
    cf_threat_score: number | null;
    user_agent: string;
    accept_language: string | null;
    last_seen_at: string;
    is_trusted: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;

    // Relationships (when loaded)
    user?: User;
}

// ==================== Navigation & UI ====================

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type BreadcrumbItemType = BreadcrumbItem;

// ==================== Page Props ====================

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface WhatsAppSession {
    id: string;
    session_name: string;
    status: 'pending' | 'qr_ready' | 'connected' | 'disconnected' | 'failed';
    phone_number?: string;
    jid?: string;
    push_name?: string;
    platform?: string;
    connected_at?: string;
    disconnected_at?: string;
    last_seen?: string;
    device_info?: Record<string, any>;
    is_business_account?: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface WhatsAppSessionSummary {
    total_sessions: number;
    connected: number;
    pending: number;
    max_devices: number;
    available_slots: number | string;
}

export interface OnboardingData {
    tour_completed?: boolean;
    whatsapp_connected?: boolean;
    contacts_imported?: boolean;
    template_created?: boolean;
    campaign_sent?: boolean;
    completed_at?: string;
}

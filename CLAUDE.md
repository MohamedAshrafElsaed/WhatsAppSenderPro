# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WhatsApp Sender Pro is a Laravel 12 + Vue 3 + Inertia.js SaaS application for WhatsApp bulk messaging and contact management. The frontend uses Vue 3 with TypeScript, Tailwind CSS v4, and Reka UI components. The application integrates with an external WhatsApp Go API server for sending messages through WhatsApp sessions.

## Core Architecture

### Backend (Laravel)

**Route Structure**
Routes are organized into separate files in the `routes/` directory:
- `web.php` - Main router that imports all other route files
- `landing.php` - Public landing pages
- `dashboard.php` - All authenticated dashboard routes (contacts, campaigns, templates, reports, WhatsApp sessions)
- `settings.php` - User settings and profile management
- `subscription.php` - Subscription and package management

**Key Services**
- `WhatsAppApiService` - Communicates with external Go API server using JWT authentication. Handles session management, QR code generation, and message sending
- `CampaignService` - Campaign CRUD, recipient management, media uploads, queue priority based on subscription tier
- `ContactService` - Contact management and validation
- `ContactImportService` - Handles bulk CSV/Excel imports with background processing
- `SubscriptionService` - Subscription management, package features, and usage limits
- `UsageTrackingService` - Tracks user quota consumption (messages, contacts, templates)
- `JWTService` - Generates JWT tokens for WhatsApp API authentication (user ID as subject)
- `BulkMessageService` - Handles actual message sending to WhatsApp API with rate limiting

**Database Models**
Core entities:
- `User` - Has subscriptions, contacts, campaigns, templates, devices. Methods: `generateJWT()`, `canAccessFeature()`, `hasReachedLimit()`, `getRemainingQuota()`
- `UserSubscription` - Links users to packages with status (active, trial, expired, cancelled)
- `Package` - Defines subscription tiers with features and limits stored as JSON
- `Campaign` - Bulk messaging campaigns with status (draft, scheduled, running, paused, completed, failed)
- `CampaignRecipient` - Junction table tracking individual message delivery status
- `Contact` - User contacts with phone validation status
- `ContactImport` - Tracks CSV/Excel import jobs with progress
- `Template` - Reusable message templates with variable substitution
- `WhatsAppSession` - Tracks WhatsApp sessions from the Go API

**Background Jobs**
- `SendCampaignMessageJob` - Processes individual campaign messages with retry logic, rate limiting (30/min), and exponential backoff

**Middleware**
- `CheckSubscription` - Validates active subscription before accessing protected features
- `CheckFeatureAccess` - Validates specific feature access based on package
- `TrackUserDevice` - Tracks user devices for security
- `HandleInertiaRequests` - Shares global data to frontend (auth user, locale, subscription info)

### Frontend (Vue 3 + Inertia.js)

**Structure**
- `resources/js/pages/` - Inertia page components organized by feature (auth, campaigns, contacts, templates, reports, whatsapp)
- `resources/js/components/ui/` - Reusable UI components from Reka UI (shadcn-vue style)
- `resources/js/composables/` - Vue composables for shared logic
- `resources/js/layouts/` - Layout components (AppLayout, AuthLayout, LandingLayout)
- `resources/js/types/` - TypeScript type definitions
- `resources/js/utils/` - Utility functions

**Key Features**
- Uses Laravel Wayfinder for type-safe routing from backend to frontend
- RTL support (Arabic locale detection)
- Dark/light theme support via `useAppearance` composable
- Real-time updates using WebSocket connections to WhatsApp API for session events

### WhatsApp Integration

The application communicates with an external Go-based WhatsApp API server:
- Base URL configured via `WHATSAPP_API_URL` env variable (default: `http://localhost:8988`)
- Authentication using JWT tokens signed with `JWT_SECRET`
- Key endpoints: `/api/v1/sessions` (CRUD), `/api/v1/sessions/{id}/send` (messaging), `/api/v1/sessions/{id}/qr` (QR codes)
- WebSocket endpoint: `ws://[api-url]/api/v1/sessions/{id}/events` for real-time session status

## Common Commands

### Development

```bash
# Start all services (Laravel server, queue worker, Vite)
composer dev

# Start with SSR support
composer dev:ssr

# Frontend only
npm run dev

# Build for production
npm run build
npm run build:ssr
```

### Setup

```bash
# Initial setup (install deps, generate key, migrate, build)
composer setup

# Database
php artisan migrate
php artisan db:seed

# Generate IDE helper files
php artisan ide-helper:generate
php artisan ide-helper:meta
```

### Testing

```bash
# Run all tests
composer test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Run specific test method
php artisan test --filter test_example
```

### Code Quality

```bash
# PHP formatting (Laravel Pint)
./vendor/bin/pint

# JS/Vue linting and formatting
npm run lint
npm run format
npm run format:check
```

### Queue Management

```bash
# Start queue worker
php artisan queue:listen

# With retry limit
php artisan queue:listen --tries=3

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Logs

```bash
# Tail logs in terminal
php artisan pail

# Clear logs
php artisan pail --clear
```

## Key Configuration

### Environment Variables
- `WHATSAPP_API_URL` - External WhatsApp Go API server URL
- `JWT_SECRET` - Shared secret for WhatsApp API authentication (defaults to APP_KEY)
- `JWT_TTL` - JWT token expiration in seconds (default: 86400)
- Database: Uses SQLite by default, configure MySQL/PostgreSQL via `DB_*` variables
- Queue: Uses database driver by default, Redis recommended for production
- Cache: Uses database driver by default, Redis recommended for production

### Package System
Subscription packages are defined in the `packages` table with JSON columns:
- `features` - Array of feature slugs (e.g., "bulk_messaging", "advanced_analytics")
- `limits` - Object with keys like "messages_per_month", "contacts_validation_per_month", "connected_numbers", "message_templates"

Usage tracking is handled per billing period in the `user_usages` table.

## Development Notes

### Adding New Features

1. Check subscription requirements using `CheckFeatureAccess` middleware
2. Track usage with `UsageTrackingService` for limited features
3. Use policies for authorization (`ContactPolicy`, `CampaignPolicy`, `TemplatePolicy`)
4. Add proper validation in Form Request classes
5. Follow the service layer pattern for business logic

### Working with Campaigns

- Campaigns use Laravel queues for background processing
- Rate limiting: 30 messages per minute per user
- Queue priority based on subscription tier: golden (highest) > pro (high) > basic (low) > free (lowest)
- Media uploads stored in `storage/app/public/campaigns/`
- Support for text, image, video, audio, and document message types

### Contact Management

- Contacts can be imported via CSV/Excel using `ContactImportService`
- Phone number validation using `libphonenumber-js` on frontend and `giggsey/libphonenumber-for-php` on backend
- Contacts can be organized with tags (many-to-many relationship)
- Background validation against WhatsApp API to check if numbers exist

### Frontend Development

- Use Wayfinder route helpers instead of hardcoding URLs
- UI components follow shadcn-vue/Reka UI patterns
- TypeScript strict mode enabled
- Use composables for shared reactive state
- Inertia.js handles SPA navigation without separate API layer

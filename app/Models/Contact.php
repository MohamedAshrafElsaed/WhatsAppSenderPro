<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Country|null $country
 * @property-read string $full_name
 * @property-read ContactImport|null $import
 * @property-read Collection<int, ContactTag> $tags
 * @property-read int|null $tags_count
 * @property-read bool|null $tags_exists
 * @property-read User|null $user
 * @method static Builder<static>|Contact byCountry(?int $countryId)
 * @method static Builder<static>|Contact bySource(?string $source)
 * @method static Builder<static>|Contact forUser(User $user)
 * @method static Builder<static>|Contact invalidWhatsApp()
 * @method static Builder<static>|Contact newModelQuery()
 * @method static Builder<static>|Contact newQuery()
 * @method static Builder<static>|Contact onlyTrashed()
 * @method static Builder<static>|Contact query()
 * @method static Builder<static>|Contact search(?string $search)
 * @method static Builder<static>|Contact validWhatsApp()
 * @method static Builder<static>|Contact withTag(?int $tagId)
 * @method static Builder<static>|Contact withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Contact withoutTrashed()
 * @mixin Eloquent
 */
class Contact extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'country_id',
        'whatsapp_jid',
        'is_whatsapp_valid',
        'validated_at',
        'source',
        'import_id',
        'custom_fields',
        'notes',
        'last_message_at',
    ];

    protected $casts = [
        'is_whatsapp_valid' => 'boolean',
        'validated_at' => 'datetime',
        'last_message_at' => 'datetime',
        'custom_fields' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(ContactImport::class, 'import_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ContactTag::class, 'contact_tag')
            ->withTimestamps();
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    // Scopes
    public function scopeSearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('phone_number', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    public function scopeValidWhatsApp($query)
    {
        return $query->where('is_whatsapp_valid', true);
    }

    public function scopeInvalidWhatsApp($query)
    {
        return $query->where('is_whatsapp_valid', false);
    }

    public function scopeBySource($query, ?string $source)
    {
        if (!$source) {
            return $query;
        }

        return $query->where('source', $source);
    }

    public function scopeWithTag($query, ?int $tagId)
    {
        if (!$tagId) {
            return $query;
        }

        return $query->whereHas('tags', function ($q) use ($tagId) {
            $q->where('contact_tags.id', $tagId);
        });
    }

    public function scopeByCountry($query, ?int $countryId)
    {
        if (!$countryId) {
            return $query;
        }

        return $query->where('country_id', $countryId);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }
}

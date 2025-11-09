<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Contact> $contacts
 * @property-read int|null $contacts_count
 * @property-read bool|null $contacts_exists
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactTag forUser(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactTag onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactTag withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactTag withoutTrashed()
 * @mixin \Eloquent
 */
class ContactTag extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'color',
        'contacts_count',
    ];

    protected $casts = [
        'contacts_count' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_tag')
            ->withTimestamps();
    }

    // Scopes
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    // Methods
    public function updateContactsCount(): void
    {
        $this->update([
            'contacts_count' => $this->contacts()->count(),
        ]);
    }
}

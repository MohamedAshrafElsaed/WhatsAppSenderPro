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
 * @property-read Collection<int, Contact> $contacts
 * @property-read int|null $contacts_count
 * @property-read bool|null $contacts_exists
 * @property-read User|null $user
 * @method static Builder<static>|ContactTag forUser(User $user)
 * @method static Builder<static>|ContactTag newModelQuery()
 * @method static Builder<static>|ContactTag newQuery()
 * @method static Builder<static>|ContactTag onlyTrashed()
 * @method static Builder<static>|ContactTag query()
 * @method static Builder<static>|ContactTag withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|ContactTag withoutTrashed()
 * @mixin Eloquent
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

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    // Scopes

    public function updateContactsCount(): void
    {
        $this->update([
            'contacts_count' => $this->contacts()->count(),
        ]);
    }

    // Methods

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_tag')
            ->withTimestamps();
    }
}

<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Collection<int, Contact> $contacts
 * @property-read int|null $contacts_count
 * @property-read bool|null $contacts_exists
 * @property-read User|null $user
 * @method static Builder<static>|ContactImport completed()
 * @method static Builder<static>|ContactImport failed()
 * @method static Builder<static>|ContactImport forUser(User $user)
 * @method static Builder<static>|ContactImport newModelQuery()
 * @method static Builder<static>|ContactImport newQuery()
 * @method static Builder<static>|ContactImport onlyTrashed()
 * @method static Builder<static>|ContactImport processing()
 * @method static Builder<static>|ContactImport query()
 * @method static Builder<static>|ContactImport withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|ContactImport withoutTrashed()
 * @mixin Eloquent
 */
class ContactImport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'filename',
        'file_path',
        'file_type',
        'status',
        'total_rows',
        'valid_contacts',
        'invalid_contacts',
        'duplicate_contacts',
        'column_mapping',
        'validation_errors',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'column_mapping' => 'array',
        'validation_errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_rows' => 'integer',
        'valid_contacts' => 'integer',
        'invalid_contacts' => 'integer',
        'duplicate_contacts' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'import_id');
    }

    // Scopes
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    // Methods
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }
}

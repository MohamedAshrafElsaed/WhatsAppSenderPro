<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'template_id',
        'message_type',
        'message_content',
        'message_caption',
        'media_path',
        'media_url',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'total_recipients',
        'messages_sent',
        'messages_delivered',
        'messages_failed',
        'session_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_recipients' => 'integer',
        'messages_sent' => 'integer',
        'messages_delivered' => 'integer',
        'messages_failed' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'campaign_recipients')
            ->withPivot('status', 'sent_at', 'delivered_at', 'error_message', 'message_id')
            ->withTimestamps();
    }

    // Scopes
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeByStatus($query, ?string $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where('name', 'like', "%{$search}%");
        }
        return $query;
    }

    // Accessors
    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_recipients === 0) {
            return 0;
        }
        return (int)round(($this->messages_sent / $this->total_recipients) * 100);
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->messages_sent === 0) {
            return 0;
        }
        return round(($this->messages_delivered / $this->messages_sent) * 100, 2);
    }

    public function getFailureRateAttribute(): float
    {
        if ($this->messages_sent === 0) {
            return 0;
        }
        return round(($this->messages_failed / $this->messages_sent) * 100, 2);
    }

    public function getIsRunningAttribute(): bool
    {
        return in_array($this->status, ['running', 'scheduled']);
    }

    public function getCanBePausedAttribute(): bool
    {
        return $this->status === 'running';
    }

    public function getCanBeResumedAttribute(): bool
    {
        return $this->status === 'paused';
    }

    public function getCanBeEditedAttribute(): bool
    {
        return $this->status === 'draft';
    }

    public function getCanBeSentAttribute(): bool
    {
        return in_array($this->status, ['draft', 'scheduled', 'paused']);
    }

    // Methods
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'running',
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

    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
        ]);
    }

    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    public function resume(): void
    {
        $this->update(['status' => 'running']);
    }

    public function incrementSent(): void
    {
        $this->increment('messages_sent');
    }

    public function incrementDelivered(): void
    {
        $this->increment('messages_delivered');
    }

    public function incrementFailed(): void
    {
        $this->increment('messages_failed');
    }
}

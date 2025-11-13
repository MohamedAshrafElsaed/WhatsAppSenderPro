<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Template extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'content',
        'caption',
        'media_path',
        'media_url',
        'last_used_at',
        'usage_count',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'usage_count' => 'integer',
    ];

    // Relationships

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($template) {
            $template->deleteMedia();
        });
    }

    // Scopes

    public function deleteMedia(): void
    {
        if ($this->media_path && Storage::exists($this->media_path)) {
            Storage::delete($this->media_path);
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeByType($query, ?string $type)
    {
        if ($type) {
            return $query->where('type', $type);
        }
        return $query;
    }

    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }
        return $query;
    }

    // Accessors

    public function scopeRecentlyUsed($query)
    {
        return $query->whereNotNull('last_used_at')
            ->orderBy('last_used_at', 'desc');
    }

    public function scopeMostUsed($query)
    {
        return $query->where('usage_count', '>', 0)
            ->orderBy('usage_count', 'desc');
    }

    // Methods

    public function getContentPreviewAttribute(): string
    {
        return mb_substr($this->content, 0, 100) . (mb_strlen($this->content) > 100 ? '...' : '');
    }

    public function getHasMediaAttribute(): bool
    {
        return !is_null($this->media_path);
    }

    public function incrementUsage(): void
    {
        $this->update([
            'last_used_at' => now(),
            'usage_count' => $this->usage_count + 1,
        ]);
    }

    /**
     * Get sample preview with placeholder replacements
     */
    public function getSamplePreview(): string
    {
        return $this->mergePlaceholders([
            'first_name' => 'Ahmed',
            'last_name' => 'Mohamed',
            'phone' => '+201234567890',
            'custom_field_1' => 'Sample data',
            'custom_field_2' => 'Sample data',
            'custom_field_3' => 'Sample data',
        ]);
    }

    // Boot method

    /**
     * Replace placeholders with actual values
     */
    public function mergePlaceholders(array $data): string
    {
        $content = $this->content;

        $placeholders = [
            '{{first_name}}' => $data['first_name'] ?? '',
            '{{last_name}}' => $data['last_name'] ?? '',
            '{{phone}}' => $data['phone'] ?? '',
            '{{custom_field_1}}' => $data['custom_field_1'] ?? '',
            '{{custom_field_2}}' => $data['custom_field_2'] ?? '',
            '{{custom_field_3}}' => $data['custom_field_3'] ?? '',
        ];

        foreach ($placeholders as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }
}

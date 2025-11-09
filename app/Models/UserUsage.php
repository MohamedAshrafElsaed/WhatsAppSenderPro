<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property int $messages_sent
 * @property int $contacts_validated
 * @property int $connected_numbers_count
 * @property int $templates_created
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserUsage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserUsage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserUsage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserUsage whereConnectedNumbersCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserUsage whereContactsValidated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserUsage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserUsage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserUsage whereMessagesSent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserUsage wherePeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserUsage wherePeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserUsage whereTemplatesCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserUsage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserUsage whereUserId($value)
 * @mixin \Eloquent
 */
class UserUsage extends Model
{
    protected $table = 'user_usage';

    protected $fillable = [
        'user_id',
        'period_start',
        'period_end',
        'messages_sent',
        'contacts_validated',
        'connected_numbers_count',
        'templates_created',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'messages_sent' => 'integer',
        'contacts_validated' => 'integer',
        'connected_numbers_count' => 'integer',
        'templates_created' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Static Methods
    public static function getCurrentPeriodUsage(User $user): ?self
    {
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        return self::firstOrCreate(
            [
                'user_id' => $user->id,
                'period_start' => $periodStart,
            ],
            [
                'period_end' => $periodEnd,
                'messages_sent' => 0,
                'contacts_validated' => 0,
                'connected_numbers_count' => 0,
                'templates_created' => 0,
            ]
        );
    }

    public static function incrementMessagesSent(User $user, int $count = 1): void
    {
        $usage = self::getCurrentPeriodUsage($user);
        $usage->increment('messages_sent', $count);
    }

    public static function incrementContactsValidated(User $user, int $count = 1): void
    {
        $usage = self::getCurrentPeriodUsage($user);
        $usage->increment('contacts_validated', $count);
    }

    public static function incrementConnectedNumbers(User $user, int $count = 1): void
    {
        $usage = self::getCurrentPeriodUsage($user);
        $usage->increment('connected_numbers_count', $count);
    }

    public static function incrementTemplatesCreated(User $user, int $count = 1): void
    {
        $usage = self::getCurrentPeriodUsage($user);
        $usage->increment('templates_created', $count);
    }

    // Instance Methods
    public function resetUsage(): void
    {
        $this->update([
            'messages_sent' => 0,
            'contacts_validated' => 0,
            'connected_numbers_count' => 0,
            'templates_created' => 0,
        ]);
    }

    public function isInCurrentPeriod(): bool
    {
        $now = now();
        return $now->between($this->period_start, $this->period_end);
    }
}

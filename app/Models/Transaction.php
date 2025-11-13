<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $package_id
 * @property int|null $payment_method_id
 * @property string $transaction_id
 * @property numeric $amount
 * @property string $currency
 * @property string $status
 * @property string|null $payment_gateway
 * @property array<array-key, mixed>|null $gateway_response
 * @property Carbon|null $paid_at
 * @property Carbon|null $refunded_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Package $package
 * @property-read PaymentMethod|null $paymentMethod
 * @property-read User $user
 * @method static Builder<static>|Transaction completed()
 * @method static Builder<static>|Transaction failed()
 * @method static Builder<static>|Transaction newModelQuery()
 * @method static Builder<static>|Transaction newQuery()
 * @method static Builder<static>|Transaction onlyTrashed()
 * @method static Builder<static>|Transaction pending()
 * @method static Builder<static>|Transaction query()
 * @method static Builder<static>|Transaction refunded()
 * @method static Builder<static>|Transaction whereAmount($value)
 * @method static Builder<static>|Transaction whereCreatedAt($value)
 * @method static Builder<static>|Transaction whereCurrency($value)
 * @method static Builder<static>|Transaction whereDeletedAt($value)
 * @method static Builder<static>|Transaction whereGatewayResponse($value)
 * @method static Builder<static>|Transaction whereId($value)
 * @method static Builder<static>|Transaction whereNotes($value)
 * @method static Builder<static>|Transaction wherePackageId($value)
 * @method static Builder<static>|Transaction wherePaidAt($value)
 * @method static Builder<static>|Transaction wherePaymentGateway($value)
 * @method static Builder<static>|Transaction wherePaymentMethodId($value)
 * @method static Builder<static>|Transaction whereRefundedAt($value)
 * @method static Builder<static>|Transaction whereStatus($value)
 * @method static Builder<static>|Transaction whereTransactionId($value)
 * @method static Builder<static>|Transaction whereUpdatedAt($value)
 * @method static Builder<static>|Transaction whereUserId($value)
 * @method static Builder<static>|Transaction withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Transaction withoutTrashed()
 * @mixin Eloquent
 */
class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'package_id',
        'payment_method_id',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'payment_gateway',
        'gateway_response',
        'paid_at',
        'refunded_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    // Methods
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason,
        ]);
    }

    public function markAsRefunded(string $reason = null): void
    {
        $this->update([
            'status' => 'refunded',
            'refunded_at' => now(),
            'notes' => $reason,
        ]);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }
}

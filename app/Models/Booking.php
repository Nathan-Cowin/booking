<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'barber_id',
        'client_id',
        'start_time',
        'end_time',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'status' => BookingStatus::class,
        ];
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->using(BookingService::class);
    }

    public function isUpcoming(): bool
    {
        return in_array($this->status, [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::InProgress])
            && $this->start_time->isFuture();
    }

    public function isPast(): bool
    {
        return $this->status === BookingStatus::Completed
            || ($this->start_time->isPast() && !in_array($this->status, [BookingStatus::Cancelled, BookingStatus::NoShow]));
    }

    public function isCancelled(): bool
    {
        return in_array($this->status, [BookingStatus::Cancelled, BookingStatus::NoShow]);
    }
}

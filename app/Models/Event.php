<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $description
 * @property string $venue
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $ticket_types_min_price
 */
#[Fillable(['user_id', 'title', 'description', 'venue', 'starts_at', 'ends_at'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * このイベントを作ったユーザー。
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * このイベントの券種。
     *
     * @return HasMany<TicketType, $this>
     */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    /**
     * 一番安い券種の価格を「1,500円〜」の形で返す。0 円なら「無料」、券種が無ければ「─」。
     *
     * `withMin('ticketTypes', 'price')` で読み込んだ前提。
     */
    public function priceLabel(): string
    {
        if ($this->ticket_types_min_price === null) {
            return '─';
        }

        $lowestPrice = (int) $this->ticket_types_min_price;

        return $lowestPrice === 0 ? '無料' : number_format($lowestPrice).'円〜';
    }

    /**
     * ログイン中のユーザーが作ったイベントか。
     */
    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->user_id === $user->id;
    }
}

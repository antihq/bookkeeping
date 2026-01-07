<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'team_id',
        'created_by',
        'date',
        'payee',
        'note',
        'amount',
        'category_id',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => match (true) {
                ! $this->account => '$' . number_format(abs($this->amount) / 100, 2),
                default => match (true) {
                    ! $this->account->currency => '$' . number_format(abs($this->amount) / 100, 2),
                    default => match (true) {
                        $currency = Currency::where('iso', $this->account->currency)->first() => $currency->symbol . number_format(abs($this->amount) / 100, 2),
                        default => '$' . number_format(abs($this->amount) / 100, 2),
                    },
                },
            },
        );
    }

    protected function displayAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->amount >= 0 ? '+' : '-') . $this->formatted_amount,
        );
    }

    protected function displayDate(): Attribute
    {
        return Attribute::make(
            get: function () {
                $date = Carbon::parse($this->date);

                if ($date->isToday()) {
                    return 'Today';
                }

                if ($date->isYesterday()) {
                    return 'Yesterday';
                }

                return $date->format('d M Y');
            },
        );
    }
}

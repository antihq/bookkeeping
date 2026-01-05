<?php

namespace App\Models;

use Carbon\Carbon;
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

    public function getFormattedAmountAttribute(): string
    {
        $currency = Currency::all()->firstWhere('iso', $this->account->currency);
        $symbol = $currency ? $currency->symbol : '$';
        $value = number_format(abs($this->amount) / 100, 2);

        return "{$symbol}{$value}";
    }

    public function getDisplayAmountAttribute(): string
    {
        $prefix = $this->amount >= 0 ? '+' : '-';

        return "{$prefix}{$this->formatted_amount}";
    }

    public function getDisplayDateAttribute(): string
    {
        $date = Carbon::parse($this->date);

        if ($date->isToday()) {
            return 'Today';
        }

        if ($date->isYesterday()) {
            return 'Yesterday';
        }

        return $date->format('d M Y');
    }
}

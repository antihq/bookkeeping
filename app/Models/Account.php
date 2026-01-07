<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'created_by',
        'type',
        'name',
        'currency',
        'start_balance',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class)->orderBy('date', 'desc')->latest();
    }

    public function getFormattedBalanceAttribute(): string
    {
        $currency = Currency::all()->firstWhere('iso', $this->currency);
        $symbol = $currency ? $currency->symbol : '$';
        $balance = $this->start_balance + $this->transactions()->sum('amount');
        $value = number_format($balance / 100, 2);

        return "{$symbol}{$value}";
    }

    public function getDisplayTypeAttribute(): string
    {
        return str($this->type)->title()->toString();
    }

    public function getBalanceInDollarsAttribute(): float
    {
        return ($this->start_balance + $this->transactions()->sum('amount')) / 100;
    }

    public function getStartBalanceInDollarsAttribute(): float
    {
        return $this->start_balance / 100;
    }

    public function setStartBalanceInDollarsAttribute(float $value): void
    {
        $this->attributes['start_balance'] = (int) round($value * 100);
    }

    public function addTransaction(string $date, string $payee, int $amount, ?string $note = null, ?int $createdBy = null, ?int $categoryId = null): Transaction
    {
        return $this->transactions()->create([
            'date' => $date,
            'payee' => $payee,
            'amount' => $amount,
            'note' => $note,
            'team_id' => $this->team_id,
            'created_by' => $createdBy ?? Auth::id(),
            'category_id' => $categoryId,
        ]);
    }

    public function delete(): bool
    {
        $this->transactions()->delete();

        return parent::delete();
    }

    protected function setTypeAttribute(string $value): void
    {
        $this->attributes['type'] = strtolower($value);
    }

    protected function setCurrencyAttribute(string $value): void
    {
        $this->attributes['currency'] = strtolower($value);
    }
}

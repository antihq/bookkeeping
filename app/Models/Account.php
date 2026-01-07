<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function addTransaction(array $input, User $createdBy, ?Category $category = null): Transaction
    {
        return $this->transactions()->create([
            'date' => $input['date'],
            'payee' => $input['payee'],
            'amount' => $input['amount'],
            'note' => $input['note'],
            'team_id' => $this->team_id,
            'created_by' => $createdBy->id,
            'category_id' => $category?->id,
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

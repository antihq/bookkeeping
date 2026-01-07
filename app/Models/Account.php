<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    public function currency()
    {
        return Currency::where('iso', $this->attributes['currency'] ?? null)->first();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class)->orderBy('date', 'desc')->latest();
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

    protected function formattedBalance(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->currencySymbol . number_format(($this->start_balance + $this->transactions()->sum('amount')) / 100, 2),
        );
    }

    protected function currencySymbol(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->currency?->symbol ?? '$',
        );
    }

    protected function displayType(): Attribute
    {
        return Attribute::make(
            get: fn () => str($this->type)->title()->toString(),
        );
    }

    protected function balanceInDollars(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->start_balance + $this->transactions()->sum('amount')) / 100,
        );
    }

    protected function startBalanceInDollars(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->start_balance / 100,
            set: fn (float $value) => (int) round($value * 100),
        );
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower($value),
        );
    }
}

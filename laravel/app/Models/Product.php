<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Currency;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'currency_id',
        'tax_cost',
        'manufacturing_cost',
    ];
    protected $hidden = ['created_at', 'updated_at'];
    // Relaciones
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }
}
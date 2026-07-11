<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'rating',
        'comment',
        'reviewed_at',
        'reviewer_name',
        'reviewer_email',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

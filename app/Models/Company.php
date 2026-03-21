<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'google_contact_id',
        'ruc',
        'business_name',
        'trade_name',
        'address',
        'mobile',
        'category',
        'creation_date',
    ];

    /**
     * Get the Google Contact associated with the company.
     */
    public function googleContact()
    {
        return $this->belongsTo(GoogleContact::class, 'google_contact_id');
    }
}

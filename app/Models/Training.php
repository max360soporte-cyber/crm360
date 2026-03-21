<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Training extends Model
{
    use HasFactory;

    protected $fillable = [
        'google_contact_id',
        'type',
        'title',
        'description',
        'scheduled_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
    ];

    /**
     * Get the contact this training belongs to.
     */
    public function contact()
    {
        return $this->belongsTo(GoogleContact::class, 'google_contact_id');
    }
}

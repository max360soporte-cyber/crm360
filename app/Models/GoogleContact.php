<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GoogleContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'google_id',
        'name',
        'notes',
        'phone_number',
        'photo_url',
        'etag',
        'synced_at',
    ];

    /**
     * Get the user that owns the contact (the CRM user).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the trainings scheduled for this contact.
     */
    public function trainings()
    {
        return $this->hasMany(Training::class);
    }

    /**
     * Get the company details associated with the contact.
     */
    public function companyDetails()
    {
        return $this->hasOne(Company::class, 'google_contact_id');
    }
}

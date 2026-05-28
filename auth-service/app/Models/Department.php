<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'description'];

    public function users()
    {
        return $this->hasManyThrough(
            User::class,
            UserProfile::class,
            'department_id', // Foreign key on user_profiles table
            'id',            // Foreign key on users table
            'id',            // Local key on departments table
            'user_id'        // Local key on user_profiles table
        );
    }
}

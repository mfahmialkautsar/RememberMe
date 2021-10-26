<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'name', 'type', 'is_following', 'picture_url'
    ];

    protected $primaryKey = 'id';

    public function notes()
    {
        return $this->hasMany('Note');
    }
}

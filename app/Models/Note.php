<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'content', 'owner', 'source'
    ];

    protected $primaryKey = 'id';

    public function owner()
    {
        return $this->belongsTo(User::class);
    }
    public function source()
    {
        return $this->belongsTo(User::class);
    }
}

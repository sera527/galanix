<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Parsing extends Model
{
    public function articles()
    {
        return $this->hasMany('App\Article');
    }
}

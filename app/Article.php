<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    public function markers()
    {
        return $this->belongsToMany('App\Marker');
    }
}

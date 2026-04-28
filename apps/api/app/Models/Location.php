<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['postal_code', 'place_name', 'province', 'country_code'];
}

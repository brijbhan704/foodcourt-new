<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RestaurantMenu extends Model
{
    protected $table = 'restaurant_menus';
    protected $fillable = ([

    	'res_id',
    	'item_name',
    	'price',
    	'item_image'
    ]);
}

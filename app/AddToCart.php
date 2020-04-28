<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AddToCart extends Model
{
    protected $table = 'add_to_cart';
    protected $fillable = ([

    	'item_id',
    	'quantity',
    	'price_per_item',
    	'restaurant_id'
    ]);
}

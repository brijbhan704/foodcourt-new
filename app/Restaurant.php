<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $table = 'restaurants';
    protected $fillable =[
     'res_name',
     'res_address',
     'res_logo',
     'res_rating'

		];

		/*public function setTotal($cart_data[]) {
			print_r($cart_data);die;
				$cart = [];
         		foreach ( $cart_data as $cart) {
         			print_r($cart['quantity']);die;
				    $cart = [
				    	'phone' 			=> $request->phone,
				        'item_id' 			=> $cart['item_id'],
				        'quantity' 			=> $cart['quantity'],
				        'price_per_item' 	=> $cart['price_per_item'],
				        'restaurant_id' 	=> $cart['restaurant_id']	        
				    ];
				}
		
    	$this->total = $this->price_per_item * $this->quantity;
}*/
}

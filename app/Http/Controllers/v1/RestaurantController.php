<?php

namespace App\Http\Controllers\v1;

use Illuminate\Support\Facades\Validator;
use App\RestaurantMenu;
use App\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\AddToCart;
use App\OrderDetail;
use App\Comment;


class RestaurantController extends Controller
{
        public function restaurant(Request $request)//Get Restaurant List
        	{               
    	       try{
    	       		
    	          	$fetchRes = Restaurant::all();
    				return response(['status' => 1, 'message' => 'All Restaurant Listed', 'data' => $fetchRes]);
    	        	}catch(Exception $e){
    	      		return response()->json(['status'=>0,'message'=>'Exception Error','data'=>json_decode("{}")]);
    	        	}
        	}


        public function getMenuListByID(Request $request)//Get Restaurant Menu List
    	    {
    		try{
	    		$res_id = $request->restaurant_id;
	    		if(!isset($request->restaurant_id)){
                return response()->json(['status'=>$status,'message'=>'Please provide Restaurant ID','data'=>json_decode('{}')]);
            	}
            	$resName = Restaurant::where('id',$res_id)->get();
	    		$data = RestaurantMenu::where('res_id',$res_id)->get();
	    		return response()->json(['status'=>1,'message'=>'All Menu List According to Restaurant','RestaurntDetails'=>$resName,'RestaurantMenus'=>$data]);
		    	}catch(Exception $e){
		    	return response()->json(['status'=>0,'message'=>'Exception Error','data'=>json_decode("{}")]);
		    	}
	        }

        				//$gst = ($total_price * 0.18);
        			    //$totalAmt_withGST = $total_price + ($total_price * 0.18); 

	       public function AddtoCart(Request $request)//Add To Cart
          {
          try{
          $validator = Validator::make($request->all(), [            
              'phone'       =>'required|string|max:10',
              'cart_data'     =>'required'
            ]);
          if($validator->fails()){
          return response()->json(["status"=>0,"responseCode"=>"NP997","message"=>"invalid input details","data"=>json_decode("{}")]);
              }
          $phone = $request->phone;
          $cart_data = $request->cart_data;
          $grand_amt=0;
          $grand_qty=0;
          //print_r($cart_data);die;
          $cart = [];
        foreach ($cart_data as $cart) {
          $quantity       =        $cart['quantity'];
          $price          =        $cart['price_per_item'];
          $item_id        =        $cart['item_id'];
          $restaurant_id  =        $cart['restaurant_id'];
          $grand_qty      =        $grand_qty + $quantity;
          $total_amt      =        $quantity * $price;
          $grand_amt      =        $grand_amt + $total_amt;           
          }
          $delivery_charge = 0;
          $cart_item = [];
          $isUpdate=0;
        foreach ($cart_data as $cart_item) {
          $quantity       =        $cart_item['quantity'];
          $price          =        $cart_item['price_per_item'];
          $item_id        =        $cart_item['item_id'];
          $restaurant_id  =        $cart_item['restaurant_id'];
          $order_id = DB::table('order_details')->where('phone', $phone)->value('id');
          $check_all = DB::table('add_to_cart')
          ->where('order_id', $order_id)
          ->where('item_id',$item_id)
          ->where('restaurant_id',$restaurant_id)
          ->value('item_id'); 
          if(!empty($check_all)){
            $total_amount      =        $quantity * $price;
            $gst = ($total_amount * 0.18);
            $total_amt_with_gst = $total_amount + $gst;
            $update = DB::update("UPDATE order_details SET total_price = total_price + $total_amount  WHERE phone = $phone");
            $update = DB::update("UPDATE order_details SET total_price_with_gst =total_price_with_gst + $total_amt_with_gst, gst = gst + $gst WHERE phone = $phone");
            
            $id = OrderDetail::where('phone',$phone)->value('id');
            $check_item =DB::update("UPDATE add_to_cart SET quantity = quantity + $quantity  WHERE item_id = $item_id and restaurant_id=$restaurant_id and order_id=$id");       
            }else{
            if(empty($order_id))
            {
            $total_amount      =        $quantity * $price;
            $gst = ($total_amount * 0.18);
            $total_amt_with_gst = $total_amount + $gst; 

             $data = OrderDetail::insertGetId ([ 
            'phone'                             =>$phone,
            'total_qty'                         =>$grand_qty,
            'total_price'                       =>$total_amount,       
            'total_price_with_gst'              =>$total_amt_with_gst,
            'gst'                               =>$gst,
            'delivery_charge'                   =>$delivery_charge
             ]);
            }
            // echo $total_amount;die;
            if(!empty($order_id))
                {
            $total_amount      =        $quantity * $price;
            $gst = ($total_amount * 0.18);
            $total_amt_with_gst = $total_amount + $gst;
           //echo $total_amount;die;
            $update1 = DB::update("UPDATE order_details SET total_price =total_price + $total_amount  WHERE phone = $phone");
            $update = DB::update("UPDATE order_details SET total_price_with_gst = total_price_with_gst + $total_amt_with_gst, gst = gst + $gst WHERE phone = $phone");

            $data            =  $order_id ;
            }
            $cart_item = [
            'order_id'               =>  $data,
            'item_id'               =>  $cart_item['item_id'],
            'quantity'              =>  $cart_item['quantity'],
            'price_per_item'        =>  $cart_item['price_per_item'],
            'restaurant_id'         =>  $cart_item['restaurant_id']
              ];
              $check_alls = DB::table('add_to_cart')
            ->where('order_id', $data )
            ->where('item_id',$item_id)
            ->where('restaurant_id',$restaurant_id)
            ->value('item_id');  
            if(empty($check_alls))
            { 
            
            $addItemInCart = AddToCart::insert($cart_item);
            
            $cart_item = [];
           }
         }
       }
          return response()->json(['status'=>'1',"responseCode"=>"TC001",'message'=>'Add to Cart Successfully','data'=>""]);
          }catch(Exception $e){
          return response()->json(['status'=>0,'message'=>'Exception Error','data'=>json_decode("{}")]);
          }
       }


	    public function viewCart(Request $request)      //Start View Cart
	    	{
	    	try {
    		    $phone = $request->phone;
            $check = DB::table('order_details')
            ->where('phone',$phone)
            ->value('phone');
            //echo $check;die;
            if($check==false){
              return response()->json(['status' =>0, 'message'=>'Mobile No does not exists ', 'data'=>""]);

            }else{
    		    $totaldata = DB::table('order_details')->select('phone','total_price','total_price_with_gst','gst')->where('phone', $phone)->get();
                $data = DB::table('add_to_cart')
                ->join('order_details', 'add_to_cart.order_id', '=', 'order_details.id')
                ->join('restaurant_menus','restaurant_menus.id','=','add_to_cart.item_id')
                ->select('add_to_cart.restaurant_id','add_to_cart.item_id','restaurant_menus.item_name','add_to_cart.quantity as total_qty','add_to_cart.price_per_item','restaurant_menus.item_image')
                ->where('order_details.phone','=',$phone)
                ->get();
                }
			    return response()->json(['status' =>1, 'message'=>'Item List Fetched Successfully ','totaldata'=> $totaldata, 'data'=>$data]);
	    	    } catch (Exception $e) {
	    	    return response()->json(['status'=>0 , 'message'=>'Exception Error','data'=>json_decode("{}")]);      
	    	   }
	        }
	    
	 
        public function updateCart(Request $request)   //Update Cart 
            {     
            try{    
            	//	echo 1;die;
            	$phone 			         = $request->phone;
            	$item_id 		         = $request->item_id;
            	$restaurant_id 	     = $request->restaurant_id;
            	$quantity 		       = $request->quantity;  	
            	$price 			         = $request->price;
			        $action              = $request->get('action'); 		           
                switch ($action)
                {
               case '1'://cart plus
              $order_id = DB::table('order_details')->where('phone',$phone)->pluck('id')->first();
         //echo $order_id;die;
			         $results = DB::table('add_to_cart')->where('restaurant_id','=',$restaurant_id)
              ->where('order_id','=',$order_id)
              ->where('item_id','=',$item_id)
              ->update([
             	'item_id'   	=>$item_id,
             	'quantity'		=>$quantity,
             	'price_per_item'=>$price
              ]);
              //$payble = DB::select('select (quantity*price_per_item) as total_price from add_to_cart where order_id=26'); 
              $payble = DB::update("UPDATE add_to_cart SET PayableAmount = quantity * price_per_item where order_id = $order_id");
              //$pay = DB::select("SELECT sum(PayableAmount) as total from add_to_cart where order_id=$order_id");
               $total_price = DB::table('add_to_cart')
              ->where('order_id', '=', $order_id)
              ->sum('PayableAmount');
              $gst = ($total_price * 0.18);
              $total_amt_with_gst = $total_price + $gst;

              //print_r($gst);die;
              $update_total_amt = DB::update("UPDATE order_details SET total_qty =  $quantity, total_price =  $total_price , total_price_with_gst =  $total_amt_with_gst , gst =  $gst WHERE phone = $phone"); 
              $totaldata = DB::table('order_details')->select('phone','total_price','total_price_with_gst','gst')->where('phone', $phone)->get();
           	  return response()->json(['status' =>1, 'message'=>'Item List Fetched Successfully ','data'=>$totaldata]);
              break;

              
                   
    
              case '2': //cart minus
              if($quantity == 0){
              $order_id = DB::table('order_details')->where('phone',$phone)->pluck('id')->first();
              $delete_data = DB::table('add_to_cart')
              ->where('order_id',$order_id)
                ->where('item_id','=',$item_id)
              ->delete();
                $payble = DB::update("UPDATE add_to_cart SET PayableAmount = quantity * price_per_item where order_id = $order_id");

                $total_price = DB::table('add_to_cart')
                ->where('order_id', '=', $order_id)
                ->sum('PayableAmount');
                // print_r($total_price);die;
                $gst = ($total_price * 0.18);
                $total_amt_with_gst = $total_price + $gst;

                // print_r($total_amt_with_gst);die;
                $update_total_amt = DB::update("UPDATE order_details SET total_qty =  $quantity, total_price =  $total_price , total_price_with_gst =  $total_amt_with_gst , gst =  $gst WHERE phone = $phone");
                $totaldata = DB::table('order_details')->select('phone','total_price','total_price_with_gst','gst')->where('phone', $phone)->get();
           
                //print_r ($totaldata);die;
              $to_price = $totaldata[0]->total_price;
                if($to_price==0){

                return response()->json(['status' =>0, 'message'=>'No Item','data'=>$totaldata]);

                }else{
               
             
                return response()->json(['status' =>1, 'message'=>'Delete Successfully ','data'=>$totaldata]);
                }
              
                }else{

              //$update_total_amt = DB::update("UPDATE order_details SET  total_qty = total_qty - $quantity,total_price = total_price - $total_amt , total_price_with_gst = total_price_with_gst - $total_amt_with_gst , gst = gst - $gst WHERE phone = $phone");
            $order_id = DB::table('order_details')->where('phone',$phone)->pluck('id')->first();
            $results = DB::table('add_to_cart')
              ->where('order_id','=',$order_id)
              ->where('restaurant_id','=',$restaurant_id)
              ->where('item_id','=',$item_id)
              ->update([
              'item_id'        =>$item_id,
              'quantity'       =>$quantity,
              'price_per_item' =>$price
                ]);
                //echo $price;die;  
//echo  $results;die;
              $payble = DB::update("UPDATE add_to_cart SET PayableAmount = quantity * price_per_item where order_id = $order_id");
             
              $total_price = DB::table('add_to_cart')
              ->where('order_id', '=', $order_id)
              ->sum('PayableAmount');
              // print_r($total_price);die;
              $gst = ($total_price * 0.18);
              $total_amt_with_gst = $total_price + $gst;

              //print_r($total_amt_with_gst);die;
              $update_total_amt = DB::update("UPDATE order_details SET total_qty =  $quantity, total_price =  $total_price , total_price_with_gst =  $total_amt_with_gst , gst =  $gst WHERE phone = $phone");
               $totaldata = DB::table('order_details')->select('phone','total_price','total_price_with_gst','gst')->where('phone', $phone)->get();
            return response()->json(['status' =>1, 'message'=>'Item List Fetched Successfully ','data'=>$totaldata]);
              break;
              }
            }      
          }catch(Exception $e){            
            return response()->json(['status'=>0,'message'=>'Quantity Updated Error','data'=>json_decode("{}")]);
          }
        }
    		
    
		public function placeOrder(Request $request)        //place order
        {
      try{
            $table_id = $request->table_id;
            $phone     = $request->phone;
            $name      = $request->name;
            if($request->status == 'ok'){
              $order_number = DB::table('place_order_details')->select('order_number')
                  ->orderBy('order_number', 'desc')
                  ->get();
                  if(sizeof($order_number) == 0){
                  $order_number=55001;        
              }else{          
              $order_number= $order_number[0]->order_number+1;
              }
              $order_id= DB::table('order_details')
              ->where('phone',$phone)
              ->value('id');
              //echo $order_id;die;
              $place_order_details = DB::table('place_order_details')->insert([
                'name'                  =>$name,
                'phone'                 =>$phone,
                'table_id'              =>$table_id,
                'order_number'          =>$order_number,
                'order_id'              =>$order_id
              ]);
              return response()->json(['status'=>1, 'message'=>'Place Order Successfully', 'order_number'=>$order_number]);

            }else{
              return response()->json(['status'=>0, 'message'=>'Place Order Failed', 'order_number'=>json_decode("{}")]);
 
            }
            
              }catch(Exception $e){
            return response()->json(['status'=>0,'message'=>'Quantity Updated Error','data'=>json_decode("{}")]);
          }
        }
		
			
	     public function comment(Request $request)       //user  comments
          {
        try{
        //echo 1;die;
          $data = DB::table('comments')->insert([
          'res_id'        =>$request->res_id,
          'phone'         =>$request->phone,
          'comment'       =>$request->comment,
          'name'          =>$request->name,
          'rating'        =>$request->rating,
          ]);
          if($data==true){
          return response()->json(['status'=>1, 'message'=>'Successfully add comment','data'=>""]);
          }else{
          return response()->json(['status'=>1, 'message'=>'comment not add ','data'=>""]);
          }
          }catch(Exception $e){
          return response()->json(['status'=>0 ,'message'=>'Exception Error','data'=>json_encode("{}")]);
          }
        }

  
        public function fetchComment(Request $request)        //fetch comments
            {
          try{
              $res_id = $request->res_id;
                    $fetch  = DB::table('comments')
                    ->where('res_id',$res_id)
                    ->where('comment','!=','NULL')
                    ->get();
              //$data  = Comment::orderBy('id','DESC')->get();
              return response()->json(['status'=>1 , 'message'=>'Fetch Successfully','data'=>$fetch]);
              }catch(Exception $e){
              return response()->json(['status'=>0 ,'message'=>'Exception Error','data'=>json_encode("{}")]);
              }
                }

	    }
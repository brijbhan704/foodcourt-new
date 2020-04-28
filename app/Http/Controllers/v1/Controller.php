<?php

namespace App\Http\Controllers\v1;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Controllers\Traits\SendMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\NotifyMe;
use App\EventRating;
use App\Coupon;
use Config;
use Mail;


class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    /**
   * 
   * Notify mail sent */
  public function sendNotificationMail($userData){
    
    //email sent code for event creator
    //$userData = User::where('email',$email)->first();
    $app = app();
    $data = $app->make('stdClass');
    
    $data->type = $userData->type;//$userData->type;
    $data->city_id = $userData->city_id;
    $data->state_id = $userData->state_id;
    $data->country_id = $userData->country_id;
    $data->country_name =  $userData->Country->name; 
    $data->state_name =  $userData->State->name; 
    $data->city_name =  $userData->City->name; 
    $notifyObj = new NotifyMe();    
    $notifyList = $notifyObj->getNotifiedDataByType($data);
    
    if($notifyList->count() > 0){
        Log::info(['add '.$data->type.' notify data found',$notifyList]);
        $mailArr = [];
        $ids = [];
        foreach($notifyList as $k=>$v){
            if(!in_array($v->mail,$mailArr)){                            
                array_push($mailArr,$this->encdesc($v->email,'decrypt'));
                array_push($ids,$v->id);
                //$ids[] = $v->id;
            }                                                   
        }                              
        $maildata['email'] = $mailArr;
        $maildata['name'] = $userData->name;
        $maildata['city_name'] = $data->city_name;
        $maildata['type'] = ucfirst($data->type);
        $maildata['subject'] = ucfirst($data->type). ' Add Notification From '.config('app.site_name');
        $maildata['supportEmail'] = config('mail.supportEmail');
        $maildata['website'] = config('app.site_url');  
        $maildata['site_name'] = config('app.site_name');  
      
        if($this->SendMail($maildata,'notify_while_add')){
            NotifyMe::whereIn('id', $ids)->update(['notified' => 1]);
            Log::info(['add user notify mail sent']);
            return true;
        } 
    }
    return true;
    
  }

  public function encdesc($stringVal,$type='encrypt'){
      
    $stringVal = str_replace("__","/",$stringVal);  
    if($type=='encrypt'){
        return openssl_encrypt($stringVal,"AES-128-ECB",'Xz!Y2zRR4567!#$!');
    }else{
        return openssl_decrypt($stringVal,"AES-128-ECB",'Xz!Y2zRR4567!#$!');
    }        
  }

  public function verifyChecksum($request){    
    return true;
    $checksum = "";  
    $json = json_encode($request->all());
    $requestJson = md5($json); 
    $checksum = $request->header("checksum");
    
    //echo $requestJson.'  '.$checksum; die;
    if($requestJson != $checksum){
        return false;
    }
    return true;
  }
    
  public function sendsms($phone,$message){
    
    $url = "https://api.msg91.com/api/sendhttp.php?mobiles=$phone&authkey=298624AWJzQa0Z8n5da2dd16&route=4&sender=FDCORT&message=$message&country=91";
    if(file_get_contents($url)){
      return true;
    }        
  }
  
  public function checkAuthUser(){

    $user  = JWTAuth::user();    
    if($user->count()==0){
      return false;
    }else{
      return $user;
    }

  }
  
  public function string_replace($repaceFrom,$replaceTo,$string){
    return str_replace($repaceFrom,$replaceTo,$string);
  }

  public function getRating($type,$id){
    $rating = ['rating'=>0,'out_of'=>0]; 
    $ratingData = [];
    if($type=="event"){
       $ratingData =  EventRating::select(DB::raw('AVG(rating) as rating'),DB::raw('count(rating) as out_of'))
        ->where('event_id',$id)->get();
    }else if($type=="trainer"){
        $ratingData = UserRating::select(DB::raw('AVG(rating) as rating'),DB::raw('count(rating) as out_of'))
        ->where([
            ['user_id',$id],
            ['role_id',3]
            ])->get();
    }else if($type=="center"){
        $ratingData =  UserRating::select(DB::raw('AVG(rating) as rating'),DB::raw('count(rating) as out_of'))
        ->where([
            ['user_id',$id],
            ['role_id',2]
            ])->get();
    }else{
        return  $rating;
    }
    $rating['rating'] = number_format($ratingData[0]->rating,1);
    $rating['out_of'] = $ratingData[0]->out_of;
    
    return $rating;
  }
  
  public function distance($lat1, $lon1, $lat2, $lon2, $unit) {

      $theta = $lon1 - $lon2;
      $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
      $dist = acos($dist);
      $dist = rad2deg($dist);
      $miles = $dist * 60 * 1.1515;
      $unit = strtoupper($unit);
    
      if ($unit == "K") {
          return ($miles * 1.609344);
      } else if ($unit == "N") {
          return ($miles * 0.8684);
      } else {
          return $miles;
      }
  }

  public function getCoupon($price, $code){
      $coupon = ["price"=>$price,"coupon_msg"=>"","coupon_discount"=>0];
      $couponData = Coupon::where('code',$code)->get();
      if($couponData->count()){
         if(($couponData[0]->publish_date <= date('Y-m-d')) && (date('Y-m-d') <= $couponData[0]->expire_date)){
             $price = $price - $couponData[0]->discount;
             $coupon['price'] = number_format($price,2);
             $coupon['coupon_discount'] = $couponData[0]->discount;
             return $coupon;
         }else{
            $coupon['coupon_msg'] = "Invalid Coupon"; 
            return $coupon;
         }
      }else{
          $coupon['coupon_msg'] = "Invalid Coupon"; 
          return $coupon;
      }
  }
}
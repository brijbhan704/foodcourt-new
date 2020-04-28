<?php

namespace App\Http\Controllers\v1;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Controllers\Traits\SendMail;
use Config;
use App\Common\Utility;
use Mail;


class UserController extends Controller
{
    //use SendMail;
    public function authenticate(Request $request)
    {
        
        $status = 0;
        $message = "";

                      
        $validator = Validator::make($request->all(), [            
            'phone' => 'required|string|max:10',
            'otp' => 'required|string',
        ]);        
        //$validator->errors()
        if($validator->fails()){
          return response()->json(["status"=>$status,"responseCode"=>"NP997","message"=>"invalid input details","data"=>json_decode("{}")]);
        }
        //echo $pwd = Hash::make($request->password).'      ='.$request->email; die;
        $validationChk = User::where('phone',$request->phone)->get();
        
        //echo $validationChk;die;
        if($validationChk->count()==0){
          return response()->json(["status"=>$status,"responseCode"=>"NP997","message"=>"invalid credentials","data"=>json_decode("{}")]);          
        }else if($validationChk[0]->status != '1'){
          return response()->json(["status"=>$status,"responseCode"=>"NP997","message"=>"User not verified","data"=>json_decode("{}")]);          
        }
        
        $credentials = $request->only('phone', 'otp');  
        //print_r($credentials);die;              
        try {
          $myTTL = 43200; //minutes
          JWTAuth::factory()->setTTL($myTTL);    

            if (! $token = JWTAuth::attempt($credentials, ['status'=>'1'])) {            
                $message = 'invalid_credentials';                
                return response()->json(['status'=>$status,"responseCode"=>"NP997",'message'=>$message,'data'=>json_decode("{}")]);
            }
        } catch (JWTException $e) {
            $message = 'could_not_create_token';
            return response()->json(['status'=>$status,"responseCode"=>"NP997",'message'=>$message,'data'=>json_decode("{}")]);            
        }        
       // echo 1;die; 
        $user  = JWTAuth::user();
        unset($user->otp);
        unset($user->verified_otp);
        $user->token = $token;
        $user->remember_token = $token;
        $user->save();
        unset($user->remember_token);
        $status = 1;        
        return response()->json(['status'=>$status,"responseCode"=>"APP001",'message'=>$message,'data'=>$user]);
    }

    //register
    public function register( Request $request ){
        $status = 0;
        $message = "";
        $user = '';
        $validator = Validator::make($request->all(), [            
            'name' => 'required|string',
            'email' => 'required|email',
            'phone'=>'required|string|max:10'
        ]);   
        if($validator->fails()){
          return response()->json(["status"=>$status,"responseCode"=>"NP997","message"=>"invalid input details","data"=>json_decode("{}")]);
        }
      $userList = User::where('phone',$request->phone)->first();
       if($userList !=null && $userList->count() > 0){
         return response()->json(['status'=>0,"responseCode"=>"TC997",'message'=>'User already exists']);
       }else{
         $addNewUser  = new User();
         $addNewUser->name = $request->name;
         $addNewUser->email = $request->email;
         $addNewUser->phone = $request->phone;
         //$addNewUser->company_id = $request->company;
         //$addNewUser->currency_id = $request->currency;
         //$addNewUser->project_id = $request->project_id;
        // $projectId =$request->project_id;
        // echo $projectReporting;die;
          /*$datas = [];  
                $dataAccToAdmin = new Project();
                $datas = $dataAccToAdmin->userFetchJoin($projectId);
               
                $datas = (new Collection($datas));*/
                
                 /*$shares = DB::table('project')
                ->join('project_reporting', 'project_reporting.project_id', '=', 'project.id')
                ->where('project.id', '=', $projectId)
                ->select('project_reporting.project_reporting_id')    
                ->get();*/
                //echo ($shares); die;
         if(!$addNewUser->save()){
         return response()->json(['status'=>0,"responseCode"=>"TC997",'message'=>'Registration Failed','data'=>json_decode("{}")]);
         }
       }
              
      return response()->json(['status'=>'1',"responseCode"=>"TC001",'message'=>'Registered Successfully','data'=>json_decode($addNewUser)]);
        
    }

    public function apilogout(Request $request){
      
      try{        
        JWTAuth::invalidate(JWTAuth::parseToken()); 
        //JWTAuth::setToken($token)->invalidate();
        return response()->json(['status'=>1,"responseCode"=>"APP001",'message'=>'','data'=>json_decode("{}")]);
      }catch(Exception $e){
        return response()->json(['status'=>0,"responseCode"=>"NP997",'message'=>'Not able to logout','data'=>json_decode("{}")]);
      }
      
    }
    

    
    
    public function getAuthenticatedUser() { 
         $status = 0;   
        try {

                if (! $user = JWTAuth::parseToken()->authenticate()) {
                  //return response()->json(['user_not_found'], 404);
                  return response()->json(['status'=>$status,'message'=>'user_not_found','data'=>json_decode("{}")]);
                }

        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            //return response()->json(['token_expired'], $e->getStatusCode());
            return response()->json(['status'=>$status,'message'=>'token_expired','data'=>json_decode("{}")]);

        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

          //return response()->json(['token_invalid'], $e->getStatusCode());
          return response()->json(['status'=>$status,'message'=>'token_invalid','data'=>json_decode("{}")]);

        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
          return response()->json(['status'=>$status,'message'=>'token_absent','data'=>json_decode("{}")]);
          //return response()->json(['token_absent'], $e->getStatusCode());
        }
        $status = 1;
        return response()->json(compact('user'));
   }


   /**
   * ge Home pate method
   * @return success or error
   * 
   * */
  public function contactus(Request $request){
    
    try{
      $status = 0;
      $message = "";
      
      $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|string|max:255',          
        'phone' => 'required|string|max:15',          
        'subject' => 'required|string|max:255',          
        'message' => 'required|string'                    
      ]);
      
      //$validator->errors()
      if($validator->fails()){
        return response()->json(["status"=>$status,"message"=>"Please provide all mandatory fields","data"=>json_decode("{}")]);
      }

      $result = DB::table('contactus')->insert(
        ['email' => $request->email, 
        'name' => $request->name,
        'phone'=>$request->phone,
        'subject'=>$request->subject,
        'message'=>$request->message
        ]
      );
      if($result){
        return response()->json(['status'=>1,'message'=>'','data'=>$result]);    
      }else{
        return response()->json(['status'=>$status,'message'=>'not sent','data'=>json_decode("{}")]);                            
      }
      
    }catch(Exception $e){
      return response()->json(['status'=>$status,'message'=>'Exception Error','data'=>json_decode("{}")]);                    
    }
            
  }


  
  /**
   * ge Home pate method
   * @return success or error
   * 
   * */
  public function commonData(Request $request){
    
    try{
      $status = 0;
      $message = "";
      
      $obj = Setting::where('id',1)->first();
      
      if($obj->count() > 0){
        return response()->json(['status'=>1,'message'=>'','data'=>$obj]);    
      }else{
        return response()->json(['status'=>$status,'message'=>'record not found sent','data'=>json_decode("{}")]);                            
      }
      
    }catch(Exception $e){
      return response()->json(['status'=>$status,'message'=>'Exception Error','data'=>json_decode("{}")]);                    
    }
            
  }
  
  /**
   * ge Home pate method
   * @return success or error
   * 
   * */
  public function editMyProfile(Request $request){
    
    try{
      $status = 0;
      $message = "";
      $user  = JWTAuth::user(); 
      $userObj = User::findOrFail($user->id);
      $userObj->name = (isset($request->name)) ? $request->name : $user->name;
      $userObj->gst_number = (isset($request->gst_number)) ? $request->gst_number : $user->gst_number;
      $userObj->pan_card = (isset($request->pan_card)) ? $request->pan_card : $user->pan_card;
      $userObj->state = (isset($request->state)) ? $request->state : $user->state;
      $userObj->city = (isset($request->city)) ? $request->city : $user->city;
      if(isset($request->email)){
        if(User::where([
              ['email',$request->email],
              ['id','<>',$user->id]
              ])->count()){
                  return response()->json(['status'=>0,'message'=>'Email already exist','data'=>[]]); 
              }
              
              
      }
      $userObj->email = (isset($request->email)) ? $request->email : $user->email;
      if($userObj->save()){
        return response()->json(['status'=>1,'message'=>'','data'=>$userObj]);    
      }else{
        return response()->json(['status'=>$status,'message'=>'record not found sent','data'=>json_decode("{}")]);                            
      }
      
    }catch(Exception $e){
      return response()->json(['status'=>$status,'message'=>'Exception Error','data'=>json_decode("{}")]);                    
    }
            
  }
  
  public function sendotp(Request $request){
    
    try{

      $status = "NP997";
      $message = "";
      //Utility::stripXSS();                
          //echo 1;die;   
      $validator = Validator::make($request->all(), [          
        'phone' => 'required|string|max:10|min:10',
        //'email' => 'required|string|max:255',          
      ]);

      
      if($validator->fails()){
         $error = json_decode(json_encode($validator->errors()));
         if(isset($error->phone[0])){
           $message = $error->phone[0];
         }

         return response()->json(["status"=>$status,"responseCode"=>"NP997","message"=>$message,"data"=>json_decode("{}")]);
       }
      

      $userList = User::where('phone',$request->phone)->first();
     // echo $userList;die;
      
      $otp = rand(100000,999999);
      //$otp = 123456;
     // echo $otp;die;
      if($userList !=null && $userList->count() > 0){

        $userList->otp = $otp;
        $userList->otp_expiration_time = time();
        $userList->save();
      }else{
        $addUser = new User();
        //$addUser->email = $request->email;
        $addUser->name = ($request->name) ? $request->name : '';
        $addUser->phone = $request->phone;
        $addUser->otp_expiration_time = time();
        $addUser->otp = $otp;
        if(!$addUser->save()){
          return response()->json(['status'=>0,"responseCode"=>"NP997",'message'=>'User not added','data'=>json_decode("{}")]);
        }
      }
      
      $phone = $request->phone;
      $message = "<#> $otp is your FoodCourt verification OTP. LDfR2qVis5m" ;
      $message=urlencode($message);
      
      if($this->sendsms($phone,$message)){
        return response()->json(["status"=>1,"responseCode"=>"APP001","message"=>"OTP Sent","data"=>json_decode("{}")]);
      }
         
    }catch(Exception $e){
      return response()->json(['status'=>0,"responseCode"=>"NP997",'message'=>'User update Error','data'=>json_decode("{}")]);                    
    }
            
  }
  
  public function updateDeviceToken(Request $request){
    
    try{
      $status = 0;
      $message = "";
      $user  = JWTAuth::user(); 
      $userObj = User::findOrFail($user->id);
      $userObj->name = (isset($request->name)) ? $request->name : $user->name;
      
      $userObj->device_id = (isset($request->device_id)) ? $request->device_id : $user->device_id;
      if($userObj->save()){
        return response()->json(['status'=>1,'message'=>'','data'=>$userObj]);    
      }else{
        return response()->json(['status'=>$status,'message'=>'record not found sent','data'=>json_decode("{}")]);                            
      }
      
    }catch(Exception $e){
      return response()->json(['status'=>$status,'message'=>'Exception Error','data'=>json_decode("{}")]);                    
    }
            
  }

  /*
   print_r($data);die;
            /*$total_data = DB::table('add_to_cart')
            ->join('order_list', 'add_to_cart.order_id', '=', 'order_list.id')
            ->select('order_list.phone','order_list.id as orderId','order_list.total_price','order_list.delivery_charge','order_list.total_price_with_delivery','order_list.gst','order_list.total_price_with_gst')
            ->where('order_list.phone','=',$phone)
            ->get();
            foreach($total_data as $key => $value){
                if(in_array($value->orderId, $s)){
                $f = count($orderArray[$value->orderId]['orderItem']);
                $orderArray[$value->orderId]['orderItem'][$f]['total_price'] = $value->total_price;
                $orderArray[$value->orderId]['orderItem'][$f]['delivery_charge'] = $value->delivery_charge;
                $orderArray[$value->orderId]['orderItem'][$f]['total_price_with_delivery'] = $value->total_price_with_delivery;
                $orderArray[$value->orderId]['orderItem'][$f]['gst'] = $value->gst;
                $orderArray[$value->orderId]['orderItem'][$f]['total_price_with_gst'] = $value->total_price_with_gst;
                $orderArray[$value->orderId]['orderItem'][0]['phone'] = $value->phone;
            }else{
                array_push($s,$value->orderId);
                $orderArray[$value->orderId]['orderItem'][0]['total_price'] = $value->total_price;
                $orderArray[$value->orderId]['orderItem'][0]['delivery_charge'] = $value->delivery_charge;
                $orderArray[$value->orderId]['orderItem'][0]['total_price_with_delivery'] = $value->total_price_with_delivery;
                $orderArray[$value->orderId]['orderItem'][0]['gst'] = $value->gst;
                $orderArray[$value->orderId]['orderItem'][0]['total_price_with_gst'] = $value->total_price_with_gst;
                $orderArray[$value->orderId]['orderItem'][0]['phone'] = $value->phone;
            }  
            }
            $a = [];
            foreach($orderArray as $key => $orders){
            $a[] = $orders;
            }
        print_r($a);die;
         
  */

}
<?php 
namespace App\Http\Controllers\Traits; 
use Mail;
use Config;
trait SendMail { 
    protected function SendMail($data,$template='notify') {     
        try{
            Mail::send($template,$data, function($newObj) use($data)
            {
                $newObj->from(config('mail.from.address'), config('app.site_name'));
                $newObj->subject($data['subject']);
                $newObj->to($data['email']);            
            });
            return true;
          }catch(Exception $e){
            return false;
          }  
    } 
}
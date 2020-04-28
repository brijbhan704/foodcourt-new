<?php
namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Hash;

/**
 * Class User
 *
 * @package App
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $remember_token
*/
class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use HasRoles;

    protected $fillable = ['name', 'email', 'password', 'remember_token'];
    
     
    public function getJWTIdentifier() {
      return $this->getKey();
    }
    public function getJWTCustomClaims(){
      return [];
    }
    /**
     * Hash password
     * @param $input
     */
    public function setPasswordAttribute($input)
    {
        if ($input)
            $this->attributes['password'] = app('hash')->needsRehash($input) ? Hash::make($input) : $input;
    }
    
    
    public function role()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }
     public function generateVarchar(){
      return bin2hex(openssl_random_pseudo_bytes(5));
    }
    public function updateUser($data){
      return DB::table('administrators')->where('id', $data[0]->id)->update(['confirm' => 1]);
    }
    
    
    
}

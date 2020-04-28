<?php

    namespace App\Http\Middleware;

    use Closure;
    use JWTAuth;
    use Exception;
    use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

    class JwtMiddleware extends BaseMiddleware
    {

        /**
         * Handle an incoming request.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  \Closure  $next
         * @return mixed
         */
        public function handle($request, Closure $next)
        {
            try {
                $user = JWTAuth::parseToken()->authenticate();
            } catch (Exception $e) {
                $status = 0;
                if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                    return response()->json(['status'=>$status,'message'=>'Token is Invalid','data'=>json_decode("{}")]);
                    //return response()->json(['status' => 'Token is Invalid']);
                }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                    //return response()->json(['status' => 'Token is Expired']);
                    return response()->json(['status'=>$status,'message'=>'Token is Expired','data'=>json_decode("{}")]);
                }else{
                    //return response()->json(['status' => 'Authorization Token not found']);
                    return response()->json(['status'=>$status,'message'=>'Authorization Token not found','data'=>json_decode("{}")]);
                }
            }
            return $next($request);
        }
    }
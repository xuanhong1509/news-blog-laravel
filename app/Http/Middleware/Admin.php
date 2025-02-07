<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    

     public function handle($request, Closure $next)
     {
         if (Auth::check()) {
             if (Auth::user()->roles == 'admin') {
                 return $next($request);
             } else {
                 return redirect('/no-permission')->with('error', 'Bạn không có quyền truy cập trang quản trị viên.');
             }
         }
     
         return redirect('/login');
     }     
     
}

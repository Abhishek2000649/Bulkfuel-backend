<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         if (Auth::check()) {

            $role = Auth::user()->role;

            if ($role === 'ADMIN') {
                return redirect()->route('admin.dashboard');
            }

            if ($role === 'DELIVERY_AGENT') {
                return redirect()->route('delivery.dashboard');
            }

            // default USER
            return redirect()->route('user.dashboard');
        }

        return $next($request);
    }
        
    
}

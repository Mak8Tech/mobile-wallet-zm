<?php

namespace Mak8Tech\MobileWalletZm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeAdminOperations
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $permission = null)
    {
        // Check if authorization is disabled in the config
        if (Config::get('mobile_wallet.admin.disable_authorization', false)) {
            return $next($request);
        }
        
        // Check if the user is authenticated
        if (!Auth::check()) {
            return $this->unauthorized('Unauthenticated.');
        }
        
        $user = Auth::user();
        
        // Check if the user is a super admin
        if ($this->isSuperAdmin($user)) {
            return $next($request);
        }
        
        // If a specific permission is required, check if the user has it
        if ($permission) {
            if ($this->hasPermission($user, $permission)) {
                return $next($request);
            }
            return $this->unauthorized("Insufficient permissions to perform this action: {$permission}");
        }
        
        // If no specific permission is required, check if the user has any admin permissions
        if ($this->hasAnyAdminPermission($user)) {
            return $next($request);
        }
        
        return $this->unauthorized('Insufficient permissions to access the admin area.');
    }
    
    /**
     * Check if the user is a super admin.
     *
     * @param  mixed  $user
     * @return bool
     */
    protected function isSuperAdmin($user)
    {
        // Use the super_admin_check from config if available
        $superAdminCheck = Config::get('mobile_wallet.admin.super_admin_check');
        
        if ($superAdminCheck && is_callable($superAdminCheck)) {
            return call_user_func($superAdminCheck, $user);
        }
        
        // Otherwise check if user has a super_admin flag
        if (method_exists($user, 'isSuperAdmin')) {
            return $user->isSuperAdmin();
        }
        
        return $user->super_admin ?? false;
    }
    
    /**
     * Check if the user has the specified permission.
     *
     * @param  mixed  $user
     * @param  string  $permission
     * @return bool
     */
    protected function hasPermission($user, $permission)
    {
        // Use the permission_check from config if available
        $permissionCheck = Config::get('mobile_wallet.admin.permission_check');
        
        if ($permissionCheck && is_callable($permissionCheck)) {
            return call_user_func($permissionCheck, $user, $permission);
        }
        
        // Otherwise check if user has permissions property or method
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($permission);
        }
        
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo($permission);
        }
        
        return in_array($permission, $user->permissions ?? []);
    }
    
    /**
     * Check if the user has any admin permissions.
     *
     * @param  mixed  $user
     * @return bool
     */
    protected function hasAnyAdminPermission($user)
    {
        $adminPermissions = Config::get('mobile_wallet.admin.permissions', [
            'mobile-wallet.admin.access',
            'mobile-wallet.transactions.view',
            'mobile-wallet.transactions.manage'
        ]);
        
        foreach ($adminPermissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Return an unauthorized response.
     *
     * @param  string  $message
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function unauthorized($message)
    {
        if (request()->expectsJson()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => $message,
            ], Response::HTTP_FORBIDDEN);
        }
        
        return redirect()->route(Config::get('mobile_wallet.admin.login_route', 'login'))
            ->with('error', $message);
    }
} 
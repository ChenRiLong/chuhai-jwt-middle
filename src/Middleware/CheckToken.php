<?php

namespace Chuhai\JwtMiddleware\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use JWTAuth;

class CheckToken extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 检查此次请求中是否带有 token，如果没有则抛出异常。
        $this->checkForToken($request);

        // 使用 try 包裹，以捕捉 token 过期所抛出的 TokenExpiredException  异常
        try {
            // 检测用户的登录状态，如果正常则通过
            if ($this->auth->parseToken()->authenticate()) {

                $user = auth('api')->user();

                if ($user) {
                    define('USER_ID', $user->id);
                    define('PRESENT_TEAM_ID', $user->present_team_id);
                    define('TEAM_ID', $user->team_id);
                } else {
                    return response()->json(['message' => 'token error1'], 401);
                }

                return $next($request);
            }
            throw new UnauthorizedHttpException('jwt-auth', '未登录');

        } catch (TokenExpiredException $exception) {
            // 此处捕获到了 token 过期所抛出的 TokenExpiredException 异常，我们在这里需要做的是刷新该用户的 token 并将它添加到响应头中
            try {
                // 刷新用户的 token
                $token = $this->auth->refresh();

                $user = auth('api')->user();

                if ($user) {
                    define('USER_ID', $user->id);
                    define('PRESENT_TEAM_ID', $user->present_team_id);
                    define('TEAM_ID', $user->team_id);
                } else {
                    return response()->json(['message' => 'token error2'], 401);
                }

                // 使用一次性登录以保证此次请求的成功
                Auth::guard('api')->onceUsingId($this->auth->manager()->getPayloadFactory()->buildClaimsCollection()->toPlainArray()['sub']);
            } catch (JWTException $exception) {
                // 如果捕获到此异常，即代表 refresh 也过期了，用户无法刷新令牌，需要重新登录。
//                throw new UnauthorizedHttpException('jwt-auth', $exception->getMessage());
                return response()->json(['message' => 'token error3'], 401);
            }
        }

        // 在响应头中返回新的 token
        return $this->setAuthenticationHeader($next($request), $token);
    }
}

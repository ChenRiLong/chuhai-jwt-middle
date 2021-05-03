<?php

namespace Chuhai\JwtMiddleware;

use Chuhai\JwtMiddleware\Middleware\CheckToken;
use Illuminate\Support\ServiceProvider;

class JwtMiddlewareServiceProvider extends ServiceProvider
{
    /**
     * Register services.~
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->addMiddlewareAlias('check.token', CheckToken::class);
    }

    # 添加中间件的别名
    protected function addMiddlewareAlias($name, $class)
    {
        $router = $this->app['router'];
        // 判断aliasMiddleware是否在类中存在
        if (method_exists($router, 'aliasMiddleware')) {

            // aliasMiddleware 顾名思义,就是给中间件设置一个别名
            $router = $router->aliasMiddleware($name, $class);
//dd($router);
            return $router;
        }

        return $router->middleware($name, $class);
    }
}

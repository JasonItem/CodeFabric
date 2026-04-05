<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;
use ReflectionMethod;

/**
 * 路由反射工具：用于读取控制器方法上的 PHP Attributes。
 */
final class ReflectRoute
{
    public static function method(): ?ReflectionMethod
    {
        $route = request()->route();
        if (!$route) {
            return null;
        }

        $controller = $route->getController();
        $actionMethod = $route->getActionMethod();

        if (!$controller || !method_exists($controller, $actionMethod)) {
            return null;
        }

        return new ReflectionMethod($controller, $actionMethod);
    }

    public static function actionName(): string
    {
        $route = request()->route();
        if (!$route) {
            return request()->path();
        }

        $action = (string) $route->getActionName();

        return Str::contains($action, '@') ? $action : request()->path();
    }
}

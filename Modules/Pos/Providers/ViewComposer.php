<?php

namespace Modules\Pos\Providers;

use Illuminate\Support\ServiceProvider;

class ViewComposer extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */


    public function boot(){
        $routes = collect(\Route::getRoutes())->map(function ($route) {
            if($route->getName() != null){
                return $route->getName();
            }
        });
        view()->composer($routes->toArray(), function ($view) {
            if(\Auth::check())
            {
                $active_module = explode(',',\Auth::user()->active_module);
                $dependency = explode(',','Pos');
                if(!empty(array_intersect($dependency,$active_module)))
                {
                    $view->getFactory()->startPush('pos_setting_sidebar', view('pos::setting.sidebar'));
                    $view->getFactory()->startPush('pos_setting_sidebar_div', view('pos::setting.nav_containt_div'));
                }
            }
        });
    }
    public function register()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}

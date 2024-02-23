<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Sidebar extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'icon',
        'parent_id',
        'sort_order',
        'route',
        'is_visible',
        'permissions',
        'module',
        'dependency',
        'disable_module',
        'type'
    ];
    protected $table ="sidebar";
    public function childs() {
        $active_module = ActivatedModule();
        array_push($active_module,'Base');
        return $this->hasMany('App\Models\Sidebar','parent_id','id')->whereIn('module',$active_module)->where('is_visible',1)->orderBy('sort_order') ;
    }

    public static function GetDashboardRoute()
    {
        $data = [];
        $data['status'] = false;
        $data['route'] = '';
        if(\Auth::user()->type == 'super admin')
        {
            $data['status'] = true;
            $data['route'] = 'dashboard';
        }
        else
        {
            $active_module = ActivatedModule();

            $dashboard = Sidebar::where('title','Dashboard')->where('parent_id',0)->where('type','company')->first();
            if(!empty($dashboard))
            {
                $sidebars = Sidebar::where('parent_id',$dashboard->id)->where('is_visible',1)->whereIn('module',$active_module)->whereNotNull('route')->orderBy('sort_order')->get();
                if(count($sidebars) > 0 && Auth::user()->canany(array_column($sidebars->toArray(), 'permissions')))
                {
                    foreach ($sidebars as $key => $sidebar)
                    {
                        if(module_is_active($sidebar->module))
                        {
                            if(Auth::user()->can($sidebar->permissions))
                            {
                                if(!empty($sidebar->route))
                                {
                                    $data['status'] = true;
                                    $data['route'] = $sidebar->route;
                                    return $data;
                                }
                            }
                        }
                    }
                }
                else
                {
                    $data['status'] = true;
                    $data['route'] = 'dashboard';
                }
            }
            else
            {
                $data['status'] = true;
                $data['route'] = 'dashboard';
            }
        }
        return $data;
    }
}

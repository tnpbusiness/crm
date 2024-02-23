<?php

namespace Database\Seeders;

use App\Models\AddOn;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Facades\Module;

class PreInstalledModule extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $modules = Module::all();

        $data = [];
        foreach ($modules as $module)
        {
            try {
                //code...
                $addon = new AddOn();
                $addon->module = $module->getName();
                $addon->name = Module_Alias_Name($module->getName());
                $addon->monthly_price = 0;
                $addon->yearly_price = 0;

                $addon->save();

                $data[] = $module->getName();
                Artisan::call('module:migrate '.$module->getName());
                Artisan::call('module:seed '.$module->getName());
                $module->enable();
            } catch (\Throwable $th) {

            }
        }
        // $module_data = implode(',',$data);
        // $user = User::where('type','company')->first();
        // $user->assignPlan('Month',$module_data,0,$user->id);
    }
}

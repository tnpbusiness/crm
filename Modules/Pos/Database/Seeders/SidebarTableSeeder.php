<?php

namespace Modules\Pos\Database\Seeders;

use App\Models\Sidebar;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class SidebarTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
            $dashboard = Sidebar::where('title',__('Dashboard'))->where('parent_id',0)->where('type','company')->where('type','company')->first();
            $check = Sidebar::where('title',__('POS Dashboard'))->where('parent_id',$dashboard->id)->where('type','company')->exists();
            if(!$check)
            {
                Sidebar::create([
                    'title' => 'POS Dashboard',
                    'icon' => '',
                    'parent_id' => $dashboard->id,
                    'sort_order' => 50,
                    'route' => 'pos.dashboard',
                    'permissions' => 'pos dashboard manage',
                    'module' => 'Pos',
                    'type'=>'company',
                ]);
            }
            $main = Sidebar::where('title',__('POS'))->where('parent_id',0)->where('type','company')->first();
            if($main == null)
            {
                $main = Sidebar::create( [
                    'title' => 'POS',
                    'icon' => 'ti ti-grid-dots',
                    'parent_id' => 0,
                    'sort_order' => 350,
                    'route' => '',
                    'permissions' => 'pos manage',
                    'module' => 'Pos',
                    'type'=>'company',
                ]);
            }
            $Warehouse = Sidebar::where('title',__('Warehouse'))->where('parent_id',$main->id)->where('type','company')->first();
            if($Warehouse == null)
            {
               Sidebar::create([
                    'title' => 'Warehouse',
                    'icon' => '',
                    'parent_id' => $main->id,
                    'sort_order' => 10,
                    'route' => 'warehouse.index',
                    'permissions' => 'warehouse manage',
                    'module' => 'Pos',
                    'type'=>'company',
                ]);
            }
            $Purchase = Sidebar::where('title',__('Purchase'))->where('parent_id',$main->id)->where('type','company')->first();
            if($Purchase == null)
            {
               Sidebar::create([
                    'title' => 'Purchase',
                    'icon' => '',
                    'parent_id' => $main->id,
                    'sort_order' => 15,
                    'route' => 'purchase.index',
                    'permissions' => 'purchase manage',
                    'module' => 'Pos',
                    'type'=>'company',
                ]);
            }

            $AddPos = Sidebar::where('title',__('Add POS'))->where('parent_id',$main->id)->where('type','company')->first();
            if($AddPos == null)
            {
               Sidebar::create([
                    'title' => 'Add POS',
                    'icon' => '',
                    'parent_id' => $main->id,
                    'sort_order' => 20,
                    'route' => 'pos.index',
                    'permissions' => 'pos add manage',
                    'module' => 'Pos',
                    'type'=>'company',
                ]);
            }
            $POS = Sidebar::where('title',__('POS Order'))->where('parent_id',$main->id)->where('type','company')->first();
            if($POS == null)
            {
               Sidebar::create([
                    'title' => 'POS Order',
                    'icon' => '',
                    'parent_id' => $main->id,
                    'sort_order' => 25,
                    'route' => 'pos.report',
                    'permissions' => 'pos add manage',
                    'module' => 'Pos',
                    'type'=>'company',
                ]);
            }


    }
}

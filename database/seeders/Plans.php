<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanField;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class Plans extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $plan = Plan::first();
        if(empty($plan))
        {
            $new_pan = new Plan();
            $new_pan->package_price_monthly = 0;
            $new_pan->package_price_yearly = 0;
            $new_pan->price_per_user_monthly = 0;
            $new_pan->price_per_user_yearly = 0;
            $new_pan->save();
        }
    }
}

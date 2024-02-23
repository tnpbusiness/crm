<?php

namespace App\Http\Controllers;

use App\Models\AddOn;
use App\Models\Order;
use App\Models\Plan;
use App\Models\PlanField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Nwidart\Modules\Facades\Module;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(Auth::user()->can('plan manage'))
        {
            $plan = Plan::first();

            if(Auth::user()->type == 'super admin')
            {
                // $modules = Module::all();
                $modules = Module::getByStatus(1);

                return view('plans.index',compact('modules','plan'));
            }
            else
            {
                // Pre selected module, user,and time period on pricing page
                $session = Session::get('user-module-selection');

                $modules = Module::getByStatus(1);
                $purchaseds = [];
                $active_module = ActivatedModule();
                if(count($active_module) > 0)
                {
                    foreach ($active_module as $key => $value)
                    {
                        if(array_key_exists($value,$modules) == true)
                        {
                            $module = Module::find($value);
                            $path = $module->getPath() . '/module.json';
                            $json = json_decode(file_get_contents($path), true);
                            if (!isset($json['display']) || $json['display'] == true)
                            {
                                array_push($purchaseds,$modules[$value]);
                            }
                            unset($modules[$value]);
                        }
                    }
                }
                return view('plans.marketplace',compact('plan','modules','purchaseds','session'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if(Auth::user()->can('plan create'))
        {
            $duretion = Plan::$arrDuration;
            return view('plans.create',compact('duretion'));
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(Auth::user()->can('plan create'))
        {
            $validator = \Validator::make(
                $request->all(), [
                    'package_price_monthly' => 'required|min:0',
                    'package_price_yearly' => 'required|min:0',
                    'price_per_user_monthly' => 'required|min:0',
                    'price_per_user_yearly' => 'required|min:0',
                ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $plan = Plan::first();
            $plan->package_price_monthly = !empty($request->package_price_monthly) ? $request->package_price_monthly : 0;
            $plan->package_price_yearly = !empty($request->package_price_yearly) ? $request->package_price_yearly : 0;
            $plan->price_per_user_monthly = !empty($request->price_per_user_monthly) ? $request->price_per_user_monthly : 0;
            $plan->price_per_user_yearly = !empty($request->price_per_user_yearly) ? $request->price_per_user_yearly : 0;
            $plan->save();

            return redirect()->route('plans.index')->with('success', 'Details Saved Successfully.');

        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
    public function show(Plan $plan)
    {
        return redirect()->route('plans.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
    public function edit(Plan $plan)
    {
        if(Auth::user()->can('plan edit'))
        {
            $duretion = Plan::$arrDuration;
            $plan = Plan::with('fields')->find($plan->id);
            return view('plans.edit',compact('duretion','plan'));
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Plan $plan)
    {
        if(Auth::user()->can('plan edit'))
        {

            $validator = \Validator::make(
                $request->all(), [
                    'name' => 'required|unique:plans,name,'.$plan->id,
                    'duration' => 'required',
                    'price' => 'required',
                ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            DB::beginTransaction();
            try {
                $plan->name = $request->name;
                $plan->price = $request->price;
                $plan->duration = $request->duration;
                $plan->save();
                if($plan){
                    $fields = getPlanField();
                    $plan_field = PlanField::where('plan_id',$plan->id)->first();
                    $plan_field->plan_id =$plan->id;
                    foreach($fields as $field){
                        $plan_field->$field = isset($request->plan_files[$field])?$request->plan_files[$field]:0;
                    }
                    $plan_field->save();
                    DB::commit();
                    return redirect()->back()->with('success', 'Plan update successfully.');
                }else{
                    DB::rollback();
                    return redirect()->back()->with('error', 'Oops something went wrong.');
                }

            }catch (\Exception $e) {
                DB::rollback();
                // something went wrong
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
    public function destroy(Plan $plan)
    {
        //
    }

    public function payment($plan_id)
    {
        if(Auth::user()->can('plan purchase'))
        {
            $plan_id = decrypt($plan_id);
            $plan = Plan::find($plan_id);
            return view('plans.payment',compact('plan'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function orders()
    {
        if(Auth::user()->can('plan orders'))
        {
            $user                       = \Auth::user();
            $orders = Order::select(
                    [
                        'orders.*',
                        'users.name as user_name',
                    ]
                )->join('users', 'orders.user_id', '=', 'users.id')->where(function($query) use ($user)
                {
                    if($user->type != 'super admin')
                    {
                        $query->where('orders.user_id', $user->id);
                    }
                })->orderBy('orders.created_at', 'DESC')->get();

            return view('plan_order.index', compact('orders'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function AddOneDetail($module = null)
    {
        if(Auth::user()->can('module edit') && !empty($module))
        {
            $addon = AddOn::where('module',$module)->first();
            if(!empty($addon))
            {
                return view('plans.module_detail',compact('addon'));
            }
            else
            {
                return response()->json(['error' => __('Something went wrong, Data not found.')], 401);
            }
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }
    public function AddOneDetailSave(Request $request,$id = null)
    {
        if(Auth::user()->can('module edit') && !empty($id))
        {
            $addon = AddOn::find($id);

            $validator = \Validator::make(
                $request->all(), [
                    'name' => 'required|unique:add_ons,name,'.$addon->id,
                    'monthly_price' => 'required|min:0',
                    'yearly_price' => 'required|min:0',
                    'module_logo' => 'mimes:jpg,jpeg,png',
                ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $addon->name = $request->name;
            $addon->monthly_price = $request->monthly_price;
            $addon->yearly_price = $request->yearly_price;
            $addon->save();

            if (!empty($request->module_logo))
            {
                $module = Module::find($addon->module);
                if(!empty($module))
                {
                    $module->getPath();
                    $fileNameToStore = 'favicon.png';
                    $dir             = $module->getPath();
                    $request->module_logo->move($dir, $fileNameToStore);
                }

            }
            return redirect()->back()->with('success', 'Module Setting Save Successfully.');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}

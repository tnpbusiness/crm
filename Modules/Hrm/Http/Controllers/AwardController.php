<?php

namespace Modules\Hrm\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Hrm\Entities\Award;
use Modules\Hrm\Entities\AwardType;
use Modules\Hrm\Entities\Employee;

class AwardController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if(Auth::user()->can('award manage'))
        {
            if(!in_array(Auth::user()->type, Auth::user()->not_emp_type))
            {
                $awards     = Award::where('user_id',Auth::user()->id)->where('workspace',getActiveWorkSpace())->get();
            }
            else
            {
                $awards     = Award::where('created_by',creatorId())->where('workspace',getActiveWorkSpace())->get();
            }

            return view('hrm::award.index', compact('awards'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if(Auth::user()->can('award create'))
        {
            $employees = User::where('workspace_id',getActiveWorkSpace())->where('created_by', '=', creatorId())->emp()->get()->pluck('name', 'id');
            $awardtypes = AwardType::where('created_by', '=', creatorId())->where('workspace',getActiveWorkSpace())->get()->pluck('name', 'id');

            return view('hrm::award.create', compact('employees', 'awardtypes'));
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if(Auth::user()->can('award create'))
        {

            $validator = \Validator::make(
                $request->all(),
                [
                    'employee_id' => 'required',
                    'award_type' => 'required',
                    'date' => 'required|after:yesterday',
                    'gift' => 'required',
                    'description' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $award              = new Award();
            $employee = Employee::where('user_id', '=', $request->employee_id)->first();
            if(!empty($employee))
            {
                $award->employee_id = $employee->id;
            }
            $award->user_id     = $request->employee_id;
            $award->award_type  = $request->award_type;
            $award->date        = $request->date;
            $award->gift        = $request->gift;
            $award->description =  $request->description;
            $award->workspace   = getActiveWorkSpace();
            $award->created_by  = creatorId();
            $award->save();

            $awardtype = AwardType::find($request->award_type);
            //slack
            if(module_is_active('Slack') && !empty(company_setting('New Award')) && company_setting('New Award')  == true)
            {
                $emp = User::find($request->employee_id);
                $msg = $awardtype->name . ' ' . __("created for") . ' ' . $emp->name . ' ' . __("from") . ' ' . $request->date . '.';
                // SendSlackMsg
                event(new \Modules\Slack\Events\SendSlackMsg($msg));
            }
            //telegram
            if(module_is_active('Telegram') && !empty(company_setting('Telegram New Award')) && company_setting('Telegram New Award')  == true)
            {
                $emp = User::find($request->employee_id);
                $msg = $awardtype->name . ' ' . __("created for") . ' ' . $emp->name . ' ' . __("from") . ' ' . $request->date . '.';
                // SendTelegramMsg
                event(new \Modules\Telegram\Events\SendTelegramMsg($msg));
            }
            //twilio
            if(module_is_active('Twilio') && !empty(company_setting('Twilio New Award')) && company_setting('Twilio New Award')  == true)
            {
                $emp = User::find($request->employee_id);
                if(!empty($emp->mobile_no)){
                    $msg = $awardtype->name . ' ' . __("created for") . ' ' . $emp->name . ' ' . __("from") . ' ' . $request->date . '.';
                    event(new \Modules\Twilio\Events\SendTwilioMsg($emp->mobile_no,$msg));
                }
            }

            if(!empty(company_setting('New Award')) && company_setting('New Award')  == true)
            {
                $User        = User::where('id', $award->user_id)->where('workspace_id', '=',  getActiveWorkSpace())->first();

                $uArr = [
                    'award_name'=>$User->name,
                    'award_date'=>$award->date,
                    'award_type'=>$awardtype->name,
                ];
                try
                {
                    $resp = EmailTemplate::sendEmailTemplate('New Award', [$User->email], $uArr);
                }
                catch(\Exception $e)
                {
                    $resp['error'] = $e->getMessage();
                }
                return redirect()->route('award.index')->with('success', __('Award  successfully created.'). ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }

            //webhook
            if(module_is_active('Webhook')){
                $employee = User::where('id', $request->employee_id)->first();
                $award->employee_id = $employee->name;
                $award->award_type = $awardtype->name;
                unset($award->user_id);
                $action = 'New Award';
                $module = 'Hrm';
                event(new \Modules\Webhook\Events\SendWebhook($module ,$award,$action));
            }
            return redirect()->route('award.index')->with('success', __('Award  successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return redirect()->route('award.index');
        return view('hrm::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit(Award $award)
    {
        if(Auth::user()->can('award edit'))
        {
            if($award->created_by == creatorId() && $award->workspace == getActiveWorkSpace())
            {
                $employees = User::where('workspace_id',getActiveWorkSpace())->where('created_by', '=', creatorId())->emp()->get()->pluck('name', 'id');
                $awardtypes = AwardType::where('created_by', '=', creatorId())->where('workspace',getActiveWorkSpace())->get()->pluck('name', 'id');
                return view('hrm::award.edit', compact('award', 'awardtypes', 'employees'));
            }
            else
            {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, Award $award)
    {
        if(Auth::user()->can('award edit'))
        {
            if($award->created_by == creatorId() && $award->workspace == getActiveWorkSpace())
            {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'employee_id' => 'required',
                        'award_type' => 'required',
                        'date' => 'required|after:'.date('Y-m-d')   ,
                        'gift' => 'required',
                        'description' => 'required',
                    ]
                );

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }
                $employee = Employee::where('user_id', '=', $request->employee_id)->first();
                if(!empty($employee))
                {
                    $award->employee_id = $employee->id;
                }
                $award->user_id     = $request->employee_id;
                $award->award_type  = $request->award_type;
                $award->date        = $request->date;
                $award->gift        = $request->gift;
                $award->description = $request->description;
                $award->save();

                return redirect()->route('award.index')->with('success', __('Award successfully updated.'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy(Award $award)
    {
        if(Auth::user()->can('award delete'))
        {
            if($award->created_by == creatorId() && $award->workspace == getActiveWorkSpace())
            {
                $award->delete();

                return redirect()->route('award.index')->with('success', __('Award successfully deleted.'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}

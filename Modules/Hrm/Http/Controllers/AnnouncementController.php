<?php

namespace Modules\Hrm\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Hrm\Entities\Announcement;
use Modules\Hrm\Entities\AnnouncementEmployee;
use Modules\Hrm\Entities\Branch;
use Modules\Hrm\Entities\Department;
use Modules\Hrm\Entities\Employee;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if(Auth::user()->can('announcement manage'))
        {
            if(!in_array(Auth::user()->type, Auth::user()->not_emp_type))
            {
                $employee = Employee::where('user_id',Auth::user()->id)->first();
                $announcements = [];
                if(!empty($employee)){
                    $announcements    = Announcement::orderBy('announcements.id', 'desc')->leftjoin('announcement_employees', 'announcements.id', '=', 'announcement_employees.announcement_id')->where('announcement_employees.employee_id', '=', $employee->id)->orWhere(
                        function ($q){
                        $q->where('announcements.department_id', '["0"]')
                            ->where('announcements.employee_id', '["0"]')
                            ->where('announcements.workspace',getActiveWorkSpace());
                        }
                    )->get();
                }
            }
            else
            {
                $announcements    = Announcement::where('created_by', '=', creatorId())->where('workspace',getActiveWorkSpace())->get();
            }

            return view('hrm::announcement.index', compact('announcements'));
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
        if(Auth::user()->can('announcement create'))
        {
            $branch    = Branch::where('created_by', '=', creatorId())->where('workspace',getActiveWorkSpace())->get()->pluck('name', 'id');
            $branch->prepend('All', 0);
            $departments  = Department::where('created_by', '=', creatorId())->where('workspace',getActiveWorkSpace())->get();

            return view('hrm::announcement.create', compact('branch', 'departments'));
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
        if(\Auth::user()->can('attendance create'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'title' => 'required',
                                   'start_date' => 'required|after:yesterday',
                                   'end_date' => 'required|after_or_equal:start_date',
                                   'branch_id' => 'required',
                                   'department_id' => 'required',
                                   'employee_id' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $announcement                = new Announcement();
            $announcement->title         = $request->title;
            $announcement->start_date    = $request->start_date;
            $announcement->end_date      = $request->end_date;
            $announcement->branch_id     = $request->branch_id ;
            $announcement->department_id = implode("," , $request->department_id);
            $announcement->employee_id   = implode("," , $request->employee_id);
            $announcement->description   = $request->description;
            $announcement->workspace     = getActiveWorkSpace();
            $announcement->created_by    = creatorId();
            $announcement->save();

            // // slack
            if(module_is_active('Slack') && !empty(company_setting('New Announcement')) && company_setting('New Announcement')  == true)
            {
                $branch = Branch ::find($request->branch_id);
                $msg = $request->title.' '. __("announcement created for branch").' '.$branch->name.' '. __("from").' '.$request->start_date.' '. __("to").' '.$request->end_date.'.';
                // SendSlackMsg
                event(new \Modules\Slack\Events\SendSlackMsg($msg));
            }

            //telegram
            if(module_is_active('Telegram') && !empty(company_setting('Telegram New Announcement')) && company_setting('Telegram New Announcement')  == true)
            {
                $branch = Branch ::find($request->branch_id);
                $msg = $request->title.' '. __("announcement created for branch").' '.$branch->name.' '. __("from").' '.$request->start_date.' '. __("to").' '.$request->end_date.'.';
                // SendTelegramMsg
                event(new \Modules\Telegram\Events\SendTelegramMsg($msg));
            }
             //twilio
            if(module_is_active('Twilio') && !empty(company_setting('Twilio New Announcement')) && company_setting('Twilio New Announcement')  == true)
            {
            $employee = Employee::whereIn('id', $request->employee_id)->get();
            foreach($employee as $emp){
                if(!empty($emp->phone)){
                    $msg = $request->title.' '. __("announcement created for branch").' '.$branch->name.' '. __("from").' '.$request->start_date.' '. __("to").' '.$request->end_date.'.';
                    // SendTwilioMsg
                    event(new \Modules\Twilio\Events\SendTwilioMsg($emp->phone,$msg));
                }
            }
            }

            if(in_array('0', $request->employee_id))
            {
                $departmentEmployee = Employee::whereIn('department_id', $request->department_id)->where('workspace',getActiveWorkSpace())->get()->pluck('id');
                $departmentEmployee = $departmentEmployee;
            }
            else
            {
                $departmentEmployee = $request->employee_id;
            }

            foreach($departmentEmployee as $employee)
            {
                $announcementEmployee                  = new AnnouncementEmployee();
                $announcementEmployee->announcement_id = $announcement->id;
                $announcementEmployee->employee_id     = $employee;
                $announcementEmployee->workspace       = getActiveWorkSpace();
                $announcementEmployee->created_by      = \Auth::user()->id;
                $announcementEmployee->save();
            }

             //webhook
             if(module_is_active('Webhook')){
                if(in_array('0',$request->department_id))
                {
                    $department_name = 'All Departments';
                }
                else
                {
                    $department_name = 'Not Found';
                    $department = Department::whereIn('id',$request->department_id)->get()->pluck('name')->toArray();
                    if(count($department) > 0)
                    {
                        $department_name = implode(',',$department);
                    }
                }
                if(in_array('0',$request->employee_id))
                {
                    $employee_name = 'All Employees';
                }
                else
                {
                    $employee_name = 'Not Found';
                    $employee = Employee::whereIn('id',$request->employee_id)->get()->pluck('name')->toArray();
                    if(count($employee) > 0)
                    {
                        $employee_name = implode(',',$employee);
                    }
                }
                if($request->branch_id == '0')
                {
                    $branch_name = 'All Branch';
                }
                else
                {
                    $branch = Branch::where('id',$request->branch_id)->first();
                    $branch_name = $branch->name;
                }
                $announcement->branch_id = $branch_name;
                $announcement->department_id = $department_name;
                $announcement->employee_id = $employee_name;
                $action = 'New Announcement';
                $module = 'Hrm';
                event(new \Modules\Webhook\Events\SendWebhook($module ,$announcement,$action));
            }

            return redirect()->route('announcement.index')->with('success', __('Announcement  successfully created.'));
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
        return redirect()->route('announcement.index');
        return view('hrm::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if(Auth::user()->can('announcement edit'))
        {
            $announcement = Announcement::find($id);
            if($announcement->created_by == creatorId() && $announcement->workspace == getActiveWorkSpace())
            {
                $branch    = Branch::where('created_by', '=', creatorId())->where('workspace',getActiveWorkSpace())->get()->pluck('name', 'id');
                $branch->prepend('All', 0);
                $departments  = Department::where('created_by', '=', creatorId())->where('workspace',getActiveWorkSpace())->get()->pluck('name', 'id');
                $departments->prepend('All', 0);

                 return view('hrm::announcement.edit', compact('announcement', 'branch', 'departments'));
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
    public function update(Request $request, Announcement $announcement)
    {
        if(Auth::user()->can('announcement edit'))
        {
            if($announcement->created_by == creatorId() && $announcement->workspace == getActiveWorkSpace())
            {
                $validator = \Validator::make(
                    $request->all(), [
                                        'title' => 'required',
                                        'start_date' => 'required|date',
                                        'end_date' => 'required|after_or_equal:start_date',
                                        'branch_id' => 'required',
                                        'department_id' => 'required',

                                   ]
                );
                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $announcement->title         = $request->title;
                $announcement->start_date    = $request->start_date;
                $announcement->end_date      = $request->end_date;
                $announcement->branch_id     = $request->branch_id;
                $announcement->department_id = implode(",",$request->department_id);
                $announcement->description   = $request->description;

                $announcement->save();


                return redirect()->route('announcement.index')->with('success', __('Announcement successfully updated.'));
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

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy(Announcement $announcement)
    {
        if(Auth::user()->can('announcement delete'))
        {
            if($announcement->created_by == creatorId() && $announcement->workspace == getActiveWorkSpace())
            {
                $announcementemployee = AnnouncementEmployee::where('announcement_id',$announcement->id)->delete();
                $announcement->delete();

                return redirect()->route('announcement.index')->with('success', __('Announcement successfully deleted.'));
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
    public function getemployee(Request $request)
    {
        if(in_array('0', $request->department_id))
        {
            $employees = Employee::where('workspace',getActiveWorkSpace())->get()->pluck('name', 'id')->toArray();
        }
        else
        {

            $employees = Employee::where('workspace',getActiveWorkSpace())->whereIn('department_id', $request->department_id)->get()->pluck('name', 'id')->toArray();
        }
        return response()->json($employees);
    }
}

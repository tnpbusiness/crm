<?php

namespace Modules\Hrm\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Hrm\Entities\Branch;
use Modules\Hrm\Entities\CompanyPolicy;

class CompanyPolicyController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if(Auth::user()->can('companypolicy manage'))
        {
            $companyPolicy = CompanyPolicy::where('workspace',getActiveWorkSpace())->where('created_by', '=', creatorId())->get();
            return view('hrm::companyPolicy.index',compact('companyPolicy'));
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
        if (Auth::user()->can('companypolicy create'))
        {
            $branches     = Branch::where('created_by', '=', creatorId())->where('workspace',getActiveWorkSpace())->get()->pluck('name', 'id');
            return view('hrm::companyPolicy.create', compact('branches'));
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
        if (Auth::user()->can('companypolicy create'))
        {
            $validator = \Validator::make(
                $request->all(),
                [
                    'branch' => 'required',
                    'title' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            if (!empty($request->attachment))
            {
                $filenameWithExt = $request->file('attachment')->getClientOriginalName();
                $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension       = $request->file('attachment')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                $uplaod = upload_file($request,'attachment',$fileNameToStore,'companyPolicy');
                if($uplaod['flag'] == 1)
                {
                    $url = $uplaod['url'];
                }
                else
                {
                    return redirect()->back()->with('error',$uplaod['msg']);
                }
            }
                $policy              = new CompanyPolicy();
                $policy->branch      = $request->branch;
                $policy->title       = $request->title;
                $policy->description = !empty($request->description) ? $request->description : '';
                $policy->attachment  = !empty($request->attachment) ? $url : '';
                $policy->workspace  = getActiveWorkSpace();
                $policy->created_by  = creatorId();
                $policy->save();

                //slack
                if(module_is_active('Slack') && !empty(company_setting('New Company Policy')) && company_setting('New Company Policy')  == true)
                {
                    $branch = Branch::find($request->branch);
                    $msg = $request->title . ' ' . __("for") . ' ' . $branch->name . ' ' . __("created") . '.';
                    // SendSlackMsg
                    event(new \Modules\Slack\Events\SendSlackMsg($msg));
                }
                //telegram
                if(module_is_active('Telegram') && !empty(company_setting('Telegram New Company Policy')) && company_setting('Telegram New Company Policy')  == true)
                {
                    $branch = Branch::find($request->branch);
                    $msg = $request->title . ' ' . __("for") . ' ' . $branch->name . ' ' . __("created") . '.';
                    // SendTelegramMsg
                    event(new \Modules\Telegram\Events\SendTelegramMsg($msg));
                }
                 //twilio
                if(module_is_active('Twilio') && !empty(company_setting('Twilio New Company Policy')) && company_setting('Twilio New Company Policy')  == true)
                {
                    $to=Auth::user()->mobile_no;
                    $branch = Branch::find($request->branch);
                    $msg = $request->title . ' ' . __("for") . ' ' . $branch->name . ' ' . __("created") . '.';
                    // SendTwilioMsg
                    if(!empty($to)){
                        event(new \Modules\Twilio\Events\SendTwilioMsg($to,$msg));
                    }
                }
                //webhook
                if(module_is_active('Webhook')){
                    $branch = Branch::where('id',$request->branch)->first();
                    $policy->branch = $branch->name;
                    unset($policy->attachment);
                    $action = 'New Company Policy';
                    $module = 'Hrm';
                    event(new \Modules\Webhook\Events\SendWebhook($module ,$policy,$action));
                }
                return redirect()->route('company-policy.index')->with('success', __('Company policy successfully created.'));
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
        return redirect()->route('company-policy.index');
        return view('hrm::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit(CompanyPolicy $companyPolicy)
    {
        if (Auth::user()->can('companypolicy edit'))
        {
            $branches     = Branch::where('created_by', '=', creatorId())->where('workspace',getActiveWorkSpace())->get()->pluck('name', 'id');

            return view('hrm::companyPolicy.edit', compact('branches', 'companyPolicy'));
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
    public function update(Request $request, CompanyPolicy $companyPolicy)
    {
        if (Auth::user()->can('companypolicy edit'))
        {
            $validator = \Validator::make(
                $request->all(),
                [
                    'branch' => 'required',
                    'title' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            if (isset($request->attachment))
            {
                if(!empty($companyPolicy->attachment))
                {
                    delete_file($companyPolicy->attachment);
                }
                $filenameWithExt = $request->file('attachment')->getClientOriginalName();
                $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension       = $request->file('attachment')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                $uplaod = upload_file($request,'attachment',$fileNameToStore,'companyPolicy');
                if($uplaod['flag'] == 1)
                {
                    $url = $uplaod['url'];
                }
                else
                {
                    return redirect()->back()->with('error',$uplaod['msg']);
                }
            }

            $companyPolicy->branch      = $request->branch;
            $companyPolicy->title       = $request->title;
            $companyPolicy->description = $request->description;
            if (isset($request->attachment))
            {
                $companyPolicy->attachment = $url;
            }
            $companyPolicy->save();

            return redirect()->route('company-policy.index')->with('success', __('Company policy successfully updated.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy(CompanyPolicy $companyPolicy)
    {
        if (Auth::user()->can('companypolicy delete'))
        {
            if ($companyPolicy->created_by == creatorId() && $companyPolicy->workspace == getActiveWorkSpace())
            {
                if(!empty($companyPolicy->attachment))
                {
                    delete_file($companyPolicy->attachment);
                }
                $companyPolicy->delete();
                return redirect()->route('company-policy.index')->with('success', __('Company policy successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}

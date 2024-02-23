<?php

namespace Modules\Stripe\Http\Controllers;

use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Events\DefaultData;
use App\Events\GivePermissionToRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Rawilk\Settings\Support\Context;
use Modules\Coupons\Entities\UserCoupon;

class StripeController extends Controller
{
    public $stripe_key;
    public $stripe_secret;
    public $is_stripe_enabled;
    public $currancy;

    public function setting(Request $request)
    {
        if(Auth::user()->can('stripe manage'))
        {
        if($request->has('stripe_is_on'))
        {
            $validator = Validator::make($request->all(), [
                'stripe_key' => 'required|string',
                'stripe_secret' => 'required|string'
            ]);
            if($validator->fails()){
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
        }
            $userContext = new Context(['user_id' => \Auth::user()->id,'workspace_id'=>getActiveWorkSpace()]);
            if($request->has('stripe_is_on')){
                \Settings::context($userContext)->set('stripe_is_on', $request->stripe_is_on);
                \Settings::context($userContext)->set('stripe_key', $request->stripe_key);
                \Settings::context($userContext)->set('stripe_secret', $request->stripe_secret);
            }else{
                \Settings::context($userContext)->set('stripe_is_on', 'off');
            }
            return redirect()->back()->with('success','Stripe setting save sucessfully.');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function planPayWithStripe(Request $request)
    {
        $user = User::find(\Auth::user()->id);
        $plan = Plan::first();
        $authuser       = Auth::user();
        $user_counter = !empty($request->user_counter_input) ? $request->user_counter_input : 0;
        $user_module = !empty($request->user_module_input) ? $request->user_module_input : '';
        $duration = !empty($request->time_period) ? $request->time_period : 'Month';
        $user_module_price = 0;
        if(!empty($user_module))
        {
            $user_module_array =    explode(',',$user_module);
            foreach ($user_module_array as $key => $value)
            {
                $temp = ($duration == 'Year') ? ModulePriceByName($value)['yearly_price'] : ModulePriceByName($value)['monthly_price'];
                $user_module_price = $user_module_price + $temp;
            }
        }
        $user_price = 0;
        if($user_counter > 0)
        {
            $temp = ($duration == 'Year') ? $plan->price_per_user_yearly : $plan->price_per_user_monthly;

            $user_price = $user_counter * $temp;
        }
        $plan_price = ($duration == 'Year') ? $plan->package_price_yearly : $plan->package_price_monthly;

        $stripe_session = '';
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
        if ($plan)
        {
            /* Check for code usage */
            $plan->discounted_price = false;
            $payment_frequency      = $plan->duration;
            $price                  = $plan_price + $user_module_price + $user_price;
            if($price <= 0){
                 $assignPlan= DirectAssignPlan($duration,$user_module,$user_counter,'STRIPE');
                 if($assignPlan['is_success']){
                    return redirect()->route('plans.index')->with('success', __('Plan activated Successfully!'));
                 }else{
                    return redirect()->route('plans.index')->with('error', __('Something went wrong, Please try again,'));
                 }
            }
            try {

                $payment_plan = $duration;
                $payment_type = 'onetime';
                /* Payment details */
                $code = '';

                $product = 'Basic Package';

                /* Final price */
                $stripe_formatted_price = in_array(
                    admin_setting('defult_currancy'),
                    [
                        'MGA',
                        'BIF',
                        'CLP',
                        'PYG',
                        'DJF',
                        'RWF',
                        'GNF',
                        'UGX',
                        'JPY',
                        'VND',
                        'VUV',
                        'XAF',
                        'KMF',
                        'KRW',
                        'XOF',
                        'XPF',
                    ]
                ) ? number_format($price, 2, '.', '') : number_format($price, 2, '.', '') * 100;
                $return_url_parameters = function ($return_type) use ($payment_frequency, $payment_type) {
                    return '&return_type=' . $return_type . '&payment_processor=stripe&payment_frequency=' . $payment_frequency . '&payment_type=' . $payment_type;
                };
                /* Initiate Stripe */
                \Stripe\Stripe::setApiKey(admin_setting('stripe_secret'));

                $stripe_session = \Stripe\Checkout\Session::create(
                    [
                        'payment_method_types' => ['card'],
                        'line_items' => [
                            [
                                'name' => $product,
                                'description' => $payment_plan,
                                'amount' => (int)$stripe_formatted_price,
                                'currency' =>  admin_setting('defult_currancy'),
                                'quantity' => 1,
                            ],
                        ],
                        'metadata' => [
                            'user_id' => $authuser->id,
                            'package_id' => $plan->id,
                            'payment_frequency' => $payment_frequency,
                            'code' => $code,
                        ],
                        'success_url' => route(
                            'plan.get.payment.status',
                            [
                                'order_id' => $orderID,
                                'plan_id' => $plan->id,
                                'user_module' => $user_module,
                                'duration' => $duration,
                                'user_counter' => $user_counter,
                                $return_url_parameters('success'),
                            ]
                        ),
                        'cancel_url' => route(
                            'plan.get.payment.status',
                            [
                                'plan_id' => $orderID,
                                'order_id' => $plan->id,
                                $return_url_parameters('cancel'),
                            ]
                        ),
                    ]
                );
                Order::create(
                    [
                        'order_id' => $orderID,
                        'name' => null,
                        'email' => null,
                        'card_number' => null,
                        'card_exp_month' => null,
                        'card_exp_year' => null,
                        'plan_name' => $product,
                        'plan_id' => $plan->id,
                        'price' => !empty($price)?$price:0,
                        'price_currency' => admin_setting('defult_currancy'),
                        'txn_id' => '',
                        'payment_type' => __('STRIPE'),
                        'payment_status' => 'pending',
                        'receipt' => null,
                        'user_id' => $authuser->id,
                    ]
                );
                $request->session()->put('stripe_session',$stripe_session);
                $stripe_session = $stripe_session ?? false;
            } catch (\Exception $e) {
                \Log::debug($e->getMessage());
                return redirect()->route('plans.index')->with('error',$e->getMessage());
            }
            return view('stripe::plan.request',compact('stripe_session'));
        } else {
            return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
        }

    }

    public function planGetStripeStatus(Request $request)
    {
        \Log::debug((array)$request->all());
        try
            {
                $stripe = new \Stripe\StripeClient(!empty(admin_setting('stripe_secret')) ? admin_setting('stripe_secret') : '');
                  $paymentIntents = $stripe->paymentIntents->retrieve(
                    $request->session()->get('stripe_session')->payment_intent,
                    []
                  );
                  $receipt_url = $paymentIntents->charges->data[0]->receipt_url;
            }
        catch(\Exception $exception)
            {
                $receipt_url = "";
            }
            \Session::forget('stripe_session');
            $request->session()->forget('stripe_session');

        try {
            if ($request->return_type == 'success')
            {
                $Order = Order::where('order_id',$request->order_id)->first();
                $Order->payment_status =  'succeeded';
                $Order->receipt =  $receipt_url;
                $Order->save();

                $user = User::find(\Auth::user()->id);
                $assignPlan = $user->assignPlan($request->duration,$request->user_module,$request->user_counter);
                $value = Session::get('user-module-selection');
                if(!empty($value))
                {
                    Session::forget('user-module-selection');
                }
                if ($assignPlan['is_success']) {
                    return redirect()->route('plans.index')->with('success', __('Plan activated Successfully!'));
                } else {
                    return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
                }
            } else {
                return redirect()->route('plans.index')->with('error', __('Your Payment has failed!'));
            }
        } catch (\Exception $exception) {
            return redirect()->route('plans.index')->with('error', $exception->getMessage());
        }
    }

    public function payment_setting($id = null, $wokspace = Null)
    {
        if (!empty($id) && empty($wokspace)) {

            $this->is_stripe_enabled = !empty(company_setting('stripe_is_on', $id)) ? company_setting('stripe_is_on', $id) : 'off';
            $this->stripe_key        = !empty(company_setting('stripe_key', $id)) ? company_setting('stripe_key', $id) : 'off';
            $this->stripe_secret     = !empty(company_setting('stripe_secret', $id)) ? company_setting('stripe_secret', $id) : 'off';
            $this->currancy          = !empty(company_setting('defult_currancy', $id)) ? company_setting('defult_currancy', $id) : 'INR';
        } elseif (!empty($id) && !empty($wokspace)) {
            $this->is_stripe_enabled = !empty(company_setting('stripe_is_on', $id, $wokspace)) ? company_setting('stripe_is_on', $id, $wokspace) : 'off';
            $this->stripe_key        = !empty(company_setting('stripe_key', $id, $wokspace)) ? company_setting('stripe_key', $id, $wokspace) : 'off';
            $this->stripe_secret     = !empty(company_setting('stripe_secret', $id, $wokspace)) ? company_setting('stripe_secret', $id, $wokspace) : 'off';
            $this->currancy          = !empty(company_setting('defult_currancy', $id, $wokspace)) ? company_setting('defult_currancy', $id, $wokspace) : 'INR';
        } else {
            $this->currancy  = !empty(company_setting('defult_currancy')) ? company_setting('defult_currancy') : 'INR';
            $this->is_stripe_enabled = (company_setting('stripe_is_on')) ? company_setting('stripe_is_on') : 'off';
            $this->stripe_key        = (company_setting('stripe_key')) ? company_setting('stripe_key') : 'off';
            $this->stripe_secret     = (company_setting('stripe_secret')) ? company_setting('stripe_secret') : 'off';
        }
    }

    public function invoicePayWithStripe(Request $request)
    {

        if ($request->type == "invoice") {
            $invoice        = \App\Models\Invoice::find($request->invoice_id);
            $user_id        = $invoice->created_by;
            $wokspace        = $invoice->workspace;
        } elseif ($request->type == "salesinvoice") {
            $invoice        = \Modules\Sales\Entities\SalesInvoice::find($request->invoice_id);
            $user_id        = $invoice->created_by;
            $wokspace        = $invoice->workspace;
        }elseif ($request->type == "retainer") {
            $invoice        = \Modules\Retainer\Entities\Retainer::find($request->invoice_id);
            $user_id        = $invoice->created_by;
            $wokspace        = $invoice->workspace;
        }

        self::payment_setting($user_id, $wokspace);
        if (isset($this->is_stripe_enabled) && $this->is_stripe_enabled == 'on' && !empty($this->stripe_key) && !empty($this->stripe_secret)) {

            $user      = Auth::user();
            $validator = Validator::make(
                $request->all(),
                [
                    'amount' => 'required|numeric',
                    'invoice_id' => 'required',
                ]
            );
            if ($validator->fails()) {
                return redirect()->back()->with('error', $validator->errors()->first());
            }
            $authuser       = Auth::user();
            $comapany_stripe_data = '';
            $invoice_id     = $request->input('invoice_id');
            if ($request->type == "invoice") {

                $invoice        = \App\Models\Invoice::find($invoice_id);
                $invoice_payID  = $invoice->invoice_id;
                $invoiceID      = $invoice->id;
                $printID        = \App\Models\Invoice::invoiceNumberFormat($invoice_payID, $user_id, $wokspace);
            } elseif ($request->type == "salesinvoice") {
                $invoice        = \Modules\Sales\Entities\SalesInvoice::find($invoice_id);
                $invoice_payID  = $invoice->invoice_id;
                $invoiceID      = $invoice->id;
                $printID        = \Modules\Sales\Entities\SalesInvoice::invoiceNumberFormat($invoice_payID, $user_id, $wokspace);
            }
            elseif ($request->type == "retainer") {
                $invoice        = \Modules\Retainer\Entities\Retainer::find($invoice_id);
                $invoice_payID  = $invoice->invoice_id;
                $invoiceID      = $invoice->id;
                $printID        = \Modules\Retainer\Entities\Retainer::retainerNumberFormat($invoice_payID, $user_id, $wokspace);
            }
            if ($invoice) {

                /* Check for code usage */
                $price = $request->amount;

                try {

                    $stripe_formatted_price = in_array(
                        company_setting('defult_currancy', $user_id, $wokspace),
                        [
                            'MGA',
                            'BIF',
                            'CLP',
                            'PYG',
                            'DJF',
                            'RWF',
                            'GNF',
                            'UGX',
                            'JPY',
                            'VND',
                            'VUV',
                            'XAF',
                            'KMF',
                            'KRW',
                            'XOF',
                            'XPF',
                        ]
                    ) ? number_format($price, 2, '.', '') : number_format($price, 2, '.', '') * 100;

                    $return_url_parameters = function ($return_type) {
                        return '&return_type=' . $return_type;
                    };
                    /* Initiate Stripe */
                    \Stripe\Stripe::setApiKey(company_setting('stripe_secret', $user_id, $wokspace));
                    $code = '';

                    $comapany_stripe_data = \Stripe\Checkout\Session::create(
                        [
                            'payment_method_types' => ['card'],
                            'line_items' => [
                                [
                                    'name' => $printID,
                                    'amount' => (int)$stripe_formatted_price,
                                    'currency' => $this->currancy,
                                    'quantity' => 1,
                                ],
                            ],
                            'metadata' => [
                                'user_id' => isset($user->name) ? $user->name : 0,
                                'package_id' => $invoiceID,
                                'code' => $code,
                            ],
                            'success_url' => route(
                                'invoice.stripe',
                                [
                                    'invoice_id' => encrypt($invoiceID),
                                    $request->type,
                                    $return_url_parameters('success'),
                                ]
                            ),
                            'cancel_url' => route(
                                'invoice.stripe',
                                [
                                    'invoice_id' => encrypt($invoiceID),
                                    $return_url_parameters('cancel'),
                                ]
                            ),
                        ]

                    );

                    $data           = [
                        'amount' => $price,
                        'currency' => $this->currancy,
                        'stripe' => $comapany_stripe_data

                    ];
                    $request->session()->put('comapany_stripe_data', $data);

                    $comapany_stripe_data = $comapany_stripe_data ?? false;
                    return new RedirectResponse($comapany_stripe_data->url);
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', $e);
                    \Log::debug($e->getMessage());
                }
            } else {
                if ($request->type == 'invoice') {

                        return redirect()->route('pay.invoice', encrypt($invoiceID))->with('error', __('Invoice is deleted.'));

                } elseif ($request->type == 'salesinvoice') {

                        return redirect()->route('pay.salesinvoice', encrypt($invoiceID))->with('error', __('Salesinvoice is deleted.'));

                }
                elseif ($request->type == 'retainer') {

                    return redirect()->route('pay.retainer', encrypt($invoiceID))->with('error', __('Retainer is deleted.'));

                }
            }
        } else {
            return redirect()->back()->with('error', __('Please Enter Stripe Details.'));
        }
    }

    public function getInvoicePaymentStatus($invoice_id, Request $request, $type)
    {
        try {
            if ($request->return_type == 'success') {
                if ($type == 'invoice') {
                    if (!empty($invoice_id)) {
                        $invoice_id = decrypt($invoice_id);
                        $invoice    = \App\Models\Invoice::find($invoice_id);
                        \Log::debug((array)$request->all());
                        $session_data = $request->session()->get('comapany_stripe_data');
                        try {
                            $stripe = new \Stripe\StripeClient(!empty(company_setting('stripe_secret', $invoice->created_by, $invoice->workspace)) ? company_setting('stripe_secret', $invoice->created_by, $invoice->workspace) : '');
                            $paymentIntents = $stripe->paymentIntents->retrieve(
                                $session_data['stripe']->payment_intent,
                                []
                            );
                            $receipt_url = $paymentIntents->charges->data[0]->receipt_url;
                        } catch (\Exception $exception) {
                            $receipt_url = "";
                        }
                        Session::forget('comapany_stripe_data');
                        $request->session()->forget('comapany_stripe_data');
                        $get_data   = $session_data;
                        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

                        if ($invoice) {
                            try {
                                if ($request->return_type == 'success') {
                                    $invoice_payment                       = new \App\Models\InvoicePayment();
                                    $invoice_payment->invoice_id           = $invoice_id;
                                    $invoice_payment->date                 = date('Y-m-d');
                                    $invoice_payment->amount               = isset($get_data['amount']) ? $get_data['amount'] : 0;
                                    $invoice_payment->account_id           = 0;
                                    $invoice_payment->payment_method       = 0;
                                    $invoice_payment->order_id             = $orderID;
                                    $invoice_payment->currency             = isset($get_data['currency']) ? $get_data['currency'] : 'INR';
                                    $invoice_payment->payment_type         = __('STRIPE');
                                    $invoice_payment->receipt              = $receipt_url;
                                    $invoice_payment->save();

                                    $due     = $invoice->getDue();
                                    if ($due <= 0) {
                                        $invoice->status = 4;
                                        $invoice->save();
                                    } else {
                                        $invoice->status = 3;
                                        $invoice->save();
                                    }

                                    // slack
                                    if(module_is_active('Slack') && !empty(company_setting('Invoice Status Updated',$invoice->created_by,$invoice->workspace)) && company_setting('Invoice Status Updated',$invoice->created_by,$invoice->workspace)  == true)
                                    {
                                        $msg = "New payment of ". $invoice_payment->amount ." created for ". $invoice->customer->name ." by ". $invoice_payment->payment_type;
                                        // SendSlackMsg
                                        event(new \Modules\Slack\Events\SendSlackMsg($msg));
                                    }

                                    // telegram
                                    if(module_is_active('Telegram') && !empty(company_setting('Telegram Invoice Status Updated',$invoice->created_by,$invoice->workspace)) && company_setting('Telegram Invoice Status Updated',$invoice->created_by,$invoice->workspace)  == true)
                                    {
                                        $msg = "New payment of ". $invoice_payment->amount ." created for ". $invoice->customer->name ." by ". $invoice_payment->payment_type;
                                        // SendTelegramMsg
                                        event(new \Modules\Telegram\Events\SendTelegramMsg($msg));
                                    }

                                    // twilio
                                    if(module_is_active('Twilio') && !empty(company_setting('Twilio Invoice Status Updated',$invoice->created_by,$invoice->workspace)) && company_setting('Twilio Invoice Status Updated',$invoice->created_by,$invoice->workspace)  == true)
                                    {
                                        $Assign_user_phone = User::where('id',$invoice->created_by)->first();
                                        if(!empty($Assign_user_phone->mobile_no))
                                        {
                                            $msg = "New payment of ". $invoice_payment->amount ." created for ". $invoice->customer->name ." by ". $invoice_payment->payment_type;
                                            // SendTwilioMsg
                                            event(new \Modules\Twilio\Events\SendTwilioMsg($Assign_user_phone->mobile_no,$msg));
                                        }
                                    }

                                    // webhook
                                    if(module_is_active('Webhook')){
                                        $company_id = $invoice->created_by;
                                        $workspace_id = $invoice->workspace;
                                        $action = 'Invoice Status Updated';
                                        $module = 'general';
                                        event(new \Modules\Webhook\Events\SendWebhook($module ,$invoice_payment,$action,$company_id,$workspace_id));
                                    }

                                    return redirect()->route('pay.invoice', \Illuminate\Support\Facades\Crypt::encrypt($invoice->id))->with('success', __('Payment added Successfully'));
                                } else {
                                    return redirect()->route('pay.invoice', \Illuminate\Support\Facades\Crypt::encrypt($invoice->id))->with('error', __('Transaction has been failed!'));
                                }
                            } catch (\Exception $e) {

                                return redirect()->route('pay.invoice', \Illuminate\Support\Facades\Crypt::encrypt($invoice->id))->with('error', __('Transaction has been failed!'));
                            }
                        } else {


                            return redirect()->route('pay.invoice', \Illuminate\Support\Facades\Crypt::encrypt($invoice->id))->with('error', __('Invoice not found.'));
                        }
                    } else {

                        return redirect()->route('pay.invoice', \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('error', __('Invoice not found.'));
                    }
                } elseif ($type == 'salesinvoice') {
                    if (!empty($invoice_id)) {
                        $invoice_id = decrypt($invoice_id);
                        $invoice    = \Modules\Sales\Entities\SalesInvoice::find($invoice_id);
                        \Log::debug((array)$request->all());
                        $session_data = $request->session()->get('comapany_stripe_data');
                        try {
                            $stripe = new \Stripe\StripeClient(!empty(company_setting('stripe_secret', $invoice->created_by, $invoice->workspace)) ? company_setting('stripe_secret', $invoice->created_by, $invoice->workspace) : '');
                            $paymentIntents = $stripe->paymentIntents->retrieve(
                                $session_data['stripe']->payment_intent,
                                []
                            );
                            $receipt_url = $paymentIntents->charges->data[0]->receipt_url;
                        } catch (\Exception $exception) {

                            $receipt_url = "";
                        }
                        Session::forget('comapany_stripe_data');
                        $request->session()->forget('comapany_stripe_data');
                        $get_data   = $session_data;
                        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
                        if ($invoice) {
                            try {
                                if ($request->return_type == 'success') {
                                    $salesinvoice_payment                     = new \Modules\Sales\Entities\SalesInvoicePayment();
                                    $salesinvoice_payment->transaction_id     = $invoice_id;
                                    $salesinvoice_payment->client_id          = 0;
                                    $salesinvoice_payment->invoice_id         = $invoice_id;
                                    $salesinvoice_payment->amount             = isset($get_data['amount']) ? $get_data['amount'] : 0;
                                    $salesinvoice_payment->date               = date('Y-m-d');
                                    $salesinvoice_payment->payment_type       = __('STRIPE');
                                    $salesinvoice_payment->notes              = '';
                                    $salesinvoice_payment->receipt            = $receipt_url;
                                    $salesinvoice_payment->save();
                                    $due     = $invoice->getDue();
                                    if ($due <= 0) {
                                        $invoice->status = 3;
                                        $invoice->save();
                                    } else {
                                        $invoice->status = 2;
                                        $invoice->save();
                                    }

                                    // slack
                                    if(module_is_active('Slack') && !empty(company_setting('New Sales Invoice Payment',$invoice->created_by,$invoice->workspace)) && company_setting('New Sales Invoice Payment',$invoice->created_by,$invoice->workspace)  == true)
                                    {
                                        $msg ="New payment of ". $salesinvoice_payment->amount ." created for ". $invoice->customer->name ." by ". $salesinvoice_payment->payment_type;
                                        // SendSlackMsg
                                        event(new \Modules\Slack\Events\SendSlackMsg($msg));
                                    }

                                    // telegram
                                    if(module_is_active('Telegram') && !empty(company_setting('Telegram New Sales Invoice Payment',$invoice->created_by,$invoice->workspace)) && company_setting('Telegram New Sales Invoice Payment',$invoice->created_by,$invoice->workspace)  == true)
                                    {
                                        $msg ="New payment of ". $salesinvoice_payment->amount ." created for ". $invoice->customer->name ." by ". $salesinvoice_payment->payment_type;
                                        // SendTelegramMsg
                                        event(new \Modules\Telegram\Events\SendTelegramMsg($msg));
                                    }

                                    // twilio
                                    if(module_is_active('Twilio') && !empty(company_setting('Twilio New Sales Invoice Payment',$invoice->created_by,$invoice->workspace)) && company_setting('Twilio New Sales Invoice Payment',$invoice->created_by,$invoice->workspace)  == true)
                                    {
                                        $Assign_user_phone = User::where('id',$invoice->created_by)->first();
                                        if(!empty($Assign_user_phone->mobile_no))
                                        {
                                            $msg ="New payment of ". $salesinvoice_payment->amount ." created for ". $invoice->customer->name ." by ". $salesinvoice_payment->payment_type;
                                            // SendTwilioMsg
                                            event(new \Modules\Twilio\Events\SendTwilioMsg($Assign_user_phone->mobile_no,$msg));
                                        }
                                    }

                                    // webhook
                                    if(module_is_active('Webhook')){
                                        $salesinvoice_payment->invoice_id = $invoice->name;
                                        $company_id = $invoice->created_by;
                                        $workspace_id = $invoice->workspace;
                                        $action = 'New Sales Invoice Payment';
                                        $module = 'Sales';
                                        event(new \Modules\Webhook\Events\SendWebhook($module ,$salesinvoice_payment,$action,$company_id,$workspace_id));
                                    }

                                    return redirect()->route('pay.salesinvoice', \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('success', __('Payment added Successfully'));
                                } else {

                                    return redirect()->route('pay.salesinvoice', \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('error', __('Transaction has been failed!'));
                                }
                            } catch (\Exception $e) {
                                return redirect()->route('pay.salesinvoice', \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('error', __('Transaction has been failed!'));
                            }
                        } else {
                            return redirect()->route('pay.salesinvoice', \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('error', __('Invoice not found.'));
                        }
                    } else {
                        return redirect()->route('pay.salesinvoice', \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('error', __('Invoice not found.'));
                    }
                } elseif ($type == 'retainer') {
                    if (!empty($invoice_id)) {
                        $invoice_id = decrypt($invoice_id);
                        $invoice    = \Modules\Retainer\Entities\Retainer::find($invoice_id);

                        \Log::debug((array)$request->all());
                        $session_data = $request->session()->get('comapany_stripe_data');
                        try {
                            $stripe = new \Stripe\StripeClient(!empty(company_setting('stripe_secret', $invoice->created_by, $invoice->workspace)) ? company_setting('stripe_secret', $invoice->created_by, $invoice->workspace) : '');

                            $paymentIntents = $stripe->paymentIntents->retrieve(
                                $session_data['stripe']->payment_intent,
                                []
                            );

                            $receipt_url = $paymentIntents->charges->data[0]->receipt_url;


                        } catch (\Exception $exception) {


                            $receipt_url = "";
                        }
                        Session::forget('comapany_stripe_data');
                        $request->session()->forget('comapany_stripe_data');
                        $get_data   = $session_data;
                        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
                        if ($invoice) {
                            try {
                                if ($request->return_type == 'success') {
                                    $retainer_payment                     = new \Modules\Retainer\Entities\RetainerPayment();
                                    $retainer_payment->retainer_id           = $invoice_id;
                                    $retainer_payment->date                 = date('Y-m-d');
                                    $retainer_payment->amount               = isset($get_data['amount']) ? $get_data['amount'] : 0;
                                    $retainer_payment->account_id           = 0;
                                    $retainer_payment->payment_method       = 0;
                                    $retainer_payment->order_id             = $orderID;
                                    $retainer_payment->currency             = isset($get_data['currency']) ? $get_data['currency'] : 'INR';
                                    $retainer_payment->payment_type         = __('STRIPE');
                                    $retainer_payment->receipt              = $receipt_url;
                                    $retainer_payment->save();

                                    $due     = $invoice->getDue();
                                    if ($due <= 0) {
                                        $invoice->status = 3;

                                        $invoice->save();
                                    } else {
                                        $invoice->status = 2;
                                        $invoice->save();
                                    }

                                    // slack
                                    if(module_is_active('Slack') && !empty(company_setting('New Retainer Payment',$invoice->created_by,$invoice->workspace)) && company_setting('New Retainer Payment',$invoice->created_by,$invoice->workspace)  == true)
                                    {
                                         $msg = "New payment of ". $retainer_payment->amount ." created for ". $invoice->customer->name ." by ". $retainer_payment->payment_type;
                                        // SendSlackMsg
                                        event(new \Modules\Slack\Events\SendSlackMsg($msg));
                                    }

                                    // telegram
                                    if(module_is_active('Telegram') && !empty(company_setting('Telegram New Retainer Payment',$invoice->created_by,$invoice->workspace)) && company_setting('Telegram New Retainer Payment',$invoice->created_by,$invoice->workspace)  == true)
                                    {
                                         $msg = "New payment of ". $retainer_payment->amount ." created for ". $invoice->customer->name ." by ". $retainer_payment->payment_type;
                                        // SendTelegramMsg
                                        event(new \Modules\Telegram\Events\SendTelegramMsg($msg));
                                    }

                                    // twilio
                                    if(module_is_active('Twilio') && !empty(company_setting('Twilio New Retainer Payment',$invoice->created_by,$invoice->workspace)) && company_setting('Twilio New Retainer Payment',$invoice->created_by,$invoice->workspace)  == true)
                                    {
                                        $Assign_user_phone = User::where('id',$invoice->created_by)->first();
                                        if(!empty($Assign_user_phone->mobile_no))
                                        {
                                             $msg = "New payment of ". $retainer_payment->amount ." created for ". $invoice->customer->name ." by ". $retainer_payment->payment_type;
                                            // SendTwilioMsg
                                            event(new \Modules\Twilio\Events\SendTwilioMsg($Assign_user_phone->mobile_no,$msg));
                                        }
                                    }

                                    // webhook
                                    if(module_is_active('Webhook')){
                                        $company_id = $invoice->created_by;
                                        $workspace_id = $invoice->workspace;
                                        $action = 'New Retainer Payment';
                                        $module = 'Retainer';
                                        event(new \Modules\Webhook\Events\SendWebhook($module ,$retainer_payment,$action,$company_id,$workspace_id));
                                    }

                                    return redirect()->route('pay.retainer', \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('success', __('Payment added Successfully'));
                                } else {

                                    return redirect()->route('pay.retainer', \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('error', __('Transaction has been failed!'));
                                }
                            } catch (\Exception $e) {

                                return redirect()->route('pay.retainer', \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('error', __('Transaction has been failed!'));
                            }
                        } else {
                            return redirect()->route('pay.retainer', \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('error', __('Retainer not found.'));
                        }
                    } else {
                        return redirect()->route('pay.retainer', \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('error', __('Retainer not found.'));
                    }
                }



                else {

                    return redirect()->back()->with('error', __('Oops something went wrong.'));
                }
            } else {

                return redirect()->back()->with('error', __('Transaction has been failed.'));
            }
        } catch (\Exception $exception) {

            return redirect()->back()->with('error', $exception->getMessage());
        }
    }

}

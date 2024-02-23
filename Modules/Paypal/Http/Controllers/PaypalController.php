<?php

namespace Modules\Paypal\Http\Controllers;

use App\Models\Plan;
use App\Models\Order;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rawilk\Settings\Support\Context;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PayPal\Rest\ApiContext;
use Illuminate\Support\Facades\Session;
use Modules\Paypal\Entities\PaypalUtility;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaypalController extends Controller
{


    // private $_api_context;
    protected $invoiceData;
    public $paypal_mode;
    public $paypal_client_id;
    public $paypal_secret_key;
    public $enable_paypal;
    public $currancy;
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function setting(Request $request)
    {

        if (Auth::user()->can('paypal manage')) {
            if ($request->has('paypal_payment_is_on')) {
                $validator = Validator::make($request->all(),
                [
                    'company_paypal_mode' => 'required|string',
                    'company_paypal_client_id' => 'required|string',
                    'company_paypal_secret_key' => 'required|string',
                ]);
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }
            }

            $userContext = new Context(['user_id' => \Auth::user()->id,'workspace_id'=>getActiveWorkSpace()]);
            if($request->has('paypal_payment_is_on')){
                \Settings::context($userContext)->set('paypal_payment_is_on', $request->paypal_payment_is_on);
                \Settings::context($userContext)->set('company_paypal_mode', $request->company_paypal_mode);
                \Settings::context($userContext)->set('company_paypal_client_id', $request->company_paypal_client_id);
                \Settings::context($userContext)->set('company_paypal_secret_key', $request->company_paypal_secret_key);
            }else{
                \Settings::context($userContext)->set('paypal_payment_is_on', 'off');
            }

            return redirect()->back()->with('success', __('Paypal Setting save successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    // get paypal payment setting
    public function paymentConfig($id=null, $workspace=Null)
    {
        if(!empty($id) && empty($workspace))
        {
            $this->currancy  = !empty(company_setting('defult_currancy',$id)) ? company_setting('defult_currancy',$id) : '$';
            $this->enable_paypal = !empty(company_setting('paypal_payment_is_on',$id)) ? company_setting('paypal_payment_is_on',$id) : 'off';

            if(company_setting('company_paypal_mode',$id) == 'live')
            {
                config(
                    [
                        'paypal.live.client_id' => !empty(company_setting('company_paypal_client_id',$id)) ? company_setting('company_paypal_client_id',$id) : '',
                        'paypal.live.client_secret' => !empty(company_setting('company_paypal_secret_key',$id)) ? company_setting('company_paypal_secret_key',$id) : '',
                        'paypal.mode' => !empty(company_setting('company_paypal_mode',$id)) ? company_setting('company_paypal_mode',$id) : '',
                    ]
                );
            }
            else{
                config(
                    [
                        'paypal.sandbox.client_id' => !empty(company_setting('company_paypal_client_id',$id)) ? company_setting('company_paypal_client_id',$id) : '',
                        'paypal.sandbox.client_secret' => !empty(company_setting('company_paypal_secret_key',$id)) ? company_setting('company_paypal_secret_key',$id) : '',
                        'paypal.mode' => !empty(company_setting('company_paypal_mode',$id)) ? company_setting('company_paypal_mode',$id) : '',
                    ]
                );
            }
        }elseif(!empty($id) && !empty($workspace)){
            $this->currancy  = !empty(company_setting('defult_currancy',$id,$workspace)) ? company_setting('defult_currancy',$id,$workspace) : '$';
            $this->enable_paypal = !empty(company_setting('paypal_payment_is_on',$id,$workspace)) ? company_setting('paypal_payment_is_on',$id,$workspace) : 'off';

            if(company_setting('company_paypal_mode',$id,$workspace) == 'live')
            {
                config(
                    [
                        'paypal.live.client_id' => !empty(company_setting('company_paypal_client_id',$id,$workspace)) ? company_setting('company_paypal_client_id',$id,$workspace) : '',
                        'paypal.live.client_secret' => !empty(company_setting('company_paypal_secret_key',$id,$workspace)) ? company_setting('company_paypal_secret_key',$id,$workspace) : '',
                        'paypal.mode' => !empty(company_setting('company_paypal_mode',$id,$workspace)) ? company_setting('company_paypal_mode',$id,$workspace) : '',
                    ]
                );
            }
            else{
                config(
                    [
                        'paypal.sandbox.client_id' => !empty(company_setting('company_paypal_client_id',$id,$workspace)) ? company_setting('company_paypal_client_id',$id,$workspace) : '',
                        'paypal.sandbox.client_secret' => !empty(company_setting('company_paypal_secret_key',$id,$workspace)) ? company_setting('company_paypal_secret_key',$id,$workspace) : '',
                        'paypal.mode' => !empty(company_setting('company_paypal_mode',$id,$workspace)) ? company_setting('company_paypal_mode',$id,$workspace) : '',
                    ]
                );
            }
        }
        else{
            $this->currancy  = !empty(company_setting('defult_currancy')) ? company_setting('defult_currancy') : '$';
            $this->enable_paypal = !empty(company_setting('paypal_payment_is_on')) ? company_setting('paypal_payment_is_on') : 'off';

            if(company_setting('company_paypal_mode') == 'live')
            {
                config(
                    [
                        'paypal.live.client_id' => !empty(company_setting('company_paypal_client_id')) ? company_setting('company_paypal_client_id') : '',
                        'paypal.live.client_secret' => !empty(company_setting('company_paypal_secret_key')) ? company_setting('company_paypal_secret_key') : '',
                        'paypal.mode' => !empty(company_setting('company_paypal_mode')) ? company_setting('company_paypal_mode') : '',
                    ]
                );
            }
            else{
                config(
                    [
                        'paypal.sandbox.client_id' => !empty(company_setting('company_paypal_client_id')) ? company_setting('company_paypal_client_id') : '',
                        'paypal.sandbox.client_secret' => !empty(company_setting('company_paypal_secret_key')) ? company_setting('company_paypal_secret_key') : '',
                        'paypal.mode' => !empty(company_setting('company_paypal_mode')) ? company_setting('company_paypal_mode') : '',
                    ]
                );
            }
        }
    }

    public function invoicePayWithPaypal(Request $request)
    {
        $user    = \Auth::user();
        $validator = Validator::make(
            $request->all(),
            ['amount' => 'required|numeric', 'invoice_id' => 'required']
        );
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }
        $invoice_id = $request->input('invoice_id');
        $type = $request->type;
        if($type == 'invoice')
        {
            $invoice = \App\Models\Invoice::find($invoice_id);
            $user_id = $invoice->created_by;
            $workspace = $invoice->workspace;
            $payment_id = $invoice->id;
        }
        elseif($type == 'salesinvoice') {

            $invoice = \Modules\Sales\Entities\SalesInvoice::find($invoice_id);
            $user_id = $invoice->created_by;
            $workspace = $invoice->workspace;
            $payment_id = $invoice->id;

        }
        elseif($type == 'retainer') {

            $invoice = \Modules\Retainer\Entities\Retainer::find($invoice_id);
            $user_id = $invoice->created_by;
            $workspace = $invoice->workspace;
            $payment_id = $invoice->id;
        }

        $this->invoiceData  = $invoice;
        $this->paymentConfig($user_id,$workspace);
        $get_amount = $request->amount;
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));

        if ($invoice) {
            if ($get_amount > $invoice->getDue()) {
                return redirect()->back()->with('error', __('Invalid amount.'));
            } else {
                $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

                $name = isset($user->name)?$user->name:'public' . " - " . $invoice_id;
                $paypalToken = $provider->getAccessToken();
                $response = $provider->createOrder([
                    "intent" => "CAPTURE",
                    "application_context" => [
                        "return_url" => route('invoice.paypal',[$payment_id,$get_amount, $type]),
                        "cancel_url" =>  route('invoice.paypal',[$payment_id,$get_amount, $type]),
                    ],
                    "purchase_units" => [
                        0 => [
                            "amount" => [
                                "currency_code" => $this->currancy = company_setting('defult_currancy', $user_id),

                                "value" => $get_amount
                            ]
                        ]
                    ]
                ]);

                if (isset($response['id']) && $response['id'] != null) {
                    // redirect to approve href
                    foreach ($response['links'] as $links) {
                        if ($links['rel'] == 'approve') {
                            return redirect()->away($links['href']);
                        }
                    }
                    return redirect()->back()->with('error', 'Something went wrong.');
                }
                else {
                    if($request->type == 'invoice'){
                        return redirect()->route('invoice.show', $invoice_id)->with('error', $response['message'] ?? 'Something went wrong.');
                    }
                    elseif($request->type == 'salesinvoice'){
                        return redirect()->route('salesinvoice.show', $invoice_id)->with('error', $response['message'] ?? 'Something went wrong.');
                    }
                    elseif($request->type == 'retainer'){
                        return redirect()->route('retainer.show', $invoice_id)->with('error', $response['message'] ?? 'Something went wrong.');
                    }
                }

                return redirect()->back()->with('error', __('Unknown error occurred'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function getInvoicePaymentStatus(Request $request, $invoice_id, $amount,$type)
    {
        if($type == 'invoice')
        {
            $invoice = \App\Models\Invoice::find($invoice_id);
            $this->paymentConfig($invoice->created_by,$invoice->workspace);
            $this->invoiceData  = $invoice;

            if ($invoice) {
                $payment_id = Session::get('paypal_payment_id');
                Session::forget('paypal_payment_id');
                if (empty($request->PayerID || empty($request->token))) {
                    return redirect()->route('invoice.show', $invoice_id)->with('error', __('Payment failed'));
                }
                $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
                try {
                    $invoice_payment                 = new \App\Models\InvoicePayment();
                    $invoice_payment->invoice_id     = $invoice_id;
                    $invoice_payment->date           = Date('Y-m-d');
                    $invoice_payment->account_id     = 0;
                    $invoice_payment->payment_method = 0;
                    $invoice_payment->amount         = $amount;
                    $invoice_payment->order_id       = $orderID;
                    $invoice_payment->currency       = $this->currancy;
                    $invoice_payment->payment_type = 'PAYPAL';
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

                    return redirect()->route('pay.invoice',\Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('success', __('Invoice paid Successfully!'));

                } catch (\Exception $e) {
                    return redirect()->route('pay.invoice', \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('success',$e->getMessage());
                }
            } else {
                return redirect()->route('pay.invoice', \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('success', __('Invoice not found.'));
            }

        }
        elseif($type == 'salesinvoice')
        {
            $salesinvoice = \Modules\Sales\Entities\SalesInvoice::find($invoice_id);
            $this->paymentConfig($salesinvoice->created_by,$salesinvoice->workspace);

            $this->invoiceData  = $salesinvoice;
            if ($salesinvoice)
            {
                $payment_id = Session::get('paypal_payment_id');
                Session::forget('paypal_payment_id');
                if (empty($request->PayerID || empty($request->token))) {
                    return redirect()->route('salesinvoice.show', $invoice_id)->with('error', __('Payment failed'));
                }

                try {
                    $salesinvoice_payment                 = new \Modules\Sales\Entities\SalesInvoicePayment();
                    $salesinvoice_payment->invoice_id     = $invoice_id;
                    $salesinvoice_payment->transaction_id = app('Modules\Sales\Http\Controllers\SalesInvoiceController')->transactionNumber($salesinvoice->created_by);
                    $salesinvoice_payment->date           = Date('Y-m-d');
                    $salesinvoice_payment->amount         = $amount;
                    $salesinvoice_payment->client_id      = 0;
                    $salesinvoice_payment->payment_type   = 'PAYPAL';
                    $salesinvoice_payment->save();
                    $due     = $salesinvoice->getDue();
                    if ($due <= 0) {
                        $salesinvoice->status = 3;
                        $salesinvoice->save();
                    } else {
                        $salesinvoice->status = 2;
                        $salesinvoice->save();
                    }

                    // slack
                    if(module_is_active('Slack') && !empty(company_setting('New Sales Invoice Payment',$salesinvoice->created_by,$salesinvoice->workspace)) && company_setting('New Sales Invoice Payment',$salesinvoice->created_by,$salesinvoice->workspace)  == true)
                    {
                        $msg ="New payment of ". $salesinvoice_payment->amount ." created for ". $salesinvoice->customer->name ." by ". $salesinvoice_payment->payment_type;
                        // SendSlackMsg
                        event(new \Modules\Slack\Events\SendSlackMsg($msg));
                    }

                    // telegram
                    if(module_is_active('Telegram') && !empty(company_setting('Telegram New Sales Invoice Payment',$salesinvoice->created_by,$salesinvoice->workspace)) && company_setting('Telegram New Sales Invoice Payment',$salesinvoice->created_by,$salesinvoice->workspace)  == true)
                    {
                        $msg ="New payment of ". $salesinvoice_payment->amount ." created for ". $salesinvoice->customer->name ." by ". $salesinvoice_payment->payment_type;
                        // SendTelegramMsg
                        event(new \Modules\Telegram\Events\SendTelegramMsg($msg));
                    }

                    // twilio
                    if(module_is_active('Twilio') && !empty(company_setting('Twilio New Sales Invoice Payment',$salesinvoice->created_by,$salesinvoice->workspace)) && company_setting('Twilio New Sales Invoice Payment',$salesinvoice->created_by,$salesinvoice->workspace)  == true)
                    {
                        $Assign_user_phone = User::where('id',$salesinvoice->created_by)->first();
                        if(!empty($Assign_user_phone->mobile_no))
                        {
                            $msg ="New payment of ". $salesinvoice_payment->amount ." created for ". $salesinvoice->customer->name ." by ". $salesinvoice_payment->payment_type;
                            // SendTwilioMsg
                            event(new \Modules\Twilio\Events\SendTwilioMsg($Assign_user_phone->mobile_no,$msg));
                        }
                    }

                    // webhook
                    if(module_is_active('Webhook')){
                        $salesinvoice_payment->invoice_id = $salesinvoice->name;
                        $company_id = $salesinvoice->created_by;
                        $workspace_id = $salesinvoice->workspace;
                        $action = 'New Sales Invoice Payment';
                        $module = 'Sales';
                        event(new \Modules\Webhook\Events\SendWebhook($module ,$salesinvoice_payment,$action,$company_id,$workspace_id));
                    }

                    return redirect()->route('pay.salesinvoice', \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('success', __('Sales Invoice paid Successfully!'));

                } catch (\Exception $e) {

                    return redirect()->route('pay.salesinvoice',  \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('success',$e->getMessage());
                }
            } else {

                return redirect()->route('pay.salesinvoice',  \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('success', __('Sales Invoice not found.'));
            }
        }


        elseif($type == 'retainer')
        {
            $retainer = \Modules\Retainer\Entities\Retainer::find($invoice_id);
            $this->paymentConfig($retainer->created_by,$retainer->workspace);

            $this->invoiceData  = $retainer;
            if ($retainer)
            {
                $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
                $payment_id = Session::get('paypal_payment_id');
                Session::forget('paypal_payment_id');
                if (empty($request->PayerID || empty($request->token))) {
                    return redirect()->route('retainer.show', $invoice_id)->with('error', __('Payment failed'));
                }

                try {
                    $retainer_payment                 = new \Modules\Retainer\Entities\RetainerPayment();
                    $retainer_payment->retainer_id     = $invoice_id;
                    $retainer_payment->date           = Date('Y-m-d');
                    $retainer_payment->account_id     = 0;
                    $retainer_payment->payment_method = 0;
                    $retainer_payment->amount         = $amount;
                    $retainer_payment->order_id       = $orderID;
                    $retainer_payment->currency       = $this->currancy;
                    $retainer_payment->payment_type = 'PAYPAL';
                    $retainer_payment->save();
                    $due     = $retainer->getDue();
                    if ($due <= 0) {
                        $retainer->status = 3;
                        $retainer->save();
                    } else {
                        $retainer->status = 2;
                        $retainer->save();
                    }

                    // slack
                    if(module_is_active('Slack') && !empty(company_setting('New Retainer Payment',$retainer->created_by,$retainer->workspace)) && company_setting('New Retainer Payment',$retainer->created_by,$retainer->workspace)  == true)
                    {
                        $msg = "New payment of ". $retainer_payment->amount ." created for ". $retainer->customer->name ." by ". $retainer_payment->payment_type;
                        // SendSlackMsg
                        event(new \Modules\Slack\Events\SendSlackMsg($msg));
                    }

                    // telegram
                    if(module_is_active('Telegram') && !empty(company_setting('Telegram New Retainer Payment',$retainer->created_by,$retainer->workspace)) && company_setting('Telegram New Retainer Payment',$retainer->created_by,$retainer->workspace)  == true)
                    {
                        $msg = "New payment of ". $retainer_payment->amount ." created for ". $retainer->customer->name ." by ". $retainer_payment->payment_type;
                        // SendTelegramMsg
                        event(new \Modules\Telegram\Events\SendTelegramMsg($msg));
                    }

                    // twilio
                    if(module_is_active('Twilio') && !empty(company_setting('Twilio New Retainer Payment',$retainer->created_by,$retainer->workspace)) && company_setting('Twilio New Retainer Payment',$retainer->created_by,$retainer->workspace)  == true)
                    {
                        $Assign_user_phone = User::where('id',$retainer->created_by)->first();
                        if(!empty($Assign_user_phone->mobile_no))
                        {
                            $msg = "New payment of ". $retainer_payment->amount ." created for ". $retainer->customer->name ." by ". $retainer_payment->payment_type;
                            // SendTwilioMsg
                            event(new \Modules\Twilio\Events\SendTwilioMsg($Assign_user_phone->mobile_no,$msg));
                        }
                    }

                    // webhook
                    if(module_is_active('Webhook')){
                        $company_id = $retainer->created_by;
                        $workspace_id = $retainer->workspace;
                        $action = 'New Retainer Payment';
                        $module = 'Retainer';
                        event(new \Modules\Webhook\Events\SendWebhook($module ,$retainer_payment,$action,$company_id,$workspace_id));
                    }

                    return redirect()->route('pay.retainer', \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('success', __('Retainer paid Successfully!'));

                } catch (\Exception $e) {

                    return redirect()->route('pay.retainer',  \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('success',$e->getMessage());
                }
            } else {

                return redirect()->route('pay.retainer',  \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('success', __('Retainer not found.'));
            }
        }
    }

    public function planPayWithPaypal(Request $request)
    {
        $plan = Plan::first();

        $user_counter = !empty($request->user_counter_input) ? $request->user_counter_input : 0;
        $user_module = !empty($request->user_module_input) ? $request->user_module_input : '0';
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

        if(admin_setting('company_paypal_mode') == 'live')
        {
            config(
                [
                    'paypal.live.client_id' => !empty(admin_setting('company_paypal_client_id')) ? admin_setting('company_paypal_client_id') : '',
                    'paypal.live.client_secret' => !empty(admin_setting('company_paypal_secret_key')) ? admin_setting('company_paypal_secret_key') : '',
                    'paypal.mode' => !empty(admin_setting('company_paypal_mode')) ? admin_setting('company_paypal_mode') : '',
                ]
            );
        }
        else{
            config(
                [
                    'paypal.sandbox.client_id' => !empty(admin_setting('company_paypal_client_id')) ? admin_setting('company_paypal_client_id') : '',
                    'paypal.sandbox.client_secret' => !empty(admin_setting('company_paypal_secret_key')) ? admin_setting('company_paypal_secret_key') : '',
                    'paypal.mode' => !empty(admin_setting('company_paypal_mode')) ? admin_setting('company_paypal_mode') : '',
                ]
            );
        }
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        if ($plan) {
            try {
                $price     = $plan_price + $user_module_price + $user_price;

                if($price <= 0){
                    $assignPlan= DirectAssignPlan($duration,$user_module,$user_counter,'PAYPAL');
                    if($assignPlan['is_success']){
                       return redirect()->route('plans.index')->with('success', __('Plan activated Successfully!'));
                    }else{
                       return redirect()->route('plans.index')->with('error', __('Something went wrong, Please try again,'));
                    }
                }
                $paypalToken = $provider->getAccessToken();
                $response = $provider->createOrder([
                    "intent" => "CAPTURE",
                    "application_context" => [
                        "return_url" => route('plan.get.paypal.status', [
                                    $plan->id,
                                    'amount' => $price,
                                    'user_module' => $user_module,
                                    'user_counter' => $user_counter,
                                    'duration' => $duration,
                    ]),
                        "cancel_url" =>  route('plan.get.paypal.status', [
                            $plan->id,
                                    'amount' => $price,
                                    'user_module' => $user_module,
                                    'user_counter' => $user_counter,
                                    'duration' => $duration,

                        ]),
                    ],
                    "purchase_units" => [
                        0 => [
                            "amount" => [
                                "currency_code" => admin_setting('defult_currancy'),
                                "value" => $price,

                            ]
                        ]
                    ]
                ]);
                if (isset($response['id']) && $response['id'] != null) {
                    // redirect to approve href
                    foreach ($response['links'] as $links) {
                        if ($links['rel'] == 'approve') {
                            return redirect()->away($links['href']);
                        }
                    }
                    return redirect()
                        ->route('plans.index', \Illuminate\Support\Facades\Crypt::encrypt($plan->id))
                        ->with('error', 'Something went wrong. OR Unknown error occurred');
                } else {
                    return redirect()
                        ->route('plans.index', \Illuminate\Support\Facades\Crypt::encrypt($plan->id))
                        ->with('error', $response['message'] ?? 'Something went wrong.');
                }

            } catch (\Exception $e) {
                return redirect()->route('plans.index')->with('error', __($e->getMessage()));
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
        }
    }

    public function planGetPaypalStatus(Request $request, $plan_id)
    {
        $user = Auth::user();
        $plan = Plan::find($plan_id);
        if ($plan)
        {
            if(admin_setting('company_paypal_mode') == 'live')
            {
                config(
                    [
                        'paypal.live.client_id' => !empty(admin_setting('company_paypal_client_id')) ? admin_setting('company_paypal_client_id') : '',
                        'paypal.live.client_secret' => !empty(admin_setting('company_paypal_secret_key')) ? admin_setting('company_paypal_secret_key') : '',
                        'paypal.mode' => !empty(admin_setting('company_paypal_mode')) ? admin_setting('company_paypal_mode') : '',
                    ]
                );
            }
            else{
                config(
                    [
                        'paypal.sandbox.client_id' => !empty(admin_setting('company_paypal_client_id')) ? admin_setting('company_paypal_client_id') : '',
                        'paypal.sandbox.client_secret' => !empty(admin_setting('company_paypal_secret_key')) ? admin_setting('company_paypal_secret_key') : '',
                        'paypal.mode' => !empty(admin_setting('company_paypal_mode')) ? admin_setting('company_paypal_mode') : '',
                    ]
                );
            }

            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();
            $response = $provider->capturePaymentOrder($request['token']);
            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
            $product = 'Basic Package';
            try {
                if (isset($response['status']) && $response['status'] == 'COMPLETED')
                {
                    if ($response['status'] == 'COMPLETED') {
                        $statuses = __('succeeded');
                    }

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
                            'price' => !empty($request->amount)?$request->amount:0,
                            'price_currency' => admin_setting('defult_currancy'),
                            'txn_id' => '',
                            'payment_type' => __('PAYPAL'),
                            'payment_status' =>$statuses,
                            'receipt' => null,
                            'user_id' => $user->id,
                        ]
                    );

                    $user = User::find($user->id);
                    $assignPlan = $user->assignPlan($request->duration,$request->user_module,$request->user_counter);
                    $value = Session::get('user-module-selection');

                    if(!empty($value))
                    {
                        Session::forget('user-module-selection');
                    }

                    if ($assignPlan['is_success']) {
                        return redirect()->route('plans.index')->with('success', __('Plan activated Successfully.'));
                    } else {
                        return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
                    }

                } else {
                    return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
                }

            } catch (\Exception $e) {
                return redirect()->route('plans.index')->with('error', __('Transaction has been failed.'));
            }

        } else {
            return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
        }
    }
}

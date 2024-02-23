<?php

namespace App\Models;

use App\Events\DefaultData;
use App\Events\GivePermissionToRole;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Rawilk\Settings\Support\Context;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile_no',
        'email_verified_at',
        'password',
        'remember_token',
        'type',
        'active_status',
        'active_workspace',
        'avatar',
        'dark_mode',
        'requested_plan',
        'messenger_color',
        'active_plan',
        'billing_type',
        'active_module',
        'plan_expire_date',
        'total_user',
        'seeder_run',
        'workspace_id',
        'created_by',
        'dark_mode',
        'dark_mode',
        'lang',
        'is_enable_login',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    public static $superadmin_activated_module = [
        'ProductService',
    ];
    public static $not_edit_role = [
        'super admin',
        'company',
        'client',
        'vendor',
        'staff'
    ];

    public  $not_emp_type = [
        'super admin',
        'company',
        'client',
        'vendor',
    ];
    public $client_type = [
        'client',
    ];
    public $not_all_user = [
        'super admin',
        'company',
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function scopeEmp($query)
    {
        return $query->whereNotIn('type', $this->not_emp_type);
    }
    public function scopeUsers($query)
    {
        return $query->whereNotIn('type', $this->not_emp_type);
    }
    public function scopeAllusers($query)
    {
        return $query->whereNotIn('type', $this->not_all_user);
    }
    public function scopeClients($query)
    {
        return $query->whereIn('type', $this->client_type);
    }

    public function assignPlan($duration = null,$modules = null,$user_count = 0,$user_id = null)
    {
        if($user_id != null)
        {
            $user = User::find($user_id);
        }
        else
        {
            $user =  User::find(Auth::user()->id);
        }
        $plan = Plan::first();
        if($plan)
        {
            $user->active_plan = $plan->id;
            if(!empty($duration))
            {
                if($duration == 'Month')
                {
                    $user->plan_expire_date = Carbon::now()->addMonths(1)->isoFormat('YYYY-MM-DD');
                }
                elseif($duration == 'Year')
                {
                    $user->plan_expire_date = Carbon::now()->addYears(1)->isoFormat('YYYY-MM-DD');
                }else{
                    $user->plan_expire_date = null;
                }
            }else{
                $user->plan_expire_date = null;
                // for days
                // $this->plan_expire_date = Carbon::now()->addDays($duration)->isoFormat('YYYY-MM-DD');
            }

            if(!empty($modules))
            {
                if(!empty($user->active_module))
                {
                    $user_module = explode(',',$user->active_module);
                    array_push($user_module,$modules);
                }
                else
                {
                    $user_module = explode(',',$modules);
                }
                $user->active_module = implode(',',$user_module);

                event(new DefaultData($user->id,null,$modules));

                $client_role = Role::where('name','client')->where('created_by',$user->id)->first();
                $staff_role = Role::where('name','staff')->where('created_by',$user->id)->first();
                $vendor_role = Role::where('name','vendor')->where('created_by',$user->id)->first();

                if(!empty($client_role))
                {
                    event(new GivePermissionToRole($client_role->id,'client',$modules));
                }
                if(!empty($staff_role))
                {
                    event(new GivePermissionToRole($staff_role->id,'staff',$modules));
                }
                if(!empty($vendor_role))
                {
                    event(new GivePermissionToRole($vendor_role->id,'vendor',$modules));
                }
            }

            if($user_count != 0)
            {
                $user->total_user = $user_count;
            }

            $user->save();

            return ['is_success' => true];
        }
        else
        {
            return [
                'is_success' => false,
                'error' => 'Plan is deleted.',
            ];
        }
    }
      // get font-color code accourding to bg-color
      public static function hex2rgb($hex)
      {
          $hex = str_replace("#", "", $hex);

          if(strlen($hex) == 3)
          {
              $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
              $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
              $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
          }
          else
          {
              $r = hexdec(substr($hex, 0, 2));
              $g = hexdec(substr($hex, 2, 2));
              $b = hexdec(substr($hex, 4, 2));
          }
          $rgb = array(
              $r,
              $g,
              $b,
          );

          //return implode(",", $rgb); // returns the rgb values separated by commas
          return $rgb; // returns an array with the rgb values
      }

      public static function getFontColor($color_code)
      {
          $rgb = self::hex2rgb($color_code);
          $R   = $G = $B = $C = $L = $color = '';

          $R = (floor($rgb[0]));
          $G = (floor($rgb[1]));
          $B = (floor($rgb[2]));

          $C = [
              $R / 255,
              $G / 255,
              $B / 255,
          ];

          for($i = 0; $i < count($C); ++$i)
          {
              if($C[$i] <= 0.03928)
              {
                  $C[$i] = $C[$i] / 12.92;
              }
              else
              {
                  $C[$i] = pow(($C[$i] + 0.055) / 1.055, 2.4);
              }
          }

          $L = 0.2126 * $C[0] + 0.7152 * $C[1] + 0.0722 * $C[2];

          if($L > 0.179)
          {
              $color = 'black';
          }
          else
          {
              $color = 'white';
          }

          return $color;
      }

    public function MakeRole()
    {
        $data = [];
        $staff_role_permission = [
            'user chat manage',
            'user profile manage',
            'user logs history',

        ];
        $client_role_permission = [
            'user chat manage',
            'user profile manage',
            'user logs history',
            'invoice manage',
            'invoice show',
            'proposal manage',
            'proposal show',

        ];
        $client_role = Role::where('name','client')->where('created_by',$this->id)->where('guard_name','web')->first();
        if(empty($client_role))
        {
            $client_role                   = new Role();
            $client_role->name             = 'client';
            $client_role->guard_name       = 'web';
            $client_role->module           = 'Base';
            $client_role->created_by       = $this->id;
            $client_role->save();

            foreach($client_role_permission as $permission_c){
                $permission = Permission::where('name',$permission_c)->first();
                $client_role->givePermissionTo($permission);
            }
        }
        $staff_role = Role::where('name','staff')->where('created_by',$this->id)->where('guard_name','web')->first();
        if(empty($staff_role))
        {
            $staff_role                   = new Role();
            $staff_role->name             = 'staff';
            $staff_role->guard_name       = 'web';
            $staff_role->module           = 'Base';
            $staff_role->created_by       = $this->id;
            $staff_role->save();

            foreach($staff_role_permission as $permission_s){
                $permission = Permission::where('name',$permission_s)->first();
                $staff_role->givePermissionTo($permission);
            }
        }
        $data['client_role'] = $client_role;
        $data['staff_role'] = $staff_role;

        return $data;
    }
    public static function CompanySetting($id = null,$workspace_id = null)
    {
        if(!empty($id))
        {
            $company = User::find($id);
            if(empty($workspace_id))
            {
                $workspace_id = $company->active_workspace;
            }
            $company_setting = [
                "currency_format" => !empty(admin_setting('currency_format')) ? admin_setting('currency_format') : "1",
                "defult_currancy" => !empty(admin_setting('defult_currancy')) ? admin_setting('defult_currancy') : "USD",
                "defult_currancy_symbol" => !empty(admin_setting('defult_currancy_symbol')) ? admin_setting('defult_currancy_symbol') : "$",
                "defult_language" => !empty(admin_setting('defult_language')) ? admin_setting('defult_language') : 'en',
                "defult_timezone" => !empty(admin_setting('defult_timezone')) ? admin_setting('defult_timezone') : 'Asia/Kolkata',
                "site_currency_symbol_position" => "pre",
                "site_date_format" => "d-m-Y",
                "site_time_format" => "g:i A",
                "title_text" => !empty(admin_setting('title_text')) ? admin_setting('title_text') : "WorkDo Dash",
                "footer_text" => !empty(admin_setting('footer_text')) ? admin_setting('footer_text') :"Copyright © WorkDo Dash",
                "site_rtl" => "off",
                "cust_darklayout" => "off",
                "site_transparent" => "on",
                "color" => "theme-1",
                "invoice_prefix" => "#INVO",
                "invoice_starting_number" => "1",
                "invoice_template" => "template1",
                "invoice_color" => "ffffff",
                "invoice_shipping_display" => "on",
                "invoice_title" => "",
                "invoice_notes" => "",
                "proposal_prefix" => "#PROP0",
                "proposal_starting_number" => "1",
                "proposal_template" => "template1",
                "proposal_color" => "ffffff",
                "proposal_shipping_display" => "on",
                "proposal_title" => "",
                "proposal_notes" => "",

            ];
            $userContext = new Context(['user_id' => $id,'workspace_id'=> $workspace_id]);
            foreach($company_setting as $key =>  $p){
                if(empty(company_setting($key,$id)))
                {
                    \Settings::context($userContext)->set($key, $p);
                }
            }
        }
    }
    public function countCompany()
    {
        return User::where('type', '=', 'company')->where('created_by', '=',creatorId())->count();
    }
    public function countPaidCompany()
    {
        return  User::where('type', '=', 'company')->whereNotIn('active_plan', [0,1])->where('created_by', '=', creatorId())->count();
    }
    public function ActiveWorkspaceName()
    {
        $name = $this->name;
        $workspace = WorkSpace::find(getActiveWorkSpace());
        if($workspace)
        {
            $name = $workspace->name;
        }
        return $name;
    }
}

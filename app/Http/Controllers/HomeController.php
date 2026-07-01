<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Image;
use DB;
use Hash;
use Session;
use Redirect;
use App\Expense as Expense;
use App\Sale as Sale;
use App\Expense_categorie as Expense_categorie;
use App\Customer as Customer;
use App\Customer_transection_historie as Customer_transection_historie;
use App\Invoice as Invoice;
use App\Vendor as Vendor;
use App\Store_statu as Store_statu;
use App\Setting as Setting;
use App\User as User;
use App\HumanResource as HumanResource;
use App\Product as Product;


class HomeController extends Controller
{
  /**
  * Create a new controller instance.
  *
  * @return void
  */
  public function __construct()
  {
    $this->middleware('auth');
    $this->middleware('storestatus');
  }

  /**
  * Show the application dashboard.
  *
  * @return \Illuminate\Http\Response
  */
  public function index()
  {

    $currency = CommonSetting::currency();
    if($currency == 'USD'){
      $currency = "$";
    }
    else if($currency == 'GMD'){
      $currency = "D";
    }
    else if($currency == 'GBP'){
      $currency = "£";
    }
    else if($currency == 'EUR'){
      $currency = "€";
    }
    else if($currency == 'INR'){
      $currency = "₹";
    }
    $numberOfVendorscount = Vendor::where('soft_delete', '=', 1)->count();
    $numberOfCustomerscount = Customer::where('soft_delete', '=', 1)->where('customer_name', '!=', 'Walk In Customer')->count();
    $todaysExpense = Expense::whereDate('created_at', \Carbon\Carbon::now(CommonSetting::timezone())->toDateString())
    ->selectRaw('sum(amount) as todays_expense')
    ->get();
    $numberOfUserscount = User::all()->where('id','<>',1)->where('password','<>','deleteduser')->count();

    // ---- Chart data (plain arrays, rendered client-side with Chart.js) ----
    // Package `consoletvs/charts` was dropped: its 4.x line was pulled from
    // Packagist and 6.x has an incompatible API, so this data is now built
    // directly and rendered with Chart.js in the view instead.

    //barchart: last 7 days expense (zero-filled so gaps don't disappear)
    $expensesOfLastSevenDayRaw = DB::table('expenses')
                     ->select(DB::raw('DATE(created_at) as date, sum(amount) as sumof'))
                     ->where('created_at', '>=', \Carbon\Carbon::now(CommonSetting::timezone())->subDays(6)->startOfDay())
                     ->groupBy('date')
                     ->pluck('sumof', 'date');
    $expensesOfLastSevenDay = ['labels' => [], 'data' => []];
    for ($i = 6; $i >= 0; $i--) {
      $day = \Carbon\Carbon::now(CommonSetting::timezone())->subDays($i)->toDateString();
      $expensesOfLastSevenDay['labels'][] = $day;
      $expensesOfLastSevenDay['data'][] = (float) ($expensesOfLastSevenDayRaw[$day] ?? 0);
    }

    //expense chart of one month
    $expenseListings = Expense::whereDate('created_at', \Carbon\Carbon::now(CommonSetting::timezone())->toDateString())->get();
    $totalExpense = Expense::whereDate('created_at', \Carbon\Carbon::now(CommonSetting::timezone())->toDateString())
    ->selectRaw('sum(amount) as total_Expense')
    ->get();
    $salesListings = Customer_transection_historie::whereDate('created_at', \Carbon\Carbon::now(CommonSetting::timezone())->toDateString())->get();
    $totalSale = Customer_transection_historie::whereDate('created_at', \Carbon\Carbon::now(CommonSetting::timezone())->toDateString())
    ->selectRaw('sum(amount_paid) as total_Sale')
    ->get();

    //7 months customer adding Chart (rolling last 7 calendar months, excludes walk-in customer id 1)
    $customerLastSevenMonthsChart = ['labels' => [], 'data' => []];
    for ($i = 6; $i >= 0; $i--) {
      $monthCursor = \Carbon\Carbon::now(CommonSetting::timezone())->subMonths($i);
      $count = Customer::where('id', '!=', 1)
        ->whereYear('created_at', $monthCursor->year)
        ->whereMonth('created_at', $monthCursor->month)
        ->count();
      $customerLastSevenMonthsChart['labels'][] = $monthCursor->format('M Y');
      $customerLastSevenMonthsChart['data'][] = $count;
    }

    //7days products chart: count of sales per day, last 7 days, zero-filled
    $productSaleOfLastSevenDayRaw = DB::table('sales')
      ->select(DB::raw('DATE(created_at) as date, COUNT(*) as total'))
      ->where('created_at', '>=', \Carbon\Carbon::now(CommonSetting::timezone())->subDays(6)->startOfDay())
      ->groupBy('date')
      ->pluck('total', 'date');
    $productSaleOfLastSevenDay = ['labels' => [], 'data' => []];
    for ($i = 6; $i >= 0; $i--) {
      $day = \Carbon\Carbon::now(CommonSetting::timezone())->subDays($i)->toDateString();
      $productSaleOfLastSevenDay['labels'][] = $day;
      $productSaleOfLastSevenDay['data'][] = (int) ($productSaleOfLastSevenDayRaw[$day] ?? 0);
    }

    //monthly stat: expenses vs sales, aligned by calendar month (Jan-Dec)
    $expensesByMonthRaw = DB::table('expenses')
      ->select(DB::raw('MONTH(created_at) as month, sum(amount) as total'))
      ->groupBy('month')
      ->pluck('total', 'month');
    $salesByMonthRaw = DB::table('customer_transection_histories')
      ->select(DB::raw('MONTH(created_at) as month, sum(amount_paid) as total'))
      ->groupBy('month')
      ->pluck('total', 'month');
    $monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $revenue = ['labels' => $monthNames, 'expenses' => [], 'sales' => []];
    for ($m = 1; $m <= 12; $m++) {
      $revenue['expenses'][] = (float) ($expensesByMonthRaw[$m] ?? 0);
      $revenue['sales'][] = (float) ($salesByMonthRaw[$m] ?? 0);
    }

    return view('home', compact('currency','numberOfVendorscount','numberOfCustomerscount','todaysExpense','numberOfUserscount','expensesOfLastSevenDay','customerLastSevenMonthsChart','expenseListings','salesListings','totalExpense','totalSale','productSaleOfLastSevenDay','revenue'));
  }


  public function profile()
  {
    return view('profile',array('user' => Auth::user()) );
  }


  public function profile_edit(Request $request){

    // Handle the user upload of avatar
    if($request->hasFile('avatar')){
      $user = Auth::user();
      if($user->avatar != 'default.jpg'){
        unlink(base_path('resources/assets/uploads/avatars/'. $user->avatar));
      }
      $avatar = $request->file('avatar');

      $filename = $user->id . '.' . $avatar->getClientOriginalExtension();
      Image::make($avatar)->resize(300, 300)->save( base_path('resources/assets/uploads/avatars/' . $filename ) );
      $user->avatar = $filename;
      $user->save();
    }
    return redirect('profile');
  }

  public function password_change()
  {
    $User = User::find(Auth::user()->id);
    if(Hash::check($_POST['old_password'], $User['password'])){
      $User->password = bcrypt($_POST['new_password']);
      $User->save();
      Session::flash('passwordChangeStatusYes', "Password Changed Sucessfully. Please Login Again with your new password.");
      return Redirect::back();
    }
    else {
      Session::flash('passwordChangeStatusNo', "Old Password Does Not Match.");
      return Redirect::back();
    }
  }

}

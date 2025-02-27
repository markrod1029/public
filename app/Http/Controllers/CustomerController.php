<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use App\Mail\AccountEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\CardCodesController;
use App\Models\CardCode; // Import ng CardCode model

class CustomerController extends Controller
{

    public function index()
    {
        $searchFields = [
            'customer_card_num',
            'zip',
            'street',
            'city',
            'province',
            'bdate',
            'users.fname',
            'users.mname',
            'users.lname',
            'users.contact',
            'users.email',
        ];

        $customers = Customer::query()
            ->select([
                'customers.id AS customer_id',
                'users.id AS user_id',
                'users.email',
                'users.contact',
                'customers.street',
                'customers.bdate',
                'customers.customer_card_num',
                'customers.zip',
                'customers.city',
                '.province',
                // Concatenate full name
                DB::raw("CONCAT_WS(' ', COALESCE(users.fname, ''), COALESCE(users.mname, ''), COALESCE(users.lname, '')) AS customer_name"),
                // Concatenate full address
                DB::raw("CONCAT_WS(', ', COALESCE(customers.street, ''), COALESCE(customers.city, ''), COALESCE(customers.province, ''), COALESCE(customers.zip, '')) AS address")
            ])
            ->leftJoin('users', 'customers.user_id', '=', 'users.id')
            ->when(request('query'), function ($query, $searchQuery) use ($searchFields) {
                $query->where(function ($query) use ($searchFields, $searchQuery) {
                    foreach ($searchFields as $field) {
                        $query->orWhere($field, 'like', "%{$searchQuery}%");
                    }
                });
            })

            ->when(request("city"), function ($query, $city) {
                $query->where("city", "like", "%{$city}%");
            })
            ->when(request("province"), function ($query, $province) {
                $query->where("province", "like", "%{$province}%");
            })
            ->where('users.status', '=', '1')
            ->get();

        return response()->json($customers);
    }
    public function indexArchive()
    {

        $searchFields = [
            'customer_card_num',
            'zip',
            'street',
            'city',
            'province',
            'bdate',
            'users.fname',
            'users.mname',
            'users.lname',
            'users.contact',
            'users.email',
        ];

        $customers = Customer::query()
            ->select([
                'customers.id AS customer_id',
                'users.id AS user_id',
                'users.email',
                'users.contact',
                'customers.street',
                'customers.bdate',
                'customers.customer_card_num',
                'customers.zip',
                'customers.city',
                '.province',
                // Concatenate full name
                DB::raw("CONCAT_WS(' ', COALESCE(users.fname, ''), COALESCE(users.mname, ''), COALESCE(users.lname, '')) AS customer_name"),
                // Concatenate full address
                DB::raw("CONCAT_WS(', ', COALESCE(customers.street, ''), COALESCE(customers.city, ''), COALESCE(customers.province, ''), COALESCE(customers.zip, '')) AS address")
            ])
            ->leftJoin('users', 'customers.user_id', '=', 'users.id')
            ->when(request('query'), function ($query, $searchQuery) use ($searchFields) {
                $query->where(function ($query) use ($searchFields, $searchQuery) {
                    foreach ($searchFields as $field) {
                        $query->orWhere($field, 'like', "%{$searchQuery}%");
                    }
                });
            })

            ->when(request("city"), function ($query, $city) {
                $query->where("city", "like", "%{$city}%");
            })
            ->when(request("province"), function ($query, $province) {
                $query->where("province", "like", "%{$province}%");
            })
            ->where('users.status', '=', '5')
            ->get();

        return response()->json($customers);

    }


    public function indexUser()
    {
        // Get the user_id of the current user
        $merchant_id = request()->user()->id;
        // Define the fields you want to search in the customers table
        $searchFields = [
            'customers.customer_card_num',
            'customers.fname',
            'customers.mname',
            'customers.lname',
            'customers.contact',
            'customers.email',
            'customers.zip',
            'customers.street',
            'customers.city',
            'customers.province',
            'customers.validity'
        ];

        // Query customers with their associated card_codes
        $customers = Customer::query()
            ->join('card_codes', 'customers.customer_card_num', '=', 'card_codes.card_number') // Join with card_codes table
            ->where('customers.status', 1) // Only active customers
            ->where('card_codes.user_id', $merchant_id) // Filter by the user's ID
            ->when(request('query'), function ($query, $searchQuery) use ($searchFields) {
                $query->where(function ($query) use ($searchFields, $searchQuery) {
                    foreach ($searchFields as $field) {
                        $query->orWhere($field, 'like', "%{$searchQuery}%");
                    }
                });
            })
            ->latest('customers.created_at') // Sort by creation date
            ->select('customers.*') // Select only the customer fields
            ->get();

        return response()->json($customers); // Return the customers as JSON
    }




    // public function points($id)
    // {
    //     $searchFields = ['customer_card_num', 'fname', 'mname', 'lname', 'points', 'points.created_at'];

    //     $points = Point::query() // Use Point::query() as the primary model
    //         ->select('customers.id as customer_id', 'customers.*', 'points.created_at as point_created_at', 'points.*') // Alias 'customers.id' as 'customer_id'
    //         ->leftJoin('customers', 'customers.customer_card_num', '=', 'points.card_number') // Join with customers table
    //         ->where('customers.status', 1) // Check status on customers table
    //         ->where('customers.id', $id) // Filter by customer_id in points table
    //         ->when(request('query'), function ($query, $searchQuery) use ($searchFields) {
    //             $query->where(function ($query) use ($searchFields, $searchQuery) {
    //                 foreach ($searchFields as $field) {
    //                     $query->orWhere($field, 'like', "%{$searchQuery}%");
    //                 }
    //             });
    //         })
    //         ->orderBy('points.created_at', 'desc') // Order by points.created_at
    //         ->get();

    //     return response()->json($points);
    // }

    public function customerResult($cardNumber)
    {
        // Fetch the card_number from the request
        $cardNumber = request('card_number');

        // Retrieve the customer's details and total points
        $customerData = Customer::query()
            ->select(
                'customers.id as customer_id',
                'customers.fname', // Include necessary fields from customers
                'customers.mname', // Include necessary fields from customers
                'customers.lname', // Include necessary fields from customers
                'customers.email', // Include necessary fields from customers
                'customers.validity', // Include necessary fields from customers
                'customers.customer_card_num',
                // Add all other necessary fields from the customers table
                DB::raw('IFNULL(SUM(points.points), 0) as total_points')
            )
            ->leftJoin('points', 'customers.customer_card_num', '=', 'points.card_number') // Join with points table
            ->where('customers.status', 1) // Check status on customers table
            ->where('customers.customer_card_num', $cardNumber) // Filter by customer card number
            ->groupBy('customers.id', 'customers.fname', 'customers.mname', 'customers.lname', 'customers.email', 'customers.validity', 'customers.customer_card_num') // Group by all selected columns
            ->first(); // Get the first result

        // Check if customer exists
        if (!$customerData) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        return response()->json($customerData);
    }


    public function approve(Request $request, $id)
    {
        // Find the user by ID or throw a 404 error if not found
        $user = User::findOrFail($id);

        // Retrieve the status from the request
        $status = $request->input('status');

        // Find the customer associated with the user ID
        $customer = Customer::where('user_id', $id)->first();

        // Ensure a customer is found
        if (!$customer) {
            return response()->json(['message' => 'Customer not found for the specified user.'], 404);
        }

         // Retrieve email and business code
         $email = $user->email;
         $passowrd = $customer->customer_card_num;

         $user->update([
            'status' => $status,
            'password' =>bcrypt($customer->customer_card_num) // Update password securely
        ]);


        // Prepare the email details
        $subject = 'Customer Account Activation';
        $message = $passowrd;

        // Send email to the user's email address
        Mail::to($email)->send(new AccountEmail($message, $subject, $email));

        // Return a JSON response confirming success
        return response()->json(['message' => 'Merchant status updated and email sent successfully.']);
    }


    public function archive(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $status = $request->input('status');
        $user->update(['status' => $status]);
        return response()->json(['message' => 'Merchant status updated successfully.']);
    }

    public function generateCardCode()
    {
        $currentYear = date('Y');
        $lastCardCode = Customer::where('customer_card_num', 'like', $currentYear . '-%')->latest('customer_card_num')->value(column: 'customer_card_num');
        $lastSerialNumber = 1;
        if ($lastCardCode) {
            $lastSerialNumber = intval(substr($lastCardCode, -7)) + 1;
        }
        return $currentYear . '-0C-' . str_pad($lastSerialNumber, 7, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {

        DB::beginTransaction();

        try {
            // Validate all input fields at once
            $validatedData = $request->validate([
                // User fields
                'fname' => 'required|string|max:255',
                'mname' => 'nullable|string|max:255',
                'lname' => 'required|string|max:255',
                'contact' => 'required|string|max:20',
                'email' => 'required|email|unique:users,email',

                // Merchant fields
                'bdate' => '',
                'zip' => 'required|string|max:10',
                'street' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'province' => 'required|string|max:255',
            ]);

            // Generate codes
            $cardNumber = $this->generateCardCode();
            $validityDate = now()->addMonths(15); // 15 months = 1 year and 3 months
            // Create User
            $user = User::create([
                'fname' => $validatedData['fname'],
                'mname' => $validatedData['mname'] ?? null,
                'lname' => $validatedData['lname'],
                'contact' => $validatedData['contact'],
                'email' => $validatedData['email'],
                'password' => bcrypt($cardNumber),
                'role' => 'Customer',
                'status' => 1,
            ]);

            // Create Merchant
            Customer::create([
                'user_id' => $user->id,
                'customer_card_num' => $cardNumber,
                'bdate' => $validatedData['bdate'],
                'street' => $validatedData['street'],
                'city' => $validatedData['city'],
                'province' => $validatedData['province'],
                'validity' => $validityDate,
            ]);

            DB::commit();

            return response()->json(['message' => 'success']);
        } catch (\Exception $e) {
            DB::rollback();
            if (isset($user)) {
                $user->delete();
            }
            return response()->json(['message' => 'error', 'error' => $e->getMessage()], 500);
        }


    }


    public function edit(Customer $customer)
    {

        return $customer;
        // return response()->json($customer);

    }

    public function update(Request $request, Customer $customer)
    {

        $validated = $request->validate([
            'mname' => '',
            'fname' => 'required',
            'lname' => 'required',
            'contact' => 'required',
            'bdate' => '',
            'email' => 'required|unique:users,email,' . $customer->id,
            'zip' => '',
            'street' => '',
            'city' => '',
            'province' => '',

        ]);

        $customer->update($validated);

        $user = Auth::user();
            activity()
            ->causedBy($user)
            ->withProperties(['role' => $user->role, 'status' => $user->status])
            ->log("Customer updated successfully");

        return response()->json(['success' => true]);

    }
    // public function count()
    // {
    //     $user = auth()->user(); // Get the authenticated user

    //     if ($user->role === 'Admin') {
    //         // Admin counts all customers
    //         $customerCount = Customer::where('status', 1)->count();
    //     } elseif ($user->role === 'Merchant' || $user->role === 'Influencer') {
    //         // Merchant or Influencer counts only their own customers
    //         $customerCount = Customer::join('card_codes', 'customers.customer_card_num', '=', 'card_codes.card_number')
    //             ->where('customers.status', 1)
    //             ->where('card_codes.user_id', $user->id) // Filter by the user's ID
    //             ->count();
    //     } else {
    //         // If the role doesn't match, return 0 or an appropriate message
    //         $customerCount = 0; // or return response()->json(['error' => 'Unauthorized'], 403);
    //     }

    //     return response()->json(['count' => $customerCount]);
    // }

    // public function barGraph()
    // {
    //     $user = auth()->user(); // Get the authenticated user
    //     $query = Customer::query()->where('status', 1);

    //     if ($user->role === 'Merchant' || $user->role === 'Influencer') {
    //         $query->join('card_codes', 'customers.customer_card_num', '=', 'card_codes.card_number')
    //             ->where('card_codes.user_id', $user->id); // Filter by the user's ID
    //     }

    //     $monthlyCounts = $query
    //         ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
    //         ->groupBy('month')
    //         ->orderBy('month')
    //         ->get();

    //     return response()->json($monthlyCounts);
    // }



}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Merchant;
use App\Mail\AccountEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

class MerchantController extends Controller
{
    public function index()
    {
        $searchFields = [
            'card_code',
            'dti',
            'business_code',
            'business_name',
            'business_category',
            'discount',
            'zip',
            'street',
            'city',
            'province',
            'discount',
            'stars_points',
            'users.fname',
            'users.mname',
            'users.lname',
            'users.contact',
            'users.email',
            'merchants.city',
            'merchants.province',
        ];

        $merchants = Merchant::query()
        ->select([
            'merchants.id AS merchant_id',
            'merchants.business_name',
            'merchants.business_category',
            'merchants.business_sub_category',
            'merchants.discount',
            'merchants.stars_points',
            'merchants.created_at',
            'users.id AS user_id',
            'users.email',
            'users.contact',
            'merchants.city',
            'merchants.province',
            DB::raw("CONCAT_WS(' ', COALESCE(users.fname, ''), COALESCE(users.mname, ''), COALESCE(users.lname, '')) AS merchant_name"),
            DB::raw("CONCAT_WS(', ', COALESCE(merchants.street, ''), COALESCE(merchants.city, ''), COALESCE(merchants.province, ''), COALESCE(merchants.zip, '')) AS address")
        ])
        ->leftJoin('users', 'merchants.user_id', '=', 'users.id')
        ->when(request('query'), function ($query, $searchQuery) use ($searchFields) {
            $query->where(function ($query) use ($searchFields, $searchQuery) {
                foreach ($searchFields as $field) {
                    $query->orWhere($field, 'like', "%{$searchQuery}%");
                }
            });
        })
        ->when(request('category'), function ($query, $category) {
            $query->where('business_category', 'like', "%{$category}%");
        })
        ->when(request('discount'), function ($query, $discount) {
            $query->where('discount', '=', $discount);
        })
        ->when(request("city"), function ($query, $city) {
            $query->where("city", "like", "%{$city}%");
        })
        ->when(request("province"), function ($query, $province) {
            $query->where("province", "like", "%{$province}%");
        })
        ->where('users.status', '=', '1')
        ->orderByRaw('CAST(merchants.discount AS UNSIGNED) DESC') // Para sigurado na number ang sorting
        ->orderBy('merchants.created_at', 'asc') // Kung pareho ang discount, maunang narehistro ang mauuna
        ->get();



        return response()->json($merchants);
    }




    public function indexPending()
    {
        $searchFields = [
            'card_code',
            'dti',
            'business_code',
            'business_name',
            'business_category',
            'discount',
            'zip',
            'street',
            'city',
            'province',
            'stars_points',
            'users.fname',
            'users.mname',
            'users.lname',
            'users.contact',
            'users.email'
        ];

        $merchants = Merchant::query()
        ->select([
            'merchants.id AS merchant_id',
            'merchants.business_name',
            'merchants.business_category',
            'merchants.business_sub_category',
            'merchants.discount',
            'merchants.stars_points',
            'users.id AS user_id',
            'users.email',
            'users.contact',
            // Concatenate full name
            DB::raw("CONCAT_WS(' ', COALESCE(users.fname, ''), COALESCE(users.mname, ''), COALESCE(users.lname, '')) AS merchant_name"),
            // Concatenate full address
            DB::raw("CONCAT_WS(', ', COALESCE(merchants.street, ''), COALESCE(merchants.city, ''), COALESCE(merchants.province, ''), COALESCE(merchants.zip, '')) AS address")
        ])
            ->leftJoin('users', 'merchants.user_id', '=', 'users.id')
            ->when(request('query'), function ($query, $searchQuery) use ($searchFields) {
                $query->where(function ($query) use ($searchFields, $searchQuery) {
                    foreach ($searchFields as $field) {
                        $query->orWhere($field, 'like', "%{$searchQuery}%");
                    }
                });
            })

            ->where('users.status', '=', '0')
            ->orderBy('stars_points', 'desc')
            ->orderBy('discount', 'desc')
            ->get();

        return response()->json($merchants);

    }



    public function indexArchive()
    {
        $searchFields = [
            'card_code',
            'dti',
            'business_code',
            'business_name',
            'business_category',
            'discount',
            'zip',
            'street',
            'city',
            'province',
            'stars_points',
            'users.fname',
            'users.mname',
            'users.lname',
            'users.contact',
            'users.email'
        ];

        $merchants = Merchant::query()
        ->select([
            'merchants.id AS merchant_id',
            'merchants.business_name',
            'merchants.business_category',
            'merchants.business_sub_category',
            'merchants.discount',
            'merchants.stars_points',
            'users.id AS user_id',
            'users.email',
            'users.contact',
            // Concatenate full name
            DB::raw("CONCAT_WS(' ', COALESCE(users.fname, ''), COALESCE(users.mname, ''), COALESCE(users.lname, '')) AS merchant_name"),
            // Concatenate full address
            DB::raw("CONCAT_WS(', ', COALESCE(merchants.street, ''), COALESCE(merchants.city, ''), COALESCE(merchants.province, ''), COALESCE(merchants.zip, '')) AS address")
        ])
            ->leftJoin('users', 'merchants.user_id', '=', 'users.id')
            ->when(request('query'), function ($query, $searchQuery) use ($searchFields) {
                $query->where(function ($query) use ($searchFields, $searchQuery) {
                    foreach ($searchFields as $field) {
                        $query->orWhere($field, 'like', "%{$searchQuery}%");
                    }
                });
            })

            ->where('users.status', '=', '5')
            ->orderBy('stars_points', 'desc')
            ->orderBy('discount', 'desc')
            ->get();

        return response()->json($merchants);

    }


    public function searchLocations()
{
    $cities = Merchant::select('city')
        ->whereNotNull('city')
        ->distinct()
        ->orderBy('city')
        ->pluck('city');

    $provinces = Merchant::select('province')
        ->whereNotNull('province')
        ->distinct()
        ->orderBy('province')
        ->pluck('province');

    return response()->json([
        'cities' => $cities,
        'provinces' => $provinces
    ]);
}



    function generateRandomStringWithNumbers($length)
    {
        $characters = '0123456789';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }

    function generateBusinessCode($businessName, $length = 8)
    {
        // Remove spaces from the business name
        $businessName = str_replace(' ', '', $businessName);

        // Extract the first few characters from the modified business name (e.g., 3 characters)
        $prefix = substr(strtoupper($businessName), 0, 3);

        // Calculate the length for the unique identifier (to ensure numbers are included)
        $uniqueIdentifierLength = max(0, $length - strlen($prefix));

        // Generate a unique identifier with numbers
        $uniqueIdentifier = $this->generateRandomStringWithNumbers($uniqueIdentifierLength);

        // Combine the prefix and unique identifier to create the store code
        $storeCode = $prefix . $uniqueIdentifier;

        return $storeCode;
    }


    public function generateCardCode()
    {
        $currentYear = date('Y');
        $lastCardCode = Merchant::where('card_code', 'like', $currentYear . '-%')->latest('card_code')->value('card_code');
        $lastSerialNumber = 1;
        if ($lastCardCode) {
            $lastSerialNumber = intval(substr($lastCardCode, -7)) + 1;
        }
        return $currentYear . '-0M-' . str_pad($lastSerialNumber, 7, '0', STR_PAD_LEFT);
    }

    // public function store(Request $request)
    // {
    //     // Separate validation for Merchant
    //     $merchantValidation = $request->validate([
    //         'business_name' => 'required',
    //         'business_category' => 'required',
    //         'business_sub_category' => 'required',
    //         'discount' => '',
    //         'zip' => '',
    //         'street' => '',
    //         'city' => '',
    //         'province' => '',
    //         'website' => '',
    //         'facebook' => '',
    //     ]);


    //     // Start a database transaction
    //     DB::beginTransaction();

    //     try {
    //         // Validate User
    //         $userValidation = $request->validate([
    //             'fname' => 'required',
    //             'lname' => 'required',
    //             'contact' => 'required',
    //             'email' => 'required|unique:users,email',
    //         ]);

    //         // Generate business code and card code
    //         $businessCode = $this->generateBusinessCode($merchantValidation['business_name']);
    //         $cardCode = $this->generateCardCode();

    //         // Create User
    //         $user = User::create([
    //             'fname' => $userValidation['fname'],
    //             'mname' => request('mname'),
    //             'lname' => $userValidation['lname'],
    //             'contact' => $userValidation['contact'],
    //             'email' => $userValidation['email'],
    //             'password' => bcrypt($businessCode),
    //             'role' => 'Merchant',
    //             'status' => 0,
    //         ]);


    //         // Create Merchant
    //         Merchant::create([
    //             'user_id' => $user->id,
    //             'business_code' => $businessCode,
    //             'card_code' => $cardCode,
    //             'business_name' => $merchantValidation['business_name'],
    //             'business_category' => $merchantValidation['business_category'],
    //             'business_sub_category' => $merchantValidation['business_sub_category'],
    //             'discount' => $merchantValidation['discount'],
    //             'zip' => request('zip'),
    //             'street' => request('street'),
    //             'city' => request('city'),
    //             'province' => request('province'),
    //             'website' => $merchantValidation['business_sub_category'],
    //             'facebook' => $merchantValidation['business_sub_category'],
    //         ]);

    //         // Commit the transaction
    //         DB::commit();



    //         return response()->json(['message' => 'success']);
    //     } catch (\Exception $e) {
    //         // Rollback the transaction and delete the user
    //         DB::rollback();
    //         if (isset($user)) {
    //             $user->delete();
    //         }
    //         return response()->json(['message' => 'error', 'error' => $e->getMessage()], 500);
    //     }
    // }

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
            'business_name' => 'required|string|max:255',
            'business_category' => 'required|string|max:255',
            'business_sub_category' => 'required|string|max:255',
            'discount' => 'nullable|numeric',
            'zip' => 'required|string|max:10',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'website' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
        ]);

        // Generate codes
        $businessCode = $this->generateBusinessCode($validatedData['business_name']);
        $cardCode = $this->generateCardCode();

        // Create User
        $user = User::create([
            'fname' => $validatedData['fname'],
            'mname' => $validatedData['mname'] ?? null,
            'lname' => $validatedData['lname'],
            'contact' => $validatedData['contact'],
            'email' => $validatedData['email'],
            'password' => bcrypt($businessCode),
            'role' => 'Merchant',
            'status' => 0,
        ]);

        // Create Merchant
        Merchant::create([
            'user_id' => $user->id,
            'business_code' => $businessCode,
            'card_code' => $cardCode,
            'business_name' => $validatedData['business_name'],
            'business_category' => $validatedData['business_category'],
            'business_sub_category' => $validatedData['business_sub_category'],
            'discount' => $validatedData['discount'] ?? null,
            'zip' => $validatedData['zip'],
            'street' => $validatedData['street'],
            'city' => $validatedData['city'],
            'province' => $validatedData['province'],
            'website' => $validatedData['website'] ?? null,
            'facebook' => $validatedData['facebook'] ?? null,
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

public function incrementViews($id)
{
    $merchant = Merchant::find($id);
    if ($merchant) {
        $merchant->increment('views'); // +1 sa views column

        return response()->json(['message' => 'View count updated']);
    }
    return response()->json(['message' => 'Merchant not found'], 404);
}

public function edit($id)
{
    // Hanapin ang merchant kasama ang user info
    $merchantWithUser = Merchant::with('user:id,fname,mname,lname,contact,email,avatar')
        ->findOrFail($id);

    return response()->json($merchantWithUser);
}


    public function update(Request $request, Merchant $merchant)
    {

        $validate = $request->validate([
            'fname' => 'required',
            'lname' => 'required',
            'contact' => 'required',
            'email' => 'required|unique:users,email,' . $merchant->user->id,
            'dtiNo' => 'required',
            'business_name' => 'required',
            'business_category' => 'required',
            'business_sub_category' => 'required',
            'zip' => 'required',
            'street' => 'required',
            'city' => 'required',
            'province' => 'required',
        ]);

        // Simulan ang transaksyon sa database
        DB::beginTransaction();

        try {
            // Kunin ang user mula sa merchant
            $user = $merchant->user;

            // I-update ang impormasyon ng user
            $user->update([
                'fname' => $validate['fname'],
                'lname' => $validate['lname'],
                'contact' => $validate['contact'],
                'email' => $validate['email'],

            ]);

            // I-update ang impormasyon ng merchant
            $merchant->update([
                'business_name' => $validate['business_name'],
                'business_category' => $validate['business_category'],
                'business_sub_category' => $validate['business_sub_category'],
                'zip' => $validate['zip'],
                'street' => $validate['street'],
                'city' => $validate['city'],
                'province' => $validate['province'],
            ]);

            // Kumpirmahin ang transaksyon at i-commit ito sa database
            DB::commit();

                // $user = Auth::user();
                // activity()
                //     ->performedOn($user)
                //     ->causedBy($user)
                // ->withProperties(['role' => $user->role, 'status' => $user->status])
                //     ->log('Updated Merchant');

            return response()->json(['message' => 'success']);
        } catch (\Exception $e) {
            // Kung may naganap na error, i-rollback ang transaksyon at itapon ang error
            DB::rollback();
            return response()->json(['message' => 'error', 'error' => $e->getMessage()], 500);
        }
    }


    public function approve(Request $request, $id)
    {
        // Find the user by ID or throw a 404 error if not found
        $user = User::findOrFail($id);

        // Retrieve the status from the request
        $status = $request->input('status');

        // Find the merchant associated with the user ID
        $merchant = Merchant::where('user_id', $id)->first();

        // Ensure a merchant is found
        if (!$merchant) {
            return response()->json(['message' => 'Merchant not found for the specified user.'], 404);
        }

         // Retrieve email and business code
         $email = $user->email;
         $passowrd = $merchant->business_code;

         $user->update([
            'status' => $status,
            'password' =>bcrypt($merchant->business_code) // Update password securely
        ]);



        // Prepare the email details
        $subject = 'Merchant Account Activation';
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


    public function count()
    {
        $merchantCount = User::where('role', 'Merchant')
            ->where('status', 1)->count();
        return response()->json(['count' => $merchantCount]);
    }


}


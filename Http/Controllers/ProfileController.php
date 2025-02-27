<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Influencer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\InvalidPasswordException;
use App\Actions\Account\UpdateUserPasswordAction;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = request()->user();
        $profileDetails = $user->only(['id','fname', 'mname', 'lname', 'contact', 'email', 'role', 'avatar', 'photo1', 'photo2', 'photo3']);

        if ($user->role === 'Merchant') {

            $merchant = Merchant::where('user_id', $user->id)->first();

            $profileDetails['user_id'] = $merchant->user_id;
            $profileDetails['business_code'] = $merchant->business_code;
            $profileDetails['card_code'] = $merchant->card_code;
            $profileDetails['business_name'] = $merchant->business_name;
            $profileDetails['business_category'] = $merchant->business_category;
            $profileDetails['discount'] = $merchant->discount;
            $profileDetails['stars_points'] = $merchant->stars_points;
            $profileDetails['business_sub_category'] = $merchant->business_sub_category;
            $profileDetails['zip'] = $merchant->zip;
            $profileDetails['street'] = $merchant->street;
            $profileDetails['city'] = $merchant->city;
            $profileDetails['province'] = $merchant->province;
            $profileDetails['description'] = $merchant->description;
            $profileDetails['tagline'] = $merchant->tagline;
            $profileDetails['photo1'] = $merchant->photo1;
            $profileDetails['photo2'] = $merchant->photo2;
            $profileDetails['photo3'] = $merchant->photo3;

        } else if ($user->role === 'Influencer') {

            $influencer = Influencer::where('user_id', $user->id)->first();
            $profileDetails['influencer_code'] = $influencer->influencer_code;
            $profileDetails['card_code'] = $influencer->card_code;
            $profileDetails['blog_name'] = $influencer->blog_name;
            $profileDetails['blog_category'] = $influencer->business_category;
            $profileDetails['zip'] = $influencer->zip;
            $profileDetails['city'] = $influencer->city;
            $profileDetails['province'] = $influencer->province;

        } else if ($user->role === 'Customer') {

            $customer = Customer::where('user_id', $user->id)->first();
            $profileDetails['customer_card_num'] = $customer->customer_card_num;
            $profileDetails['bdate'] = $customer->bdate;
            $profileDetails['zip'] = $customer->zip;
            $profileDetails['street'] = $customer->street;
            $profileDetails['city'] = $customer->city;
            $profileDetails['province'] = $customer->province;
            $profileDetails['validity'] = $customer->validity;

        }


        return $profileDetails;
    }

    public function updateProfile(Request $request)
{
    $user = $request->user();

    // Validate the request
    $validatedData = $request->validate([
        'fname' => 'required|string|max:255',
        'mname' => 'nullable|string|max:255',
        'lname' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        'contact' => 'required|string|max:20|unique:users,contact,' . $user->id,
    ]);

    // Update the user with validated data
    $user->update($validatedData);

    // Determine display name based on role
    $name = $user->role === 'Merchant' ? $user->business_name : trim("{$user->fname} {$user->mname} {$user->lname}");

    // Log the update action
    activity()
        ->performedOn($user)
        ->causedBy($user)
        ->withProperties([
            'role' => $user->role,
            'status' => $user->status,
            'updated_fields' => array_keys($validatedData),
        ])
        ->log("$name updated their profile.");

    // Return response with updated user data
    return response()->json([
        'message' => 'User information updated successfully.',
        'user' => $user
    ], 200);
}


public function updateDiscount(Request $request)
{
    $user = $request->user();

    // Validate the request
    $validatedData = $request->validate([
        'discount' => 'required|numeric|min:0|max:100', // Dapat number, at limitadong 0-100%
        'description' => 'nullable|string',
    ]);

    // Kunin ang business na konektado sa user
    $business = Merchant::where('user_id', $user->id)->first();

    // Kumpirmahin kung may negosyo ang user
    if (!$business) {
        return response()->json(['message' => 'User does not have an associated business'], 404);
    }

    // I-update ang discount sa Merchant model
    $business->update([
        'discount' => $validatedData['discount'],
        'description' => $validatedData['description'] ?? $business->description,
    ]);

    // Tukuyin ang display name batay sa role
    $name = $user->role === 'Merchant' ? $business->business_name : trim("{$user->fname} {$user->mname} {$user->lname}");

    // Log the update action
    activity()
        ->performedOn($business)
        ->causedBy($user)
        ->withProperties([
            'role' => $user->role,
            'status' => $user->status,
            'updated_fields' => array_keys($validatedData),
        ])
        ->log("$name updated their Discount.");

    // Return response with updated data
    return response()->json([
        'message' => 'Business discount updated successfully.',
        'business' => $business
    ], 200);
}



public function updateAddress(Request $request)
{
    $user = $request->user();

    // Validate the request
    $validatedData = $request->validate([
        'business_name' => 'required|max:255',
        'street' => 'required|max:255',
        'city' => 'required|max:255',
        'province' => 'required|max:255',
        'zip' => 'required|max:255',

    ]);

    // Kunin ang business na konektado sa user
    $business = Merchant::where('user_id', $user->id)->first();

    // Kumpirmahin kung may negosyo ang user
    if (!$business) {
        return response()->json(['message' => 'User does not have an associated business'], 404);
    }

    // I-update ang discount sa Merchant model
    $business->update([
        'business_name' => $validatedData['business_name'],
        'street' => $validatedData['street'],
        'province' => $validatedData['province'],
        'zip' => $validatedData['zip'],
    ]);

    // Tukuyin ang display name batay sa role
    $name = $user->role === 'Merchant' ? $business->business_name : trim("{$user->fname} {$user->mname} {$user->lname}");

    // Log the update action
    activity()
        ->performedOn($business)
        ->causedBy($user)
        ->withProperties([
            'role' => $user->role,
            'status' => $user->status,
            'updated_fields' => array_keys($validatedData),
        ])
        ->log("$name updated their Address.");

    // Return response with updated data
    return response()->json([
        'message' => 'Address updated successfully.',
        'business' => $business
    ], 200);
}

    public function updateBusiness(Request $request)
    {
        // Kunin ang tamang rekord ng negosyo mula sa query na may left join
        $business = Merchant::leftJoin('users', 'users.id', '=', 'merchants.user_id')
            ->where('users.id', $request->user()->id)
            ->select('merchants.*')
            ->first();
        // Kumpirmahin kung mayroong negosyo na nauugnay sa user
        if (!$business) {
            return response()->json(['message' => 'User does not have associated business'], 404);
        }

        if ($request->access === 'description') {

            // Dito iproseso ang pag-update ng deskripsyon ng negosyo
            $validate = $request->validate([
                'discount' => 'required',
                'description' => 'required',
            ]);
            $validatedData = $request->only(['discount', 'description', 'tagline']);
            // I-update ang impormasyon ng negosyo
            $business->update($validatedData);
            return response()->json(['message' => 'Business Description updated successfully'], 200);
        } else {

            // Dito iproseso ang pag-update ng iba pang impormasyon ng negosyo (e.g., information)
            $validate = $request->validate([
                'business_name' => 'required',
                'business_category' => 'required',
                'business_sub_category' => 'required',
                'zip' => 'required',
                'street' => 'required',
                'city' => 'required',
                'province' => 'required',
            ]);
            $validatedData = $request->only(['business_name', 'business_category', 'business_sub_category', 'zip', 'street', 'city', 'province']);

            // I-update ang impormasyon ng negosyo
            $business->update($validatedData);
            $user = Auth::user();
            // Get the authenticated user
            $user = Auth::user();
            $name = $user->role === 'Merchant'
            ? $user->business_name
            : ($user->role === 'Influencer'
                ? $user->blog_name
                : trim($user->fname . ' ' . $user->mname . ' ' . $user->lname));



            return response()->json(['message' => 'Business information updated successfully'], 200);
        }
    }

    public function updatePassword(Request $request, UpdateUserPasswordAction $updater)
    {
        $user = $request->user();

        // Validate inputs
        $validatedData = $request->validate([
            'currentPassword' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            // Update the password
            $updater->update($user, [
                'current_password' => $validatedData['currentPassword'],
                'password' => $validatedData['password'],
            ]);

            // Get display name based on role
            $name = match ($user->role) {
                'Merchant' => $user->business_name,
                default => trim("{$user->fname} {$user->mname} {$user->lname}"),
            };

            // Log the action
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'role' => $user->role,
                    'status' => $user->status
                ])
                ->log("$name changed their password.");

            return response()->json(['message' => 'Password changed successfully'], 200);
        } catch (InvalidPasswordException $e) {
            return response()->json([
                'message' => 'The provided password does not match your current password.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to change password.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function uploadLogo(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png|max:2048', // Only accept PNG/JPG, max 2MB
        ]);

        if ($request->hasFile('logo')) {
            $previousPath = $user->getRawOriginal('avatar');

            $path = 'photos/logo/' . $user->id;
            if (!Storage::disk('public')->exists($path)) {
                Storage::disk('public')->makeDirectory($path);
            }

            $file = $request->file('logo');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs($path, $filename, 'public');

            $user->update(['avatar' => $filePath]);

            if ($previousPath && Storage::disk('public')->exists($previousPath)) {
                Storage::disk('public')->delete($previousPath);
            }

            return response()->json([
                'message' => 'Logo uploaded successfully',
                'avatar' => asset('storage/' . $filePath)
            ]);
        }

        return response()->json(['message' => 'No image uploaded'], 400);
    }


    public function uploadBackground(Request $request)
    {
        $request->validate([
            'photo1' => 'nullable|image|max:2048',
            'photo2' => 'nullable|image|max:2048',
            'photo3' => 'nullable|image|max:2048',
        ]);

        $user = $request->user();
        $merchant = Merchant::where('user_id', $user->id)->first();
        $path = "photos/background/{$user->id}";

        // Ensure the directory exists
        if (!Storage::disk('public')->exists($path)) {
            Storage::disk('public')->makeDirectory($path);
        }

        $paths = [];
        foreach (['photo1', 'photo2', 'photo3'] as $photo) {
            if ($request->hasFile($photo)) {
                // Delete existing file if it exists
                if ($merchant->$photo) {
                    Storage::disk('public')->delete($merchant->$photo);
                }

                // Store new file and save its path
                $paths[$photo] = $request->file($photo)->store($path, 'public');
            }
        }

        // Update merchant record with new paths
        $merchant->update([
            'photo1' => $paths['photo1'] ?? $merchant->photo1,
            'photo2' => $paths['photo2'] ?? $merchant->photo2,
            'photo3' => $paths['photo3'] ?? $merchant->photo3,
        ]);

        return response()->json(['message' => 'Images Uploaded Successfully', 'paths' => $paths]);
    }


//     public function uploadBackground(Request $request)
// {
//     // Validate that all three photos are present

//     // Store each photo separately
//     if ($request->hasFile('photo1')) {
//         $photo1 = Storage::put('/photos/background', $request->file('photo1'));
//         $request->user()->update(['photo1' => $photo1]);
//     }

//     if ($request->hasFile('photo2')) {
//         $photo2 = Storage::put('/photos/background', $request->file('photo2'));
//         $request->user()->update(['photo2' => $photo2]);

//     }

//     if ($request->hasFile('photo3')) {
//         $photo3 = Storage::put('/photos/background', $request->file('photo3'));
//         $request->user()->update(['photo3' => $photo3]);

//     }

//     // Update user's data with the paths to the uploaded photos

//     return response()->json(['message' => 'Background Uploaded Successfully']);
// }








}

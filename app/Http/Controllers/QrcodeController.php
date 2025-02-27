<?php

namespace App\Http\Controllers;

use App\Models\CardCode;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QrcodeController extends Controller
{
    public function merchantQrCode(Request $request)
    {
        // Fetch the card_number from the request
        $cardNumber = $request->input('card_number');

        // Check if the card_number exists in the card_codes table
        $Customer_Code = Customer::where('customer_card_num', $cardNumber)->first();

        if ($Customer_Code !== null) {
            if ($Customer_Code->status == 1) {
                // Redirect to merchant.discount.register
                return response()->json([
                    'message' => 'Redirecting to Merchant Discount Register',
                    'card_exists' => true,
                    'redirect_route' => 'merchant.discount.register',
                    'card_number' => $cardNumber,
                ], 200);
            }
        }

        // If the card is not found, return an error message
        return response()->json([
            'message' => 'Card not available',
            'card_exists' => false,
            'card_number' => $cardNumber,
        ], 200);
    }



    public function influencerQrCode(Request $request)
    {
        // Fetch the card_number from the request
        $cardNumber = $request->input('card_number');

        // Check if the card_number exists in the card_codes table
        $cardCode = CardCode::where('card_number', $cardNumber)->first();

        if ($cardCode !== null) {
            if ($cardCode->status == 0) {
                // Redirect to 'merchant.customer.scan'
                return response()->json([
                    'message' => 'Redirecting to Influencer Customer Scan',
                    'card_exists' => true,
                    'redirect_route' => 'influencer.customer.scan',
                    'card_number' => $cardNumber,
                ], 200);
            } elseif ($cardCode->status == 1) {
                // Redirect to 'merchant.loyalty-stars.scan'
                return response()->json([
                    'message' => 'Redirecting to Influencer Loyalty Stars Scan',
                    'card_exists' => true,
                    'redirect_route' => 'customer.information',
                    'card_number' => $cardNumber,
                ], 200);
            }
        } else {
            // If the card is not found in card_codes table, return a "Not Available" message
            return response()->json([
                'message' => 'Card not available',
                'card_exists' => false,
                'card_number' => $cardNumber,
            ], 200);
        }
        // Get the authenticated user
        $user = Auth::user();
        $name = $user->role === 'Merchant'
        ? $user->business_name
        : ($user->role === 'Influencer'
            ? $user->blog_name
            : trim($user->fname . ' ' . $user->mname . ' ' . $user->lname));

        // activity()
        //     ->performedOn($user)
        //     ->causedBy($user)
        // ->withProperties(['role' => $user->role, 'status' => $user->status])
        //     ->log("$name scanned the QR code");
    }




    public function homeQrCode(Request $request) {
        // Fetch the card_number from the request
        $cardNumber = $request->input('card_number');

        // Retrieve the customer based on the card_number
        $card = Customer::where('customer_card_num', $cardNumber)->first();

        if ($card !== null) {
            // If the card is found, return the card details and a success indicator
            return response()->json([
                'message' => 'Customer Card found',
                'card_exists' => true,
                'card_number' => $cardNumber,
            ], 200);
        } else {
            // If the card is not found, return an error message
            return response()->json(['error' => 'Card Number Not Found'], 404); // Use 404 for not found
        }
    }

    }

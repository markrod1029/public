<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\CardCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CardCodeController extends Controller
{

    public function index()
    {
        $searchFields = [
            'influencers.influencer_code',
            'merchants.business_code',
            'card_codes.card_number',
            'card_codes.card_types',
            'merchants.business_name',
            'influencers.blog_name',
        ];

        $tripidkards = User::query()
            ->leftJoin('card_codes', 'card_codes.user_id', '=', 'users.id')
            ->leftJoin('influencers', 'influencers.user_id', '=', 'users.id')
            ->leftJoin('merchants', 'merchants.user_id', '=', 'users.id')
            ->select(
                'card_codes.card_number',
                'card_codes.card_types',

                // Pagsamahin ang business_name o blog_name depende sa kung alin ang meron
                DB::raw('COALESCE(merchants.business_name, influencers.blog_name) as business_or_blog_name')
            )
            ->when(request('query'), function ($query, $searchQuery) use ($searchFields) {
                $query->where(function ($query) use ($searchFields, $searchQuery) {
                    foreach ($searchFields as $field) {
                        $query->orWhere($field, 'like', "%{$searchQuery}%");
                    }
                });
            })
            ->where('card_codes.status', '=', 0)
            ->get();

        return response()->json($tripidkards);
    }

    public function merchantIndex()
    {

        $user = auth()->user();

        $searchFields = [
            'influencers.influencer_code',
            'merchants.business_code',
            'card_codes.card_number',
            'card_codes.card_types',
            'merchants.business_name',
            'influencers.blog_name',
        ];

        $tripidkards = User::query()
            ->leftJoin('card_codes', 'card_codes.user_id', '=', 'users.id')
            ->leftJoin('influencers', 'influencers.user_id', '=', 'users.id')
            ->leftJoin('merchants', 'merchants.user_id', '=', 'users.id')
            ->select(
                'card_codes.card_number',
                'card_codes.card_types',
                // Pagsamahin ang business_name o blog_name depende sa kung alin ang meron
                DB::raw('COALESCE(merchants.business_name, influencers.blog_name) as business_or_blog_name')
            )
            ->when(request('query'), function ($query, $searchQuery) use ($searchFields) {
                $query->where(function ($query) use ($searchFields, $searchQuery) {
                    foreach ($searchFields as $field) {
                        $query->orWhere($field, 'like', "%{$searchQuery}%");
                    }
                });
            })
            ->where('card_codes.status', '=', 0)
            ->where('card_codes.user_id', $user->id)
            ->get();

        return response()->json($tripidkards);
    }


    public function store(Request $request)
    {
        // Validate incoming request data
        $validated = $request->validate([
            'tripidkard_number' => 'required|numeric', // Example validation rule, adjust as needed
            'user_id' => 'required',
            'card_types' => 'required',
            // Add more validation rules as needed
        ]);

        // Retrieve current user
        $user = $request->user();

        // Retrieve the number of tripidkards to generate
        $numberOfTripidkards = $request->input('tripidkard_number');
        $user_id = $validated['user_id'];
        $card_types = $validated['card_types'];

        // Initialize an empty array to hold the card codes
        $cardCodes = [];

        // Loop to generate multiple customer codes
        for ($i = 0; $i < $numberOfTripidkards; $i++) {
            $cardNumber = $this->generateCardCode();
            $cardCodes[] = [
                'user_id' => $user_id,
                'card_number' => $cardNumber,
                'card_types' => $card_types,
                'status' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert the generated card codes in a single query
        CardCode::insert($cardCodes);

          // Get the authenticated user
          $user = Auth::user();
        $name = $user->role === 'Merchant'
        ? $user->business_name
        : ($user->role === 'Influencer'
            ? $user->blog_name
            : trim($user->fname . ' ' . $user->mname . ' ' . $user->lname));

        // Log the activity
        activity()
            ->causedBy($user)
            ->withProperties(['role' => $user->role, 'status' => $user->status])
            ->log("$name generated $numberOfTripidkards tripidkards.");

        // Optionally, you can return a response or redirect the user
        return response()->json(['message' => 'Tripidkards generated successfully']);
    }



    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'tripidkard_number' => 'required|numeric',
    //         'user_id' => 'required',
    //         'card_types' => 'required',
    //     ]);

    //     $userId = $validated['user_id'];
    //     $cardTypes = $validated['card_types'];
    //     $numberOfTripidkards = $validated['tripidkard_number'];

    //     // Dispatch the job to generate card codes in the background
    //     GenerateCardCodes::dispatch($userId, $cardTypes, $numberOfTripidkards);

    //     return response()->json(['message' => 'Card generation is in progress']);
    // }


    public function generateCardCode()
    {
        $currentYear = date('Y');
        $lastCardCode = CardCode::where('card_number', 'like', $currentYear . '-%')->latest('card_number')->value('card_number');
        $lastSerialNumber = 1;
        if ($lastCardCode) {
            $lastSerialNumber = intval(substr($lastCardCode, -7)) + 1;
        }
        return $currentYear . '-0C-' . str_pad($lastSerialNumber, 7, '0', STR_PAD_LEFT);
    }

    public function count()
    {
        $user = auth()->user(); // Get the authenticated user

        if ($user->role === 'Admin') {
            // Admin counts all customers
            $cardCount = CardCode::where('status', 0)
                ->count();

        } elseif ($user->role === 'Merchant' || $user->role === 'Influencer') {
            // Merchant counts only their own customers
            $cardCount = CardCode::where('status', 0)
                ->where('user_id', $user->id) // Assuming you have a foreign key linking customers to merchants
                ->count();

        } else {
            // If the role doesn't match, return 0 or an appropriate message
            $cardCount = 0; // or return response()->json(['error' => 'Unauthorized'], 403);
        }
        return response()->json(['count' => $cardCount]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user(); // Tamang paraan ng pagkuha ng authenticated user

        $searchFields = [
            'discounts.card_number',
            'discounts.product_price',
            'discounts.discount', // Tama ang column name
            'customers.full_name',
        ];

        $discounts = Discount::query()
            ->select([
                'discounts.user_id',
                'discounts.card_number',
                'discounts.product_price',
                'discounts.discount AS total_discount', // Idagdag ang column na ito
                DB::raw("CONCAT_WS(' ', COALESCE(customers.fname, ''), COALESCE(customers.mname, ''), COALESCE(customers.lname, '')) AS customer_name"),
            ])
            ->leftJoin('customers', 'discounts.card_number', '=', 'customers.customer_card_num')
            ->when(request('query'), function ($query, $searchQuery) use ($searchFields) {
                $query->where(function ($query) use ($searchFields, $searchQuery) {
                    foreach ($searchFields as $field) {
                        $query->orWhere($field, 'like', "%{$searchQuery}%");
                    }
                });
            })
            ->where('discounts.user_id', '=', $user->id)
            ->get();

        return response()->json([
            'percentage' => $user->discount, // Ibalik ang discount percentage ng user
            'discounts' => $discounts
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user(); // Tamang paraan ng pagkuha ng authenticated user

        // Validate the request data
        $validated = $request->validate([
            'card_number' => 'required',
            'price' => 'required|numeric',
            'discount' => 'required|numeric',
        ]);

        try {
            // Insert data for Discount
            $discount = Discount::create([
                'user_id' => $user->id,
                'card_number' => $validated['card_number'],
                'product_price' => $validated['price'],
                'discount' => $validated['discount'],

            ]);

            // Activity Log for Discount
            activity()
                ->performedOn($discount) // Mas tamang i-log ang bagong discount kaysa sa user
                ->causedBy($user)
                ->withProperties([
                    'role' => $user->role,
                    'status' => $user->status,
                ])
                ->log("{$user->fname} {$user->lname} applied a discount on card {$validated['card_number']} with price {$validated['price']}");

            // Return success response
            return response()->json([
                'message' => 'Discount applied successfully!',
                'success' => true,
                'data' => $discount
            ], 200);

        } catch (\Exception $e) {
            // Return error response kung may issue sa pag-save ng discount
            return response()->json([
                'message' => 'Failed to apply discount!',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

}

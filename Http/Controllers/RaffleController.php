<?php

namespace App\Http\Controllers;

use App\Models\Raffle;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RaffleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user(); // Kunin ang authenticated user

        $raffles = Raffle::where('user_id', $user->id)->get();

        return response()->json($raffles);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user(); // Kunin ang authenticated user

        // Check kung may existing raffle na active (status = 0)
        $existingRaffle = Raffle::where('user_id', $user->id)
                                ->where('status', 0)
                                ->first();

        if ($existingRaffle) {
            return response()->json([
                'message' => 'error',
                'error' => 'You already have an active raffle. Please delete or complete it first.'
            ], 400);
        }

        // Validation Rules
        $request->validate([
            'title' => 'required|string|max:255',
            'prize' => 'required|string|max:255',
            'mechanics' => 'required|string',
            'total_winner' => 'required|integer|min:1',
            'entries_deadline' => 'required|date',
            'draw_date' => 'required|date|after:entries_deadline',
            'image' => 'nullable|image|mimes:jpeg,png|max:2048',
        ]);

        // Handle Image Upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('photos/raffle', 'public');
        }

        // Create New Raffle
        $raffle = Raffle::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'prize' => $request->prize,
            'mechanics' => $request->mechanics,
            'total_winner' => $request->total_winner,
            'entries_deadline' => $request->entries_deadline,
            'draw_date' => $request->draw_date,
            'image' => $imagePath,
            'status' => 0,
        ]);

        return response()->json([
            'message' => 'success',
            'data' => $raffle,
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function raffleEdit($id)
    {
        // Kunin ang merchant gamit ang user_id at i-join sa raffles
        $merchantWithRaffle = Merchant::with('raffles')
            ->where('id', $id)
            ->first(); // ✅ Para hindi mag-error

        if (!$merchantWithRaffle) {
            return response()->json(['error' => 'Merchant not found.'], 404);
        }

        return response()->json($merchantWithRaffle);
    }


    public function raffleShow($id)
    {


        $raffles = Raffle::where('id', $id)->get();

        return response()->json($raffles);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

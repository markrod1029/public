<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bookmark;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    public function index()
    {
        $bookmarks = Bookmark::where('user_id', Auth::id())->get(['merchant_id']);
        return response()->json($bookmarks);
    }

    public function store(Request $request)
    {
        $request->validate(['merchant_id' => 'required|exists:merchants,id']);

        Bookmark::firstOrCreate([
            'merchant_id' => $request->merchant_id,
            'user_id' => Auth::id(),
        ]);

        return response()->json(['message' => 'Bookmark added'], 201);
    }

    public function destroy($merchant_id)
    {
        Bookmark::where('merchant_id', $merchant_id)
            ->where('user_id', Auth::id())
            ->delete();

        return response()->json(['message' => 'Bookmark removed'], 200);
    }
}

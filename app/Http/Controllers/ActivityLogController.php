<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity; // Import the correct Activity model

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {


        // Define fields that can be searched in the activity log
        $searchFields = ['properties->role', 'causer.fname', 'causer.lname', 'description'];

        // Fetch activity logs with filtering and search logic
        $logs = Activity::query()
            ->with('causer') // Eager load causer relationship
            ->when($request->query('query'), function ($query, $searchQuery) use ($searchFields) {
                $query->where(function ($subQuery) use ($searchFields, $searchQuery) {
                    foreach ($searchFields as $field) {
                        // Handle nested JSON or relationship fields
                        if ($field === 'properties->role') {
                            $subQuery->orWhere('properties->role', 'like', "%{$searchQuery}%");
                        } elseif (in_array($field, ['causer.fname', 'causer.mname', 'causer.lname'])) {
                            // Use a relationship query for causer fields
                            $subQuery->orWhereHas('causer', function ($causerQuery) use ($field, $searchQuery) {
                                $column = str_replace('causer.', '', $field); // Get the actual column name
                                $causerQuery->where($column, 'like', "%{$searchQuery}%");
                            });
                        } else {
                            // Default case for normal fields
                            $subQuery->orWhere($field, 'like', "%{$searchQuery}%");
                        }
                    }
                });
            })
            ->latest() // Order by latest activity log
            ->get();

        return response()->json($logs);


    }


    public function indexUser(Request $request)
    {
         // Define fields that can be searched in the activity log
    $searchFields = ['properties->role', 'causer.fname', 'causer.lname', 'description'];

    // Get the authenticated user's ID
    $user = $request->user(); // Get the authenticated user from the request
    $user_id = $user->id;

    // Fetch activity logs with filtering and search logic
    $logs = Activity::query()
        ->with('causer') // Eager load causer relationship
        ->where('causer_id', $user_id) // Filter logs by the authenticated user's ID
        ->when($request->query('query'), function ($query, $searchQuery) use ($searchFields) {
            $query->where(function ($subQuery) use ($searchFields, $searchQuery) {
                foreach ($searchFields as $field) {
                    // Handle nested JSON or relationship fields
                    if ($field === 'properties->role') {
                        $subQuery->orWhere('properties->role', 'like', "%{$searchQuery}%");
                    } elseif (in_array($field, ['causer.fname', 'causer.mname', 'causer.lname'])) {
                        // Use a relationship query for causer fields
                        $subQuery->orWhereHas('causer', function ($causerQuery) use ($field, $searchQuery) {
                            $column = str_replace('causer.', '', $field); // Get the actual column name
                            $causerQuery->where($column, 'like', "%{$searchQuery}%");
                        });
                    } else {
                        // Default case for normal fields
                        $subQuery->orWhere($field, 'like', "%{$searchQuery}%");
                    }
                }
            });
        })
        ->latest() // Order by latest activity log
        ->get();

    return response()->json($logs);


    }
}

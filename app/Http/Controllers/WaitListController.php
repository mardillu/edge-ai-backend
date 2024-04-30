<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WaitList;

class WaitListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json([
            'message' => 'Not implemented'
        ], 501);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'string|email',
        ]);

        $email = WaitList::where('email', $request->email)->first();
        $phone = WaitList::where('phone', $request->phone)->first();

        if ($email !== null) {
            return response()->json([
                'message' => 'You are already on our waiting list'
            ], 409);
        }

        if ($phone !== null) {
            return response()->json([
                'message' => 'You are already on our waiting list'
            ], 409);
        }

        if ($request->email !== null) {
            $user = WaitList::create([
                'phone' => 'NA',
                'email' => $request->email,
            ]);
            return response()->json([
                'data' => $user,
                'message' => 'Email successfully added to wait list'
            ], 201);
        } else if ($request->phone !== null) {
            $user = WaitList::create([
                'phone' =>  $request->phone,
                'email' => 'NA',
            ]);
            return response()->json([
                'data' => $user,
                'message' => 'Phone successfully added to wait list'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Phone or email is required'
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response()->json([
            'message' => 'Not implemented'
        ], 501);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return response()->json([
            'message' => 'Not implemented'
        ], 501);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return response()->json([
            'message' => 'Not implemented'
        ], 501);
    }
}

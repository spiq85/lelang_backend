<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BidSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $users = User::when($request->role, function($query, $role) {
                return $query->where('role', $role);
            })
            ->when($request->is_active !== null, function($query) use ($request) {
                return $query->where('is_active', $request->is_active);
            })
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'nullable|string|max:20',
            'npwp' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,seller,bidder',
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'npwp' => $request->npwp,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => $request->is_active,
        ]);

        return response()->json($user, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = User::with(['products', 'auctionBatches', 'bids'])->findOrFail($id);
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'phone_number' => 'sometimes|nullable|string|max:20',
            'npwp' => 'sometimes|nullable|string|max:20',
            'password' => 'sometimes|nullable|string|min:8',
            'role' => 'sometimes|required|in:admin,seller,bidder',
            'is_active' => 'sometimes|required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = $request->except('password');

        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json($user);
    }

    /**F
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Delete user's products and related data
        foreach ($user->products as $product) {
            // Delete product images
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->image_url);
            }
            $product->images()->delete();

            // Delete product categories
            $product->categories()->detach();

            // Delete product
            $product->delete();
        }

        // Delete user's auction batches and related bids
        foreach ($user->auctionBatches as $batch) {
            $batch->bids()->delete();
            $batch->delete();
        }

        // Delete user's bids
        $user->bids()->delete();

        // Delete user
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Get user profile
     */
    public function profile()
    {
        $user = auth()->user();
        return response()->json($user);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'npwp' => 'nullable|string|max:20',
        ]);

        $user->full_name = $request->full_name;
        $user->phone_number = $request->phone_number;
        $user->npwp = $request->npwp;
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Get user's auction history
     */
    public function auctionHistory(Request $request)
    {
        $user = auth()->user();

        $bids = $user->bids()
            ->with(['batch.product', 'batch.seller'])
            ->orderByDesc('submitted_at')
            ->paginate($request->get('per_page', 15));

        return response()->json($bids);
    }

    /**
     * Upload payment proof for a won auction
     */
    public function uploadPaymentProof(Request $request, $bidId)
    {
        $request->validate([
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $bid = Bidset::findOrFail($bidId);

        // Check if bid belongs to this user and status is winner
        if ($bid->user_id !== auth()->id() || $bid->status !== 'winner') {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Handle file upload
        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $path = $file->store('payment_proofs', 'public');

            // Update bid with payment proof path
            $bid->payment_proof_path = $path;
            $bid->payment_status = 'pending';
            $bid->save();

            return response()->json([
                'message' => 'Payment proof uploaded successfully',
                'bid' => $bid
            ]);
        }

        return response()->json([
            'message' => 'Failed to upload payment proof'
        ], 500);
    }
}

<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wishlist;
use App\Models\Product;

class WishlistController extends Controller
{
    // GET /api/wishlist
    public function index(Request $req)
    {
        $items = Wishlist::with('product')
            ->where('user_id', $req->user()->id)
            ->get()
            ->map(fn($w) => $w->product);
            
        return response()->json([
            'success' => true,
            'data'    => $items
        ]);
    }

    // POST /api/wishlist/add
    public function add(Request $req)
    {
        $req->validate(['product_id'=>'required|exists:products,id']);

        // create or ignore if exists
        Wishlist::firstOrCreate([
            'user_id'    => $req->user()->id,
            'product_id' => $req->product_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Added to wishlist'
        ]);
    }

    // DELETE /api/wishlist/remove/{id}
    public function remove(Request $req, $id)
    {
        $deleted = Wishlist::where([
            'user_id'    => $req->user()->id,
            'product_id' => $id
        ])->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted
                ? 'Removed from wishlist'
                : 'Item was not in your wishlist'
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::orderBy('name')
            ->paginate(20);

        return response()->json($products);
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }

    public function store(Request $request)
    {
        if (!$request->user()->canManageProducts()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::create([
            'name' => $request->name,
            'amount' => $request->amount,
            'description' => $request->description,
            'is_active' => $request->get('is_active', true),
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    public function update(Product $product, Request $request)
    {
        if (!$request->user()->canManageProducts()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0.01',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = [];
        
        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }
        
        if ($request->has('amount')) {
            $updateData['amount'] = $request->amount;
        }
        
        if ($request->has('description')) {
            $updateData['description'] = $request->description;
        }
        
        if ($request->has('is_active')) {
            $updateData['is_active'] = $request->boolean('is_active');
        }

        $product->update($updateData);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }

    public function destroy(Product $product, Request $request)
    {
        if (!$request->user()->canManageProducts()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if product is used in any transaction
        if ($product->transactionProducts()->exists()) {
            return response()->json([
                'message' => 'Cannot delete product. It has been used in transactions.'
            ], 400);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}

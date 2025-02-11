<?php

namespace App\Http\Controllers\products;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the products, with a search feature.
     * 
     */
    public function index(Request $request)
    {
        $search = $request->get('search'); // Get the search term from the query string

        $products = Product::query(); // Start building the query

        if ($search) {
            $products->where('name', 'LIKE', "%$search%") // Search in the name column
                ->orWhere('id', $search); // Search in the id column
        }

        $products = $products->paginate(10); // Paginate after applying the search

        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Store a newly created products in storage.
     */
    public function store(Request $request)
    {
        $existingProduct = Product::onlyTrashed()->where('name', $request->name)->first();

        if ($existingProduct) {
            $existingProduct->forceDelete(); // Permanently delete the record
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:products,name'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validatedData = $validator->validated(); // Get the validated data

        Product::create($validatedData); // Use the validated data

        return redirect()->route('products.index')->with('success', __('messages.created_successfully'));
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:products,name,' . $product->id
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $product->update($validator->validated());
        return redirect()->route('products.index')->with('success', __('messages.updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', __('messages.deleted_successfully'));
    }
}

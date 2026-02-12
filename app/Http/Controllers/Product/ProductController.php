<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'name' => 'required|string|max:255|unique:products,name',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validatedData = $validator->validated(); // Get the validated data

        if ($request->hasFile('image')) {
            $validatedData['image_path'] = $request->file('image')->store('products', 'public');
        }

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
            'name' => 'required|string|max:255|unique:products,name,' . $product->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);
        return redirect()->route('products.index')->with('success', __('messages.updated_successfully'));
    }

    /**
     * Remove the image from the specified product.
     */
    public function destroyImage(Product $product)
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
            $product->update(['image_path' => null]);
        }
        return redirect()->route('products.edit', $product)->with('success', __('messages.image_deleted'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();
        return redirect()->route('products.index')->with('success', __('messages.deleted_successfully'));
    }
}

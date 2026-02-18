<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\ProductServicePrice;
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
        $search = $request->get('search');

        $products = Product::withCount('productServicePrices');

        if ($search) {
            $products->where('name', 'LIKE', "%$search%")
                ->orWhere('id', $search);
        }

        $products = $products->paginate(10);

        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        $services = ProductService::all();
        return view('products.create', compact('services'));
    }

    /**
     * Store a newly created products in storage.
     */
    public function store(Request $request)
    {
        $existingProduct = Product::onlyTrashed()->where('name', $request->name)->first();

        if ($existingProduct) {
            $existingProduct->forceDelete();
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:products,name',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'services' => 'nullable|array',
            'services.*.enabled' => 'nullable|boolean',
            'services.*.price' => 'nullable|numeric|min:0|max:9999.999',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validatedData = $validator->validated();

        if ($request->hasFile('image')) {
            $validatedData['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'name' => $validatedData['name'],
            'image_path' => $validatedData['image_path'] ?? null,
        ]);

        if (isset($validatedData['services'])) {
            foreach ($validatedData['services'] as $serviceId => $serviceData) {
                if (isset($serviceData['enabled']) && $serviceData['enabled'] && isset($serviceData['price']) && $serviceData['price'] !== null) {
                    ProductServicePrice::create([
                        'product_id' => $product->id,
                        'product_service_id' => $serviceId,
                        'price' => $serviceData['price'],
                    ]);
                }
            }
        }

        return redirect()->route('products.index')->with('success', __('messages.created_successfully'));
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        $product->load(['productServicePrices.productService']);
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        $services = ProductService::all();
        $productServicePrices = $product->productServicePrices->keyBy('product_service_id');
        return view('products.edit', compact('product', 'services', 'productServicePrices'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:products,name,' . $product->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'services' => 'nullable|array',
            'services.*.enabled' => 'nullable|boolean',
            'services.*.price' => 'nullable|numeric|min:0|max:9999.999',
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

        $product->update([
            'name' => $data['name'],
            'image_path' => $data['image_path'] ?? $product->image_path,
        ]);

        if (isset($data['services'])) {
            $product->productServicePrices()->delete();
            
            foreach ($data['services'] as $serviceId => $serviceData) {
                if (isset($serviceData['enabled']) && $serviceData['enabled'] && isset($serviceData['price']) && $serviceData['price'] !== null) {
                    ProductServicePrice::create([
                        'product_id' => $product->id,
                        'product_service_id' => $serviceId,
                        'price' => $serviceData['price'],
                    ]);
                }
            }
        }

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

    /**
     * Get available services and prices for a specific product (API endpoint).
     */
    public function getServicePrices(Product $product)
    {
        $servicePrices = $product->productServicePrices()
            ->with('productService')
            ->get()
            ->map(function ($price) {
                return [
                    'id' => $price->product_service_id,
                    'name' => $price->productService->name,
                    'price' => number_format((float) $price->price, 3),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'services' => $servicePrices,
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\ProductService;

use App\Http\Controllers\Controller;
use App\Models\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search'); // Get the search term from the query string

        $productServices = ProductService::query(); // Start building the query

        if ($search) {
            $productServices->where('name', 'LIKE', "%$search%") // Search in the name column
                ->orWhere('id', $search); // Search in the id column
        }

        $productServices = $productServices->paginate(10);
        return view('product_services.index', compact('productServices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('product_services.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        ProductService::create($validatedData);

        return redirect()->route('product_services.index')->with('success', __('messages.created_successfully'));
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductService $productService)
    {
        return view('product_services.show', compact('productService'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductService $productService)
    {
        return view('product_services.edit', compact('productService'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductService $productService)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $productService->update($validatedData);

        return redirect()->route('product_services.index')->with('success', __('messages.updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductService $productService)
    {
        $productService->delete();

        return redirect()->route('product_services.index')->with('success', __('messages.deleted_successfully'));
    }
}

<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreProductRequest;
use App\Http\Requests\Company\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * Store a newly created product in storage.
     *
     * @param  \App\Http\Requests\Company\StoreProductRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        if (!$companyId) {
            return response()->json(['message' => 'User is not associated with a company.'], 403);
        }

        $productData = $request->validated();
        $productData['company_id'] = $companyId;
        $productData['status'] = 'draft';

        $product = Product::create($productData);

        return response()->json($product, 201);
    }

    /**
     * Update the specified product in storage.
     *
     * @param  \App\Http\Requests\Company\UpdateProductRequest  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        return response()->json($product);
    }

    /**
     * Submit the specified product for review.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function submit(Request $request, Product $product): JsonResponse
    {
        $this->authorize('submit', $product);

        $product->status = 'submitted';
        $product->save();

        return response()->json(['message' => 'Product submitted for review.', 'product' => $product]);
    }
}
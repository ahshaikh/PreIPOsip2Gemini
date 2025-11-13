<?php
// V-FINAL-1730-186

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        return Faq::orderBy('category_id')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'category_id' => 'nullable|integer' 
        ]);
        
        $faq = Faq::create($validated);
        return response()->json($faq, 201);
    }

    public function update(Request $request, Faq $faq)
    {
        $faq->update($request->all());
        return response()->json($faq);
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();
        return response()->noContent();
    }
}
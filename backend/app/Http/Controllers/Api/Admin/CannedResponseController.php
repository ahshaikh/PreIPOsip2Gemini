<?php
// V-FINAL-1730-486 (Created)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CannedResponse;
use Illuminate{Http\Request;

class CannedResponseController extends Controller
{
    // GET /admin/canned-responses
    public function index()
    {
        return CannedResponse::where('is_active', true)->orderBy('title')->get();
    }

    // POST /admin/canned-responses
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);
        $response = CannedResponse::create($validated);
        return response()->json($response, 201);
    }
    
    // PUT /admin/canned-responses/{id}
    public function update(Request $request, CannedResponse $cannedResponse)
    {
         $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean'
        ]);
        $cannedResponse->update($validated);
        return response()->json($cannedResponse);
    }
    
    // DELETE /admin/canned-responses/{id}
    public function destroy(CannedResponse $cannedResponse)
    {
        $cannedResponse->delete();
        return response()->noContent();
    }
}
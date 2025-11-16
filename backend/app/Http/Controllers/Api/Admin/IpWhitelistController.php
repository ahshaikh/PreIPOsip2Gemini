<?php
// V-FINAL-1730-542 (Created)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpWhitelist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IpWhitelistController extends Controller
{
    private function clearCache()
    {
        Cache::forget('ip_whitelist.active');
    }

    public function index()
    {
        return IpWhitelist::latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ip_address' => 'required|string|unique:ip_whitelist,ip_address',
            'description' => 'required|string',
            'is_active' => 'boolean',
        ]);
        
        $ip = IpWhitelist::create($validated);
        $this->clearCache();
        
        return response()->json($ip, 201);
    }

    public function update(Request $request, IpWhitelist $ipWhitelist)
    {
        $validated = $request->validate([
            'ip_address' => 'required|string|unique:ip_whitelist,ip_address,' . $ipWhitelist->id,
            'description' => 'required|string',
            'is_active' => 'boolean',
        ]);
        
        $ipWhitelist->update($validated);
        $this->clearCache();
        
        return response()->json($ipWhitelist);
    }

    public function destroy(IpWhitelist $ipWhitelist)
    {
        $ipWhitelist->delete();
        $this->clearCache();
        
        return response()->noContent();
    }
}
<?php
// V-PHASE2-1730-060


namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index()
    {
        return EmailTemplate::all();
    }

    public function store(Request $request)
    {
        // This would typically be seeded, but adding for completeness
        $validated = $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:email_templates',
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);
        
        $template = EmailTemplate::create($validated);
        return response()->json($template, 201);
    }

    public function show(EmailTemplate $emailTemplate)
    {
        return $emailTemplate;
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);
        
        $emailTemplate->update($validated);
        return response()->json($emailTemplate);
    }
}
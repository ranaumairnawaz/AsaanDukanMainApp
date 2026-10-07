<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ThemeController extends Controller
{
    public function index()
    {
        $themes = Theme::orderBy('name')->get();
        return Inertia::render('Admin/Themes/Index', [
            'themes' => $themes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:themes',
            'demo_url' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'is_premium' => 'boolean',
            'screenshot' => 'nullable|image|max:10240',
        ]);

        $theme = new Theme();
        $theme->name = $validated['name'];
        $theme->slug = Str::slug($validated['slug']);
        $theme->demo_url = $validated['demo_url'];
        $theme->is_active = $validated['is_active'] ?? true;
        $theme->is_premium = $validated['is_premium'] ?? true;

        if ($request->hasFile('screenshot')) {
            $theme->screenshot_path = $request->file('screenshot')->store('themes', 'public');
        }

        $theme->save();

        return back()->with('success', 'Theme created successfully.');
    }

    public function update(Request $request, Theme $theme)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:themes,slug,' . $theme->id,
            'demo_url' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'is_premium' => 'boolean',
            'screenshot' => 'nullable|image|max:10240',
        ]);

        $theme->name = $validated['name'];
        $theme->slug = Str::slug($validated['slug']);
        $theme->demo_url = $validated['demo_url'];
        $theme->is_active = $validated['is_active'] ?? true;
        $theme->is_premium = $validated['is_premium'] ?? true;

        if ($request->hasFile('screenshot')) {
            $theme->screenshot_path = $request->file('screenshot')->store('themes', 'public');
        }

        $theme->save();

        return back()->with('success', 'Theme updated successfully.');
    }

    public function destroy(Theme $theme)
    {
        $theme->delete();
        return back()->with('success', 'Theme deleted.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Event;
use App\Models\NewsItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CommunityContentController extends Controller
{
    protected function getModelClass(string $type)
    {
        return match ($type) {
            'announcements' => Announcement::class,
            'events' => Event::class,
            'news' => NewsItem::class,
            default => abort(404, 'Invalid content type'),
        };
    }

    protected function validateData(Request $request, string $type)
    {
        $commonRules = [
            'title' => 'required|string|max:255',
            'is_published' => 'boolean',
            'image' => 'nullable|image|max:5120',
        ];

        $specificRules = match ($type) {
            'announcements' => [
                'body' => 'required|string',
            ],
            'events' => [
                'description' => 'required|string',
                'event_date' => 'required|date',
                'location' => 'nullable|string|max:255',
            ],
            'news' => [
                'summary' => 'required|string|max:500',
                'body' => 'required|string',
            ],
        };

        return $request->validate(array_merge($commonRules, $specificRules));
    }

    /**
     * Admin view for Community Content
     */
    public function adminIndex(Request $request)
    {
        abort_unless($request->user() && $request->user()->is_admin, 403, 'Unauthorized access.');

        return Inertia::render('Admin/Community/Index', [
            'announcements' => Announcement::with('author')->latest()->get(),
            'events' => Event::with('author')->latest()->get(),
            'news' => NewsItem::with('author')->latest()->get(),
        ]);
    }

    /**
     * API methods for both Admin (full) and Merchant (published only)
     */
    public function index(Request $request, string $type)
    {
        $model = $this->getModelClass($type);
        $query = $model::with('author')->latest();

        // If not admin, only show published
        if (!$request->user() || !$request->user()->is_admin) {
            $query->where('is_published', true);
        }

        return response()->json($query->paginate(20));
    }

    public function show(Request $request, string $type, $id)
    {
        $model = $this->getModelClass($type);
        $query = $model::with('author');

        if (!$request->user() || !$request->user()->is_admin) {
            $query->where('is_published', true);
        }

        return response()->json($query->findOrFail($id));
    }

    /**
     * Write operations (Admin only)
     */
    public function store(Request $request, string $type)
    {
        abort_unless($request->user() && $request->user()->is_admin, 403, 'Unauthorized access.');

        $model = $this->getModelClass($type);
        $validated = $this->validateData($request, $type);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('community/' . $type, 'public');
        }

        $validated['author_id'] = $request->user()->id;

        $item = $model::create($validated);

        if ($item->is_published) {
            app(\App\Services\WhatsAppMessageService::class)->broadcastCommunityPost($item->title, $item->body ?? $item->description ?? $item->summary ?? '', $type);
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Created successfully']);
        }

        return back()->with('success', ucfirst(rtrim($type, 's')) . ' created successfully.');
    }

    public function update(Request $request, string $type, $id)
    {
        abort_unless($request->user() && $request->user()->is_admin, 403, 'Unauthorized access.');

        $model = $this->getModelClass($type);
        $item = $model::findOrFail($id);

        $validated = $this->validateData($request, $type);

        if ($request->hasFile('image')) {
            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('community/' . $type, 'public');
        }

        $item->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Updated successfully']);
        }

        return back()->with('success', ucfirst(rtrim($type, 's')) . ' updated successfully.');
    }

    public function destroy(Request $request, string $type, $id)
    {
        abort_unless($request->user() && $request->user()->is_admin, 403, 'Unauthorized access.');

        $model = $this->getModelClass($type);
        $item = $model::findOrFail($id);

        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }

        $item->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Deleted successfully']);
        }

        return back()->with('success', ucfirst(rtrim($type, 's')) . ' deleted successfully.');
    }

    public function togglePublish(Request $request, string $type, $id)
    {
        abort_unless($request->user() && $request->user()->is_admin, 403, 'Unauthorized access.');

        $model = $this->getModelClass($type);
        $item = $model::findOrFail($id);

        $item->update([
            'is_published' => !$item->is_published
        ]);

        if ($item->is_published) {
            app(\App\Services\WhatsAppMessageService::class)->broadcastCommunityPost($item->title, $item->body ?? $item->description ?? $item->summary ?? '', $type);
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Publish status updated']);
        }

        return back()->with('success', 'Status updated successfully.');
    }
}

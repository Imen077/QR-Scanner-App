<?php

namespace App\Http\Controllers\Api;

use App\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    use ApiResponse;
    public function store(Request $request)
    {
         $validated = $request->validate([
            'name' => 'required|string',
            'desc' => 'required|string',
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:png,jpg,jpeg|max:2048',
            'date' => 'required|date',
            'max_reservation' => 'required|integer|min:1',
        ]);

        $paths = [];
        if($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('events', 'public');
                $paths[] = Storage::url($path);
            }
        }
        
        $validated['images'] = $paths;
        // dd($validated);

        $event = Event::create($validated);

        return $this->succesResponse($event, 'Event created successfully', 201);
    }
    public function index() 
    {
        $events = Event::withCount([
            'tickets' => function ($query) {
                $query->where('is_canceled', false);
            },
        ])->latest()->get();
        return $this->succesResponse($events, 'Events fetched successfully', 200);
    }
    public function show($eventId) 
    {
        $event = Event::withCount([
            'tickets' => function ($query) {
                $query->where('is_canceled', false);
            }
        ])->find($eventId);

        if (!$event) {
            return $this->errorResponse('Event not found', 404);
        }

        return $this->succesResponse($event, 'Event fetched successfully', 200);
    }
       public function update(Request $request, $eventId)
    {
        $event = Event::find($eventId);

        if (!$event) {
            return $this->errorResponse('Event not found', 404);
        }

         $validated = $request->validate([
            'name' => 'required|string',
            'desc' => 'required|string',
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:png,jpg,jpeg|max:2048',
            'date' => 'required|date',
            'max_reservation' => 'required|integer|min:1',
        ]);

        $paths = [];
        if($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('events', 'public');
                $paths[] = Storage::url($path);
            }
        }
        
        $validated['images'] = $paths;

        // delete old files
        foreach ($event->images as $image) {
            $path = str_replace('storage/', '', $image);
            Storage::disk('public')->delete($path);
        }

        $event->update($validated);

        return $this->succesResponse($event, 'Event created successfully', 201);
    }
    public function delete($eventId) 
    {
        $event = Event::find($eventId);

        if (!$event) {
            return $this->errorResponse('Event not found', 404);
        }
        
        // delete old files
        foreach ($event->images as $image) {
            $path = str_replace('storage/', '', $image);
            Storage::disk('public')->delete($path);
        }

        $event->delete();

        return $this->succesResponse(null, 'Event deleted successfully', 200);
    }
}

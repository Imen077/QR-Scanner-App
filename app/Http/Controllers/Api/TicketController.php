<?php

namespace App\Http\Controllers\Api;

use App\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    use ApiResponse;
    public function store (Request $request, $eventId)
    {
        $event = Event::find($eventId);

        if (!$event) {
           return $this->errorResponse('Event not found', 404);
        }
        $user = $request->user();

        DB::beginTransaction();

        try {
                $event = Event::where('id', $eventId)->lockForUpdate()->firstOrFail();

                if ($event->date < now()) {
                    DB::rollBack();
                    return $this->errorResponse('Event has already started', 400);
                }

                $exisitingTicket = Ticket::where('user_id', $user->id)->where('event_id', $event->id)->where
                ('is_canceled', false)->exists();

                if ($exisitingTicket) {
                    DB::rollBack();
                    return $this->errorResponse('You have already reserved a ticket for this event', 400);
                }
                $currentBookings = $event->tickets()->where('is_canceled', false)->count();

                if ($currentBookings >= $event->max_reservation) {
                    DB::rollBack();
                    return $this->errorResponse('Event is fully booked', 400);
                }

              
                $payload = [
                    'un' => $user->name,     // user name
                    'ue' => $user->email,    // user email
                    'en' => $event->name,    // event name
                    'ed' => $event->date,    // event date
                ];

                $encode = base64_encode(json_encode($payload));
                  // ikutan-xxxx-payload
                  $code = 'ikutan-' . uniqid() . '-' . $encode;

                  $ticket = Ticket::create([
                      'user_id' => $user->id,
                      'event_id' => $event->id,
                      'code' => $code   
                  ]);

                  DB::commit();
                  return $this->succesResponse($ticket, 'Ticket reserved successfully');

        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage(), 500);
        }
    }
    public function indexByUser(Request $request)
    {
        $user = $request->user();

        $tickets = $user->tickets()->latest()->get(); 

        return $this->succesResponse($tickets, 'Tickets fetched successfully', 200); 
    }
    public function indexByEvent(Request $request, $eventId)
    {
        $event = Event::find($eventId);
        if (!$event) {
            return $this->errorResponse('Event not found', 404);
        }
        $tickets = $event->tickets()->where('is_canceled', false)->latest()->get();
        return $this->succesResponse($tickets, 'Tickets fetched successfully', 200);
    }
    public function cancel(Request $request, $ticketId) 
    {
        $ticket = Ticket::find($ticketId);

        if (!$ticket) {
            return $this->errorResponse('Ticket not found', 404);
        }

        if ($ticket->is_canceled) {
            return $this->errorResponse('Ticket already canceled', 400);
        }

        $ticket->is_canceled = true;
        $ticket->save();

        return $this->succesResponse(null, 'Ticket canceled successfully', 200);
    }
    public function checkIn(Request $request) 
    {
        $code = $request->input('code');
        $ticket = Ticket::where('code', $code)->where('is_canceled', false)->first();
        if (!$ticket) {
            return $this->errorResponse('Ticket not found', 404);
        }
        if ($ticket->is_canceled) {
            return $this->errorResponse('Ticket already canceled', 400);
        }
        if ($ticket->checked_at) {
            return $this->errorResponse('Ticket already checked in', 400);
        }
        $ticket->checked_at = now();
        $ticket->save();
        return $this->succesResponse(null, 'Ticket checked in successfully', 200);
    }
}
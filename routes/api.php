Muhamad Parisz, Now
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// route group

/*
|--------------------------------------------------------------------------
| Protected Routes -> Route group = harus ke auntentikasi (butuh token)
|--------------------------------------------------------------------------
|
| - route group for authenticated users 
| a. route group for ADMIN
| b. route group for ATTENDE
|
*/

Route::middleware(['apiKey'])->group(function () {
Route::middleware('auth:sanctum')->group( function () {
    // Get user 
    Route::get('/user', function (Request $request) {
    return $request->user();
    });
    // logout
    Route::post('/logout', [AuthController::class, 'logout']);
    // Get Event index
    Route::get('/event', [EventController::class, 'index']);
    // Get Event detail
    Route::get('/event/{eventId}', [EventController::class, 'show']);

    // ADMIN ONLY
    Route::group(['middleware' => ['role:admin']], function () {
         // Create Event
         Route::post('/event', [EventController::class, 'store']);
         // Update Event
         Route::post('/event/{eventId} ', [EventController::class, 'update']);
         // Delete Event
         Route::delete('/event/{eventId} ', [EventController::class, 'delete']);
        //  Get Ticket List by Event
        Route::get('/event/{eventId}/ticket', [TicketController::class, 'indexByEvent']);
        // CheckIn
        Route::patch('/checkin', [TicketController::class, 'checkin']);

    });
    // ATTENDEE ONLY
    Route::group(['middleware' => ['role:attendee']], function () {
        // Reserve Ticket
        Route::post(('/event/{eventId}/reserve'), [TicketController::class, 'store']);
        // My Ticket List
        Route::get('/my-tickets', [TicketController::class, 'indexByUser']);
        // Cancel Ticket
        Route::patch('/ticket/{ticketId}/cancel', [TicketController::class, 'cancel']);
    });
}); 

// Guest Route 
Route::group([], function () {
// Register
Route::post('/register', [AuthController::class, 'register']);
// login
Route::post('/login', [AuthController::class, 'login']);
});
});





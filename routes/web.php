<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleContactController;
use App\Services\CompanyMatchingService;

Route::get('/', function () {
    return view('welcome');
});

// Google OAuth Routes
Route::prefix('auth/google')->group(function () {
    Route::get('/', [GoogleContactController::class, 'redirectToGoogle'])->name('google.login');
    Route::get('/callback', [GoogleContactController::class, 'handleGoogleCallback'])->name('google.callback');
});

// Protected Contacts Routes (Ideally inside an auth middleware, but left open for immediate testing)
Route::prefix('contacts')->group(function () {
    Route::get('/', [GoogleContactController::class, 'index'])->name('contacts.index');
    Route::get('/sync', [GoogleContactController::class, 'sync'])->name('contacts.sync');
    Route::get('/match-companies', function(CompanyMatchingService $matcher) {
        $count = $matcher->runMatchingEngine();
        return redirect()->route('contacts.index')->with('success', "Emparejamiento completado! Se vincularon {$count} empresas a tus contactos de Google.");
    })->name('contacts.match');
    
    Route::post('/{id}/notes', [GoogleContactController::class, 'updateNotes'])->name('contacts.notes.update');
    Route::post('/{id}/agenda', [GoogleContactController::class, 'storeAgenda'])->name('contacts.agenda.store');
    Route::patch('/activities/{activityId}/complete', [GoogleContactController::class, 'completeActivity'])->name('contacts.agenda.complete');
    Route::patch('/activities/{activityId}/notes', [GoogleContactController::class, 'updateActivityNotes'])->name('contacts.agenda.notes');
});

// Agenda/Calendar Routes
Route::get('/calendar', [\App\Http\Controllers\CalendarController::class, 'index'])->name('calendar.index');
Route::get('/api/events', [\App\Http\Controllers\CalendarController::class, 'events'])->name('api.events');

Route::get('/debug-notes', function() {
    try {
        $contact = \App\Models\GoogleContact::first();
        if(!$contact) return "No contacts found";
        
        $contact->notes = 'Test from route';
        $contact->save();
        
        $googleService = new \App\Services\GooglePeopleService('DUMMY');
        $googleService->updateContact($contact->google_id, $contact->etag, ['notes' => 'Test']);
        
        return "ALL OK";
    } catch (\Exception $e) {
        return "Catch: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine();
    } catch (\Error $e) {
        return "Fatal Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine();
    }
});

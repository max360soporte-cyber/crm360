<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\GoogleContact;
use Illuminate\Support\Facades\Log;

class GoogleContactController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     * Requesting 'contacts' scope for read/write access.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/contacts'])
            ->with(['access_type' => 'offline', 'prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * Obtain the user information from Google and save tokens.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Guardamos los tokens en la sesión para poder usarlos en el sync()
            // Incluso si no hay un usuario logueado en Laravel para pruebas locales.
            session([
                'google_access_token' => $googleUser->token,
                'google_refresh_token' => $googleUser->refreshToken ?? session('google_refresh_token'),
            ]);

            // After successful auth, automatically trigger a sync
            return redirect()->route('contacts.sync')->with('success', 'Google Contacts connected successfully. Syncing contacts...');

        } catch (\Exception $e) {
            return redirect()->route('contacts.index')->with('error', 'Failed to connect to Google: ' . $e->getMessage());
        }
    }

    /**
     * Display the initial Master-Detail view.
     */
    public function index()
    {
        // For now, return a basic view. The real view will be built in Frontend.
        $contacts = GoogleContact::with(['trainings' => function($query) {
            $query->latest();
        }])->orderBy('name')->get();
        return view('contacts.index', compact('contacts'));
    }

    /**
     * Sync contacts from Google People API to MySQL.
     */
    public function sync()
    {
        $accessToken = session('google_access_token');

        if (!$accessToken) {
            return redirect()->route('google.login');
        }

        try {
            $googleService = new \App\Services\GooglePeopleService($accessToken);
            $connections = $googleService->getContacts();

            $userId = Auth::check() ? Auth::id() : 1; // Fallback to user 1 if not fully logged in for testing

            if ($connections) {
                $syncedGoogleIds = [];
                foreach ($connections as $person) {
                    $googleId = $person->getResourceName();
                    $syncedGoogleIds[] = $googleId;
                    
                    $name = '';
                    if ($person->getNames() && count($person->getNames()) > 0) {
                        $name = $person->getNames()[0]->getDisplayName();
                    }

                    $phone = '';
                    if ($person->getPhoneNumbers() && count($person->getPhoneNumbers()) > 0) {
                        $phone = $person->getPhoneNumbers()[0]->getValue();
                    }

                    $photo = '';
                    if ($person->getPhotos() && count($person->getPhotos()) > 0) {
                        $photo = $person->getPhotos()[0]->getUrl();
                    }

                    $notes = '';
                    if ($person->getBiographies() && count($person->getBiographies()) > 0) {
                        $notes = $person->getBiographies()[0]->getValue();
                    }

                    GoogleContact::updateOrCreate(
                        ['google_id' => $googleId],
                        [
                            'user_id' => $userId,
                            'name' => $name,
                            'notes' => $notes,
                            'phone_number' => $phone,
                            'photo_url' => $photo,
                            'etag' => $person->getEtag(),
                            'synced_at' => now(),
                            'is_active' => true,
                        ]
                    );
                }

                // Archive the contacts that no longer exist in Google (to keep as backups)
                if (count($syncedGoogleIds) > 0) {
                    GoogleContact::whereNotIn('google_id', $syncedGoogleIds)
                                 ->update(['is_active' => false]);
                }
            }

            return redirect()->route('contacts.index')->with('success', 'Contacts synced successfully!');

        } catch (\Exception $e) {
            if (strpos($e->getMessage(), '401') !== false || strpos($e->getMessage(), 'UNAUTHENTICATED') !== false) {
                session()->forget('google_access_token');
                return redirect()->route('contacts.index')->with('error', 'Tu cuenta de Google fue desconectada o el token expiró. Por favor, vuelve a iniciar sesión conectando tu cuenta.');
            }
            return redirect()->route('contacts.index')->with('error', 'Error syncing contacts: ' . $e->getMessage());
        }
    }

    /**
     * Update the contact notes in DB and push to Google natively.
     */
    public function updateNotes(Request $request, $id)
    {
        $contact = GoogleContact::findOrFail($id);
        $contact->notes = $request->input('notes', '');
        
        $googleSyncSuccess = true;
        $googleSyncError = null;

        $accessToken = session('google_access_token');
        if ($accessToken && $contact->google_id) {
            try {
                $googleService = new \App\Services\GooglePeopleService($accessToken);
                $updatedPerson = $googleService->updateContact(
                    $contact->google_id, 
                    $contact->etag, 
                    ['notes' => $contact->notes]
                );
                
                // Update the etag from Google's response to avoid conflicts mapping
                $contact->etag = $updatedPerson->getEtag();
            } catch (\Exception $e) {
                Log::error('Google Sync Error: ' . $e->getMessage());
                // We soft-fail the Google sync, but we will still save it locally.
                $googleSyncSuccess = false;
                $googleSyncError = 'Localmente guardado, pero Google Sync falló (Token Expirado).';
            }
        } else {
            Log::warning('Google Sync skipped. Access token: ' . ($accessToken ? 'Yes' : 'No') . ', Google ID: ' . $contact->google_id);
            $googleSyncSuccess = false;
            $googleSyncError = 'No hay token de Google activo. Solo se guardó localmente.';
        }
        $contact->save();
        
        if ($googleSyncSuccess) {
            return response()->json(['success' => true]);
        } else {
            // Return success true so the UI updates, but attach a warning
            return response()->json([
                'success' => true, 
                'warning' => $googleSyncError
            ]);
        }
    }

    /**
     * Store a new Training/Support ticket for a Google Contact
     */
    public function storeAgenda(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:training,support',
            'title' => 'required|string|max:255',
            'scheduled_date' => 'required|date',
            'description' => 'nullable|string',
            'reschedule_activity_id' => 'nullable|exists:trainings,id'
        ]);

        $contact = GoogleContact::findOrFail($id);

        if ($request->filled('reschedule_activity_id')) {
            $oldTraining = \App\Models\Training::find($request->reschedule_activity_id);
            if ($oldTraining) {
                $oldTraining->status = 'rescheduled';
                $oldTraining->notes = empty($oldTraining->notes) ? 'REAGENDADO' : $oldTraining->notes . "\n\nREAGENDADO";
                $oldTraining->save();
            }
        }

        $training = $contact->trainings()->create([
            'type' => $request->type,
            'title' => $request->title,
            'scheduled_date' => \Carbon\Carbon::parse($request->scheduled_date),
            'notes' => $request->description,
            'status' => 'scheduled',
        ]);

        return response()->json([
            'success' => true,
            'training' => $training,
            'rescheduled_id' => $request->reschedule_activity_id
        ]);
    }

    /**
     * Mark an activity (Training/Support) as completed
     */
    public function completeActivity(Request $request, $activityId)
    {
        $training = \App\Models\Training::findOrFail($activityId);
        $training->status = 'completed';
        if ($request->has('notes')) {
            $training->notes = $request->input('notes');
        }
        $training->save();

        return response()->json([
            'success' => true,
            'training' => $training
        ]);
    }

    /**
     * Update the notes/description of a specific activity
     */
    public function updateActivityNotes(Request $request, $activityId)
    {
        $request->validate([
            'notes' => 'nullable|string'
        ]);

        $training = \App\Models\Training::findOrFail($activityId);
        $training->notes = $request->input('notes');
        $training->save();

        return response()->json([
            'success' => true,
            'training' => $training
        ]);
    }
}

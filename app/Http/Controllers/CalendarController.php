<?php

namespace App\Http\Controllers;

use App\Models\Training;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    /**
     * Display the calendar view.
     */
    public function index()
    {
        return view('calendar');
    }

    /**
     * Get events for the calendar (training and support).
     */
    public function events(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');

        $activities = Training::with('contact')
            ->whereBetween('scheduled_date', [$start, $end])
            ->where('status', '!=', 'rescheduled')
            ->get();

        $events = $activities->map(function ($activity) {
            $color = '#94a3b8'; // Default Grey for completed/others
            $icon = $activity->type === 'training' ? '🎓 ' : '🎧 ';
            
            if ($activity->status === 'scheduled') {
                $color = $activity->type === 'training' ? '#10b981' : '#f43f5e'; // Green for training, Rose for support
            }

            $contactName = $activity->contact->name ?? 'Sin Nombre';
            $title = $activity->title;
            
            // Si el título ya contiene el nombre del contacto o parte de él, no lo duplicamos
            if (!empty($contactName) && stripos($title, $contactName) === false) {
                $title .= ' - ' . $contactName;
            }
            
            return [
                'id' => $activity->id,
                'title' => $title,
                'start' => $activity->scheduled_date->toIso8601String(),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'google_contact_id' => $activity->google_contact_id,
                    'description' => $activity->description,
                    'notes' => $activity->notes,
                    'status' => $activity->status,
                    'contact_name' => $activity->contact->name ?? 'Sin Nombre',
                    'type' => $activity->type,
                    'color' => $color // Pass the color to use in custom content if needed
                ]
            ];
        });

        return response()->json($events);
    }
}

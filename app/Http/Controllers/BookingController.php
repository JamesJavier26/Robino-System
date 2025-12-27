<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $now = now();

        // Start with all bookings
        $query = Booking::query();

        // --- Minimal-added filters ---
        if ($request->filled('date')) {
            $query->where('date', $request->date);
            $scheduleDate = $request->date; // use this for the grid
        } else {
            $scheduleDate = Carbon::today()->toDateString();
        }
        
        if ($request->filled('court')) {
            $query->where('court', $request->court);
        }
        // ------------------------------

        // Get filtered bookings first
        $bookings = $query->get();

        // Keep your SAME logic for active bookings
        $activeBookings = $bookings->filter(function ($booking) use ($now) {
            $start = Carbon::parse($booking->date . ' ' . $booking->time);
            $end = $start->clone()->addHours($booking->duration);

            return $end->greaterThanOrEqualTo($now);
        });

        // Sort by date THEN by time
        $activeBookings = $activeBookings->sortBy([
            ['date', 'asc'],
            ['time', 'asc']
        ]);

    $todaysBookings = $activeBookings->filter(fn($b) => $b->date == $scheduleDate);

    return view('bookings.index', compact('activeBookings', 'todaysBookings', 'scheduleDate'));
    }


    public function completed()
    {
        $now = now();

        $completedBookings = Booking::get()->filter(function ($booking) use ($now) {
            $start = Carbon::parse($booking->date . ' ' . $booking->time);
            $end = $start->clone()->addHours($booking->duration);

            return $end->lessThan($now);
        });

        return view('bookings.completed', compact('completedBookings'));
    }

    public function create(Request $request)
    {
        // Get all existing bookings grouped by date + court
        $booked = Booking::get()->map(function($b){
            return [
                'date'     => $b->date,
                'court'    => $b->court,
                'start'    => $b->time,
                'end'      => Carbon::parse($b->time)->addHours($b->duration)->format('H:i'),
            ];
        });

        return view('bookings.create', compact('booked'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'nullable',
            'duration' => 'required|integer|min:1',
            'time_end' => 'required|string',
            'color' => 'required|string',
            'court' => 'required|integer|between:1,6'
        ]);

        if ($this->hasOverlap($request)) {
            return back()->withErrors([
                'time' => 'This time overlaps with an existing booking or scheduled booking for this court.'
            ])->withInput();
        }

        Booking::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'date' => $request->date,
            'time' => $request->time,
            'duration' => $request->duration,
            'time_end' => $request->time_end,
            'color' => $request->color,
            'court' => $request->court,
        ]);


        return redirect()->route('bookings.index')->with('success', 'Booking created!');
    }

    public function edit(Booking $booking)
    {
        // Get all bookings except the current one
        $booked = Booking::where('id', '!=', $booking->id)->get();

        return view('bookings.edit', compact('booking', 'booked'));
    }

    public function update(Request $request, Booking $booking)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'nullable',
            'duration' => 'required|integer|min:1',
            'time_end' => 'required|string',
            'color' => 'required|string',
            'court' => 'required|integer|between:1,6'
        ]);

        if ($this->hasOverlap($request, $booking->id)) {
            return back()->withErrors([
                'time' => 'This time overlaps with an existing booking or scheduled booking for this court.'
            ])->withInput();
        }

        $booking->update($request->all());

        return redirect()->route('bookings.index')->with('success', 'Booking updated!');
    }

    public function destroy(Booking $booking)
    {
        $booking->delete();
        return redirect()->route('bookings.index')->with('success', 'Booking deleted!');
    }


    /**
     * Overlap Checker
     */
    private function hasOverlap(Request $request, $ignoreId = null)
    {
        $date  = $request->date;
        $court = $request->court;

        // Start and end times from request
        $newStart = Carbon::parse($request->time);
        $newEnd   = Carbon::parse($request->time_end);

        // Existing bookings
        $existing = Booking::where('court', $court)
            ->where('date', $date)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->get();

        // 1️⃣ Check for overlap with existing bookings
        foreach ($existing as $b) {
            $existingStart = Carbon::parse($b->time);
            $existingEnd   = Carbon::parse($b->time_end);

            if ($newStart < $existingEnd && $newEnd > $existingStart) {
                return true;
            }
        }

        // 2️⃣ Check for blackout periods (example: courts 1–3 only)
        $dayOfWeek = Carbon::parse($date)->dayOfWeek; // 0 = Sunday, 6 = Saturday
        $blackoutRules = [
            0 => [['start' => 13, 'end' => 16]], // Sunday
            3 => [['start' => 16, 'end' => 19]], // Wednesday
            5 => [['start' => 16, 'end' => 19]], // Friday
            6 => [['start' => 14, 'end' => 17]], // Saturday
        ];

        if (in_array($court, [1, 2, 3]) && isset($blackoutRules[$dayOfWeek])) {
            foreach ($blackoutRules[$dayOfWeek] as $rule) {
                $blackoutStart = Carbon::createFromTime($rule['start'], 0);
                $blackoutEnd   = Carbon::createFromTime($rule['end'], 0);

                // If any part of booking overlaps blackout, block it
                if ($newStart < $blackoutEnd && $newEnd > $blackoutStart) {
                    return true;
                }
            }
        }

        return false;
    }

    public function show(Booking $booking)
    {
        return redirect()->route('bookings.index');
    }

}

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

        return view('bookings.index', compact('activeBookings'));
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
            'time' => 'required',
            'duration' => 'required|integer|min:1',
            'time_end' => 'required|string',
            'color' => 'required|string',
            'court' => 'required|integer|between:1,6'
        ]);

        if ($this->hasOverlap($request)) {
            return back()->withErrors([
                'time' => 'This time overlaps with an existing booking for this court.'
            ])->withInput();
        }

        Booking::create($request->all());

        return redirect()->route('bookings.index')->with('success', 'Booking created!');
    }

    public function edit(Booking $booking)
    {
        return view('bookings.edit', compact('booking'));
    }

    public function update(Request $request, Booking $booking)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'required',
            'duration' => 'required|integer|min:1',
            'time_end' => 'required|string',
            'color' => 'required|string',
            'court' => 'required|integer|between:1,6'
        ]);

        if ($this->hasOverlap($request, $booking->id)) {
            return back()->withErrors([
                'time' => 'This time overlaps with an existing booking for this court.'
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
        $newStart = $request->time;          // "HH:MM" string, no parsing
        $newEnd   = Carbon::parse($request->time_end); // only parse time_end

        $existing = Booking::where('court', $court)
            ->where('date', $date)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->get();

        foreach ($existing as $b) {
            $existingStart = $b->time;          // keep as string
            $existingEnd   = Carbon::parse($b->time_end); // parse only time_end

            // Compare using Carbon for end times, strings for start times
            if ($newStart < $existingEnd->format('H:i') && $newEnd->format('H:i') > $existingStart) {
                return true;
            }
        }

        return false;
    }

    public function show(Booking $booking)
    {
        return redirect()->route('bookings.index');
    }

}

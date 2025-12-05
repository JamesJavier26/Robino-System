<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::orderBy('date')->orderBy('time')->get();
        return view('bookings.index', compact('bookings'));
    }

    public function create()
    {
        return view('bookings.create');
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

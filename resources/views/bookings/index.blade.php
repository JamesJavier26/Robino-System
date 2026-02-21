<x-app-layout>
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Bookings</h1>
            <a href="{{ route('bookings.create') }}"
               class="bg-blue-500 hover:bg-blue-600 text-white px-5 py-2 rounded shadow">
                New Booking
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif

        {{-- Filters (always visible) --}}
        <div class="overflow-x-auto mb-6">
            <div class="mb-6 bg-white p-4 rounded shadow border">
                <form method="GET" action="{{ route('bookings.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    {{-- Date Filter --}}
                    <div>
                        <label class="text-gray-700 font-medium">Filter by Date</label>
                        <input type="date" name="date" value="{{ request('date') }}"
                            class="w-full border p-2 rounded">
                    </div>

                    {{-- Court Filter --}}
                    <div>
                        <label class="text-gray-700 font-medium">Filter by Court</label>
                        <select name="court" class="w-full border p-2 rounded">
                            <option value="">All Courts</option>
                            @for($i = 1; $i <= 6; $i++)
                                <option value="{{ $i }}" {{ request('court') == $i ? 'selected' : '' }}>
                                    Court {{ $i }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    {{-- Total Duration --}}
                    <div class="mb-4 p-4">
                        @php
                            $totalDuration = $activeBookings->sum('duration');
                        @endphp
                        <strong>Total Hours:</strong> {{ $totalDuration }} hours
                    </div>

                    {{-- Submit --}}
                    <div class="flex items-end">
                        <button class="bg-blue-500 hover:bg-blue-600 text-white px-5 py-2 rounded shadow w-full">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Court Schedule Grid --}}
        <div class="overflow-x-auto mb-6">
            <div class="bg-white p-4 rounded shadow border">
                <h2 class="text-xl font-bold mb-4">Court Schedule</h2>

                <div class="grid grid-cols-7 border border-gray-300">
                    {{-- Header Row: Courts --}}
                    <div class="p-2 border-b border-r border-gray-300 font-semibold bg-gray-100">Time</div>
                    @for ($c = 1; $c <= 6; $c++)
                        <div class="p-2 border-b border-r border-gray-300 font-semibold bg-gray-100 text-center">
                            Court {{ $c }}
                        </div>
                    @endfor

                    {{-- Blackout Rules --}}
                        @php
                            $blackoutRules = [
                                1 => [
                                    ['start' => 17, 'end' => 20, 'color' => 'bg-red-500 text-gray-400'] // Monday
                                ],
                                2 => [
                                    ['start' => 18, 'end' => 24, 'color' => 'bg-yellow-500 text-gray-700'] // Tuesday (new color)
                                ],
                                3 => [
                                    ['start' => 16, 'end' => 19, 'color' => 'bg-red-500 text-gray-400'] // Wednesday
                                ],
                                4 => [
                                    ['start' => 18, 'end' => 24, 'color' => 'bg-yellow-500 text-gray-700'] // Thursday (new color)
                                ],
                                5 => [
                                    ['start' => 16, 'end' => 19, 'color' => 'bg-red-500 text-gray-400'] // Friday
                                ],
                                6 => [
                                    ['start' => 13, 'end' => 16, 'color' => 'bg-red-500 text-gray-400'], // Saturday 1pm-4pm
                                    ['start' => 18, 'end' => 24, 'color' => 'bg-yellow-500 text-gray-700'] // Saturday 6pm-12am (new color)
                                ],
                                0 => [
                                    ['start' => 13, 'end' => 16, 'color' => 'bg-red-500 text-gray-400'],
                                    ['start' => 16, 'end' => 20, 'color' => 'bg-red-500 text-gray-400'] // Sunday
                                ],
                            ];

                        $dayOfWeek = \Carbon\Carbon::parse(request('date') ?? now())->dayOfWeek;
                    @endphp

                    {{-- Time Slots --}}
                    @for ($hour = 7; $hour <= 24; $hour++)
                        {{-- Time Label --}}
                        <div class="p-2 border-b border-r border-gray-300 bg-gray-50 font-medium">
                            {{ \Carbon\Carbon::createFromTime($hour % 24, 0)->format('g:i A') }}
                        </div>

                        {{-- Slots per court --}}
                        @for ($c = 1; $c <= 6; $c++)
                            @php
                                $courtColors = [
                                    1 => 'bg-orange-500 text-white',
                                    2 => 'bg-blue-500 text-white',
                                    3 => 'bg-gray-500 text-white',
                                    4 => 'bg-orange-500 text-white',
                                    5 => 'bg-blue-500 text-white',
                                    6 => 'bg-gray-500 text-white',
                                ];

                                // Check if slot is booked
                                $slotBooking = $todaysBookings->first(function($b) use ($c, $hour) {
                                    $bookingHour = \Carbon\Carbon::parse($b->time)->hour;
                                    return $b->court == $c && $bookingHour <= $hour && $hour < ($bookingHour + $b->duration);
                                });

                                // Check blackout period (only for courts 1-3)
                                $isBlackout = false;
                                $blackoutClass = '';

                                if (in_array($c, [1, 2, 3]) && isset($blackoutRules[$dayOfWeek])) {
                                    foreach ($blackoutRules[$dayOfWeek] as $rule) {
                                        if ($hour >= $rule['start'] && $hour < $rule['end']) {
                                            $isBlackout = true;
                                            $blackoutClass = $rule['color'];
                                            break;
                                        }
                                    }
                                }

                                $slotClass = $slotBooking
                                    ? $courtColors[$c]
                                    : ($isBlackout ? $blackoutClass : 'bg-white');

                            @endphp

                            <div
                                class="p-2 border-b border-r border-gray-300 h-10 text-center {{ $slotClass }}"
                                @if($slotBooking)
                                    title="{{ $slotBooking->name }}"
                                @elseif($isBlackout)
                                    title="Blackout period"
                                @endif
                            >
                            </div>
                        @endfor
                    @endfor
                </div>
            </div>
        </div>


        {{-- Bookings Table --}}
        <table class="min-w-full bg-white border border-gray-200 shadow rounded">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left text-gray-700">Name</th>
                    <th class="p-3 text-left text-gray-700">Date</th>
                    <th class="p-3 text-left text-gray-700">Start Time</th>
                    <th class="p-3 text-left text-gray-700">Duration (hrs)</th>
                    <th class="p-3 text-left text-gray-700">End Time</th>
                    <th class="p-3 text-left text-gray-700">Court</th>
                    <th class="p-3 text-left text-gray-700">Color</th>
                    <th class="p-3 text-left text-gray-700">Created By</th>
                    <th class="p-3 text-left text-gray-700">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($activeBookings as $booking)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3 font-medium" style="background-color: {{ $booking->color }}20;">
                            {{ $booking->name }}
                        </td>
                        <td class="p-3">{{ $booking->date }}</td>
                        <td class="p-3">{{ \Carbon\Carbon::parse($booking->time)->format('g:i A') }}</td>
                        <td class="p-3">{{ $booking->duration }}</td>
                        <td class="p-3">{{ $booking->time_end ?? '-' }}</td>
                        <td class="p-3 font-semibold">Court {{ $booking->court }}</td>
                        <td class="p-3">
                            <div class="w-8 h-6 rounded" style="background-color: {{ $booking->color }};"></div>
                        </td>
                        <td class="p-3">{{ $booking->user->name ?? 'N/A' }}</td>
                        <td class="p-3 flex space-x-2">
                            <a href="{{ route('bookings.edit', $booking) }}" class="text-blue-600 hover:underline">Edit</a>
                            @auth
                                @if (auth()->user()->role === 'admin')
                                    <form action="{{ route('bookings.destroy', $booking) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:underline"
                                                onclick="return confirm('Delete booking?')">Delete</button>
                                    </form>
                                @endif
                            @endauth
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-3 text-center text-gray-500">
                            No bookings found for the selected date/court.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>

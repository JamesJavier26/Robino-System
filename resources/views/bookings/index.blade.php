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

        {{-- 🔥 CHECK ACTIVE BOOKINGS --}}
        @if($activeBookings->count() > 0)
            <div class="overflow-x-auto">
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
                                <option value="1" {{ request('court') == 1 ? 'selected' : '' }}>Court 1</option>
                                <option value="2" {{ request('court') == 2 ? 'selected' : '' }}>Court 2</option>
                                <option value="3" {{ request('court') == 3 ? 'selected' : '' }}>Court 3</option>
                                <option value="4" {{ request('court') == 4 ? 'selected' : '' }}>Court 4</option>
                                <option value="5" {{ request('court') == 5 ? 'selected' : '' }}>Court 5</option>
                                <option value="6" {{ request('court') == 6 ? 'selected' : '' }}>Court 6</option>
                            </select>
                        </div>
                        {{-- Total Duration --}}
                        <div class="mb-4 p-4">
                            @php
                                // Sum durations of current filtered bookings
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
                            <th class="p-3 text-left text-gray-700">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($activeBookings as $booking)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-3 font-medium" style="background-color: {{ $booking->color }}20;">
                                    {{ $booking->name }}
                                </td>

                                <td class="p-3">{{ $booking->date }}</td>

                                <td class="p-3">
                                    {{ \Carbon\Carbon::parse($booking->time)->format('g:i A') }}
                                </td>

                                <td class="p-3">{{ $booking->duration }}</td>

                                <td class="p-3">
                                    {{ $booking->time_end ? $booking->time_end : '-' }}
                                </td>

                                <td class="p-3 font-semibold">Court {{ $booking->court }}</td>

                                <td class="p-3">
                                    <div class="w-8 h-6 rounded" style="background-color: {{ $booking->color }};"></div>
                                </td>

                                <td class="p-3 flex space-x-2">
                                    <a href="{{ route('bookings.edit', $booking) }}"
                                       class="text-blue-600 hover:underline">Edit</a>

                                    @auth
                                        @if (auth()->user()->role === 'admin')
                                            <form action="{{ route('bookings.destroy', $booking) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-600 hover:underline"
                                                        onclick="return confirm('Delete booking?')">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    @endauth
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 mt-6">No active bookings available. Create a new one!</p>
        @endif

    </div>
</x-app-layout>

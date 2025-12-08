<x-app-layout>
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Completed Bookings</h1>

            {{-- Optional: Show New Booking button if you want --}}
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

        @if($completedBookings->count() > 0)
            <div class="overflow-x-auto">
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
                        @foreach($completedBookings as $booking)
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

                                    <form action="{{ route('bookings.destroy', $booking) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:underline" 
                                                onclick="return confirm('Delete booking?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 mt-6">No completed bookings found.</p>
        @endif
    </div>
</x-app-layout>

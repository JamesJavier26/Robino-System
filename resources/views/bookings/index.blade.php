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

        @if($bookings->count() > 0)
            <div class="grid md:grid-cols-3 sm:grid-cols-2 gap-6">
                @foreach($bookings as $booking)
                    <div class="bg-white shadow rounded p-4 border-l-8" style="border-left-color: {{ $booking->color }}">
                        <h2 class="text-xl font-semibold mb-2">{{ $booking->name }}</h2>
                        <p class="text-gray-600 mb-1"><strong>Date:</strong> {{ $booking->date }}</p>
                        <p class="text-gray-600 mb-3"><strong>Time:</strong> {{ $booking->time }}</p>
                        
                        <div class="flex justify-between items-center">
                            <a href="{{ route('bookings.edit', $booking) }}" 
                               class="text-blue-600 hover:underline">Edit</a>
                            <form action="{{ route('bookings.destroy', $booking) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline" 
                                        onclick="return confirm('Delete booking?')">Delete</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 mt-6">No bookings available. Create a new one!</p>
        @endif
    </div>
</x-app-layout>

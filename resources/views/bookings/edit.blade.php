<x-app-layout>
    <h1 class="text-2xl font-bold mb-4">Edit Booking</h1>

    <form method="POST" action="{{ route('bookings.update', $booking) }}">
        @csrf
        @method('PUT')

        <label>Name:</label>
        <input type="text" name="name" class="border p-2 w-full mb-4" value="{{ $booking->name }}" required>

        <label>Date:</label>
        <input type="date" name="date" class="border p-2 w-full mb-4" value="{{ $booking->date }}" required>

        <label>Time:</label>
        <input type="time" name="time" class="border p-2 w-full mb-4" value="{{ $booking->time }}" required>

        <label>Color:</label>
        <input type="color" name="color" class="border p-2 w-full mb-4" value="{{ $booking->color }}" required>

        <button class="bg-blue-500 text-white px-4 py-2 rounded">Update</button>
    </form>
</x-app-layout>

<x-app-layout>
    <h1 class="text-2xl font-bold mb-4">Create Booking</h1>

    <form method="POST" action="{{ route('bookings.store') }}">
        @csrf

        <label>Name:</label>
        <input type="text" name="name" class="border p-2 w-full mb-4" required>

        <label>Date:</label>
        <input type="date" name="date" class="border p-2 w-full mb-4" required>

        <label>Time:</label>
        <input type="time" name="time" class="border p-2 w-full mb-4" required>

        <label>Color:</label>
        <input type="color" name="color" class="border p-2 w-full mb-4" value="#3490dc">

        <button class="bg-green-500 text-white px-4 py-2 rounded">Submit</button>
    </form>
</x-app-layout>

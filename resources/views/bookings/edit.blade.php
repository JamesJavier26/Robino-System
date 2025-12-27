<x-app-layout>
    <div class="max-w-md mx-auto bg-white shadow rounded p-6 mt-6">
        <h1 class="text-2xl font-bold mb-6 text-center">Edit Booking</h1>

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                <ul class="list-disc ml-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('bookings.update', $booking) }}">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Name</label>
                <input type="text" name="name" required
                       value="{{ old('name', $booking->name) }}"
                       class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <!-- Date -->
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Date</label>
                <input type="date" name="date" required
                       value="{{ old('date', $booking->date) }}"
                       class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <!-- Start Time -->
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Start Time</label>
                <select name="time" id="time" required
                        class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @for($h = 0; $h < 24; $h++)
                        @php
                            $value = sprintf('%02d:00', $h);
                            $label = \Carbon\Carbon::createFromTime($h, 0)->format('g:i A');
                        @endphp
                        <option value="{{ $value }}"
                            {{ old('time', $booking->time) === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endfor
                </select>
            </div>

            <!-- Duration -->
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Duration (hours)</label>
                <input type="number" name="duration" id="duration" min="1"
                       value="{{ old('duration', $booking->duration) }}"
                       required
                       class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <!-- End Time -->
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">End Time</label>
                <input type="text" id="time_end" name="time_end" readonly
                       value="{{ old('time_end', $booking->time_end) }}"
                       class="w-full border border-gray-300 p-2 rounded bg-gray-100 cursor-not-allowed">
            </div>

            <!-- Court -->
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Court</label>
                <select name="court" id="court" required
                        class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @for($i = 1; $i <= 6; $i++)
                        <option value="{{ $i }}"
                            {{ old('court', $booking->court) == $i ? 'selected' : '' }}>
                            Court {{ $i }}
                        </option>
                    @endfor
                </select>
            </div>

            <!-- Color -->
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-2">Color</label>
                <select name="color" required
                        class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="#f97316"
                        {{ old('color', $booking->color) == '#f97316' ? 'selected' : '' }}>Orange</option>
                    <option value="#3b82f6"
                        {{ old('color', $booking->color) == '#3b82f6' ? 'selected' : '' }}>Blue</option>
                    <option value="#9ca3af"
                        {{ old('color', $booking->color) == '#9ca3af' ? 'selected' : '' }}>Gray</option>
                </select>
            </div>

            <button type="submit"
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-medium">
                Update Booking
            </button>
        </form>
    </div>

    <!-- Auto-calculate end time and disable conflicts -->
<!-- Auto-calculate end time and disable conflicts -->
<script>
    const timeInput = document.getElementById('time');
    const durationInput = document.getElementById('duration');
    const timeEndInput = document.getElementById('time_end');
    const dateInput = document.querySelector('input[name="date"]');
    const courtInput = document.getElementById('court');
    const timeSelect = timeInput;

    const booked = @json($booked); // ensure this comes from controller
    const blackoutRules = {
        0: [{ start: 13, end: 16 }], // Sunday
        3: [{ start: 16, end: 19 }], // Wednesday
        5: [{ start: 16, end: 19 }], // Friday
        6: [{ start: 14, end: 17 }]  // Saturday
    };

    function updateEndTime() {
        const start = timeInput.value;
        const duration = parseFloat(durationInput.value);
        if (!start || !duration) {
            timeEndInput.value = '';
            return;
        }

        const [hours, minutes] = start.split(':').map(Number);
        const date = new Date();
        date.setHours(hours);
        date.setMinutes(minutes);
        date.setSeconds(0);
        date.setHours(date.getHours() + duration);

        const options = { hour: 'numeric', minute: '2-digit', hour12: true };
        timeEndInput.value = date.toLocaleTimeString([], options);
    }

    function updateDisabledTimes() {
        const date = dateInput.value;
        const court = courtInput.value;
        const duration = parseFloat(durationInput.value);
        if (!date || !court || !duration) return;

        const dayOfWeek = new Date(date).getDay();

        // Reset all options
        [...timeSelect.options].forEach(opt => {
            opt.disabled = false;
            opt.classList.remove('bg-red-200', 'text-gray-400');
        });

        // Filter bookings for the same court and date excluding the current booking
        const filtered = booked.filter(b => b.date === date && b.court == court && b.id != {{ $booking->id }});

        [...timeSelect.options].forEach(opt => {
            const startHour = parseFloat(opt.value.split(':')[0]);
            const endHour = startHour + duration;

            // Check overlap with existing bookings
            const overlapsBooking = filtered.some(b => {
                const bStart = parseFloat(b.time.split(':')[0]);
                const bEnd = parseFloat(b.time_end.split(':')[0]);
                return startHour < bEnd && endHour > bStart;
            });

            // Check overlap with blackout periods
            let overlapsBlackout = false;
            if (['1','2','3'].includes(court) && blackoutRules[dayOfWeek]) {
                overlapsBlackout = blackoutRules[dayOfWeek].some(rule => startHour < rule.end && endHour > rule.start);
            }

            if (overlapsBooking || overlapsBlackout) {
                opt.disabled = true;
                opt.classList.add('bg-red-200', 'text-gray-400');
            }
        });
    }

    timeInput.addEventListener('change', updateEndTime);
    durationInput.addEventListener('input', updateEndTime);
    dateInput.addEventListener('change', updateDisabledTimes);
    courtInput.addEventListener('change', updateDisabledTimes);
    durationInput.addEventListener('input', updateDisabledTimes);

    // Initialize on page load
    updateEndTime();
    updateDisabledTimes();
</script>

</x-app-layout>

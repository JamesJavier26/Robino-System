    <x-app-layout>
        <div class="max-w-md mx-auto bg-white shadow rounded p-6 mt-6">
            <h1 class="text-2xl font-bold mb-6 text-center">Create Booking</h1>

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    <ul class="list-disc ml-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('bookings.store') }}">
                @csrf

                <!-- Name -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Name</label>
                    <input type="text" name="name" required
                        class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                <!-- Date -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Date</label>
                    <input type="date" name="date" required
                        class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                <!-- Start Time -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Start Time</label>
                    <select name="time" id="time" required
                            class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
                        @for($h = 0; $h < 24; $h++)
                            @php
                                $timeValue = sprintf('%02d:00', $h);
                                $timeLabel = \Carbon\Carbon::createFromTime($h,0)->format('g:i A');
                            @endphp
                            <option value="{{ $timeValue }}">{{ $timeLabel }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Duration -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Duration (hours)</label>
                    <input type="number" name="duration" id="duration" min="1" value="1" required
                        class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                <!-- End Time (auto-calculated) -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">End Time</label>
                    <input type="text" id="time_end" name="time_end" readonly
                        class="w-full border border-gray-300 p-2 rounded bg-gray-100 cursor-not-allowed">
                </div>

                <!-- Court -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Court</label>
                    <select name="court" required
                            class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
                        @for($i = 1; $i <= 6; $i++)
                            <option value="{{ $i }}">Court {{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Color -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Color</label>
                    <select name="color" required
                            class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="#f97316" style="color:#f97316;">Orange</option>
                        <option value="#3b82f6" style="color:#3b82f6;">Blue</option>
                        <option value="#9ca3af" style="color:#9ca3af;">Gray</option>
                    </select>
                </div>


                <button type="submit"
                        class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded font-medium">
                    Submit
                </button>
            </form>
        </div>

        <!-- Auto calculate end time -->
<script>
    const timeInput = document.getElementById('time');
    const durationInput = document.getElementById('duration');
    const timeEndInput = document.getElementById('time_end');

    function updateEndTime() {
        const start = timeInput.value;
        const duration = parseInt(durationInput.value);

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

    timeInput.addEventListener('change', updateEndTime);
    durationInput.addEventListener('input', updateEndTime);

    updateEndTime();

    /* --------------------------
       Disable conflicting times
       -------------------------- */

    const booked = @json($booked);

    const dateInput  = document.querySelector('input[name="date"]');
    const courtInput = document.querySelector('select[name="court"]');
    const timeSelect = document.getElementById('time');

    /* ✅ ADDED: fixed blackout rules */
    const blackoutRules = {
        1: [{ start: 17, end: 20 }], // Monday
        2: [{ start: 18, end: 24 }], // Tuesday
        3: [{ start: 16, end: 19 }], // Wednesday
        4: [{ start: 18, end: 24 }], // Thursday
        5: [{ start: 16, end: 19 }], // Friday
        6: [ // Saturday
            { start: 13, end: 16 }, // 1PM–4PM
            { start: 18, end: 24 }  // 6PM–12AM
        ],
        0: [{ start: 13, end: 16 }] // Sunday
    };

    function updateDisabledTimes() {
    const date = dateInput.value;
    const court = courtInput.value;
    const duration = parseInt(durationInput.value);

    if (!date || !court || !duration) return;

    const dayOfWeek = new Date(date).getDay();

    // Reset all time options
    [...timeSelect.options].forEach(opt => {
        opt.disabled = false;
        opt.classList.remove('bg-red-200', 'text-gray-400');
    });

    const filtered = booked.filter(b => b.date === date && b.court == court);

    [...timeSelect.options].forEach(opt => {
        const startHour = parseInt(opt.value.split(':')[0]);
        const endHour = startHour + duration;

        /* Check overlap with existing bookings */
        const overlapsBooking = filtered.some(b => {
            const bStart = parseInt(b.start.split(':')[0]);
            const bEnd = parseInt(b.end.split(':')[0]);
            return startHour < bEnd && endHour > bStart; // any overlap
        });

        /* Check overlap with blackout periods */
        let overlapsBlackout = false;
        if (['1','2','3'].includes(court) && blackoutRules[dayOfWeek]) {
            overlapsBlackout = blackoutRules[dayOfWeek].some(rule => {
                // Check if any hour of the booking overlaps the blackout
                return startHour < rule.end && endHour > rule.start;
            });
        }

        if (overlapsBooking || overlapsBlackout) {
            opt.disabled = true;
            opt.classList.add('bg-red-200', 'text-gray-400');
        }
    });
}

    dateInput.addEventListener('change', updateDisabledTimes);
    courtInput.addEventListener('change', updateDisabledTimes);
    durationInput.addEventListener('input', updateDisabledTimes);

    updateDisabledTimes();
</script>

</x-app-layout>

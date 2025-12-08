<x-app-layout>
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6">Add New Player</h1>

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('players.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block mb-1 font-medium">Name</label>
                <input type="text" name="name" class="w-full border rounded p-2" 
                       value="{{ old('name') }}" required>
            </div>

            <div>
                <label class="block mb-1 font-medium">Age</label>
                <input type="number" name="age" class="w-full border rounded p-2" 
                       value="{{ old('age') }}">
            </div>

            <div>
                <label class="block mb-1 font-medium">Sex</label>
                <select name="sex" class="w-full border rounded p-2" required>
                    <option value="">Select</option>
                    <option value="Male" {{ old('sex') == 'Male' ? 'selected' : '' }}>Male</option>
                    <option value="Female" {{ old('sex') == 'Female' ? 'selected' : '' }}>Female</option>
                </select>
            </div>

            <div>
                <label class="block mb-1 font-medium">Skill Level</label>
                <select name="skill_level" class="w-full border rounded p-2" required>
                    <option value="">Select</option>
                    @foreach(['Beginner','Intermediate','Advanced','Professional'] as $level)
                        <option value="{{ $level }}" {{ old('skill_level') == $level ? 'selected' : '' }}>
                            {{ $level }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Create Player
                </button>
            </div>
        </form>
    </div>
</x-app-layout>

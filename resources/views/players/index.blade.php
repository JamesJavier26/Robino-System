<x-app-layout>
    <div class="container mx-auto p-6">

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Players</h1>
            <a href="{{ route('players.create') }}"
               class="bg-blue-500 hover:bg-blue-600 text-white px-5 py-2 rounded shadow">
                Add New Player
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if($players->count() > 0)
            <table class="min-w-full bg-white border border-gray-200 shadow rounded">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">Name</th>
                        <th class="p-3 text-left">Age</th>
                        <th class="p-3 text-left">Sex</th>
                        <th class="p-3 text-left">Skill Level</th>
                        <th class="p-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($players as $player)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3 font-medium">{{ $player->name }}</td>
                            <td class="p-3">{{ $player->age ?? '-' }}</td>
                            <td class="p-3">{{ $player->sex }}</td>
                            <td class="p-3">{{ $player->skill_level }}</td>
                            <td class="p-3 flex space-x-2">
                                <a href="{{ route('players.edit', $player) }}"
                                   class="text-blue-600 hover:underline">Edit</a>

                                <form action="{{ route('players.destroy', $player) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 hover:underline"
                                            onclick="return confirm('Delete this player?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-gray-500 mt-6">No players found. Add one!</p>
        @endif

    </div>
</x-app-layout>

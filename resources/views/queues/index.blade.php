<x-app-layout>
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6">Queue Management (Queue Master)</h1>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif
        
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                {{ session('error') }}
            </div>
        @endif

        {{-- Add Player to Queue --}}
        <form method="POST" action="{{ route('queues.store') }}" class="mb-6 flex space-x-2 items-end">
            @csrf

            {{-- Players Multi-Select --}}
            <div class="flex-1">
                <label class="block text-gray-700 font-medium mb-1">Select Players</label>
                <select id="player-select" name="player_ids[]" multiple required class="w-full border p-2 rounded">
                    @foreach($players as $player)
                        <option value="{{ $player->id }}">
                            {{ $player->name }} - {{ ucfirst($player->sex) }} - Skill: {{ $player->skill_level }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Date --}}
            <div>
                <label class="block text-gray-700 font-medium mb-1">Date</label>
                <input type="date" name="date" value="{{ $date }}" class="border p-2 rounded" required>
            </div>

            {{-- Submit --}}
            <div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                    Add to Queue
                </button>
            </div>
        </form>

        <div class="flex items-center space-x-2 mb-4">
            {{-- Matchup Filter --}}
            <form method="POST" action="{{ route('queues.matchup') }}" class="flex items-center space-x-2">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">
                <select name="strategy" class="border p-2 rounded w-48">
                    <option value="random" selected>Random</option>
                    <option value="skill">By Skill</option>
                    <option value="gender">By Gender</option>
                </select>

                {{-- Max Courts --}}
                <select name="max_courts" class="border p-2 rounded w-32">
                    <option value="4" selected>No. of Courts</option>
                    <option value="3">3 Courts</option>
                    <option value="4">4 Courts</option>
                    <option value="5">5 Courts</option>
                    <option value="6">6 Courts</option>
                </select>
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                    Generate Matchups
                </button>
            </form>

            {{-- Reset Matchups --}}
            <form method="POST" action="{{ route('queues.resetMatchups') }}" class="flex items-center">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">
                <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">
                    Reset Matchups
                </button>
            </form>
        </div>

        {{-- Queue Table --}}
        <table class="min-w-full bg-white border border-gray-200 shadow rounded">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Match</th>
                    <th class="p-3 text-left">Court</th>
                    <th class="p-3 text-left">Position</th>
                    <th class="p-3 text-left">Player Name</th>
                    <th class="p-3 text-left">Skill Level</th>
                    <th class="p-3 text-left">Gender</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                {{-- Section 1: Waiting Players --}}
                @php
                    $waitingPlayers = $queues->where('status', 'waiting');
                @endphp

                @if($waitingPlayers->count())
                    <tr class="bg-yellow-100 font-semibold">
                        <td class="p-3" colspan="8">Waiting Players (Not Matched Yet)</td>
                    </tr>

                    @foreach($waitingPlayers as $queue)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3">-</td>
                            <td class="p-3">-</td>
                            <td class="p-3">{{ $queue->position }}</td>
                            <td class="p-3">{{ $queue->player->name }}</td>
                            <td class="p-3">{{ $queue->player->skill_level }}</td>
                            <td class="p-3">{{ ucfirst($queue->player->sex) }}</td>
                            <td class="p-3">{{ ucfirst($queue->status) }}</td>
                            <td class="p-3 flex space-x-2">
                                <form method="POST" action="{{ route('queues.remove', $queue) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 hover:underline" onclick="return confirm('Remove from queue?')">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                @endif

                {{-- Section 2: Assigned Players Grouped by Match --}}
                @php
                    $assignedPlayers = $queues->whereNotNull('match_id')
                                            ->groupBy('match_id')
                                            ->sortKeys();
                @endphp

                @foreach($assignedPlayers as $matchId => $matchPlayers)
                    {{-- Match header --}}
                    <tr class="bg-gray-200 font-semibold">
                        <td class="p-3" colspan="8">
                            Match #{{ $matchId }}
                            @if($matchPlayers->first()->court)
                                - Court {{ $matchPlayers->first()->court }}
                            @endif
                        </td>
                    </tr>

                    @foreach($matchPlayers as $queue)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3">-</td>
                            <td class="p-3">-</td>
                            <td class="p-3">{{ $queue->position }}</td>
                            <td class="p-3">{{ $queue->player->name }}</td>
                            <td class="p-3">{{ $queue->player->skill_level }}</td>
                            <td class="p-3">{{ ucfirst($queue->player->sex) }}</td>
                            <td class="p-3">{{ ucfirst($queue->status) }}</td>
                            <td class="p-3 flex space-x-2">
                                <form method="POST" action="{{ route('queues.remove', $queue) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 hover:underline" onclick="return confirm('Remove from queue?')">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>


    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const playerSelect = document.getElementById('player-select');
        new Choices(playerSelect, {
            removeItemButton: true,   // show "x" to remove selected items
            searchEnabled: true,      // allow searching players
            placeholderValue: 'Select players',
            shouldSort: false         // keep original order
        });
    });
    </script>
</x-app-layout>

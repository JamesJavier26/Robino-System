<x-app-layout>
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">Queue Management (Queue Master)</h1>

    {{-- Flash Messages --}}
    @foreach (['success', 'error'] as $msg)
        @if(session($msg))
            <div class="mb-4 p-4 {{ $msg == 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700' }} rounded">
                {{ session($msg) }}
            </div>
        @endif
    @endforeach

    {{-- Add Player to Queue --}}
    <form method="POST" action="{{ route('queues.store') }}" class="mb-6 flex space-x-2 items-end">
        @csrf
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
        <div>
            <label class="block text-gray-700 font-medium mb-1">Date</label>
            <input type="date" name="date" value="{{ $date }}" class="border p-2 rounded" required>
        </div>
        <div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                Add to Queue
            </button>
        </div>
    </form>

    {{-- Matchup Controls --}}
    <div class="flex items-center space-x-2 mb-4">
        <form method="POST" action="{{ route('queues.matchup') }}" class="flex items-center space-x-2">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            <select name="strategy" class="border p-2 rounded w-48">
                <option value="random">Random</option>
                <option value="skill">By Skill</option>
                <option value="gender">By Gender</option>
            </select>
            <select name="max_courts" class="border p-2 rounded w-32">
                <option value="4">No. of Courts</option>
                <option value="3">3 Courts</option>
                <option value="4">4 Courts</option>
                <option value="5">5 Courts</option>
                <option value="6">6 Courts</option>
            </select>
            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                Generate Matchups
            </button>
        </form>

        <form method="POST" action="{{ route('queues.resetMatchups') }}">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">
                Reset Matchups
            </button>
        </form>

        {{-- Match History Button --}}
        <a href="{{ route('matches.records') }}" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded">
            Match History
        </a>
    </div>

    {{-- Queue Table --}}
    <table class="min-w-full bg-white border shadow rounded">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-3 text-left">Match</th>
                <th class="p-3 text-left">Court</th>
                <th class="p-3 text-left">Position</th>
                <th class="p-3 text-left">Player</th>
                <th class="p-3 text-left">Skill</th>
                <th class="p-3 text-left">Gender</th>
                <th class="p-3 text-left">Status</th>
                <th class="p-3 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            {{-- Waiting Players --}}
            @php $waitingPlayers = $queues->where('status','waiting'); @endphp
            @if($waitingPlayers->count())
                <tr class="bg-yellow-100 font-semibold">
                    <td colspan="8" class="p-3">Waiting Players</td>
                </tr>
                @foreach($waitingPlayers as $queue)
                    <tr>
                        <td class="p-3">-</td>
                        <td class="p-3">-</td>
                        <td class="p-3">{{ $queue->position }}</td>
                        <td class="p-3">{{ $queue->player->name }}</td>
                        <td class="p-3">{{ $queue->player->skill_level }}</td>
                        <td class="p-3">{{ ucfirst($queue->player->sex) }}</td>
                        <td class="p-3">Waiting</td>
                        <td class="p-3">
                            <form method="POST" action="{{ route('queues.remove',$queue) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            @endif

            {{-- Assigned Players --}}
            @php $assigned = $queues->whereNotNull('match_id')->groupBy('match_id')->sortKeys(); @endphp
            @foreach($assigned as $matchId => $matchPlayers)
                <tr class="bg-gray-200 font-semibold">
                    <td colspan="8" class="p-3">
                        <div class="flex items-center w-full justify-between">
                            {{-- Match Info --}}
                            <span class="flex-1">Match #{{ $matchId }} — Court {{ $matchPlayers->first()->court }}</span>

                            {{-- Mark as Done Button with Tooltip --}}
                            <div class="relative group flex-shrink-0">
                                <form method="POST" action="{{ route('queues.match.done', $matchId) }}">
                                    @csrf
                                    @php
                                        $allDone = $matchPlayers->every(fn($q) => $q->status === 'waiting'); // or $q->done if using boolean
                                    @endphp
                                    <button type="submit"
                                        class="w-8 h-8 rounded-full flex items-center justify-center
                                        {{ $allDone ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-700 hover:bg-gray-400' }}">
                                        @if($allDone)
                                            ✓
                                        @else
                                            ○
                                        @endif
                                    </button>
                                </form>

                                {{-- Tooltip --}}
                                <span class="absolute right-full mr-2 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                    {{ $allDone ? 'Already done' : 'Mark match as done' }}
                                </span>
                            </div>
                        </div>
                    </td>
                </tr>

                @foreach($matchPlayers as $queue)
                    <tr>
                        <td class="p-3">-</td>
                        <td class="p-3">{{ $queue->court }}</td>
                        <td class="p-3">{{ $queue->position }}</td>
                        <td class="p-3">{{ $queue->player->name }}</td>
                        <td class="p-3">{{ $queue->player->skill_level }}</td>
                        <td class="p-3">{{ ucfirst($queue->player->sex) }}</td>
                        <td class="p-3">{{ ucfirst($queue->status) }}</td>
                        <td class="p-3 flex space-x-2">
                            {{-- Replace Button --}}
                            <button type="button" class="text-blue-600 hover:underline"
                                onclick="openReplaceModal({{ $queue->id }})">
                                Replace
                            </button>

                            {{-- Remove Button --}}
                            <form method="POST" action="{{ route('queues.remove',$queue) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>

{{-- Replace Modal --}}
<div id="replaceModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center">
    <div class="bg-white p-6 rounded w-96">
        <h2 class="font-bold mb-4">Replace Player</h2>

        <form method="POST" action="{{ route('queues.replace') }}">
            @csrf
            <input type="hidden" name="out_queue_id" id="outQueueId">

            <select name="in_queue_id" class="w-full border p-2 rounded" required>
                @foreach($waitingPlayers as $waiting)
                    <option value="{{ $waiting->id }}">
                        {{ $waiting->player->name }} (Skill {{ $waiting->player->skill_level }})
                    </option>
                @endforeach
            </select>

            <div class="flex justify-end space-x-2 mt-4">
                <button type="button" onclick="closeReplaceModal()" class="border px-4 py-2 rounded">Cancel</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Replace</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReplaceModal(id) {
    document.getElementById('outQueueId').value = id;
    document.getElementById('replaceModal').classList.remove('hidden');
}
function closeReplaceModal() {
    document.getElementById('replaceModal').classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', function () {
    const playerSelect = document.getElementById('player-select');
    new Choices(playerSelect, {
        removeItemButton: true,
        searchEnabled: true,
        placeholderValue: 'Select players',
        shouldSort: false
    });
});
</script>
</x-app-layout>

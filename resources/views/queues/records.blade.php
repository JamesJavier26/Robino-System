<x-app-layout>
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">Completed Matches</h1>

    <table class="min-w-full bg-white border shadow rounded">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-3 text-left">Match ID</th>
                <th class="p-3 text-left">Date</th>
                <th class="p-3 text-left">Court</th>
                <th class="p-3 text-left">Players</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
                <tr class="border-b">
                    <td class="p-3">{{ $record->match_id }}</td>
                    <td class="p-3">{{ $record->date }}</td>
                    <td class="p-3">{{ $record->court }}</td>
                    <td class="p-3">{{ implode(', ', $record->players) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
</x-app-layout>

<x-app-layout>
    <div class="p-6 max-w-md mx-auto">
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf
            @method('PUT')

            <input name="name" value="{{ $user->name }}" class="w-full mb-3" required>
            <input name="email" type="email" value="{{ $user->email }}" class="w-full mb-3" required>

            <input name="password" type="password" placeholder="New Password (optional)" class="w-full mb-3">
            <input name="password_confirmation" type="password" placeholder="Confirm Password" class="w-full mb-3">
            <select name="role" class="w-full mb-6 p-2 border rounded" required>
                <option value="" disabled selected>Select Role</option>
                <option value="admin">Admin</option>
                <option value="user">User</option>
            </select>
                
            <button class="bg-blue-500 text-white px-4 py-2 rounded">Update</button>
        </form>
    </div>
</x-app-layout>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="container mx-auto my-10">
    <h1 class="text-3xl font-bold mb-6 text-center">Attendance Dashboard</h1>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-5 text-center">
            {{ session('success') }}
        </div>
    @endif

    <!-- Attendance Form -->
    <div class="bg-white p-6 rounded shadow mb-10">
        <h2 class="text-xl font-semibold mb-4">Add / Update Attendance</h2>
        <form method="POST" action="{{ route('attendance.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            @csrf
            <select name="employee_id" class="border rounded p-2" required>
                <option value="">Select Employee</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date" class="border rounded p-2" required>
            <input type="time" name="in_time" class="border rounded p-2">
            <input type="time" name="out_time" class="border rounded p-2">
            <button type="submit" class="bg-blue-600 text-white rounded p-2 hover:bg-blue-700 transition">Save</button>
        </form>
    </div>

    <!-- Attendance Table -->
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-xl font-semibold mb-4">Attendance Records</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-2 px-3 border-b">Employee</th>
                        <th class="py-2 px-3 border-b">Date</th>
                        <th class="py-2 px-3 border-b">In Time</th>
                        <th class="py-2 px-3 border-b">Out Time</th>
                        <th class="py-2 px-3 border-b">Status</th>
                        <th class="py-2 px-3 border-b">Overtime</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $att)
                        <tr class="text-center">
                            @php
                            $emp = DB::table('employees')->where('id',$att->employee_id)->latest()->first();
                            @endphp
                            <td class="py-2 px-3 border-b">{{ $emp->fname ?? '-' }}</td>
                            <td class="py-2 px-3 border-b">{{ $att->date }}</td>
                            <td class="py-2 px-3 border-b">
                                {{ $att->in_time ? \Carbon\Carbon::createFromFormat('H:i:s', $att->in_time)->format('h:i A') : '-' }}
                            </td>
                            <td class="py-2 px-3 border-b">
                                {{ $att->out_time ? \Carbon\Carbon::createFromFormat('H:i:s', $att->out_time)->format('h:i A') : '-' }}
                            </td>
                            <td class="py-2 px-3 border-b">
                                @if($att->status == 'Present')
                                    <span class="bg-green-200 text-green-800 px-2 py-1 rounded-full text-sm">Present</span>
                                @elseif($att->status == 'Absent')
                                    <span class="bg-red-200 text-red-800 px-2 py-1 rounded-full text-sm">Absent</span>
                                @elseif($att->status == 'Holiday')
                                    <span class="bg-green-200 text-blue-800 px-2 py-1 rounded-full text-sm">Holiday</span>
                                @elseif($att->status == 'Late')
                                    <span class="bg-yellow-200 text-sky-800 px-2 py-1 rounded-full text-sm">Late</span>
                                @elseif($att->status == 'Leave')
                                    <span class="bg-blue-200 text-blue-800 px-2 py-1 rounded-full text-sm">Leave</span>
                                @endif
                            </td>
                            <td class="py-2 px-3 border-b">{{ $att->overtime_hours ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-center text-gray-500">No attendance records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>

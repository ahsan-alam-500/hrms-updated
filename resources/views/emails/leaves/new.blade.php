@component('mail::message')
# New Leave Request

Employee: {{ $leave->employee->name }}  
Leave Type: {{ $leave->leave_type }}  
From: {{ $leave->start_date }}  
To: {{ $leave->end_date }}  
Reason: {{ $leave->reason ?? 'N/A' }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent

@extends('emails.layouts.branded')

@section('title', __('messages.reminder.title', ['organization' => $organization?->company_name ?? $organization?->name]))

@section('content')
<div class="email-body">{!! $customBody !!}</div>

<table role="presentation" class="reminder-table" width="100%" cellpadding="0" cellspacing="0" border="0">
    <thead>
        <tr>
            <th align="left">{{ __('messages.reminder.invoice') }}</th>
            <th align="left">{{ __('messages.reminder.due') }}</th>
            <th align="right">{{ __('messages.reminder.balance') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td><a href="{{ $row['url'] }}">{{ $row['number'] }}</a></td>
            <td>
                {{ $row['due'] }}
                @if($row['days_overdue'])
                <br><span class="reminder-overdue">{{ __('messages.reminder.days_overdue', ['days' => $row['days_overdue']]) }}</span>
                @endif
            </td>
            <td align="right">{{ $row['balance'] }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        @foreach($totals as $total)
        <tr>
            <td colspan="2">{{ __('messages.reminder.total_due', ['currency' => $total['currency']]) }}</td>
            <td align="right">{{ $total['amount'] }}</td>
        </tr>
        @endforeach
    </tfoot>
</table>
<p class="cta-note reminder-note">{{ __('messages.share_link.links_work_until', ['date' => $expiresAt->format('j M Y')]) }}</p>

@include('emails.partials.organization-footer', [
    'organization' => $organization,
    'location' => $location,
    'tight' => false,
])
@endsection

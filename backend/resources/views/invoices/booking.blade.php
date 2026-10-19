<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $booking->booking_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0A3F63; font-size: 13px; }
        h1 { font-size: 20px; margin: 0; }
        .muted { color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #ddd; padding: 8px; text-align: left; }
        .gold { color: #CFA356; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>S T Amar Bari Resort</h1>
    <p class="muted">Your Home on the Island</p>
    <p>{{ $resort['address'] }}<br>{{ $resort['phone'] }} · {{ $resort['email'] }}</p>
    <h2>Invoice {{ $booking->booking_number }}</h2>
    <p>
        Guest: {{ $booking->guests->first()?->full_name }}<br>
        Check-in: {{ $booking->check_in?->toDateString() }} 12:00<br>
        Check-out: {{ $booking->check_out?->toDateString() }} 10:00<br>
        Room: {{ $booking->category?->name_en }}<br>
        Guests: {{ $booking->adults }} adults, {{ $booking->children }} children<br>
        Meal plan: {{ $booking->mealPlan?->name_en ?? 'Room Only' }}<br>
        Status: {{ $booking->status->value }} / Payment {{ $booking->payments->where('status', 'successful')->count() ? 'Paid (advance)' : 'Pending' }}
    </p>
    <table>
        <tr><th>Item</th><th class="right">Amount (BDT)</th></tr>
        <tr><td>Room</td><td class="right">{{ number_format($booking->room_subtotal, 2) }}</td></tr>
        <tr><td>Meals</td><td class="right">{{ number_format($booking->meal_total, 2) }}</td></tr>
        <tr><td>Add-ons</td><td class="right">{{ number_format($booking->addon_total, 2) }}</td></tr>
        <tr><td>Discount</td><td class="right">-{{ number_format($booking->discount_total, 2) }}</td></tr>
        <tr><td><strong>Total</strong></td><td class="right"><strong>{{ number_format($booking->grand_total, 2) }}</strong></td></tr>
        <tr><td>Advance paid</td><td class="right">{{ number_format($booking->amount_paid, 2) }}</td></tr>
        <tr><td>Remaining balance</td><td class="right">{{ number_format($booking->balance_amount, 2) }}</td></tr>
    </table>
    <p class="muted">Staying at S T Amar Bari Resort — Your Home on the Island.</p>
</body>
</html>

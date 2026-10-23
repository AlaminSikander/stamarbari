<?php

namespace App\Filament\Resources\Bookings\Support;

use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\RoomCategory;
use App\Services\BookingService;
use App\Services\SettingsService;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Live summary under the room picker: availability per selected room, booking-time or
 * current prices, the total, the advance and what is still due.
 */
class ReservationSummary
{
    public static function render(Get $get, ?Booking $record): HtmlString
    {
        $payload = ReservationPayload::fromFormState([
            'check_in' => $get('check_in'),
            'check_out' => $get('check_out'),
            'adults' => $get('adults'),
            'children' => $get('children'),
            'meal_plan_id' => $get('meal_plan_id'),
            'room_selections' => $get('room_selections'),
        ]);
        if (! $payload['check_in'] || ! $payload['check_out'] || $payload['rooms'] === []) {
            return self::note('Choose dates and at least one room to see availability and the total.');
        }

        try {
            $preview = app(BookingService::class)->previewReservation(
                $payload,
                $record,
                ignoreBlocks: (bool) $get('override_room_blocks') && (bool) auth()->user()?->can('manage_rooms'),
            );
        } catch (BookingException $e) {
            return self::note($e->getMessage(), danger: true);
        }

        $quote = $preview['quote'];
        $categories = RoomCategory::withTrashed()->whereIn('id', array_column($payload['rooms'], 'room_category_id'))->get()->keyBy('id');
        $lines = $quote
            ? collect($quote['items'])
            : $record->roomLines();
        $totals = $quote ?? [
            'room_subtotal' => (float) $record->room_subtotal,
            'meal_total' => (float) $record->meal_total,
            'addon_total' => (float) $record->addon_total,
            'discount_total' => (float) $record->discount_total,
            'grand_total' => (float) $record->grand_total,
            'advance_amount' => (float) $record->advance_amount,
            'advance_percent' => (int) ($record->price_breakdown['advance_percent'] ?? app(SettingsService::class)->advancePercent()),
        ];
        $paid = (float) ($get('amount_paid') ?? 0);
        $nights = (int) ($lines->first()['nights'] ?? 0);

        $cell = 'padding:8px 10px;border-bottom:1px solid rgba(128,128,128,.25);text-align:left;vertical-align:top';
        $right = $cell.';text-align:right;white-space:nowrap';
        $rows = '';
        foreach ($lines as $line) {
            $category = $categories->get($line['room_category_id']);
            $free = $preview['availability'][$line['room_category_id']] ?? 0;
            $enough = $free >= $line['quantity'];
            $status = $enough
                ? '<span style="color:#1C7255">'.$free.' free</span>'
                : '<span style="color:#A84848;font-weight:600">'.($free === 0 ? 'Not available' : 'Only '.$free.' free').'</span>';
            $rows .= '<tr>'
                .'<td style="'.$cell.'"><strong>'.e($line['name']).'</strong><br><span style="opacity:.7">'.e($line['room_type_label'] ?? '').($category ? ' · '.e($category->capacityLabel()) : '').'</span></td>'
                .'<td style="'.$right.'">'.$line['quantity'].'</td>'
                .'<td style="'.$right.'">'.self::bdt($line['nightly_rate']).'</td>'
                .'<td style="'.$right.'">'.self::bdt($line['subtotal']).'</td>'
                .'<td style="'.$right.'">'.$status.'</td>'
                .'</tr>';
        }

        $rooms = $lines->sum('quantity');
        $summary = [
            [$rooms.' '.Str::plural('room', $rooms).' × '.$nights.' '.Str::plural('night', $nights), self::bdt($totals['room_subtotal'])],
        ];
        if ($totals['meal_total'] > 0) {
            $summary[] = ['Meals', self::bdt($totals['meal_total'])];
        }
        if ($totals['addon_total'] > 0) {
            $summary[] = ['Add-ons', self::bdt($totals['addon_total'])];
        }
        if ($totals['discount_total'] > 0) {
            $summary[] = ['Discount', '−'.self::bdt($totals['discount_total'])];
        }
        $summary[] = ['<strong>Total</strong>', '<strong>'.self::bdt($totals['grand_total']).'</strong>'];
        $summary[] = ['Advance required ('.$totals['advance_percent'].'%)', self::bdt($totals['advance_amount'])];
        $summary[] = ['Remaining after advance', self::bdt($totals['grand_total'] - $totals['advance_amount'])];
        $summary[] = ['Paid so far', self::bdt($paid)];
        $summary[] = ['<strong>Balance due</strong>', '<strong>'.self::bdt(max(0, $totals['grand_total'] - $paid)).'</strong>'];

        $summaryRows = '';
        foreach ($summary as [$label, $value]) {
            $summaryRows .= '<tr><td style="padding:4px 10px">'.$label.'</td><td style="padding:4px 10px;text-align:right;white-space:nowrap">'.$value.'</td></tr>';
        }

        $banner = '';
        if ($preview['error']) {
            $banner = self::banner($preview['error'].' This reservation cannot be saved until the rooms or dates are changed.', '#A84848');
        } elseif ($record && $quote) {
            $banner = self::banner('The stay or rooms changed. Saving will re-check availability and price this booking at current rates.', '#CFA356');
        } elseif ($record) {
            $banner = self::banner('Prices shown are the booking-time prices and will not change.', '#188B97');
        }

        return new HtmlString(
            $banner
            .'<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:14px">'
            .'<thead><tr>'
            .'<th style="'.$cell.'">Room</th><th style="'.$right.'">Qty</th><th style="'.$right.'">Per room / night</th><th style="'.$right.'">Subtotal</th><th style="'.$right.'">Availability</th>'
            .'</tr></thead><tbody>'.$rows.'</tbody></table></div>'
            .'<table style="margin-top:12px;margin-left:auto;min-width:280px;border-collapse:collapse;font-size:14px">'.$summaryRows.'</table>'
        );
    }

    private static function bdt(float|int|string $amount): string
    {
        return '৳'.number_format((float) $amount);
    }

    private static function note(string $message, bool $danger = false): HtmlString
    {
        return new HtmlString($danger ? self::banner($message, '#A84848') : '<p style="opacity:.7">'.e($message).'</p>');
    }

    private static function banner(string $message, string $color): string
    {
        return '<p style="margin-bottom:12px;padding:10px 12px;border-radius:8px;border:1px solid '.$color.';color:'.$color.'">'.e($message).'</p>';
    }
}

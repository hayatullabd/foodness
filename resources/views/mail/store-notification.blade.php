<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mailSubject }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f6f6f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f6f6f8; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
                    {{-- Header --}}
                    <tr>
                        <td style="padding: 24px 30px; background-color: #111827; text-align: left;">
                            <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: #ffffff; letter-spacing: 0.5px;">
                                {{ config('app.name', 'Foodness') }}
                            </h1>
                        </td>
                    </tr>

                    {{-- Main Content --}}
                    <tr>
                        <td style="padding: 30px;">
                            <div style="font-size: 15px; color: #374151; line-height: 1.7;">
                                {!! nl2br(e($mailBody)) !!}
                            </div>

                            {{-- Optional Order Summary Box --}}
                            @if (!empty($order))
                                <div style="margin-top: 25px; padding: 20px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
                                    <h3 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #111827;">
                                        Order Summary (#{{ $order->invoice_no }})
                                    </h3>
                                    <table width="100%" cellpadding="0" cellspacing="0" style="font-size: 13px; color: #4b5563;">
                                        <tr>
                                            <td style="padding: 4px 0; font-weight: 500;">Invoice No:</td>
                                            <td style="padding: 4px 0; text-align: right; font-weight: 600; color: #111827;">#{{ $order->invoice_no }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 4px 0; font-weight: 500;">Customer:</td>
                                            <td style="padding: 4px 0; text-align: right;">{{ $order->customer_name }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 4px 0; font-weight: 500;">Phone:</td>
                                            <td style="padding: 4px 0; text-align: right;">{{ $order->customer_phone }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 4px 0; font-weight: 500;">Payment Method:</td>
                                            <td style="padding: 4px 0; text-align: right; text-transform: uppercase;">{{ $order->payment_method }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 4px 0; font-weight: 500;">Status:</td>
                                            <td style="padding: 4px 0; text-align: right; font-weight: 600; color: #059669;">{{ ucfirst($order->statusRelation?->name ?? $order->status) }}</td>
                                        </tr>
                                        @if (!empty($order->tracking_number))
                                            <tr>
                                                <td style="padding: 4px 0; font-weight: 500;">Tracking Number:</td>
                                                <td style="padding: 4px 0; text-align: right; font-family: monospace;">{{ $order->tracking_number }}</td>
                                            </tr>
                                        @endif
                                        <tr style="border-top: 1px solid #e5e7eb;">
                                            <td style="padding: 8px 0 0 0; font-weight: 700; color: #111827; font-size: 14px;">Total:</td>
                                            <td style="padding: 8px 0 0 0; text-align: right; font-weight: 700; color: #111827; font-size: 14px;">Tk {{ number_format((float) $order->total, 2, '.', '') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            @endif
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 20px 30px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center; font-size: 12px; color: #9ca3af;">
                            <p style="margin: 0 0 4px 0;">&copy; {{ date('Y') }} {{ config('app.name', 'Foodness') }}. All rights reserved.</p>
                            <p style="margin: 0;">This is an automated system notification.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

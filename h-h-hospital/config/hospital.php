<?php

return [
    'timezone' => env('HOSPITAL_TIMEZONE', 'Asia/Karachi'),

    'currency' => env('HOSPITAL_CURRENCY', 'PKR'),

    'contact' => [
        'phone' => env('HOSPITAL_CONTACT_PHONE'),
        'address' => env('HOSPITAL_CONTACT_ADDRESS'),
    ],

    'booking' => [
        'horizon_days' => (int) env('HOSPITAL_BOOKING_HORIZON_DAYS', 30),
        'cutoff_minutes_before_closing' => (int) env('HOSPITAL_BOOKING_CUTOFF_MINUTES', 0),
        'requests_per_minute' => (int) env('HOSPITAL_BOOKING_REQUESTS_PER_MINUTE', 60),
        'requests_per_phone_per_minute' => (int) env('HOSPITAL_BOOKING_PHONE_REQUESTS_PER_MINUTE', 10),
        'requests_per_session_per_minute' => (int) env('HOSPITAL_BOOKING_SESSION_REQUESTS_PER_MINUTE', 10),
        'availability_requests_per_minute' => (int) env('HOSPITAL_AVAILABILITY_REQUESTS_PER_MINUTE', 120),
    ],
];

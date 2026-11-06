<?php

return [
    'overview' => 'Hospital overview', 'appointments' => 'Hospital appointments', 'intro' => 'Review every doctor’s appointments and patient records for the selected OPD date.',
    'all_statuses' => 'All statuses', 'all_sources' => 'All booking sources', 'source' => 'Booking source', 'apply' => 'Apply filters', 'date' => 'OPD date',
    'counts_hint' => 'Counts use the selected OPD date, doctor and source across all statuses. Confirmed includes waiting and current consultations.',
    'patients' => 'Find patient / MR', 'staff' => 'Manage staff', 'money' => 'Collections & refunds', 'activity' => 'Activity history', 'settings' => 'Hospital settings',
    'money_intro' => 'Review original cash collections, recorded refunds and net cash movement.', 'cash_date' => 'Cash recording date', 'cashier' => 'Original collection cashier', 'all_cashiers' => 'All cashiers',
    'money_hint' => 'Dates use :timezone. Collections use their payment time; refunds use their own refund time, including refunds for older payments. The cashier filter follows the original collection. Negative net means more cash refunded than collected on this date. Currencies are shown separately.',
    'collected' => 'Collected', 'refunded' => 'Refunded', 'net' => 'Net cash movement', 'collections' => 'Original collections', 'refunds' => 'Recorded refunds', 'amount' => 'Amount', 'recorded_at' => 'Recorded at', 'empty_money' => 'No cash records match these filters.',
    'activity_intro' => 'Trace committed changes with the staff member, time and saved before/after values.', 'activity_date' => 'Activity date', 'actor' => 'Staff member', 'all_actors' => 'All staff members', 'action' => 'Action', 'all_actions' => 'All actions',
    'activity_hint' => 'Saved payment, confirmation, Priority, cancellation, refund and completion facts from before activity logging are labelled “Retained history”. Earlier fee and schedule changes cannot be reconstructed; new changes are recorded from this release onward. Clinical notes, medicine text, passwords and form keys are excluded.',
    'historical' => 'Retained history', 'open_record' => 'Open record', 'before' => 'Before', 'after' => 'After', 'empty_activity' => 'No activity matches these filters.', 'yes' => 'Yes', 'no' => 'No',
    'settings_intro' => 'Manage the hospital details shown publicly and saved on new receipts and prescriptions.', 'name' => 'Hospital name', 'phone' => 'Hospital contact phone', 'address' => 'Hospital address', 'print_footer' => 'Print footer',
    'settings_hint' => 'Public pages use the latest details. New receipts and new prescription drafts save these details for future reprints. Existing receipts and prescriptions keep their original saved details. Timezone, currency and booking rules remain configured by the application.',
    'save_settings' => 'Save hospital details', 'settings_saved' => 'Hospital details saved. Public pages and new print records will use these details.', 'settings_conflict' => 'Another administrator updated these details. Copy your changes, reload the saved version, then review before saving.', 'settings_retry' => 'The result could not be confirmed. Review the latest saved details or retry this same form.',
    'actions' => [
        'doctor.created' => 'Doctor profile created', 'doctor.updated' => 'Doctor profile / fees changed', 'doctor.weekly_changed' => 'Weekly schedule changed', 'doctor.date_changed' => 'Date availability changed',
        'appointment.confirmed' => 'Appointment confirmed', 'payment.collected' => 'Cash collected', 'appointment.priority' => 'Paid Priority assigned', 'appointment.started' => 'Consultation started',
        'appointment.cancelled' => 'Appointment cancelled', 'payment.refunded' => 'Cash refund recorded', 'appointment.completed' => 'OPD completed', 'hospital.updated' => 'Hospital details changed',
    ],
    'fields' => [
        'specialization' => 'Specialization', 'is_active' => 'Profile active', 'normal_fee_minor' => 'Normal consultation fee', 'priority_surcharge_minor' => 'Priority charge', 'schedule' => 'OPD days / hours',
        'date' => 'OPD date', 'schedule_override' => 'Date availability', 'opens_at' => 'Opens at', 'closes_at' => 'Closes at', 'reason' => 'Reason', 'status' => 'Visit status', 'source' => 'Booking source',
        'token_number' => 'Token', 'receipt_number' => 'Receipt', 'total_minor' => 'Collected amount', 'is_priority' => 'Paid Priority', 'released_capacity' => 'Seats released', 'refund_reference' => 'Refund reference', 'amount_minor' => 'Cash refunded', 'prescription_id' => 'Prescription record',
        'name' => 'Hospital name', 'phone' => 'Contact phone', 'address' => 'Address', 'print_footer' => 'Print footer',
    ],
];

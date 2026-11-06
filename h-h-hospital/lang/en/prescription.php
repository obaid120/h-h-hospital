<?php

return [
    'title' => 'Prescription', 'edit' => 'Write prescription', 'view' => 'View prescription', 'draft' => 'Draft — not finalized', 'final' => 'Finalized prescription',
    'intro' => 'Save your notes and medicine lines as a draft. Print the saved copy or explicitly complete the OPD consultation when it is ready.',
    'notes' => 'Visit notes', 'advice' => 'Advice / general instructions', 'medicines' => 'Medicines', 'medicine_name' => 'Medicine name',
    'dose' => 'Dose', 'frequency' => 'Frequency', 'duration' => 'Duration', 'instructions' => 'Medicine instructions',
    'add' => 'Add medicine', 'remove' => 'Remove medicine', 'row' => 'Medicine :number', 'row_limit' => 'A maximum of 30 medicine lines can be saved.',
    'save' => 'Save Draft', 'saved' => 'Draft saved. The visit remains Confirmed.', 'saved_at' => 'Last saved', 'unsaved' => 'Unsaved changes. Save Draft before printing or completing OPD.',
    'clean' => 'You are viewing the saved draft.', 'new' => 'No draft has been saved yet.', 'draft_hint' => 'You can save incomplete dose, frequency or duration details in a draft. Fill those fields before completing OPD.',
    'print' => 'Print saved prescription', 'print_button' => 'Print A4 prescription', 'print_hint' => 'This page shows the saved record. Printing does not complete the consultation.',
    'complete' => 'Complete OPD', 'complete_hint' => 'Completes this consultation using the saved prescription and releases the current slot. Payment, token and reserved capacity stay recorded.',
    'complete_confirmed' => 'I reviewed the saved prescription and finished this consultation.', 'completed' => 'OPD completed. The finalized prescription is retained for view and reprint.',
    'back' => 'Back to my queue', 'reload' => 'Reload saved copy', 'reload_hint' => 'For a conflicting edit, copy your unsaved changes before reloading the newer saved copy.',
    'no_medicines' => 'No medicine lines recorded.', 'no_notes' => 'No visit notes recorded.', 'no_advice' => 'No general instructions recorded.',
    'doctor' => 'Doctor', 'patient' => 'Patient', 'mr' => 'MR number', 'age' => 'Visit age (years)', 'date' => 'OPD date', 'token' => 'Token',
    'finalized_at' => 'Finalized at', 'record' => 'Prescription record', 'admin_back' => 'Patients & MR',
    'errors' => [
        'not_current' => 'Only the doctor’s current paid Confirmed consultation can be edited or completed.',
        'key_conflict' => 'This form was already used with different prescription details. Review the saved copy before retrying.',
        'conflict' => 'Another window saved a newer draft, or this record is finalized. Your changes were not overwritten. Copy them before reloading the saved copy.',
        'save_first' => 'Save and review the latest draft before completing OPD.',
        'empty' => 'Record visit notes, advice or a medicine before completing OPD.',
        'incomplete_lines' => 'Fill the dose, frequency and duration for every medicine, then save the draft before completing OPD.',
        'retry' => 'The result could not be confirmed. Keep this form and review the saved record or retry the same draft. Completion can be safely checked again.',
    ],
];

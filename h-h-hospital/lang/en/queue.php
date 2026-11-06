<?php

return [
    'title' => 'My doctor queue', 'intro' => 'Today’s confirmed patients. Priority is called after the current consultation, then normal patients in token order.',
    'current' => 'Current consultation', 'waiting' => 'Waiting patients', 'empty' => 'No confirmed patients are waiting.',
    'no_current' => 'No consultation is in progress.', 'next' => 'Call Next', 'refresh' => 'Refresh queue',
    'token' => 'Token', 'patient' => 'Patient', 'mr' => 'MR number', 'age' => 'Visit age', 'priority' => 'Priority', 'normal' => 'Normal',
    'started' => 'Started :time', 'in_progress' => 'Finish the current consultation before calling another patient.',
    'called' => 'Token :token is the current consultation.', 'history' => 'Patient history',
    'no_profile' => 'Your account has no linked doctor profile. Contact the administrator to set up your queue.',
    'live_hint' => 'Updates every 10 seconds while this page is visible. Consultation entries are kept when the waiting list updates.',
    'updated' => 'Queue updated.', 'loading' => 'Updating queue…',
    'refresh_failed' => 'Queue could not be refreshed. The list may be out of date. Try Refresh queue.',
    'session_ended' => 'Your staff access has changed or expired. Reload this page to sign in again.',
    'reload' => 'Reload page', 'changed' => 'The current consultation changed in another window. Reload when your consultation entries are saved.',
    'date_changed' => 'The hospital date has changed. Save your consultation entries and reload before calling another patient.',
    'errors' => [
        'date_changed' => 'This queue form belongs to another OPD date. Refresh the page before calling a patient.',
        'stale' => 'This Call Next form is out of date. Refresh the queue before continuing.',
        'needs_review' => 'The current consultation record needs review. Contact the administrator before calling another patient.',
        'retry' => 'The call result could not be confirmed. Refresh the queue to check the current patient before trying again.',
    ],
];

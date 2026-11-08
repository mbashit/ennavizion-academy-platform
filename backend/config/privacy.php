<?php

return [
    // How long to keep form submissions (applications, messages, newsletter sign-ups,
    // unpaid donation attempts), in days. Null keeps them until deleted by hand. This is a
    // decision for the academy, ideally with legal advice — see docs/PRODUCTION-READINESS.md.
    'retention_days' => env('SUBMISSION_RETENTION_DAYS') ? (int) env('SUBMISSION_RETENTION_DAYS') : null,
];

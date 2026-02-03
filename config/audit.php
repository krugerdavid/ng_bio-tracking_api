<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auditable Models
    |--------------------------------------------------------------------------
    |
    | Models that implement AuditableContract will be observed and their
    | create, update, delete events will be logged to audit_logs.
    |
    */
    'enabled' => env('AUDIT_ENABLED', true),

    'models' => [
        \App\Models\Member::class,
        \App\Models\MembershipPlan::class,
        \App\Models\Payment::class,
        \App\Models\Bioimpedance::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Events to log
    |--------------------------------------------------------------------------
    */
    'events' => ['created', 'updated', 'deleted'],

    /*
    |--------------------------------------------------------------------------
    | Attributes to exclude from old_values / new_values
    |--------------------------------------------------------------------------
    |
    | Sensitive or irrelevant attributes that should never be stored in logs.
    | Applied globally; individual models can override via excludedAttributes().
    |
    */
    'excluded_attributes' => [
        'password',
        'remember_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue audit logging
    |--------------------------------------------------------------------------
    |
    | If true, audit records are created via a queued job to avoid blocking
    | the request. Set to false for synchronous logging.
    |
    */
    'queue' => env('AUDIT_QUEUE', false),

    'queue_connection' => env('AUDIT_QUEUE_CONNECTION', null),

];

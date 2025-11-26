<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database backup behavior
    |
    */

    /**
     * Number of days to retain backup files
     * Older backups will be automatically deleted
     */
    'retention_days' => env('BACKUP_RETENTION_DAYS', 30),

    /**
     * Delete local backup file after uploading to cloud storage
     */
    'delete_local_after_upload' => env('BACKUP_DELETE_LOCAL', false),

    /**
     * Default storage disk for backups
     * Options: 'local', 's3', 'ftp', etc.
     */
    'default_disk' => env('BACKUP_DISK', 'local'),

    /**
     * Compress backups by default
     */
    'compress' => env('BACKUP_COMPRESS', true),

    /**
     * Notification settings
     */
    'notifications' => [
        /**
         * Send notification on backup success
         */
        'on_success' => env('BACKUP_NOTIFY_SUCCESS', false),

        /**
         * Send notification on backup failure
         */
        'on_failure' => env('BACKUP_NOTIFY_FAILURE', true),

        /**
         * Email addresses to notify
         */
        'emails' => explode(',', env('BACKUP_NOTIFY_EMAILS', '')),
    ],

];

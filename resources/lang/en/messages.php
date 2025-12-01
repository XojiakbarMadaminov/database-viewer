<?php

return [
    'navigation' => [
        'label' => 'Database Browser',
        'title' => 'Database Browser',
    ],

    'fields' => [
        'table' => 'Table',
    ],

    'toolbar' => [
        'table_name' => 'Table: :table',
        'no_table' => 'No table selected',
    ],

    'actions' => [
        'select_table' => [
            'label' => 'Select table',
            'heading' => 'Select table',
            'submit' => 'Apply',
        ],
        'refresh' => [
            'label' => 'Refresh',
        ],
        'create' => [
            'label' => 'Create',
            'heading' => 'Create record',
        ],
        'edit' => [
            'label' => 'Edit',
        ],
        'delete' => [
            'label' => 'Delete',
            'heading' => 'Delete record?',
            'description' => 'This action cannot be undone.',
        ],
    ],

    'notifications' => [
        'table_refreshed' => 'Table data refreshed',
        'record_created' => 'Record created',
        'record_updated' => 'Record updated successfully',
        'record_deleted' => 'Record deleted',
    ],

    'errors' => [
        'select_table_before_create' => 'Select a table before creating a record.',
        'select_table_before_edit' => 'Select a table before editing.',
        'select_table_before_delete' => 'Select a table before deleting records.',
        'writes_disabled' => 'Writes are disabled for this environment.',
        'no_create_payload' => 'Provide at least one value before creating a record.',
        'no_edit_payload' => 'No editable data provided.',
        'unknown_key' => 'Unable to determine the record key.',
    ],

    'preview' => [
        'heading' => 'Column value',
        'placeholder' => '—',
        'null' => 'NULL',
    ],
];

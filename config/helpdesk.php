<?php

return [

    // Prefix used when generating human-readable ticket references (TKT-000123).
    'ticket_reference_prefix' => env('TICKET_REFERENCE_PREFIX', 'TKT'),

    // Small credit line shown in the footer.
    'credit' => env('APP_CREDIT', 'Built with coffee by pradigta'),

    // Attachment upload constraints (enforced in StoreAttachmentRequest).
    'attachments' => [
        'max_size_kb' => (int) env('ATTACHMENT_MAX_SIZE_KB', 10240), // 10 MB
        'allowed_extensions' => array_filter(explode(',', env(
            'ATTACHMENT_ALLOWED_EXTENSIONS',
            'jpg,jpeg,png,gif,webp,pdf,txt,log,csv,doc,docx,xls,xlsx,ppt,pptx,zip'
        ))),
    ],

];

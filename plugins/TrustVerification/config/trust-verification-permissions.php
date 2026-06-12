<?php

/**
 * Trust Verification admin permission module (merged into config('rolepermission') at boot).
 *
 * Staff UI labels map as trust_verification_{role}, e.g. trust_verification_read → module trust_verification, role read.
 */
return [
    'module' => 'trust_verification',
    'roles' => [
        'read' => 'View orders and order detail',
        'update' => 'Change order status and verification checks',
        'documents' => 'Download or delete customer uploaded documents',
        'reports' => 'Upload, download, or delete verification report PDFs',
        'payments' => 'Manual payment status changes',
        'settings' => 'Packages, cities, automation and retention settings',
        'audit' => 'View audit history on orders',
    ],
];

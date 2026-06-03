<?php
/**
 * Declarative routes (method + path + middleware).
 * Unmatched URLs fall back to legacy Dispatch (ClassRoutes + dynamic methods).
 *
 * Path params: {id}, {entityType}, etc.
 * Middleware: auth, csrf, can:permission.name, admin, super_admin
 */
return [
    // --- Auth ---
    [
        'path' => 'authenticate',
        'controller' => 'ControllerAuth',
        'action' => 'authenticate',
        'methods' => ['POST'],
        'middleware' => ['csrf'],
    ],
    [
        'path' => 'store',
        'controller' => 'ControllerAuth',
        'action' => 'store',
        'methods' => ['POST'],
        'middleware' => ['csrf'],
    ],

    // --- Dashboard: notificações ---
    [
        'path' => 'dashboard/markNotificationsRead',
        'controller' => 'ControllerDashboard',
        'action' => 'markNotificationsRead',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf'],
    ],
    [
        'path' => 'dashboard/markNotificationRead/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'markNotificationRead',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf'],
    ],
    [
        'path' => 'dashboard/markNotificationUnread/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'markNotificationUnread',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf'],
    ],

    // --- Dashboard: financeiro ---
    [
        'path' => 'dashboard/confirmPayment/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'confirmPayment',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:payments.manage'],
    ],
    [
        'path' => 'dashboard/cancelPayment/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'cancelPayment',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:payments.manage'],
    ],
    [
        'path' => 'dashboard/rejectCommissionOwnerPayment/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'rejectCommissionOwnerPayment',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:payments.manage'],
    ],
    [
        'path' => 'dashboard/confirmAffiliatePayout/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'confirmAffiliatePayout',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:payments.manage'],
    ],
    [
        'path' => 'dashboard/submitCommissionPayment/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'submitCommissionPayment',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf'],
    ],

    // --- Dashboard: moderação utilizadores ---
    [
        'path' => 'dashboard/moderate_users',
        'controller' => 'ControllerDashboard',
        'action' => 'moderateUsers',
        'methods' => ['GET'],
        'middleware' => ['auth', 'can:users.review'],
    ],
    [
        'path' => 'dashboard/blockUserAccess/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'blockUserAccess',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:users.review'],
    ],
    [
        'path' => 'dashboard/unblockUserAccess/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'unblockUserAccess',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:users.review'],
    ],
    [
        'path' => 'dashboard/suspendUserAccess/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'suspendUserAccess',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:users.review'],
    ],
    [
        'path' => 'dashboard/unsuspendUserAccess/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'unsuspendUserAccess',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:users.review'],
    ],
    [
        'path' => 'dashboard/setAdminRole/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'setAdminRole',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'super_admin'],
    ],
    [
        'path' => 'dashboard/approveUser/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'approveUser',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:users.review'],
    ],
    [
        'path' => 'dashboard/rejectUser/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'rejectUser',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:users.review'],
    ],

    // --- Dashboard: trust badge ---
    [
        'path' => 'dashboard/requestTrustedBadge',
        'controller' => 'ControllerDashboard',
        'action' => 'requestTrustedBadge',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf'],
    ],
    [
        'path' => 'dashboard/approveTrustedBadge/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'approveTrustedBadge',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:users.review'],
    ],
    [
        'path' => 'dashboard/rejectTrustedBadge/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'rejectTrustedBadge',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:users.review'],
    ],
    [
        'path' => 'dashboard/confirmTrustedBadgePayment/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'confirmTrustedBadgePayment',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:payments.manage'],
    ],

    // --- Dashboard: documentos ---
    [
        'path' => 'dashboard/reviewDocuments',
        'controller' => 'ControllerDashboard',
        'action' => 'reviewDocuments',
        'methods' => ['GET'],
        'middleware' => ['auth', 'can:documents.review'],
    ],
    [
        'path' => 'dashboard/approveDocument/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'approveDocument',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:documents.review'],
    ],
    [
        'path' => 'dashboard/rejectDocument/{id}',
        'controller' => 'ControllerDashboard',
        'action' => 'rejectDocument',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:documents.review'],
    ],
    [
        'path' => 'dashboard/submitAccountDocument',
        'controller' => 'ControllerDashboard',
        'action' => 'submitAccountDocument',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf'],
    ],
    [
        'path' => 'dashboard/resubmitDocument/{documentId}',
        'controller' => 'ControllerDashboard',
        'action' => 'resubmitDocument',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf'],
    ],

    // --- Property moderation ---
    [
        'path' => 'property/moderate',
        'controller' => 'ControllerProperty',
        'action' => 'moderate',
        'methods' => ['GET'],
        'middleware' => ['auth', 'can:properties.moderate'],
    ],
    [
        'path' => 'property/approve/{id}',
        'controller' => 'ControllerProperty',
        'action' => 'approve',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:properties.moderate'],
    ],
    [
        'path' => 'property/reject/{id}',
        'controller' => 'ControllerProperty',
        'action' => 'reject',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:properties.moderate'],
    ],
    [
        'path' => 'property/startAnalysis/{id}',
        'controller' => 'ControllerProperty',
        'action' => 'startAnalysis',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:properties.moderate'],
    ],
    [
        'path' => 'property/approveBoost/{id}',
        'controller' => 'ControllerProperty',
        'action' => 'approveBoost',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:properties.moderate'],
    ],
    [
        'path' => 'property/rejectBoost/{id}',
        'controller' => 'ControllerProperty',
        'action' => 'rejectBoost',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf', 'can:properties.moderate'],
    ],

    // --- Requests ---
    [
        'path' => 'request/updateStatus/{id}',
        'controller' => 'ControllerRequest',
        'action' => 'updateStatus',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf'],
    ],

    // --- Notification archive API ---
    [
        'path' => 'notification/archiveItem',
        'controller' => 'ControllerNotification',
        'action' => 'archiveItem',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf'],
    ],
    [
        'path' => 'notification/markAllAsRead',
        'controller' => 'ControllerNotification',
        'action' => 'markAllAsRead',
        'methods' => ['POST'],
        'middleware' => ['auth', 'csrf'],
    ],
];

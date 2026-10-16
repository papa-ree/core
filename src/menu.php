<?php

/**
 * Menu definisi untuk package bale/core (Landlord Layout).
 *
 * Grup 'core' mencakup fitur-fitur inti sistem: dashboard,
 * manajemen user, role, permission, log, dan tools.
 */
return [
    'type' => 'landlord',

    'groups' => [
        [
            'key' => 'core',
            'label' => 'System',
            'icon' => 'layout-dashboard',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'url' => 'landlord-dashboard',
                    'icon' => 'layout-dashboard',
                    'permission' => null,
                ],
                [
                    'label' => 'User Management',
                    'url' => 'user-management',
                    'icon' => 'users',
                    'permission' => 'user-management.read',
                ],
                [
                    'label' => 'Role',
                    'url' => 'roles',
                    'icon' => 'user-key',
                    'permission' => 'role.read',
                ],
                [
                    'label' => 'Permission',
                    'url' => 'permissions',
                    'icon' => 'shield-check',
                    'permission' => 'permission.read',
                ],
                [
                    'label' => 'Authentication Log',
                    'url' => 'authentication-logs',
                    'icon' => 'rotate-ccw-key',
                    'permission' => 'authentication-log.read',
                ],
                [
                    'label' => 'Activity Log',
                    'url' => 'activity-logs',
                    'icon' => 'folder-clock',
                    'permission' => 'activity-log.read',
                ],
                [
                    'label' => 'Login Allowlist',
                    'url' => 'security/login-allowlist',
                    'icon' => 'shield',
                    'permission' => 'entrance-gate.read',
                ],
                [
                    'label' => 'WP Tools',
                    'url' => 'wp-tools/sql-migrator',
                    'icon' => 'database-zap',
                    'permission' => null,
                ],
            ],
        ],
    ],
];

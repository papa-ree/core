<?php

namespace Bale\Core\Livewire\SharedComponents;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Bale\GupaPanel\GupaPanelServiceProvider;
use Nawasara\Frasasti\FrasastiServiceProvider;
use Nawasara\Kosadata\Livewire\Pages\Overview\Index;
use Paparee\Rakaca\RakacaServiceProvider;

#[Layout('core::layouts.app')]
class LandlordSidebar extends Component
{
    public function render()
    {
        return view('core::livewire.shared-components.landlord-sidebar');
    }

    #[Computed]
    public function availableMenus(): array
    {
        $menu = [
            [
                'label' => 'Dashboard',
                'url' => 'landlord-dashboard',
                'icon' => 'layout-dashboard',
            ],
        ];

        // Add User Management menu if user has permission
        if (auth()->check() && auth()->user()->can('user-management.read')) {
            $menu[] = [
                'label' => __('User Management'),
                'url' => 'user-management',
                'icon' => 'users',
            ];
        }

        // Add Role Management menu if user has permission
        if (auth()->check() && auth()->user()->can('role.read')) {
            $menu[] = [
                'label' => __('Role'),
                'url' => 'roles',
                'icon' => 'user-key',
            ];
        }

        // Add Permission Management menu if user has permission
        if (auth()->check() && auth()->user()->can('permission.read')) {
            $menu[] = [
                'label' => __('Permission'),
                'url' => 'permissions',
                'icon' => 'shield-check',
            ];
        }

        // Add Authentication Log menu if user has permission
        if (auth()->check() && auth()->user()->can('authentication-log.read')) {
            $menu[] = [
                'label' => __('Authentication Log'),
                'url' => 'authentication-logs',
                'icon' => 'rotate-ccw-key',
            ];
        }

        // Activity Log
        if (auth()->check() && auth()->user()->can('activity-log.read')) {
            $menu[] = [
                'label' => __('Activity Log'),
                'url' => 'activity-logs',
                'icon' => 'folder-clock',
            ];
        }

        // WP Tools — available to all authenticated landlord users
        $menu[] = [
            'label' => __('WP Tools'),
            'url' => 'wp-tools/sql-migrator',
            'icon' => 'database-zap',
        ];

        // Add Rakaca Menus if package exists
        if (class_exists(RakacaServiceProvider::class)) {
            // Add Rakaca Service Management
            if (auth()->check() && auth()->user()->can('service.read')) {
                $menu[] = [
                    'label' => __('Service'),
                    'url' => 'rakaca/services',
                    'icon' => 'layers',
                    'group' => 'rakaca',
                ];
            }

            // Add Rakaca Form Management
            if (auth()->check() && auth()->user()->can('form.read')) {
                $menu[] = [
                    'label' => __('Formulir'),
                    'url' => 'rakaca/forms',
                    'icon' => 'clipboard-list',
                    'group' => 'rakaca',
                ];
            }

            // Add Rakaca Submission Management
            if (auth()->check() && auth()->user()->can('submission.read')) {
                $menu[] = [
                    'label' => __('Submission'),
                    'url' => 'rakaca/submissions',
                    'icon' => 'file-text',
                    'group' => 'rakaca',
                ];
            }

            // Add Rakaca Personal Service (Customer) Management
            if (auth()->check() && auth()->user()->can('personal-service.read')) {
                $menu[] = [
                    'label' => __('Personal Service'),
                    'url' => 'rakaca/personal-services',
                    'icon' => 'user-check',
                    'group' => 'rakaca',
                ];
            }
        }

        // Add Frasasti Menus if package exists
        if (class_exists(FrasastiServiceProvider::class)) {
            // Overview
            $menu[] = [
                'label' => __('Overview'),
                'url' => 'frasasti/overview',
                'icon' => 'layout-dashboard',
                'group' => 'frasasti',
            ];

            // Ticket Management
            if (auth()->check() && auth()->user()->can('ticket.read')) {
                $menu[] = [
                    'label' => __('Tickets'),
                    'url' => 'frasasti/tickets',
                    'icon' => 'ticket',
                    'group' => 'frasasti',
                ];
            }

            // Contact Management
            if (auth()->check() && auth()->user()->can('contact.read')) {
                $menu[] = [
                    'label' => __('Contacts'),
                    'url' => 'frasasti/contacts',
                    'icon' => 'users',
                    'group' => 'frasasti',
                ];
            }
        }

        // Add GupaPanel Menus if package exists
        if (class_exists(GupaPanelServiceProvider::class)) {
            // Overview
            $menu[] = [
                'label' => __('Overview'),
                'url' => 'gupa-panel/overview',
                'icon' => 'bar-chart-3',
                'group' => 'gupa-panel',
            ];

            // Blacklist
            if (auth()->check() && auth()->user()->can('gupa-panel.blacklist.read')) {
                $menu[] = [
                    'label' => __('Blacklist'),
                    'url' => 'gupa-panel/blacklist',
                    'icon' => 'list-minus',
                    'group' => 'gupa-panel',
                ];
            }

            // Whitelist
            if (auth()->check() && auth()->user()->can('gupa-panel.whitelist.read')) {
                $menu[] = [
                    'label' => __('Whitelist'),
                    'url' => 'gupa-panel/whitelist',
                    'icon' => 'list-plus',
                    'group' => 'gupa-panel',
                ];
            }

            // Blocked IPs
            if (auth()->check() && auth()->user()->can('gupa-panel.blocked-ip.read')) {
                $menu[] = [
                    'label' => __('Blocked IPs'),
                    'url' => 'gupa-panel/blocked-ips',
                    'icon' => 'shield-x',
                    'group' => 'gupa-panel',
                ];
            }

            // False Positive Review
            if (auth()->check() && auth()->user()->can('gupa-panel.false-positive.review')) {
                $menu[] = [
                    'label' => __('False Positive Review'),
                    'url' => 'gupa-panel/review-false-positive',
                    'icon' => 'check-circle',
                    'group' => 'gupa-panel',
                ];
            }

            // Sync Management
            if (auth()->check() && auth()->user()->can('gupa-panel.sync.manual')) {
                $menu[] = [
                    'label' => __('Sync Management'),
                    'url' => 'gupa-panel/sync',
                    'icon' => 'refresh-cw',
                    'group' => 'gupa-panel',
                ];
            }

            // Known Crawlers
            if (auth()->check() && auth()->user()->can('gupa-panel.known-crawler.read')) {
                $menu[] = [
                    'label' => __('Known Crawlers'),
                    'url' => 'gupa-panel/known-crawlers',
                    'icon' => 'bot',
                    'group' => 'gupa-panel',
                ];
            }
        }

        // Add Bale CMS Menus if package exists
        if (class_exists(RakacaServiceProvider::class)) {
            // Add Organization Management
            if (auth()->check() && auth()->user()->can('organization.read')) {
                $menu[] = [
                    'label' => __('Organization'),
                    'url' => 'rakaca/organizations',
                    'icon' => 'building-2',
                    'group' => 'bale cms',
                ];
            }

            // Add Bale List Management
            if (auth()->check() && auth()->user()->can('bale-list.read')) {
                $menu[] = [
                    'label' => __('Bale List'),
                    'url' => 'rakaca/bale-lists',
                    'icon' => 'server',
                    'group' => 'bale cms',
                ];
            }

            // Add Bale User Management
            if (auth()->check() && auth()->user()->can('bale-user.read')) {
                $menu[] = [
                    'label' => __('Bale User'),
                    'url' => 'rakaca/bale-users',
                    'icon' => 'user-cog',
                    'group' => 'bale cms',
                ];
            }

            // Add Analytics Management
            if (auth()->check() && auth()->user()->can('analytic.read')) {
                $menu[] = [
                    'label' => __('Analytics'),
                    'url' => 'rakaca/analytics',
                    'icon' => 'bar-chart-3',
                    'group' => 'bale cms',
                ];
            }
        }

        return $menu;

    }

    #[Computed]
    public function availableKosadataMenus(): array
    {
        $menu = [];

        $conditionalMenus = [
            Index::class => [
                'label' => 'Overview',
                'url' => 'internet-desa-overview',
                'icon' => 'activity',
            ],
            \Nawasara\Kosadata\Livewire\Pages\InternetProvider\Index::class => [
                'label' => 'Internet Provider',
                'url' => 'internet-provider',
                'icon' => 'globe',
            ],
            \Nawasara\Kosadata\Livewire\Pages\IspDesa\Index::class => [
                'label' => 'ISP Desa',
                'url' => 'isp-desa',
                'icon' => 'network',
            ],
        ];

        foreach ($conditionalMenus as $class => $item) {
            if (class_exists($class)) {
                $menu[] = $item;
            }
        }

        return $menu;
    }
}

<?php

namespace Bale\Core\Livewire\SharedComponents;

use Livewire\Component;
use Livewire\Attributes\{Computed, Layout};

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
                'icon' => 'shield',
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
                'icon' => 'history',
            ];
        }

        // Add Rakaca Menus if package exists
        if (class_exists(\Paparee\Rakaca\RakacaServiceProvider::class)) {
            // Add Rakaca Service Management
            if (auth()->check() && auth()->user()->can('service.read')) {
                $menu[] = [
                    'label' => __('Service'),
                    'url' => 'rakaca/services',
                    'icon' => 'layers',
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

        // Add Bale CMS Menus if package exists
        if (class_exists(\Paparee\Rakaca\RakacaServiceProvider::class)) {
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
            \Nawasara\Kosadata\Livewire\Pages\Overview\Index::class =>
                [
                    'label' => 'Overview',
                    'url' => 'internet-desa-overview',
                    'icon' => 'activity',
                ],
            \Nawasara\Kosadata\Livewire\Pages\InternetProvider\Index::class =>
                [
                    'label' => 'Internet Provider',
                    'url' => 'internet-provider',
                    'icon' => 'globe',
                ],
            \Nawasara\Kosadata\Livewire\Pages\IspDesa\Index::class =>
                [
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

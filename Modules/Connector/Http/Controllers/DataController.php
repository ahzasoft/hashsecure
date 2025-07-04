<?php

namespace Modules\Connector\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{
    public function superadmin_package()
    {
        return [
            [
                'name' => 'connector_module',
                'label' => __('connector::lang.connector_module'),
                'default' => false,
            ],
        ];
    }

    /**
     * Adds Connectoe menus
     *
     * @return null
     */
    public function modifyAdminMenu()
    {
        $module_util = new ModuleUtil();

        if (auth()->user()->can('superadmin')) {
            $is_connector_enabled = $module_util->isModuleInstalled('Connector');
        } else {
            $business_id = session()->get('user.business_id');
            $is_connector_enabled = (bool) $module_util->hasThePermissionInSubscription($business_id, 'connector_module', 'superadmin_package');
        }
        if (false) {
            Menu::modify('admin-sidebar-menu', function ($menu) {
                $menu->url(
                    action([\Modules\Connector\Http\Controllers\ClientController::class, 'index']),
                    __('connector::lang.connector'),
                    ['icon' => 'fa fas fa-tachometer-alt', 'active' => request()->segment(1) == 'home'])->order(150);


               /* $menu->dropdown(
                    __('connector::lang.connector'),
                    function ($sub) {
                        if (auth()->user()->can('superadmin')) {
                            $sub->url(
                                action([\Modules\Connector\Http\Controllers\ClientController::class, 'index']),
                               __('connector::lang.clients'),
                                ['icon' => 'fa fas fa-network-wired', 'active' => request()->segment(1) == 'connector' && request()->segment(2) == 'api']
                            );
                        }
                        $sub->url(
                            url('\docs'),
                           __('connector::lang.documentation'),
                            ['icon' => 'fa fas fa-book', 'active' => request()->segment(1) == 'docs']
                        );
                    },
                    ['icon' => 'fas fa-plug']
                )->order(89);*/
            });
        }
    }
}

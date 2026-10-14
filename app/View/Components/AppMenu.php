<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;

class AppMenu extends Component
{
    public $user;
    public $activeMenu;
    public $menuItems;
    public $showUserSection;

    public function __construct($activeMenu = 'dashboard', $showUserSection = true)
    {
        $this->user = Auth::user();
        $this->activeMenu = $activeMenu;
        $this->showUserSection = $showUserSection;
        $this->menuItems = $this->getMenuItems();
    }

    private function getMenuItems()
    {
        return [
            
            
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'feather icon-clipboard',
                'url' => '#',
                'submenu' => [
                    'Dashboard' => ['title' => 'Tableau de bord', 'url' => '#']
                   
                ]
            ],
             'immobilier' => [
                'title' => 'Immobilier',
                'icon' => 'feather icon-clipboard',
                'url' => '#',
                'submenu' => [
                    'Immobilier' => ['title' => 'Parc Immobilier', 'url' => '#']
                   
                ]
            ],
            
           
            
            
           
        ];
    }

    public function isActive($menuKey, $submenuKey = null)
    {
        if ($submenuKey) {
            return $this->activeMenu === $submenuKey;
        }
        return $this->activeMenu === $menuKey;
    }

    public function render()
    {
        return view('components.app-menu');
    }
}
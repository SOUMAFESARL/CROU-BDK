<?php

namespace App\View\Components;

use Illuminate\View\Component;

class AppFooter extends Component
{
    public $showIEWarning;
    public $copyrightText;
    public $version;
    public $showBrowserWarning;

    public function __construct(
        $showIEWarning = false,
        $copyrightText = null,
        $version = '1.0.0',
        $showBrowserWarning = true
    ) {
        $this->showIEWarning = $showIEWarning;
        $this->copyrightText = $copyrightText ?? '&copy; ' . date('Y') . ' Your Company. All rights reserved.';
        $this->version = $version;
        $this->showBrowserWarning = $showBrowserWarning;
    }

    public function render()
    {
        return view('components.app-footer');
    }
}
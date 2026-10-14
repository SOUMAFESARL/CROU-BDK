@props([
    'showIEWarning' => false,
    'copyrightText' => '&copy; ' . date('Y') . ' Your Company. All rights reserved.',
    'showBrowserWarning' => true
])

{{-- Pied de page du shell admin (styles dans public/css/admin-shell.css). --}}
<footer class="as-footer">
    <span class="as-footer-mark" aria-hidden="true"></span>
    <p class="as-footer-text">{{ $copyrightText }}</p>
</footer>

<!-- Warning Section for Older Browsers -->
@if($showBrowserWarning)
<!--[if lt IE 10]>
<div class="ie-warning">
    <h1>Warning!!</h1>
    <p>You are using an outdated version of Internet Explorer, please upgrade <br/>to any of the following web browsers to access this website.</p>
    <div class="iew-container">
        <ul class="iew-download">
            <li>
                <a href="http://www.google.com/chrome/" target="_blank">
                    <img src="{{ asset('files/assets/images/browser/chrome.png') }}" alt="Chrome">
                    <div>Chrome</div>
                </a>
            </li>
            <li>
                <a href="https://www.mozilla.org/en-US/firefox/new/" target="_blank">
                    <img src="{{ asset('files/assets/images/browser/firefox.png') }}" alt="Firefox">
                    <div>Firefox</div>
                </a>
            </li>
            <li>
                <a href="http://www.opera.com" target="_blank">
                    <img src="{{ asset('files/assets/images/browser/opera.png') }}" alt="Opera">
                    <div>Opera</div>
                </a>
            </li>
            <li>
                <a href="https://www.apple.com/safari/" target="_blank">
                    <img src="{{ asset('files/assets/images/browser/safari.png') }}" alt="Safari">
                    <div>Safari</div>
                </a>
            </li>
            <li>
                <a href="http://windows.microsoft.com/en-us/internet-explorer/download-ie" target="_blank">
                    <img src="{{ asset('files/assets/images/browser/ie.png') }}" alt="IE">
                    <div>IE (9 & above)</div>
                </a>
            </li>
        </ul>
    </div>
    <p>Sorry for the inconvenience!</p>
</div>
<![endif]-->
@endif
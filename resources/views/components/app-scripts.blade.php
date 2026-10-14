@props([
    'jqueryVersion' => '3.3.1',
    'bootstrapVersion' => '4.1.3',
    'loadChartJS' => true,
    'loadAmCharts' => true,
    'loadCustomDashboard' => true,
    'additionalScripts' => []
])

<!-- Required Jquery -->
<script type="text/javascript" src="{{ asset('files/bower_components/jquery/dist/jquery.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('files/bower_components/jquery-ui/jquery-ui.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('files/bower_components/popper.js/dist/umd/popper.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('files/bower_components/bootstrap/dist/js/bootstrap.min.js') }}"></script>

<!-- jquery slimscroll js -->
<script type="text/javascript" src="{{ asset('files/bower_components/jquery-slimscroll/jquery.slimscroll.js') }}"></script>

<!-- modernizr js -->
<script type="text/javascript" src="{{ asset('files/bower_components/modernizr/modernizr.js') }}"></script>

<!-- Chart js -->
@if($loadChartJS)
<script type="text/javascript" src="{{ asset('files/bower_components/chart.js/dist/Chart.js') }}"></script>
@endif

<!-- amchart js -->
@if($loadAmCharts)
<script src="{{ asset('files/assets/pages/widget/amchart/amcharts.js') }}"></script>
<script src="{{ asset('files/assets/pages/widget/amchart/serial.js') }}"></script>
<script src="{{ asset('files/assets/pages/widget/amchart/light.js') }}"></script>
@endif

<!-- Scrollbar -->
<script src="{{ asset('files/assets/js/jquery.mCustomScrollbar.concat.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('files/assets/js/SmoothScroll.js') }}"></script>

<!-- Pcoded js -->
<script src="{{ asset('files/assets/js/pcoded.min.js') }}"></script>

<!-- Custom js -->
<script src="{{ asset('files/assets/js/vartical-layout.min.js') }}"></script>

@if($loadCustomDashboard)
<script type="text/javascript" src="{{ asset('files/assets/pages/dashboard/custom-dashboard.js') }}"></script>
@endif

<!-- Main Script -->
<script type="text/javascript" src="{{ asset('files/assets/js/script.min.js') }}"></script>

<!-- Additional Scripts -->
@foreach($additionalScripts as $script)
@if(is_array($script))
<script src="{{ $script['src'] }}" 
        @if(isset($script['type'])) type="{{ $script['type'] }}" @endif
        @if(isset($script['async'])) async @endif
        @if(isset($script['defer'])) defer @endif></script>
@else
<script src="{{ $script }}"></script>
@endif
@endforeach

<!-- Cloudflare Insights -->
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/vcd15cbe7772f49c399c6a5babf22c1241717689176015" 
        integrity="sha512-ZpsOmlRQV6y907TI0dKBHq9Md29nnaEIPlkf84rnaERnq6zvWvPUqr2ft8M1aS28oN72PdrCzSjY4U6VaAw1EQ==" 
        data-cf-beacon='{"version":"2024.11.0","token":"a6117f2172474edeb039d2a90d541d53","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' 
        crossorigin="anonymous"></script>

<!-- Page Specific Scripts -->
<!-- Switch component js -->
    <script type="text/javascript" src="{{ asset('files/bower_components/switchery/dist/switchery.min.js') }}"></script>
    
<!-- Bootstrap date-time-picker js -->
    <script type="text/javascript" src="{{ asset('files/assets/pages/advance-elements/moment-with-locales.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('files/bower_components/bootstrap-datepicker/js/bootstrap-datepicker.js') }}"></script>
    <script type="text/javascript" src="{{ asset('files/assets/pages/advance-elements/bootstrap-datetimepicker.min.js') }}"></script>
    <!-- Date-range picker js -->
    <script type="text/javascript" src="{{ asset('files/bower_components/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
    <!-- Date-dropper js -->
    <script type="text/javascript" src="{{ asset('files/bower_components/datedropper/datedropper.min.js') }}"></script>
    <!-- Color picker js -->
    <script type="text/javascript" src="{{ asset('files/bower_components/spectrum/spectrum.js') }}"></script>
    <script type="text/javascript" src="{{ asset('files/bower_components/jscolor/jscolor.js') }}"></script>
    <!-- Mini-color js -->
    <script type="text/javascript" src="{{ asset('files/bower_components/jquery-minicolors/jquery.minicolors.min.js') }}"></script>
    <!-- i18next.min.js -->
<script type="text/javascript" src="{{ asset('files/assets/pages/advance-elements/custom-picker.js') }}"></script>
 <!-- Tags js -->
    <script type="text/javascript" src="{{ asset('files/bower_components/bootstrap-tagsinput/dist/bootstrap-tagsinput.js') }}"></script>
    <script src="{{ asset('files/bower_components/libs/typeahead.js/0.10.4/typeahead.bundle.min.js') }}"></script>
    <!-- Max-length js -->
 <script type="text/javascript" src="{{ asset('files/bower_components/bootstrap-maxlength/src/bootstrap-maxlength.js') }}"></script>


   <!-- Select 2 js -->
        <script type="text/javascript" src="{{ asset('files/bower_components/select2/dist/js/select2.full.min.js') }}"></script>
        <!-- Multiselect js -->
        <script type="text/javascript"src="{{ asset('files/bower_components/bootstrap-multiselect/dist/js/bootstrap-multiselect.js') }}"></script>
        <script type="text/javascript" src="{{ asset('files/bower_components/multiselect/js/jquery.multi-select.js') }}"></script>
        <script type="text/javascript" src="{{ asset('files/assets/js/jquery.quicksearch.js') }}"></script>

  <script type="text/javascript" src="{{ asset('files/assets/pages/advance-elements/select2-custom.js') }}"></script>

<script >
    function getRequest(route, id, type,value) {
        const target = document.getElementById(id);
        if (!target) return;
        const previous = $(target).data('pending-request');
        if (previous) previous.abort();
        const wasDisabled = target.disabled;
        target.disabled = true;
        target.setAttribute('aria-busy', 'true');
        const request = $.get({
            url: route,
            dataType: 'json',
            success: function(data) {

                if (type == 'select') {

                    $('#' + id).empty().append(data.select_tag);
                }


            },
            error: function(xhr, status) {
                if (status === 'abort') return;
                const message = xhr.status === 401 || xhr.status === 419
                    ? 'Votre session a expiré. Reconnectez-vous pour continuer.'
                    : xhr.status === 403 ? 'Votre rôle ne permet pas cet accès.'
                    : 'Impossible de charger les options. Vérifiez votre connexion puis réessayez.';
                if (window.appNotify) window.appNotify(message);
            },
            complete: function() {
                target.disabled = wasDisabled;
                target.removeAttribute('aria-busy');
                $(target).removeData('pending-request');
            },
        });
        $(target).data('pending-request', request);
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@26.8.1/build/js/intlTelInput.min.js"></script>
<script>
    const input = document.querySelector("#telephone1");
    const input2 = document.querySelector("#telephone2");
    if (input && window.intlTelInput) window.intlTelInput(input, {
        initialCountry: "ci",
        geoIpLookup: (success, failure) => {
            fetch("https://ipapi.co/json")
                .then((res) => res.json())
                .then((data) => success(data.country_code))
                .catch(() => failure());
        }
    });
    if (input2 && window.intlTelInput) window.intlTelInput(input2, {
        initialCountry: "ci",
        geoIpLookup: (success, failure) => {
            fetch("https://ipapi.co/json")
                .then((res) => res.json())
                .then((data) => success(data.country_code))
                .catch(() => failure());
        }
    });

    const iti = input && window.intlTelInput ? window.intlTelInput.getInstance(input) : null;
    if (iti) iti.isValidNumber();
</script>
 @stack('scripts')





<!-- all plugins here -->
<script type="text/javascript"   src="{{  asset('files/assets/js_web/vendor.js') }}"></script>
<!-- main js  -->
<script   type="text/javascript"   src="{{  asset('files/assets/js_web/main.js')  }}"></script>

<script type="text/javascript" src="{{ asset('files/bower_components/jquery/dist/jquery.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@26.8.1/build/js/intlTelInput.min.js"></script>
<script >
    function getRequest(route, id, type,value) {
        $.get({
            url: route,
            dataType: 'json',
            success: function(data) {

                if (type == 'select') {

                    $('#' + id).empty().append(data.select_tag);
                }


            },
        });
    }
</script>

@stack('scripts')

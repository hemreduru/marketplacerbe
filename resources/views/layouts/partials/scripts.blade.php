<!--begin::Javascript-->
<!--begin::Global Javascript Bundle(mandatory for all pages)-->
<script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
<script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
<!--end::Global Javascript Bundle-->

<!--begin::Toastr JS (from Metronic)-->
<script src="{{ asset('metronic_html_v8.2.0_demo1/demo1/src/plugins/toastr/build/toastr.min.js') }}"></script>
<!--end::Toastr JS-->

<!--begin::Global Configuration-->
@include('layouts.partials.scripts.config')
<!--end::Global Configuration-->

<!--begin::Toastr Configuration-->
@include('layouts.partials.scripts.toastr-config')
<!--end::Toastr Configuration-->

<!--begin::Notification Helper Functions-->
@include('layouts.partials.scripts.notification-helpers')
<!--end::Notification Helper Functions-->

<!--begin::Axios Configuration & Interceptors-->
@include('layouts.partials.scripts.axios-config')
<!--end::Axios Configuration & Interceptors-->

<!--begin::Authentication Functions-->
@include('layouts.partials.scripts.auth')
<!--end::Authentication Functions-->

<!--begin::Theme Management Functions-->
@include('layouts.partials.scripts.theme')
<!--end::Theme Management Functions-->

<!--begin::Page Initialization-->
@include('layouts.partials.scripts.init')
<!--end::Page Initialization-->

<!--begin::Page Custom Javascript-->
@stack('scripts')
<!--end::Page Custom Javascript-->
<!--end::Javascript-->

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
	{{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>


	<!-- Meta data -->
	<meta name="csrf-token" content="{{ csrf_token() }}" />

	<meta charset="UTF-8">
	<meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=0'>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<!-- Favicon -->

	<!-- Title -->
	<title>{{$title}}</title>

	@include('includes.css')
<style>
.app-content .side-app {
    padding: 20px 30px 0 30px !important;
}	
</style>
</head>


@include('includes.navigation')
@yield('content')

<!--Footer-->
<footer class="footer">
	<div class="container">
		<div class="row align-items-center flex-row-reverse">
					<div class="col-lg-12 col-sm-12 mt-3 mt-lg-0 text-center">
				Copyright © {{date('Y')}} <a href="javascript:void(0)" class="fs-14 text-primary">LMS</a>.
				All rights reserved. 
			</div>
		</div>
	</div>
</footer>
<!--/Footer-->
</div>

<!-- Back to top -->
<a href="#top" id="back-to-top"><i class="fa fa-long-arrow-up"></i></a>

@include('includes.js')

</body>

</html>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Eloquent Bears</title>

	<!-- CSS -->
	<!-- BOOTSTRAP -->
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
	<style>
		body { padding-top:50px; } /* add some padding to the top of our site */
	</style>
</head>
<body class="container">
<div class="col-sm-8 col-sm-offset-2">

	<!-- BEARS -->
	<!-- loop over the bears and show off some things -->
	@foreach ($entries as $entry)

		<!-- GET OUR BASIC BEAR INFORMATION -->
		<h2>{{ $entry->entry_name }} <small>{{ $entry->user->user_display_name }}</small></h2>


	@endforeach

</div>
</body>
</html>

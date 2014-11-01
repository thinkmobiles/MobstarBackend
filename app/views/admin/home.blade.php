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
        <div class="row">
        <div class="col-md-12">
		<!-- GET OUR BASIC BEAR INFORMATION -->
		<h2>{{ $entry['entry_name'] }} <small>{{ $entry['entry_display_name']}}</small></h2>


        </div>
        </div>


        <div class="row">
            <div class="col-md-6">
                @if ($entry['entry_type'] == 'video')

                    @if ($entry['entry_file'] != '' && isset($entry['entry_file']))
                        <video style="width:100%; height:auto" controls>
                          <source src="{{$entry['entry_file']}}" type="video/mp4">
                        Your browser does not support the video tag.
                        </video>
                    @else
                        There is an error with this file, please contact support about this immediately, quote "Entry ID {{$entry['entry_id']}}"
                    @endif
                @endif



                @if ($entry['entry_type'] == 'audio')
                    @if ($entry['entry_file'] != '' && isset($entry['entry_file']))
                        @if ($entry['entry_image'] != '' && isset($entry['entry_image']))
                            <img class="img-responsive" src="{{$entry['entry_image']}}">
                        @endif

                        <audio controls>
                          <source src="{{$entry['entry_file']}}" type="audio/mpeg">
                            Your browser does not support the audio tag.
                        </audio>
                    @else
                        There is an error with this file, please contact support about this immediately, quote "Entry ID {{$entry['entry_id']}}"
                    @endif
                @endif

            </div>
            <div class="col-md-6">

            </div>
        </div>

        <hr>
	@endforeach

</div>
</body>
</html>

<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>MobStar Admin</title>
    <link href="/css/video-js.css" rel="stylesheet" type="text/css">
    <!-- video.js must be in the <head> for older IEs to work. -->
    <script src="/js/video.js"></script>
    <script src="/js/jquery-1.11.1.min.js"></script>
	<!-- CSS -->
	<!-- BOOTSTRAP -->
	<link rel="stylesheet" href="/css/bootstrap.min.css">
	<style>
		body { padding-top:50px; } /* add some padding to the top of our site */
	</style>
</head>
<body class="container">

<div class="jumbotron">
  <h1>MobStar Admin</h1>
  <p><a class="btn btn-primary btn-lg" href="/admin/entry" role="button">Add an Entrty</a></p>
</div>
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

                    @if (isset($entry['entry_file']) && $entry['entry_file'] != '')
                        <video style="width:100%; height:auto" class="video-js vjs-default-skin"
                                                                         controls preload="metadata"
                                                                         poster="{{$entry['entry_image']}}">
                          <source src="{{$entry['entry_file']}}" type="video/mp4">
                        Your browser does not support the video tag.
                        </video>
                    @else
                        There is an error with this file, please contact support about this immediately, quote "Entry ID {{$entry['entry_id']}}"
                    @endif
                @endif



                @if ($entry['entry_type'] == 'audio')
                    @if (isset($entry['entry_file']) && $entry['entry_file'] != '')
                        @if ( isset($entry['entry_image']) && $entry['entry_image'] != '')
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

                 @if ($entry['entry_type'] == 'image')
                    @if ( isset($entry['entry_image']) && $entry['entry_image'] != '')
                        @if ( isset($entry['entry_image']) && $entry['entry_image'] != '')
                            <img class="img-responsive" src="{{$entry['entry_image']}}">
                        @endif
                    @else
                        There is an error with this file, please contact support about this immediately, quote "Entry ID {{$entry['entry_id']}}"
                    @endif
                @endif
            </div>
            <div class="col-md-6">
                @if($entry['entry_deleted'] == 0)
                    <a class="disable btn btn-warning toggle" id="{{$entry['entry_id']}}">Disable Entry</a>
                @else
                    <a class="restore btn btn-success toggle" id="{{$entry['entry_id']}}">Enable Entry</a>
                @endif
            </div>
        </div>

        <hr>
	@endforeach


<script>
$('.toggle').click(function(){
var id = $(this).attr('id');

if($(this).hasClass("disable"))
{

    $.ajax({
        url: '/entry/'+id,
        type: 'DELETE'
    }
    ).done(function(){
    $('a#'+id+'.disable').removeClass('disable btn-warning').addClass('restore btn-success').text("Enable Entry");
    });
}
else if($(this).hasClass("restore"))
{

$.ajax({
    url: '/restoreentry/'+id,
    type: 'GET'
}
).done(function(){
$('a#'+id+'.restore').removeClass('restore btn-success').addClass('disable btn-warning').text("Disable Entry");
});


}
});

</script>
</div>
</body>
</html>

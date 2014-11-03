<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>MobStar Admin | Add Entry</title>
    <link href="/css/video-js.css" rel="stylesheet" type="text/css">
    <!-- video.js must be in the <head> for older IEs to work. -->
    <script src="/js/video.js"></script>
    <script src="/js/jquery-1.11.1.min.js"></script>
	<!-- CSS -->
	<!-- BOOTSTRAP -->
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
	<style>
		body { padding-top:50px; } /* add some padding to the top of our site */
	</style>
</head>
<body class="container">

<form method="post" action="/admin/entry" enctype="multipart/form-data" class="form-horizontal" role="form">

  <div class="form-group">
    <label for="description" class="col-sm-2 control-label">Name</label>
    <div class="col-sm-10">
      <input type="text" class="form-control" id="description" placeholder="Entry Display Name" name="description" required="true">
    </div>
  </div>
  <div class="form-group">
    <label for="type" class="col-sm-2 control-label">Type</label>
    <div class="col-sm-10">
      <select class="form-control" id="type" name="type" required="true">
        <option value="video">Video</option>
        <option value="audio">Audio</option>
        <option value="image">Image</option>
      </select>
    </div>
  </div>
  <div class="form-group">
    <label for="tags" class="col-sm-2 control-label">Tags</label>
    <div class="col-sm-10">
      <input type="text" class="form-control" id="tags" placeholder="Comma Seperated List" name="tags" required="true">
    </div>
  </div>

  <div class="form-group">
        <label for="file1" class="col-sm-2 control-label">File 1</label>
        <div class="col-sm-10">
          <input type="file" class="form-control" id="file1" placeholder="File 1" name="file1" required="true">
        </div>
      </div>

    <div class="col-sm-offset-2 col-sm-10">
    Second file only required as an image for audio entries
    </div>
    <div class="form-group">
          <label for="file2" class="col-sm-2 control-label">File 2</label>
          <div class="col-sm-10">
            <input type="file" class="form-control" id="file2" placeholder="Only for Audio" name="file2">
          </div>
        </div>


  <input type="hidden" value="0" name="enabled">

  <div class="form-group">
    <div class="col-sm-offset-2 col-sm-10">
      <div class="checkbox">
        <label>
          <input type="checkbox" value="1" name="enabled">Enabled
        </label>
      </div>
    </div>
  </div>

  <div class="form-group">
    <div class="col-sm-offset-2 col-sm-10">
      <button type="submit" class="btn btn-default">Add</button>
    </div>
  </div>
</form>



</div>
</body>
</html>

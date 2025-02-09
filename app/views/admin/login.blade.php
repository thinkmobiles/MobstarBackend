<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Please Login</title>

	<link rel="stylesheet" href="/css/bootstrap.min.css">

	<style>
		@import url(//fonts.googleapis.com/css?family=Lato:700);

		body {
			margin:0;
			font-family:'Lato', sans-serif;
			text-align:center;
			color: #999;
		}

		.welcome {
			width: 300px;
			height: 200px;
			position: absolute;
			left: 50%;
			top: 50%;
			margin-left: -150px;
			margin-top: -100px;
		}

		a, a:visited {
			text-decoration:none;
		}

		h1 {
			font-size: 32px;
			margin: 16px 0 0 0;
		}
		.container{
		margin-top:100px;
		}
	</style>
</head>
<body>
<div class="container">
	<form method="post" action="/admin/validate">

		<div class="row">
		    <div class="col-md-offset-3 col-md-3">
		        Email:
		    </div>

		    <div class="col-md-3">
		        <input name="email" type="email" class="form-control" >
		    </div>

		</div>

		<div class="row">
            <div class="col-md-offset-3 col-md-3">
                Password:
            </div>

            <div class="col-md-3">
                <input name="password" type="password" class="form-control">
            </div>
        </div>

        <div class="col-md-offset-3 col-md-6">
        <center>
          <button type="submit" class="btn btn-default">Submit</button>
        </center>
        </div>
    </form>
	</div>

</body>
</html>
</html>

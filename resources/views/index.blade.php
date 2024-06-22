<!DOCTYPE html>
<html lang="en">
<head>
      <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <link rel="icon" href="assets/img/MSP5.png" type="image/x-icon">
        <title>INVENTORY MSP</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.1.0/js/all.js" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css"
            integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.css" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Noto+Sans&display=swap" rel="stylesheet">
        <style> @import url('https://fonts.googleapis.com/css2?family=Noto+Sans&display=swap'); </style>

        <style>
          .gradient-custom {
              /* fallback for old browsers */
              background: #6a11cb;

              /* Chrome 10-25, Safari 5.1-6 */
              background: -webkit-linear-gradient(to right, rgba(106, 17, 203, 1), rgba(37, 117, 252, 1));

              /* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */
              background: linear-gradient(to right, rgba(106, 17, 203, 1), rgba(37, 117, 252, 1))
              }


        </style>
</head>


<body>


<section class="vh-100 gradient-custom">
  <div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
      <div class="col-12 col-md-8 col-lg-6 col-xl-5">
        <div class="card bg-dark text-white" style="border-radius: 1rem;">
          <div class="card-body p-5 text-center">
            
			<form method="POST" action="{{ route('login.post') }}" enctype="multipart/form-data">
				@csrf
				<div class="mb-md-5 mt-md-4 pb-5">
					<h2 class="fw-bold mb-2 text-uppercase">INVENTORY MSP</h2>
					<p class="text-white-50 mb-5">Please enter your login and password!</p>

					<div class="form-outline form-white mb-4">
						<input type="text" id="inputText" name="username" class="form-control form-control-lg" />
						<label class="form-label" for="typeEmailX">Username</label>
					</div>

					<div class="form-outline form-white mb-4">
						<input type="password" id="inputPassword" name="password" class="form-control form-control-lg" />
						<label class="form-label" for="typePasswordX">Password</label>
						
            @if (session('error'))
              <div class="alert alert-danger">
                {{ session('error') }}
              </div>
              @endif 
					</div>
					<button type ="submit" class="btn btn-outline-light btn-lg px-5">Login</button>
				</div>
      </form>
      
    </div>
  </div>
</div>
</div>
</div>
</section>
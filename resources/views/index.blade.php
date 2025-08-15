<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>INVENTORY MSP</title>
    <link rel="icon" href="assets/img/MSP5.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <style>
        body {
            background: linear-gradient(90deg, #C7C5F4, #776BCC);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-family: 'Raleway', sans-serif;
        }

        .container {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .screen {
            background: linear-gradient(90deg, #5D54A4, #7C78B8);
            position: relative;
            height: 600px;
            width: 360px;
            box-shadow: 0px 0px 24px #5C5696;
            overflow: hidden;
        }

        .screen__background {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }

        .screen__background__shape {
            transform: rotate(45deg);
            position: absolute;
        }

        .screen__background__shape1 {
            height: 520px;
            width: 520px;
            background: #FFF;
            top: -50px;
            right: 120px;
            border-radius: 0 72px 0 0;
        }

        .screen__background__shape2 {
            height: 220px;
            width: 220px;
            background: #6C63AC;
            top: -172px;
            right: 0;
            border-radius: 32px;
        }

        .screen__background__shape3 {
            height: 540px;
            width: 190px;
            background: linear-gradient(270deg, #5D54A4, #6A679E);
            top: -24px;
            right: 0;
            border-radius: 32px;
        }

        .screen__background__shape4 {
            height: 400px;
            width: 200px;
            background: #7E7BB9;
            top: 420px;
            right: 50px;
            border-radius: 60px;
        }

        .screen__content {
            z-index: 1;
            position: relative;
            height: 100%;
            padding: 150px 30px 0;
        }

        /* Form */
        .login__field {
            position: relative;
            margin-bottom: 25px;
        }

        .login__icon {
            position: absolute;
            top: 16px;
            left: 0;
            color: #7875B5;
            font-size: 18px;
        }

        .login__input {
            border: none;
            border-bottom: 2px solid #D1D1D4;
            background: none;
            padding: 14px 10px 14px 28px;
            font-size: 16px;
            width: 100%;
            color: #333;
        }

        .login__input:focus {
            outline: none;
            border-bottom-color: #6A679E;
        }

        /* Floating Label */
        .login__label {
            position: absolute;
            top: 16px;
            left: 28px;
            font-size: 14px;
            color: #aaa;
            pointer-events: none;
            transition: 0.3s ease;
        }

        .login__input:focus+.login__label,
        .login__input:not(:placeholder-shown)+.login__label {
            top: -10px;
            font-size: 12px;
            color: #6A679E;
        }

        /* Button */
        .login__submit {
            background: #fff;
            padding: 16px;
            border-radius: 26px;
            border: 1px solid #D4D3E8;
            text-transform: uppercase;
            font-weight: bold;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            color: #4C489D;
            cursor: pointer;
            transition: .2s;
        }

        .login__submit:hover {
            background: #f0f0f0;
        }

        .button__icon {
            font-size: 20px;
            margin-left: auto;
            color: #7875B5;
        }

        /* Error alert */
        .alert {
            background: #fff;
            color: #e50914;
            padding: 8px;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="screen">
            <div class="screen__content">
                <form class="login" method="POST" action="{{ route('login.post') }}">
                    @csrf


                    <div class="login__field">
                        <i class="login__icon fas fa-user"></i>
                        <input type="text" name="username" class="login__input" placeholder=" "
                            value="{{ old('username') }}" autocomplete="off">
                        <label class="login__label">Username</label>
                    </div>

                    <div class="login__field">
                        <i class="login__icon fas fa-lock"></i>
                        <input type="password" name="password" class="login__input" placeholder=" ">
                        <label class="login__label">Password</label>
                    </div>

                    @if (session('error'))
                        <div class="alert">{{ session('error') }}</div>
                    @endif
                    <button class="login__submit">
                        Log In Now
                        <i class="button__icon fas fa-chevron-right"></i>
                    </button>
                </form>
            </div>

            <div class="screen__background">
                <span class="screen__background__shape screen__background__shape4"></span>
                <span class="screen__background__shape screen__background__shape3"></span>
                <span class="screen__background__shape screen__background__shape2"></span>
                <span class="screen__background__shape screen__background__shape1"></span>
            </div>
        </div>
    </div>
</body>

</html>

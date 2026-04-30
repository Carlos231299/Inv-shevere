<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #27AE60; 
            --primary-dark: #1E8449;
            --text-color: #f8fafc;
        }
        body {
            background-image: url('{{ asset("images/login-bg.png") }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            font-family: 'Outfit', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: var(--text-color);
        }
        /* Gradient Overlay */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(10, 26, 19, 0.9), rgba(0, 0, 0, 0.8));
            z-index: -1;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            width: 90%;
            max-width: 420px;
            text-align: center;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .logo-container {
            margin-bottom: 30px;
        }
        .logo-container img {
            max-height: 120px;
            filter: drop-shadow(0 0 15px rgba(39, 174, 96, 0.3));
        }

        h1 { 
            color: #fff; 
            margin-bottom: 5px; 
            font-weight: 700;
            letter-spacing: -1px;
        }
        
        p.subtitle {
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 35px;
            font-size: 0.95rem;
        }

        .form-group { 
            margin-bottom: 25px; 
            text-align: left; 
        }
        label { 
            display: block; 
            margin-bottom: 10px; 
            color: rgba(255, 255, 255, 0.8); 
            font-weight: 500;
            font-size: 0.9rem;
        }
        input {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            box-sizing: border-box;
            font-size: 1rem;
            transition: all 0.3s;
        }
        input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 4px rgba(39, 174, 96, 0.2);
        }

        /* Password Toggle */
        .password-wrapper {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255, 255, 255, 0.4);
            background: none;
            border: none;
            padding: 0;
            font-size: 1.2rem;
        }
        .toggle-password:hover {
            color: var(--primary-color);
        }

        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 15px;
            box-shadow: 0 10px 20px rgba(39, 174, 96, 0.2);
        }
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 15px 25px rgba(39, 174, 96, 0.3);
        }
        button:active {
            transform: scale(0.98);
        }

        .error { 
            color: #ff4d4d; 
            font-size: 0.85em; 
            margin-top: 8px; 
            display: flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo-container">
        <img src="{{ asset('images/logo.svg') }}" alt="{{ config('app.name') }} Logo">
    </div>
    <h1>Bienvenido</h1>
    <p class="subtitle">Accede al panel de control de Shevere</p>


    <form action="{{ route('login') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" required autofocus value="{{ old('email') }}" placeholder="ejemplo@correo.com" autocomplete="off">
            @error('email')
                <div class="error">⚠️ {{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">Contraseña</label>
            <div class="password-wrapper">
                <input type="password" id="password" name="password" required placeholder="••••••••" autocomplete="off">
                <span class="toggle-password" onclick="togglePassword()">👁️</span>
            </div>
        </div>

        <button type="submit">Ingresar al Sistema</button>
    </form>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.querySelector('.toggle-password');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.textContent = '🙈'; // Monkey covering eyes or slashed eye
        } else {
            passwordInput.type = 'password';
            toggleIcon.textContent = '👁️';
        }
    }
</script>

</body>
</html>


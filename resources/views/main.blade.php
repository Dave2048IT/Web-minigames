<?php
    @session_start();
    // Um neue Sessions zu testen
    // session_destroy();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8" http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hauptseite der Web-Minispiele</title>
    <link rel="stylesheet" href="../resources/css/app.css" />
</head>
<body>
    <h1>Willkommen auf meiner Minispiele-Website</h1>
    @guest
    <header>
        <h2>Die Spiele sind auch als Gast spielbar,
        <br>aber dann kannst du keine Highscores hochladen.</h2>
        <h3>Das Anmelden funktioniert nur mit Cookies 🍪😉🥠</h3>
        <div class="loginarea">
            <h2>Login</h2>
            <form method="POST" action="{{route('login')}}">
                @csrf
                @if ($with_login = isset($_SESSION["with_login"]) && $_SESSION["with_login"] == true && $errors->any())
                    <div>
                        <ul class="text-red-500">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <table>
                    <tr>
                        <td>Username</td>
                        <td><input name="login-name" value="{{ old('login-name') }}"/></td>
                    </tr>
                    <tr>
                        <td>Oder E-Mail</td>
                        <td><input name="login-email" type="email" value="{{ old('login-email') }}"/></td>
                    </tr>
                    <tr>
                        <td>Passwort *</td>
                        <td><input name="login-password" type="password"/></td>
                    </tr>
                </table>
                <!-- <button onclick="loginUser()">Login</button> -->
                <button type="submit" class="btn_link">Login</button>
            </form>
        </div>
        <div class="loginarea">
            <h2>Oder Registrieren</h2>
            <form method="POST" action="register">
                @csrf
                @if (!$with_login && $errors->any())
                    <div class="alert alert-danger">
                        <ul class="text-red-500">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <table>
                    <tr>
                        <td>Username *</td>
                        <td><input name="name" value="{{ old('name') }}"/></td>
                    </tr>
                    <tr>
                        <td>E-Mail *</td>
                        <td><input name="email" type="email" value="{{ old('email') }}"/></td>
                    </tr>
                    <tr>
                        <td>Passwort *</td>
                        <td><input name="password" type="password"/></td>
                    </tr>
                    <tr>
                        <td>Bestätige Passwort *</td>
                        <td><input name="confirmation" type="password"/></td>
                    </tr>
                </table>
                <button type="submit" class="btn_link">Register</button>
            </form>
        </div>
    </header>
    @else
        <div class="medium_text">{{ session("success_message") ?: "Eingeloggt als" }} 
            '<span id="username">{{ auth()->user()->name }}</span>'
            @if (session()->has("with_name"))
            
                durch {{ session("with_name") ? "Usernamen" : "E-Mail" }}
            @endif
            <p>Dein Freundescode: 
                <span id="fc">{{ auth()->user()->friend_code }}</span>
            </p>
        </div>
        @if (empty($fs_flag))
            <button><a class="btn_link" href="{{ route('friend_list') }}">Freundesliste</a></button>
        @else
            <article id="friend_list">
                <div>
                <h2>Freunde: </h2>
                @foreach ($sended_fs as $f1 => $friend)
                    @if ($friend->accepted)
                        <li>{{ $friend->name }}</li>
                    @else
                        @break
                    @endif
                @endforeach
                ---
                @foreach ($received_fs as $f2 => $friend)
                    @if ($friend->accepted)
                        <li>{{ $friend->name }}</li>
                    @else
                        @break
                    @endif
                @endforeach
                </div><br><br><div>

                @if (isset($f1))
                    <h2>Gesendet: </h2>
                    @for ($i = $f1 + $friend->accepted; $i < $count1; $i++)
                        <li>{{ $sended_fs[$i]->name }}</li>
                    @endfor
                @else
                    (Nichts)
                @endif
                </div><br><br><div>
    
                @if (isset($f2))
                    <h2>Empfangen: </h2>
                    <p>Muss noch angepasst werden!
                    <br>Bei Ja: "accepted" auf 1
                    <br>Bei Nein: Anfrage löschen</p>
                    <br>
                    @for ($i = $f2 + $friend->accepted; $i < $count2; $i++)
                        <li>{{ $received_fs[$i]->name }}
                            <button>✓</button>
                            <button>✘</button>
                        </li>
                    @endfor
                @else
                    (Nichts)
                @endif
                </div><br><br>
            </article>
            
            @if (session()->has("fc_message"))
                <h2>{{ session("fc_message")}}</h2>
            @endif
            <form id="fc_field" method="POST" action="request_friend">
                @csrf
                  <input required name="fc" value="{{ old('fc') }}" type="text" placeholder="0987-6543-2100"/>
                <button type="submit" class="btn_link">Anfragen</button>
            </form>
            <p></p>
            <button><a class="btn_link" href="{{ route('main') }}">Zurück</a></button>
        @endif
        <p></p>
        <button id="logout_btn"><a class="btn_link" href="{{ route('logout') }}">Ausloggen</a></button>
    @endguest
    <hr>
    <div id="gamelist">
        <h1>Spieleliste</h1>
        <a href="remember_numbers.php">Remember the numbers</a>
    </div>
</body>
</html>
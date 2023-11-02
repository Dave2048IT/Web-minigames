<?php

namespace App\Http\Controllers;

@session_start();
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function logout()
    {
        session_destroy();
        auth()->logout();
        return redirect("/");
    }
    
    public function test_auth()
    {
        if (auth()->guest())
            return false;
        return true;
    }

    public function suspend_user()
    {
        if (null == $user = auth()->user())
            return $this->logout();
        
        // Banne den User für 10 Minuten, bevor er sich wieder einloggen kann
        User::query()->where("id", "=", $user->id)
            ->update(
                ["banned_until" => now()->addRealHour()]
            );
        return $this->logout();
    }

    public function loginUser() {
        $_SESSION["with_login"] = true;
        
        $old = request()->validate([
            'login-name'  => ["max:254", "prohibited_unless:login-email,null"],
            'login-email' => ["max:254", "required_without:login-name"],
            'login-password' => ["required", "min:8"],
        ],
        [ 'login-name.prohibited_unless' => 'Bitte geben Sie entweder Ihren Namen oder Ihre E-Mail-Adresse ein, aber nicht beides.']);
        // Wenn irgendwas bis hierhin ungültig ist, breche automatisch ab.
        
        $attr = array_combine(array('name', 'email', 'password'), $old);
        // Wenn kein Name übergeben wurde, lösche ihn aus der SQL-Anfrage
        if (!$attr["name"])
            unset($attr["name"]);
        
        // Sonst, wenn keine Email übergeben wurde
        // ODER der Name da ist, lösche die Email aus der SQL-Anfrage
        else if (!$attr["email"] || isset($attr["name"]))
            unset($attr["email"]);
        // $attr = array_values($attr);
        
        // Versuche, den Nutzen einzuloggen
        if (! auth()->attempt($attr, true)) {
            throw ValidationException::withMessages([
                'email' => "Ihre Anmeldedaten sind falsch, bitte versuchen Sie es erneut"
            ]);
        }

        // Um Session-Fixation zu verhindern
        session()->regenerate();

        $user = auth()->user();
        if (strtotime(now()) < strtotime($user->banned_until)) {
            auth()->logout();
            return redirect("/")
                ->withErrors(["Hey, es ist nicht erlaubt, zu cheaten. Es wird nicht nur anderen Spielern den Spaß verderben, einen unfairen Mitspieler zu haben, ".
                    "sondern auch dir selbst, da du sonst deinen ganzen Stolz wegwerfen würdest. ".
                    "So, spiele doch beim nächsten Mal bitte vernünftig. ;-) ",
                    "Somit musst du noch warten bis: $user->banned_until"]);
        }
        
        return redirect("/")
            ->with("success_message", "Willkommen zurück, ")
            ->with("with_name", isset($attr["name"]));
    }

    /**
     * Attempt to register a new User and check password
     */
    public function registerUser() {
        // dd(request()->all());
        $_SESSION["with_login"] = false;

        $attr = request()->validate([
            'name' =>  ["required", "min:3", "max:254", Rule::unique("users", "name")],
            'email' => ["required", "min:5", "max:254", Rule::unique("users", "email"), "email"],
            'password' => ["required", "same:confirmation",
                Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);
        // Wenn irgendwas bis hierhin ungültig ist, breche automatisch ab.

        $attr["password"] = bcrypt($attr["password"]);
        
        $fc = (string) random_int(10 ** 13, 10 ** 14 - 1);
        $fc[4] = $fc[9] = "-";
        $attr["friend_code"] = $fc;
        
        // Erstelle den Nutzer mit Daten von $attr[]
        $user = User::create($attr);
        auth()->login($user, true);

        return redirect("/")
            ->with("success_message", "Herzlich willkommen, ");
    }
    
    public function show_friends()
    {
        if (null == $user = auth()->user())
            return "Du bist ein Gast.";
        
        // Joine zuerst alle empfangenen Anfragen
        $received_fs = DB::table("friendships as fs")
            ->join("users", "users.id", "=", "fs.userid")
            ->where("friendid", "=", $user->id)
            ->orderByDesc("accepted")
            ->get(["name", "accepted"]);
        
        // Dann lade alle gesendeten Anfragen
        $sended_fs = DB::table("friendships as fs")
            ->join("users", "users.id", "=", "fs.friendid")
            ->where("userid", "=", $user->id)
            ->orderByDesc("accepted")
            ->get(["name", "accepted"]);

        $fs_flag = true;
        $count1 = $sended_fs->count();
        $count2 = $received_fs->count();
        
        return view('main', compact('fs_flag', 'sended_fs', 'received_fs', 'count1', 'count2'));
    }

    public function request_friend(Request $request)
    {
        $yourself = auth()->user();
        $own_id = $yourself->id;
        $fc = $request->fc;
        // $fc = "$request->fs1-$request->fs2-$request->fs3";
        
        if ($fc == $yourself->friend_code) {
            return redirect()->back()
                ->with("fc_message", "Du kannst dich nicht selber hinzufügen.");
        }

        $found_user = DB::table("users")
            ->where("friend_code", "=", $fc)
            ->get(["id", "name", "friend_code"]);
        
        // Wenn nicht gefunden
        if (is_null($found_user->first())) {
            return redirect()->back()
                ->with("fc_message", "Diesen Code gibt es nicht.");
        }

        $received_friend = DB::table("friendships as fs")
            ->join("users", "users.id", "=", "fs.friendid")
            ->where("userid", "=", $found_user[0]->id)
            ->where("friendid", "=", $own_id)
            ->get("accepted");
        
        $sent_friend = DB::table("friendships as fs")
            ->join("users", "users.id", "=", "fs.userid")
            ->where("friendid", "=", $found_user[0]->id)
            ->where("userid", "=", $own_id)
            ->get("accepted");

        $is_sent = !is_null($sent_friend->first());
        $is_received = !is_null($received_friend->first());
        
        // if ($sent_friend || $received_friend) {
        if ($is_sent     && $sent_friend[0]->accepted ||
            $is_received && $received_friend[0]->accepted) {
            return redirect()->back()
                ->with("fc_message", "Du bist bereits mit '".$found_user[0]->name."' befreundet.");
        }
        else if ($is_sent && $sent_friend[0]->accepted == 0) {
            return redirect()->back()
                ->with("fc_message", "Du hast '".$found_user[0]->name."' bereits eine Anfrage geschickt.");
        }
        else if ($is_received && $received_friend[0]->accepted == 0) {
            return redirect()->back()
                ->with("fc_message", "'".$found_user[0]->name."' hatte dir vorhin eine Anfrage geschickt. Sieh mal nach. ;-)");
        }

        $new_request = DB::table("friendships")
            ->insert([
                "userid" => $yourself->id,
                "friendid" => $found_user[0]->id
                //"accepted" => 0]);
            ]);
        
        return redirect()->back()
            ->with("msg_color", "green")
            ->with("fc_message", "Die Anfrage an '".$found_user[0]->name."' wurde erfolgreich geschickt. :-)");
    }
}

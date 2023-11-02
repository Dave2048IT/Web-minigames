<?php
namespace App\Http\Controllers;
@session_start();
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HighscoreController extends Controller
{
    function array2csv(array &$array)
	{
		if (count($array) == 0) {
			return null;
		}
		ob_start();
		$df = fopen("php://output", 'w');
		fputcsv($df, array_keys(reset($array)));
		foreach ($array as $row) {
			fputcsv($df, $row);
		}
		fclose($df);
		return ob_get_clean();
	}

	function download_send_headers($filename) {
		// disable caching
		$now = gmdate("D, d M Y H:i:s");
		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
		header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
		header("Last-Modified: {$now} GMT");

		// force download  
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");

		// disposition / encoding on response body
		header("Content-Disposition: attachment;filename={$filename}");
		header("Content-Transfer-Encoding: binary");
	}

	function download_list_as_csv($data)
	{
		// $this->download_send_headers("highscore_export_" . date("Y-m-d") . ".csv");
		$array = json_decode(json_encode($data->all()), true);
		echo $this->array2csv($array);
		// die();
	}

    public function check_valid_scores(Request $request)
    {
        // Nur am Anfang eines Spiels:
        // -> Setze die Scores im Server in eine Session oder in einem Token auf 0 (oder was am Anfang so ist)

        // Vor einem Level-Up
        // und beim Game Over:
        // -> Prüfe, ob der Server die gleichen Scores hat, wie der Client.
        // Wenn ja, alles okay und ersetze die Server-Scores durch die Client-Scores
        // Wenn nein, lade die Seite neu

        return response()->json([
            'status' => 200 // oder 422
        ]);
    }

    public function find_user_position(Request $request)
    {
        if (is_null($user = auth()->user()))
            return view('remember_numbers');
        
        /* SELECT * FROM (
                SELECT *, ROW_NUMBER() OVER(
                    ORDER BY levels DESC, points DESC, time_played ASC
                ) AS pos
                FROM highscores_remember_numbers
            ) q
            WHERE userid = auth()->user()
            LIMIT 1
        */
        // Wenn von den einzelnen Scores gelesen werden soll:
        $pos = DB::table(DB::raw('(SELECT userid, button_count, is_fuzzy, ROW_NUMBER() OVER(ORDER BY levels DESC, points DESC, time_played ASC)
            AS pos FROM highscores_remember_numbers) q'))
            ->when($request->game_mode, fn ($q, $is_fuzzy) => $q->where('is_fuzzy', '=', $is_fuzzy - 1))
            ->when($request->button_count, fn ($q, $button_count) => $q->where('button_count', '=', $button_count | 0))
            ->where("userid", "=", $user->id)->first();

        if (!$pos)
            return $this->load_remember_numbers($request);
        else
            return $this->load_remember_numbers($request, $pos->pos);
    }

    public function find_user_position_accumulated(Request $request)
    {
        if (is_null($user = auth()->user()))
            return view('remember_numbers');
        
        $pos = DB::table(DB::raw('(SELECT userid, button_count, is_fuzzy, ROW_NUMBER() OVER(ORDER BY accumulated_levels DESC, accumulated_points DESC, accumulated_time ASC)
                AS pos FROM highscores_remember_numbers) q'))
                ->when($request->game_mode, fn ($q, $is_fuzzy) => $q->where('is_fuzzy', '=', $is_fuzzy - 1))
                ->when($request->button_count, fn ($q, $button_count) => $q->where('button_count', '=', $button_count | 0))
                ->where("userid", "=", $user->id)->first();
    
            if (!$pos)
                return $this->load_accumulated_remember_numbers($request);
            else
                return $this->load_accumulated_remember_numbers($request, $pos->pos);
    }
    
    public function load_accumulated_remember_numbers(Request $request, $rank = 0) 
    {
        if ($request->per_page == 0)
            $request->per_page = 50;
        
        if ($request->flag_download)
            $request->per_page = 1000;
        
        $accumulated = true;
        $data = DB::table('highscores_remember_numbers as hs')
            ->select(["hs.*", "users.name"])
            ->join("users", "users.id", "=", "hs.userid")
            ->when($request->game_mode, fn ($q, $is_fuzzy) => $q->where('is_fuzzy', '=', $is_fuzzy - 1))
            ->when($request->button_count, fn ($q, $button_count) => $q->where('button_count', '=', $button_count | 0))
            ->orderByDesc("accumulated_levels")
            ->orderByDesc("accumulated_points")
            ->orderBy("accumulated_time")
            ->paginate($request->per_page ?? 50)
            ->withQueryString();

        if ($request->flag_download)
            return $this->download_list_as_csv($data);

        return view('remember_numbers', compact('data', 'rank', 'accumulated'))->render();
    }
    
    public function load_remember_numbers(Request $request, $rank = 0)
    {
        if ($request->per_page == 0)
            $request->per_page = 50;
        
        if ($request->flag_download)
            $request->per_page = 1000;
        
        $data = DB::table('highscores_remember_numbers as hs')
            ->select(["hs.*", "users.name"])
            ->join("users", "users.id", "=", "hs.userid")
            ->when($request->game_mode, fn ($q, $is_fuzzy) => $q->where('is_fuzzy', '=', $is_fuzzy - 1))
            ->when($request->button_count, fn ($q, $button_count) => $q->where('button_count', '=', $button_count | 0))
            ->orderByDesc("levels")
            ->orderByDesc("points")
            ->orderBy("time_played")
            ->paginate($request->per_page ?? 50)
            ->withQueryString();

        if ($request->flag_download)
            return $this->download_list_as_csv($data);

        return view('remember_numbers', compact('data', 'rank'))->render();
    }
    
    /**
     * Upload the highscore only when the user has improved but update the accumulated values always
     * @param Request $request The users current game score
     * @var DB\Builder $result Gets the users highest level from the database or NULL if N/A
     * @return JsonResponse with message
     */
    
    public function save_remember_numbers(Request $request)
    {
        if (null == $user = auth()->user()) {
            return response()->json([
                'status' => 200,
                'message' => 'Ach ne, Gäste können keine Scores hochladen. ;-)',
            ]);
        }

        $my_attr = [
            'userid' => $user->id,
            'is_fuzzy' => $request->is_fuzzy,
            'button_count' => $request->button_count
        ];
        $pre_result = DB::table('highscores_remember_numbers')->where($my_attr);
        $result = $pre_result->first();
        $flag_better_now = is_null($result) || $result->levels < $request->levels;
        // $result && $result->levels >= $request->levels;

        $arr2 = [];
        $arr1 = [
            'accumulated_levels' => $request->levels + (!is_null($result) ? $result->accumulated_levels : 0),
            'accumulated_points' => $request->points + (!is_null($result) ? $result->accumulated_points : 0),
            'accumulated_time'   => $request->time_played + (!is_null($result) ? $result->accumulated_time : 0),
        ];
        if ($flag_better_now) {
            $arr2 = [
                'levels' => $request->levels,
                'points' => $request->points,
                'time_played' => $request->time_played,
                'time_for_highest_level' => $request->time_for_highest_level,
            ];
        }
        $my_records = array_merge($arr1, $arr2);
        
        // Ist noch kein Rekord enthalten, dann einfügen
        if (is_null($result))
            DB::table('highscores_remember_numbers')->insert(array_merge($my_attr, $my_records));
        // Sonst updaten
        else {
            $pre_result->update($my_records);
        }

        // Sind die geposteten Punkte kleiner oder gleich von der Datenbank, Dann gebe hier die Nachricht aus
        if (!$flag_better_now){
            return response()->json([
                'status' => 200,
                'message' => 'Oh, du warst schon mal besser mit '.$result->levels.' Stufen,'.
                "\naber deine Statistik wird trotzdem geupdatet",
            ]);
        }
        
        return response()->json([
            'status' => 200,
            'message' => 'Erfolgreich hochgeladen, \''.$user->name.'\'',
        ]);
    }
}

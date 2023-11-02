<?php
	// $tsep = localeconv()['thousands_sep'];
	@session_start();

	function Time_String($current_time = 0, $flag_zero_to_minus = true) {
		if ($current_time == 0 && $flag_zero_to_minus)
			return "--'--.---";
		
		$minus = false;
		if ($current_time < 0) {
			$current_time = -$current_time;
			$minus = true;
		}
		
		return ($minus ? "- " : "") . ($current_time >= 3600000 ? ($current_time / 3600000 | 0) . "h " : "") .
		($current_time / 6e4 % 60 | 0) ."'".
		sprintf('%02d', $current_time / 1e3 % 60 | 0) .".".
		sprintf('%03d', $current_time % 1e3 | 0);
	}
?>
<!doctype html>
<html lang="de-DE">
	<head>
		<meta charset="utf-8" name="csrf-token" content="{{ csrf_token() }}">
		<title>Remember the Numbers - The Game</title>
		<link rel="stylesheet" href="/web-minigames/resources/css/remember_numbers.css" />
	</head>
	<body>
		
		<a href="{{ url('.') }}">Zurück zur Hauptseite</a>
		<p id="player_name">
			@guest
				Als Gast spielen
			@else
				Viel Spaß, '{{ auth()->user()->name }}'
			@endguest
		</p>
		
		@if(empty($data))
		<br>
		<button id="start_btn" type="button" onclick="new_game()">START</button>
		<a href="{{ url('remember_numbers.php/load_highscores?page=1') }}">
			<button id="highscore_btn" type="button" onclick="">Highscores</button>
		</a>
		<p></p>
		<p id="info_text">Viel Glück ;-)</p>
		<hr>
		<div id="btnsDiv"></div>

		<main id="game_info">
			<p>Aktuelles Level: <span id="levels">0</span></p>
			<p>Punktestand: <span id="points">0</span> <span id="bonus"></span></p>
			<p>Gesamtzeit: <span id="watch_1">-'--.---</span></p>
			<p>Aktuelle Runde: <span id="watch_2">-'--.---</span></p>
			<p>Normaler Modus? <input id="not_fuzzy" type="checkbox" checked></input></p>
			<p>Wie viele Buttons? <input id="button_count" type="number" min="2" max="255" value="5"></input></p>
			<p>Pausen für Farbwechsel: <input id="pause_ms" type="number" min="0" max="999" value="250"></input> ms</p>
		</main>
		<footer id="level_history">
			<b>Rundenverlauf</b><br>
		</footer>
		
		<script src="../resources/js/Stopwatch.js"></script>
		<script src="../resources/js/jquery-3.6.1.min.js"></script>
		<script src="../resources/js/tone.js"></script>
		<script src="../resources/js/remember_numbers.js"></script>
		
		@else
		<script src="../../resources/js/jquery-3.6.1.min.js"></script>
		<script src="../../resources/js/download_files.js"></script>
		
		<a href="{{ url('remember_numbers.php') }}">
			<button id="back_btn" type="button">Zum Spiel</button>
		</a><br>
		<button id="csv_btn">Als CSV speichern</button><br>
		@if (isset($accumulated))
		<a href="{{ url('remember_numbers.php/load_highscores') }}">
			<button id="highscore_btn" type="button">Single Scores</button>
		</a><br>
		<a href="{{ url('remember_numbers.php/find_rank/load_highscores_accumulated') }}">
			<button type="button">Deinen Gesamt-Rang finden</button>
		</a>

		<form method="GET" action="{{ url('remember_numbers.php/load_highscores_accumulated') }}">
		@else
		<a href="{{ url('remember_numbers.php/load_highscores_accumulated') }}">
			<button id="highscore_btn" type="button">Gesammelte Scores</button>
		</a><br>
		<a href="{{ url('remember_numbers.php/find_rank/load_highscores') }}">
			<button type="button">Deinen Einzel-Rang finden</button>
		</a>
		<form method="GET" action="{{ url('remember_numbers.php/load_highscores') }}">
		@endif
			<p>Wenn irgendwo 0 (Null) drin steht,<br>dann wird die Eigenschaft nicht gefiltert.</p>
			<input type="hidden" name="page" value="{{ $_GET['page'] ?? 1}}">
			<p>
				<label for=per_page>Elemente pro Seite:</label>
				<input type="number" id="per_page" name="per_page" min="1" max="999" value="{{ $_GET['per_page'] ?? 50}}">
			</p>
			<p>
				<label for=button_count>Für wieviele Buttons?</label>
				<input type="number" id="button_count" name="button_count" min="0" max="255" value="{{ $_GET['button_count'] ?? 5}}">
			</p>
			<p>
				<input type="number" id="game_mode" name="game_mode" min="0" max="2" value="{{ $_GET['game_mode'] ?? 1}}">
				<label for=game_mode>Modus?<br>1 => Normal<br>2 => Schwer</label>
			</p>
			<button style="font-size: 1em;" type="submit">Filtern</button>
		</form>
		<p></p>
		<div class="row">  
			<div class="col-lg-12">
				{!! $data->links() !!}
			</div>
			<br>* Letzte Zeit =
			<br>Rundenzeit im höchsten<br>erreichten Level
			<p></p>
		</div>
		
		<div class="scrollable">
		<table id="score_table">
			@if (isset($accumulated))
			<thead>
				<tr>
					<th>Pos.</th>
					<th>Spieler</th>
					<th>Buttons</th>
					<th>Modus</th>
					<th>Gesamt-Level</th>
					<th>Gesamt-Punkte</th>
					<th>Gesamt-Zeit</th>
				</tr>
			</thead>
			@else
			<thead>
				<tr>
					<th>Pos.</th>
					<th>Spieler</th>
					<th>Buttons</th>
					<th>Modus</th>
					<th>Level</th>
					<th>Punkte</th>
					<th>Spielzeit</th>
					<th>* Letzte Zeit</th>
				</tr>
			</thead>
			@endif
			<tbody id="score_data">
			{{ false == $pos = (($_GET["page"] ?? 1) - 1) * ($_GET["per_page"] ?? 50) + 1 }}
			{{ false == $medal_colors = ["gold", "silver", "bronze"]}}
			<script>
				function myScrollToPos(pos) {
					console.log(pos);
					if (pos < 1)
						return;
					if (pos > score_data.childElementCount - 2)
						// Auf die Zielseite klicken
						return document.querySelector(".z-0").children[pos / (score_data.childElementCount - 2)].click();
						
					return score_table.rows[pos - 1].scrollIntoView({behavior: 'smooth'});
				}
			</script>
			@if(!empty($data) && $data->count() && isset($accumulated))
				@foreach($data as $i => $score)
				<tr class="{{ $pos + $i < 4 ? $medal_colors[$pos + $i - 1] : ''}}">
					<td>{{ $pos + $i }}.</td>
					<td>{{ $score->name }}</td>
					<td>{{ $score->button_count }}</td>
					<td>{{ $score->is_fuzzy + 1 }}</td>
					<td>{{ number_format($score->accumulated_levels, 0, ',', '.') }}</td>
					<td>{{ number_format($score->accumulated_points, 0, ',', '.') }}</td>
					<td>{{ Time_String($score->accumulated_time) }}</td>
				</tr>
				@endforeach
				<script>
					myScrollToPos({{ $rank }});
					$('#csv_btn').click(() => get_csv("load_highscores_accumulated"));
				</script>
			@elseif(!empty($data) && $data->count())
				@foreach($data as $i => $score)
				<tr class="{{ $pos + $i < 4 ? $medal_colors[$pos + $i - 1] : ''}}">
					<td>{{ $pos + $i }}.</td>
					<td>{{ $score->name }}</td>
					<td>{{ $score->button_count }}</td>
					<td>{{ $score->is_fuzzy + 1 }}</td>
					<td>{{ number_format($score->levels, 0, ',', '.') }}</td>
					<td>{{ number_format($score->points, 0, ',', '.') }}</td>
					<td>{{ Time_String($score->time_played) }}</td>
					<td>{{ Time_String($score->time_for_highest_level) }}</td>
				</tr>
				@endforeach
				<script>
					myScrollToPos({{ $rank }});
					$('#csv_btn').click(() => get_csv("load_highscores"));
				</script>
			@else
				<tr>
					<td colspan="10">No data found on position {{ $pos }}.</td>
				</tr>
			@endif
			</tbody>
		</table>
		</div>
		<div id="footer" class="row">  
			<div class="col-lg-12">
				{!! $data->links() !!}
			</div>
		</div>
		@endif
		<footer id="created_from">Erstellt von: David Schüppel, 2022<span id="show_year"></span></footer>
	</body>
</html>

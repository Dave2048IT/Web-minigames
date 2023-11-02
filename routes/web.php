<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\HighscoreController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::view('/', 'main')->name("main");
Route::view('memory_game.php', 'memory_game');
Route::view('remember_numbers.php', 'remember_numbers');
Route::view('slide_puzzle.php', 'slide_puzzle');

Route::get('test_auth', [LoginController::class, 'test_auth']);
Route::get('suspend_user', [LoginController::class, 'suspend_user']);
Route::get('logout', [LoginController::class, 'logout'])->name('logout');
Route::post('login', [LoginController::class, 'loginUser'])->name('login');
Route::post('register', [LoginController::class, 'registerUser'])->name('register');
Route::get('friend_list', [LoginController::class, 'show_friends'])->name('friend_list');
Route::post('request_friend', [LoginController::class, 'request_friend']);//->name('friend_list');

Route::get('load_csv', [HighscoreController::class, 'download_list_as_csv']);
Route::post('check_valid_scores', [HighscoreController::class, 'check_valid_scores']);

Route::get('remember_numbers.php/load_highscores/',  [HighscoreController::class, 'load_remember_numbers']);
Route::get('remember_numbers.php/load_highscores_accumulated',  [HighscoreController::class, 'load_accumulated_remember_numbers']);
Route::get('remember_numbers.php/find_rank/load_highscores',  [HighscoreController::class, 'find_user_position'])->name('remember_numbers.php/load_highscores');
Route::get('remember_numbers.php/find_rank/load_highscores_accumulated',  [HighscoreController::class, 'find_user_position_accumulated'])->name('remember_numbers.php/load_highscores_accumulated');
Route::post('save_highscores_in_remember_numbers',  [HighscoreController::class, 'save_remember_numbers']);

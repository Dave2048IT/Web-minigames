test_auth();
$a = a=>document.querySelector(a);
$$ = a=>document.createElement(a);

levels = $a("#levels");

function add_btns(a) {
    btn = $$("button");
    btn.id = "btn" + a;
    btn.innerText = a + 1;
    btn.onclick = function() { check_if_button_is_correct(a); };
    $a("#btnsDiv").appendChild(btn);
}

// A wait function
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function change_color_to(color = "grey", id = 1) {
    $a("#btn" + id).style.background = color;
}

/**
 * Disable or enable all buttons on the field
 * @param state - true => disable / false => enable
 * @param grey_color - true => grey / false => normal
 */
function disable_btns(state = true, grey_color = false) {
    for (var i = button_count; i--;) {
        $a("#btn" + i).disabled = state;
        
        if (!grey_color)
            change_color_to("", i);
        else
            change_color_to("grey", i);
    }
}

// Start a new game
function new_game() {
    is_game_over = false;
    is_fuzzy = !$a("#not_fuzzy").checked;
    button_count = $a("#button_count").value | 0;
    if (1 >= button_count || button_count >= 256)
        return info_text.innerText = "Bitte gib eine Zahl zwischen 2 und 255 ein.";
    
    sw_1.restart();
    level_history.innerHTML = "<b>Rundenverlauf</b><br>";

    if (info_text.innerText.startsWith("Fehler beim Hochladen"))
        info_text.innerText = "Oh, dann eben beim nächsten Mal. ;-)";
    else
        info_text.innerText = "Versuche, dir so viele Zahlen, wie möglich, zu merken. ;-)";

    nums = [];
    seq_nr = 0;
    pts = 0;
    $a("#not_fuzzy").disabled = true;
    $a("#button_count").disabled = true;

    points.innerText = 0;
    levels.innerText = 1;
    return generate_and_show_nums();
}

// Show the next sequence of buttons to be clicked
async function generate_and_show_nums() {
    sw_2.clear();
    music_for_next_level();

    hits = 0;
    ++seq_nr;
    pause_ms = $a("#pause_ms").value | 0;
    start_btn.disabled = true;
    start_btn.style.background = "grey";
    highscore_btn.disabled = true;
    highscore_btn.style.background = "grey";
    disable_btns(true, true);
    levels.innerText = seq_nr;
    $a("#info_text").innerText = "Pass gut auf. Merke dir die Reihenfolge.";

    await sleep(3000);
    // Im normalen Modus: Füge einfach eine Zahl hinzu.
    if (!is_fuzzy) {
        nums.push(Math.floor(Math.random() * button_count));
    }
    // Im Fuzzy-Modus: Regeneriere das nums[] Array und füge eine Zufallszahl so oft ein,
    // wie es dem aktuellen Level entspricht.
    else {
        nums = new Uint8Array(seq_nr);
        i = seq_nr;
        while (i--)
            nums[i] = Math.floor(Math.random() * button_count);
    }
    await sleep(pause_ms);
    
    for (i = 0; i < seq_nr; i++) {
        var button_nr = nums[i];
        
        make_tone(button_nr, 1);
        change_color_to("blue", button_nr);
        await sleep(pause_ms);
        // Auf Farbwechsel warten.
        
        change_color_to("grey", button_nr);
        await sleep(pause_ms);
    }
    sw_2.restart();
    $a("#info_text").innerText = "Jetzt wiederhole das!";
    return disable_btns(false);
}

function animate_points(add = 0) {
    var result = pts + add | 0;
    var step = add / 60;
    return new Promise((resolve, reject) => {
        var itv = setInterval(() => {
            if (add > 0) {
                add -= step;
                pts += step;
                points.innerText = pts | 0;
                bonus.innerText  = "+"+ (add + 1 | 0);
            }
            else {
                clearInterval(itv);
                points.innerText = pts = result;
                bonus.innerText  = "";
                bonus.style.color = "blue";
                resolve(result);
            }
        }, 1000 / 30);
    });
}

async function show_bonus(result = 0) {
    await sleep(2000);
    var add = result - pts | 0;
    bonus.style.color = "yellow";
    bonus.innerText = "+"+ add;
    await sleep(1000);
    return add;
}

async function level_up(button_nr) {
    sw_2.stop();
    disable_btns(true);
    change_color_to("green", button_nr);
    time_for_highest_level = sw_2.current_time;
    info_text.innerText = "Toll gemacht. Weiter so ;-)";
    level_history.innerHTML += "<br>"+(seq_nr)+": "+Time_String(sw_2.current_time);

    var add2 = 0;
    var add = 10 * seq_nr;
    bonus.innerText = "+"+add;
    if (sw_2.current_time < 1000 * seq_nr)
    {
        add += add2 = add + 1 -
            sw_2.current_time / 100 | 0;
        bonus.innerText += " +"+add2;
        await sleep(1000);
        bonus.innerText = "+"+add;
        bonus.style.color = "gold";
    }
    await sleep(1000);
    animate_points(add);
    
    return generate_and_show_nums();
}

async function game_over(button_nr) {
    sw_1.stop();
    sw_2.stop();
    is_game_over = true;
    disable_btns(true);
    change_color_to("red", button_nr);

    let duration = music_for_game_over();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    if (pts)
        info_text.innerText = "Oh nein. Das war's :-O";
    
    let result = add = pts;
    if (is_fuzzy && pts) {
        duration -= 6;
        result += add = pts / 2;
        // bonus.style.color = "yellow";
        bonus.innerText = "× 1.5 \n Auf Schwer gespielt.";
        add = await show_bonus(result);
        animate_points(add);
        await sleep(3000);
    }
    
    if (pts) {
        duration -= 3;
        // result *= add = button_count / 10 + 1;
        result *= add = Math.log2(button_count);
        bonus.innerText = "× "+ add.toFixed(2);
        add = await show_bonus(result);
        animate_points(add).then((result) => {
            // In Datenbank hochladen
            SendData();
        });
    }
    else
        info_text.innerText = 'Hey, du musst schon auf die blinkenden Felder klicken. ;-)';

    await sleep(duration * 1000 + 1500);
    for (a = 3; a--;) {
        make_tone(nums[hits], 1, 4, 8, pause_ms / 1000);

        await sleep(pause_ms);
        change_color_to("orange", nums[hits]);

        await sleep(pause_ms);
        change_color_to("", nums[hits]);
    }
    await sleep(pause_ms);
    start_btn.disabled = false;
    start_btn.style.background = "";
    highscore_btn.disabled = false;
    highscore_btn.style.background = "";
    
    $a("#not_fuzzy").disabled = false;
    $a("#button_count").disabled = false;
    return disable_btns(true, true);
}

function check_if_button_is_correct(button_nr) {
    if (button_count < button_nr + 1)
        return;
    // Färbe alle Buttons zu ihrer normalen Farbe.
    for (a = button_count; a--;)
        change_color_to("", a);
    
    if (button_nr != nums[hits])
        return game_over(button_nr);
        
    make_tone(button_nr, 1);
    change_color_to("green", button_nr);

    if (++hits == seq_nr)
        return level_up(button_nr);
}

function test_auth() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $.ajax({
        type: "GET",
        url: "test_auth",
        error: function (xhr) {
            var message = "";
            console.log(xhr.status);

            if (xhr.status == 404)
                message = "Der Server wird wohl gewartet.\nEs tut uns leid für die Unannehmlichkeiten :-(";
            else if (xhr.status == 419)
                message = "Oh, die Session scheint abgelaufen zu sein.\nVersuche nochmal, offline und wieder online zu gehen."+
                          "\nAnsonsten musst du wohl oder übel die Seite neu laden."
            
            alert(info_text.innerText = message);
        },
        success: function (response) {
            if (response == false && !confirm("Du willst also nur als Gast spielen?"))
                return window.location.replace(".");
            
            console.log("Alles okay");
        }
    });
}

function check_valid_scores() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    var data = {
        'points': pts,
        'levels': seq_nr - 1,
        'is_fuzzy': is_fuzzy | 0,
        'button_count': button_count,
    }

    $.ajax({
        type: "POST",
        url: "check_valid_scores",
        data: data,
        error: function (xhr) {
            var message = "";
            console.log(xhr.status);

            if (xhr.status == 404)
                message = "Der Server wird wohl gewartet.\nEs tut uns leid für die Unannehmlichkeiten :-(";
            else if (xhr.status == 419)
                message = "Oh, die Session scheint abgelaufen zu sein.\nVersuche nochmal, offline und wieder online zu gehen."+
                          "\nAnsonsten musst du wohl oder übel die Seite neu laden."
            
            alert(info_text.innerText = message);
        },
        success: function (response) {
            // Wenn gecheatet wurde, lade die Seite neu.
            if (response.status == 405)
                return window.location.replace(".");
        }
    });
}

function SendData() {
    // Wenn du 0 Level hast
    if (seq_nr == 1)
        return info_text.innerText = 'Hey, du musst schon auf die blinkenden Felder klicken. ;-)';

    console.log("Sende", pts);
    var data = {
        'points': pts,
        'levels': seq_nr - 1,
        'is_fuzzy': is_fuzzy | 0,
        'button_count': button_count,
        'time_played': sw_1.current_time,
        'time_for_highest_level': time_for_highest_level,
    }

    var xhr = $.ajax({
        timeout: 4000,
        type: "POST",
        data: data,
        dataType: "json",
        url: "save_highscores_in_remember_numbers",
        beforeSend: function (jqXHR, settings) {
            
            // jqXHR wird im Hintergrund benötigt
            var self = this;
            var xhr = settings.xhr;
            settings.xhr = function () {
                var output = xhr();
                output.onreadystatechange = function () {
                    if (typeof(self.readyStateChanged) == "function") {
                        self.readyStateChanged(this);
                    }
                };
                return output;
            };
        },    
        readyStateChanged: function (xhr) {
            info_text.innerText = "Score wird hochgeladen... "+25*xhr.readyState+" %";
        },
        error: function (xhr) {
            var message = "";
            console.log(xhr.status);

            if (xhr.status == 404)
                message = "Der Server wird wohl gewartet.\nEs tut uns leid für die Unannehmlichkeiten :-(";
            else if (xhr.status == 419)
                message = "Oh, die Session scheint abgelaufen zu sein.\nVersuche nochmal, offline und wieder online zu gehen."+
                          "\nAnsonsten musst du wohl oder übel die Seite neu laden."
            else
                message = "Fehler beim Hochladen. Bist du offline oder im Funkloch?\n\n"+
                "Sobald du wieder online bist,\nwird dein Score automatisch hochgeladen. :-)";
            
            alert(info_text.innerText = message);
        },
        success: function (response) {
            if (response) {
                is_game_over = false;
                info_text.innerText = response["message"];
            }
            else {
                alert(info_text.innerText = "Unbekannter Fehler: "+response["status"]);
            }
        },
    });
}

/**
 * This function plays a tone immediately, but there is a better function, called "make_tone" underneath
 */
function playFrequency(frequency = 432, duration = 0.5) {
    // create 2 second worth of audio buffer, with single channels and sampling rate of your device.
    var sampleRate = audioContext.sampleRate;
    duration *= sampleRate;
    var numChannels = 1;
    var buffer  = audioContext.createBuffer(numChannels, duration, sampleRate);
    
    // fill the channel with the desired frequency's data
    var channelData = buffer.getChannelData(0);
    // Calculate the frequency before the loop
    var aum = 2 * Math.PI * frequency / sampleRate;
    console.log(0);

    for (var i = 0; i < duration; i++) {
      channelData[i] = Math.sin(aum*i);
    }
    console.log(sampleRate, duration);

    // create audio source node.
    var source = audioContext.createBufferSource();
    source.buffer = buffer;
    source.connect(audioContext.destination);

    // finally start to play
    source.start(0);
}

/**
 * This function tries to create a good sounding tone,
 * but you should be aware that you test this carefully with the parameters, otherwise you will break your ears.
 * 
 * @param {Number} tone The Tone from C (0) to B (6) to be played as a sound
 * @param {Number} depends 0 => as is, 1 => depends on button_count
 * @param {Number} oct The octave on the waves
 * @param {Number} note the duration of 1 / "note" in seconds
 * @param {Number} delay The delay in seconds to wait for playing
 * @returns "Zu hoch" | or playing sound
 */
function make_tone(tone = 0, depends = 0, oct = 4, note = 8, delay = 0) {
    // This statement works even if button_count does not exist, but only if @depends is set to 0
    tone = depends == 0 || button_count < 8
        ? tone
        : tone / button_count * 7 | 0;
    
    if (oct >= 7 || tone >= 7)
        return "Zu hoch";
    
    synth.triggerAttackRelease(""+tone_letters[tone] + oct, ""+note+"n", Tone.now() + delay);
}

var button_count = $a("#button_count").value | 0;
for (i = 0; i < button_count; i++) {
    add_btns(i);
}

function music_for_next_level() {
    let start = 0;
    make_tone(4, 0, 3, 2, start);
    make_tone(5, 0, 3, 2, start += 1/8);
    make_tone(0, 0, 4, 8, start += 1/8);
    make_tone(4, 0, 4, 8, start += 1/4);
    make_tone(0, 0, 4, 8, start += 1/4);
    make_tone(2, 0, 4, 8, start += 1/4);
    make_tone(0, 0, 5, 8, start += 1/2);
    return start;
}

function music_for_game_over() {
    let start = 0;
    make_tone(1, 0, 4, 8);
    make_tone(0, 0, 4, 8, start += 1/16);
    make_tone(1, 0, 4, 4, start += 1/8);
    make_tone(2, 0, 3, 16, start += 1);
    make_tone(3, 0, 3, 16, start += 1/4);
    make_tone(2, 0, 4, 8, start += 1/8);
    make_tone(2, 0, 4, 8, start += 1/4);
    make_tone(1, 0, 4, 8, start += 1/2);
    make_tone(1, 0, 4, 8, start += 1/4);
    make_tone(0, 0, 4, 4, start += 1/4);
    make_tone(1, 0, 3, 8, start += 3/4);
    make_tone(0, 0, 3, 4, start += 1/4);
    return start;
}

function check_keyCode(evt) {
    var keycode;
    if (window.event)
        keycode = window.event.keyCode;
    else if (evt)
        keycode = evt.which;

    console.log(keycode);
    if ( btn0.disabled || (false == (keycode >= 49 && keycode < 58 || keycode >= 97 && keycode < 106)) )
        return;
    check_if_button_is_correct(keycode < 58 ? keycode - 49 : keycode - 97);
}

function Time_String(current_time) {
    let minus = false;
    if (current_time < 0) {
        current_time = -current_time;
        minus = true;
    }
    
    return (minus ? "- " : "") + (current_time >= 3600000 ? (current_time / 3600000 | 0) + "h " : "") +
    (current_time / 6e4 % 60 | 0) +"'"+
    String(current_time / 1e3 % 60 | 0).padStart(2, '0') +"."+
    String(current_time % 1e3 | 0).padStart(3, '0');
}

// encoded = btoa(JSON.stringify(gameData))
// hash != (pts ^ seq_nr ^ is_fuzzy ^ button_count ^
//     sw_1.start_time ^ sw_2.start_time ^ window.performance.timeOrigin)

function punish_invalid() {
    var tmo = setTimeout(() => {hash = 0}, 10);
    if (!hash || hash != (pts ^ seq_nr ^ is_fuzzy ^ button_count ^
            sw_1.start_time ^ sw_2.start_time ^ window.performance.timeOrigin))
            // || encoded != btoa(JSON.stringify(gameData))
        return window.location.replace("./suspend_user");
    
    clearTimeout(tmo);
}

disable_btns(true, true);
setTimeout(() => {
    level_history.innerHTML += "";
    show_year.innerText = (year = new Date().getFullYear()) == 2022 ? '' : " - " + year;
});

var is_game_over = false;
var tone_up = Math.pow(2, 1/8);
var tone_letters = "CDEFGAB";
var time_for_highest_level = 0;
var hash = 12208714;
const synth = new Tone.Synth().toDestination();

$a('#button_count').onchange = function() {
    $a("#btnsDiv").innerHTML = "";
    button_count = $a("#button_count").value | 0;
    for (i = 0; i < button_count; i++) {
        add_btns(i);
    }
    disable_btns(true, true);
}
window.onkeydown = function() {check_keyCode()};
window.addEventListener('offline', () => alert('Oh, kein Internet'));
window.addEventListener('online', () => {console.log('Jaaa, wieder online'); if (is_game_over) SendData()});

$(document).on('click', '.pagination a', function(event){
    event.preventDefault(); 
    var page = $(this).attr('href').split('page=')[1];
    fetch_data(page);
});
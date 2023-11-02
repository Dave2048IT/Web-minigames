class Stopwatch {
	constructor(id = 1) {
		this.id = id;
		this.flag_stop = 1;
		this.last_lap_start = Date.now();

		this.laps =
		this.start_time =
		this.current_time = 0;
	}
	start() {
		this.flag_stop = 0;
		this.last_lap_start = Date.now();
		this.start_time = this.last_lap_start - this.current_time;
		this.timer();
	}
	stop() {
		this.flag_stop = 1;
	}
	clear() {
		this.stop();
		this.current_time = 0;
		window["watch_"+this.id].innerText = "-'--.---";
	}
	restart() {
		this.clear();
		this.start();
	}
	timer() {
		if (!this.flag_stop) {
			this.current_time = Date.now() - this.start_time;
			window["watch_"+this.id].innerText = Time_String(this.current_time);
			window.requestAnimationFrame(() => this.timer());
			// let requestId = requestAnimFrame(() => { this.animate(); });
		}
	}
	lap() {
		this.laps++;
		let lap_time = Date.now() - this.last_lap_start;
		this.last_lap_start = Date.now();
		return lap_time;
	}
};

var sw_1 = new Stopwatch();
var sw_2 = new Stopwatch(2);
<?php
	return [
		'server' => env('TATTLER_SERVER'),
		'ssl' => env('TATTLER_SSL', false), // форсированное wss вместо ws
		'root' => env('TATTLER_ROOT', NULL) // оставь NULL для автогенерации значения. Если не знаешь что это и зачем - тоже оставь NULL
	];

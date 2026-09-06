<?php

use Illuminate\Support\Facades\Schedule;

// Prune expired Sanctum tokens weekly (no-op if the table is empty).
Schedule::command('sanctum:prune-expired --hours=24')->weekly();

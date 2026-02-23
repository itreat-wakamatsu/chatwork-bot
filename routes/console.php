<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('Stay curious.');
})->purpose('Display an inspiring quote');

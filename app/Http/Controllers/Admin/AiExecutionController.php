<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiExecution;
use Illuminate\Contracts\View\View;

class AiExecutionController extends Controller
{
    public function index(): View
    {
        return view('admin.executions.index', [
            'executions' => AiExecution::query()->latest()->paginate(30),
        ]);
    }

    public function show(AiExecution $execution): View
    {
        $execution->load('turns');

        return view('admin.executions.show', [
            'execution' => $execution,
        ]);
    }
}

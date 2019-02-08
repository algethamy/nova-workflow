<?php

use Cammac\Workflow\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::get('/{workflow}/{id}/{transaction}/{reason?}', WorkflowController::class);

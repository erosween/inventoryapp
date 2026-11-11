<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

// Kelas dasar controller Laravel. Akses dan aturan fitur diletakkan di route, middleware, atau
// controller turunannya.
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}

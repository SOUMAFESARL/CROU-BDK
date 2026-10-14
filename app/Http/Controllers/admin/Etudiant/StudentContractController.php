<?php

namespace App\Http\Controllers\admin\Etudiant;

use App\Http\Controllers\Controller;
use App\Http\Services\StudentContractService;
use App\Models\Etudiant;

class StudentContractController extends Controller
{
    public function __invoke(Etudiant $etudiant, StudentContractService $contracts)
    {
        return $contracts->stream($etudiant);
    }
}

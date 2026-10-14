<?php

namespace Tests\Feature;

use App\Http\Middleware\LogUserActivity;
use Tests\TestCase;

class StudentMobileApiTest extends TestCase
{
    public function test_student_mobile_endpoints_require_a_bearer_token(): void
    {
        $this->withoutMiddleware(LogUserActivity::class);

        foreach ([
            ['GET', '/api/V1/paiement'],
            ['GET', '/api/V1/paiement/foreign'],
            ['GET', '/api/V1/paiements-en-ligne/moyens'],
            ['GET', '/api/V1/paiements-en-ligne'],
            ['POST', '/api/V1/paiements-en-ligne'],
            ['GET', '/api/V1/paiements-en-ligne/test'],
            ['POST', '/api/V1/paiements-en-ligne/test/verifier'],
            ['GET', '/api/V1/documents'],
            ['GET', '/api/V1/mes-documents'],
            ['GET', '/api/V1/mes-documents/recu/test'],
            ['GET', '/api/V1/etat-des-lieux'],
            ['GET', '/api/V1/etat-des-lieux/test'],
            ['POST', '/api/V1/etat-des-lieux/eld'],
            ['PUT', '/api/V1/etat-des-lieux/eld/test'],
            ['GET', '/api/V1/etudiant/demandes-sortie'],
            ['POST', '/api/V1/etudiant/demandes-sortie'],
            ['GET', '/api/V1/etudiant/etat-des-lieux-sortie'],
            ['PATCH', '/api/V1/etudiant/etat-des-lieux-sortie/test/approuver'],
            ['GET', '/api/V1/etudiant/maintenances'],
            ['POST', '/api/V1/etudiant/maintenances'],
        ] as [$method, $uri]) {
            $this->json($method, $uri)->assertUnauthorized()->assertJsonPath('success', false);
        }
    }
}

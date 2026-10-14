<?php
namespace Tests\Feature;

use App\Support\UserFacingErrors;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserFacingErrorsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug'=>true,'session.driver'=>'array']);
        $this->withoutMiddleware([\App\Http\Middleware\LogUserActivity::class,\App\Http\Middleware\EnforceSystemSecurity::class]);
        Route::get('/api/test-user-errors/server',fn()=>throw new \RuntimeException('PRIVATE_SQL_PASSWORD_STACK'));
        Route::get('/test-user-errors/server',fn()=>throw new \RuntimeException('PRIVATE_SQL_PASSWORD_STACK'));
        Route::get('/api/test-user-errors/status/{status}',fn($status)=>abort((int)$status,'PRIVATE_EXCEPTION_DETAIL',['Retry-After'=>'60']));
        Route::post('/api/test-user-errors/validation',fn(\Illuminate\Http\Request $request)=>$request->validate(['email'=>'required|email']));
        Route::middleware('web')->post('/test-user-errors/validation',fn(\Illuminate\Http\Request $request)=>$request->validate(['email'=>'required|email']));
        Route::get('/api/test-user-errors/manual',fn()=>response()->json(['message'=>'PRIVATE_SQL','trace'=>['PRIVATE_STACK']],503));
        Route::get('/test-user-errors/html',fn()=>response('<html><body>Page de test</body></html>'));
    }

    public function test_unexpected_errors_have_safe_json_even_with_debug_enabled_and_without_accept_header(): void
    {
        $this->get('/api/test-user-errors/server')->assertStatus(500)->assertJsonPath('success',false)
            ->assertJsonPath('message',UserFacingErrors::messageForStatus(500))->assertDontSee('PRIVATE_SQL_PASSWORD_STACK')
            ->assertHeader('Cache-Control','no-store, private');
    }

    public function test_web_error_page_is_independent_of_database_and_has_navigation(): void
    {
        $this->get('/test-user-errors/server')->assertStatus(500)->assertSee('Action interrompue')
            ->assertSee('Retour à l’accueil')->assertDontSee('PRIVATE_SQL_PASSWORD_STACK');
        $this->get('/page-inexistante-test-erreurs')->assertNotFound()->assertSee('Page introuvable');
    }

    #[DataProvider('httpStatuses')]
    public function test_http_codes_and_retry_headers_are_preserved_without_technical_details(int $status): void
    {
        $this->getJson('/api/test-user-errors/status/'.$status)->assertStatus($status)
            ->assertJsonPath('success',false)->assertJsonPath('message',UserFacingErrors::messageForStatus($status))
            ->assertDontSee('PRIVATE_EXCEPTION_DETAIL')->assertHeader('Retry-After','60');
    }
    public static function httpStatuses(): array
    {
        return array_map(fn($status)=>[$status],[400,401,403,404,405,408,409,410,413,415,419,422,429,500,502,503,504]);
    }

    public function test_validation_has_french_field_errors_and_web_redirect_keeps_input_without_password(): void
    {
        $this->postJson('/api/test-user-errors/validation',[])->assertUnprocessable()
            ->assertJsonPath('message','Vérifiez les champs indiqués et réessayez.')
            ->assertJsonPath('errors.email.0','Le champ adresse e-mail est obligatoire.');
        $this->from('/formulaire')->post('/test-user-errors/validation',['email'=>'incorrect','password'=>'PRIVATE_PASSWORD'])
            ->assertRedirect('/formulaire')->assertSessionHasErrors('email')->assertSessionHasInput('email','incorrect');
        $this->assertNull(session('_old_input.password'));
    }

    public function test_manually_returned_server_errors_are_sanitized(): void
    {
        $this->getJson('/api/test-user-errors/manual')->assertStatus(503)->assertExactJson([
            'success'=>false,'message'=>UserFacingErrors::messageForStatus(503),
        ])->assertDontSee('PRIVATE_SQL');
    }

    public function test_caught_database_errors_are_clear_and_validation_messages_remain_useful(): void
    {
        $previous=new \PDOException('PRIVATE_SQL');$previous->errorInfo=['23000',1062,'PRIVATE_COLUMN'];
        $error=new QueryException('mysql','INSERT INTO PRIVATE_TABLE VALUES (?)',['PRIVATE_BINDING'],$previous);
        $this->assertSame('Cette information est déjà enregistrée. Vérifiez les données saisies.',UserFacingErrors::caught($error));
        $this->assertSame('Stock disponible insuffisant.',UserFacingErrors::caught(ValidationException::withMessages(['quantite'=>'Stock disponible insuffisant.'])));
    }

    public function test_html_pages_receive_browser_error_handling_without_changing_json(): void
    {
        $this->get('/test-user-errors/html')->assertOk()->assertSee('js/user-errors.js',false)->assertSee('Page de test');
        $this->getJson('/api/test-user-errors/manual')->assertStatus(503)->assertDontSee('<script',false);
    }

    public function test_response_exceptions_keep_the_intended_response(): void
    {
        Route::get('/api/test-user-errors/response',fn()=>throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json(['message'=>'Action à confirmer.'],409)));
        $this->getJson('/api/test-user-errors/response')->assertConflict()->assertJsonPath('message','Action à confirmer.');
    }

    public function test_printing_html_in_scripts_is_not_modified_by_error_script_injection(): void
    {
        $printing = '<script>const printHtml = `<html><body>Constat</body></html>`;</script>';
        Route::get('/test-user-errors/printing', fn () => response('<html><body>'.$printing.'<footer>Fin de page</footer></body></html>'));
        $response = $this->get('/test-user-errors/printing')->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString($printing, $html);
        $this->assertSame(1, substr_count($html, 'js/user-errors.js'));
        $this->assertGreaterThan(strpos($html, '<footer>'), strpos($html, 'js/user-errors.js'));
    }
}

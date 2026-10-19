<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Middleware\LogUserActivity;
use App\Models\ConfigApp;
use App\Models\Etudiant;
use App\Notifications\EtudiantResetPasswordNotification;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EtudiantPasswordResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class]);
        // Paramétrage vide : règles de mot de passe par défaut (8 caractères, majuscules, chiffre).
        $this->mock(ConfigAppRepositoryInterface::class)->shouldReceive('getConfig')->andReturn(new ConfigApp());

        Schema::create('config_app', fn (Blueprint $t) => $t->string('config_id')->primary());
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary();
            $t->string('name')->nullable();
            $t->string('email1')->nullable();
            $t->string('password')->nullable();
            $t->integer('statut')->default(1);
            // Colonnes renseignées par défaut dans Etudiant::boot()
            foreach (['nationalite', 'statut_inscription', 'created_by', 'updated_by'] as $f) $t->string($f)->nullable();
            foreach (['is_validation', 'is_rejet', 'allergies', 'maladies_chroniques', 'autiste', 'boursier'] as $f) $t->integer($f)->default(0);
            $t->timestamps();
            $t->softDeletes();
        });
        (require database_path('migrations/2026_10_14_000002_create_etudiant_password_reset_tokens_table.php'))->up();

        Notification::fake();
    }

    private function student(): Etudiant
    {
        return Etudiant::create(['name' => 'Awa Koné', 'email1' => 'awa@exemple.ci', 'password' => Hash::make('Ancien123')]);
    }

    private function page(string $url): array
    {
        $html = $this->get($url)->assertOk()->getContent();
        preg_match('/data-page="([^"]+)"/', $html, $matches);

        return json_decode(html_entity_decode($matches[1] ?? ''), true);
    }

    public function test_login_page_links_to_forgot_password(): void
    {
        $page = $this->page('/login');

        $this->assertSame(route('etudiant.password.request'), $page['props']['forgotUrl']);
        $this->assertSame('Etudiant/Auth/ForgotPassword', $this->page('/mot-de-passe-oublie')['component']);
    }

    public function test_reset_link_is_sent_to_student_email(): void
    {
        $this->student();

        $this->from('/mot-de-passe-oublie')->post('/mot-de-passe-oublie', ['email' => 'awa@exemple.ci'])
            ->assertRedirect('/mot-de-passe-oublie')
            ->assertSessionHas('status');

        Notification::assertSentTo(
            new AnonymousNotifiable,
            EtudiantResetPasswordNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'awa@exemple.ci',
        );
        $this->assertSame(1, DB::table('etudiant_password_reset_tokens')->where('email', 'awa@exemple.ci')->count());
    }

    public function test_unknown_email_gets_the_same_message_without_mail(): void
    {
        $this->from('/mot-de-passe-oublie')->post('/mot-de-passe-oublie', ['email' => 'inconnu@exemple.ci'])
            ->assertRedirect('/mot-de-passe-oublie')
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_student_can_reset_password_with_valid_token(): void
    {
        $student = $this->student();
        $token = app('auth.password')->broker('etudiants')->createToken($student);

        $this->assertSame('Etudiant/Auth/ResetPassword', $this->page("/reinitialiser-mot-de-passe/{$token}?email=awa@exemple.ci")['component']);

        $this->post('/reinitialiser-mot-de-passe', [
            'token' => $token,
            'email' => 'awa@exemple.ci',
            'password' => 'Nouveau123',
            'password_confirmation' => 'Nouveau123',
        ])->assertRedirect(route('etudiant.login'))->assertSessionHas('success');

        $this->assertTrue(Hash::check('Nouveau123', $student->fresh()->password));
        $this->assertSame(0, DB::table('etudiant_password_reset_tokens')->count());
    }

    public function test_invalid_token_is_rejected(): void
    {
        $student = $this->student();

        $this->from('/reinitialiser-mot-de-passe/faux')->post('/reinitialiser-mot-de-passe', [
            'token' => 'faux',
            'email' => 'awa@exemple.ci',
            'password' => 'Nouveau123',
            'password_confirmation' => 'Nouveau123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('Ancien123', $student->fresh()->password));
    }
}

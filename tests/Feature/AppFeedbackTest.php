<?php

namespace Tests\Feature;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AppFeedbackTest extends TestCase
{
    public function test_feedback_escapes_messages_and_links_validation_errors_to_fields(): void
    {
        session()->flash('error', '<script>alert(1)</script>');
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['email' => 'Adresse incorrecte.']));
        $html = view('components.app-feedback', compact('errors'))->render();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('data-error-field="email"', $html);
        $this->assertStringContainsString('Adresse incorrecte.', $html);
    }

    public function test_shared_views_compile(): void
    {
        foreach (['components.app-feedback', 'components.app-scripts', 'components.alert', 'components.breadcrumbs', 'admin.layouts.app'] as $name) {
            $path = resource_path('views/'.str_replace('.', '/', $name).'.blade.php');
            $compiled = app('blade.compiler')->compileString(file_get_contents($path));
            $this->assertNotEmpty(token_get_all($compiled, TOKEN_PARSE));
        }
    }
}

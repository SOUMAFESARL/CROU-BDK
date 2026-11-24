<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use App\Services\AdminAccess;
use App\Services\AdmissionSmsTemplates;
use App\Services\AdmissionEmailTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdmissionSmsController extends Controller
{
    public function __construct(private ConfigAppRepositoryInterface $config) {}

    /** Modèles de SMS des résultats d'admission (page React `Admin/Parametrage/ConfigurationMessages`). */
    public function index(Request $request, AdminAccess $access): Response
    {
        $config = $this->config->getConfig();
        $saved = $config?->admission_sms_templates ?? [];
        $canEdit = $access->allows($request->user('admin'), 'admin.parametrage.configuration-messages.update');

        return Inertia::render('Admin/Parametrage/ConfigurationMessages', [
            'labels' => AdmissionSmsTemplates::LABELS,
            'messages' => AdmissionSmsTemplates::resolve($saved),
            'formatted' => (object) array_intersect_key($saved['formatted'] ?? [], AdmissionSmsTemplates::LABELS),
            'defaults' => AdmissionSmsTemplates::resolve([]),
            'emails' => AdmissionEmailTemplates::resolve($saved),
            'reclamationEmail' => $saved['reclamation_email'] ?? '',
            'effectiveReclamationEmail' => AdmissionEmailTemplates::reclamationEmail($config),
            'urls' => [
                'update' => $canEdit ? route('admin.parametrage.configuration-messages.update') : null,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = ['messages' => 'required|array:'.implode(',', array_keys(AdmissionSmsTemplates::LABELS))];
        $rules['formatted'] = 'sometimes|array:'.implode(',', array_keys(AdmissionSmsTemplates::LABELS));
        $rules['emails'] = 'sometimes|array:'.implode(',', array_keys(AdmissionSmsTemplates::LABELS));
        $rules['reclamation_email'] = 'sometimes|nullable|email:rfc|max:254';
        $attributes = ['reclamation_email' => 'adresse de réception des réclamations'];
        foreach (AdmissionSmsTemplates::LABELS as $key => $label) {
            $rules["messages.$key"] = 'required|string';
            $attributes["messages.$key"] = $label;
            $rules["emails.$key"] = 'required_with:emails|array:subject,body';
            $rules["emails.$key.subject"] = ['required_with:emails', 'string', 'max:180', 'not_regex:/[\r\n]/'];
            $rules["emails.$key.body"] = 'required_with:emails|string|max:12000';
            $attributes["emails.$key.subject"] = 'objet — '.$label;
            $attributes["emails.$key.body"] = 'e-mail — '.$label;
            $rules["formatted.$key"] = 'nullable|string';
            $attributes["formatted.$key"] = $label;
        }

        $data = $request->validate($rules, [], $attributes);
        $saved = $this->config->getConfig()?->admission_sms_templates ?? [];
        $formatted = $saved['formatted'] ?? [];
        $previous = AdmissionSmsTemplates::resolve($saved);
        foreach ($data['messages'] as $key => $message) {
            if (isset($data['formatted'][$key])) {
                $formatted[$key] = $this->cleanFormatting($data['formatted'][$key]);
            } elseif ($message !== $previous[$key]) {
                unset($formatted[$key]);
            }
        }

        $this->config->updateOrCreate([
            'admission_sms_templates' => array_replace(
                $saved, $data['messages'], ['formatted' => $formatted],
                array_intersect_key($data, array_flip(['emails', 'reclamation_email'])),
            ),
        ]);

        return to_route('admin.parametrage.configuration-messages.index')->with('success', 'Les modèles de messages ont été enregistrés.');
    }

    private function cleanFormatting(string $html): string
    {
        $document = new \DOMDocument;
        $document->loadHTML('<!DOCTYPE html><meta charset="UTF-8"><body>'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        $render = function (\DOMNode $node) use (&$render): string {
            if ($node instanceof \DOMText) {
                return e($node->data);
            }

            if (! $node instanceof \DOMElement || in_array($node->tagName, ['script', 'style'], true)) {
                return '';
            }

            $tag = $node->tagName;
            if ($tag === 'br') {
                return '<br>';
            }

            $content = '';
            foreach ($node->childNodes as $child) {
                $content .= $render($child);
            }

            if (in_array($tag, ['p', 'div', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li'], true)) {
                return "<$tag>$content</$tag>";
            }

            if ($tag === 'span') {
                $style = $node->getAttribute('style');
                if (preg_match('/(?:^|;)\s*font-size:\s*(12|14|16|18|20|24|28|32)px\s*(?:;|$)/i', $style, $size)) {
                    $content = '<span style="font-size: '.$size[1].'px">'.$content.'</span>';
                }
                if (preg_match('/(?:^|;)\s*text-decoration(?:-line)?:\s*underline\s*(?:;|$)/i', $style)) {
                    $content = '<u>'.$content.'</u>';
                }
            }

            return $content;
        };

        $body = $document->getElementsByTagName('body')->item(0);

        return $body ? $render($body) : '';
    }
}

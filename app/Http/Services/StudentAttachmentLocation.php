<?php
namespace App\Http\Services;

class StudentAttachmentLocation
{
    public function remoteUrl(string $stored): ?string
    {
        $parts = parse_url(trim($stored));
        if (!$parts || ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) return null;
        $origin = parse_url(config('student_documents.legacy_origin', 'https://croudedaloa.soumafe.ci'));
        if (strtolower($parts['host'] ?? '') !== strtolower($origin['host'] ?? '')) return null;
        $path = rawurldecode($parts['path'] ?? '');
        if (!preg_match('#^/admin/assets/images/etudiant-document/([a-zA-Z0-9_. -]+)$#D', $path, $match)
            || in_array($match[1], ['.', '..'], true)) return null;
        // Ne reprendre ni paramètres ni fragments provenant de la valeur enregistrée.
        return 'https://'.$origin['host'].'/admin/assets/images/etudiant-document/'.rawurlencode($match[1]);
    }
}

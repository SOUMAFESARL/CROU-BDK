@adminroute('admin.etudiant.contrat')
<a href="{{ route('admin.etudiant.contrat', ['etudiant' => $student->id_etudiant]) }}"
   target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"
   title="Ouvrir le contrat PDF généré depuis le dossier dans un nouvel onglet">
    <i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Consulter le contrat
</a>
@endadminroute

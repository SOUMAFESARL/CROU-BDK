<?php
namespace App\Http\Services;
use App\Models\Etudiant;
use Illuminate\Validation\ValidationException;

class StudentIdentityResolver
{
    public function find(?string $phone, ?string $identity): ?Etudiant
    {
        $phone=preg_replace('/\D/','',(string)$phone);
        if (strlen($phone)===15 && str_starts_with($phone,'00225')) $phone=substr($phone,5);
        if (strlen($phone)===13 && str_starts_with($phone,'225')) $phone=substr($phone,3);
        $identity=mb_strtoupper(preg_replace('/[\s-]+/u','',(string)$identity));
        if ($phone==='' && $identity==='') return null;
        $phoneSql="telephone1";
        foreach ([' ', '+', '-', '.', '(', ')', '/'] as $char) $phoneSql="REPLACE($phoneSql, '$char', '')";
        $identitySql="UPPER(REPLACE(REPLACE(numero_piece, ' ', ''), '-', ''))";
        $matches=Etudiant::withTrashed()->where(function($q)use($phone,$identity,$phoneSql,$identitySql){
            if ($phone!=='') $q->whereIn(\Illuminate\Support\Facades\DB::raw($phoneSql),
                strlen($phone)===10 ? [$phone,'225'.$phone,'00225'.$phone] : [$phone]);
            if ($identity!=='') $q->orWhereRaw("$identitySql = ?",[$identity]);
        })->limit(2)->get();
        if ($matches->count()>1) throw ValidationException::withMessages([
            'telephone1'=>'Le téléphone et la pièce correspondent à plusieurs fiches. Faites vérifier les doublons avant de continuer.',
        ]);
        $student=$matches->first();
        if ($student?->trashed()) throw ValidationException::withMessages([
            'telephone1'=>'Cette fiche a été supprimée. Faites vérifier le compte par l’administration.',
        ]);
        return $student;
    }

    public function existingForAdmin(array $data): ?Etudiant
    {
        abort_unless(auth('admin')->check(),403);
        return $this->find($data['telephone1']??null,$data['numero_piece']??null);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalDocumentVersion extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['snapshot'];
    protected $casts = ['snapshot' => 'array', 'legal_document_id' => 'integer', 'revision' => 'integer'];

    public function document() { return $this->belongsTo(LegalDocument::class, 'legal_document_id'); }
}

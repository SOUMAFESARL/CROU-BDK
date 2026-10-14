<article class="user-list-card">
    <div class="user-list-card-heading">
        <div class="user-list-card-avatar" aria-hidden="true"
             x-text="String(item.name || '').trim().split(/\s+/).slice(0, 2).map(word => Array.from(word)[0] || '').join('').toUpperCase() || 'U'"></div>
        <div class="user-list-card-identity">
            @if(in_array('genre.name', $fields, true))
                <p class="user-list-card-civility" x-text="item.genre?.name || '—'"></p>
            @endif
            <h3 x-text="item.name || '—'"></h3>
        </div>
    </div>
    <div class="user-list-card-role">
        <i class="fas fa-shield-alt" aria-hidden="true"></i>
        <span x-text="item.role?.name || 'Rôle non renseigné'"></span>
    </div>
    <dl class="user-list-card-contact"><div><dt>Statut</dt><dd x-text="item.statut === 'active' ? 'Actif' : 'Inactif'"></dd></div>
        @if(in_array('email', $fields, true))
            <div>
                <dt><i class="fas fa-envelope" aria-hidden="true"></i> Email</dt>
                <dd x-text="item.email || '—'"></dd>
            </div>
        @endif
        @if(in_array('telephone_principal', $fields, true))
            <div>
                <dt><i class="fas fa-phone" aria-hidden="true"></i> Téléphone</dt>
                <dd x-text="item.telephone_principal || '—'"></dd>
            </div>
        @endif
    </dl>
    @if(isset($actionRoutes['status']))
        <form method="POST" :action="generateRoute(actionRoutes.status, item)"
              x-show="String(item[idField]) !== {{ \Illuminate\Support\Js::from((string) auth('admin')->id()) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="statut" :value="item.statut === 'active' ? 'inactive' : 'active'">
            <button type="submit" class="btn btn-outline-secondary w-100" x-text="item.statut === 'active' ? 'Désactiver' : 'Activer'"></button>
        </form>
    @endif
    @if(isset($actionRoutes['view']) || isset($actionRoutes['edit']))
        <div class="user-list-card-actions">
            @if(isset($actionRoutes['view']))
                <a :href="generateRoute(actionRoutes.view, item)" class="user-list-card-view"
                   :aria-label="'Voir la fiche de ' + (item.name || 'cet utilisateur')">
                    <i class="fas fa-eye" aria-hidden="true"></i> Voir
                </a>
            @endif
            @if(isset($actionRoutes['edit']))
                <a :href="generateRoute(actionRoutes.edit, item)" class="user-list-card-edit"
                   :aria-label="'Modifier ' + (item.name || 'cet utilisateur')">
                    <i class="fas fa-edit" aria-hidden="true"></i> Modifier
                </a>
            @endif
        </div>
    @endif
</article>

@once
    @push('styles')
        <style>
            .user-list-card {
                display: flex;
                flex-direction: column;
                gap: 18px;
                min-width: 0;
                height: 100%;
                padding: 22px;
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                box-shadow: 0 3px 12px rgba(15, 23, 42, .04);
                transition: border-color .2s, box-shadow .2s;
            }
            .user-list-card:hover { border-color: #a7cbb5; box-shadow: 0 6px 20px rgba(15, 23, 42, .08); }
            .user-list-card-heading { display: flex; align-items: center; gap: 14px; }
            .user-list-card-avatar {
                display: flex; align-items: center; justify-content: center; flex-shrink: 0;
                width: 52px; height: 52px; border-radius: 16px;
                background: #eaf5ee; color: #1f7c43; font-size: 20px; font-weight: 700;
            }
            .user-list-card-identity { min-width: 0; }
            .user-list-card-civility { margin: 0 0 4px; color: #64748b; font-size: var(--app-font-min, 15px); }
            .user-list-card h3 { margin: 0; color: #0f172a; font-size: 17px; font-weight: 600; overflow-wrap: anywhere; }
            .user-list-card-role { display: flex; align-items: center; gap: 8px; align-self: flex-start; padding: 7px 10px; border-radius: 8px; background: #f1f5f9; color: #475569; font-size: var(--app-font-min, 15px); }
            .user-list-card-role span { overflow-wrap: anywhere; min-width: 0; }
            .user-list-card-contact { display: grid; gap: 16px; margin: 0; flex: 1; }
            .user-list-card-contact dt { display: flex; align-items: center; gap: 8px; color: #64748b; font-size: var(--app-font-min, 15px); font-weight: 400; }
            .user-list-card-contact dd { margin: 5px 0 0; color: #0f172a; font-size: var(--app-font-min, 15px); overflow-wrap: anywhere; }
            .user-list-card-actions { display: flex; flex-wrap: wrap; gap: 10px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
            .user-list-card-actions a { display: inline-flex; justify-content: center; align-items: center; gap: 7px; flex: 1; padding: 9px 12px; border-radius: 8px; text-decoration: none; font-size: var(--app-font-min, 15px); font-weight: 500; }
            .user-list-card-view { background: #1f7c43; color: #fff; border: 1px solid #1f7c43; }
            .user-list-card-view:hover { background: #196536; color: #fff; }
            .user-list-card-edit { background: #fff; color: #475569; border: 1px solid #cbd5e1; }
            .user-list-card-edit:hover { background: #f8fafc; color: #0f172a; }
            .user-list-card-actions a:focus-visible { outline: 3px solid #86b99a; outline-offset: 3px; }
        </style>
    @endpush
@endonce

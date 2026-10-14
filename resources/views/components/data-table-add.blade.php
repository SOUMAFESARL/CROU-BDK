@props([
    'items' => [],
    'perPage' => 10,
    'tabs' => ['All', 'Particulier', 'Entreprise'],
    'tabFilterField' => 'type_proprietaire',
    'showTabs' => true,
    'title' => 'Products List',
    'description' => '',
    'showExport' => true,
    'showBack' => true,
    'showAdd' => true,
    'addName' => 'Ajouter',
    'addRoute' => '#',
    'backRoute' => '#',
    'exportRoute' => '#',
    'actionRoutes' => [
        'view' => '#',
        'edit' => '#',
        'delete' => '#',
    ],
    'routeParams' => ['id' => 'id'],
    'columns' => ['Nom', 'Category', 'Brand', 'Status', 'Price', 'Date', 'Actions'],
    'fields' => ['name', 'category', 'brand', 'status', 'price', 'date'],
    'columnTypes' => [],
    'statusClasses' => [],
    'idField' => 'id',
    'cardLayout' => 'default',
])

@php
    use Illuminate\Support\Js;
    if (request()->routeIs('admin.users.*') && !auth('admin')->user()?->hasPermission('utilisateurs.personal')) {
        foreach ($fields as $index => $field) {
            if (in_array($field, ['telephone_principal', 'telephone_secondaire', 'email', 'email_secondaire', 'adresse', 'numcni', 'dateNaiss'], true)) {
                unset($fields[$index], $columns[$index]);
            }
        }
        $fields = array_values($fields);
        $columns = array_values($columns);
    }
    // Only display/search fields and identifiers are sent to Alpine, never whole models.
    $visibleFields = array_unique(array_merge($fields, [$idField, $tabFilterField], array_values($routeParams)));
    if ($cardLayout === 'users') $visibleFields[] = 'statut';
    $items = collect($items)->map(function ($item) use ($visibleFields) {
        $row = [];
        foreach ($visibleFields as $field) {
            \Illuminate\Support\Arr::set($row, $field, data_get($item, $field));
        }
        return $row;
    });
    if (request()->routeIs('admin.*')) {
        $access = app(\App\Services\AdminAccess::class);
        $actor = auth('admin')->user();
        $showAdd = $showAdd && $access->allowsUrl($actor, $addRoute);
        $showExport = $showExport && $access->allowsUrl($actor, $exportRoute);
        $actionRoutes = array_filter($actionRoutes, fn ($url, $action) =>
            $access->allowsUrl($actor, $url, $action === 'delete' ? 'DELETE' : ($action === 'status' ? 'PUT' : 'GET')), ARRAY_FILTER_USE_BOTH);
    }
@endphp

<div class="overflow-hidden rounded-[20px] bg-white shadow-[0_8px_24px_rgba(0,0,0,0.08)]"
     x-data="{
        selectAll: false,
        selected: [],
        items: {!! Js::from($items) !!},
        currentPage: 1,
        perPage: {{ $perPage }},
        sortBy: '',
        sortAsc: true,
        viewMode: 'table',
        searchQuery: '',

        showTabs: {{ $showTabs ? 'true' : 'false' }},
        tabs: {{ Js::from($tabs) }},
        activeTab: '{{ count($tabs) > 0 ? $tabs[0] : 'All' }}',
        tabFilterField: '{{ $tabFilterField }}',

        actionRoutes: {{ Js::from($actionRoutes) }},
        routeParams: {{ Js::from($routeParams) }},
        idField: '{{ $idField }}',

        // Fonction pour générer les routes dynamiquement
        generateRoute(route, item) {
            if (!route || route === '#') return '#';

            let generatedRoute = route;

            // Remplacez chaque paramètre défini dans routeParams
            Object.keys(this.routeParams).forEach(param => {
                const placeholder = ':' + param;
                if (generatedRoute.includes(placeholder)) {
                    const itemField = this.routeParams[param];
                    const value = item[itemField] || item[this.idField];
                    if (value) {
                        generatedRoute = generatedRoute.replace(placeholder, value);
                    }
                }
            });

            return generatedRoute;
        },

        get filteredItems() {
            return this.items.filter(item => {
                const matchesTab =
                    !this.showTabs ||
                    this.activeTab === 'All' ||
                    (item[this.tabFilterField] &&
                     String(item[this.tabFilterField]).toLowerCase() === this.activeTab.toLowerCase());

                const matchesSearch =
                    !this.searchQuery ||
                    Object.values(item).some(value =>
                        value !== null &&
                        value !== undefined &&
                        String(value).toLowerCase().includes(this.searchQuery.toLowerCase())
                    );

                return matchesTab && matchesSearch;
            });
        },

        get totalPages() {
            return Math.ceil(this.filteredItems.length / this.perPage) || 1;
        },

        get paginatedItems() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredItems.slice(start, start + this.perPage);
        },

        startItem() {
            if (!this.filteredItems.length) return 0;
            return (this.currentPage - 1) * this.perPage + 1;
        },

        endItem() {
            return Math.min(this.currentPage * this.perPage, this.filteredItems.length);
        },

        nextPage() {
            if (this.currentPage < this.totalPages) this.currentPage++;
        },

        prevPage() {
            if (this.currentPage > 1) this.currentPage--;
        },

        goToPage(page) {
            this.currentPage = page;
        },

        changeTab(tab) {
            this.activeTab = tab;
            this.currentPage = 1;
        },

        countItemsByTab(tab) {
            if (tab === 'All') return this.items.length;
            return this.items.filter(item => {
                const itemValue = item[this.tabFilterField];
                if (!itemValue) return false;
                return String(itemValue).toLowerCase() === tab.toLowerCase();
            }).length;
        },

        getValue(item, key) {
            return item?.[key] ?? '—';
        },

        formatDate(dateString) {
            if (!dateString) return '—';
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('fr-FR');
            } catch (e) {
                return dateString;
            }
        },

        sortItems(field) {
            if (this.sortBy === field) {
                this.sortAsc = !this.sortAsc;
            } else {
                this.sortBy = field;
                this.sortAsc = true;
            }

            const sortedItems = [...this.items].sort((a, b) => {
                let valA = a[field];
                let valB = b[field];

                if (valA == null) valA = '';
                if (valB == null) valB = '';

                if (typeof valA === 'string') valA = valA.toLowerCase();
                if (typeof valB === 'string') valB = valB.toLowerCase();

                if (valA < valB) return this.sortAsc ? -1 : 1;
                if (valA > valB) return this.sortAsc ? 1 : -1;
                return 0;
            });

            this.items = sortedItems;
        }
     }">

    <!-- Header -->
    <div class="flex flex-col justify-between gap-5 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">{{ $title }}</h3>
            <p class="text-sm text-gray-500">{{ $description }}</p>
        </div>

        <div class="flex gap-3">
            @if($showExport)
                <a href="{{ $exportRoute }}" class="inline-flex items-center justify-center gap-2 rounded-[20px] bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50">
                    Export
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M16.667 13.3333V15.4166C16.667 16.1069 15.417 16.6666 15.417 16.6666H4.58295C3.89259 16.6666 3.33295 16.1069 3.33295 15.4166V13.3333M10.0013 13.3333L10.0013 3.33325M6.14547 9.47942L9.99951 13.331L13.8538 9.47942" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </a>
            @endif

            @if($showBack)
                <a href="{{ $backRoute }}" class="inline-flex items-center justify-center gap-2 rounded-[20px] bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50">
                    Retour
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M16.667 10H3.33366M3.33366 10L8.33366 15M3.33366 10L8.33366 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            @endif

            @if($showAdd)
                <a href="{{ $addRoute }}" class="bg-yellow-600 hover:bg-yellow-500 inline-flex items-center justify-center gap-2 rounded-[20px] px-4 py-3 text-sm font-medium text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M5 10.0002H15.0006M10.0002 5V15.0006" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                    {{$addName}}
                </a>
            @endif
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="border-b border-gray-200 px-4 py-4">
        <div class="flex gap-3 sm:justify-between flex-wrap">
            <div class="relative flex-1 sm:flex-auto">
                <span class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500">
                    <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z" fill=""></path>
                    </svg>
                </span>
                <input type="text" placeholder="Rechercher..." x-model="searchQuery" @input="currentPage = 1" class="h-11 w-full rounded-[20px] border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1F7C43] sm:w-[300px] sm:min-w-[300px]">
            </div>

            <div x-show="showTabs" x-cloak class="h-11 items-center gap-0.5 rounded-[20px] bg-gray-100 p-0.5 inline-flex overflow-x-auto">
                <template x-for="tab in tabs" :key="tab">
                    <button
                            @click="changeTab(tab)"
                            :class="activeTab === tab ? 'shadow text-gray-900 bg-white' : 'text-gray-500 hover:text-gray-900'"
                            class="h-10 rounded-[20px] px-3 py-2 font-medium text-sm flex items-center transition-colors focus:outline-none focus:ring-0 focus-visible:outline-none focus-visible:ring-0"
                    >
                        <span x-text="tab"></span>
                        <span
                                :class="activeTab === tab ? 'bg-white/20 text-gray-900' : 'bg-gray-100 text-gray-600'"
                                class="ml-3 px-2 py-0.5 text-xs rounded-[20px]"
                                x-text="countItemsByTab(tab)"
                        ></span>
                    </button>
                </template>
            </div>

            <div class="flex items-center gap-2">
                <button
                        @click="viewMode = 'table'"
                        :class="viewMode === 'table' ? 'bg-[#1F7C43] text-white border-[#1F7C43]' : 'bg-white text-gray-700 border-gray-300'"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-[20px] border px-4 py-2.5 text-sm font-medium sm:w-auto"
                        title="Table view">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                </button>

                <button
                        @click="viewMode = 'cards'"
                        :class="viewMode === 'cards' ? 'bg-[#1F7C43] text-white border-[#1F7C43]' : 'bg-white text-gray-700 border-gray-300'"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-[20px] border px-4 py-2.5 text-sm font-medium sm:w-auto"
                        title="Card view">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h7v7H3V3zm11 0h7v7h-7V3zM3 14h7v7H3v-7zm11 0h7v7h-7v-7z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <div x-show="filteredItems.length === 0" x-cloak class="p-12 text-center">
        <div class="mx-auto max-w-md my-6">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">Aucun élément trouvé</h3>
            <p class="mt-2 text-sm text-gray-500">Aucun résultat ne correspond à votre recherche. Essayez de modifier vos critères de recherche.</p>
            <div class="mt-6">
                <button @click="searchQuery = ''; activeTab = 'All'" class="inline-flex items-center rounded-[20px] bg-[#1F7C43] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#196836]">
                    Réinitialiser la recherche
                </button>
            </div>
        </div>
    </div>

    <!-- Table View -->
    <div x-show="viewMode === 'table' && filteredItems.length > 0" x-cloak>
        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                <tr class="border-b border-gray-200">
                    @foreach($columns as $index => $column)
                        @php $field = $fields[$index] ?? null; @endphp
                        @if($field)
                            <th class="cursor-pointer px-4 py-4 text-left text-xs font-medium text-gray-500" @click="sortItems('{{ $field }}')">
                                <div class="flex items-center gap-3">
                                    <p class="font-medium text-gray-500">{{ $column }}</p>
                                    <span class="flex flex-col gap-0.5">
                                        <svg :class="sortBy === '{{ $field }}' && sortAsc ? 'text-gray-500' : 'text-gray-300'" width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M4.40962 0.585167C4.21057 0.300808 3.78943 0.300807 3.59038 0.585166L1.05071 4.21327C0.81874 4.54466 1.05582 5 1.46033 5H6.53967C6.94418 5 7.18126 4.54466 6.94929 4.21327L4.40962 0.585167Z" fill="currentColor"></path>
                                        </svg>
                                        <svg :class="sortBy === '{{ $field }}' && !sortAsc ? 'text-gray-500' : 'text-gray-300'" width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M4.40962 4.41483C4.21057 4.69919 3.78943 4.69919 3.59038 4.41483L1.05071 0.786732C0.81874 0.455343 1.05582 0 1.46033 0H6.53967C6.94418 0 7.18126 0.455342 6.94929 0.786731L4.40962 4.41483Z" fill="currentColor"></path>
                                        </svg>
                                    </span>
                                </div>
                            </th>
                        @else
                            <th class="px-4 py-4 text-left text-xs font-medium text-gray-500">{{ $column }}</th>
                        @endif
                    @endforeach
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                <template x-for="item in paginatedItems" :key="item[idField]">
                    <tr class="transition hover:bg-gray-50">
                        @foreach($fields as $field)
                            <td class="px-4 py-4 whitespace-nowrap">
                                @php
                                    $type = $columnTypes[$field] ?? null;
                                    $statusClass = $statusClasses[$field] ?? 'statusClass';
                                @endphp
                                @if($type === 'image')
                                    <div class="flex items-center gap-3">
                                        <img :src="item.{{ $field }}" class="h-12 w-12 rounded-[20px]" alt="">
                                        <span class="text-sm font-medium text-gray-700" x-text="item.name"></span>
                                    </div>
                                @elseif($type === 'badge')
                                    <span :class="item.{{ $statusClass }}" class="rounded-[20px] px-2 py-0.5 font-medium text-xs" x-text="item.{{ $field }}"></span>
                                @elseif($type === 'date')
                                    <p class="text-sm text-gray-700" x-text="formatDate(item.{{ $field }})"></p>
                                @else
                                    <p class="text-sm text-gray-700" x-text="item.{{ $field }}"></p>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="flex gap-2">
                                @if(isset($actionRoutes['view']))
                                    <a :href="generateRoute(actionRoutes.view, item)"
                                       class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-[20px] hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#1F7C43]">
                                        Voir
                                    </a>
                                @endif

                                @if(isset($actionRoutes['edit']))
                                    <a :href="generateRoute(actionRoutes.edit, item)"
                                       class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-[20px] hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#1F7C43]">
                                        Modifier
                                    </a>
                                @endif

                                @if(isset($actionRoutes['delete']))
                                    <a :href="generateRoute(actionRoutes.delete, item)"
                                       @click="return confirm('Confirmer la suppression?')"
                                       class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium text-white bg-red-500 border border-red-500 rounded-[20px] hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500">
                                        Supprimer
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Card View -->
    <div x-show="viewMode === 'cards' && filteredItems.length > 0" x-cloak class="p-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <template x-for="item in paginatedItems" :key="item[idField]">
                @if($cardLayout === 'users')
                    @include('admin.users._card')
                @else
                <div class="rounded-[20px] border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md min-h-[200px] flex flex-col">
                    <div class="flex items-center gap-3 mb-4">
                        <img :src="item.image" class="h-12 w-12 rounded-[20px]" alt="">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900" x-text="item.name"></h3>
                            <p class="text-xs text-gray-500" x-text="item.category"></p>
                        </div>
                    </div>
                    <div class="flex-grow grid grid-cols-2 gap-2 text-xs mb-4">
                        <div>
                            <span class="text-gray-500">Brand:</span>
                            <span class="text-gray-900" x-text="item.brand"></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Status:</span>
                            <span :class="item.statusClass" class="rounded-[20px] px-1 py-0.5 font-medium" x-text="item.status"></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Price:</span>
                            <span class="text-gray-900" x-text="item.price"></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Date:</span>
                            <span class="text-gray-900" x-text="formatDate(item.date)"></span>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-auto">
                        <div class="flex gap-2 w-full justify-center">
                            @if(isset($actionRoutes['view']))
                                <a :href="generateRoute(actionRoutes.view, item)"
                                   class="inline-flex items-center justify-center p-2.5 text-gray-700 bg-white border border-gray-300 rounded-[20px] hover:bg-gray-50 transition-colors duration-200"
                                   title="Voir">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                            @endif

                            @if(isset($actionRoutes['edit']))
                                <a :href="generateRoute(actionRoutes.edit, item)"
                                   class="inline-flex items-center justify-center p-2.5 text-gray-700 bg-white border border-gray-300 rounded-[20px] hover:bg-gray-50 transition-colors duration-200"
                                   title="Modifier">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                            @endif

                            @if(isset($actionRoutes['delete']))
                                <a :href="generateRoute(actionRoutes.delete, item)"
                                   @click="return confirm('Confirmer la suppression?')"
                                   class="inline-flex items-center justify-center p-2.5 text-white bg-red-500 border border-red-500 rounded-[20px] hover:bg-red-600 transition-colors duration-200"
                                   title="Supprimer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </template>
        </div>
    </div>

    <!-- Pagination -->
    <div x-show="filteredItems.length > 0" x-cloak class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-4">
        <div class="flex items-center gap-2 text-sm text-gray-700">
            <span>Affichage de</span>
            <span class="font-medium" x-text="startItem()"></span>
            <span>à</span>
            <span class="font-medium" x-text="endItem()"></span>
            <span>sur</span>
            <span class="font-medium" x-text="filteredItems.length"></span>
            <span>résultats</span>
        </div>

        <div class="flex items-center gap-2">
            <button @click="prevPage()" :disabled="currentPage === 1" class="flex h-8 w-8 items-center justify-center rounded-[20px] border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <template x-for="page in totalPages" :key="page">
                <button
                        @click="currentPage = page"
                        :class="[
                        'h-8 w-8 rounded-full border transition-colors duration-200',
                        currentPage === page
                            ? 'bg-[#1F7C43] text-white border-[#1F7C43]'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                    ]"
                        x-text="page"
                ></button>
            </template>

            <button @click="nextPage()" :disabled="currentPage === totalPages" class="flex h-8 w-8 items-center justify-center rounded-[20px] border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>
</div>

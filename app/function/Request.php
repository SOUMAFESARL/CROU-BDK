<?php

use App\Repositories\CampagneRepository;

if (! function_exists('listeCampagnes')) {
function listeCampagnes()
{
    $campagne = app(CampagneRepository::class);
    return  $campagne->getAll();

}}

function documentationApi()
{
    return [
        'base_url' => 'http://dev3.soumafe.ci/api/V1',
        'modules' => [
            'users' => [
                'id' => 'users',
                'title' => 'Utilisateurs',
                'icon' => 'bi-people',
                'description' => 'Gestion des utilisateurs de l\'application — CRUD complet',
                'order' => 1,
                'visible' => true,
                'endpoints' => [
                    [
                        'id' => 'get-users',
                        'method' => 'GET',
                        'path' => '/users',
                        'summary' => 'Liste des utilisateurs',
                        'description' => 'Récupère la liste des utilisateurs avec pagination et filtres',
                        'operation_id' => 'listUsers',
                        'tags' => ['users'],
                        'parameters' => [
                            'query' => [
                                [
                                    'name' => 'page',
                                    'type' => 'integer',
                                    'format' => 'int32',
                                    'description' => 'Numéro de page pour la pagination',
                                    'required' => false,
                                    'default' => 1,
                                    'minimum' => 1,
                                    'example' => 1
                                ],
                                [
                                    'name' => 'limit',
                                    'type' => 'integer',
                                    'format' => 'int32',
                                    'description' => "Nombre d'utilisateurs par page",
                                    'required' => false,
                                    'default' => 10,
                                    'maximum' => 100,
                                    'minimum' => 1,
                                    'example' => 10
                                ],
                                [
                                    'name' => 'role',
                                    'type' => 'string',
                                    'description' => 'Filtrer par rôle',
                                    'required' => false,
                                    'enum' => ['admin', 'user', 'moderator'],
                                    'example' => 'admin'
                                ],
                                [
                                    'name' => 'search',
                                    'type' => 'string',
                                    'description' => 'Recherche dans le nom et l\'email',
                                    'required' => false,
                                    'min_length' => 2,
                                    'example' => 'Jean'
                                ],
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Succès',
                                'content' => [
                                    'application/json' => [
                                        'example' => '{
                                            "status": "success",
                                            "data": [
                                                {
                                                    "id": 1,
                                                    "name": "Jean Dupont",
                                                    "email": "jean@exemple.com",
                                                    "role": "admin",
                                                    "created_at": "2024-01-15T10:30:00Z"
                                                }
                                            ],
                                            "pagination": {
                                                "page": 1,
                                                "limit": 10,
                                                "total": 150,
                                                "pages": 15
                                            }
                                        }'
                                    ]
                                ]
                            ],
                            '400' => [
                                'description' => 'Requête invalide',
                                'content' => [
                                    'application/json' => [
                                        'example' => '{
                                            "status": "error",
                                            "message": "Paramètres de pagination invalides"
                                        }'
                                    ]
                                ]
                            ],
                            '401' => [
                                'description' => 'Non authentifié',
                                'content' => [
                                    'application/json' => [
                                        'example' => '{
                                            "status": "error",
                                            "message": "Token d\'authentification manquant ou invalide"
                                        }'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 'create-user',
                        'method' => 'POST',
                        'path' => '/users',
                        'summary' => 'Créer un utilisateur',
                        'description' => 'Crée un nouvel utilisateur dans le système',
                        'operation_id' => 'createUser',
                        'tags' => ['users'],
                        'parameters' => [
                            'body' => [
                                [
                                    'name' => 'name',
                                    'type' => 'string',
                                    'description' => 'Nom complet de l\'utilisateur',
                                    'required' => true,
                                    'min_length' => 2,
                                    'max_length' => 100,
                                    'example' => 'Marie Martin'
                                ],
                                [
                                    'name' => 'email',
                                    'type' => 'string',
                                    'format' => 'email',
                                    'description' => 'Adresse email valide',
                                    'required' => true,
                                    'example' => 'marie@exemple.com'
                                ],
                                [
                                    'name' => 'password',
                                    'type' => 'string',
                                    'format' => 'password',
                                    'description' => 'Mot de passe',
                                    'required' => true,
                                    'min_length' => 8,
                                    'example' => '********'
                                ],
                                [
                                    'name' => 'role',
                                    'type' => 'string',
                                    'description' => 'Rôle de l\'utilisateur',
                                    'required' => false,
                                    'default' => 'user',
                                    'enum' => ['admin', 'user', 'moderator'],
                                    'example' => 'user'
                                ],
                            ]
                        ],
                        'request_examples' => [
                            'application/json' => '{
                                "name": "Marie Martin",
                                "email": "marie@exemple.com",
                                "password": "securePassword123",
                                "role": "user"
                            }'
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Utilisateur créé',
                                'content' => [
                                    'application/json' => [
                                        'example' => '{
                                            "status": "success",
                                            "message": "Utilisateur créé avec succès",
                                            "data": {
                                                "id": 2,
                                                "name": "Marie Martin",
                                                "email": "marie@exemple.com",
                                                "role": "user",
                                                "created_at": "2024-01-20T14:25:00Z"
                                            }
                                        }'
                                    ]
                                ]
                            ],
                            '400' => [
                                'description' => 'Données invalides',
                                'content' => [
                                    'application/json' => [
                                        'example' => '{
                                            "status": "error",
                                            "message": "Validation échouée",
                                            "errors": {
                                                "email": "L\'email est déjà utilisé",
                                                "password": "Le mot de passe doit contenir au moins 8 caractères"
                                            }
                                        }'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 'get-user',
                        'method' => 'GET',
                        'path' => '/users/{id}',
                        'summary' => 'Récupérer un utilisateur',
                        'description' => 'Récupère les détails d\'un utilisateur spécifique',
                        'operation_id' => 'getUser',
                        'tags' => ['users'],
                        'parameters' => [
                            'path' => [
                                [
                                    'name' => 'id',
                                    'type' => 'integer',
                                    'format' => 'int64',
                                    'description' => 'Identifiant unique de l\'utilisateur',
                                    'required' => true,
                                    'example' => 1
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Succès',
                                'content' => [
                                    'application/json' => [
                                        'example' => '{
                                            "status": "success",
                                            "data": {
                                                "id": 1,
                                                "name": "Jean Dupont",
                                                "email": "jean@exemple.com",
                                                "role": "admin",
                                                "created_at": "2024-01-15T10:30:00Z",
                                                "last_login": "2024-01-19T08:45:00Z",
                                                "stats": {
                                                    "posts": 45,
                                                    "followers": 1200,
                                                    "following": 350
                                                }
                                            }
                                        }'
                                    ]
                                ]
                            ],
                            '404' => [
                                'description' => 'Utilisateur non trouvé',
                                'content' => [
                                    'application/json' => [
                                        'example' => '{
                                            "status": "error",
                                            "message": "Utilisateur avec l\'id 999 non trouvé"
                                        }'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 'update-user',
                        'method' => 'PUT',
                        'path' => '/users/{id}',
                        'summary' => 'Mettre à jour un utilisateur',
                        'description' => 'Met à jour les informations d\'un utilisateur',
                        'operation_id' => 'updateUser',
                        'tags' => ['users'],
                        'parameters' => [
                            'path' => [
                                [
                                    'name' => 'id',
                                    'type' => 'integer',
                                    'format' => 'int64',
                                    'description' => 'Identifiant de l\'utilisateur à modifier',
                                    'required' => true,
                                    'example' => 1
                                ]
                            ],
                            'body' => [
                                [
                                    'name' => 'name',
                                    'type' => 'string',
                                    'description' => 'Nouveau nom',
                                    'required' => false,
                                    'min_length' => 2,
                                    'max_length' => 100,
                                    'example' => 'Jean Martin'
                                ],
                                [
                                    'name' => 'email',
                                    'type' => 'string',
                                    'format' => 'email',
                                    'description' => 'Nouvel email',
                                    'required' => false,
                                    'example' => 'jean.martin@exemple.com'
                                ],
                                [
                                    'name' => 'role',
                                    'type' => 'string',
                                    'description' => 'Nouveau rôle',
                                    'required' => false,
                                    'enum' => ['admin', 'user', 'moderator'],
                                    'example' => 'moderator'
                                ],
                            ]
                        ],
                        'request_examples' => [
                            'application/json' => '{
                                "name": "Jean Martin",
                                "email": "jean.martin@exemple.com",
                                "role": "moderator"
                            }'
                        ]
                    ],
                    [
                        'id' => 'delete-user',
                        'method' => 'DELETE',
                        'path' => '/users/{id}',
                        'summary' => 'Supprimer un utilisateur',
                        'description' => 'Supprime définitivement un utilisateur',
                        'operation_id' => 'deleteUser',
                        'tags' => ['users'],
                        'parameters' => [
                            'path' => [
                                [
                                    'name' => 'id',
                                    'type' => 'integer',
                                    'format' => 'int64',
                                    'description' => 'Identifiant de l\'utilisateur à supprimer',
                                    'required' => true,
                                    'example' => 1
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Utilisateur supprimé',
                                'content' => [
                                    'application/json' => [
                                        'example' => '{
                                            "status": "success",
                                            "message": "Utilisateur supprimé avec succès"
                                        }'
                                    ]
                                ]
                            ],
                            '404' => [
                                'description' => 'Utilisateur non trouvé'
                            ]
                        ]
                    ]
                ]
            ],
            'products' => [
                'id' => 'products',
                'title' => 'Produits',
                'icon' => 'bi-box-seam',
                'description' => 'Gestion du catalogue de produits — consultation et création',
                'order' => 2,
                'visible' => true,
                'endpoints' => [
                    [
                        'id' => 'get-products',
                        'method' => 'GET',
                        'path' => '/products',
                        'summary' => 'Liste des produits',
                        'description' => 'Récupère la liste des produits avec filtres',
                        'operation_id' => 'listProducts',
                        'tags' => ['products'],
                        'parameters' => [
                            'query' => [
                                [
                                    'name' => 'category',
                                    'type' => 'string',
                                    'description' => 'Filtrer par catégorie',
                                    'required' => false,
                                    'enum' => ['electronics', 'clothing', 'books'],
                                    'example' => 'electronics'
                                ],
                                [
                                    'name' => 'min_price',
                                    'type' => 'number',
                                    'format' => 'float',
                                    'description' => 'Prix minimum',
                                    'required' => false,
                                    'minimum' => 0,
                                    'example' => 10.99
                                ],
                                [
                                    'name' => 'max_price',
                                    'type' => 'number',
                                    'format' => 'float',
                                    'description' => 'Prix maximum',
                                    'required' => false,
                                    'minimum' => 0,
                                    'example' => 99.99
                                ],
                                [
                                    'name' => 'sort',
                                    'type' => 'string',
                                    'description' => 'Tri des résultats',
                                    'required' => false,
                                    'enum' => ['price_asc', 'price_desc', 'name_asc', 'name_desc'],
                                    'example' => 'price_asc'
                                ],
                            ]
                        ]
                    ],
                    [
                        'id' => 'create-product',
                        'method' => 'POST',
                        'path' => '/products',
                        'summary' => 'Créer un produit',
                        'description' => 'Crée un nouveau produit dans le catalogue',
                        'operation_id' => 'createProduct',
                        'tags' => ['products'],
                        'parameters' => [
                            'body' => [
                                [
                                    'name' => 'name',
                                    'type' => 'string',
                                    'description' => 'Nom du produit',
                                    'required' => true,
                                    'min_length' => 2,
                                    'max_length' => 200,
                                    'example' => 'Smartphone XYZ'
                                ],
                                [
                                    'name' => 'description',
                                    'type' => 'string',
                                    'description' => 'Description du produit',
                                    'required' => false,
                                    'max_length' => 1000,
                                    'example' => 'Un smartphone haut de gamme...'
                                ],
                                [
                                    'name' => 'price',
                                    'type' => 'number',
                                    'format' => 'float',
                                    'description' => 'Prix du produit',
                                    'required' => true,
                                    'minimum' => 0,
                                    'example' => 499.99
                                ],
                                [
                                    'name' => 'category',
                                    'type' => 'string',
                                    'description' => 'Catégorie du produit',
                                    'required' => false,
                                    'enum' => ['electronics', 'clothing', 'books'],
                                    'example' => 'electronics'
                                ],
                            ]
                        ]
                    ]
                ]
            ],
            'parametres' => [
                'id' => 'parametres',
                'title' => 'Paramètres',
                'icon' => 'bi-gear',
                'description' => 'Listes des APIs parametres',
                'order' => 3,
                'visible' => true,
                'endpoints' => [
                    [
                        'id' => 'get-regions',
                        'method' => 'GET',
                        'path' => '/parametrage/region',
                        'summary' => 'Liste des régions',
                        'description' => 'Récupère la liste des régions configurées',
                        'operation_id' => 'listRegions',
                        'tags' => ['parametrage'],
//                        'parameters' => [
//                            'query' => [
//                                [
//                                    'name' => 'pays_id',
//                                    'type' => 'integer',
//                                    'description' => 'Filtrer par pays',
//                                    'required' => false,
//                                    'example' => 1
//                                ],
//                                [
//                                    'name' => 'active',
//                                    'type' => 'boolean',
//                                    'description' => 'Filtrer par statut actif/inactif',
//                                    'required' => false,
//                                    'example' => true
//                                ],
//                            ]
//                        ],
//                        'request_examples' => [
//                            'application/json' => '{
//                                "pays_id": 1,
//                                "active": true
//                            }'
//                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Succès',
                                'content' => [
                                    'application/json' => [
                                        'example' => '{
                                            "status": "success",
                                            "data": [
                                                {
                                                    "id": 1,
                                                    "nom": "Abidjan",
                                                    "pays_id": 1,
                                                    "pays_nom": "Côte d\'Ivoire",
                                                    "active": true
                                                },
                                                {
                                                    "id": 2,
                                                    "nom": "Yamoussoukro",
                                                    "pays_id": 1,
                                                    "pays_nom": "Côte d\'Ivoire",
                                                    "active": true
                                                }
                                            ]
                                        }'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 'create-region',
                        'method' => 'POST',
                        'path' => '/parametrage/region',
                        'summary' => 'Créer une région',
                        'description' => 'Ajoute une nouvelle région dans le système',
                        'operation_id' => 'createRegion',
                        'tags' => ['parametrage'],
                        'parameters' => [
                            'body' => [
                                [
                                    'name' => 'nom',
                                    'type' => 'string',
                                    'description' => 'Nom de la région',
                                    'required' => true,
                                    'min_length' => 2,
                                    'max_length' => 100,
                                    'example' => 'Bouaké'
                                ],
                                [
                                    'name' => 'pays_id',
                                    'type' => 'integer',
                                    'description' => 'ID du pays associé',
                                    'required' => true,
                                    'example' => 1
                                ],
                                [
                                    'name' => 'active',
                                    'type' => 'boolean',
                                    'description' => 'Statut actif/inactif',
                                    'required' => false,
                                    'default' => true,
                                    'example' => true
                                ],
                            ]
                        ],
                        'request_examples' => [
                            'application/json' => '{
                                "nom": "Bouaké",
                                "pays_id": 1,
                                "active": true
                            }'
                        ]
                    ],
                    [
                        'id' => 'update-region',
                        'method' => 'PUT',
                        'path' => '/parametrage/region/{id}',
                        'summary' => 'Modifier une région',
                        'description' => 'Met à jour les informations d\'une région',
                        'operation_id' => 'updateRegion',
                        'tags' => ['parametrage'],
                        'parameters' => [
                            'path' => [
                                [
                                    'name' => 'id',
                                    'type' => 'integer',
                                    'description' => 'ID de la région à modifier',
                                    'required' => true,
                                    'example' => 1
                                ]
                            ],
                            'body' => [
                                [
                                    'name' => 'nom',
                                    'type' => 'string',
                                    'description' => 'Nouveau nom de la région',
                                    'required' => false,
                                    'example' => 'Bouaké (Mise à jour)'
                                ],
                                [
                                    'name' => 'active',
                                    'type' => 'boolean',
                                    'description' => 'Nouveau statut',
                                    'required' => false,
                                    'example' => false
                                ],
                            ]
                        ]
                    ],
                    [
                        'id' => 'delete-region',
                        'method' => 'DELETE',
                        'path' => '/parametrage/region/{id}',
                        'summary' => 'Supprimer une région',
                        'description' => 'Supprime une région du système',
                        'operation_id' => 'deleteRegion',
                        'tags' => ['parametrage'],
                        'parameters' => [
                            'path' => [
                                [
                                    'name' => 'id',
                                    'type' => 'integer',
                                    'description' => 'ID de la région à supprimer',
                                    'required' => true,
                                    'example' => 1
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'orders' => [
                'id' => 'orders',
                'title' => 'Commandes',
                'icon' => 'bi-cart3',
                'description' => 'Gestion des commandes clients',
                'order' => 4,
                'visible' => true,
                'empty' => true,
                'message' => 'Cette section contient les endpoints pour la gestion des commandes. Les fonctionnalités seront ajoutées prochainement.'
            ]
        ],
        'auth' => [
            'id' => 'auth',
            'title' => 'Authentification',
            'icon' => 'bi-shield-lock',
            'description' => 'Informations de sécurité et headers requis',
            'order' => 999,
            'info' => 'Toutes les routes (sauf spécifiées) nécessitent une authentification via token Bearer',
            'type' => 'bearer',
            'scheme' => 'bearer',
            'bearer_format' => 'JWT',
            'headers' => [
                [
                    'name' => 'Authorization',
                    'value' => 'Bearer {token}',
                    'description' => 'Token JWT pour l\'authentification',
                    'required' => true,
                    'example' => 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'
                ],
                [
                    'name' => 'Content-Type',
                    'value' => 'application/json',
                    'description' => 'Type de contenu pour les requêtes avec corps',
                    'required' => true,
                    'example' => 'application/json'
                ],
                [
                    'name' => 'Accept',
                    'value' => 'application/json',
                    'description' => 'Format de réponse souhaité',
                    'required' => false,
                    'default' => 'application/json',
                    'example' => 'application/json'
                ],
                [
                    'name' => 'X-Request-ID',
                    'value' => 'string',
                    'description' => 'Identifiant unique pour le tracing des requêtes',
                    'required' => false,
                    'example' => '550e8400-e29b-41d4-a716-446655440000'
                ]
            ],
            'endpoints' => [
                [
                    'path' => '/auth/login',
                    'method' => 'POST',
                    'description' => 'Endpoint d\'authentification (ne nécessite pas de token)'
                ],
                [
                    'path' => '/auth/register',
                    'method' => 'POST',
                    'description' => 'Endpoint d\'inscription (ne nécessite pas de token)'
                ],
                [
                    'path' => '/auth/refresh',
                    'method' => 'POST',
                    'description' => 'Rafraîchir le token'
                ]
            ]
        ]
    ];
}
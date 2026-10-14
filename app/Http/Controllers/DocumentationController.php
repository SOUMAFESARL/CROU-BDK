<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;

class DocumentationController extends Controller
{
    public function index()
    {
        $docs = $this->generateDocumentation();
        return view('admin.documentation.doc', compact('docs'));
    }

    private function generateDocumentation()
    {
        return [
            'base_url' => '/admin',
            'modules' => $this->getModules()
        ];
    }

    private function getModules()
    {
        // DÉFINIR MANUELLEMENT LES CONTROLLEURS
        $controllers = [
            // Module PARAMETRAGE
            'App\Http\Controllers\admin\parametrage\RegionController' => [
                'module' => 'Régions',
                'name' => 'Régions'
            ],
            'App\Http\Controllers\admin\parametrage\FiliereController' => [
                'module' => 'Filières',
                'name' => 'Filières'
            ],
            'App\Http\Controllers\admin\parametrage\VilleController' => [
                'module' => 'Villes',
                'name' => 'Villes'
            ],
            'App\Http\Controllers\admin\parametrage\DepartementController' => [
                'module' => 'Départements',
                'name' => 'Départements'
            ],
            'App\Http\Controllers\admin\parametrage\NiveauEtudeController' => [
                'module' => "Niveaux d'étude",
                'name' => "Niveaux d'étude"
            ],
            'App\Http\Controllers\admin\parametrage\TypeUtilisateurController' => [
                'module' => "Types d'utilisateur",
                'name' => "Types d'utilisateur"
            ],

            'App\Http\Controllers\admin\immobilier\parametrage\type_cite\TypeCiteController' => [
                'module' => "Types de cite",
                'name' => "Types de cite"
            ],

            // Module IMMOBILIER
            'App\Http\Controllers\admin\immobilier\parc_immobilier\ParcImmobilierController' => [
                'module' => 'Parc Immobilier',
                'name' => 'Parc Immobilier'
            ],

            'App\Http\Controllers\admin\immobilier\equipements\EquipementController' => [
                'module' => 'Équipements',
                'name' => 'Équipements'
            ],
            'App\Http\Controllers\admin\immobilier\parametrage\batiments_campagne\BatimentsCampagneController' => [
                'module' => 'Bâtiments Campagne',
                'name' => 'Bâtiments Campagne'
            ],

            // Module AUTHENTIFICATION
            'App\Http\Controllers\admin\auth\AuthController' => [
                'module' => 'Authentification',
                'name' => 'Authentification'
            ],

            // Module UTILISATEURS
            'App\Http\Controllers\admin\users\usersController' => [
                'module' => 'Utilisateurs',
                'name' => 'Utilisateurs'
            ],
        ];

        $modules = [];

        foreach ($controllers as $controllerClass => $info) {
            $endpoints = $this->analyzeController($controllerClass);

            if (!empty($endpoints)) {
                $module = $info['module'];

                if (!isset($modules[$module])) {
                    $modules[$module] = [
                        'title' => $module,
                        'icon' => $this->getModuleIcon($module),
                        'description' => $this->getModuleDescription($module),
                        'endpoints' => []
                    ];
                }

                $modules[$module]['endpoints'] = array_merge(
                    $modules[$module]['endpoints'],
                    $endpoints
                );
            }
        }

        return $modules;
    }

    private function analyzeController($controllerClass)
    {
        $endpoints = [];

        try {
            $reflection = new ReflectionClass($controllerClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            $routes = $this->getRoutesForController($controllerClass);

            // Vérifier si c'est AuthController
            $isAuthController = str_contains($controllerClass, 'AuthController');

            foreach ($methods as $method) {
                if ($method->class !== $controllerClass) continue;

                $methodName = $method->getName();
                if (strpos($methodName, '__') === 0) continue;
                if (in_array($methodName, ['getMiddleware', 'callAction', '__construct'])) continue;

                foreach ($routes as $route) {
                    if ($route['action'] === $methodName) {
                        // Traitement spécial pour AuthController
                        if ($isAuthController) {
                            $endpoints[] = $this->buildAuthEndpoint($route, $method, $controllerClass);
                        } else {
                            $endpoints[] = $this->buildEndpoint($route, $method, $controllerClass);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Log error if needed
        }

        return $endpoints;
    }

    /**
     * Construit un endpoint spécifique pour l'authentification
     */
    private function buildAuthEndpoint($route, ReflectionMethod $method, $controllerClass)
    {
        $methodName = $method->getName();
        $httpMethod = $this->getHttpMethod($route['methods']);
        $path = '/' . $route['uri'];

        // Définir les descriptions spécifiques
        $descriptions = [
            'index' => 'Afficher le formulaire de connexion',
            'forgot' => 'Afficher le formulaire de mot de passe oublié',
            'login' => 'Authentifier un administrateur',
            'logout' => 'Déconnecter l\'administrateur'
        ];

        // Définir les exemples de réponse spécifiques
        $responseExamples = [
            'index' => [
                'view' => 'admin.auth.login',
                'message' => 'Page de connexion'
            ],
            'forgot' => [
                'view' => 'admin.auth.forgot',
                'message' => 'Page de récupération de mot de passe'
            ],
            'login' => [
                'success' => true,
                'message' => 'Connexion réussie',
                'redirect' => '/admin/dashboard'
            ],
            'logout' => [
                'success' => true,
                'message' => 'Déconnexion réussie',
                'redirect' => '/'
            ]
        ];

        // Définir les codes de réponse spécifiques
        $responseCodes = [
            'index' => [
                ['code' => 200, 'type' => 'success', 'message' => 'Page de connexion affichée']
            ],
            'forgot' => [
                ['code' => 200, 'type' => 'success', 'message' => 'Page de récupération affichée']
            ],
            'login' => [
                ['code' => 302, 'type' => 'success', 'message' => 'Redirection vers le dashboard après connexion réussie'],
                ['code' => 422, 'type' => 'warning', 'message' => 'Erreur de validation (email/mot de passe requis)'],
                ['code' => 401, 'type' => 'danger', 'message' => 'Email ou mot de passe incorrect']
            ],
            'logout' => [
                ['code' => 302, 'type' => 'success', 'message' => 'Redirection vers la page d\'accueil après déconnexion'],
                ['code' => 401, 'type' => 'danger', 'message' => 'Non authentifié']
            ]
        ];

        // Paramètres du corps pour la méthode login
        $bodyParameters = [];
        if ($methodName === 'login') {
            $bodyParameters = [
                'email' => [
                    'name' => 'email',
                    'type' => 'email',
                    'required' => true,
                    'description' => 'Email de l\'administrateur',
                    'example' => 'admin@example.com'
                ],
                'password' => [
                    'name' => 'password',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Mot de passe de l\'administrateur',
                    'example' => '********'
                ]
            ];
        }

        return [
            'method' => $httpMethod,
            'path' => $path,
            'summary' => $descriptions[$methodName] ?? "Opération $methodName",
            'description' => $descriptions[$methodName] ?? "Opération $methodName sur l'authentification",
            'url_parameters' => $this->extractUrlParameters($path),
            'body_parameters' => $bodyParameters,
            'request_example' => $methodName === 'login' ? [
                'email' => 'admin@example.com',
                'password' => 'password123'
            ] : null,
            'response_example' => $responseExamples[$methodName] ?? [
                    'success' => true,
                    'message' => 'Opération réussie'
                ],
            'response_codes' => $responseCodes[$methodName] ?? [
                    ['code' => 200, 'type' => 'success', 'message' => 'Succès'],
                    ['code' => 401, 'type' => 'danger', 'message' => 'Non authentifié']
                ],
            'access' => $methodName === 'logout' ? 'Protégé' : 'Public'
        ];
    }

    private function getRoutesForController($controllerClass)
    {
        $routes = [];

        try {
            foreach (Route::getRoutes() as $route) {
                $action = $route->getAction();

                if (isset($action['controller']) && strpos($action['controller'], '@') !== false) {
                    list($controller, $method) = explode('@', $action['controller']);

                    if ($controller === $controllerClass) {
                        $routes[] = [
                            'uri' => $route->uri(),
                            'methods' => $route->methods(),
                            'action' => $method,
                            'name' => $route->getName()
                        ];
                    }
                }
            }
        } catch (\Exception $e) {}

        return $routes;
    }

    private function buildEndpoint($route, ReflectionMethod $method, $controllerClass)
    {
        $methodName = $method->getName();
        $httpMethod = $this->getHttpMethod($route['methods']);
        $path = '/' . $route['uri'];

        return [
            'method' => $httpMethod,
            'path' => $path,
            'summary' => $this->generateDescription($methodName, $controllerClass),
            'description' => $this->generateDescription($methodName, $controllerClass),
            'url_parameters' => $this->extractUrlParameters($path),
            'body_parameters' => $this->extractBodyParametersFromMethod($controllerClass, $methodName),
            'request_example' => $this->generateRequestExample($controllerClass, $methodName),
            'response_example' => $this->generateResponseExample($methodName, $controllerClass),
            'response_codes' => $this->getResponseCodes($methodName)
        ];
    }

    private function generateDescription($methodName, $controllerClass)
    {
        // Ignorer AuthController - il a son propre traitement
        if (str_contains($controllerClass, 'AuthController')) {
            return '';
        }

        $resourceName = $this->getResourceName($controllerClass);
        $resourceLower = lcfirst($resourceName);

        // Pour les méthodes personnalisées comme storeCampus, campusStore, etc.
        if (stripos($methodName, 'store') !== false) {
            $type = str_ireplace(['store', 'Store'], '', $methodName);
            if (!empty($type)) {
                return "Création d'un nouvel élément de type $type";
            }
            return "Création d'un nouvel élément de type $resourceName";
        }

        if (stripos($methodName, 'update') !== false) {
            $type = str_ireplace(['update', 'Update'], '', $methodName);
            if (!empty($type)) {
                return "Mise à jour d'un élément de type $type";
            }
            return "Mise à jour d'un $resourceLower";
        }

        if (stripos($methodName, 'delete') !== false || stripos($methodName, 'destroy') !== false) {
            $type = str_ireplace(['delete', 'Delete', 'destroy', 'Destroy'], '', $methodName);
            if (!empty($type)) {
                return "Suppression d'un élément de type $type";
            }
            return "Suppression d'un $resourceLower";
        }

        $descriptions = [
            'index' => "Liste de tous les éléments de type $resourceName",
            'liste' => "Liste de tous les éléments de type $resourceName",
        ];

        return $descriptions[$methodName] ?? "Opération $methodName sur $resourceName";
    }

    /**
     * Extrait les paramètres depuis n'importe quelle méthode qui contient "store"
     */
    private function extractBodyParametersFromMethod($controllerClass, $methodName)
    {
        // Ignorer AuthController
        if (str_contains($controllerClass, 'AuthController')) {
            return [];
        }

        // Ne traiter que les méthodes qui contiennent "store"
        if (stripos($methodName, 'store') === false) {
            return [];
        }

        try {
            $reflection = new ReflectionClass($controllerClass);

            if (!$reflection->hasMethod($methodName)) {
                return [];
            }

            $method = $reflection->getMethod($methodName);
            $fileName = $method->getFileName();
            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();

            // Lire le contenu du fichier
            $lines = file($fileName);
            $methodCode = implode("", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

            // Chercher la validation
            if (preg_match('/validate\s*\(\s*\[\s*(.*?)\s*\]\s*[\),]/s', $methodCode, $matches)) {
                $validationCode = $matches[1];

                // Extraire les champs
                preg_match_all('/(?:\'|")([^\'"]+)(?:\'|")\s*=>\s*[^\n,]+/', $validationCode, $fieldMatches);

                $bodyParams = [];
                foreach ($fieldMatches[1] as $field) {
                    // Déterminer si le champ est requis
                    $isRequired = strpos($validationCode, "'$field' => 'required") !== false
                        || strpos($validationCode, "\"$field\" => \"required") !== false
                        || preg_match("/'$field'\s*=>\s*'[^']*required[^']*'/", $validationCode)
                        || preg_match("/\"$field\"\s*=>\s*\"[^\"]*required[^\"]*\"/", $validationCode);

                    // Déterminer le type à partir des règles
                    $type = $this->guessTypeFromValidation($validationCode, $field);

                    $bodyParams[$field] = [
                        'name' => $field,
                        'type' => $type,
                        'required' => $isRequired,
                        'description' => ucfirst(str_replace('_', ' ', $field)),
                        'example' => $this->generateExampleForField($field, $type)
                    ];
                }

                return $bodyParams;
            }
        } catch (\Exception $e) {
            return [];
        }

        return [];
    }

    /**
     * Devine le type à partir des règles de validation
     */
    private function guessTypeFromValidation($validationCode, $field)
    {
        // Chercher la règle pour ce champ
        if (preg_match("/'$field'\s*=>\s*'([^']+)'/", $validationCode, $matches)) {
            $rules = $matches[1];
        } elseif (preg_match("/\"$field\"\s*=>\s*\"([^\"]+)\"/", $validationCode, $matches)) {
            $rules = $matches[1];
        } else {
            return 'string';
        }

        if (strpos($rules, 'integer') !== false || strpos($rules, 'numeric') !== false) {
            return 'integer';
        }
        if (strpos($rules, 'boolean') !== false) {
            return 'boolean';
        }
        if (strpos($rules, 'email') !== false) {
            return 'email';
        }
        if (strpos($rules, 'date') !== false) {
            return 'date';
        }
        if (strpos($rules, 'array') !== false) {
            return 'array';
        }

        return 'string';
    }

    /**
     * GÉNÈRE UN EXEMPLE DE REQUÊTE
     */
    private function generateRequestExample($controllerClass, $methodName)
    {
        // Ignorer AuthController
        if (str_contains($controllerClass, 'AuthController')) {
            return null;
        }

        $bodyParams = $this->extractBodyParametersFromMethod($controllerClass, $methodName);

        if (empty($bodyParams)) {
            return null;
        }

        $example = [];
        foreach ($bodyParams as $field => $param) {
            $example[$field] = $param['example'];
        }

        return $example;
    }

    /**
     * GÉNÈRE UN EXEMPLE POUR UN CHAMP
     */
    private function generateExampleForField($field, $type = 'string')
    {
        $field = strtolower($field);

        // Exemples basés sur le nom du champ
        $examples = [
            'name' => 'Exemple',
            'nom' => 'Exemple',
            'prenom' => 'Jean',
            'email' => 'user@example.com',
            'phone' => '+221771234567',
            'telephone' => '+221771234567',
            'adresse' => '123 Rue Example',
            'code' => 'CODE001',
            'reference' => 'REF001',
            'description' => 'Description exemple',
            'status' => 'actif',
            'statut' => 'actif',
            'type' => 'standard',
            'categorie' => 'Catégorie A',
            'montant' => 10000,
            'prix' => 5000,
            'quantite' => 10,
            'date' => '2024-01-01',
            'date_debut' => '2024-01-01',
            'date_fin' => '2024-12-31',
            'age' => 25,
            'campus' => 'Campus Principal',
            'cite' => 'Cité Universitaire',
            'batiment' => 'Bâtiment A',
            'chambre' => 'Chambre 101',
            'equipement' => 'Ordinateur',
        ];

        if (isset($examples[$field])) {
            return $examples[$field];
        }

        // Exemples basés sur le type
        switch ($type) {
            case 'integer':
                return 1;
            case 'boolean':
                return true;
            case 'email':
                return 'user@example.com';
            case 'date':
                return '2024-01-01';
            default:
                return 'Exemple';
        }
    }

    /**
     * GÉNÈRE UN EXEMPLE DE RÉPONSE
     */
    private function generateResponseExample($methodName, $controllerClass)
    {
        // Ignorer AuthController
        if (str_contains($controllerClass, 'AuthController')) {
            return null;
        }

        $resourceName = $this->getResourceName($controllerClass);
        $bodyParams = $this->extractBodyParametersFromMethod($controllerClass, $methodName);

        // Adapter le message selon le type de méthode
        $action = 'créé';
        if (strpos($methodName, 'update') !== false) {
            $action = 'modifié';
        } elseif (strpos($methodName, 'delete') !== false || strpos($methodName, 'destroy') !== false) {
            $action = 'supprimé';
        }

        $examples = [
            'index' => [
                'data' => [
                    $this->generateDataExample($bodyParams),
                    $this->generateDataExample($bodyParams),
                    $this->generateDataExample($bodyParams)
                ],
                'total' => 3,
                'success' => true
            ],
            'delete' => [
                'success' => true,
                'message' => "$resourceName supprimé avec succès"
            ],
            'destroy' => [
                'success' => true,
                'message' => "$resourceName supprimé avec succès"
            ]
        ];

        // Pour toutes les méthodes qui contiennent "store", utiliser le message de création
        if (stripos($methodName, 'store') !== false) {
            return [
                'success' => true,
                'message' => "$resourceName créé avec succès",
                'data' => $this->generateDataExample($bodyParams, 4)
            ];
        }

        // Pour toutes les méthodes qui contiennent "update", utiliser le message de modification
        if (stripos($methodName, 'update') !== false) {
            return [
                'success' => true,
                'message' => "$resourceName modifié avec succès",
                'data' => $this->generateDataExample($bodyParams, 1)
            ];
        }

        return $examples[$methodName] ?? [
            'success' => true,
            'message' => 'Opération réussie'
        ];
    }

    /**
     * GÉNÈRE UN EXEMPLE DE DONNÉES
     */
    private function generateDataExample($bodyParams, $id = 1)
    {
        $data = ['id' => $id];

        foreach ($bodyParams as $field => $param) {
            $data[$field] = $param['example'];
        }

        return $data;
    }

    private function getHttpMethod($methods)
    {
        $priority = ['POST', 'PUT', 'PATCH', 'DELETE', 'GET'];
        foreach ($priority as $method) {
            if (in_array($method, $methods)) {
                return $method;
            }
        }
        return $methods[0] ?? 'GET';
    }

    private function extractUrlParameters($path)
    {
        $params = [];
        preg_match_all('/\{([^}]+)\}/', $path, $matches);

        foreach ($matches[1] as $param) {
            $params[] = [
                'name' => $param,
                'type' => 'integer|string',
                'required' => true,
                'description' => "Identifiant du " . str_replace(['id', '_'], ['', ' '], $param)
            ];
        }

        return $params;
    }

    private function getResponseCodes($methodName)
    {
        $codes = [
            'index' => [
                ['code' => 200, 'type' => 'success', 'message' => 'Liste récupérée'],
                ['code' => 401, 'type' => 'danger', 'message' => 'Non authentifié']
            ],
            'delete' => [
                ['code' => 200, 'type' => 'success', 'message' => 'Supprimé'],
                ['code' => 404, 'type' => 'warning', 'message' => 'Non trouvé']
            ],
            'destroy' => [
                ['code' => 200, 'type' => 'success', 'message' => 'Supprimé'],
                ['code' => 404, 'type' => 'warning', 'message' => 'Non trouvé']
            ]
        ];

        // Pour toutes les méthodes store
        if (stripos($methodName, 'store') !== false) {
            return [
                ['code' => 201, 'type' => 'success', 'message' => 'Créé avec succès'],
                ['code' => 422, 'type' => 'warning', 'message' => 'Erreur de validation'],
                ['code' => 401, 'type' => 'danger', 'message' => 'Non authentifié']
            ];
        }

        // Pour toutes les méthodes update
        if (stripos($methodName, 'update') !== false) {
            return [
                ['code' => 200, 'type' => 'success', 'message' => 'Mis à jour'],
                ['code' => 404, 'type' => 'warning', 'message' => 'Non trouvé'],
                ['code' => 422, 'type' => 'warning', 'message' => 'Erreur de validation'],
                ['code' => 401, 'type' => 'danger', 'message' => 'Non authentifié']
            ];
        }

        return $codes[$methodName] ?? [
            ['code' => 200, 'type' => 'success', 'message' => 'Succès'],
            ['code' => 401, 'type' => 'danger', 'message' => 'Non authentifié']
        ];
    }

    private function getResourceName($controllerClass)
    {
        $controllerName = class_basename($controllerClass);
        $resource = str_replace('Controller', '', $controllerName);

        $frenchNames = [
            'Region' => 'Région',
            'Filiere' => 'Filière',
            'Departement' => 'Département',
            'NiveauEtude' => "Niveau d'étude",
            'TypeUtilisateur' => "Type d'utilisateur",
            'Ville' => 'Ville',
            'ParcImmobilier' => 'Parc immobilier',
            'Equipement' => 'Équipement',
            'BatimentsCampagne' => 'Bâtiment de campagne',
            'Auth' => 'Authentification',
            'users' => 'Utilisateur'
        ];

        return $frenchNames[$resource] ?? $resource;
    }

    private function getModuleIcon($module)
    {
        $icons = [
            'Régions' => 'bi-geo-alt',
            'Filières' => 'bi-diagram-3',
            'Villes' => 'bi-building',
            'Départements' => 'bi-map',
            "Niveaux d'étude" => 'bi-mortarboard',
            "Types d'utilisateur" => 'bi-person-badge',
            'Parc Immobilier' => 'bi-building',
            'Équipements' => 'bi-tools',
            'Bâtiments Campagne' => 'bi-house',
            'Utilisateurs' => 'bi-people',
            'Authentification' => 'bi-shield-lock'
        ];

        return $icons[$module] ?? 'bi-folder';
    }

    private function getModuleDescription($module)
    {
        if ($module === 'Authentification') {
            return "Gestion de l'authentification et des sessions administrateur";
        }
        return "Gestion des " . lcfirst($module);
    }
}
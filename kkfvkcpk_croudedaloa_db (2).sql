-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 13 oct. 2026 à 12:53
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `kkfvkcpk_croudedaloa_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` char(36) NOT NULL,
  `actor_type` varchar(30) DEFAULT NULL,
  `actor_id` varchar(150) DEFAULT NULL,
  `actor_name` varchar(255) DEFAULT NULL,
  `action` varchar(20) NOT NULL,
  `method` varchar(10) NOT NULL,
  `route_name` varchar(255) DEFAULT NULL,
  `controller_action` varchar(500) DEFAULT NULL,
  `url` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status_code` smallint(5) UNSIGNED DEFAULT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `actor_type`, `actor_id`, `actor_name`, `action`, `method`, `route_name`, `controller_action`, `url`, `ip_address`, `user_agent`, `status_code`, `context`, `created_at`, `updated_at`) VALUES
('08263deb-b77f-4dde-ac5d-8eab9717472a', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-25 14:58:58', '2026-09-25 14:58:58'),
('0cecf559-7d58-4434-b63e-27eccf7ac846', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'update', 'PUT', 'admin.parametrage.payment-providers.update', 'App\\Http\\Controllers\\admin\\parametrage\\PaymentProviderController@update', 'http://127.0.0.1:8000/admin/parametrage/api-paiement/4', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-25 15:17:25', '2026-09-25 15:17:25'),
('10e845b5-8a18-49d1-9b69-5fe53b5d8913', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-29 11:59:28', '2026-09-29 11:59:28'),
('10f157ea-6fce-471b-8a2f-569b211722e8', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'update', 'PUT', 'admin.parametrage.configuration.update', 'App\\Http\\Controllers\\admin\\parametrage\\configurationController@update', 'http://127.0.0.1:8000/admin/parametrage/configuration/update', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-13 09:22:20', '2026-10-13 09:22:20'),
('146b43c0-e876-46f1-a4d6-000fdcd33b32', 'student', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'John Doe', 'login', 'POST', 'login.etudiant', 'App\\Http\\Controllers\\web\\frontedController@authenticateEtudiant', 'http://127.0.0.1:8000/login-etudiant', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-23 10:41:58', '2026-09-23 10:41:58'),
('19071c65-7902-45d2-80a5-c32133d83291', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'admin.hebergement.parc-immobilier.toggle-status', 'App\\Http\\Controllers\\admin\\immobilier\\parc_immobilier\\ParcImmobilierController@toggleStatus', 'http://127.0.0.1:8000/admin/gestion-hebergement/parc-immobilier/toggle-status/cite/359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 302, '{\"route_parameters\":{\"type\":\"cite\",\"code\":\"359b0f79-8bc9-4340-8c5e-3dffbbafa6b5\"}}', '2026-09-11 08:45:29', '2026-09-11 08:45:29'),
('190d916b-82df-4917-ab16-a6347e572720', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'admin.users.store', 'App\\Http\\Controllers\\admin\\users\\usersController@store', 'http://127.0.0.1:8000/admin/utilisateur/store', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-12 21:17:51', '2026-10-12 21:17:51'),
('1958e489-4dfa-4faa-9bf9-eb1a45715295', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'update', 'PUT', 'admin.parametrage.configuration.update', 'App\\Http\\Controllers\\admin\\parametrage\\configurationController@update', 'http://127.0.0.1:8000/admin/parametrage/configuration/update', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-08-18 10:15:35', '2026-08-18 10:15:35'),
('1962aef4-0061-4290-bf5f-87352bf16c3b', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'admin.hebergement.equipements.affectations.store', 'App\\Http\\Controllers\\admin\\immobilier\\equipements\\EquipmentAllocationController@store', 'http://127.0.0.1:8000/admin/gestion-hebergement/equipements/affectations', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-28 17:43:46', '2026-09-28 17:43:46'),
('1df11377-b7e7-4c54-ace3-1aef616fccb9', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-19 20:05:45', '2026-09-19 20:05:45'),
('2566252c-ef73-47af-be58-c12f004c0a20', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'login.etudiant', 'App\\Http\\Controllers\\web\\frontedController@authenticateEtudiant', 'http://127.0.0.1:8000/login-etudiant', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-13 08:20:36', '2026-10-13 08:20:36'),
('2983f31f-8b94-45d5-82b7-438f9b8369a8', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-11 08:33:28', '2026-09-11 08:33:28'),
('2bc1b2db-b7b9-4be5-bf5a-752a184649d4', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'admin.etudiant.admission_directe.import', 'App\\Http\\Controllers\\admin\\Etudiant\\etudiantController@importAdmissionDirecte', 'http://127.0.0.1:8000/admin/etudiant/admission-directe/import', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-12 23:00:29', '2026-10-12 23:00:29'),
('2bf0777c-9ef6-426e-b2ce-f489eb535980', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'admin.hebergement.maintenance.satisfaction', 'App\\Http\\Controllers\\admin\\immobilier\\vie_residentielle\\MaintenanceController@storeSatisfaction', 'http://127.0.0.1:8000/admin/gestion-hebergement/maintenance/satisfaction', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-14 16:04:58', '2026-09-14 16:04:58'),
('2c6083c9-b72b-43a2-8824-022fce1e6944', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'update', 'PUT', 'admin.parametrage.configuration.update', 'App\\Http\\Controllers\\admin\\parametrage\\configurationController@update', 'http://127.0.0.1:8000/admin/parametrage/configuration/update/profil', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":{\"menus\":\"profil\"}}', '2026-10-13 10:18:58', '2026-10-13 10:18:58'),
('2de27648-591e-4044-8e66-c179cf319143', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'etudiants.contract.approve', 'App\\Http\\Controllers\\etudiant\\ContractApprovalController@store', 'http://127.0.0.1:8000/etudiant/mon-contrat/approbation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-23 16:12:58', '2026-09-23 16:12:58'),
('3026ba17-c1e4-44cc-8016-183c5b1f1803', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-10 09:37:05', '2026-10-10 09:37:05'),
('39c832c0-a694-4d0d-b1de-081b50893bf5', 'student', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'John Doe', 'login', 'POST', 'login.etudiant', 'App\\Http\\Controllers\\web\\frontedController@authenticateEtudiant', 'http://127.0.0.1:8000/login-etudiant', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-23 10:38:06', '2026-09-23 10:38:06'),
('3a2110a9-2485-4ac9-854d-65fd89a34c1d', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'update', 'PUT', 'admin.parametrage.configuration.update', 'App\\Http\\Controllers\\admin\\parametrage\\configurationController@update', 'http://127.0.0.1:8000/admin/parametrage/configuration/update', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-08-18 10:21:17', '2026-08-18 10:21:17'),
('3d0e0bb6-4c3f-4bf0-a131-216c2436d3a2', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'update', 'PUT', 'admin.parametrage.payment-providers.update', 'App\\Http\\Controllers\\admin\\parametrage\\PaymentProviderController@update', 'http://127.0.0.1:8000/admin/parametrage/api-paiement/4', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-25 15:16:44', '2026-09-25 15:16:44'),
('3f3992d9-deeb-4b5e-9fbc-fb1474b4ef9e', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'login.etudiant', 'App\\Http\\Controllers\\web\\frontedController@authenticateEtudiant', 'http://127.0.0.1:8000/login-etudiant', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-08-18 10:49:39', '2026-08-18 10:49:39'),
('3f87a3e3-6614-4a4e-a6e1-2e363fff5c8f', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'update', 'PUT', 'admin.parametrage.configuration.update', 'App\\Http\\Controllers\\admin\\parametrage\\configurationController@update', 'http://127.0.0.1:8000/admin/parametrage/configuration/update/securite', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 302, '{\"route_parameters\":{\"menus\":\"securite\"}}', '2026-08-18 10:22:24', '2026-08-18 10:22:24'),
('4411abf7-dcb0-4385-892e-049ad95738ee', 'student', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'John Doe', 'login', 'POST', 'login.etudiant', 'App\\Http\\Controllers\\web\\frontedController@authenticateEtudiant', 'http://127.0.0.1:8000/login-etudiant', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-28 12:21:43', '2026-09-28 12:21:43'),
('4b415a10-92ca-441f-a060-e1fd8daf7aeb', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'admin.hebergement.parc-immobilier.toggle-status', 'App\\Http\\Controllers\\admin\\immobilier\\parc_immobilier\\ParcImmobilierController@toggleStatus', 'http://127.0.0.1:8000/admin/gestion-hebergement/parc-immobilier/toggle-status/batiment/c1123cbd-658c-483a-a246-dc6726039649', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 302, '{\"route_parameters\":{\"type\":\"batiment\",\"code\":\"c1123cbd-658c-483a-a246-dc6726039649\"}}', '2026-09-11 08:44:35', '2026-09-11 08:44:35'),
('4c9b4cce-2c8d-4bb1-b23c-4639c9f29236', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'login.etudiant', 'App\\Http\\Controllers\\web\\frontedController@authenticateEtudiant', 'http://127.0.0.1:8000/login-etudiant', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-19 20:22:11', '2026-09-19 20:22:11'),
('4ea4992c-5228-4ebf-935c-06606c55b15d', 'student', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'John Doe', 'login', 'POST', 'login.etudiant', 'App\\Http\\Controllers\\web\\frontedController@authenticateEtudiant', 'http://127.0.0.1:8000/login-etudiant', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-14 14:47:12', '2026-09-14 14:47:12'),
('5084ccfa-e9bf-4ae4-b80f-592a3c0997b0', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'login.etudiant', 'App\\Http\\Controllers\\web\\frontedController@authenticateEtudiant', 'http://127.0.0.1:8000/login-etudiant', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-22 15:47:19', '2026-09-22 15:47:19'),
('54c11400-5e9a-43f9-b7db-da7be739846a', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'update', 'PUT', 'admin.parametrage.configuration.update', 'App\\Http\\Controllers\\admin\\parametrage\\configurationController@update', 'http://127.0.0.1:8000/admin/parametrage/configuration/update/notifications', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":{\"menus\":\"notifications\"}}', '2026-10-13 10:21:31', '2026-10-13 10:21:31'),
('562dc564-b928-421a-8905-53c98db401b0', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'admin.hebergement.maintenance.satisfaction', 'App\\Http\\Controllers\\admin\\immobilier\\vie_residentielle\\MaintenanceController@storeSatisfaction', 'http://127.0.0.1:8000/admin/gestion-hebergement/maintenance/satisfaction', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-14 15:36:40', '2026-09-14 15:36:40'),
('5b9b7a79-cfbd-462f-b29c-0c5c6e8be2e5', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-21 17:36:52', '2026-09-21 17:36:52'),
('62e1f0e1-11b2-4d4d-b4c9-0e08a4bd0999', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'update', 'PUT', 'admin.parametrage.configuration.update', 'App\\Http\\Controllers\\admin\\parametrage\\configurationController@update', 'http://127.0.0.1:8000/admin/parametrage/configuration/update', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-08-18 11:05:29', '2026-08-18 11:05:29'),
('6bd11390-1e14-49c2-a21a-8affe27ca277', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'update', 'PUT', 'admin.parametrage.configuration.update', 'App\\Http\\Controllers\\admin\\parametrage\\configurationController@update', 'http://127.0.0.1:8000/admin/parametrage/configuration/update', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-08-18 11:11:01', '2026-08-18 11:11:01'),
('6d391d8a-82e5-4a4d-adfe-cec034051011', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'etudiants.contract.approve', 'App\\Http\\Controllers\\etudiant\\ContractApprovalController@store', 'http://127.0.0.1:8000/etudiant/mon-contrat/approbation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-23 16:13:00', '2026-09-23 16:13:00'),
('6f782127-6ff5-468d-9c88-0718b8dd865b', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'update', 'PUT', 'admin.parametrage.configuration.update', 'App\\Http\\Controllers\\admin\\parametrage\\configurationController@update', 'http://127.0.0.1:8000/admin/parametrage/configuration/update/profil', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":{\"menus\":\"profil\"}}', '2026-10-13 10:40:26', '2026-10-13 10:40:26'),
('79457835-00cb-4758-9b86-a3555fc35eca', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'admin.etudiant.admission_directe.import', 'App\\Http\\Controllers\\admin\\Etudiant\\etudiantController@importAdmissionDirecte', 'http://127.0.0.1:8000/admin/etudiant/admission-directe/import', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-12 23:02:42', '2026-10-12 23:02:42'),
('7d06855f-0b19-4923-a2d7-4fcc7e71fa5d', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-13 07:29:41', '2026-10-13 07:29:41'),
('820f1034-17e2-4b43-baeb-24d4e2a69099', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'update', 'PUT', 'admin.parametrage.configuration.update', 'App\\Http\\Controllers\\admin\\parametrage\\configurationController@update', 'http://127.0.0.1:8000/admin/parametrage/configuration/update', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-13 09:27:04', '2026-10-13 09:27:04'),
('84a73a8a-a28a-46a2-8512-968d63c5011c', 'student', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'John Doe', 'login', 'POST', 'login.etudiant', 'App\\Http\\Controllers\\web\\frontedController@authenticateEtudiant', 'http://127.0.0.1:8000/login-etudiant', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-20 10:08:29', '2026-09-20 10:08:29'),
('8779c569-005f-48eb-a04d-4e154bbe35a4', 'student', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'John Doe', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-23 10:54:12', '2026-09-23 10:54:12'),
('8aa1ba99-fc3c-4fdc-93a8-ab658c3fd644', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'etudiants.contract.approve', 'App\\Http\\Controllers\\etudiant\\ContractApprovalController@store', 'http://127.0.0.1:8000/etudiant/mon-contrat/approbation', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-23 16:12:27', '2026-09-23 16:12:27'),
('8bdf09a5-77de-4234-a790-94124b34ee27', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'admin.etudiant.admission_directe.import', 'App\\Http\\Controllers\\admin\\Etudiant\\etudiantController@importAdmissionDirecte', 'http://127.0.0.1:8000/admin/etudiant/admission-directe/import', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-12 23:19:52', '2026-10-12 23:19:52'),
('8cfd5fd2-3445-4270-a87c-146beec8e228', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'update', 'PUT', 'admin.parametrage.configuration.update', 'App\\Http\\Controllers\\admin\\parametrage\\configurationController@update', 'http://127.0.0.1:8000/admin/parametrage/configuration/update', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-13 09:23:57', '2026-10-13 09:23:57'),
('8d49c0c9-af4f-4b5f-b483-739ddd4cbcf0', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'admin.etudiant.admission_directe.import', 'App\\Http\\Controllers\\admin\\Etudiant\\etudiantController@importAdmissionDirecte', 'http://127.0.0.1:8000/admin/etudiant/admission-directe/import', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-12 23:10:43', '2026-10-12 23:10:43'),
('9163761e-3099-4a34-8365-a37bb5089ec0', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-20 20:02:55', '2026-09-20 20:02:55'),
('9594f686-8f1c-4dee-ab2c-125f602bd888', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'admin.etudiant.admission_directe.import', 'App\\Http\\Controllers\\admin\\Etudiant\\etudiantController@importAdmissionDirecte', 'http://127.0.0.1:8000/admin/etudiant/admission-directe/import', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-12 23:01:40', '2026-10-12 23:01:40'),
('9b432a71-cb1c-4721-be88-0f2d56f00115', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-12 21:01:50', '2026-10-12 21:01:50'),
('9c6faf46-a223-4c90-b54a-8ab61e4873ec', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'admin.users.store', 'App\\Http\\Controllers\\admin\\users\\usersController@store', 'http://127.0.0.1:8000/admin/utilisateur/store', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-12 21:21:10', '2026-10-12 21:21:10'),
('9f57f585-2cb2-420b-814f-78a5695e334d', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-22 14:08:38', '2026-09-22 14:08:38'),
('a23b5d09-d2b9-4f3c-9fee-031e98ac8186', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'admin.users.store', 'App\\Http\\Controllers\\admin\\users\\usersController@store', 'http://127.0.0.1:8000/admin/utilisateur/store', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-12 21:20:38', '2026-10-12 21:20:38'),
('a347bbde-fa45-4002-87ad-fb6e594504b5', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'admin.hebergement.parc-immobilier.toggle-status', 'App\\Http\\Controllers\\admin\\immobilier\\parc_immobilier\\ParcImmobilierController@toggleStatus', 'http://127.0.0.1:8000/admin/gestion-hebergement/parc-immobilier/toggle-status/cite/359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 302, '{\"route_parameters\":{\"type\":\"cite\",\"code\":\"359b0f79-8bc9-4340-8c5e-3dffbbafa6b5\"}}', '2026-09-11 08:34:19', '2026-09-11 08:34:19'),
('a41ec540-48eb-43d2-bbcf-073968f886b0', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-08-20 08:23:11', '2026-08-20 08:23:11'),
('a5508421-8220-4099-a5b4-2ad2b3af5544', 'student', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'John Doe', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-14 14:59:08', '2026-09-14 14:59:08'),
('a5cc6a32-8dd4-4ab1-b7d3-4e6b37f54ebd', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'admin.hebergement.maintenance.satisfaction', 'App\\Http\\Controllers\\admin\\immobilier\\vie_residentielle\\MaintenanceController@storeSatisfaction', 'http://127.0.0.1:8000/admin/gestion-hebergement/maintenance/satisfaction', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-14 15:35:42', '2026-09-14 15:35:42'),
('a96a5ce6-ad12-4e13-9762-453707037a02', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'admin.hebergement.parc-immobilier.toggle-status', 'App\\Http\\Controllers\\admin\\immobilier\\parc_immobilier\\ParcImmobilierController@toggleStatus', 'http://127.0.0.1:8000/admin/gestion-hebergement/parc-immobilier/toggle-status/cite/359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 302, '{\"route_parameters\":{\"type\":\"cite\",\"code\":\"359b0f79-8bc9-4340-8c5e-3dffbbafa6b5\"}}', '2026-09-11 08:40:09', '2026-09-11 08:40:09'),
('aa51b0bc-2c3d-4c9a-802b-7e0f18c6243e', 'student', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'John Doe', 'login', 'POST', 'login.etudiant', 'App\\Http\\Controllers\\web\\frontedController@authenticateEtudiant', 'http://127.0.0.1:8000/login-etudiant', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-23 10:53:02', '2026-09-23 10:53:02'),
('aad63433-5854-4f63-a44f-804dc98b4e06', 'student', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'John Doe', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-25 15:06:01', '2026-09-25 15:06:01'),
('bd2dfe21-da96-416e-9897-b1736c06ac27', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-10 16:01:48', '2026-09-10 16:01:48'),
('c1ccb498-be73-444d-93cc-3be61ce5ef71', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-12 18:09:28', '2026-10-12 18:09:28'),
('c4c4891d-a96d-40e6-9e93-2963f345d820', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-24 09:31:03', '2026-09-24 09:31:03'),
('c92bcf01-92d2-4886-b150-95dc8b1633e7', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'update', 'PATCH', 'admin.hebergement.vie_residentielle.incidents.transition', 'App\\Http\\Controllers\\admin\\immobilier\\vie_residentielle\\DisciplinaryReportController@transition', 'http://127.0.0.1:8000/admin/gestion-hebergement/vie-residentielle/incidents-disciplinaires/e1cdb369-4478-4fd2-9fc6-81a26098d20a/transition', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-08-18 10:50:29', '2026-08-18 10:50:29'),
('cb2b38f1-7602-4295-91cd-19c3489f9bb4', 'student', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'John Doe', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-28 14:08:30', '2026-09-28 14:08:30'),
('cc9afae2-5899-44f0-b050-138346fe5d37', 'student', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'John Doe', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-20 10:13:08', '2026-09-20 10:13:08'),
('d5181cd9-2efe-4182-9b4a-871a5e498dde', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'update', 'PUT', 'admin.parametrage.payment-providers.update', 'App\\Http\\Controllers\\admin\\parametrage\\PaymentProviderController@update', 'http://127.0.0.1:8000/admin/parametrage/api-paiement/4', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-25 15:18:39', '2026-09-25 15:18:39'),
('dcb07e6c-ecbe-4f58-8da4-b49047aa9fa2', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'update', 'PUT', 'admin.parametrage.configuration.update', 'App\\Http\\Controllers\\admin\\parametrage\\configurationController@update', 'http://127.0.0.1:8000/admin/parametrage/configuration/update', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-08-18 11:10:50', '2026-08-18 11:10:50'),
('df7e81bd-df54-4814-b14d-da823d190784', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-24 17:47:20', '2026-09-24 17:47:20'),
('e34638d8-7884-4c15-ae82-95fbd5851ddf', 'student', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'John Doe', 'login', 'POST', 'login.etudiant', 'App\\Http\\Controllers\\web\\frontedController@authenticateEtudiant', 'http://127.0.0.1:8000/login-etudiant', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-25 15:02:14', '2026-09-25 15:02:14'),
('ed5e1fb9-37cd-439f-9ded-529f3d724f04', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-25 09:45:48', '2026-09-25 09:45:48'),
('ede13f21-976d-44ae-9a6d-8ef03b83ca3a', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'create', 'POST', 'admin.parametrage.roles-permissions.roles.store', 'App\\Http\\Controllers\\admin\\parametrage\\RolePermissionController@storeRole', 'http://127.0.0.1:8000/admin/parametrage/roles-permissions/roles', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-28 14:09:56', '2026-09-28 14:09:56'),
('f307f869-bae3-4b0c-bbc1-4b51b693a245', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'login.etudiant', 'App\\Http\\Controllers\\web\\frontedController@authenticateEtudiant', 'http://127.0.0.1:8000/login-etudiant', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-09-25 10:58:51', '2026-09-25 10:58:51'),
('fc387dc3-32f4-412a-952b-3173e11b3dbb', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'login', 'POST', 'admin.post.login', 'App\\Http\\Controllers\\admin\\auth\\AuthController@login', 'http://127.0.0.1:8000/admin/login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-12 21:49:19', '2026-10-12 21:49:19'),
('febd05c7-a21f-4f15-8f20-1565e999cba0', 'admin', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'Yapo Rodrigue', 'update', 'PUT', 'admin.parametrage.configuration.update', 'App\\Http\\Controllers\\admin\\parametrage\\configurationController@update', 'http://127.0.0.1:8000/admin/parametrage/configuration/update', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 302, '{\"route_parameters\":[]}', '2026-10-13 09:27:26', '2026-10-13 09:27:26');

-- --------------------------------------------------------

--
-- Structure de la table `admission_derogations`
--

CREATE TABLE `admission_derogations` (
  `id` char(36) NOT NULL,
  `etudiant_id` varchar(255) NOT NULL,
  `demande_id` varchar(255) NOT NULL,
  `campagne_id` varchar(255) NOT NULL,
  `requested_by` varchar(255) NOT NULL,
  `requester_name` varchar(255) NOT NULL,
  `reason` text NOT NULL,
  `eligibility` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`eligibility`)),
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `decided_by` varchar(255) DEFAULT NULL,
  `decider_name` varchar(255) DEFAULT NULL,
  `decision_reason` text DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `affectation_equipement_type_equipement`
--

CREATE TABLE `affectation_equipement_type_equipement` (
  `id_affectation` int(11) NOT NULL,
  `equipement_id` int(11) NOT NULL,
  `type_equipement_id` int(11) NOT NULL,
  `quantite` int(11) DEFAULT 1,
  `date_affectation` date NOT NULL,
  `date_retrait` date DEFAULT NULL,
  `etat` enum('neuf','bon','moyen','mauvais','hors_service') DEFAULT 'neuf',
  `numero_serie` varchar(100) DEFAULT NULL,
  `garantie_jusque` date DEFAULT NULL,
  `cout_acquisition` decimal(10,2) DEFAULT 0.00,
  `localisation` varchar(255) DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structure de la table `aile_batiment`
--

CREATE TABLE `aile_batiment` (
  `id_aile_batiment` int(11) NOT NULL,
  `code_aile` varchar(255) NOT NULL,
  `batiment_id` varchar(255) NOT NULL,
  `numero` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `nombre_niveaux` int(11) NOT NULL,
  `total_chambres` int(11) DEFAULT 0,
  `ordre_display` int(11) DEFAULT 0,
  `created_by` varchar(250) DEFAULT NULL,
  `updated_by` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `aile_batiment`
--

INSERT INTO `aile_batiment` (`id_aile_batiment`, `code_aile`, `batiment_id`, `numero`, `name`, `nombre_niveaux`, `total_chambres`, `ordre_display`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(22, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 1, 'Aile Principale', 5, 50, 1, NULL, NULL, '2026-07-06 08:50:13', '2026-07-06 08:50:13');

-- --------------------------------------------------------

--
-- Structure de la table `annonces`
--

CREATE TABLE `annonces` (
  `id_annonce` char(36) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `canaux` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '["flux", "sms", "email", "push"]' CHECK (json_valid(`canaux`)),
  `audience` enum('all','cite','batiment','chambre','specific') NOT NULL DEFAULT 'all',
  `audience_cible` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '{"cites": ["id1", "id2"], "batiments": ["id1"], "etudiants": ["id1"]}' CHECK (json_valid(`audience_cible`)),
  `statut` enum('brouillon','publie','planifie','annule') NOT NULL DEFAULT 'brouillon',
  `priorite` enum('normale','haute','urgente') NOT NULL DEFAULT 'normale',
  `date_publication` datetime DEFAULT NULL,
  `date_expiration` datetime DEFAULT NULL,
  `date_planification` datetime DEFAULT NULL,
  `vues` int(11) DEFAULT 0,
  `clics` int(11) DEFAULT 0,
  `reactions` int(11) DEFAULT 0,
  `pieces_jointes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pieces_jointes`)),
  `created_by` char(36) DEFAULT NULL,
  `updated_by` char(36) DEFAULT NULL,
  `deleted_by` char(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `diffusion_preparee_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `annonces`
--

INSERT INTO `annonces` (`id_annonce`, `titre`, `contenu`, `canaux`, `audience`, `audience_cible`, `statut`, `priorite`, `date_publication`, `date_expiration`, `date_planification`, `vues`, `clics`, `reactions`, `pieces_jointes`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`, `diffusion_preparee_at`) VALUES
('6d26955b-0db6-4ba1-b31c-689a16d20dda', 'maintenance complet', '<p>test</p>', '[\"flux\"]', 'all', NULL, 'publie', 'normale', '2026-07-07 02:49:13', NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-07-07 01:33:22', '2026-09-20 20:38:24', NULL, '2026-09-20 20:38:24'),
('89706cbd-89b6-4296-b550-894d48bde438', 'maintenance complets deux', '<p>test tezt</p>', '[\"flux\",\"sms\"]', 'all', NULL, 'publie', 'normale', '2026-07-07 02:45:04', NULL, NULL, 0, 0, 0, '\"[\\\"\\\\\\/annonce\\\\\\/2026-07-07-6a4c5a24aea3b.jpg\\\"]\"', NULL, NULL, NULL, '2026-07-07 01:45:09', '2026-09-20 20:38:24', NULL, '2026-09-20 20:38:24');

-- --------------------------------------------------------

--
-- Structure de la table `annonce_deliveries`
--

CREATE TABLE `annonce_deliveries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `annonce_id` varchar(64) NOT NULL,
  `etudiant_id` varchar(64) NOT NULL,
  `channel` varchar(10) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `error` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `assignation_chambres`
--

CREATE TABLE `assignation_chambres` (
  `id_assignation` varchar(150) NOT NULL,
  `etudiant_id` varchar(150) NOT NULL,
  `chambre_id` varchar(150) NOT NULL,
  `campagne_id` varchar(150) NOT NULL,
  `date_assignation` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `statut` varchar(30) NOT NULL DEFAULT 'en_attente',
  `type_assignation` varchar(150) DEFAULT NULL,
  `commentaires` text DEFAULT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `lit_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `assignation_chambres`
--

INSERT INTO `assignation_chambres` (`id_assignation`, `etudiant_id`, `chambre_id`, `campagne_id`, `date_assignation`, `date_fin`, `statut`, `type_assignation`, `commentaires`, `created_by`, `updated_by`, `created_at`, `updated_at`, `lit_id`) VALUES
('bdc6e064-477e-4bbf-97a3-233965e42a50', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', '1edb4ef8-3b43-4287-88c4-c1e1959e8ffa', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', '2026-08-03 16:35:48', NULL, 'confirmee', 'manuelle', NULL, NULL, NULL, '2026-08-03 16:35:38', '2026-09-21 17:24:29', 13);

-- --------------------------------------------------------

--
-- Structure de la table `batiment`
--

CREATE TABLE `batiment` (
  `id_batiment` varchar(150) NOT NULL,
  `campus_id` varchar(255) NOT NULL,
  `responsable_id` varchar(150) DEFAULT NULL,
  `cite_id` varchar(150) NOT NULL,
  `statut` varchar(20) NOT NULL DEFAULT 'actif',
  `name` varchar(100) NOT NULL,
  `type_architecture` enum('simple','complexe') NOT NULL,
  `type_occupation_id` varchar(150) DEFAULT NULL,
  `etat_initial` enum('en_construction','operationnel','renovation','ferme') DEFAULT 'operationnel',
  `longitude` float DEFAULT NULL,
  `latitude` float DEFAULT NULL,
  `description` text DEFAULT NULL,
  `plan_file` varchar(255) DEFAULT NULL,
  `total_chambres` int(11) DEFAULT 0,
  `total_etages` int(11) DEFAULT 0,
  `total_ailes` int(11) DEFAULT 0,
  `services_acces_handicape` tinyint(1) DEFAULT 0,
  `services_ascenseur` tinyint(1) DEFAULT 0,
  `services_gardiennage` tinyint(1) DEFAULT 0,
  `is_valid` tinyint(1) DEFAULT 1,
  `created_by` varchar(255) DEFAULT NULL,
  `updated_by` varchar(255) DEFAULT NULL,
  `deleted_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `batiment`
--

INSERT INTO `batiment` (`id_batiment`, `campus_id`, `responsable_id`, `cite_id`, `statut`, `name`, `type_architecture`, `type_occupation_id`, `etat_initial`, `longitude`, `latitude`, `description`, `plan_file`, `total_chambres`, `total_etages`, `total_ailes`, `services_acces_handicape`, `services_ascenseur`, `services_gardiennage`, `is_valid`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('c1123cbd-658c-483a-a246-dc6726039649', '3c11a70c-a236-4177-a98c-a77b5c45530c', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', 'actif', 'BATIMENT-A', 'simple', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', 'operationnel', -6.45365, 6.89706, '', NULL, 50, 5, 1, 1, 1, 1, 1, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:13', '2026-07-06 08:50:14', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `campagne`
--

CREATE TABLE `campagne` (
  `id_campagne` varchar(150) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `description` text DEFAULT NULL,
  `annee_campagne` varchar(150) DEFAULT NULL,
  `annee_debut` int(11) DEFAULT NULL,
  `annee_fin` int(11) DEFAULT NULL,
  `is_discipline` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'N''avoir causé aucun trouble à l''ordre ou à la discipline dans les résidences\r\n\r\nN''avoir fait l''objet d''aucune sanction pour fautes graves',
  `is_participate` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Maintenir sa chambre et tous les espaces occupés propres en permanence\r\n\r\nParticiper aux activités organisées par le GROS Bondoukou',
  `pourcentage_validate` int(11) NOT NULL DEFAULT 0 COMMENT 'Au moins 90 % des crédits validés pour les étudiants',
  `periode_debut` timestamp NULL DEFAULT NULL,
  `periode_fin` timestamp NULL DEFAULT NULL,
  `date_echeance_demande` timestamp NULL DEFAULT NULL,
  `date_echeance_contrat` timestamp NULL DEFAULT NULL,
  `admission` tinyint(4) DEFAULT 0,
  `readmission` tinyint(4) DEFAULT 0,
  `statut` enum('active','terminer','planifier') NOT NULL DEFAULT 'planifier',
  `pmr` tinyint(4) DEFAULT 1,
  `internationnaux` tinyint(4) DEFAULT 1,
  `moyenne_min` decimal(10,0) DEFAULT NULL,
  `redoublement` tinyint(4) DEFAULT 1,
  `distance_min` int(11) DEFAULT NULL,
  `age_L1_max` int(11) DEFAULT NULL,
  `age_L2_max` int(11) DEFAULT NULL,
  `age_L3_max` int(11) NOT NULL DEFAULT 1,
  `age_M1_max` int(11) NOT NULL DEFAULT 1,
  `age_M2_max` int(11) NOT NULL DEFAULT 1,
  `age_D1_max` int(11) NOT NULL DEFAULT 1,
  `age_D2_max` int(11) NOT NULL DEFAULT 1,
  `age_D3_max` int(11) NOT NULL DEFAULT 1,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `deleted_by` varchar(150) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `campagne`
--

INSERT INTO `campagne` (`id_campagne`, `description`, `annee_campagne`, `annee_debut`, `annee_fin`, `is_discipline`, `is_participate`, `pourcentage_validate`, `periode_debut`, `periode_fin`, `date_echeance_demande`, `date_echeance_contrat`, `admission`, `readmission`, `statut`, `pmr`, `internationnaux`, `moyenne_min`, `redoublement`, `distance_min`, `age_L1_max`, `age_L2_max`, `age_L3_max`, `age_M1_max`, `age_M2_max`, `age_D1_max`, `age_D2_max`, `age_D3_max`, `created_by`, `updated_by`, `deleted_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
('6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'test test ets', '2025-2026', 2025, 2026, 1, 1, 80, '2026-07-31 00:00:00', '2026-08-31 00:00:00', '2026-09-30 00:00:00', '2026-10-21 00:00:00', 1, 1, 'terminer', 1, 1, 10, 1, 12, 18, 19, 20, 22, 23, 25, 26, 27, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, NULL, '2026-08-03 15:56:34', '2026-09-10 16:01:05');

-- --------------------------------------------------------

--
-- Structure de la table `campaign_scorings`
--

CREATE TABLE `campaign_scorings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `campagne_id` varchar(255) NOT NULL,
  `weights` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`weights`)),
  `points` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`points`)),
  `minimum_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `pmr_first` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `campus`
--

CREATE TABLE `campus` (
  `id_campus` varchar(150) NOT NULL,
  `code_campus` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `ville_id` int(11) DEFAULT NULL,
  `region_id` int(11) DEFAULT NULL,
  `countrie_id` int(11) DEFAULT NULL,
  `code` varchar(150) NOT NULL,
  `telephone1` varchar(20) DEFAULT NULL,
  `telephone2` varchar(20) DEFAULT NULL,
  `telephone3` varchar(20) DEFAULT NULL,
  `email1` varchar(255) DEFAULT NULL,
  `email2` varchar(255) DEFAULT NULL,
  `email3` varchar(255) DEFAULT NULL,
  `longitude` float DEFAULT NULL,
  `latitude` float DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `devise` varchar(255) DEFAULT NULL,
  `directeur_code` varchar(150) DEFAULT NULL,
  `capacite_max` int(11) DEFAULT 0,
  `superficie` decimal(10,2) DEFAULT 0.00,
  `date_creation` date DEFAULT NULL,
  `statut` enum('actif','inactif','en_construction') DEFAULT 'actif',
  `created_by` varchar(150) DEFAULT NULL,
  `deleted_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `campus`
--

INSERT INTO `campus` (`id_campus`, `code_campus`, `name`, `description`, `adresse`, `ville_id`, `region_id`, `countrie_id`, `code`, `telephone1`, `telephone2`, `telephone3`, `email1`, `email2`, `email3`, `longitude`, `latitude`, `logo`, `slogan`, `devise`, `directeur_code`, `capacite_max`, `superficie`, `date_creation`, `statut`, `created_by`, `deleted_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('3c11a70c-a236-4177-a98c-a77b5c45530c', 'Daloa_01', 'L’Université Jean Lorougnon Guédé', NULL, 'Université Jean Lorougnon Guédé', 62, 16, NULL, 'WNGuecsYGIxHuj9ye7deSnd3Obe012IXcdVDeSDForETFyCYCqJMMhmDQY7n0KWvPkGIMjGPYS1MLVLAvaTqB4UYmodCs0XeBRgvkbFmrNIY7fK2LwCzSedVNMeTp7oLMBVLqET11ML2mGhN5ImCgi', '+225 2724555409', NULL, NULL, 'info@ujlg.edu.ci', NULL, NULL, -6.4389, 6.91203, 'http://dev3.soumafe.ci/admin/assets/images/campus/6a2e2df4752616.62629474.jpg', NULL, NULL, NULL, 0, 0.00, NULL, 'actif', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, NULL, '2026-06-14 04:28:36', '2026-06-14 04:28:36', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `cautions`
--

CREATE TABLE `cautions` (
  `id_caution` varchar(150) NOT NULL,
  `resident_id` varchar(150) NOT NULL COMMENT 'ID du résident',
  `contrat_id` varchar(150) NOT NULL COMMENT 'ID du contrat associé',
  `chambre_id` varchar(150) DEFAULT NULL COMMENT 'ID de la chambre',
  `batiment_id` varchar(150) DEFAULT NULL COMMENT 'ID du batiment',
  `cite_id` varchar(150) DEFAULT NULL COMMENT 'ID de la résidence',
  `montant_initial` decimal(15,2) NOT NULL COMMENT 'Montant initial de la caution',
  `montant_retenu` decimal(15,2) DEFAULT 0.00 COMMENT 'Montant retenu sur la caution',
  `montant_restitue` decimal(15,2) DEFAULT 0.00 COMMENT 'Montant restitué',
  `solde` decimal(15,2) GENERATED ALWAYS AS (`montant_initial` - `montant_retenu` - `montant_restitue`) STORED COMMENT 'Solde de la caution',
  `statut` enum('encaissement','retenue_partielle','restitue','solde_debiteur') NOT NULL DEFAULT 'encaissement' COMMENT 'Statut de la caution',
  `paiement_encaissement_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID du paiement d''encaissement',
  `paiement_restitution_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID du paiement de restitution',
  `date_encaissement` date NOT NULL COMMENT 'Date d''encaissement',
  `date_restitution` date DEFAULT NULL COMMENT 'Date de restitution',
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_mise_a_jour` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `details_retenues` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Détails des retenues (JSON)' CHECK (json_valid(`details_retenues`)),
  `observations` text DEFAULT NULL COMMENT 'Observations',
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `deleted_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gestion des cautions';

-- --------------------------------------------------------

--
-- Structure de la table `chambre`
--

CREATE TABLE `chambre` (
  `id_chambre` varchar(150) NOT NULL,
  `niveau_aile_code` varchar(250) DEFAULT NULL,
  `numero_chambre` varchar(250) DEFAULT NULL,
  `type_chambre_id` varchar(150) DEFAULT NULL,
  `type_occupation_id` varchar(150) DEFAULT NULL,
  `code_chambre` varchar(250) DEFAULT NULL,
  `code_aile` varchar(250) DEFAULT NULL,
  `batiment_id` varchar(150) DEFAULT NULL,
  `statut` varchar(20) NOT NULL DEFAULT 'actif',
  `responsable_id` varchar(150) DEFAULT NULL,
  `cite_id` varchar(150) DEFAULT NULL,
  `campus_id` varchar(150) DEFAULT NULL,
  `name` varchar(20) DEFAULT NULL,
  `etat` enum('disponible','occupee','maintenance','reservee','hors_service') DEFAULT 'disponible',
  `capacite_max` int(11) DEFAULT 1,
  `prix` int(11) DEFAULT 0,
  `caution` int(11) DEFAULT 0,
  `avance` int(11) DEFAULT 0,
  `occupants_actuels` int(11) DEFAULT 0,
  `cote_orientation` enum('nord','sud','est','ouest') DEFAULT NULL,
  `superficie` decimal(5,2) DEFAULT NULL,
  `pmr` tinyint(4) DEFAULT 0,
  `nb_lits` int(11) DEFAULT 0,
  `niveau_confort` varchar(150) DEFAULT NULL,
  `equipements` text DEFAULT NULL,
  `created_by` varchar(250) DEFAULT NULL,
  `updated_by` varchar(250) DEFAULT NULL,
  `deleted_by` varchar(250) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `chambre`
--

INSERT INTO `chambre` (`id_chambre`, `niveau_aile_code`, `numero_chambre`, `type_chambre_id`, `type_occupation_id`, `code_chambre`, `code_aile`, `batiment_id`, `statut`, `responsable_id`, `cite_id`, `campus_id`, `name`, `etat`, `capacite_max`, `prix`, `caution`, `avance`, `occupants_actuels`, `cote_orientation`, `superficie`, `pmr`, `nb_lits`, `niveau_confort`, `equipements`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('ab2dfa31-d59a-4cd9-bec5-ad11be555b48', '0w0jdNyfeyv4D9ZQqWucCtr7hGknWauzKZWVs8kbJUBcRzgUSsBAZtFAACozMMDGEvLysL9oYEyU9BxsR0FE0XyjXTDoI6h1RBmeltDVDvCwaSEEZiKSEzfYE2Y57G0cFGk3j417JnCNzIB8zAGZiZ', NULL, NULL, NULL, NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 1, 0, 0, 0, 0, NULL, NULL, 0, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-06 08:50:14', '2026-07-06 08:50:14', NULL),
('ea8b0a85-7e74-42c1-ba9e-d7adc4685b01', '0w0jdNyfeyv4D9ZQqWucCtr7hGknWauzKZWVs8kbJUBcRzgUSsBAZtFAACozMMDGEvLysL9oYEyU9BxsR0FE0XyjXTDoI6h1RBmeltDVDvCwaSEEZiKSEzfYE2Y57G0cFGk3j417JnCNzIB8zAGZiZ', NULL, NULL, NULL, NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 1, 0, 0, 0, 0, NULL, NULL, 0, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-06 08:50:14', '2026-07-06 08:50:14', NULL),
('0ca0afc9-a3fa-4f45-87bd-c74e75a7c2e6', '0w0jdNyfeyv4D9ZQqWucCtr7hGknWauzKZWVs8kbJUBcRzgUSsBAZtFAACozMMDGEvLysL9oYEyU9BxsR0FE0XyjXTDoI6h1RBmeltDVDvCwaSEEZiKSEzfYE2Y57G0cFGk3j417JnCNzIB8zAGZiZ', NULL, NULL, NULL, NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 1, 0, 0, 0, 0, NULL, NULL, 0, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-06 08:50:14', '2026-07-06 08:50:14', NULL),
('79d7b6da-1688-4e56-8918-faa3371d01c6', '0w0jdNyfeyv4D9ZQqWucCtr7hGknWauzKZWVs8kbJUBcRzgUSsBAZtFAACozMMDGEvLysL9oYEyU9BxsR0FE0XyjXTDoI6h1RBmeltDVDvCwaSEEZiKSEzfYE2Y57G0cFGk3j417JnCNzIB8zAGZiZ', NULL, NULL, NULL, NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 1, 0, 0, 0, 0, NULL, NULL, 0, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-06 08:50:14', '2026-07-06 08:50:14', NULL),
('8b747a53-2c5d-49ef-824d-f7f8051cf7f3', '0w0jdNyfeyv4D9ZQqWucCtr7hGknWauzKZWVs8kbJUBcRzgUSsBAZtFAACozMMDGEvLysL9oYEyU9BxsR0FE0XyjXTDoI6h1RBmeltDVDvCwaSEEZiKSEzfYE2Y57G0cFGk3j417JnCNzIB8zAGZiZ', NULL, NULL, NULL, NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 1, 0, 0, 0, 0, NULL, NULL, 0, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-06 08:50:14', '2026-07-06 08:50:14', NULL),
('7a54ce02-6906-45e9-848a-3e90ba4d776d', '0w0jdNyfeyv4D9ZQqWucCtr7hGknWauzKZWVs8kbJUBcRzgUSsBAZtFAACozMMDGEvLysL9oYEyU9BxsR0FE0XyjXTDoI6h1RBmeltDVDvCwaSEEZiKSEzfYE2Y57G0cFGk3j417JnCNzIB8zAGZiZ', NULL, NULL, NULL, NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 1, 0, 0, 0, 0, NULL, NULL, 0, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-06 08:50:14', '2026-07-06 08:50:14', NULL),
('5be87eb1-73bb-4dc5-98a3-0787e3ba45ef', '0w0jdNyfeyv4D9ZQqWucCtr7hGknWauzKZWVs8kbJUBcRzgUSsBAZtFAACozMMDGEvLysL9oYEyU9BxsR0FE0XyjXTDoI6h1RBmeltDVDvCwaSEEZiKSEzfYE2Y57G0cFGk3j417JnCNzIB8zAGZiZ', NULL, NULL, NULL, NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 1, 0, 0, 0, 0, NULL, NULL, 0, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-06 08:50:14', '2026-07-06 08:50:14', NULL),
('9fdf0a8d-7bfb-46f0-860b-627446759076', '0w0jdNyfeyv4D9ZQqWucCtr7hGknWauzKZWVs8kbJUBcRzgUSsBAZtFAACozMMDGEvLysL9oYEyU9BxsR0FE0XyjXTDoI6h1RBmeltDVDvCwaSEEZiKSEzfYE2Y57G0cFGk3j417JnCNzIB8zAGZiZ', NULL, NULL, NULL, NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 1, 0, 0, 0, 0, NULL, NULL, 0, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-06 08:50:14', '2026-07-06 08:50:14', NULL),
('16279d71-514e-45dc-940e-2d77df7fa953', '0w0jdNyfeyv4D9ZQqWucCtr7hGknWauzKZWVs8kbJUBcRzgUSsBAZtFAACozMMDGEvLysL9oYEyU9BxsR0FE0XyjXTDoI6h1RBmeltDVDvCwaSEEZiKSEzfYE2Y57G0cFGk3j417JnCNzIB8zAGZiZ', NULL, NULL, NULL, NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 1, 0, 0, 0, 0, NULL, NULL, 0, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-06 08:50:14', '2026-07-06 08:50:14', NULL),
('67aa7531-8ced-446a-a453-2771d77080af', '0w0jdNyfeyv4D9ZQqWucCtr7hGknWauzKZWVs8kbJUBcRzgUSsBAZtFAACozMMDGEvLysL9oYEyU9BxsR0FE0XyjXTDoI6h1RBmeltDVDvCwaSEEZiKSEzfYE2Y57G0cFGk3j417JnCNzIB8zAGZiZ', NULL, NULL, NULL, NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 1, 0, 0, 0, 0, NULL, NULL, 0, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-06 08:50:14', '2026-07-06 08:50:14', NULL),
('1dd0e79d-7413-4c7b-b268-fba2ea5ff745', 'waTdnDbVqc2lH09yNjP3mBW0Mh0TBvO3MAWHkXkhp8IKmmR34BLVuuOyEcr59k3WDzKp5euKoL7dyb87bQBOucHv9MF0RnkEVCg01r51o3dy7NiqeOvzxlEUojoRh16NvlUvkJ9SnGlElk4aMN9T5W', 'CH-01', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-20 11:38:05', NULL),
('2d610196-1e6b-43a8-91ed-76f5f613f84f', 'waTdnDbVqc2lH09yNjP3mBW0Mh0TBvO3MAWHkXkhp8IKmmR34BLVuuOyEcr59k3WDzKp5euKoL7dyb87bQBOucHv9MF0RnkEVCg01r51o3dy7NiqeOvzxlEUojoRh16NvlUvkJ9SnGlElk4aMN9T5W', 'CH01', '8fce98d7-fde1-11f0-82f8-fa163e85dd73', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 1, 12000, 0, 0, 0, NULL, 0.00, 0, 1, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Climatiseur\":\"Climatiseur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-20 12:06:06', NULL),
('d048b03d-68cc-48ac-93e3-b2642998469e', 'waTdnDbVqc2lH09yNjP3mBW0Mh0TBvO3MAWHkXkhp8IKmmR34BLVuuOyEcr59k3WDzKp5euKoL7dyb87bQBOucHv9MF0RnkEVCg01r51o3dy7NiqeOvzxlEUojoRh16NvlUvkJ9SnGlElk4aMN9T5W', 'CH-02', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-20 12:06:30', NULL),
('71d9edf0-72b0-4c66-b6d8-2dd505ffadc8', 'waTdnDbVqc2lH09yNjP3mBW0Mh0TBvO3MAWHkXkhp8IKmmR34BLVuuOyEcr59k3WDzKp5euKoL7dyb87bQBOucHv9MF0RnkEVCg01r51o3dy7NiqeOvzxlEUojoRh16NvlUvkJ9SnGlElk4aMN9T5W', 'CH-03', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-20 12:15:55', NULL),
('1edb4ef8-3b43-4287-88c4-c1e1959e8ffa', 'waTdnDbVqc2lH09yNjP3mBW0Mh0TBvO3MAWHkXkhp8IKmmR34BLVuuOyEcr59k3WDzKp5euKoL7dyb87bQBOucHv9MF0RnkEVCg01r51o3dy7NiqeOvzxlEUojoRh16NvlUvkJ9SnGlElk4aMN9T5W', 'CH-04', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 1, NULL, 0.00, 0, 2, 'standard', '[{\"name\":\"Chaise\",\"quantity\":2},{\"name\":\"Table\",\"quantity\":1},{\"name\":\"Courant\",\"quantity\":1},{\"name\":\"Eau\",\"quantity\":1},{\"name\":\"Cuisine\",\"quantity\":1},{\"name\":\"Placard\",\"quantity\":1},{\"name\":\"Matelas\",\"quantity\":1},{\"name\":\"Prise\",\"quantity\":1},{\"name\":\"Douche\",\"quantity\":1},{\"name\":\"WC\",\"quantity\":1},{\"name\":\"Ventilateur\",\"quantity\":1}]', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-08-03 16:35:38', NULL),
('70164ed3-5bb9-4b10-a5b5-b68d7fe6ca59', 'waTdnDbVqc2lH09yNjP3mBW0Mh0TBvO3MAWHkXkhp8IKmmR34BLVuuOyEcr59k3WDzKp5euKoL7dyb87bQBOucHv9MF0RnkEVCg01r51o3dy7NiqeOvzxlEUojoRh16NvlUvkJ9SnGlElk4aMN9T5W', 'CH-05', '8fce98d7-fde1-11f0-82f8-fa163e85dd73', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 1, 12000, 0, 6, 0, NULL, 0.00, 0, 1, 'standard', '[{\"name\":\"Chaise\",\"quantity\":1},{\"name\":\"Table\",\"quantity\":1},{\"name\":\"Courant\",\"quantity\":1},{\"name\":\"Eau\",\"quantity\":1},{\"name\":\"Cuisine\",\"quantity\":2},{\"name\":\"Placard\",\"quantity\":1},{\"name\":\"Matelas\",\"quantity\":3},{\"name\":\"Prise\",\"quantity\":1},{\"name\":\"Douche\",\"quantity\":1},{\"name\":\"WC\",\"quantity\":1},{\"name\":\"Climatiseur\",\"quantity\":1}]', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-08-03 16:23:12', NULL),
('13567795-9f26-4126-a1ba-9c3166add82e', 'waTdnDbVqc2lH09yNjP3mBW0Mh0TBvO3MAWHkXkhp8IKmmR34BLVuuOyEcr59k3WDzKp5euKoL7dyb87bQBOucHv9MF0RnkEVCg01r51o3dy7NiqeOvzxlEUojoRh16NvlUvkJ9SnGlElk4aMN9T5W', NULL, NULL, NULL, NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 1, 0, 0, 0, 0, NULL, NULL, 0, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-06 08:50:14', '2026-07-06 08:50:14', NULL),
('19759a49-95ba-4909-a145-bc43cb9434aa', 'waTdnDbVqc2lH09yNjP3mBW0Mh0TBvO3MAWHkXkhp8IKmmR34BLVuuOyEcr59k3WDzKp5euKoL7dyb87bQBOucHv9MF0RnkEVCg01r51o3dy7NiqeOvzxlEUojoRh16NvlUvkJ9SnGlElk4aMN9T5W', NULL, NULL, NULL, NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 1, 0, 0, 0, 0, NULL, NULL, 0, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-06 08:50:14', '2026-07-06 08:50:14', NULL),
('fed1d2af-2dbe-4f49-a780-3d7cfe500c50', 'waTdnDbVqc2lH09yNjP3mBW0Mh0TBvO3MAWHkXkhp8IKmmR34BLVuuOyEcr59k3WDzKp5euKoL7dyb87bQBOucHv9MF0RnkEVCg01r51o3dy7NiqeOvzxlEUojoRh16NvlUvkJ9SnGlElk4aMN9T5W', NULL, NULL, NULL, NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 1, 0, 0, 0, 0, NULL, NULL, 0, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-06 08:50:14', '2026-07-06 08:50:14', NULL),
('cc5ae3e0-7fd2-4655-8a5c-48311247d3ba', 'waTdnDbVqc2lH09yNjP3mBW0Mh0TBvO3MAWHkXkhp8IKmmR34BLVuuOyEcr59k3WDzKp5euKoL7dyb87bQBOucHv9MF0RnkEVCg01r51o3dy7NiqeOvzxlEUojoRh16NvlUvkJ9SnGlElk4aMN9T5W', NULL, NULL, NULL, NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 1, 0, 0, 0, 0, NULL, NULL, 0, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-06 08:50:14', '2026-07-06 08:50:14', NULL),
('c1c8b689-9e87-42dc-9b33-600ec9cf17bd', 'qVCMYQLfpSkGnQlQXHORj1x131kqqfNhuxI6Vqx50evCFnrVnUQszaM4fT5GzlqnDImU0rQdafHD03yCdDKcf0cPCSfcjS3cSUxZBdAC4dT5ai9hTNg3zpkDxzN2YEdpzX5bERuNOpkLgrlb88ZL6W', 'CH-01', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 15:35:07', NULL),
('9a15839c-f571-4856-b86b-d001ced7a2b5', 'qVCMYQLfpSkGnQlQXHORj1x131kqqfNhuxI6Vqx50evCFnrVnUQszaM4fT5GzlqnDImU0rQdafHD03yCdDKcf0cPCSfcjS3cSUxZBdAC4dT5ai9hTNg3zpkDxzN2YEdpzX5bERuNOpkLgrlb88ZL6W', 'CH-02', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 15:35:07', NULL),
('aa9376f8-7dfa-440f-8ed3-5e4e22665e0a', 'qVCMYQLfpSkGnQlQXHORj1x131kqqfNhuxI6Vqx50evCFnrVnUQszaM4fT5GzlqnDImU0rQdafHD03yCdDKcf0cPCSfcjS3cSUxZBdAC4dT5ai9hTNg3zpkDxzN2YEdpzX5bERuNOpkLgrlb88ZL6W', 'CH-03', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 15:35:07', NULL),
('0c184f8e-3e6e-4014-9940-76c16f9dfcf2', 'qVCMYQLfpSkGnQlQXHORj1x131kqqfNhuxI6Vqx50evCFnrVnUQszaM4fT5GzlqnDImU0rQdafHD03yCdDKcf0cPCSfcjS3cSUxZBdAC4dT5ai9hTNg3zpkDxzN2YEdpzX5bERuNOpkLgrlb88ZL6W', 'CH-04', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 15:35:07', NULL),
('8aa139e7-5e62-44ee-a6f4-1b4b61f97db9', 'qVCMYQLfpSkGnQlQXHORj1x131kqqfNhuxI6Vqx50evCFnrVnUQszaM4fT5GzlqnDImU0rQdafHD03yCdDKcf0cPCSfcjS3cSUxZBdAC4dT5ai9hTNg3zpkDxzN2YEdpzX5bERuNOpkLgrlb88ZL6W', 'CH-05', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 15:35:07', NULL),
('620e378f-4fc3-4f32-9095-e1d60dd312eb', 'qVCMYQLfpSkGnQlQXHORj1x131kqqfNhuxI6Vqx50evCFnrVnUQszaM4fT5GzlqnDImU0rQdafHD03yCdDKcf0cPCSfcjS3cSUxZBdAC4dT5ai9hTNg3zpkDxzN2YEdpzX5bERuNOpkLgrlb88ZL6W', 'CH-06', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 15:35:07', NULL),
('15df47f5-2dfc-456c-b3f0-2de67e363dd8', 'qVCMYQLfpSkGnQlQXHORj1x131kqqfNhuxI6Vqx50evCFnrVnUQszaM4fT5GzlqnDImU0rQdafHD03yCdDKcf0cPCSfcjS3cSUxZBdAC4dT5ai9hTNg3zpkDxzN2YEdpzX5bERuNOpkLgrlb88ZL6W', 'CH-07', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 15:35:07', NULL),
('3003c0c1-23cc-4022-b000-e1d7075fd62c', 'qVCMYQLfpSkGnQlQXHORj1x131kqqfNhuxI6Vqx50evCFnrVnUQszaM4fT5GzlqnDImU0rQdafHD03yCdDKcf0cPCSfcjS3cSUxZBdAC4dT5ai9hTNg3zpkDxzN2YEdpzX5bERuNOpkLgrlb88ZL6W', 'CH-08', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 15:35:07', NULL),
('c9e3c26c-f733-4e70-8d8b-bf3decd0234f', 'qVCMYQLfpSkGnQlQXHORj1x131kqqfNhuxI6Vqx50evCFnrVnUQszaM4fT5GzlqnDImU0rQdafHD03yCdDKcf0cPCSfcjS3cSUxZBdAC4dT5ai9hTNg3zpkDxzN2YEdpzX5bERuNOpkLgrlb88ZL6W', 'CH-09', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 15:35:07', NULL),
('e3a903de-4fd5-4367-b272-98fc5fbd8ddd', 'qVCMYQLfpSkGnQlQXHORj1x131kqqfNhuxI6Vqx50evCFnrVnUQszaM4fT5GzlqnDImU0rQdafHD03yCdDKcf0cPCSfcjS3cSUxZBdAC4dT5ai9hTNg3zpkDxzN2YEdpzX5bERuNOpkLgrlb88ZL6W', 'CH-10', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 15:35:07', NULL),
('804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'K5EudOorMz5iSJ1PGkFpEVG3zsG3JESIP399xHEFMYVnaIqti3bXSXqO9z9Ryf6Gs02tAdPBUHXOl4zg2rEMFFUKUukRbeV2UJXjRtwLvxyjqR4tX0kJgZ5ocz4Vz7DXgFxZ4XE35UqbF4RZI0i86M', 'CH-01', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 1, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 10:12:22', NULL),
('7e2ea3a1-0770-4d0f-b40d-da2b257cb61f', 'K5EudOorMz5iSJ1PGkFpEVG3zsG3JESIP399xHEFMYVnaIqti3bXSXqO9z9Ryf6Gs02tAdPBUHXOl4zg2rEMFFUKUukRbeV2UJXjRtwLvxyjqR4tX0kJgZ5ocz4Vz7DXgFxZ4XE35UqbF4RZI0i86M', 'CH-02', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:53:38', NULL),
('09533b58-503d-4069-9fe6-2c79309a422b', 'K5EudOorMz5iSJ1PGkFpEVG3zsG3JESIP399xHEFMYVnaIqti3bXSXqO9z9Ryf6Gs02tAdPBUHXOl4zg2rEMFFUKUukRbeV2UJXjRtwLvxyjqR4tX0kJgZ5ocz4Vz7DXgFxZ4XE35UqbF4RZI0i86M', 'CH-03', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:53:38', NULL),
('f75703a7-dcce-4bcb-a1a3-17735f32193e', 'K5EudOorMz5iSJ1PGkFpEVG3zsG3JESIP399xHEFMYVnaIqti3bXSXqO9z9Ryf6Gs02tAdPBUHXOl4zg2rEMFFUKUukRbeV2UJXjRtwLvxyjqR4tX0kJgZ5ocz4Vz7DXgFxZ4XE35UqbF4RZI0i86M', 'CH-04', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:53:38', NULL),
('84099055-1fbd-43fb-b061-079d4b2da439', 'K5EudOorMz5iSJ1PGkFpEVG3zsG3JESIP399xHEFMYVnaIqti3bXSXqO9z9Ryf6Gs02tAdPBUHXOl4zg2rEMFFUKUukRbeV2UJXjRtwLvxyjqR4tX0kJgZ5ocz4Vz7DXgFxZ4XE35UqbF4RZI0i86M', 'CH-05', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:53:38', NULL),
('8855821a-ff7f-4535-9a0b-3a0442b3625d', 'K5EudOorMz5iSJ1PGkFpEVG3zsG3JESIP399xHEFMYVnaIqti3bXSXqO9z9Ryf6Gs02tAdPBUHXOl4zg2rEMFFUKUukRbeV2UJXjRtwLvxyjqR4tX0kJgZ5ocz4Vz7DXgFxZ4XE35UqbF4RZI0i86M', 'CH-06', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:53:38', NULL),
('cd1d8b33-4f0d-4bcc-9f5e-670cc4ece161', 'K5EudOorMz5iSJ1PGkFpEVG3zsG3JESIP399xHEFMYVnaIqti3bXSXqO9z9Ryf6Gs02tAdPBUHXOl4zg2rEMFFUKUukRbeV2UJXjRtwLvxyjqR4tX0kJgZ5ocz4Vz7DXgFxZ4XE35UqbF4RZI0i86M', 'CH-07', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:53:38', NULL),
('6f7bf24d-c91b-432b-9203-db2ea6c143c9', 'K5EudOorMz5iSJ1PGkFpEVG3zsG3JESIP399xHEFMYVnaIqti3bXSXqO9z9Ryf6Gs02tAdPBUHXOl4zg2rEMFFUKUukRbeV2UJXjRtwLvxyjqR4tX0kJgZ5ocz4Vz7DXgFxZ4XE35UqbF4RZI0i86M', 'CH-08', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:53:38', NULL),
('5fc2bbe3-447d-4758-8154-4948ed89c5b7', 'K5EudOorMz5iSJ1PGkFpEVG3zsG3JESIP399xHEFMYVnaIqti3bXSXqO9z9Ryf6Gs02tAdPBUHXOl4zg2rEMFFUKUukRbeV2UJXjRtwLvxyjqR4tX0kJgZ5ocz4Vz7DXgFxZ4XE35UqbF4RZI0i86M', 'CH-09', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:53:38', NULL),
('2c471588-30dc-4315-aa29-78f5a421d897', 'K5EudOorMz5iSJ1PGkFpEVG3zsG3JESIP399xHEFMYVnaIqti3bXSXqO9z9Ryf6Gs02tAdPBUHXOl4zg2rEMFFUKUukRbeV2UJXjRtwLvxyjqR4tX0kJgZ5ocz4Vz7DXgFxZ4XE35UqbF4RZI0i86M', 'CH-10', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 0, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:53:38', NULL),
('1c1821f6-8781-4657-a3b7-b36983e7530a', 'pJVj2RBoMksSkXIMqjxFO1Q1DGP2RzIHundrwx2KpLL4EjgFLAGKLh2BwmtDWokFKAgRDP7r320WLygT1iiXOqVYVMnsvzQwOECT8hNwNuZRODskJCjF8CykHsP95rrQOnIf0tVOwTFu0yth8R8cvX', 'CH-01', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 1, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:51:18', NULL),
('05cbf47a-ee9d-48a8-aa31-a72f81a4b180', 'pJVj2RBoMksSkXIMqjxFO1Q1DGP2RzIHundrwx2KpLL4EjgFLAGKLh2BwmtDWokFKAgRDP7r320WLygT1iiXOqVYVMnsvzQwOECT8hNwNuZRODskJCjF8CykHsP95rrQOnIf0tVOwTFu0yth8R8cvX', 'CH-02', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 1, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:51:18', NULL),
('ab3ead0a-d3a6-4940-9adc-f222c3151350', 'pJVj2RBoMksSkXIMqjxFO1Q1DGP2RzIHundrwx2KpLL4EjgFLAGKLh2BwmtDWokFKAgRDP7r320WLygT1iiXOqVYVMnsvzQwOECT8hNwNuZRODskJCjF8CykHsP95rrQOnIf0tVOwTFu0yth8R8cvX', 'CH-03', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 1, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:51:18', NULL),
('b28d8f67-ad53-4591-bfa6-f1e88e0ca796', 'pJVj2RBoMksSkXIMqjxFO1Q1DGP2RzIHundrwx2KpLL4EjgFLAGKLh2BwmtDWokFKAgRDP7r320WLygT1iiXOqVYVMnsvzQwOECT8hNwNuZRODskJCjF8CykHsP95rrQOnIf0tVOwTFu0yth8R8cvX', 'CH-04', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 1, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:51:18', NULL),
('487163bf-762a-449e-9c73-5c23a12b8b6b', 'pJVj2RBoMksSkXIMqjxFO1Q1DGP2RzIHundrwx2KpLL4EjgFLAGKLh2BwmtDWokFKAgRDP7r320WLygT1iiXOqVYVMnsvzQwOECT8hNwNuZRODskJCjF8CykHsP95rrQOnIf0tVOwTFu0yth8R8cvX', 'CH-05', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 1, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:51:18', NULL),
('37a48c3a-016f-4aa4-a80f-272b0d7cfa7a', 'pJVj2RBoMksSkXIMqjxFO1Q1DGP2RzIHundrwx2KpLL4EjgFLAGKLh2BwmtDWokFKAgRDP7r320WLygT1iiXOqVYVMnsvzQwOECT8hNwNuZRODskJCjF8CykHsP95rrQOnIf0tVOwTFu0yth8R8cvX', 'CH-06', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 1, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:51:18', NULL),
('e5b54a62-6ac1-44b6-8825-bda93f5e231a', 'pJVj2RBoMksSkXIMqjxFO1Q1DGP2RzIHundrwx2KpLL4EjgFLAGKLh2BwmtDWokFKAgRDP7r320WLygT1iiXOqVYVMnsvzQwOECT8hNwNuZRODskJCjF8CykHsP95rrQOnIf0tVOwTFu0yth8R8cvX', 'CH-07', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 1, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:51:18', NULL),
('96c94717-b46d-4e12-9166-a6571b2d316c', 'pJVj2RBoMksSkXIMqjxFO1Q1DGP2RzIHundrwx2KpLL4EjgFLAGKLh2BwmtDWokFKAgRDP7r320WLygT1iiXOqVYVMnsvzQwOECT8hNwNuZRODskJCjF8CykHsP95rrQOnIf0tVOwTFu0yth8R8cvX', 'CH-08', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 1, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:51:18', NULL),
('e692d1b6-980c-4df3-a573-b630fb5ac37e', 'pJVj2RBoMksSkXIMqjxFO1Q1DGP2RzIHundrwx2KpLL4EjgFLAGKLh2BwmtDWokFKAgRDP7r320WLygT1iiXOqVYVMnsvzQwOECT8hNwNuZRODskJCjF8CykHsP95rrQOnIf0tVOwTFu0yth8R8cvX', 'CH-09', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 1, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:51:18', NULL),
('acc9a5ff-612f-433e-9eea-a564cb3e4562', 'pJVj2RBoMksSkXIMqjxFO1Q1DGP2RzIHundrwx2KpLL4EjgFLAGKLh2BwmtDWokFKAgRDP7r320WLygT1iiXOqVYVMnsvzQwOECT8hNwNuZRODskJCjF8CykHsP95rrQOnIf0tVOwTFu0yth8R8cvX', 'CH-10', 'bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'c1123cbd-658c-483a-a246-dc6726039649', 'actif', NULL, '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'disponible', 2, 6000, 0, 0, 0, NULL, 0.00, 1, 2, 'standard', '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:51:18', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `cite`
--

CREATE TABLE `cite` (
  `id_cite` varchar(150) NOT NULL,
  `code_cite` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type_cite_id` varchar(150) NOT NULL,
  `campus_id` varchar(150) NOT NULL,
  `adresse` text DEFAULT NULL,
  `capacite_max` int(11) DEFAULT 0,
  `nombre_batiments` int(11) DEFAULT 0,
  `superficie` decimal(10,2) DEFAULT 0.00,
  `annee_construction` year(4) DEFAULT NULL,
  `responsable_id` varchar(150) DEFAULT NULL,
  `telephone1` varchar(20) DEFAULT NULL,
  `telephone2` varchar(20) DEFAULT NULL,
  `telephone3` varchar(20) DEFAULT NULL,
  `email1` varchar(255) DEFAULT NULL,
  `email2` varchar(255) DEFAULT NULL,
  `longitude` float DEFAULT NULL,
  `latitude` float DEFAULT NULL,
  `reglement_interieur_id` varchar(150) DEFAULT NULL,
  `statut` enum('ouvert','ferme','en_renovation','en_construction') DEFAULT 'ouvert',
  `wifi_gratuit` tinyint(4) NOT NULL DEFAULT 0,
  `plan` varchar(255) DEFAULT NULL,
  `salle_etude` tinyint(4) NOT NULL DEFAULT 0,
  `infirmerie` tinyint(4) NOT NULL DEFAULT 0,
  `restaurant` tinyint(4) NOT NULL DEFAULT 0,
  `created_by` varchar(150) DEFAULT NULL,
  `deleted_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `cite`
--

INSERT INTO `cite` (`id_cite`, `code_cite`, `name`, `type_cite_id`, `campus_id`, `adresse`, `capacite_max`, `nombre_batiments`, `superficie`, `annee_construction`, `responsable_id`, `telephone1`, `telephone2`, `telephone3`, `email1`, `email2`, `longitude`, `latitude`, `reglement_interieur_id`, `statut`, `wifi_gratuit`, `plan`, `salle_etude`, `infirmerie`, `restaurant`, `created_by`, `deleted_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', 'Kalilou', 'cite kalilou', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', '3c11a70c-a236-4177-a98c-a77b5c45530c', 'cite kalilou', 1000, 10, NULL, '2026', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '0701010190', NULL, NULL, 'cite@kalilou.ci', NULL, -6.45365, 6.89706, NULL, 'ouvert', 1, NULL, 1, 1, 1, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-06-18 17:56:34', '2026-06-18 22:44:14', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `config_app`
--

CREATE TABLE `config_app` (
  `config_id` varchar(150) NOT NULL,
  `app_name` varchar(100) NOT NULL DEFAULT 'Crou de Bondoukou',
  `locale` varchar(10) DEFAULT 'fr',
  `timezone` varchar(50) DEFAULT 'Africa/Abidjan',
  `date_format` varchar(20) DEFAULT 'd/m/Y',
  `maintenance_mode` tinyint(1) DEFAULT 0,
  `public_registration` tinyint(1) DEFAULT 1,
  `activity_log` tinyint(1) DEFAULT 1,
  `force_dark_mode` tinyint(1) DEFAULT 0,
  `company_name` varchar(150) DEFAULT NULL,
  `country` varchar(60) DEFAULT 'Côte d''Ivoire',
  `region_id` varchar(150) DEFAULT NULL,
  `ville_id` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `brand_color` varchar(7) DEFAULT '#534AB7',
  `two_factor_auth` tinyint(1) DEFAULT 0,
  `two_factor_method` enum('totp','sms','email') DEFAULT 'totp',
  `session_lifetime` int(11) DEFAULT 120,
  `allow_multiple_sessions` tinyint(1) DEFAULT 1,
  `password_min_length` int(11) DEFAULT 8,
  `password_require_uppercase` tinyint(1) DEFAULT 1,
  `password_require_number` tinyint(1) DEFAULT 1,
  `password_require_special` tinyint(1) DEFAULT 0,
  `password_expiry_days` int(11) DEFAULT 0,
  `max_login_attempts` int(11) DEFAULT 5,
  `lockout_duration` int(11) DEFAULT 15,
  `ip_restriction` tinyint(1) DEFAULT 0,
  `allowed_ips` text DEFAULT NULL,
  `force_https` tinyint(1) DEFAULT 1,
  `tva_enabled` tinyint(1) DEFAULT 1,
  `tva_rate` decimal(5,2) DEFAULT 18.00,
  `tva_number` varchar(50) DEFAULT NULL,
  `price_display` enum('TTC','HT') DEFAULT 'TTC',
  `tva_on_services` tinyint(1) DEFAULT 1,
  `digital_tax_rate` decimal(5,2) DEFAULT 0.00,
  `service_fee` int(11) DEFAULT 0,
  `tax_on_delivery` tinyint(1) DEFAULT 0,
  `google_maps_enabled` tinyint(1) DEFAULT 0,
  `google_maps_key` varchar(255) DEFAULT NULL,
  `ga_tracking_id` varchar(30) DEFAULT NULL,
  `fb_pixel_id` varchar(30) DEFAULT NULL,
  `livechat_enabled` tinyint(1) DEFAULT 0,
  `livechat_provider` varchar(50) DEFAULT NULL,
  `livechat_token` varchar(255) DEFAULT NULL,
  `social_google` tinyint(1) DEFAULT 0,
  `social_facebook` tinyint(1) DEFAULT 0,
  `social_github` tinyint(1) DEFAULT 0,
  `methods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '["card","mobile"]' CHECK (json_valid(`methods`)),
  `online_payment_enabled` tinyint(1) DEFAULT 1,
  `payment_gateway` varchar(50) DEFAULT 'cinetpay',
  `api_key_public` varchar(255) DEFAULT NULL,
  `api_key_secret` varchar(255) DEFAULT NULL,
  `sandbox_mode` tinyint(1) DEFAULT 1,
  `currency` varchar(10) DEFAULT 'XOF',
  `auto_receipt` tinyint(1) DEFAULT 1,
  `min_amount` int(11) DEFAULT 500,
  `three_d_secure` tinyint(1) DEFAULT 1,
  `anti_fraud` tinyint(1) DEFAULT 1,
  `daily_limit` int(11) DEFAULT 500000,
  `email_enabled` tinyint(1) DEFAULT 1,
  `mail_driver` varchar(30) DEFAULT 'smtp',
  `mail_host` varchar(100) DEFAULT NULL,
  `mail_port` int(11) DEFAULT 587,
  `mail_username` varchar(100) DEFAULT NULL,
  `mail_password` varchar(255) DEFAULT NULL,
  `mail_from_name` varchar(100) DEFAULT NULL,
  `mail_from_address` varchar(100) DEFAULT NULL,
  `sms_enabled` tinyint(1) DEFAULT 0,
  `sms_provider` varchar(50) DEFAULT NULL,
  `sms_api_key` varchar(100) DEFAULT NULL,
  `sms_sender` varchar(30) DEFAULT 'MonApp',
  `notif_new_user` tinyint(1) DEFAULT 1,
  `notif_new_order` tinyint(1) DEFAULT 1,
  `notif_payment_ok` tinyint(1) DEFAULT 1,
  `notif_payment_fail` tinyint(1) DEFAULT 1,
  `notif_low_stock` tinyint(1) DEFAULT 1,
  `notif_report` tinyint(1) DEFAULT 1,
  `base_currency` varchar(10) DEFAULT 'XOF',
  `currency_symbol` varchar(10) DEFAULT 'FCFA',
  `symbol_position` enum('before','after') DEFAULT 'after',
  `thousands_separator` varchar(1) DEFAULT ' ',
  `secondary_currencies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]' CHECK (json_valid(`secondary_currencies`)),
  `auto_rate` tinyint(1) DEFAULT 0,
  `rate_frequency` enum('hourly','daily','weekly','manual') DEFAULT 'daily',
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sms_api_url` varchar(2048) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `config_app`
--

INSERT INTO `config_app` (`config_id`, `app_name`, `locale`, `timezone`, `date_format`, `maintenance_mode`, `public_registration`, `activity_log`, `force_dark_mode`, `company_name`, `country`, `region_id`, `ville_id`, `phone`, `email`, `website`, `logo`, `brand_color`, `two_factor_auth`, `two_factor_method`, `session_lifetime`, `allow_multiple_sessions`, `password_min_length`, `password_require_uppercase`, `password_require_number`, `password_require_special`, `password_expiry_days`, `max_login_attempts`, `lockout_duration`, `ip_restriction`, `allowed_ips`, `force_https`, `tva_enabled`, `tva_rate`, `tva_number`, `price_display`, `tva_on_services`, `digital_tax_rate`, `service_fee`, `tax_on_delivery`, `google_maps_enabled`, `google_maps_key`, `ga_tracking_id`, `fb_pixel_id`, `livechat_enabled`, `livechat_provider`, `livechat_token`, `social_google`, `social_facebook`, `social_github`, `methods`, `online_payment_enabled`, `payment_gateway`, `api_key_public`, `api_key_secret`, `sandbox_mode`, `currency`, `auto_receipt`, `min_amount`, `three_d_secure`, `anti_fraud`, `daily_limit`, `email_enabled`, `mail_driver`, `mail_host`, `mail_port`, `mail_username`, `mail_password`, `mail_from_name`, `mail_from_address`, `sms_enabled`, `sms_provider`, `sms_api_key`, `sms_sender`, `notif_new_user`, `notif_new_order`, `notif_payment_ok`, `notif_payment_fail`, `notif_low_stock`, `notif_report`, `base_currency`, `currency_symbol`, `symbol_position`, `thousands_separator`, `secondary_currencies`, `auto_rate`, `rate_frequency`, `created_by`, `updated_by`, `created_at`, `updated_at`, `sms_api_url`, `address`) VALUES
('ed3acb1a-d6f6-45c0-979a-b6bbbc08cd05', 'Crou de Daloa', 'fr', 'Africa/Abidjan', 'd/m/Y', 0, 0, 1, 0, 'Centre Régional des Œuvres Universitaires de Daloa', 'Côte d\'Ivoire', '16', '62', '+2250707902963', 'rodrigue.yapo@soumafe.ci', NULL, 'https://croudedaloa.soumafe.ci/admin/assets/images/logos/6ace0590aac7b3.65231798.jpeg', '#000000', 0, 'totp', 120, 1, 6, 1, 1, 0, 0, 5, 15, 0, NULL, 0, 1, 18.00, NULL, 'TTC', 1, 0.00, 0, 0, 0, NULL, NULL, NULL, 0, NULL, NULL, 0, 0, 0, '[\"cash\",\"mobile\"]', 1, 'orange', NULL, NULL, 1, 'XOF', 1, 500, 0, 0, 500000, 1, 'smtp', NULL, 587, NULL, NULL, 'Crou_connect', NULL, 1, 'orange_sms_api', NULL, 'MonApp', 1, 1, 1, 1, 1, 1, 'XOF', 'FCFA', 'after', NULL, '[\"NGN\",\"GBP\"]', 0, 'daily', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-06-29 12:58:24', '2026-10-13 10:40:25', NULL, 'Bondoukou');

-- --------------------------------------------------------

--
-- Structure de la table `countrie`
--

CREATE TABLE `countrie` (
  `id` int(11) NOT NULL,
  `name` varchar(225) DEFAULT NULL,
  `code` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `countrie`
--

INSERT INTO `countrie` (`id`, `name`, `code`, `created_at`, `updated_at`) VALUES
(1, 'Côte d\'Ivoire', 'CI', NULL, NULL),
(2, 'Togo', 'TG', '2025-12-31 13:09:02', '2025-12-31 13:09:02'),
(4, 'Cameroun', 'Cam', '2026-03-11 16:17:03', '2026-03-11 16:17:11');

-- --------------------------------------------------------

--
-- Structure de la table `demande`
--

CREATE TABLE `demande` (
  `id_demande` char(150) NOT NULL,
  `etudiant_id` varchar(200) NOT NULL,
  `campagne_id` varchar(150) DEFAULT NULL,
  `date_demande` timestamp NOT NULL DEFAULT current_timestamp(),
  `numero_demande` varchar(50) DEFAULT NULL,
  `type_hebergement_souhaite` varchar(50) DEFAULT NULL,
  `annee_academique_precedent` varchar(250) DEFAULT NULL,
  `annee_academique_actuel` varchar(150) DEFAULT NULL,
  `niveau_etude_precedent_code` varchar(250) DEFAULT NULL,
  `niveau_etude_actuel_code` varchar(250) DEFAULT NULL,
  `filiere_code` varchar(250) DEFAULT NULL,
  `moyenne` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_valider` int(11) DEFAULT NULL,
  `boursier` tinyint(4) NOT NULL DEFAULT 0,
  `periode` varchar(20) DEFAULT NULL,
  `statut_demande` enum('En_attente','En_verification','Acceptée','Refusée') DEFAULT 'En_attente',
  `commentaires` text DEFAULT NULL,
  `created_by` varchar(200) DEFAULT NULL,
  `updated_by` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `etat_dossier` varchar(30) NOT NULL DEFAULT 'soumis',
  `assignation_id` varchar(150) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `demande`
--

INSERT INTO `demande` (`id_demande`, `etudiant_id`, `campagne_id`, `date_demande`, `numero_demande`, `type_hebergement_souhaite`, `annee_academique_precedent`, `annee_academique_actuel`, `niveau_etude_precedent_code`, `niveau_etude_actuel_code`, `filiere_code`, `moyenne`, `credit_valider`, `boursier`, `periode`, `statut_demande`, `commentaires`, `created_by`, `updated_by`, `created_at`, `updated_at`, `etat_dossier`, `assignation_id`) VALUES
('130a5b92-86ad-4953-86d2-5d3689a11762', 'd052d932-81c1-4065-bf7a-c6bbda59d7d1', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', '2026-08-03 16:28:44', 'CROU-2026-52539', NULL, '2025-2026', '2025-2026', '3df1433f-d890-4739-ba08-ca1523f4c8de', '2ba2b3e7-b046-4160-9201-47070b58ba35', '1', 12.00, 90, 0, '2026', 'En_attente', 'Éligibilité vérifiée automatiquement : score 100% (hors documents).', NULL, NULL, '2026-08-03 16:28:44', '2026-08-03 16:28:46', 'soumis', NULL),
('596920df-512f-4178-8e6c-269b3f5cf1ea', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', '2026-08-03 15:58:57', 'CROU-2026-25228', NULL, '2025-2026', '2025-2026', '3df1433f-d890-4739-ba08-ca1523f4c8de', 'cf8b2108-0ee3-4b41-9cad-67785f1c1618', '1', 12.00, 90, 0, '2026', 'Acceptée', 'l\'attribution de chambre est effective', NULL, 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', '2026-08-03 15:58:57', '2026-09-24 09:05:55', 'attribue_definitif', 'bdc6e064-477e-4bbf-97a3-233965e42a50'),
('bfb2d82e-6b3e-4c0f-889d-fd848cee838a', 'a5802ce0-35ea-4649-af43-c61ecebe4f0a', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', '2026-08-05 11:12:54', 'CROU-2026-27389', NULL, '2025-2026', '2025-2026', '3df1433f-d890-4739-ba08-ca1523f4c8de', 'cf8b2108-0ee3-4b41-9cad-67785f1c1618', '1', 9.00, 86, 0, '2026', 'En_attente', 'Éligibilité vérifiée automatiquement : score 88% (hors documents).', NULL, 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', '2026-08-05 11:12:54', '2026-08-05 11:13:03', 'soumis', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `departement`
--

CREATE TABLE `departement` (
  `id_departement` int(11) NOT NULL,
  `code_departement` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `abreviation` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `campus_code` varchar(150) DEFAULT NULL,
  `directeur_code` varchar(150) DEFAULT NULL,
  `directeur_adjoint_code` varchar(150) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `site_web` varchar(500) DEFAULT NULL,
  `annee_creation` year(4) DEFAULT NULL,
  `nombre_filieres` int(11) DEFAULT 0,
  `nombre_enseignants` int(11) DEFAULT 0,
  `nombre_etudiants` int(11) DEFAULT 0,
  `superficie` decimal(8,2) DEFAULT NULL,
  `laboratoires` text DEFAULT NULL,
  `equipements_principaux` text DEFAULT NULL,
  `partenariats` text DEFAULT NULL,
  `budget_annuel` decimal(14,2) DEFAULT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `couleur_hex` varchar(7) DEFAULT NULL,
  `statut` enum('actif','inactif','fusionne','dissous') DEFAULT 'actif',
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `deleted_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `departement`
--

INSERT INTO `departement` (`id_departement`, `code_departement`, `name`, `abreviation`, `description`, `campus_code`, `directeur_code`, `directeur_adjoint_code`, `adresse`, `telephone`, `email`, `site_web`, `annee_creation`, `nombre_filieres`, `nombre_enseignants`, `nombre_etudiants`, `superficie`, `laboratoires`, `equipements_principaux`, `partenariats`, `budget_annuel`, `logo`, `couleur_hex`, `statut`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '69HXXxJMnivT8eumK5vcNjN1MWpiWHuKp5QuAI7crKBVSW4UAd', 'Département math-info', 'Math-info', 'math-info', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'actif', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, '2026-01-14 13:06:30', '2026-03-11 16:30:20', NULL),
(3, 'RrFb7DMX9LZ4rLVf9H7DBTm7Qo0kxg5BYlF1nA0CGLo6p5qqhK', 'Sciences économiques', 'Sciences éco', 'Sciences de l\'économie', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'actif', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, NULL, '2026-03-11 16:25:41', '2026-03-11 16:25:41', NULL),
(4, '2x8TewJizkR5HRTelW4PaQIq9LvTbNEAE9BRr2NsQILjiLhNO8', 'Département Réseaux & Telecommunications', 'RTEL', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'actif', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, NULL, '2026-03-11 16:26:42', '2026-03-11 16:26:42', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `departure_requests`
--

CREATE TABLE `departure_requests` (
  `id_demande_sortie` char(36) NOT NULL,
  `etudiant_id` varchar(150) NOT NULL,
  `assignation_id` varchar(150) NOT NULL,
  `chambre_id` varchar(150) NOT NULL,
  `date_demande` date NOT NULL,
  `date_sortie_prevue` date NOT NULL,
  `motif` varchar(100) NOT NULL,
  `observations` text DEFAULT NULL,
  `statut` enum('en_attente','cloturee','annulee') NOT NULL DEFAULT 'en_attente',
  `decision` varchar(150) DEFAULT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sanction_id` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `departure_settings`
--

CREATE TABLE `departure_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `notice_days` int(10) UNSIGNED NOT NULL DEFAULT 30,
  `late_notice_penalty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unpaid_blocking_threshold` decimal(12,2) NOT NULL DEFAULT 0.00,
  `damage_retention_grid` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`damage_retention_grid`)),
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `departure_settings`
--

INSERT INTO `departure_settings` (`id`, `notice_days`, `late_notice_penalty`, `unpaid_blocking_threshold`, `damage_retention_grid`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 30, 0.00, 0.00, '[]', NULL, '2026-08-10 23:41:20', '2026-08-10 23:41:20');

-- --------------------------------------------------------

--
-- Structure de la table `departure_settlements`
--

CREATE TABLE `departure_settlements` (
  `id_decompte` char(36) NOT NULL,
  `demande_sortie_id` char(36) DEFAULT NULL,
  `etat_des_lieux_id` varchar(150) NOT NULL,
  `etudiant_id` varchar(150) NOT NULL,
  `chambre_id` varchar(150) NOT NULL,
  `preavis_requis_jours` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `preavis_effectif_jours` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `penalite_preavis` decimal(12,2) NOT NULL DEFAULT 0.00,
  `retenues_degradations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`retenues_degradations`)),
  `total_degradations` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_retenues` decimal(12,2) NOT NULL DEFAULT 0.00,
  `impute_caution` decimal(12,2) NOT NULL DEFAULT 0.00,
  `solde_debiteur` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `disciplinary_reports`
--

CREATE TABLE `disciplinary_reports` (
  `id_rapport` char(36) NOT NULL,
  `numero_rapport` varchar(40) NOT NULL,
  `etudiant_id` varchar(150) NOT NULL,
  `declarant_id` varchar(150) DEFAULT NULL,
  `source` varchar(30) NOT NULL DEFAULT 'administration',
  `cite_id` varchar(150) NOT NULL,
  `batiment_id` varchar(150) NOT NULL,
  `chambre_id` varchar(150) NOT NULL,
  `date_incident` datetime NOT NULL,
  `lieu` varchar(255) DEFAULT NULL,
  `nature` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `temoins` text DEFAULT NULL,
  `confidentiel` tinyint(1) NOT NULL DEFAULT 0,
  `gravite` enum('faible','moyenne','grave','critique') NOT NULL DEFAULT 'moyenne',
  `statut` enum('brouillon','transmis','en_cours','retourne','decision_prise','cloture') NOT NULL DEFAULT 'brouillon',
  `decision` text DEFAULT NULL,
  `transmis_at` timestamp NULL DEFAULT NULL,
  `pris_en_charge_at` timestamp NULL DEFAULT NULL,
  `decision_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sanction_decidee` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `disciplinary_reports`
--

INSERT INTO `disciplinary_reports` (`id_rapport`, `numero_rapport`, `etudiant_id`, `declarant_id`, `source`, `cite_id`, `batiment_id`, `chambre_id`, `date_incident`, `lieu`, `nature`, `description`, `temoins`, `confidentiel`, `gravite`, `statut`, `decision`, `transmis_at`, `pris_en_charge_at`, `decision_at`, `created_by`, `updated_by`, `created_at`, `updated_at`, `sanction_decidee`) VALUES
('e1cdb369-4478-4fd2-9fc6-81a26098d20a', 'RDI-20260817-CIW1H', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'etudiant', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', 'c1123cbd-658c-483a-a246-dc6726039649', '1edb4ef8-3b43-4287-88c4-c1e1959e8ffa', '2026-08-17 17:00:00', 'le couloir', 'Tapage / nuisance sonore', 'testvdjkhdfhh;khfd;sljadsjkljadf.,sc', 'moi seul', 1, 'moyenne', 'en_cours', NULL, '2026-08-17 17:01:32', '2026-08-18 10:50:29', NULL, 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-17 17:01:32', '2026-08-18 10:50:29', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `disciplinary_report_histories`
--

CREATE TABLE `disciplinary_report_histories` (
  `id_historique` char(36) NOT NULL,
  `rapport_id` char(36) NOT NULL,
  `action` varchar(60) NOT NULL,
  `ancien_statut` varchar(30) DEFAULT NULL,
  `nouveau_statut` varchar(30) NOT NULL,
  `commentaire` text DEFAULT NULL,
  `auteur_id` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `disciplinary_report_histories`
--

INSERT INTO `disciplinary_report_histories` (`id_historique`, `rapport_id`, `action`, `ancien_statut`, `nouveau_statut`, `commentaire`, `auteur_id`, `created_at`) VALUES
('34053a90-a09b-4eb8-bec0-061f9fc481e5', 'e1cdb369-4478-4fd2-9fc6-81a26098d20a', 'prendre_en_charge', 'transmis', 'en_cours', 's', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-18 10:50:29'),
('806c7082-d384-49dc-903c-00f0e84729f8', 'e1cdb369-4478-4fd2-9fc6-81a26098d20a', 'declaration_etudiant', NULL, 'transmis', 'Incident déclaré depuis l’espace étudiant.', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', '2026-08-17 17:01:32');

-- --------------------------------------------------------

--
-- Structure de la table `document`
--

CREATE TABLE `document` (
  `id_document` varchar(150) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `delai_validite_mois` int(11) DEFAULT NULL,
  `format_accepte` varchar(255) DEFAULT 'pdf',
  `taille_max_mo` int(11) DEFAULT 10,
  `champs_requis` tinyint(4) DEFAULT 0,
  `statut` tinyint(4) DEFAULT 1,
  `ordre_affiche` int(11) DEFAULT 0,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `document`
--

INSERT INTO `document` (`id_document`, `name`, `slug`, `description`, `delai_validite_mois`, `format_accepte`, `taille_max_mo`, `champs_requis`, `statut`, `ordre_affiche`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('56ca2bfa-db92-4356-9727-08da7f904e45', 'CNI', 'cni', NULL, NULL, 'pdf', 10, 1, 1, 5, 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', '2026-02-25 12:06:39', '2026-03-13 09:58:57', NULL),
('61fc122e-0637-4ddc-8b0e-f781815ef3cf', 'Extrait de naissance', 'extrait-de-naissance', NULL, 3, 'pdf', 10, 1, 1, 2, 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, '2026-03-17 12:02:05', '2026-03-17 12:02:05', NULL),
('c34c7ddc-cca9-4d41-bef5-8cf93b5fc0a1', 'Passeport', 'passeport', NULL, 24, 'pdf', 10, 0, 1, 3, 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, '2026-03-17 12:18:16', '2026-03-17 12:18:16', NULL),
('fda508f3-b146-49dd-899c-8ab347f5e796', 'CMU', 'cmu', NULL, 24, 'pdf', 10, 0, 1, 4, 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, '2026-03-17 12:23:09', '2026-03-17 12:23:09', NULL),
('1384772c-86c2-435e-a53d-105295261d0e', 'Carte étudiant', 'carte-etudiant', NULL, NULL, 'pdf', 10, 0, 1, 3, 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, '2026-03-17 12:25:16', '2026-03-17 12:25:16', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `document_campagne`
--

CREATE TABLE `document_campagne` (
  `id_document_campagne` varchar(150) NOT NULL,
  `id_campagne` varchar(150) DEFAULT NULL,
  `document_id` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `document_campagne`
--

INSERT INTO `document_campagne` (`id_document_campagne`, `id_campagne`, `document_id`, `created_at`, `updated_at`) VALUES
('ffc5b00c-878d-48b5-92c8-3e55cf6175fc', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', '1384772c-86c2-435e-a53d-105295261d0e', '2026-08-03 15:56:34', '2026-08-03 15:56:34'),
('eabaf9bf-d9fc-4ee0-b782-c1d188beb6c6', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'fda508f3-b146-49dd-899c-8ab347f5e796', '2026-08-03 15:56:34', '2026-08-03 15:56:34'),
('1b362048-b2d3-466e-acd4-e6ba506bd533', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', '56ca2bfa-db92-4356-9727-08da7f904e45', '2026-08-03 15:56:34', '2026-08-03 15:56:34');

-- --------------------------------------------------------

--
-- Structure de la table `document_etudiant`
--

CREATE TABLE `document_etudiant` (
  `id_document` char(150) NOT NULL,
  `id_campagne` varchar(150) DEFAULT NULL,
  `etudiant_id` varchar(150) NOT NULL,
  `campus_code` varchar(150) DEFAULT NULL,
  `chemin_fichier` varchar(500) NOT NULL,
  `extension` varchar(10) NOT NULL,
  `taille_octets` int(11) DEFAULT 0,
  `type_document` varchar(150) DEFAULT NULL,
  `date_validation` date DEFAULT NULL,
  `valide_par` varchar(150) DEFAULT NULL,
  `statut` enum('depose','en_verification','valide','rejete','expire','archive') DEFAULT 'depose',
  `motif_rejet` text DEFAULT NULL,
  `score_qualite` int(11) DEFAULT NULL,
  `document_precedent_id` int(11) DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `created_by` varchar(250) DEFAULT NULL,
  `updated_by` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `document_etudiant`
--

INSERT INTO `document_etudiant` (`id_document`, `id_campagne`, `etudiant_id`, `campus_code`, `chemin_fichier`, `extension`, `taille_octets`, `type_document`, `date_validation`, `valide_par`, `statut`, `motif_rejet`, `score_qualite`, `document_precedent_id`, `observations`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
('4581c71d-2675-48b8-8da7-73a4210f55d2', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'a5802ce0-35ea-4649-af43-c61ecebe4f0a', NULL, 'https://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/6a731ab66f55b1.53573190.pdf', 'pdf', 0, 'CNI', '2026-08-05', NULL, 'valide', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-05 11:12:59', '2026-08-05 11:16:34'),
('335355ab-c84c-458d-8903-52df07bf9402', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'd052d932-81c1-4065-bf7a-c6bbda59d7d1', NULL, 'https://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/6a70c1be590534.13077624.pdf', 'pdf', 0, 'Carte étudiant', NULL, NULL, 'en_verification', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-03 16:28:46', '2026-08-03 16:28:46'),
('5f9e8557-82db-40f8-b2d2-0e4b05fd936f', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'd052d932-81c1-4065-bf7a-c6bbda59d7d1', NULL, 'https://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/6a70c1bdca8445.70732270.pdf', 'pdf', 0, 'CMU', NULL, NULL, 'en_verification', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-03 16:28:46', '2026-08-03 16:28:46'),
('77119807-dfa3-478d-b2e9-b62e15f5d1a7', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'd052d932-81c1-4065-bf7a-c6bbda59d7d1', NULL, 'https://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/6a70c1bc81be57.51589063.pdf', 'pdf', 0, 'CNI', NULL, NULL, 'en_verification', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-03 16:28:45', '2026-08-03 16:28:45'),
('52c1cc02-e796-4b28-9010-2f3951e97382', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'd052d932-81c1-4065-bf7a-c6bbda59d7d1', NULL, 'https://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/6a70c1bc81be57.51589063.pdf', 'pdf', 0, 'CNI', NULL, NULL, 'en_verification', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-03 16:28:45', '2026-08-03 16:28:45'),
('eb97c2e3-c694-406e-a20e-beaa3cdff2dd', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', NULL, 'https://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/6a70bac3232016.83924946.pdf', 'pdf', 0, 'Carte étudiant', '2026-08-03', NULL, 'valide', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-03 15:58:59', '2026-08-03 16:26:52'),
('f5beda2a-4c87-4844-945c-05f2907385b2', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', NULL, 'https://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/6a70bac2980428.20922432.pdf', 'pdf', 0, 'CMU', '2026-08-03', NULL, 'valide', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-03 15:58:59', '2026-08-03 16:26:54'),
('3da8e93e-2a34-4c4e-9286-8702629ff5ca', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'd052d932-81c1-4065-bf7a-c6bbda59d7d1', NULL, 'https://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/6a70c1bc81be57.51589063.pdf', 'pdf', 0, 'CNI', NULL, NULL, 'en_verification', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-03 16:28:45', '2026-08-03 16:28:45'),
('585d1aad-6929-4845-9af7-c06d9582a845', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', NULL, 'https://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/6a70bac16e5233.78164634.pdf', 'pdf', 0, 'CNI', '2026-08-03', NULL, 'valide', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-03 15:58:58', '2026-08-03 16:26:56'),
('aaf7716e-c31a-473a-b45f-f619e5c38234', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', NULL, 'https://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/6a70bac16e5233.78164634.pdf', 'pdf', 0, 'CNI', '2026-08-03', NULL, 'valide', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-03 15:58:58', '2026-08-03 16:26:58'),
('5bd30346-cbed-415a-8f9c-a4b49bed4e50', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', NULL, 'https://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/6a70bac16e5233.78164634.pdf', 'pdf', 0, 'CNI', '2026-08-03', NULL, 'valide', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-03 15:58:58', '2026-08-03 16:27:01'),
('00016eff-51fc-4344-b609-dcebae751583', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'a5802ce0-35ea-4649-af43-c61ecebe4f0a', NULL, 'https://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/6a731ab66f55b1.53573190.pdf', 'pdf', 0, 'CNI', '2026-08-05', NULL, 'valide', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-05 11:12:59', '2026-08-05 11:16:37'),
('de34372a-5346-4e9b-8e1f-d98066115cba', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'a5802ce0-35ea-4649-af43-c61ecebe4f0a', NULL, 'https://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/6a731ab66f55b1.53573190.pdf', 'pdf', 0, 'CNI', '2026-08-05', NULL, 'valide', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-05 11:12:59', '2026-08-05 11:16:41'),
('6a14601b-b47b-4257-bdf7-e845640c5922', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'a5802ce0-35ea-4649-af43-c61ecebe4f0a', NULL, 'https://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/6a731abb7291e8.82614616.pdf', 'pdf', 0, 'CMU', '2026-08-05', NULL, 'valide', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-05 11:13:03', '2026-08-05 11:16:44'),
('027c4c7c-ec63-4881-95ed-10fc6be10cbe', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'a5802ce0-35ea-4649-af43-c61ecebe4f0a', NULL, 'https://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/6a731abf0d5a83.79740030.pdf', 'pdf', 0, 'Carte étudiant', '2026-08-05', NULL, 'valide', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-05 11:13:03', '2026-08-05 11:16:49');

-- --------------------------------------------------------

--
-- Structure de la table `dossier_transitions`
--

CREATE TABLE `dossier_transitions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `demande_id` varchar(150) NOT NULL,
  `ancien_etat` varchar(30) DEFAULT NULL,
  `nouvel_etat` varchar(30) NOT NULL,
  `acteur_type` varchar(30) NOT NULL,
  `acteur_id` varchar(150) DEFAULT NULL,
  `motif` text DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `dossier_transitions`
--

INSERT INTO `dossier_transitions` (`id`, `demande_id`, `ancien_etat`, `nouvel_etat`, `acteur_type`, `acteur_id`, `motif`, `details`, `created_at`) VALUES
(1, '130a5b92-86ad-4953-86d2-5d3689a11762', NULL, 'soumis', 'reprise', NULL, 'Reprise des données existantes ; aucune validation historique supplémentaire présumée.', '{\"ancien_statut\":\"En_attente\"}', '2026-09-24 09:05:55'),
(2, '596920df-512f-4178-8e6c-269b3f5cf1ea', NULL, 'attribue_definitif', 'reprise', NULL, 'Reprise des données existantes ; aucune validation historique supplémentaire présumée.', '{\"ancien_statut\":\"Accept\\u00e9e\"}', '2026-09-24 09:05:55'),
(3, 'bfb2d82e-6b3e-4c0f-889d-fd848cee838a', NULL, 'soumis', 'reprise', NULL, 'Reprise des données existantes ; aucune validation historique supplémentaire présumée.', '{\"ancien_statut\":\"En_attente\"}', '2026-09-24 09:05:55');

-- --------------------------------------------------------

--
-- Structure de la table `echeances`
--

CREATE TABLE `echeances` (
  `id_echeance` varchar(150) NOT NULL,
  `echeancier_id` bigint(20) UNSIGNED NOT NULL COMMENT 'ID de l''échéancier',
  `numero_echeance` int(11) NOT NULL COMMENT 'Numéro de l''échéance',
  `mois` varchar(10) NOT NULL COMMENT 'Mois de l''échéance (ex: 2026-06)',
  `date_echeance` date NOT NULL COMMENT 'Date d''échéance',
  `montant_du` decimal(15,2) NOT NULL COMMENT 'Montant dû',
  `statut` enum('a_payer','paye','en_retard','annule') NOT NULL DEFAULT 'a_payer' COMMENT 'Statut de l''échéance',
  `paiement_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID du paiement associé',
  `date_paiement` datetime DEFAULT NULL COMMENT 'Date du paiement',
  `date_rappel1` datetime DEFAULT NULL COMMENT 'Date du premier rappel',
  `date_rappel2` datetime DEFAULT NULL COMMENT 'Date du deuxième rappel',
  `date_mise_en_demeure` datetime DEFAULT NULL COMMENT 'Date de mise en demeure',
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_mise_a_jour` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `penalite` decimal(15,2) DEFAULT 0.00 COMMENT 'Pénalité de retard',
  `penalite_appliquee` tinyint(1) DEFAULT 0 COMMENT 'Pénalité appliquée',
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `deleted_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Détail des échéances individuelles';

-- --------------------------------------------------------

--
-- Structure de la table `echeanciers`
--

CREATE TABLE `echeanciers` (
  `id_echeancier` varchar(150) NOT NULL,
  `contrat_id` varchar(150) NOT NULL COMMENT 'ID du contrat associé',
  `resident_id` varchar(150) NOT NULL COMMENT 'ID du résident',
  `periodicite` enum('mensuel','trimestriel','semestriel','annuel') NOT NULL DEFAULT 'mensuel' COMMENT 'Périodicité de paiement',
  `date_debut` date NOT NULL COMMENT 'Date de début de l''échéancier',
  `date_fin` date DEFAULT NULL COMMENT 'Date de fin de l''échéancier',
  `montant_par_echeance` decimal(15,2) NOT NULL COMMENT 'Montant par échéance',
  `nombre_echeances` int(11) NOT NULL DEFAULT 0 COMMENT 'Nombre total d''échéances',
  `echeances_effectuees` int(11) NOT NULL DEFAULT 0 COMMENT 'Nombre d''échéances effectuées',
  `statut` enum('actif','termine','suspendu','annule') NOT NULL DEFAULT 'actif' COMMENT 'Statut de l''échéancier',
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_mise_a_jour` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `deleted_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Échéanciers des paiements';

-- --------------------------------------------------------

--
-- Structure de la table `edl_element`
--

CREATE TABLE `edl_element` (
  `edl_element_id` varchar(150) NOT NULL,
  `id_edl_piece` varchar(150) NOT NULL,
  `name_element` varchar(150) DEFAULT NULL,
  `etat` enum('bon','moyen','mauvais') NOT NULL DEFAULT 'bon',
  `observation` text DEFAULT NULL,
  `ordre` int(11) NOT NULL DEFAULT 1,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `photo` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `edl_element`
--

INSERT INTO `edl_element` (`edl_element_id`, `id_edl_piece`, `name_element`, `etat`, `observation`, `ordre`, `updated_by`, `created_by`, `updated_at`, `created_at`, `photo`) VALUES
('91a2faeb-c539-4a1d-b9e5-f4981df3feb5', '2d3d5cc6-58a2-43d8-83fd-7b8cb1aff9e3', 'Équipements', 'mauvais', 'test', 7, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('4c72b4f5-c9bc-4e2f-b0d3-234a719d72a3', '2d3d5cc6-58a2-43d8-83fd-7b8cb1aff9e3', 'Électricité', 'bon', 'test', 6, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('8e62eb87-f807-4c41-838f-af77f6cd5a96', '2d3d5cc6-58a2-43d8-83fd-7b8cb1aff9e3', 'Porte', 'bon', 'test', 5, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('c378912e-b238-479d-bee1-30d61dbe5589', '2d3d5cc6-58a2-43d8-83fd-7b8cb1aff9e3', 'Fenêtres', 'bon', 'test', 4, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('d9770f2c-1c71-40f5-bbca-40451ed1fa9f', '2d3d5cc6-58a2-43d8-83fd-7b8cb1aff9e3', 'Plafond', 'bon', 'test', 3, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('e3f265df-06c8-448d-8666-e2911d293f20', '2d3d5cc6-58a2-43d8-83fd-7b8cb1aff9e3', 'Murs', 'bon', 'test', 2, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('edece04a-9e09-466b-ac6d-d1b8643cbe0d', '2d3d5cc6-58a2-43d8-83fd-7b8cb1aff9e3', 'Sol', 'bon', 'test', 1, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('cec02a84-e57b-4894-9250-9e1638f1079b', 'a2e7336e-a81b-4188-8923-06474143c3f3', 'Équipements', 'bon', '', 7, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('e2a3997e-d82d-4a9b-94ee-ae07ace6a09b', 'a2e7336e-a81b-4188-8923-06474143c3f3', 'Électricité', 'bon', '', 6, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('bf75398b-e973-4a3d-a5af-a75b6750ee06', 'a2e7336e-a81b-4188-8923-06474143c3f3', 'Porte', 'bon', '', 5, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('fb840411-9233-4307-8d02-bd0046bebac0', 'a2e7336e-a81b-4188-8923-06474143c3f3', 'Fenêtres', 'bon', '', 4, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('c24a513f-b74f-4a40-8796-c1e5c7acc400', 'a2e7336e-a81b-4188-8923-06474143c3f3', 'Plafond', 'bon', '', 3, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('770341c8-1fb3-4760-b642-7e4cfe004b4b', 'a2e7336e-a81b-4188-8923-06474143c3f3', 'Murs', 'bon', '', 2, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('d5644b0e-6df4-4b4c-9aa6-723849423025', 'a2e7336e-a81b-4188-8923-06474143c3f3', 'Sol', 'bon', '', 1, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('377a72a6-01d7-4315-b9c0-852f054f46a8', 'e518f9cd-f664-4eca-af9b-d570958dd9d9', 'Équipements', 'moyen', '', 7, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('39516bf3-dc64-4df6-b00a-8b63794cbc88', 'e518f9cd-f664-4eca-af9b-d570958dd9d9', 'Électricité', 'moyen', '', 6, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('cd88fbd8-0ff8-4a75-b892-7a5dc8ec8fa5', 'e518f9cd-f664-4eca-af9b-d570958dd9d9', 'Porte', 'moyen', '', 5, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('dc8a3daa-4ef1-4896-b8ac-c52744852c5f', 'e518f9cd-f664-4eca-af9b-d570958dd9d9', 'Fenêtres', 'moyen', '', 4, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('18f3e086-e630-4510-b650-53fa0fbdae2e', 'e518f9cd-f664-4eca-af9b-d570958dd9d9', 'Plafond', 'moyen', '', 3, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('20b13c3d-65be-432c-a08d-e7148bff2c8f', '87ebfffe-a32d-4d50-82c9-b96100f640ee', 'Sol', 'moyen', '', 1, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('03dc3ebc-9345-4378-9498-f75106d55fa0', 'e518f9cd-f664-4eca-af9b-d570958dd9d9', 'Murs', 'moyen', '', 2, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL),
('6fa186bb-8928-4f10-bb44-ff3128ece23c', '87ebfffe-a32d-4d50-82c9-b96100f640ee', 'Murs', 'moyen', '', 2, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('0bf7b023-1a1c-43b3-a077-ee64b7922457', '87ebfffe-a32d-4d50-82c9-b96100f640ee', 'Plafond', 'moyen', '', 3, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('58d2ddb8-bd3b-4ea1-b379-65a165cdfc51', '87ebfffe-a32d-4d50-82c9-b96100f640ee', 'Fenêtres', 'moyen', '', 4, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('df7d5d72-2014-46e0-8fb6-aabb3adf9bac', '87ebfffe-a32d-4d50-82c9-b96100f640ee', 'Porte', 'moyen', '', 5, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('5909bdbd-2cad-4d77-a77a-a3b01a8e8d73', '87ebfffe-a32d-4d50-82c9-b96100f640ee', 'Électricité', 'moyen', '', 6, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('397c03b0-73b7-4f9b-a3e9-ef3b58bc80c9', '87ebfffe-a32d-4d50-82c9-b96100f640ee', 'Équipements', 'moyen', '', 7, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('f73ba91e-9ec3-414f-8bb1-60278d1d4aea', 'ccd7e6fa-b98d-4e77-bd49-b5e31c7b30e8', 'Sol', 'bon', '', 1, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('e06c8596-a7f4-451e-926a-a035c87b0bf0', 'ccd7e6fa-b98d-4e77-bd49-b5e31c7b30e8', 'Murs', 'bon', '', 2, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('30f36b25-3482-476e-98fb-892f2708a4d1', 'ccd7e6fa-b98d-4e77-bd49-b5e31c7b30e8', 'Plafond', 'bon', '', 3, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('1951ae1c-b223-4a06-b259-0e0083a1e5bb', 'ccd7e6fa-b98d-4e77-bd49-b5e31c7b30e8', 'Fenêtres', 'bon', '', 4, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('bef0e8be-897a-4e65-ab98-1fc16275b7a3', 'ccd7e6fa-b98d-4e77-bd49-b5e31c7b30e8', 'Porte', 'bon', '', 5, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('e8afeb22-c3b9-4f94-a7ea-2484d54baf14', 'ccd7e6fa-b98d-4e77-bd49-b5e31c7b30e8', 'Électricité', 'bon', '', 6, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('ff7d14d4-79c0-4590-9b15-e96710a522a7', 'ccd7e6fa-b98d-4e77-bd49-b5e31c7b30e8', 'Équipements', 'bon', '', 7, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('1991c8fb-c026-4929-b857-80d8a46fe775', '6dc6d473-6158-451b-8da7-8e7f478bc21e', 'Sol', 'mauvais', 'test', 1, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('cb7a175e-bbdb-436e-8195-ac19b5a714de', '6dc6d473-6158-451b-8da7-8e7f478bc21e', 'Murs', 'mauvais', 'test', 2, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('e1a47f17-6cfd-45b2-bea5-32e6a4325311', '6dc6d473-6158-451b-8da7-8e7f478bc21e', 'Plafond', 'mauvais', 'test', 3, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('619f2f20-8e02-49c8-bb2c-bd7741cf2f6b', '6dc6d473-6158-451b-8da7-8e7f478bc21e', 'Fenêtres', 'mauvais', 'test', 4, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('e3566437-3f7a-4d86-97bc-b1486658e59d', '6dc6d473-6158-451b-8da7-8e7f478bc21e', 'Porte', 'mauvais', 'test', 5, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('1b024066-48a7-48a7-831d-66cb2b9656af', '6dc6d473-6158-451b-8da7-8e7f478bc21e', 'Électricité', 'mauvais', 'test', 6, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('fdf6b570-54a3-48a4-b46d-4684d6b7f107', '6dc6d473-6158-451b-8da7-8e7f478bc21e', 'Équipements', 'mauvais', 'test', 7, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:25:23', '2026-08-05 15:25:23', NULL),
('9c75355e-e481-44a0-8932-b41ff17beec8', 'e518f9cd-f664-4eca-af9b-d570958dd9d9', 'Sol', 'moyen', '', 1, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-05 15:31:11', '2026-08-05 15:31:11', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `edl_image`
--

CREATE TABLE `edl_image` (
  `edl_image_id` varchar(150) NOT NULL,
  `id_edl_piece` varchar(150) NOT NULL,
  `chemin_fichier` varchar(250) DEFAULT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structure de la table `edl_piece`
--

CREATE TABLE `edl_piece` (
  `edl_piece_id` varchar(150) NOT NULL,
  `id_etat_des_lieux` varchar(150) NOT NULL,
  `name_piece` varchar(150) DEFAULT NULL,
  `ordre` int(11) NOT NULL DEFAULT 1,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `edl_piece`
--

INSERT INTO `edl_piece` (`edl_piece_id`, `id_etat_des_lieux`, `name_piece`, `ordre`, `created_by`, `updated_by`, `updated_at`, `created_at`) VALUES
('2d3d5cc6-58a2-43d8-83fd-7b8cb1aff9e3', '0d4ce841-8569-40fb-8aba-ae7435a784f5', 'Salle de bain', 3, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-08-05 15:31:11', '2026-08-05 15:31:11'),
('a2e7336e-a81b-4188-8923-06474143c3f3', '0d4ce841-8569-40fb-8aba-ae7435a784f5', 'Cuisine', 2, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-08-05 15:31:11', '2026-08-05 15:31:11'),
('87ebfffe-a32d-4d50-82c9-b96100f640ee', '50e4f9cc-e04c-4423-9a78-af5a930a4d16', 'Chambre', 1, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-08-05 15:25:23', '2026-08-05 15:25:23'),
('ccd7e6fa-b98d-4e77-bd49-b5e31c7b30e8', '50e4f9cc-e04c-4423-9a78-af5a930a4d16', 'Cuisine', 2, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-08-05 15:25:23', '2026-08-05 15:25:23'),
('6dc6d473-6158-451b-8da7-8e7f478bc21e', '50e4f9cc-e04c-4423-9a78-af5a930a4d16', 'Salle de bain', 3, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-08-05 15:25:23', '2026-08-05 15:25:23'),
('e518f9cd-f664-4eca-af9b-d570958dd9d9', '0d4ce841-8569-40fb-8aba-ae7435a784f5', 'Chambre', 1, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-08-05 15:31:11', '2026-08-05 15:31:11');

-- --------------------------------------------------------

--
-- Structure de la table `equipement`
--

CREATE TABLE `equipement` (
  `equipement_id` char(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `reference` varchar(150) NOT NULL,
  `type_equipement_id` varchar(50) NOT NULL,
  `stock_initial` int(11) NOT NULL DEFAULT 0,
  `stock_actuel` int(11) NOT NULL DEFAULT 0,
  `threshold` int(11) NOT NULL DEFAULT 0 COMMENT 'seuil alerte',
  `photo` varchar(250) DEFAULT NULL,
  `prix` double(10,2) NOT NULL DEFAULT 0.00,
  `date_reception` datetime DEFAULT NULL,
  `created_by` varchar(250) DEFAULT NULL,
  `updated_by` varchar(250) DEFAULT NULL,
  `deleted_by` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `equipement`
--

INSERT INTO `equipement` (`equipement_id`, `name`, `description`, `reference`, `type_equipement_id`, `stock_initial`, `stock_actuel`, `threshold`, `photo`, `prix`, `date_reception`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('e7f61afa-e64f-40c1-8c6a-3884399a5c66', 'chaise', NULL, 'CHA', '78345544-fdce-11f0-82f8-fa163e85dd73', 500, 150, 50, NULL, 0.00, '2026-06-15 00:00:00', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, NULL, '2026-06-15 17:22:02', '2026-09-28 17:43:46', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `equipement_affectations`
--

CREATE TABLE `equipement_affectations` (
  `id` char(36) NOT NULL,
  `equipement_id` varchar(150) NOT NULL,
  `cite_id` varchar(150) NOT NULL,
  `batiment_id` varchar(150) DEFAULT NULL,
  `chambre_id` varchar(150) DEFAULT NULL,
  `quantite` int(10) UNSIGNED NOT NULL,
  `etat` varchar(30) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `equipement_affectations`
--

INSERT INTO `equipement_affectations` (`id`, `equipement_id`, `cite_id`, `batiment_id`, `chambre_id`, `quantite`, `etat`, `notes`, `created_by`, `returned_at`, `created_at`, `updated_at`) VALUES
('01a0e91d-9a5a-701e-8c45-65412726d3ae', 'e7f61afa-e64f-40c1-8c6a-3884399a5c66', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', NULL, NULL, 150, 'bon', NULL, NULL, NULL, '2026-09-28 17:43:46', '2026-09-28 17:43:46');

-- --------------------------------------------------------

--
-- Structure de la table `equipement_mouvement`
--

CREATE TABLE `equipement_mouvement` (
  `equipement_mouvement_id` char(50) NOT NULL,
  `equipement_id` varchar(50) NOT NULL,
  `type` enum('entry','exit') DEFAULT NULL COMMENT 'entry=entrée, exit=sortie',
  `quantity` int(11) NOT NULL DEFAULT 0,
  `prix` int(11) NOT NULL DEFAULT 0,
  `reason` text DEFAULT NULL,
  `created_by` varchar(250) DEFAULT NULL,
  `updated_by` varchar(250) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `equipement_mouvement`
--

INSERT INTO `equipement_mouvement` (`equipement_mouvement_id`, `equipement_id`, `type`, `quantity`, `prix`, `reason`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
('5662386e-2370-4bca-a3f0-d547f0207588', 'e7f61afa-e64f-40c1-8c6a-3884399a5c66', 'entry', 500, 0, 'Entree de stock ', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, '2026-06-15 17:22:02', '2026-06-15 17:22:02'),
('007cbdde-cc1c-46da-b0aa-e979a561cb5e', 'e7f61afa-e64f-40c1-8c6a-3884399a5c66', 'exit', 200, 0, 'attribution des chambres', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, '2026-06-15 17:22:43', '2026-06-15 17:22:43'),
('6e66b413-0b44-469e-b850-a48b4e1aee1a', 'e7f61afa-e64f-40c1-8c6a-3884399a5c66', 'exit', 150, 0, 'Affectation au parc 01a0e91d-9a5a-701e-8c45-65412726d3ae', NULL, NULL, '2026-09-28 17:43:46', '2026-09-28 17:43:46');

-- --------------------------------------------------------

--
-- Structure de la table `etat_des_lieux`
--

CREATE TABLE `etat_des_lieux` (
  `etat_des_lieux_id` varchar(150) NOT NULL,
  `id_cite` varchar(150) NOT NULL,
  `id_batiment` varchar(150) NOT NULL,
  `id_chambre` varchar(150) NOT NULL,
  `id_etudiant` varchar(150) NOT NULL,
  `type_etat` enum('entree','sortie') DEFAULT 'entree',
  `date_etat` timestamp NULL DEFAULT NULL,
  `nbre_cle_recus` int(11) NOT NULL DEFAULT 1,
  `observation_generale` text DEFAULT NULL,
  `signature_crou` text DEFAULT NULL,
  `signature_etudiant` text DEFAULT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `etat_des_lieux`
--

INSERT INTO `etat_des_lieux` (`etat_des_lieux_id`, `id_cite`, `id_batiment`, `id_chambre`, `id_etudiant`, `type_etat`, `date_etat`, `nbre_cle_recus`, `observation_generale`, `signature_crou`, `signature_etudiant`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
('0d4ce841-8569-40fb-8aba-ae7435a784f5', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', 'c1123cbd-658c-483a-a246-dc6726039649', '1edb4ef8-3b43-4287-88c4-c1e1959e8ffa', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'sortie', '2026-08-05 00:00:00', 1, 'dfsdf', 'signatures/21c5cb4c-2a41-46fc-9e56-6e6844301091.png', 'signatures/0b6b79b0-19a9-4609-9ad4-0cb2e4368248.png', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', '2026-08-05 15:31:11', '2026-08-05 15:59:28'),
('50e4f9cc-e04c-4423-9a78-af5a930a4d16', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', 'c1123cbd-658c-483a-a246-dc6726039649', '1edb4ef8-3b43-4287-88c4-c1e1959e8ffa', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'entree', '2026-08-05 00:00:00', 12, 'dssadvfd', 'signatures/56ebc833-8e06-42ba-b66c-6d35b1537dfc.png', 'signatures/89e82521-c6c9-489f-8441-0e175326ce1b.png', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', '2026-08-05 15:21:44', '2026-08-05 15:25:23');

-- --------------------------------------------------------

--
-- Structure de la table `etudiants`
--

CREATE TABLE `etudiants` (
  `id_etudiant` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `lieu_naissance` varchar(100) DEFAULT NULL,
  `genre_id` int(11) NOT NULL,
  `nationalite` varchar(50) DEFAULT 'IVOIRIENNE',
  `email1` varchar(100) DEFAULT NULL,
  `email2` varchar(100) DEFAULT NULL,
  `telephone1` varchar(20) DEFAULT NULL,
  `telephone2` varchar(20) DEFAULT NULL,
  `countrie_id` int(11) DEFAULT NULL,
  `password` varchar(250) DEFAULT NULL,
  `regions_id` int(11) DEFAULT NULL,
  `ville_id` int(11) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `departement_code` varchar(100) DEFAULT NULL,
  `faculte` varchar(100) DEFAULT NULL,
  `moyenne` decimal(10,0) DEFAULT 0,
  `matricule` varchar(50) DEFAULT NULL,
  `annee_academique_actuel` varchar(20) DEFAULT NULL,
  `numero_etudiant` varchar(20) DEFAULT NULL,
  `type_piece_id` int(11) DEFAULT NULL,
  `numero_piece` varchar(50) DEFAULT NULL,
  `date_delivrance` date DEFAULT NULL,
  `date_expiration` date DEFAULT NULL,
  `groupe_sanguin_id` int(11) DEFAULT NULL,
  `allergies` tinyint(1) DEFAULT 0,
  `maladies_chroniques` tinyint(1) DEFAULT 0,
  `autiste` tinyint(1) DEFAULT 0,
  `name_tuteur` varchar(250) DEFAULT NULL,
  `telephone_tuteur` varchar(20) DEFAULT NULL,
  `adresse_tuteur` text DEFAULT NULL,
  `boursier` tinyint(1) DEFAULT 0,
  `type_bourse` varchar(100) DEFAULT NULL,
  `photo_identite` varchar(250) DEFAULT NULL,
  `piece_identite_recto` varchar(500) DEFAULT NULL,
  `piece_identite_verso` varchar(500) DEFAULT NULL,
  `certificat_scolarite` varchar(500) DEFAULT NULL,
  `statut_inscription` enum('en_attente','en_verification','residents','demande','annuler') DEFAULT 'demande',
  `statut` tinyint(4) DEFAULT 1,
  `distance` int(11) DEFAULT NULL,
  `date_inscription` timestamp NOT NULL DEFAULT current_timestamp(),
  `users_validation` varchar(250) DEFAULT NULL,
  `is_validation` tinyint(1) DEFAULT 0,
  `date_validation` date DEFAULT NULL,
  `users_rejet` varchar(250) DEFAULT NULL,
  `is_rejet` tinyint(1) DEFAULT 0,
  `motif_rejet` text DEFAULT NULL,
  `date_rejet` datetime DEFAULT NULL,
  `created_by` varchar(250) DEFAULT NULL,
  `updated_by` varchar(250) DEFAULT NULL,
  `deleted_by` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `pmr_confirme` tinyint(1) NOT NULL DEFAULT 0,
  `pmr_confirme_by` varchar(255) DEFAULT NULL,
  `pmr_confirme_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `etudiants`
--

INSERT INTO `etudiants` (`id_etudiant`, `name`, `date_naissance`, `lieu_naissance`, `genre_id`, `nationalite`, `email1`, `email2`, `telephone1`, `telephone2`, `countrie_id`, `password`, `regions_id`, `ville_id`, `adresse`, `departement_code`, `faculte`, `moyenne`, `matricule`, `annee_academique_actuel`, `numero_etudiant`, `type_piece_id`, `numero_piece`, `date_delivrance`, `date_expiration`, `groupe_sanguin_id`, `allergies`, `maladies_chroniques`, `autiste`, `name_tuteur`, `telephone_tuteur`, `adresse_tuteur`, `boursier`, `type_bourse`, `photo_identite`, `piece_identite_recto`, `piece_identite_verso`, `certificat_scolarite`, `statut_inscription`, `statut`, `distance`, `date_inscription`, `users_validation`, `is_validation`, `date_validation`, `users_rejet`, `is_rejet`, `motif_rejet`, `date_rejet`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`, `pmr_confirme`, `pmr_confirme_by`, `pmr_confirme_at`) VALUES
('d052d932-81c1-4065-bf7a-c6bbda59d7d1', 'John Doe femme', '2026-07-07', 'abobo', 1, 'IVOIRIENNE', 'yaporodrigue1109@gmail.com', NULL, '+2250707902963', NULL, NULL, NULL, 2, 15, 'Bondoukou', NULL, NULL, 0, '23000000876500', NULL, NULL, 1, 'ci8780809100', NULL, '2026-09-05', 1, 0, 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'en_attente', 1, 100, '2026-08-03 16:28:44', NULL, 0, NULL, NULL, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-08-03 16:28:44', '2026-08-03 16:28:46', NULL, 0, NULL, NULL),
('a5802ce0-35ea-4649-af43-c61ecebe4f0a', 'Koffi Koundjodji', '2026-06-01', 'Bingerville', 1, 'IVOIRIENNE', 'yaporodrigue09@gmail.com', NULL, '+2250707902969', NULL, NULL, '$2y$12$44z3oXFu.eM/L9DEHLDE7.8McYRhTErA7i1J15jUP8jq1lUCzeUn.', 2, 16, 'Bondoukou', NULL, NULL, 0, '230000007608', NULL, NULL, 1, 'ci8780809210', NULL, '2026-08-30', 1, 0, 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'en_attente', 1, 233, '2026-08-05 11:12:54', NULL, 0, NULL, NULL, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-08-05 11:12:54', '2026-08-05 11:33:58', NULL, 0, NULL, NULL),
('cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'John Doe', '2026-07-29', 'treichville', 1, 'IVOIRIENNE', 'rodrigue.yapo@soumafe.ci', NULL, '0707902962', NULL, NULL, '$2y$12$qdNmOmolXBd.XMIUoDKnXO/RNpfhXHo2t6wh023vdMLBPHaJZYLD2', 1, 4, 'cite kalilou', NULL, NULL, 0, '23000000761', NULL, NULL, 1, 'ci878080921', NULL, '2026-09-04', 2, 0, 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'residents', 1, 120, '2026-08-03 15:58:57', NULL, 0, NULL, NULL, 1, 'Critères non respectés : Crédits validés, Distance domicile – campus', '2026-08-03 15:58:59', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-08-03 15:58:57', '2026-08-03 16:35:48', NULL, 0, NULL, NULL),
('7dd5b3aa-bebb-42b8-880c-a607f737babd', 'test', NULL, NULL, 2, 'IVOIRIENNE', NULL, NULL, '707902962', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '457896321', '2025-2026', '457896321', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'demande', 1, NULL, '2026-10-12 00:00:00', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 0, '2026-10-12', NULL, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-10-12 23:10:42', '2026-10-12 23:10:42', NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `filiere`
--

CREATE TABLE `filiere` (
  `id_filiere` int(11) NOT NULL,
  `code_filiere` varchar(50) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `departement_code` varchar(150) DEFAULT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `filiere`
--

INSERT INTO `filiere` (`id_filiere`, `code_filiere`, `name`, `description`, `departement_code`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'nFkjEt9S2M06AWcwP04T0Few3J8NFm19JBoVimN9eSu7Vfrk9i', 'Algèbre', 'Algèbre1', '69HXXxJMnivT8eumK5vcNjN1MWpiWHuKp5QuAI7crKBVSW4UAd', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', '2026-01-14 14:52:18', '2026-01-14 14:52:27'),
(2, 'py8XxzDWB0i4thJZQmQynDcW40P9IhKZk7q4c7VJXX1Ij3CMG0', 'RTEL', 'Réseaux & Telecom', '2x8TewJizkR5HRTelW4PaQIq9LvTbNEAE9BRr2NsQILjiLhNO8', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, '2026-03-11 16:27:22', '2026-03-11 16:27:22');

-- --------------------------------------------------------

--
-- Structure de la table `genre`
--

CREATE TABLE `genre` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `genre`
--

INSERT INTO `genre` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Homme', '2025-12-29 21:41:27', '2025-12-29 21:41:27'),
(2, 'Femme', '2025-12-29 21:41:27', '2025-12-29 21:41:27');

-- --------------------------------------------------------

--
-- Structure de la table `groupe_sanguins`
--

CREATE TABLE `groupe_sanguins` (
  `id` int(11) NOT NULL,
  `name` varchar(225) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `groupe_sanguins`
--

INSERT INTO `groupe_sanguins` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'A+', '2025-11-11 16:18:39', NULL),
(2, 'A-', '2025-11-11 16:18:39', NULL),
(3, 'B+', '2025-11-11 16:18:39', NULL),
(4, 'B-', '2025-11-11 16:18:39', NULL),
(5, 'AB+', '2025-11-11 16:18:39', NULL),
(6, 'AB-', '2025-11-11 16:18:39', NULL),
(7, 'O+', '2025-11-11 16:18:39', NULL),
(8, 'O-', '2025-11-11 16:18:39', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `housing_campaign_announcements`
--

CREATE TABLE `housing_campaign_announcements` (
  `campaign_id` varchar(150) NOT NULL,
  `announcement_id` varchar(150) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `housing_entry_appointments`
--

CREATE TABLE `housing_entry_appointments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assignment_id` varchar(150) NOT NULL,
  `student_id` varchar(150) NOT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `location` varchar(255) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `housing_entry_messages`
--

CREATE TABLE `housing_entry_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_key` varchar(190) NOT NULL,
  `student_id` varchar(150) NOT NULL,
  `assignment_id` varchar(150) DEFAULT NULL,
  `kind` varchar(30) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `error` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `housing_entry_settings`
--

CREATE TABLE `housing_entry_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`values`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `housing_entry_settings`
--

INSERT INTO `housing_entry_settings` (`id`, `values`, `created_at`, `updated_at`) VALUES
(1, '{\"enabled\":false,\"started_at\":\"2026-10-10 09:47:01\"}', '2026-10-10 09:47:01', '2026-10-10 09:47:01');

-- --------------------------------------------------------

--
-- Structure de la table `interventions`
--

CREATE TABLE `interventions` (
  `id_intervention` varchar(150) NOT NULL COMMENT 'ID unique de l''intervention',
  `id_ordres_travail` varchar(150) NOT NULL COMMENT 'ID de l''ordre de travail',
  `date_intervention` datetime NOT NULL COMMENT 'Date de l''intervention',
  `duree` int(11) DEFAULT NULL COMMENT 'Durée de l''intervention en minutes',
  `description` text DEFAULT NULL COMMENT 'Description de l''intervention réalisée',
  `technicien_id` varchar(150) DEFAULT NULL COMMENT 'ID du technicien intervenant',
  `equipe` varchar(100) DEFAULT NULL COMMENT 'Nom de l''équipe',
  `materiel_utilise` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Matériel utilisé (JSON)' CHECK (json_valid(`materiel_utilise`)),
  `pieces_detachees` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Pièces détachées utilisées (JSON)' CHECK (json_valid(`pieces_detachees`)),
  `resultat` enum('resolu','partiellement_resolu','non_resolu','necessite_suivi') NOT NULL DEFAULT 'resolu' COMMENT 'Résultat de l''intervention',
  `remarques` text DEFAULT NULL COMMENT 'Remarques sur l''intervention',
  `photos_avant` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Photos avant intervention (JSON)' CHECK (json_valid(`photos_avant`)),
  `photos_apres` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Photos après intervention (JSON)' CHECK (json_valid(`photos_apres`)),
  `date_creation` datetime DEFAULT current_timestamp() COMMENT 'Date de création',
  `date_mise_a_jour` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Date de mise à jour',
  `taux_satisfaction` int(11) DEFAULT NULL,
  `commentaire_satisfaction` text DEFAULT NULL,
  `date_satisfaction` datetime DEFAULT NULL,
  `created_by` varchar(150) DEFAULT NULL COMMENT 'Créé par',
  `updated_by` varchar(150) DEFAULT NULL COMMENT 'Modifié par',
  `deleted_by` varchar(150) DEFAULT NULL COMMENT 'Supprimé par',
  `deleted_at` datetime DEFAULT NULL COMMENT 'Date de suppression',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'Date de création',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Date de mise à jour'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Détail des interventions de maintenance';

--
-- Déchargement des données de la table `interventions`
--

INSERT INTO `interventions` (`id_intervention`, `id_ordres_travail`, `date_intervention`, `duree`, `description`, `technicien_id`, `equipe`, `materiel_utilise`, `pieces_detachees`, `resultat`, `remarques`, `photos_avant`, `photos_apres`, `date_creation`, `date_mise_a_jour`, `taux_satisfaction`, `commentaire_satisfaction`, `date_satisfaction`, `created_by`, `updated_by`, `deleted_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
('5dce42bf-b77d-4075-902b-2defa7456246', '50247af4-b86b-4706-b4da-993aa6271154', '2026-07-13 11:30:00', 45, 'sa', NULL, NULL, NULL, NULL, 'resolu', NULL, NULL, NULL, '2026-07-13 11:40:37', '2026-07-13 11:40:37', NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, NULL, '2026-07-13 11:40:37', '2026-07-13 11:40:37'),
('9cd061ab-d565-42b6-8ec7-0fc64959c5d5', '50247af4-b86b-4706-b4da-993aa6271154', '2026-07-13 11:30:00', 45, 'sa', NULL, NULL, NULL, NULL, 'resolu', NULL, NULL, NULL, '2026-07-13 11:40:51', '2026-07-13 11:40:51', NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, NULL, '2026-07-13 11:40:51', '2026-07-13 11:40:51'),
('ef107d86-ab78-431f-9046-c6a18962a20f', '50247af4-b86b-4706-b4da-993aa6271154', '2026-07-13 11:30:00', 45, 'sa', NULL, NULL, NULL, NULL, 'resolu', NULL, NULL, NULL, '2026-07-13 11:39:06', '2026-09-14 16:04:55', 4, 'test eytsdhjb', '2026-09-14 16:04:55', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-13 11:39:06', '2026-09-14 16:04:55');

-- --------------------------------------------------------

--
-- Structure de la table `legal_documents`
--

CREATE TABLE `legal_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(40) NOT NULL,
  `title` varchar(200) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `version_label` varchar(30) NOT NULL DEFAULT '1.0',
  `scope_label` varchar(150) DEFAULT NULL,
  `institution` varchar(200) NOT NULL,
  `academic_year` varchar(30) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `body` longtext NOT NULL,
  `schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`schedule`)),
  `signatory_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`signatory_roles`)),
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `lock_version` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `published_version_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `body_format` varchar(10) NOT NULL DEFAULT 'text',
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `legal_document_versions`
--

CREATE TABLE `legal_document_versions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `legal_document_id` bigint(20) UNSIGNED NOT NULL,
  `revision` int(10) UNSIGNED NOT NULL,
  `snapshot` longtext NOT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `legal_signatories`
--

CREATE TABLE `legal_signatories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_code` varchar(40) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lit`
--

CREATE TABLE `lit` (
  `id_lit` int(11) NOT NULL,
  `code_lit` varchar(50) NOT NULL,
  `chambre_code` varchar(150) NOT NULL,
  `numero_lit` int(11) NOT NULL,
  `type_lit` enum('simple','superpose_haut','superpose_bas','mezzanine') DEFAULT 'simple',
  `dimensions` varchar(50) DEFAULT NULL,
  `materiel` enum('bois','metal','mixte') DEFAULT 'metal',
  `matelas_type` varchar(100) DEFAULT NULL,
  `etat` enum('excellent','bon','moyen','mauvais','hors_service') DEFAULT 'bon',
  `date_achat` date DEFAULT NULL,
  `garantie_jusque` date DEFAULT NULL,
  `cout_achat` decimal(10,2) DEFAULT 0.00,
  `numero_serie` varchar(100) DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `statut` enum('disponible','occupe','maintenance','reserve','hors_service') DEFAULT 'disponible',
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lits`
--

CREATE TABLE `lits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `chambre_id` varchar(255) NOT NULL,
  `numero` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `lits`
--

INSERT INTO `lits` (`id`, `chambre_id`, `numero`, `created_at`, `updated_at`) VALUES
(1, '05cbf47a-ee9d-48a8-aa31-a72f81a4b180', 1, '2026-09-21 17:24:27', '2026-09-21 17:24:27'),
(2, '05cbf47a-ee9d-48a8-aa31-a72f81a4b180', 2, '2026-09-21 17:24:27', '2026-09-21 17:24:27'),
(3, '09533b58-503d-4069-9fe6-2c79309a422b', 1, '2026-09-21 17:24:28', '2026-09-21 17:24:28'),
(4, '09533b58-503d-4069-9fe6-2c79309a422b', 2, '2026-09-21 17:24:28', '2026-09-21 17:24:28'),
(5, '0c184f8e-3e6e-4014-9940-76c16f9dfcf2', 1, '2026-09-21 17:24:28', '2026-09-21 17:24:28'),
(6, '0c184f8e-3e6e-4014-9940-76c16f9dfcf2', 2, '2026-09-21 17:24:28', '2026-09-21 17:24:28'),
(7, '15df47f5-2dfc-456c-b3f0-2de67e363dd8', 1, '2026-09-21 17:24:28', '2026-09-21 17:24:28'),
(8, '15df47f5-2dfc-456c-b3f0-2de67e363dd8', 2, '2026-09-21 17:24:28', '2026-09-21 17:24:28'),
(9, '1c1821f6-8781-4657-a3b7-b36983e7530a', 1, '2026-09-21 17:24:28', '2026-09-21 17:24:28'),
(10, '1c1821f6-8781-4657-a3b7-b36983e7530a', 2, '2026-09-21 17:24:28', '2026-09-21 17:24:28'),
(11, '1dd0e79d-7413-4c7b-b268-fba2ea5ff745', 1, '2026-09-21 17:24:28', '2026-09-21 17:24:28'),
(12, '1dd0e79d-7413-4c7b-b268-fba2ea5ff745', 2, '2026-09-21 17:24:28', '2026-09-21 17:24:28'),
(13, '1edb4ef8-3b43-4287-88c4-c1e1959e8ffa', 1, '2026-09-21 17:24:28', '2026-09-21 17:24:28'),
(14, '1edb4ef8-3b43-4287-88c4-c1e1959e8ffa', 2, '2026-09-21 17:24:28', '2026-09-21 17:24:28'),
(15, '2c471588-30dc-4315-aa29-78f5a421d897', 1, '2026-09-21 17:24:29', '2026-09-21 17:24:29'),
(16, '2c471588-30dc-4315-aa29-78f5a421d897', 2, '2026-09-21 17:24:29', '2026-09-21 17:24:29'),
(17, '2d610196-1e6b-43a8-91ed-76f5f613f84f', 1, '2026-09-21 17:24:29', '2026-09-21 17:24:29'),
(18, '3003c0c1-23cc-4022-b000-e1d7075fd62c', 1, '2026-09-21 17:24:29', '2026-09-21 17:24:29'),
(19, '3003c0c1-23cc-4022-b000-e1d7075fd62c', 2, '2026-09-21 17:24:29', '2026-09-21 17:24:29'),
(20, '37a48c3a-016f-4aa4-a80f-272b0d7cfa7a', 1, '2026-09-21 17:24:29', '2026-09-21 17:24:29'),
(21, '37a48c3a-016f-4aa4-a80f-272b0d7cfa7a', 2, '2026-09-21 17:24:29', '2026-09-21 17:24:29'),
(22, '487163bf-762a-449e-9c73-5c23a12b8b6b', 1, '2026-09-21 17:24:29', '2026-09-21 17:24:29'),
(23, '487163bf-762a-449e-9c73-5c23a12b8b6b', 2, '2026-09-21 17:24:29', '2026-09-21 17:24:29'),
(24, '5fc2bbe3-447d-4758-8154-4948ed89c5b7', 1, '2026-09-21 17:24:30', '2026-09-21 17:24:30'),
(25, '5fc2bbe3-447d-4758-8154-4948ed89c5b7', 2, '2026-09-21 17:24:30', '2026-09-21 17:24:30'),
(26, '620e378f-4fc3-4f32-9095-e1d60dd312eb', 1, '2026-09-21 17:24:30', '2026-09-21 17:24:30'),
(27, '620e378f-4fc3-4f32-9095-e1d60dd312eb', 2, '2026-09-21 17:24:30', '2026-09-21 17:24:30'),
(28, '6f7bf24d-c91b-432b-9203-db2ea6c143c9', 1, '2026-09-21 17:24:30', '2026-09-21 17:24:30'),
(29, '6f7bf24d-c91b-432b-9203-db2ea6c143c9', 2, '2026-09-21 17:24:30', '2026-09-21 17:24:30'),
(30, '70164ed3-5bb9-4b10-a5b5-b68d7fe6ca59', 1, '2026-09-21 17:24:30', '2026-09-21 17:24:30'),
(31, '71d9edf0-72b0-4c66-b6d8-2dd505ffadc8', 1, '2026-09-21 17:24:30', '2026-09-21 17:24:30'),
(32, '71d9edf0-72b0-4c66-b6d8-2dd505ffadc8', 2, '2026-09-21 17:24:30', '2026-09-21 17:24:30'),
(33, '7e2ea3a1-0770-4d0f-b40d-da2b257cb61f', 1, '2026-09-21 17:24:30', '2026-09-21 17:24:30'),
(34, '7e2ea3a1-0770-4d0f-b40d-da2b257cb61f', 2, '2026-09-21 17:24:30', '2026-09-21 17:24:30'),
(35, '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 1, '2026-09-21 17:24:30', '2026-09-21 17:24:30'),
(36, '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 2, '2026-09-21 17:24:30', '2026-09-21 17:24:30'),
(37, '84099055-1fbd-43fb-b061-079d4b2da439', 1, '2026-09-21 17:24:31', '2026-09-21 17:24:31'),
(38, '84099055-1fbd-43fb-b061-079d4b2da439', 2, '2026-09-21 17:24:31', '2026-09-21 17:24:31'),
(39, '8855821a-ff7f-4535-9a0b-3a0442b3625d', 1, '2026-09-21 17:24:31', '2026-09-21 17:24:31'),
(40, '8855821a-ff7f-4535-9a0b-3a0442b3625d', 2, '2026-09-21 17:24:31', '2026-09-21 17:24:31'),
(41, '8aa139e7-5e62-44ee-a6f4-1b4b61f97db9', 1, '2026-09-21 17:24:31', '2026-09-21 17:24:31'),
(42, '8aa139e7-5e62-44ee-a6f4-1b4b61f97db9', 2, '2026-09-21 17:24:31', '2026-09-21 17:24:31'),
(43, '96c94717-b46d-4e12-9166-a6571b2d316c', 1, '2026-09-21 17:24:31', '2026-09-21 17:24:31'),
(44, '96c94717-b46d-4e12-9166-a6571b2d316c', 2, '2026-09-21 17:24:31', '2026-09-21 17:24:31'),
(45, '9a15839c-f571-4856-b86b-d001ced7a2b5', 1, '2026-09-21 17:24:31', '2026-09-21 17:24:31'),
(46, '9a15839c-f571-4856-b86b-d001ced7a2b5', 2, '2026-09-21 17:24:31', '2026-09-21 17:24:31'),
(47, 'aa9376f8-7dfa-440f-8ed3-5e4e22665e0a', 1, '2026-09-21 17:24:31', '2026-09-21 17:24:31'),
(48, 'aa9376f8-7dfa-440f-8ed3-5e4e22665e0a', 2, '2026-09-21 17:24:31', '2026-09-21 17:24:31'),
(49, 'ab3ead0a-d3a6-4940-9adc-f222c3151350', 1, '2026-09-21 17:24:31', '2026-09-21 17:24:31'),
(50, 'ab3ead0a-d3a6-4940-9adc-f222c3151350', 2, '2026-09-21 17:24:31', '2026-09-21 17:24:31'),
(51, 'acc9a5ff-612f-433e-9eea-a564cb3e4562', 1, '2026-09-21 17:24:32', '2026-09-21 17:24:32'),
(52, 'acc9a5ff-612f-433e-9eea-a564cb3e4562', 2, '2026-09-21 17:24:32', '2026-09-21 17:24:32'),
(53, 'b28d8f67-ad53-4591-bfa6-f1e88e0ca796', 1, '2026-09-21 17:24:32', '2026-09-21 17:24:32'),
(54, 'b28d8f67-ad53-4591-bfa6-f1e88e0ca796', 2, '2026-09-21 17:24:32', '2026-09-21 17:24:32'),
(55, 'c1c8b689-9e87-42dc-9b33-600ec9cf17bd', 1, '2026-09-21 17:24:32', '2026-09-21 17:24:32'),
(56, 'c1c8b689-9e87-42dc-9b33-600ec9cf17bd', 2, '2026-09-21 17:24:32', '2026-09-21 17:24:32'),
(57, 'c9e3c26c-f733-4e70-8d8b-bf3decd0234f', 1, '2026-09-21 17:24:32', '2026-09-21 17:24:32'),
(58, 'c9e3c26c-f733-4e70-8d8b-bf3decd0234f', 2, '2026-09-21 17:24:32', '2026-09-21 17:24:32'),
(59, 'cd1d8b33-4f0d-4bcc-9f5e-670cc4ece161', 1, '2026-09-21 17:24:32', '2026-09-21 17:24:32'),
(60, 'cd1d8b33-4f0d-4bcc-9f5e-670cc4ece161', 2, '2026-09-21 17:24:32', '2026-09-21 17:24:32'),
(61, 'd048b03d-68cc-48ac-93e3-b2642998469e', 1, '2026-09-21 17:24:32', '2026-09-21 17:24:32'),
(62, 'd048b03d-68cc-48ac-93e3-b2642998469e', 2, '2026-09-21 17:24:32', '2026-09-21 17:24:32'),
(63, 'e3a903de-4fd5-4367-b272-98fc5fbd8ddd', 1, '2026-09-21 17:24:32', '2026-09-21 17:24:32'),
(64, 'e3a903de-4fd5-4367-b272-98fc5fbd8ddd', 2, '2026-09-21 17:24:32', '2026-09-21 17:24:32'),
(65, 'e5b54a62-6ac1-44b6-8825-bda93f5e231a', 1, '2026-09-21 17:24:33', '2026-09-21 17:24:33'),
(66, 'e5b54a62-6ac1-44b6-8825-bda93f5e231a', 2, '2026-09-21 17:24:33', '2026-09-21 17:24:33'),
(67, 'e692d1b6-980c-4df3-a573-b630fb5ac37e', 1, '2026-09-21 17:24:33', '2026-09-21 17:24:33'),
(68, 'e692d1b6-980c-4df3-a573-b630fb5ac37e', 2, '2026-09-21 17:24:33', '2026-09-21 17:24:33'),
(69, 'f75703a7-dcce-4bcb-a1a3-17735f32193e', 1, '2026-09-21 17:24:33', '2026-09-21 17:24:33'),
(70, 'f75703a7-dcce-4bcb-a1a3-17735f32193e', 2, '2026-09-21 17:24:33', '2026-09-21 17:24:33');

-- --------------------------------------------------------

--
-- Structure de la table `loyers`
--

CREATE TABLE `loyers` (
  `id_loyer` varchar(150) NOT NULL,
  `etudiant_id` varchar(150) NOT NULL,
  `chambre_id` varchar(150) NOT NULL,
  `cite_id` varchar(150) NOT NULL,
  `campus_id` varchar(150) DEFAULT NULL,
  `assignation_id` varchar(150) DEFAULT NULL,
  `montant_base` decimal(10,2) NOT NULL COMMENT 'Montant de base selon la catégorie de chambre',
  `montant_total` decimal(10,2) NOT NULL COMMENT 'Montant total (base + suppléments - réductions + caution)',
  `caution` decimal(10,2) DEFAULT 0.00 COMMENT 'Montant de la caution',
  `frais_inscription` decimal(10,2) DEFAULT 0.00 COMMENT 'Frais d''avance \r\nEx: montant de base * nbre avance',
  `annee_universitaire` varchar(9) NOT NULL COMMENT 'Format: 2026-2027',
  `periode` enum('annuel','semestriel','trimestriel','mensuel') NOT NULL DEFAULT 'annuel',
  `nombre_echeances` int(11) DEFAULT 1 COMMENT 'Nombre total de paiements prévus',
  `montant_echeance` decimal(10,2) NOT NULL COMMENT 'Montant de chaque échéance',
  `statut` enum('actif','en_retard','suspendu','termine','annule') NOT NULL DEFAULT 'actif',
  `date_paiement` timestamp NULL DEFAULT NULL COMMENT 'Date de paiement',
  `date_limit_paiement` timestamp NULL DEFAULT NULL COMMENT 'Date de limite de paiement ',
  `mois_facturation` int(11) DEFAULT NULL COMMENT 'mois de facturation',
  `annee_facturation` int(11) DEFAULT NULL COMMENT 'année de facturation',
  `boursier` tinyint(1) DEFAULT 0 COMMENT 'Indique si l étudiant est boursier',
  `reduction_bourse` decimal(10,2) DEFAULT 0.00 COMMENT 'Montant de la réduction liée à la bourse',
  `type_bourse` varchar(50) DEFAULT NULL COMMENT 'Type de bourse: entiere, demi, merite, autre',
  `reduction_sociale` decimal(10,2) DEFAULT 0.00 COMMENT 'Réduction pour cas social',
  `justificatif_reduction` text DEFAULT NULL COMMENT 'Document justificatif des réductions',
  `supplement_prestation` decimal(10,2) DEFAULT 0.00 COMMENT 'Total des suppléments pour services additionnels',
  `detail_supplements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Détail des suppléments au format JSON' CHECK (json_valid(`detail_supplements`)),
  `total_paye` decimal(10,2) DEFAULT 0.00 COMMENT 'Montant total déjà payé',
  `solde_restant` decimal(10,2) NOT NULL COMMENT 'Montant restant à payer',
  `nombre_impayes` int(11) DEFAULT 0 COMMENT 'Nombre d échéances impayées',
  `date_dernier_paiement` date DEFAULT NULL COMMENT 'Date du dernier paiement enregistré',
  `penalite_journaliere` decimal(10,2) DEFAULT 0.00 COMMENT 'Montant de la pénalité par jour de retard',
  `total_penalites` decimal(10,2) DEFAULT 0.00 COMMENT 'Total des pénalités accumulées',
  `jours_retard_autorises` int(11) DEFAULT 5 COMMENT 'Délai de grâce avant application des pénalités',
  `renouvellement_auto` tinyint(1) DEFAULT 0 COMMENT 'Renouvellement automatique pour l année suivante',
  `periode_rappel_jours` int(11) DEFAULT 3 COMMENT 'Nombre de jours avant échéance pour envoyer un rappel',
  `notes` text DEFAULT NULL COMMENT 'Notes administratives',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Données supplémentaires au format JSON' CHECK (json_valid(`metadata`)),
  `created_by` varchar(150) DEFAULT NULL COMMENT 'ID utilisateur ayant créé l enregistrement',
  `updated_by` varchar(150) DEFAULT NULL COMMENT 'ID utilisateur ayant modifié l enregistrement',
  `deleted_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table de gestion des loyers étudiants';

--
-- Déchargement des données de la table `loyers`
--

INSERT INTO `loyers` (`id_loyer`, `etudiant_id`, `chambre_id`, `cite_id`, `campus_id`, `assignation_id`, `montant_base`, `montant_total`, `caution`, `frais_inscription`, `annee_universitaire`, `periode`, `nombre_echeances`, `montant_echeance`, `statut`, `date_paiement`, `date_limit_paiement`, `mois_facturation`, `annee_facturation`, `boursier`, `reduction_bourse`, `type_bourse`, `reduction_sociale`, `justificatif_reduction`, `supplement_prestation`, `detail_supplements`, `total_paye`, `solde_restant`, `nombre_impayes`, `date_dernier_paiement`, `penalite_journaliere`, `total_penalites`, `jours_retard_autorises`, `renouvellement_auto`, `periode_rappel_jours`, `notes`, `metadata`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('019f9409-6427-73fe-8174-f08e44d14823', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', 6000.00, 6000.00, 0.00, 0.00, '2025-2026', 'mensuel', 1, 6000.00, 'termine', NULL, '2026-08-10 23:59:59', 8, 2026, 0, 0.00, NULL, 0.00, NULL, 0.00, NULL, 6000.00, 0.00, 0, '2026-07-24', 0.00, 0.00, 5, 1, 3, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 12:11:11', '2026-07-24 12:11:11', NULL),
('019f9410-a3dc-71d3-b70f-34a043e85239', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', 6000.00, 6000.00, 0.00, 0.00, '2025-2026', 'mensuel', 1, 6000.00, 'termine', NULL, '2026-09-10 23:59:59', 9, 2026, 0, 0.00, NULL, 0.00, NULL, 0.00, NULL, 6000.00, 0.00, 0, '2026-07-24', 0.00, 0.00, 5, 1, 3, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 12:19:06', '2026-07-24 12:19:06', NULL),
('019f9414-9141-727b-8830-d53ed6961dbc', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', 6000.00, 6000.00, 0.00, 0.00, '2025-2026', 'mensuel', 1, 6000.00, 'termine', NULL, '2026-10-10 23:59:59', 10, 2026, 0, 0.00, NULL, 0.00, NULL, 0.00, NULL, 6000.00, 0.00, 0, '2026-07-24', 0.00, 0.00, 5, 1, 3, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 12:23:23', '2026-07-24 12:23:23', NULL),
('019f9418-aaf1-70d4-9b07-f42b3bab600f', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', 6000.00, 6000.00, 0.00, 0.00, '2025-2026', 'mensuel', 1, 6000.00, 'termine', NULL, '2026-11-10 23:59:59', 11, 2026, 0, 0.00, NULL, 0.00, NULL, 0.00, NULL, 6000.00, 0.00, 0, '2026-07-24', 0.00, 0.00, 5, 1, 3, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 12:27:52', '2026-07-24 12:27:52', NULL),
('019f9419-0ccd-738d-a06e-1fdacc5f3f17', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', 6000.00, 6000.00, 0.00, 0.00, '2025-2026', 'mensuel', 1, 6000.00, 'termine', NULL, '2026-12-10 23:59:59', 12, 2026, 0, 0.00, NULL, 0.00, NULL, 0.00, NULL, 6000.00, 0.00, 0, '2026-07-24', 0.00, 0.00, 5, 1, 3, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 12:28:17', '2026-07-24 12:28:17', NULL),
('019f941a-861f-72a6-aa3e-264631d55281', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', 6000.00, 6000.00, 0.00, 0.00, '2026-2027', 'mensuel', 1, 6000.00, 'termine', NULL, '2027-01-10 23:59:59', 1, 2027, 0, 0.00, NULL, 0.00, NULL, 0.00, NULL, 6000.00, 0.00, 0, '2026-07-24', 0.00, 0.00, 5, 1, 3, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 12:29:54', '2026-07-24 12:29:54', NULL),
('019f9420-9bd7-7365-b6da-2169d829a72c', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', 6000.00, 6000.00, 0.00, 0.00, '2026-2027', 'mensuel', 1, 6000.00, 'termine', NULL, '2027-02-10 23:59:59', 2, 2027, 0, 0.00, NULL, 0.00, NULL, 0.00, NULL, 6000.00, 0.00, 0, '2026-07-24', 0.00, 0.00, 5, 1, 3, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 12:36:32', '2026-07-24 12:36:32', NULL),
('019f9488-0dfe-735e-abda-b89ba2ba2ce3', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', 6000.00, 6000.00, 0.00, 0.00, '2026-2027', 'mensuel', 1, 6000.00, 'termine', NULL, '2027-03-10 23:59:59', 3, 2027, 0, 0.00, NULL, 0.00, NULL, 0.00, NULL, 6000.00, 0.00, 0, '2026-07-24', 0.00, 0.00, 5, 1, 3, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 14:29:32', '2026-07-24 14:29:32', NULL),
('019f9488-4375-71d7-95aa-aa0b0371dcfd', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', 6000.00, 6000.00, 0.00, 0.00, '2026-2027', 'mensuel', 1, 6000.00, 'termine', NULL, '2027-04-10 23:59:59', 4, 2027, 0, 0.00, NULL, 0.00, NULL, 0.00, NULL, 6000.00, 0.00, 0, '2026-07-24', 0.00, 0.00, 5, 1, 3, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 14:29:45', '2026-07-24 14:29:45', NULL),
('019f9488-4385-72cb-bd95-a253bbb5fde6', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', 6000.00, 6000.00, 0.00, 0.00, '2026-2027', 'mensuel', 1, 6000.00, 'termine', NULL, '2027-05-10 23:59:59', 5, 2027, 0, 0.00, NULL, 0.00, NULL, 0.00, NULL, 6000.00, 0.00, 0, '2026-07-24', 0.00, 0.00, 5, 1, 3, NULL, NULL, 'system', NULL, NULL, '2026-07-24 14:29:45', '2026-07-24 14:29:45', NULL),
('019f9490-8be5-704b-adf8-135eec61fcf4', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', 6000.00, 6000.00, 0.00, 0.00, '2026-2027', 'mensuel', 1, 6000.00, 'termine', NULL, '2027-06-10 23:59:59', 6, 2027, 0, 0.00, NULL, 0.00, NULL, 0.00, NULL, 6000.00, 0.00, 0, '2026-07-24', 0.00, 0.00, 5, 1, 3, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 14:38:48', '2026-07-24 14:38:48', NULL),
('019f9491-45b6-7058-b9d8-c145416b0acf', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', 6000.00, 6000.00, 0.00, 0.00, '2026-2027', 'mensuel', 1, 6000.00, 'termine', NULL, '2027-07-10 23:59:59', 7, 2027, 0, 0.00, NULL, 0.00, NULL, 0.00, NULL, 6000.00, 0.00, 0, '2026-07-24', 0.00, 0.00, 5, 1, 3, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 14:39:36', '2026-07-24 14:39:36', NULL),
('019f9492-0860-7093-9b57-c1ba019f6d6c', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '3c11a70c-a236-4177-a98c-a77b5c45530c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', 6000.00, 6000.00, 0.00, 0.00, '2026-2027', 'mensuel', 1, 6000.00, 'termine', NULL, '2027-08-10 23:59:59', 8, 2027, 0, 0.00, NULL, 0.00, NULL, 0.00, NULL, 6000.00, 0.00, 0, '2026-07-24', 0.00, 0.00, 5, 1, 3, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 14:40:26', '2026-07-24 14:40:26', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `maintenanciers`
--

CREATE TABLE `maintenanciers` (
  `id_maintenancier` varchar(150) NOT NULL,
  `name` varchar(250) NOT NULL,
  `tel` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `specialite` enum('electricite','plomberie','menuiserie','climatisation','maconnerie','peinture','multiservices') DEFAULT 'multiservices',
  `cite_id` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `adresse` varchar(250) DEFAULT NULL,
  `statut` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `deleted_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `maintenanciers`
--

INSERT INTO `maintenanciers` (`id_maintenancier`, `name`, `tel`, `email`, `specialite`, `cite_id`, `notes`, `adresse`, `statut`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('9ab7fa4c-27f5-40dd-8dbd-b0249b2324f4', 'Mainenancier', '0987654321', NULL, 'multiservices', NULL, NULL, 'Abidjan', 1, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-07 17:07:26', '2026-07-07 17:07:26', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_08_10_000001_create_sanctions_tables', 1),
(2, '2026_08_10_000002_add_scope_to_sanctions_table', 2),
(3, '2026_08_10_000003_create_disciplinary_reports_tables', 3),
(4, '2026_08_10_000004_create_departure_settings_table', 4),
(5, '2026_08_10_000005_create_departure_requests_and_settlements', 5),
(6, '2026_08_11_000001_create_role_permissions_tables', 6),
(7, '2026_08_11_000002_create_modules_table', 7),
(8, '2026_08_11_000003_generate_module_permissions', 8),
(9, '2026_08_17_000001_add_student_declaration_fields_to_disciplinary_reports', 9),
(10, '2026_08_18_000001_create_activity_logs_table', 10),
(11, '2026_08_18_000002_add_activity_log_permission', 11),
(12, '2026_08_18_000003_add_allowed_ips_to_config_app', 12),
(13, '2026_09_19_000001_create_payment_providers_table', 13),
(14, '2026_09_19_000002_add_payment_provider_permission', 13),
(15, '2026_09_19_000003_add_specific_payment_credentials', 14),
(16, '2026_09_19_000004_create_online_payments_table', 15),
(17, '2026_09_20_000001_create_legal_documents_tables', 16),
(18, '2026_09_20_000002_add_legal_documents_permissions', 16),
(19, '2026_09_20_000003_add_legal_document_body_format', 17),
(20, '2026_09_20_000004_create_annonce_deliveries', 18),
(21, '2026_09_21_000001_add_pmr_confirmation_to_etudiants', 19),
(22, '2026_09_22_000001_create_lits', 20),
(23, '2026_09_23_000001_create_admission_derogations', 21),
(24, '2026_09_23_000002_create_student_contract_approvals', 22),
(25, '2026_09_23_000003_create_campaign_scorings', 23),
(26, '2026_09_23_000004_link_disciplinary_decisions_to_sanctions', 24),
(27, '2026_09_23_000005_allow_expulsion_assignment_status', 25),
(28, '2026_09_23_000006_create_dossier_workflow', 26),
(29, '2026_09_24_000001_create_housing_entry_workflow', 27),
(30, '2026_08_27_100000_create_student_api_tokens_table', 28),
(31, '2026_09_28_000002_create_equipement_affectations', 29),
(32, '2026_09_29_000001_complete_admin_permissions', 30),
(33, '2026_09_29_000002_add_admin_view_permissions', 31),
(34, '2026_10_09_000001_add_photo_to_edl_elements', 32),
(35, '2026_09_11_000001_add_statut_to_batiment_and_chambre', 33),
(36, '2026_10_12_000001_add_uploaded_file_to_legal_documents', 34),
(37, '2026_10_13_000001_add_address_to_config_app', 35);

-- --------------------------------------------------------

--
-- Structure de la table `modules`
--

CREATE TABLE `modules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `route_name` varchar(180) DEFAULT NULL,
  `menu_key` varchar(80) DEFAULT NULL,
  `submenu_key` varchar(80) DEFAULT NULL,
  `position` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `level` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`actions`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `show_in_menu` tinyint(1) NOT NULL DEFAULT 1,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `modules`
--

INSERT INTO `modules` (`id`, `parent_id`, `name`, `slug`, `description`, `icon`, `route_name`, `menu_key`, `submenu_key`, `position`, `level`, `actions`, `is_active`, `show_in_menu`, `is_system`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Tableau de bord', 'dashboard', NULL, 'fa-solid fa-table-columns', 'admin.dashboard.index', 'dashboard', NULL, 1, 0, '[\"view\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:28'),
(2, NULL, 'Utilisateurs', 'utilisateurs', NULL, 'fa-solid fa-users', 'admin.users.index', 'users', NULL, 2, 0, '[\"view\",\"create\",\"delete\",\"update\",\"personal\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 17:45:40'),
(3, NULL, 'Gestion Hébergement', 'hebergement', NULL, 'fa-solid fa-building', 'admin.hebergement.index', 'hebergement', NULL, 3, 0, '[\"view\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(4, NULL, 'Paramétrage général', 'parametrage', NULL, 'fa-solid fa-gear', 'admin.parametrage.index', 'parametres', NULL, 4, 0, '[\"view\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(5, 3, 'Parc immobilier', 'hebergement.parc', NULL, 'fa-solid fa-building', 'admin.hebergement.parc-immobilier.index', 'hebergement', 'parc-immobilier', 1, 1, '[\"view\",\"create\",\"update\",\"delete\",\"export\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(6, 3, 'Équipements', 'hebergement.equipements', NULL, 'fa-solid fa-toolbox', 'admin.hebergement.equipements.index', 'hebergement', 'equipements', 2, 1, '[\"view\",\"create\",\"update\",\"delete\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(7, 3, 'Campagnes et admissions', 'hebergement.admissions', NULL, 'fa-solid fa-bullhorn', 'admin.hebergement.campagnes_admission.admission', 'hebergement', 'campagnes', 3, 1, '[\"view\",\"create\",\"update\",\"validate\",\"personal\",\"documents\",\"history\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 17:45:39'),
(8, 3, 'Attribution des chambres', 'hebergement.attributions', NULL, 'fa-solid fa-bed', 'admin.allocation.index', 'hebergement', 'residents', 4, 1, '[\"view\",\"assign\",\"validate\",\"export\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:28'),
(9, 3, 'Résidents et contrats', 'hebergement.residents', NULL, 'fa-solid fa-users', 'admin.hebergement.residents_contrat.admission', 'hebergement', 'residents', 5, 1, '[\"view\",\"create\",\"update\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(10, 3, 'Vie résidentielle', 'hebergement.vie-residentielle', NULL, 'fa-solid fa-city', 'admin.hebergement.vie_residentielle.index', 'hebergement', 'vie-cite', 6, 1, '[\"view\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(11, 3, 'Départs et états des lieux', 'hebergement.departs', NULL, 'fa-solid fa-door-open', 'admin.hebergement.depart_etat_des_lieux.depart', 'hebergement', 'departs', 7, 1, '[\"view\",\"create\",\"update\",\"close\",\"export\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:28'),
(12, 3, 'Paiements des loyers', 'hebergement.paiements', NULL, 'fa-solid fa-money-bill-wave', 'admin.hebergement.paiements.index', 'hebergement', 'finances', 8, 1, '[\"view\",\"create\",\"validate\",\"export\",\"update\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(13, 3, 'Reporting et statistiques', 'hebergement.reporting', NULL, 'fa-solid fa-chart-bar', 'admin.hebergement.reporting.index', 'hebergement', 'reporting', 9, 1, '[\"view\",\"export\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(14, 3, 'Paramétrage hébergement', 'hebergement.parametrage', NULL, 'fa-solid fa-tools', 'admin.hebergement.parametrage-herb.index', 'hebergement', 'parametrage-herb', 10, 1, '[\"view\",\"configure\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(15, 4, 'Gestion des modules', 'parametrage.modules', NULL, 'fa-solid fa-sitemap', 'admin.parametrage.modules.index', 'parametres', 'modules', 1, 1, '[\"view\",\"create\",\"update\",\"delete\",\"configure\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(16, 4, 'Rôles et permissions', 'parametrage.roles', NULL, 'fa-solid fa-user-shield', 'admin.parametrage.roles-permissions.index', 'parametres', 'roles', 2, 1, '[\"view\",\"create\",\"update\",\"delete\",\"assign\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(17, 4, 'gestions des pays', 'parametrage.countrie', NULL, 'fa-solid fa-list', 'admin.parametrage.countrie.index', 'parametres', 'referentiels', 3, 1, '[\"view\",\"create\",\"update\",\"delete\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-08-11 11:53:03'),
(18, 4, 'Configuration système', 'parametrage.configuration', NULL, 'fa-solid fa-scroll', 'admin.parametrage.configuration.index', 'parametres', 'parametrage', 9, 1, '[\"view\",\"configure\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(19, 4, 'Gestions des regions', 'parametrage.region', NULL, 'fa-solid fa-map', 'admin.parametrage.region.index', 'parametres', 'regions', 4, 1, '[\"view\",\"create\",\"update\"]', 1, 1, 0, '2026-08-11 10:35:17', '2026-08-11 11:53:24'),
(20, 4, 'Gestions des villes', 'parametrage.ville', NULL, 'fa-solid fa-city', 'admin.parametrage.ville.index', 'parametres', 'villes', 5, 1, '[\"view\",\"create\",\"update\"]', 1, 1, 0, '2026-08-11 10:42:19', '2026-08-11 11:53:43'),
(21, 4, 'Gestion des niveaux d’études', 'parametrage.niveau', NULL, 'fa-solid fa-graduation-cap', 'admin.parametrage.niveau-etude.index', 'parametres', 'niveaux-etude', 6, 1, '[\"view\",\"create\",\"update\",\"delete\"]', 1, 1, 0, '2026-08-11 11:42:29', '2026-08-11 11:54:02'),
(22, 4, 'geation des filières', 'parametrage.filiere', NULL, 'fa-solid fa-diagram-project', 'admin.parametrage.filiere.index', 'parametres', 'filieres', 7, 1, '[\"view\",\"create\",\"update\",\"delete\"]', 1, 1, 0, '2026-08-11 11:44:14', '2026-08-11 11:54:26'),
(23, 4, 'Gestion des départements', 'parametrage.departement', NULL, 'fa-solid fa-layer-group', 'admin.parametrage.departement.index', 'parametres', 'departements', 8, 1, '[\"view\",\"create\",\"update\",\"delete\"]', 1, 1, 0, '2026-08-11 11:46:52', '2026-08-11 11:54:49'),
(24, 4, 'Gestion des types d\'utilisateurs', 'parametrage.type-utilisateur', NULL, 'fa-solid fa-user-gear', 'admin.parametrage.type-utilisateur.index', 'parametres', 'type-utilisateur', 10, 1, '[\"view\",\"create\",\"update\",\"delete\"]', 1, 1, 0, '2026-08-11 11:50:26', '2026-08-11 11:55:25'),
(25, NULL, 'Journal d\'activité', 'activity', 'Consulter les actions enregistrées dans l’application.', 'fa-solid fa-clock-rotate-left', 'admin.activity-logs.index', 'activity', NULL, 5, 0, '[\"view\"]', 1, 1, 1, '2026-08-18 10:14:01', '2026-09-29 11:16:28'),
(26, 4, 'API de paiement', 'parametrage.payment-providers', 'Configurer les réseaux de paiement et leur visibilité étudiant.', 'fa-solid fa-credit-card', 'admin.parametrage.payment-providers.index', 'parametres', 'payment-providers', 15, 1, '[\"configure\"]', 1, 1, 1, '2026-09-19 20:13:51', '2026-09-29 11:16:29'),
(27, 4, 'Signatures et documents', 'parametrage.legal-documents', 'Configurer les signataires et rédiger les documents de la résidence.', 'fa-solid fa-file-signature', 'admin.parametrage.legal-documents.index', 'parametres', 'legal-documents', 16, 1, '[\"manage\",\"signatures\"]', 1, 1, 1, '2026-09-20 11:00:09', '2026-09-29 11:16:29'),
(28, 7, 'Dérogations d’admission', 'hebergement.admissions.derogations', NULL, NULL, NULL, NULL, NULL, 0, 2, '[\"validate\"]', 1, 0, 1, '2026-09-23 08:55:12', '2026-09-29 11:16:28'),
(29, 3, 'Annonces', 'hebergement.annonces', NULL, NULL, NULL, NULL, NULL, 0, 1, '[\"create\",\"delete\",\"update\",\"validate\",\"view\"]', 1, 0, 1, '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(30, 3, 'Discipline', 'hebergement.discipline', NULL, NULL, NULL, NULL, NULL, 0, 1, '[\"create\",\"update\",\"view\"]', 1, 0, 1, '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(31, 3, 'Maintenance', 'hebergement.maintenance', NULL, NULL, NULL, NULL, NULL, 0, 1, '[\"assign\",\"create\",\"update\",\"view\"]', 1, 0, 1, '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(32, 4, 'parametrage.referentiels', 'parametrage.referentiels', NULL, NULL, NULL, NULL, NULL, 0, 1, '[\"create\",\"delete\",\"update\",\"view\"]', 1, 0, 1, '2026-09-29 11:16:29', '2026-09-29 11:16:29');

-- --------------------------------------------------------

--
-- Structure de la table `niveau`
--

CREATE TABLE `niveau` (
  `id_niveau` int(11) NOT NULL,
  `batiment_code` varchar(150) NOT NULL,
  `numero_etage` varchar(150) NOT NULL,
  `code_niveau` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `surface` decimal(10,2) DEFAULT 0.00,
  `nombre_chambres` int(11) DEFAULT 0,
  `chambres_disponibles` int(11) DEFAULT 0,
  `acces_ascenseur` tinyint(1) DEFAULT 0,
  `acces_escalier` tinyint(1) DEFAULT 1,
  `equipements_communs` text DEFAULT NULL,
  `plan_url` varchar(500) DEFAULT NULL,
  `responsable_etage_code` varchar(150) DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structure de la table `niveau_aile_batiment`
--

CREATE TABLE `niveau_aile_batiment` (
  `id_niveau_aile` int(11) NOT NULL,
  `code_aile` varchar(255) NOT NULL,
  `niveau_aile_code` varchar(250) DEFAULT NULL,
  `niveau_numero` int(11) NOT NULL,
  `type_occupation` enum('hommes','femmes','mixte') DEFAULT 'mixte',
  `total_chambres` int(11) DEFAULT 0,
  `chambres_nord` int(11) DEFAULT 0,
  `chambres_sud` int(11) DEFAULT 0,
  `chambres_est` int(11) DEFAULT 0,
  `chambres_ouest` int(11) DEFAULT 0,
  `created_by` varchar(250) DEFAULT NULL,
  `updated_by` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `niveau_aile_batiment`
--

INSERT INTO `niveau_aile_batiment` (`id_niveau_aile`, `code_aile`, `niveau_aile_code`, `niveau_numero`, `type_occupation`, `total_chambres`, `chambres_nord`, `chambres_sud`, `chambres_est`, `chambres_ouest`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(55, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', '0w0jdNyfeyv4D9ZQqWucCtr7hGknWauzKZWVs8kbJUBcRzgUSsBAZtFAACozMMDGEvLysL9oYEyU9BxsR0FE0XyjXTDoI6h1RBmeltDVDvCwaSEEZiKSEzfYE2Y57G0cFGk3j417JnCNzIB8zAGZiZ', 4, 'mixte', 10, 0, 0, 0, 0, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:50:14'),
(54, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'waTdnDbVqc2lH09yNjP3mBW0Mh0TBvO3MAWHkXkhp8IKmmR34BLVuuOyEcr59k3WDzKp5euKoL7dyb87bQBOucHv9MF0RnkEVCg01r51o3dy7NiqeOvzxlEUojoRh16NvlUvkJ9SnGlElk4aMN9T5W', 3, 'mixte', 10, 0, 0, 0, 0, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:14', '2026-07-06 08:50:14'),
(52, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'K5EudOorMz5iSJ1PGkFpEVG3zsG3JESIP399xHEFMYVnaIqti3bXSXqO9z9Ryf6Gs02tAdPBUHXOl4zg2rEMFFUKUukRbeV2UJXjRtwLvxyjqR4tX0kJgZ5ocz4Vz7DXgFxZ4XE35UqbF4RZI0i86M', 1, 'mixte', 10, 0, 0, 0, 0, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:13', '2026-07-06 08:50:13'),
(53, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'qVCMYQLfpSkGnQlQXHORj1x131kqqfNhuxI6Vqx50evCFnrVnUQszaM4fT5GzlqnDImU0rQdafHD03yCdDKcf0cPCSfcjS3cSUxZBdAC4dT5ai9hTNg3zpkDxzN2YEdpzX5bERuNOpkLgrlb88ZL6W', 2, 'mixte', 10, 0, 0, 0, 0, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:13', '2026-07-06 08:50:13'),
(51, 'jcRRpNAch1yiaIZlsqElQY96A252jNUkA10iQRzmwus8rBMVffJPDXk22qVgugNJznRWpTs8vntKW3TKqBGj1kSZ8SgVGcoezSxztHXGfxBGJ3AjXF7Wbhss9XzgfkJLJB4hMth6MpmrXo4jWC1Ds0', 'pJVj2RBoMksSkXIMqjxFO1Q1DGP2RzIHundrwx2KpLL4EjgFLAGKLh2BwmtDWokFKAgRDP7r320WLygT1iiXOqVYVMnsvzQwOECT8hNwNuZRODskJCjF8CykHsP95rrQOnIf0tVOwTFu0yth8R8cvX', 0, 'mixte', 10, 0, 0, 0, 0, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:13', '2026-07-06 08:50:13');

-- --------------------------------------------------------

--
-- Structure de la table `niveau_etude`
--

CREATE TABLE `niveau_etude` (
  `id_niveau_etude` varchar(150) NOT NULL,
  `name` varchar(100) NOT NULL,
  `abreviation` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `niveau_etude`
--

INSERT INTO `niveau_etude` (`id_niveau_etude`, `name`, `abreviation`, `description`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
('2ba2b3e7-b046-4160-9201-47070b58ba35', 'Terminale', 'terminale', 'terminale', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-06-19 17:24:57', '2026-06-19 17:24:57'),
('3df1433f-d890-4739-ba08-ca1523f4c8de', 'Licence 1', 'L1', 'Licence 1', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-06-19 17:25:13', '2026-06-19 17:26:14'),
('cf8b2108-0ee3-4b41-9cad-67785f1c1618', 'Licence 2', 'L2', 'Licence 2', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-06-19 17:25:26', '2026-06-19 17:25:55'),
('aa53486f-2be6-47e2-8ffb-d19ba6aa73b0', 'Licence 3', 'L3', 'Licence 3', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-06-19 17:25:48', '2026-06-19 17:25:48'),
('31499824-aea0-427c-bfc2-2a7523ead943', 'Master 1', 'M1', 'Master 1', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-06-19 17:26:31', '2026-06-19 17:26:31'),
('c9ce2f48-620c-42ed-a556-5d5f384afa76', 'Master 2', 'M2', 'Master 2', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-06-19 17:26:48', '2026-06-19 17:26:48'),
('47662603-be18-4121-81be-03ecf5f44f03', 'Doctorant 1', 'D1', 'Doctorant 1', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-06-19 17:27:01', '2026-06-19 17:27:01'),
('bc4f87ee-90f1-4d69-a3b3-1391e98f0843', 'Doctorant 2', 'D2', 'Doctorant 3', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-06-19 17:27:14', '2026-06-19 17:27:14'),
('ad8e5ca6-3179-47bb-8db6-91b23122cfc0', 'Doctorant 3', 'D3', 'Doctorant 3', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-06-19 17:27:28', '2026-06-19 17:27:28');

-- --------------------------------------------------------

--
-- Structure de la table `online_payments`
--

CREATE TABLE `online_payments` (
  `id` char(36) NOT NULL,
  `reference` varchar(40) NOT NULL,
  `provider_id` bigint(20) UNSIGNED NOT NULL,
  `provider_code` varchar(30) NOT NULL,
  `student_id` varchar(150) NOT NULL,
  `loyer_id` varchar(150) NOT NULL,
  `amount` bigint(20) UNSIGNED NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'XOF',
  `status` varchar(30) NOT NULL DEFAULT 'initializing',
  `provider_reference` varchar(100) DEFAULT NULL,
  `checkout_url` text DEFAULT NULL,
  `credentials` text NOT NULL,
  `callback_token_hash` varchar(64) NOT NULL,
  `paiement_id` varchar(150) DEFAULT NULL,
  `last_error` varchar(100) DEFAULT NULL,
  `next_check_at` timestamp NULL DEFAULT NULL,
  `notified_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ordres_travail`
--

CREATE TABLE `ordres_travail` (
  `id_ordres_travail` varchar(150) NOT NULL COMMENT 'ID unique de l''ordre de travail',
  `numero_ot` varchar(50) NOT NULL COMMENT 'Numéro unique de l''ordre de travail',
  `cite_id` varchar(150) DEFAULT NULL COMMENT 'ID de la résidence/cité',
  `batiment_id` varchar(150) DEFAULT NULL COMMENT 'ID du bâtiment',
  `chambre_id` varchar(150) DEFAULT NULL COMMENT 'ID de la chambre',
  `etudiant_id` varchar(150) DEFAULT NULL COMMENT 'ID de l''étudiant ayant signalé',
  `equipement_id` varchar(150) DEFAULT NULL COMMENT 'ID de l''équipement concerné',
  `type_intervention` enum('corrective','preventive','urgente') NOT NULL DEFAULT 'corrective' COMMENT 'Type d''intervention',
  `categorie` enum('electricite','plomberie','eau','sanitaire','mobilier','securite','climatisation','batiment','autre') NOT NULL COMMENT 'Catégorie d''intervention',
  `priorite` enum('critique','elevee','moyenne','faible') NOT NULL DEFAULT 'moyenne' COMMENT 'Niveau de priorité',
  `titre` varchar(255) DEFAULT NULL COMMENT 'Titre de l''OT',
  `description` text NOT NULL COMMENT 'Description détaillée',
  `cause` text DEFAULT NULL COMMENT 'Cause identifiée',
  `solution_apportee` text DEFAULT NULL COMMENT 'Solution apportée',
  `date_disponibilite` datetime DEFAULT NULL COMMENT 'Date de disponibilite de l''etudiant(le demandeur)',
  `periode_disponibilite` varchar(150) DEFAULT NULL COMMENT 'Periode de disponibilite de l''etudiant(le demandeur)',
  `date_debut_intervention` datetime DEFAULT NULL COMMENT 'Date de début de l''intervention',
  `date_fin_intervention` datetime DEFAULT NULL COMMENT 'Date de fin de l''intervention',
  `date_echeance` date DEFAULT NULL COMMENT 'Date d''échéance',
  `date_resolution` datetime DEFAULT NULL COMMENT 'Date de résolution',
  `date_mise_a_jour` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Date de mise à jour',
  `statut` enum('en_attente','en_cours','en_attente_pieces','termine','annule','reporte') NOT NULL DEFAULT 'en_attente' COMMENT 'Statut de l''OT',
  `assigne_a` varchar(150) DEFAULT NULL COMMENT 'ID du technicien assigné',
  `assigne_par` varchar(150) DEFAULT NULL COMMENT 'ID de l''agent ayant assigné',
  `instructions` text DEFAULT NULL COMMENT 'instructions de mission au maintenancier',
  `cout_estime` decimal(15,2) DEFAULT NULL COMMENT 'Coût estimé',
  `cout_reel` decimal(15,2) DEFAULT NULL COMMENT 'Coût réel',
  `temps_passe` int(11) DEFAULT NULL COMMENT 'Temps passé en heures',
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Photos de l''intervention (JSON)' CHECK (json_valid(`photos`)),
  `pieces_jointes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Pièces jointes (JSON)' CHECK (json_valid(`pieces_jointes`)),
  `notification_envoyee` tinyint(1) DEFAULT 0 COMMENT 'Notification envoyée au résident',
  `taux_satisfaction` int(11) DEFAULT NULL COMMENT 'Taux de satisfaction (1-5)',
  `commentaire_satisfaction` text DEFAULT NULL COMMENT 'Commentaire de satisfaction',
  `created_by` varchar(150) DEFAULT NULL COMMENT 'Créé par',
  `updated_by` varchar(150) DEFAULT NULL COMMENT 'Modifié par',
  `deleted_by` varchar(150) DEFAULT NULL COMMENT 'Supprimé par',
  `deleted_at` datetime DEFAULT NULL COMMENT 'Date de suppression',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'Date de création',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Date de mise à jour'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ordres de travail pour la maintenance';

--
-- Déchargement des données de la table `ordres_travail`
--

INSERT INTO `ordres_travail` (`id_ordres_travail`, `numero_ot`, `cite_id`, `batiment_id`, `chambre_id`, `etudiant_id`, `equipement_id`, `type_intervention`, `categorie`, `priorite`, `titre`, `description`, `cause`, `solution_apportee`, `date_disponibilite`, `periode_disponibilite`, `date_debut_intervention`, `date_fin_intervention`, `date_echeance`, `date_resolution`, `date_mise_a_jour`, `statut`, `assigne_a`, `assigne_par`, `instructions`, `cout_estime`, `cout_reel`, `temps_passe`, `photos`, `pieces_jointes`, `notification_envoyee`, `taux_satisfaction`, `commentaire_satisfaction`, `created_by`, `updated_by`, `deleted_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
('50247af4-b86b-4706-b4da-993aa6271154', 'OT2026-0001', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', 'c1123cbd-658c-483a-a246-dc6726039649', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', NULL, 'corrective', 'plomberie', 'moyenne', NULL, 'dsjhbnkm,f.dcx', NULL, NULL, '2026-07-08 00:00:00', 'Matin (8h-12h)', '2026-07-07 18:00:00', '2026-07-13 11:47:56', NULL, '2026-07-13 11:47:56', '2026-07-13 11:47:56', 'termine', '9ab7fa4c-27f5-40dd-8dbd-b0249b2324f4', NULL, 'test', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-07 15:05:23', '2026-07-13 11:47:56'),
('5412097f-bb90-432a-9c79-067d7b0ed917', 'OT2026-0003', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', 'c1123cbd-658c-483a-a246-dc6726039649', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', NULL, 'corrective', 'plomberie', 'moyenne', NULL, 'test', NULL, NULL, '2026-07-16 00:00:00', 'Matin (8h-12h)', '2026-08-03 16:48:00', '2026-08-21 16:48:00', NULL, NULL, '2026-08-03 16:48:35', 'en_cours', '9ab7fa4c-27f5-40dd-8dbd-b0249b2324f4', NULL, 'frter', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-15 16:42:49', '2026-08-03 16:48:35'),
('e8c0633a-9a1c-49f4-b20b-4fdd5799c3d5', 'OT2026-0002', NULL, NULL, NULL, NULL, NULL, 'corrective', 'electricite', 'moyenne', NULL, 'sdadsdds', NULL, NULL, NULL, NULL, '2026-07-08 11:47:00', '2026-07-08 11:47:00', NULL, NULL, '2026-07-08 11:48:38', 'en_attente', '9ab7fa4c-27f5-40dd-8dbd-b0249b2324f4', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, NULL, '2026-07-08 11:48:38', '2026-07-08 11:48:38');

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

CREATE TABLE `paiements` (
  `id_paiement` varchar(150) NOT NULL,
  `numero_paiement` varchar(50) NOT NULL COMMENT 'Numéro unique de paiement',
  `resident_id` varchar(150) NOT NULL COMMENT 'ID du résident',
  `contrat_id` varchar(150) DEFAULT NULL COMMENT 'ID du contrat associé',
  `chambre_id` varchar(150) DEFAULT NULL COMMENT 'ID de la chambre',
  `batiment_id` varchar(150) DEFAULT NULL COMMENT 'ID du batiment',
  `cite_id` varchar(150) DEFAULT NULL COMMENT 'ID de la résidence',
  `loyer_id` varchar(150) DEFAULT NULL,
  `type_paiement` enum('loyer','caution','penalite','autre') NOT NULL DEFAULT 'loyer' COMMENT 'Type de paiement',
  `mois` varchar(250) DEFAULT NULL COMMENT 'Mois concerné (ex: 2026-06)',
  `montant` decimal(15,2) NOT NULL COMMENT 'Montant du paiement',
  `montant_du` decimal(15,2) DEFAULT NULL COMMENT 'Montant dû initial',
  `devise` varchar(10) DEFAULT 'FCFA' COMMENT 'Devise',
  `methode_paiement` enum('orange_money','mtn_money','moov_money','carte_bancaire','virement','especes','autre') NOT NULL COMMENT 'Méthode de paiement',
  `reference_transaction` varchar(100) DEFAULT NULL COMMENT 'Référence de la transaction',
  `statut_paiement` enum('en_attente','valide','echoue','annule','rembourse') NOT NULL DEFAULT 'en_attente' COMMENT 'Statut du paiement',
  `date_paiement` datetime DEFAULT NULL COMMENT 'Date effective du paiement',
  `date_echeance` date DEFAULT NULL COMMENT 'Date d''échéance',
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_mise_a_jour` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL COMMENT 'Notes ou commentaires',
  `justificatif` varchar(255) DEFAULT NULL COMMENT 'Chemin du justificatif de paiement',
  `valide_par` varchar(150) DEFAULT NULL COMMENT 'ID de l''agent ayant validé',
  `transaction_id` varchar(100) DEFAULT NULL COMMENT 'ID transaction PSP',
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `deleted_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table des paiements des loyers et cautions';

--
-- Déchargement des données de la table `paiements`
--

INSERT INTO `paiements` (`id_paiement`, `numero_paiement`, `resident_id`, `contrat_id`, `chambre_id`, `batiment_id`, `cite_id`, `loyer_id`, `type_paiement`, `mois`, `montant`, `montant_du`, `devise`, `methode_paiement`, `reference_transaction`, `statut_paiement`, `date_paiement`, `date_echeance`, `date_creation`, `date_mise_a_jour`, `notes`, `justificatif`, `valide_par`, `transaction_id`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('0b77853c-82ef-4a63-83d4-e15de74137af', 'PAY2607240289843001784903372V6ZJVR', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'd052d932-81c1-4065-bf7a-c6bbda59d7d1', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9488-0dfe-735e-abda-b89ba2ba2ce3', 'loyer', '[\"03-2027\"]', 6000.00, 0.00, 'FCFA', 'especes', 'PAY-6a6376cc46e08-1784903372', 'valide', '2026-07-24 14:29:32', NULL, '2026-07-24 14:29:32', '2026-08-18 08:36:58', 'dsa', NULL, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 14:29:32', '2026-08-18 08:36:58', NULL),
('0cdc6e58-7c70-40ab-92b1-8b203ec4322b', 'PAY-240726-0002', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9490-8be5-704b-adf8-135eec61fcf4', 'loyer', NULL, 6000.00, NULL, 'FCFA', 'orange_money', 'PAY-6a6378f8c946d-1784903928', 'en_attente', '2026-07-24 14:38:48', NULL, '2026-07-24 14:38:48', '2026-07-24 14:40:26', NULL, NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-24 14:38:48', '2026-07-24 14:40:26', '2026-07-24 14:40:26'),
('0f0e1551-018c-4fda-80ac-d0457dabb1ac', 'PAY2607240013', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9420-9bd7-7365-b6da-2169d829a72c', 'loyer', '[\"02-2027\"]', 6000.00, 0.00, 'FCFA', 'especes', 'PAY-6a635c50d2137-1784896592', 'valide', '2026-07-24 12:36:32', NULL, '2026-07-24 12:36:32', '2026-07-24 12:36:32', 'tes', NULL, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 12:36:32', '2026-07-24 12:36:32', NULL),
('0f1e701b-7f1b-4d4a-9678-40347b78d550', 'PAY2607240984690001784903385SA45VH', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9488-4375-71d7-95aa-aa0b0371dcfd', 'loyer', NULL, 6000.00, NULL, 'FCFA', 'orange_money', 'PAY-6a6376d9f12d0-1784903385', 'en_attente', '2026-07-24 14:29:45', NULL, '2026-07-24 14:29:45', '2026-07-24 14:29:46', NULL, NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-24 14:29:45', '2026-07-24 14:29:45', '2026-07-24 14:29:45'),
('2c1be229-12d9-4bda-a94e-8ff953280dd0', 'PAY-240726-0015', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9492-0860-7093-9b57-c1ba019f6d6c', 'loyer', NULL, 6000.00, NULL, 'FCFA', 'orange_money', 'PAY-6a63795a37b9f-1784904026', 'en_attente', '2026-07-24 14:40:26', NULL, '2026-07-24 14:40:26', '2026-07-24 14:40:26', NULL, NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-24 14:40:26', '2026-07-24 14:40:26', '2026-07-24 14:40:26'),
('2d73b8d9-ba99-46fc-9547-f01fbd75eeaf', 'PAY2607240009', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9419-0ccd-738d-a06e-1fdacc5f3f17', 'loyer', '[\"12-2026\"]', 6000.00, 0.00, 'FCFA', 'especes', 'PAY-6a635a61779fc-1784896097', 'valide', '2026-07-24 12:28:17', NULL, '2026-07-24 12:28:17', '2026-07-24 12:28:17', 'test', NULL, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 12:28:17', '2026-07-24 12:28:17', NULL),
('42f83735-a1b4-4a59-b3fc-9aa69db3bd60', 'PAY2607240014', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9420-9bd7-7365-b6da-2169d829a72c', 'loyer', NULL, 6000.00, NULL, 'FCFA', 'orange_money', 'PAY-6a635c50d4845-1784896592', 'en_attente', '2026-07-24 12:36:32', NULL, '2026-07-24 12:36:32', '2026-07-24 12:36:32', NULL, NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-24 12:36:32', '2026-07-24 12:36:32', '2026-07-24 12:36:32'),
('4ebf9aa7-2b01-46f0-984d-cffbe1c19c33', 'PAY2607240007', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9418-aaf1-70d4-9b07-f42b3bab600f', 'loyer', '[\"11-2026\"]', 6000.00, 0.00, 'FCFA', 'especes', 'PAY-6a635a486ad6b-1784896072', 'valide', '2026-07-24 12:27:52', NULL, '2026-07-24 12:27:52', '2026-07-24 12:27:52', 'test', NULL, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 12:27:52', '2026-07-24 12:27:52', NULL),
('570ff0ff-e0a9-484c-bee1-4c1d0b0cb81b', 'PAY2607240004', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9410-a3dc-71d3-b70f-34a043e85239', 'loyer', NULL, 6000.00, NULL, 'FCFA', 'orange_money', 'PAY-6a63583a5862b-1784895546', 'en_attente', '2026-07-24 12:19:06', NULL, '2026-07-24 12:19:06', '2026-07-24 12:36:32', NULL, NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-24 12:19:06', '2026-07-24 12:36:32', '2026-07-24 12:36:32'),
('588229b2-79b2-412c-937a-bd44193f3437', 'PAY-240726-0004', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9491-45b6-7058-b9d8-c145416b0acf', 'loyer', NULL, 6000.00, NULL, 'FCFA', 'orange_money', 'PAY-6a6379285f00f-1784903976', 'en_attente', '2026-07-24 14:39:36', NULL, '2026-07-24 14:39:36', '2026-07-24 14:40:26', NULL, NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-24 14:39:36', '2026-07-24 14:40:26', '2026-07-24 14:40:26'),
('66b1b3ca-d0b0-4230-a97c-92db417932d6', 'PAY2607240001', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9409-6427-73fe-8174-f08e44d14823', 'loyer', '[\"08-2026\"]', 6000.00, 0.00, 'FCFA', 'especes', 'PAY-6a63565f43478-1784895071', 'valide', '2026-07-24 12:11:11', NULL, '2026-07-24 12:11:11', '2026-07-24 12:11:11', 'test', NULL, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 12:11:11', '2026-07-24 12:11:11', NULL),
('6a6a7eff-380e-45df-aa9b-05e3858f06fd', 'PAY-240726-0003', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9491-45b6-7058-b9d8-c145416b0acf', 'loyer', '[\"07-2027\"]', 6000.00, 0.00, 'FCFA', 'especes', 'PAY-6a6379285cc69-1784903976', 'valide', '2026-07-24 14:39:36', NULL, '2026-07-24 14:39:36', '2026-07-24 14:39:36', 're', NULL, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 14:39:36', '2026-07-24 14:39:36', NULL),
('85890cce-b9e2-4667-a20a-82396307d2f8', 'PAY2607240010', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9419-0ccd-738d-a06e-1fdacc5f3f17', 'loyer', NULL, 6000.00, NULL, 'FCFA', 'orange_money', 'PAY-6a635a617b329-1784896097', 'en_attente', '2026-07-24 12:28:17', NULL, '2026-07-24 12:28:17', '2026-07-24 12:36:32', NULL, NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-24 12:28:17', '2026-07-24 12:36:32', '2026-07-24 12:36:32'),
('9ba90227-09cc-44c8-a767-da7217fab45f', 'PAY2607240977035001784903385VQLDBT', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9488-4375-71d7-95aa-aa0b0371dcfd', 'loyer', '[\"04-2027\",\"05-2027\"]', 12000.00, 0.00, 'FCFA', 'especes', 'PAY-6a6376d9eea6e-1784903385', 'valide', '2026-07-24 14:29:45', NULL, '2026-07-24 14:29:45', '2026-07-24 14:29:45', NULL, NULL, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 14:29:45', '2026-07-24 14:29:45', NULL),
('9f1a7f8d-aef2-4b50-9da4-b0761528c22c', 'PAY2607240002', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9409-6427-73fe-8174-f08e44d14823', 'loyer', NULL, 6000.00, NULL, 'FCFA', 'orange_money', 'PAY-6a63565f45a9a-1784895071', 'en_attente', '2026-07-24 12:11:11', NULL, '2026-07-24 12:11:11', '2026-07-24 12:36:32', NULL, NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-24 12:11:11', '2026-07-24 12:36:32', '2026-07-24 12:36:32'),
('a85f1bec-df0a-4273-a7e1-8be1f1f55da3', 'PAY2607240011', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f941a-861f-72a6-aa3e-264631d55281', 'loyer', '[\"01-2027\"]', 6000.00, 0.00, 'FCFA', 'especes', 'PAY-6a635ac214753-1784896194', 'valide', '2026-07-24 12:29:54', NULL, '2026-07-24 12:29:54', '2026-07-24 12:29:54', 'tes', NULL, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 12:29:54', '2026-07-24 12:29:54', NULL),
('a8918c28-b0e2-43a7-8514-50baa76fb0c4', 'PAY-240726-0001', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9490-8be5-704b-adf8-135eec61fcf4', 'loyer', '[\"06-2027\"]', 6000.00, 0.00, 'FCFA', 'especes', 'PAY-6a6378f8c63b8-1784903928', 'valide', '2026-07-24 14:38:48', NULL, '2026-07-24 14:38:48', '2026-07-24 14:38:48', 'test', NULL, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 14:38:48', '2026-07-24 14:38:48', NULL),
('aadd8b66-fa09-4984-9a41-e83b3dc5bb49', 'PAY-240726-0014', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9492-0860-7093-9b57-c1ba019f6d6c', 'loyer', '[\"08-2027\"]', 6000.00, 0.00, 'FCFA', 'especes', 'PAY-6a63795a35bd8-1784904026', 'valide', '2026-07-24 14:40:26', NULL, '2026-07-24 14:40:26', '2026-07-24 14:40:26', 'ddd', NULL, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 14:40:26', '2026-07-24 14:40:26', NULL),
('af63d127-77c1-4a0a-8ba4-ffa1411f7a27', 'PAY2607240008', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9418-aaf1-70d4-9b07-f42b3bab600f', 'loyer', NULL, 6000.00, NULL, 'FCFA', 'orange_money', 'PAY-6a635a486df0b-1784896072', 'en_attente', '2026-07-24 12:27:52', NULL, '2026-07-24 12:27:52', '2026-07-24 12:36:32', NULL, NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-24 12:27:52', '2026-07-24 12:36:32', '2026-07-24 12:36:32'),
('bd934686-52ba-4bd5-9b78-ed5020916ffc', 'PAY2607240012', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f941a-861f-72a6-aa3e-264631d55281', 'loyer', NULL, 6000.00, NULL, 'FCFA', 'orange_money', 'PAY-6a635ac216f9c-1784896194', 'en_attente', '2026-07-24 12:29:54', NULL, '2026-07-24 12:29:54', '2026-07-24 12:36:32', NULL, NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-24 12:29:54', '2026-07-24 12:36:32', '2026-07-24 12:36:32'),
('ce172d90-42e9-475f-8b82-cd193a4d666e', 'PAY2607240005', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9414-9141-727b-8830-d53ed6961dbc', 'loyer', '[\"10-2026\"]', 6000.00, 0.00, 'FCFA', 'especes', 'PAY-6a63593baf3f1-1784895803', 'valide', '2026-07-24 12:23:23', NULL, '2026-07-24 12:23:23', '2026-07-24 12:23:23', 'test', NULL, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 12:23:23', '2026-07-24 12:23:23', NULL),
('d5848c1f-0a17-4121-9a3b-2abb50dee4af', 'PAY2607240003', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9410-a3dc-71d3-b70f-34a043e85239', 'loyer', '[\"09-2026\"]', 6000.00, 0.00, 'FCFA', 'especes', 'PAY-6a63583a535d4-1784895546', 'valide', '2026-07-24 12:19:06', NULL, '2026-07-24 12:19:06', '2026-07-24 12:19:06', 'tes', NULL, NULL, NULL, 'system', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-24 12:19:06', '2026-07-24 12:19:06', NULL),
('d99de0aa-236e-4ed5-a532-3e02ea74e6af', 'PAY2607240006', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9414-9141-727b-8830-d53ed6961dbc', 'loyer', NULL, 6000.00, NULL, 'FCFA', 'orange_money', 'PAY-6a63593bb165e-1784895803', 'en_attente', '2026-07-24 12:23:23', NULL, '2026-07-24 12:23:23', '2026-07-24 12:36:32', NULL, NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-24 12:23:23', '2026-07-24 12:36:32', '2026-07-24 12:36:32'),
('e5ec9697-743a-4eff-b451-010bc310e9c2', 'PAY2607240298552001784903372IOYG3A', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', 'a3755ba5-567a-4f76-b127-b6ce9115c8a0', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'c1123cbd-658c-483a-a246-dc6726039649', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '019f9488-0dfe-735e-abda-b89ba2ba2ce3', 'loyer', NULL, 6000.00, NULL, 'FCFA', 'orange_money', 'PAY-6a6376cc4989c-1784903372', 'en_attente', '2026-07-24 14:29:32', NULL, '2026-07-24 14:29:32', '2026-07-24 14:29:32', NULL, NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-24 14:29:32', '2026-07-24 14:29:32', '2026-07-24 14:29:32');

-- --------------------------------------------------------

--
-- Structure de la table `paiement_repartitions`
--

CREATE TABLE `paiement_repartitions` (
  `id_repartition` char(150) NOT NULL,
  `paiement_id` char(150) NOT NULL,
  `type` enum('arriere','mois_cours','avance') NOT NULL,
  `montant` decimal(15,2) NOT NULL DEFAULT 0.00,
  `mois` varchar(10) DEFAULT NULL,
  `annee` varchar(10) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `paiement_repartitions`
--

INSERT INTO `paiement_repartitions` (`id_repartition`, `paiement_id`, `type`, `montant`, `mois`, `annee`, `description`, `created_at`, `updated_at`) VALUES
('019f9409-6438-702c-91f1-24cf4dfa6597', '66b1b3ca-d0b0-4230-a97c-92db417932d6', 'mois_cours', 6000.00, '08', '2026', 'Paiement du mois en cours', '2026-07-24 12:11:11', '2026-07-24 12:11:11'),
('019f9410-a3fd-73fa-8213-f7fc32e17b1a', 'd5848c1f-0a17-4121-9a3b-2abb50dee4af', 'mois_cours', 6000.00, '09', '2026', 'Paiement du mois en cours', '2026-07-24 12:19:06', '2026-07-24 12:19:06'),
('019f9414-9150-7302-8379-53b7a374232e', 'ce172d90-42e9-475f-8b82-cd193a4d666e', 'mois_cours', 6000.00, '10', '2026', 'Paiement du mois en cours', '2026-07-24 12:23:23', '2026-07-24 12:23:23'),
('019f9418-ab04-71b1-93b5-9c7c255c21ba', '4ebf9aa7-2b01-46f0-984d-cffbe1c19c33', 'mois_cours', 6000.00, '11', '2026', 'Paiement du mois en cours', '2026-07-24 12:27:52', '2026-07-24 12:27:52'),
('019f9419-0ce2-7104-b139-101cea8bc6cb', '2d73b8d9-ba99-46fc-9547-f01fbd75eeaf', 'mois_cours', 6000.00, '12', '2026', 'Paiement du mois en cours', '2026-07-24 12:28:17', '2026-07-24 12:28:17'),
('019f941a-862f-7125-96a8-11c28a4f4a2b', 'a85f1bec-df0a-4273-a7e1-8be1f1f55da3', 'mois_cours', 6000.00, '01', '2027', 'Paiement du mois en cours', '2026-07-24 12:29:54', '2026-07-24 12:29:54'),
('019f9420-9be9-7245-9896-95e2476f1cfe', '0f0e1551-018c-4fda-80ac-d0457dabb1ac', 'mois_cours', 6000.00, '02', '2027', 'Paiement du mois en cours', '2026-07-24 12:36:32', '2026-07-24 12:36:32'),
('019f9488-0e0f-7189-953c-618ae0bd34a7', '0b77853c-82ef-4a63-83d4-e15de74137af', 'mois_cours', 6000.00, '03', '2027', 'Paiement du mois en cours', '2026-07-24 14:29:32', '2026-07-24 14:29:32'),
('019f9488-4387-732f-a3eb-28ba4c2f6361', '9ba90227-09cc-44c8-a767-da7217fab45f', 'mois_cours', 6000.00, '04', '2027', 'Paiement du mois en cours', '2026-07-24 14:29:45', '2026-07-24 14:29:45'),
('019f9488-4388-73e9-ae8a-79489b13811c', '9ba90227-09cc-44c8-a767-da7217fab45f', 'avance', 6000.00, '5', '2027', 'Paiement anticipé - loyer_id 019f9488-4385-72cb-bd95-a253bbb5fde6', '2026-07-24 14:29:45', '2026-07-24 14:29:45'),
('019f9490-8bfb-725c-ab94-c6b84e7fb30f', 'a8918c28-b0e2-43a7-8514-50baa76fb0c4', 'mois_cours', 6000.00, '06', '2027', 'Paiement du mois en cours', '2026-07-24 14:38:48', '2026-07-24 14:38:48'),
('019f9491-45c7-729c-9111-f20efab32950', '6a6a7eff-380e-45df-aa9b-05e3858f06fd', 'mois_cours', 6000.00, '07', '2027', 'Paiement du mois en cours', '2026-07-24 14:39:36', '2026-07-24 14:39:36'),
('019f9492-0875-71a7-b884-4cb057775651', 'aadd8b66-fa09-4984-9a41-e83b3dc5bb49', 'mois_cours', 6000.00, '08', '2027', 'Paiement du mois en cours', '2026-07-24 14:40:26', '2026-07-24 14:40:26');

-- --------------------------------------------------------

--
-- Structure de la table `parametres_historique`
--

CREATE TABLE `parametres_historique` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` bigint(20) UNSIGNED NOT NULL,
  `action` enum('create','update','delete') NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parametres_logs`
--

CREATE TABLE `parametres_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `level` enum('info','warning','error','critical') DEFAULT 'info',
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structure de la table `payment_providers`
--

CREATE TABLE `payment_providers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(60) NOT NULL,
  `api_url` varchar(2048) DEFAULT NULL,
  `api_key` text DEFAULT NULL,
  `merchant_key` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `api_user` text DEFAULT NULL,
  `subscription_key` text DEFAULT NULL,
  `request_signing_secret` text DEFAULT NULL,
  `environment` varchar(30) NOT NULL DEFAULT 'sandbox',
  `webhook_secret` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `payment_providers`
--

INSERT INTO `payment_providers` (`id`, `code`, `name`, `api_url`, `api_key`, `merchant_key`, `is_active`, `created_at`, `updated_at`, `api_user`, `subscription_key`, `request_signing_secret`, `environment`, `webhook_secret`) VALUES
(1, 'moov', 'Moov', NULL, NULL, NULL, 0, '2026-09-19 20:13:50', '2026-09-19 20:13:50', NULL, NULL, NULL, 'sandbox', NULL),
(2, 'orange', 'Orange', NULL, NULL, NULL, 0, '2026-09-19 20:13:50', '2026-09-19 20:13:50', NULL, NULL, NULL, 'sandbox', NULL),
(3, 'mtn', 'MTN', NULL, NULL, NULL, 0, '2026-09-19 20:13:50', '2026-09-19 20:13:50', NULL, NULL, NULL, 'sandbox', NULL),
(4, 'wave', 'Wave', 'https://api.wave.com', 'eyJpdiI6InR3cTNZdEFmQlFOQXBVWUkrVFY1SUE9PSIsInZhbHVlIjoiZzFZY0F2UGFuaUk4Y3poZ3Q1dTAvdz09IiwibWFjIjoiNjhjNmMwYTkxYmM4NzcxYjdjZGNlZDdkZDE2YzExZDgyMjlmMzM0NjE1YWZjYWNiMmExMDVhMmY2ZWQzMWQwNiIsInRhZyI6IiJ9', NULL, 1, '2026-09-19 20:13:50', '2026-09-25 15:18:38', NULL, NULL, 'eyJpdiI6IkNKMTQ4V0hoNDNVeFlaNzl6SnB4YWc9PSIsInZhbHVlIjoiWWVPbmVGb1paSm0vcXQyRXdpRi95VHlNWkZLS3MyYVowODViUHV1WGkxWT0iLCJtYWMiOiJjMjRiYjhlZjVkNmIwMjVkZmM1NmFjNzZmMTMzZjAzYjg3ZmU5YjUwZWE1ZTM4M2Q0Y2I0Yzg3ZGIwNjA1MTIwIiwidGFnIjoiIn0=', 'sandbox', 'eyJpdiI6InYyTXo4MlNvc3JWWHcwVWJMRTdNK0E9PSIsInZhbHVlIjoiN1U1K3ZSaFJQRGRQOHkvQmQ0SnQ3Zz09IiwibWFjIjoiNDAxZmIxYTg2OGRlOTIwZjMyZWE0ZmE1NTYxZmE1YzRjNDU2MDhkNDk1YWFhMWQ2MmZhOTI0ZGVhNTQyYjkxNSIsInRhZyI6IiJ9'),
(5, 'cinetpay', 'CinetPay', NULL, NULL, NULL, 0, '2026-09-19 20:13:50', '2026-09-19 20:13:50', NULL, NULL, NULL, 'sandbox', NULL),
(6, 'tresorpay', 'TrésorPay', NULL, NULL, NULL, 0, '2026-09-19 20:13:51', '2026-09-19 20:13:51', NULL, NULL, NULL, 'sandbox', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `module_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `module` varchar(80) NOT NULL,
  `action` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `permissions`
--

INSERT INTO `permissions` (`id`, `module_id`, `name`, `slug`, `module`, `action`, `description`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Gérer les rôles et permissions', 'parametrage.roles.manage', 'Paramétrage', NULL, 'Créer les rôles et définir leurs autorisations.', '2026-08-11 08:22:46', '2026-08-11 08:22:46'),
(2, NULL, 'Gérer les utilisateurs', 'utilisateurs.manage', 'Utilisateurs', NULL, 'Créer, modifier et affecter les utilisateurs.', '2026-08-11 08:22:46', '2026-08-11 08:22:46'),
(3, NULL, 'Consulter les statistiques', 'statistiques.view', 'Statistiques', NULL, 'Consulter les tableaux de bord et indicateurs.', '2026-08-11 08:22:46', '2026-08-11 08:22:46'),
(4, NULL, 'Gérer les admissions', 'admissions.manage', 'Admissions', NULL, 'Traiter les dossiers et admissions.', '2026-08-11 08:22:46', '2026-08-11 08:22:46'),
(5, NULL, 'Gérer les attributions', 'attributions.manage', 'Hébergement', NULL, 'Attribuer et réaffecter les chambres.', '2026-08-11 08:22:46', '2026-08-11 08:22:46'),
(6, NULL, 'Gérer le parc immobilier', 'parc.manage', 'Hébergement', NULL, 'Administrer cités, bâtiments et chambres.', '2026-08-11 08:22:46', '2026-08-11 08:22:46'),
(7, NULL, 'Gérer les paiements', 'paiements.manage', 'Vie résidentielle', NULL, 'Enregistrer et valider les paiements.', '2026-08-11 08:22:46', '2026-08-11 08:22:46'),
(8, NULL, 'Gérer la maintenance', 'maintenance.manage', 'Vie résidentielle', NULL, 'Créer et traiter les ordres de travail.', '2026-08-11 08:22:46', '2026-08-11 08:22:46'),
(9, NULL, 'Gérer la discipline', 'discipline.manage', 'Vie résidentielle', NULL, 'Traiter rapports et sanctions disciplinaires.', '2026-08-11 08:22:46', '2026-08-11 08:22:46'),
(10, NULL, 'Clôturer les séjours', 'sejours.cloture', 'Vie résidentielle', NULL, 'Gérer les départs et états des lieux de sortie.', '2026-08-11 08:22:46', '2026-08-11 08:22:46'),
(11, NULL, 'Gérer les paramètres', 'parametrage.manage', 'Paramétrage', NULL, 'Modifier les paramètres généraux.', '2026-08-11 08:22:46', '2026-08-11 08:22:46'),
(12, 1, 'Consulter — Tableau de bord', 'dashboard.view', 'Tableau de bord', 'view', 'Autorise cette action dans le module Tableau de bord.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(13, 2, 'Consulter — Utilisateurs', 'utilisateurs.view', 'Utilisateurs', 'view', 'Autorise cette action dans le module Utilisateurs.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(14, 3, 'Consulter — Gestion Hébergement', 'hebergement.view', 'Gestion Hébergement', 'view', 'Autorise cette action dans le module Gestion Hébergement.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(15, 4, 'Consulter — Paramétrage général', 'parametrage.view', 'Paramétrage général', 'view', 'Autorise cette action dans le module Paramétrage général.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(16, 5, 'Consulter — Parc immobilier', 'hebergement.parc.view', 'Parc immobilier', 'view', 'Autorise cette action dans le module Parc immobilier.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(17, 5, 'Créer — Parc immobilier', 'hebergement.parc.create', 'Parc immobilier', 'create', 'Autorise cette action dans le module Parc immobilier.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(18, 5, 'Modifier — Parc immobilier', 'hebergement.parc.update', 'Parc immobilier', 'update', 'Autorise cette action dans le module Parc immobilier.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(19, 5, 'Supprimer — Parc immobilier', 'hebergement.parc.delete', 'Parc immobilier', 'delete', 'Autorise cette action dans le module Parc immobilier.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(20, 6, 'Consulter — Équipements', 'hebergement.equipements.view', 'Équipements', 'view', 'Autorise cette action dans le module Équipements.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(21, 6, 'Créer — Équipements', 'hebergement.equipements.create', 'Équipements', 'create', 'Autorise cette action dans le module Équipements.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(22, 6, 'Modifier — Équipements', 'hebergement.equipements.update', 'Équipements', 'update', 'Autorise cette action dans le module Équipements.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(23, 6, 'Supprimer — Équipements', 'hebergement.equipements.delete', 'Équipements', 'delete', 'Autorise cette action dans le module Équipements.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(24, 7, 'Consulter — Campagnes et admissions', 'hebergement.admissions.view', 'Campagnes et admissions', 'view', 'Autorise cette action dans le module Campagnes et admissions.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(25, 7, 'Créer — Campagnes et admissions', 'hebergement.admissions.create', 'Campagnes et admissions', 'create', 'Autorise cette action dans le module Campagnes et admissions.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(26, 7, 'Modifier — Campagnes et admissions', 'hebergement.admissions.update', 'Campagnes et admissions', 'update', 'Autorise cette action dans le module Campagnes et admissions.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(27, 7, 'Valider — Campagnes et admissions', 'hebergement.admissions.validate', 'Campagnes et admissions', 'validate', 'Autorise cette action dans le module Campagnes et admissions.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(28, 8, 'Consulter — Attribution des chambres', 'hebergement.attributions.view', 'Attribution des chambres', 'view', 'Autorise cette action dans le module Attribution des chambres.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(29, 8, 'Affecter — Attribution des chambres', 'hebergement.attributions.assign', 'Attribution des chambres', 'assign', 'Autorise cette action dans le module Attribution des chambres.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(30, 8, 'Valider — Attribution des chambres', 'hebergement.attributions.validate', 'Attribution des chambres', 'validate', 'Autorise cette action dans le module Attribution des chambres.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(31, 8, 'Exporter — Attribution des chambres', 'hebergement.attributions.export', 'Attribution des chambres', 'export', 'Autorise cette action dans le module Attribution des chambres.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(32, 9, 'Consulter — Résidents et contrats', 'hebergement.residents.view', 'Résidents et contrats', 'view', 'Autorise cette action dans le module Résidents et contrats.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(33, 9, 'Créer — Résidents et contrats', 'hebergement.residents.create', 'Résidents et contrats', 'create', 'Autorise cette action dans le module Résidents et contrats.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(34, 9, 'Modifier — Résidents et contrats', 'hebergement.residents.update', 'Résidents et contrats', 'update', 'Autorise cette action dans le module Résidents et contrats.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(35, 10, 'Consulter — Vie résidentielle', 'hebergement.vie-residentielle.view', 'Vie résidentielle', 'view', 'Autorise cette action dans le module Vie résidentielle.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(36, 11, 'Consulter — Départs et états des lieux', 'hebergement.departs.view', 'Départs et états des lieux', 'view', 'Autorise cette action dans le module Départs et états des lieux.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(37, 11, 'Créer — Départs et états des lieux', 'hebergement.departs.create', 'Départs et états des lieux', 'create', 'Autorise cette action dans le module Départs et états des lieux.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(38, 11, 'Modifier — Départs et états des lieux', 'hebergement.departs.update', 'Départs et états des lieux', 'update', 'Autorise cette action dans le module Départs et états des lieux.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(39, 11, 'Clôturer — Départs et états des lieux', 'hebergement.departs.close', 'Départs et états des lieux', 'close', 'Autorise cette action dans le module Départs et états des lieux.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(40, 12, 'Consulter — Paiements des loyers', 'hebergement.paiements.view', 'Paiements des loyers', 'view', 'Autorise cette action dans le module Paiements des loyers.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(41, 12, 'Créer — Paiements des loyers', 'hebergement.paiements.create', 'Paiements des loyers', 'create', 'Autorise cette action dans le module Paiements des loyers.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(42, 12, 'Valider — Paiements des loyers', 'hebergement.paiements.validate', 'Paiements des loyers', 'validate', 'Autorise cette action dans le module Paiements des loyers.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(43, 12, 'Exporter — Paiements des loyers', 'hebergement.paiements.export', 'Paiements des loyers', 'export', 'Autorise cette action dans le module Paiements des loyers.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(44, 13, 'Consulter — Reporting et statistiques', 'hebergement.reporting.view', 'Reporting et statistiques', 'view', 'Autorise cette action dans le module Reporting et statistiques.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(45, 13, 'Exporter — Reporting et statistiques', 'hebergement.reporting.export', 'Reporting et statistiques', 'export', 'Autorise cette action dans le module Reporting et statistiques.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(46, 14, 'Consulter — Paramétrage hébergement', 'hebergement.parametrage.view', 'Paramétrage hébergement', 'view', 'Autorise cette action dans le module Paramétrage hébergement.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(47, 14, 'Configurer — Paramétrage hébergement', 'hebergement.parametrage.configure', 'Paramétrage hébergement', 'configure', 'Autorise cette action dans le module Paramétrage hébergement.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(48, 15, 'Consulter — Gestion des modules', 'parametrage.modules.view', 'Gestion des modules', 'view', 'Autorise l’action « Consulter » dans le module Gestion des modules.', '2026-08-11 08:47:46', '2026-08-11 11:51:31'),
(49, 15, 'Créer — Gestion des modules', 'parametrage.modules.create', 'Gestion des modules', 'create', 'Autorise l’action « Créer » dans le module Gestion des modules.', '2026-08-11 08:47:46', '2026-08-11 11:51:31'),
(50, 15, 'Modifier — Gestion des modules', 'parametrage.modules.update', 'Gestion des modules', 'update', 'Autorise l’action « Modifier » dans le module Gestion des modules.', '2026-08-11 08:47:46', '2026-08-11 11:51:31'),
(51, 15, 'Supprimer — Gestion des modules', 'parametrage.modules.delete', 'Gestion des modules', 'delete', 'Autorise l’action « Supprimer » dans le module Gestion des modules.', '2026-08-11 08:47:46', '2026-08-11 11:51:31'),
(52, 15, 'Configurer — Gestion des modules', 'parametrage.modules.configure', 'Gestion des modules', 'configure', 'Autorise l’action « Configurer » dans le module Gestion des modules.', '2026-08-11 08:47:46', '2026-08-11 11:51:31'),
(53, 16, 'Consulter — Rôles et permissions', 'parametrage.roles.view', 'Rôles et permissions', 'view', 'Autorise l’action « Consulter » dans le module Rôles et permissions.', '2026-08-11 08:47:46', '2026-08-11 11:51:47'),
(54, 16, 'Créer — Rôles et permissions', 'parametrage.roles.create', 'Rôles et permissions', 'create', 'Autorise l’action « Créer » dans le module Rôles et permissions.', '2026-08-11 08:47:46', '2026-08-11 11:51:47'),
(55, 16, 'Modifier — Rôles et permissions', 'parametrage.roles.update', 'Rôles et permissions', 'update', 'Autorise l’action « Modifier » dans le module Rôles et permissions.', '2026-08-11 08:47:46', '2026-08-11 11:51:47'),
(56, 16, 'Supprimer — Rôles et permissions', 'parametrage.roles.delete', 'Rôles et permissions', 'delete', 'Autorise l’action « Supprimer » dans le module Rôles et permissions.', '2026-08-11 08:47:46', '2026-08-11 11:51:47'),
(57, 16, 'Affecter — Rôles et permissions', 'parametrage.roles.assign', 'Rôles et permissions', 'assign', 'Autorise l’action « Affecter » dans le module Rôles et permissions.', '2026-08-11 08:47:46', '2026-08-11 11:51:47'),
(58, 17, 'Consulter — Référentiels généraux', 'parametrage.referentiels.view', 'Référentiels généraux', 'view', 'Autorise cette action dans le module Référentiels généraux.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(59, 17, 'Créer — Référentiels généraux', 'parametrage.referentiels.create', 'Référentiels généraux', 'create', 'Autorise cette action dans le module Référentiels généraux.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(60, 17, 'Modifier — Référentiels généraux', 'parametrage.referentiels.update', 'Référentiels généraux', 'update', 'Autorise cette action dans le module Référentiels généraux.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(61, 17, 'Supprimer — Référentiels généraux', 'parametrage.referentiels.delete', 'Référentiels généraux', 'delete', 'Autorise cette action dans le module Référentiels généraux.', '2026-08-11 08:47:46', '2026-08-11 08:47:46'),
(62, 18, 'Consulter — Configuration système', 'parametrage.configuration.view', 'Configuration système', 'view', 'Autorise l’action « Consulter » dans le module Configuration système.', '2026-08-11 08:47:46', '2026-08-11 11:49:04'),
(63, 18, 'Configurer — Configuration système', 'parametrage.configuration.configure', 'Configuration système', 'configure', 'Autorise l’action « Configurer » dans le module Configuration système.', '2026-08-11 08:47:46', '2026-08-11 11:49:04'),
(64, 19, 'Consulter — Gestions des regions', 'parametrage.region.view', 'Gestions des regions', 'view', 'Autorise l’action « Consulter » dans le module Gestions des regions.', '2026-08-11 10:35:17', '2026-08-11 10:36:01'),
(65, 19, 'Créer — Gestions des regions', 'parametrage.region.create', 'Gestions des regions', 'create', 'Autorise l’action « Créer » dans le module Gestions des regions.', '2026-08-11 10:35:17', '2026-08-11 10:36:01'),
(66, 19, 'Modifier — Gestions des regions', 'parametrage.region.update', 'Gestions des regions', 'update', 'Autorise l’action « Modifier » dans le module Gestions des regions.', '2026-08-11 10:35:17', '2026-08-11 10:36:01'),
(67, 20, 'Consulter — Gestions des villes', 'parametrage.ville.view', 'Gestions des villes', 'view', 'Autorise l’action « Consulter » dans le module Gestions des villes.', '2026-08-11 10:42:19', '2026-08-11 10:42:19'),
(68, 20, 'Créer — Gestions des villes', 'parametrage.ville.create', 'Gestions des villes', 'create', 'Autorise l’action « Créer » dans le module Gestions des villes.', '2026-08-11 10:42:19', '2026-08-11 10:42:19'),
(69, 20, 'Modifier — Gestions des villes', 'parametrage.ville.update', 'Gestions des villes', 'update', 'Autorise l’action « Modifier » dans le module Gestions des villes.', '2026-08-11 10:42:19', '2026-08-11 10:42:19'),
(70, 21, 'Consulter — Gestion des niveaux d’études', 'parametrage.niveau.view', 'Gestion des niveaux d’études', 'view', 'Autorise l’action « Consulter » dans le module Gestion des niveaux d’études.', '2026-08-11 11:42:29', '2026-08-11 11:42:29'),
(71, 21, 'Créer — Gestion des niveaux d’études', 'parametrage.niveau.create', 'Gestion des niveaux d’études', 'create', 'Autorise l’action « Créer » dans le module Gestion des niveaux d’études.', '2026-08-11 11:42:29', '2026-08-11 11:42:29'),
(72, 21, 'Modifier — Gestion des niveaux d’études', 'parametrage.niveau.update', 'Gestion des niveaux d’études', 'update', 'Autorise l’action « Modifier » dans le module Gestion des niveaux d’études.', '2026-08-11 11:42:29', '2026-08-11 11:42:29'),
(73, 21, 'Supprimer — Gestion des niveaux d’études', 'parametrage.niveau.delete', 'Gestion des niveaux d’études', 'delete', 'Autorise l’action « Supprimer » dans le module Gestion des niveaux d’études.', '2026-08-11 11:42:29', '2026-08-11 11:42:29'),
(74, 22, 'Consulter — geation des filières', 'parametrage.filiere.view', 'geation des filières', 'view', 'Autorise l’action « Consulter » dans le module geation des filières.', '2026-08-11 11:44:14', '2026-08-11 11:44:14'),
(75, 22, 'Créer — geation des filières', 'parametrage.filiere.create', 'geation des filières', 'create', 'Autorise l’action « Créer » dans le module geation des filières.', '2026-08-11 11:44:14', '2026-08-11 11:44:14'),
(76, 22, 'Modifier — geation des filières', 'parametrage.filiere.update', 'geation des filières', 'update', 'Autorise l’action « Modifier » dans le module geation des filières.', '2026-08-11 11:44:14', '2026-08-11 11:44:14'),
(77, 22, 'Supprimer — geation des filières', 'parametrage.filiere.delete', 'geation des filières', 'delete', 'Autorise l’action « Supprimer » dans le module geation des filières.', '2026-08-11 11:44:14', '2026-08-11 11:44:14'),
(78, 23, 'Consulter — Gestion des départements', 'parametrage.departement.view', 'Gestion des départements', 'view', 'Autorise l’action « Consulter » dans le module Gestion des départements.', '2026-08-11 11:46:52', '2026-08-11 11:46:52'),
(79, 23, 'Créer — Gestion des départements', 'parametrage.departement.create', 'Gestion des départements', 'create', 'Autorise l’action « Créer » dans le module Gestion des départements.', '2026-08-11 11:46:52', '2026-08-11 11:46:52'),
(80, 23, 'Modifier — Gestion des départements', 'parametrage.departement.update', 'Gestion des départements', 'update', 'Autorise l’action « Modifier » dans le module Gestion des départements.', '2026-08-11 11:46:52', '2026-08-11 11:46:52'),
(81, 23, 'Supprimer — Gestion des départements', 'parametrage.departement.delete', 'Gestion des départements', 'delete', 'Autorise l’action « Supprimer » dans le module Gestion des départements.', '2026-08-11 11:46:52', '2026-08-11 11:46:52'),
(82, 24, 'Consulter — Gestion des types d\'utilisateurs', 'parametrage.type-utilisateur.view', 'Gestion des types d\'utilisateurs', 'view', 'Autorise l’action « Consulter » dans le module Gestion des types d\'utilisateurs.', '2026-08-11 11:50:26', '2026-08-11 11:50:26'),
(83, 24, 'Créer — Gestion des types d\'utilisateurs', 'parametrage.type-utilisateur.create', 'Gestion des types d\'utilisateurs', 'create', 'Autorise l’action « Créer » dans le module Gestion des types d\'utilisateurs.', '2026-08-11 11:50:26', '2026-08-11 11:50:26'),
(84, 24, 'Modifier — Gestion des types d\'utilisateurs', 'parametrage.type-utilisateur.update', 'Gestion des types d\'utilisateurs', 'update', 'Autorise l’action « Modifier » dans le module Gestion des types d\'utilisateurs.', '2026-08-11 11:50:26', '2026-08-11 11:50:26'),
(85, 24, 'Supprimer — Gestion des types d\'utilisateurs', 'parametrage.type-utilisateur.delete', 'Gestion des types d\'utilisateurs', 'delete', 'Autorise l’action « Supprimer » dans le module Gestion des types d\'utilisateurs.', '2026-08-11 11:50:26', '2026-08-11 11:50:26'),
(86, 17, 'Consulter — gestions des pays', 'parametrage.countrie.view', 'gestions des pays', 'view', 'Autorise l’action « Consulter » dans le module gestions des pays.', '2026-08-11 11:52:43', '2026-08-11 11:52:43'),
(87, 17, 'Créer — gestions des pays', 'parametrage.countrie.create', 'gestions des pays', 'create', 'Autorise l’action « Créer » dans le module gestions des pays.', '2026-08-11 11:52:43', '2026-08-11 11:52:43'),
(88, 17, 'Modifier — gestions des pays', 'parametrage.countrie.update', 'gestions des pays', 'update', 'Autorise l’action « Modifier » dans le module gestions des pays.', '2026-08-11 11:52:43', '2026-08-11 11:52:43'),
(89, 17, 'Supprimer — gestions des pays', 'parametrage.countrie.delete', 'gestions des pays', 'delete', 'Autorise l’action « Supprimer » dans le module gestions des pays.', '2026-08-11 11:52:43', '2026-08-11 11:52:43'),
(90, 25, 'Consulter tous les journaux d\'activité', 'activity.view', 'Journal d\'activité', 'view', 'Autorise un responsable à consulter l\'activité de tous les utilisateurs.', '2026-08-18 10:14:01', '2026-08-18 10:14:01'),
(91, 26, 'Configurer les API de paiement', 'parametrage.payment-providers.configure', 'API de paiement', 'configure', 'Modifier les identifiants et activer ou désactiver les réseaux.', '2026-09-19 20:13:51', '2026-09-19 20:13:51'),
(92, 27, 'Rédiger et valider les documents', 'parametrage.legal-documents.manage', 'Signatures et documents', 'manage', 'Rédiger et valider les documents', '2026-09-20 11:00:09', '2026-09-20 11:00:09'),
(93, 27, 'Configurer les signatures des responsables', 'parametrage.legal-documents.signatures', 'Signatures et documents', 'signatures', 'Configurer les signatures des responsables', '2026-09-20 11:00:09', '2026-09-20 11:00:09'),
(94, 28, 'Valider les dérogations d’admission', 'hebergement.admissions.derogations.validate', 'Admissions', 'validate', 'Décider sur la dérogation demandée par un autre agent.', '2026-09-23 08:55:13', '2026-09-23 08:55:13'),
(95, 29, 'Créer — Annonces', 'hebergement.annonces.create', 'Annonces', 'create', 'Autorise cette action dans le module Annonces.', '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(96, 29, 'Supprimer — Annonces', 'hebergement.annonces.delete', 'Annonces', 'delete', 'Autorise cette action dans le module Annonces.', '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(97, 29, 'Modifier — Annonces', 'hebergement.annonces.update', 'Annonces', 'update', 'Autorise cette action dans le module Annonces.', '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(98, 29, 'Valider — Annonces', 'hebergement.annonces.validate', 'Annonces', 'validate', 'Autorise cette action dans le module Annonces.', '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(99, 29, 'Consulter — Annonces', 'hebergement.annonces.view', 'Annonces', 'view', 'Autorise cette action dans le module Annonces.', '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(100, 11, 'Exporter — Départs et états des lieux', 'hebergement.departs.export', 'Départs et états des lieux', 'export', 'Autorise cette action dans le module Départs et états des lieux.', '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(101, 30, 'Créer — Discipline', 'hebergement.discipline.create', 'Discipline', 'create', 'Autorise cette action dans le module Discipline.', '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(102, 30, 'Modifier — Discipline', 'hebergement.discipline.update', 'Discipline', 'update', 'Autorise cette action dans le module Discipline.', '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(103, 30, 'Consulter — Discipline', 'hebergement.discipline.view', 'Discipline', 'view', 'Autorise cette action dans le module Discipline.', '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(104, 31, 'Affecter — Maintenance', 'hebergement.maintenance.assign', 'Maintenance', 'assign', 'Autorise cette action dans le module Maintenance.', '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(105, 31, 'Créer — Maintenance', 'hebergement.maintenance.create', 'Maintenance', 'create', 'Autorise cette action dans le module Maintenance.', '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(106, 31, 'Modifier — Maintenance', 'hebergement.maintenance.update', 'Maintenance', 'update', 'Autorise cette action dans le module Maintenance.', '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(107, 31, 'Consulter — Maintenance', 'hebergement.maintenance.view', 'Maintenance', 'view', 'Autorise cette action dans le module Maintenance.', '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(108, 12, 'Modifier — Paiements des loyers', 'hebergement.paiements.update', 'Paiements des loyers', 'update', 'Autorise cette action dans le module Paiements des loyers.', '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(109, 5, 'Exporter — Parc immobilier', 'hebergement.parc.export', 'Parc immobilier', 'export', 'Autorise cette action dans le module Parc immobilier.', '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(110, 2, 'Créer — Utilisateurs', 'utilisateurs.create', 'Utilisateurs', 'create', 'Autorise cette action dans le module Utilisateurs.', '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(111, 2, 'Supprimer — Utilisateurs', 'utilisateurs.delete', 'Utilisateurs', 'delete', 'Autorise cette action dans le module Utilisateurs.', '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(112, 2, 'Modifier — Utilisateurs', 'utilisateurs.update', 'Utilisateurs', 'update', 'Autorise cette action dans le module Utilisateurs.', '2026-09-29 11:16:30', '2026-09-29 11:16:30'),
(113, 7, 'Consulter les informations personnelles — Campagnes et admissions', 'hebergement.admissions.personal', 'Campagnes et admissions', 'personal', 'Consulter les informations personnelles dans les vues partagées.', '2026-09-29 17:45:39', '2026-09-29 17:45:39'),
(114, 7, 'Consulter les pièces justificatives — Campagnes et admissions', 'hebergement.admissions.documents', 'Campagnes et admissions', 'documents', 'Consulter les pièces justificatives dans les vues partagées.', '2026-09-29 17:45:39', '2026-09-29 17:45:39'),
(115, 7, 'Consulter l’historique des demandes — Campagnes et admissions', 'hebergement.admissions.history', 'Campagnes et admissions', 'history', 'Consulter l’historique des demandes dans les vues partagées.', '2026-09-29 17:45:39', '2026-09-29 17:45:39'),
(116, 2, 'Consulter les informations personnelles — Utilisateurs', 'utilisateurs.personal', 'Utilisateurs', 'personal', 'Consulter les informations personnelles dans les vues partagées.', '2026-09-29 17:45:40', '2026-09-29 17:45:40');

-- --------------------------------------------------------

--
-- Structure de la table `regions`
--

CREATE TABLE `regions` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `regions`
--

INSERT INTO `regions` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'District autonome d\'Abidjan', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(2, 'Région de l\'Agnéby-Tiassa', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(3, 'Région du Bafing', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(4, 'Région du Bagoué', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(35, 'Région de Bélier', '2026-03-11 16:11:09', '2026-03-11 16:11:09'),
(6, 'Région du Béré', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(7, 'Région de Bounkani', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(8, 'Région du Cavally', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(9, 'Région du Folon', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(10, 'Région du Gbêkê', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(11, 'Région du Gbôklé', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(12, 'Région du Gôh', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(13, 'Région du Gontougou', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(14, 'Région du Grands Ponts', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(15, 'Région du Guémon', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(16, 'Région du Haut-Sassandra', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(17, 'Région de l\'Iffou', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(18, 'Région de l\'Indénié-Djuablin', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(19, 'Région du Kabadougou', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(20, 'Région de La Mé', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(21, 'Région du Lôh-Djiboua', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(22, 'Région de la Marahoué', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(23, 'Région du Hambol', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(24, 'Région du Moronou', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(25, 'Région de la Nawa', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(26, 'Région du N\'Zi', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(27, 'Région du Poro', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(28, 'Région de San-Pédro', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(29, 'Région du Sud-Comoé', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(30, 'Région du Tchologo', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(31, 'Région du Tonkpi', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(32, 'Région du Worodougou', '2024-12-24 05:59:05', '2024-12-24 05:59:05'),
(33, 'District autonome de Yamoussoukro', '2024-12-24 05:59:05', '2024-12-24 05:59:05');

-- --------------------------------------------------------

--
-- Structure de la table `reglement_interieur`
--

CREATE TABLE `reglement_interieur` (
  `id_reglement` varchar(150) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `version` varchar(20) NOT NULL,
  `type_entite` enum('campus','cite','batiment','chambre') NOT NULL,
  `entite_id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `date_application` date NOT NULL,
  `date_expiration` date DEFAULT NULL,
  `statut` enum('actif','inactif','en_revision') DEFAULT 'actif',
  `created_by` varchar(150) DEFAULT NULL,
  `deleted_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `approved_by` varchar(150) DEFAULT NULL,
  `date_approbation` date DEFAULT NULL,
  `fichier_url` varchar(500) DEFAULT NULL,
  `signature_requise` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structure de la table `residence_campagne`
--

CREATE TABLE `residence_campagne` (
  `id_residence_campagne` varchar(150) NOT NULL,
  `campagne_id` varchar(150) NOT NULL,
  `cite_id` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `residence_campagne`
--

INSERT INTO `residence_campagne` (`id_residence_campagne`, `campagne_id`, `cite_id`, `created_at`, `updated_at`) VALUES
('c43c5878-6288-47e1-b1ba-e2349fe7e880', '70191757-54fa-4d3c-b4d8-a946e00267c7', '67196Jo08pLGSJJUcyFvhBE5stV36MGUBznKq34StaxRgMVHM2K4Ph2RQTQzsY8l2cg77vk5676d9w5pgJEzT20tmwl7quDZtV5DFjeBxtYrGlHCDHMjIQXqAGfhvnvjObqswPiFwICv5AEFSMDkge', '2026-04-07 13:02:44', '2026-04-07 13:02:44'),
('3b5164fb-2297-4288-ab29-a114fb40c2e9', '6fcedc17-cb4a-48b6-877d-584ceca70c79', 'nry2gQ0o5LTTaHw6MlQwaTiOwMtBFdUFrBXSwO0hyBeKuil1wnaLnW8eV607Uu1cfxvESsOkwKdpNywxZdnYcSMQaMSZ99feTtI8NbgHZoOM25wQm5oTwv13WAsfYoCMi85RGIkxKW03TRl5sV6anq', '2026-03-25 14:51:04', '2026-03-25 14:51:04'),
('489b7506-4785-4003-ad49-d7f57f014fe0', '6fcedc17-cb4a-48b6-877d-584ceca70c79', '19hmeTjtqPpAlvPbxMRVu6mWthctMJhG4J2sMSMy2XITc4bkzdNOJBeuItTi1ZRdTECMf8BUxZyUb92EHMjddy4d6lCSlNeyrXqvCg35BfMXLB4yskmQAgZwBxVIBb0yoUrMRNlruu749nANuYn9iG', '2026-03-25 14:51:04', '2026-03-25 14:51:04'),
('5ebb161b-98e1-4eee-a662-b04853a0f32e', '8280dba3-5016-44f0-943e-fbff6ddf2f25', '67196Jo08pLGSJJUcyFvhBE5stV36MGUBznKq34StaxRgMVHM2K4Ph2RQTQzsY8l2cg77vk5676d9w5pgJEzT20tmwl7quDZtV5DFjeBxtYrGlHCDHMjIQXqAGfhvnvjObqswPiFwICv5AEFSMDkge', '2026-04-07 13:48:54', '2026-04-07 13:48:54'),
('b2f48327-e0d4-4226-8e7e-2f588f809562', '8280dba3-5016-44f0-943e-fbff6ddf2f25', '19hmeTjtqPpAlvPbxMRVu6mWthctMJhG4J2sMSMy2XITc4bkzdNOJBeuItTi1ZRdTECMf8BUxZyUb92EHMjddy4d6lCSlNeyrXqvCg35BfMXLB4yskmQAgZwBxVIBb0yoUrMRNlruu749nANuYn9iG', '2026-04-07 13:48:54', '2026-04-07 13:48:54'),
('9ff885e3-0e01-4b0d-830b-cdbfdc72227b', 'b0d3debd-1ca9-44cf-a9f1-56806b3bab36', 'Tmby582poUBE0MXKajunZvjWspB93QSjGjaoR5PDVQdgbKG5FleuNqZx8Imj0pDbmnQULOgzBqmkHzxolH9poQuswbsw6edmeRVvLcH6jgQX2Yi0PQyCAu8bvRcbhcr5E3u2oQXbDDVqkQ38YggoB5', '2026-05-05 08:09:16', '2026-05-05 08:09:16'),
('0fc9a419-1ada-48bb-8bd4-b5cdf3c84452', '67df0d77-1200-4f77-afe5-688696d8082a', 'U4wO3OHOIUi3mFSXDqvgun6xddO9BDaowFv6vZL6ZyQHOl0GdoO6bGJnrtUXqQzL4H3v1yh4wVJa51nDF2YEBGxBJO66AtBhPLTXoQzyLKvNQposjYzVrbwEJ4d91OvM8sWjFoEMbBaoWLpRCWFTIM', '2026-06-15 10:15:45', '2026-06-15 10:15:45'),
('854effcd-ccd9-4f75-8c18-728431f6aa4e', 'e747b32a-8441-41df-9393-cc9fc5b7b679', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '2026-06-19 15:33:24', '2026-06-19 15:33:24'),
('1ae948cb-5557-4c2f-bc67-9ce97e8768a0', '10163b0b-d75d-4097-8de9-d8ef57eb4ddb', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '2026-06-19 16:51:34', '2026-06-19 16:51:34'),
('f8635bea-18f3-4fdc-95f1-49e7a1c3945d', '9fc9e6b2-7571-4e67-9481-e6a551a13c87', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '2026-07-06 09:33:24', '2026-07-06 09:33:24'),
('387f7f29-73b8-4f96-8d8d-468253a676e0', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', '2026-08-03 15:56:34', '2026-08-03 15:56:34');

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(225) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Superadmin', '2025-12-30 15:56:35', '2025-12-30 15:56:05'),
(2, 'Sous-DAL', '2026-09-23 08:55:13', '2026-09-23 08:55:13'),
(3, 'Chef du service logement', '2026-09-23 08:55:14', '2026-09-23 08:55:14'),
(4, 'conciege', '2026-09-28 14:09:56', '2026-09-28 14:09:56');

-- --------------------------------------------------------

--
-- Structure de la table `role_permission`
--

CREATE TABLE `role_permission` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `role_permission`
--

INSERT INTO `role_permission` (`role_id`, `permission_id`, `created_at`, `updated_at`) VALUES
(1, 12, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 13, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 14, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 15, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 16, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 17, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 18, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 19, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 20, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 21, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 22, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 23, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 24, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 25, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 26, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 27, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 28, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 29, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 30, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 31, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 32, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 33, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 34, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 35, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 36, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 37, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 38, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 39, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 40, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 41, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 42, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 43, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 44, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 45, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 46, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 47, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 48, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 49, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 50, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 51, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 52, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 53, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 54, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 55, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 56, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 57, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 58, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 59, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 60, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 61, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 62, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 63, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 64, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 65, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 66, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 67, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 68, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 69, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 70, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 71, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 72, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 73, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 74, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 75, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 76, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 77, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 78, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 79, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 80, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 81, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 82, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 83, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 84, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 85, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 86, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 87, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 88, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 89, '2026-08-11 12:09:51', '2026-08-11 12:09:51'),
(1, 90, '2026-08-18 10:14:01', '2026-08-18 10:14:01'),
(1, 91, '2026-09-19 20:13:51', '2026-09-19 20:13:51'),
(1, 92, '2026-09-20 11:00:09', '2026-09-20 11:00:09'),
(1, 93, '2026-09-20 11:00:09', '2026-09-20 11:00:09'),
(1, 94, '2026-09-28 14:09:18', '2026-09-28 14:09:18'),
(1, 95, '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(1, 96, '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(1, 97, '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(1, 98, '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(1, 99, '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(1, 100, '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(1, 101, '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(1, 102, '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(1, 103, '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(1, 104, '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(1, 105, '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(1, 106, '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(1, 107, '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(1, 108, '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(1, 109, '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(1, 110, '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(1, 111, '2026-09-29 11:16:30', '2026-09-29 11:16:30'),
(1, 112, '2026-09-29 11:16:30', '2026-09-29 11:16:30'),
(1, 113, '2026-09-29 17:45:39', '2026-09-29 17:45:39'),
(1, 114, '2026-09-29 17:45:39', '2026-09-29 17:45:39'),
(1, 115, '2026-09-29 17:45:40', '2026-09-29 17:45:40'),
(1, 116, '2026-09-29 17:45:40', '2026-09-29 17:45:40'),
(2, 94, '2026-09-23 08:55:14', '2026-09-23 08:55:14'),
(3, 94, '2026-09-23 08:55:14', '2026-09-23 08:55:14');

-- --------------------------------------------------------

--
-- Structure de la table `sanctions`
--

CREATE TABLE `sanctions` (
  `id_sanction` char(36) NOT NULL,
  `numero_sanction` varchar(40) NOT NULL,
  `scope_type` varchar(20) NOT NULL DEFAULT 'etudiant',
  `etudiant_id` varchar(150) DEFAULT NULL,
  `cite_id` varchar(150) DEFAULT NULL,
  `batiment_id` varchar(150) DEFAULT NULL,
  `chambre_id` varchar(150) DEFAULT NULL,
  `type` varchar(30) NOT NULL,
  `motif` text NOT NULL,
  `faits_reproches` text DEFAULT NULL,
  `date_decision` date NOT NULL,
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `statut` enum('active','levee','annulee') NOT NULL DEFAULT 'active',
  `commentaire` text DEFAULT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `rapport_id` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sanctions`
--

INSERT INTO `sanctions` (`id_sanction`, `numero_sanction`, `scope_type`, `etudiant_id`, `cite_id`, `batiment_id`, `chambre_id`, `type`, `motif`, `faits_reproches`, `date_decision`, `date_debut`, `date_fin`, `statut`, `commentaire`, `created_by`, `updated_by`, `created_at`, `updated_at`, `rapport_id`) VALUES
('40348a62-b83a-461c-b789-c16881f7a387', 'SAN-20260810-TS7XH', 'etudiant', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', 'c1123cbd-658c-483a-a246-dc6726039649', '1edb4ef8-3b43-4287-88c4-c1e1959e8ffa', 'blame', 'tetgdhsjzxcmn/,dmxcz/.,', 'sz;kldm,./dzxcm', '2026-08-10', NULL, NULL, 'active', 'vcghds', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-08-10 22:01:31', '2026-08-10 22:01:31', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `sanction_histories`
--

CREATE TABLE `sanction_histories` (
  `id_historique` char(36) NOT NULL,
  `sanction_id` char(36) NOT NULL,
  `action` varchar(60) NOT NULL,
  `ancien_statut` varchar(30) DEFAULT NULL,
  `nouveau_statut` varchar(30) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `auteur_id` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sanction_histories`
--

INSERT INTO `sanction_histories` (`id_historique`, `sanction_id`, `action`, `ancien_statut`, `nouveau_statut`, `details`, `auteur_id`, `created_at`) VALUES
('65ddd924-eeaa-4673-9f80-f200b859deb3', '40348a62-b83a-461c-b789-c16881f7a387', 'creation', NULL, 'active', 'Sanction blame enregistrée.', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-10 22:01:31');

-- --------------------------------------------------------

--
-- Structure de la table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(225) NOT NULL,
  `user_agent` text NOT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sms_pros`
--

CREATE TABLE `sms_pros` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_compagnie` int(11) NOT NULL,
  `sender_name` varchar(191) NOT NULL,
  `authorization_bearer` varchar(191) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `api_path` varchar(191) NOT NULL DEFAULT 'https://ocl.ci'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sms_pros`
--

INSERT INTO `sms_pros` (`id`, `id_compagnie`, `sender_name`, `authorization_bearer`, `active`, `api_path`) VALUES
(1, 0, 'AGAT', 'Bearer 4|eicvRsC6T4BxKLHDeZ5Yfihk1bfeqrWyaoxlPDvg573ecd94 ', 1, 'https://vs.smsvisio.com/api/v3/sms/send');

-- --------------------------------------------------------

--
-- Structure de la table `student_api_tokens`
--

CREATE TABLE `student_api_tokens` (
  `id` char(36) NOT NULL,
  `etudiant_id` varchar(150) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT 'mobile',
  `token_hash` varchar(64) NOT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `student_contract_approvals`
--

CREATE TABLE `student_contract_approvals` (
  `id` char(36) NOT NULL,
  `assignment_id` varchar(255) NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `document_html` longtext NOT NULL,
  `document_hash` varchar(64) NOT NULL,
  `consent_text` text DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `student_contract_approvals`
--

INSERT INTO `student_contract_approvals` (`id`, `assignment_id`, `student_id`, `student_name`, `document_html`, `document_hash`, `consent_text`, `approved_at`, `created_at`, `updated_at`) VALUES
('5a0c0106-5c11-4b1a-aff8-b6a7e7e470c7', 'bdc6e064-477e-4bbf-97a3-233965e42a50', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'John Doe', 'resources/views/admin/contrats/pdf.blade.php\n        <!DOCTYPE html>\n<html lang=\"fr\">\n<head>\n    <meta charset=\"UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <title>Contrat de location - CONTRAT-20260923-23000000761</title>\n    <style>\n        /* ============================================================\n           STYLES POUR LE PDF - DomPDF compatible\n           ============================================================ */\n        * {\n            margin: 0;\n            padding: 0;\n            box-sizing: border-box;\n        }\n\n        body {\n            font-family: \'DejaVu Sans\', \'Arial\', sans-serif;\n            font-size: 11px;\n            line-height: 1.6;\n            color: #1a1a1a;\n            padding: 30px 40px;\n            background: #ffffff;\n        }\n\n        .page {\n            max-width: 1000px;\n            margin: 0 auto;\n        }\n\n        /* ============================================================\n           EN-TÊTE\n           ============================================================ */\n        .header {\n            text-align: center;\n            border-bottom: 3px double #1a237e;\n            padding-bottom: 20px;\n            margin-top: -100px;\n            position: relative;\n\n        }\n\n        .header .logo-text {\n            font-size: 22px;\n            font-weight: 800;\n            color: #1a237e;\n            letter-spacing: 3px;\n            text-transform: uppercase;\n        }\n\n        .header .logo-text small {\n            font-weight: 300;\n            font-size: 12px;\n            color: #555;\n            letter-spacing: 1px;\n            display: block;\n        }\n\n        .header h1 {\n            font-size: 20px;\n            font-weight: 700;\n            color: #1a237e;\n            text-transform: uppercase;\n            letter-spacing: 2px;\n            margin: 8px 0 4px 0;\n        }\n\n        .header .subtitle {\n            font-size: 12px;\n            color: #666;\n            font-style: italic;\n        }\n\n        .header .ref-badge {\n            position: absolute;\n            top: 0;\n            right: 0;\n            background: #1a237e;\n            color: white;\n            padding: 4px 14px;\n            border-radius: 15px;\n            font-size: 10px;\n            font-weight: 600;\n            letter-spacing: 0.5px;\n        }\n\n        /* ============================================================\n           TITRES DE SECTIONS\n           ============================================================ */\n        .section-title {\n            font-size: 14px;\n            font-weight: 700;\n            color: #1a237e;\n            margin: 20px 0 12px 0;\n            padding-bottom: 6px;\n            border-bottom: 2px solid #e8eaf6;\n            display: flex;\n            align-items: center;\n            gap: 10px;\n        }\n\n        .section-title .badge {\n            background: #1a237e;\n            color: white;\n            font-size: 10px;\n            padding: 1px 10px;\n            border-radius: 10px;\n            font-weight: 500;\n        }\n\n        /* ============================================================\n           TABLEAU RÉCAPITULATIF\n           ============================================================ */\n        .summary-grid {\n            background: #f8f9fa;\n            padding: 15px 20px;\n            border-radius: 8px;\n            border-left: 4px solid #1a237e;\n            margin-bottom: 20px;\n        }\n\n        .summary-row {\n            display: table;\n            width: 100%;\n            padding: 4px 0;\n            border-bottom: 1px dashed #e0e0e0;\n        }\n\n        .summary-row:last-child {\n            border-bottom: none;\n        }\n\n        .summary-row .label {\n            display: table-cell;\n            width: 40%;\n            font-weight: 500;\n            color: #555;\n            font-size: 11px;\n            padding: 3px 0;\n        }\n\n        .summary-row .value {\n            display: table-cell;\n            width: 60%;\n            font-weight: 600;\n            color: #1a1a1a;\n            font-size: 11px;\n            padding: 3px 0;\n        }\n\n        .summary-row .value.highlight {\n            color: #1a237e;\n        }\n\n        /* ============================================================\n           ARTICLES\n           ============================================================ */\n        .article {\n            margin-bottom: 16px;\n        }\n\n        .article-header {\n            margin-bottom: 4px;\n        }\n\n        .article-number {\n            display: inline-block;\n            background: #1a237e;\n            color: white;\n            font-weight: 700;\n            font-size: 10px;\n            padding: 1px 10px;\n            border-radius: 3px;\n            margin-right: 6px;\n        }\n\n        .article-title {\n            font-weight: 700;\n            font-size: 13px;\n            color: #1a237e;\n        }\n\n        .article-content {\n            padding-left: 28px;\n            font-size: 11.5px;\n            line-height: 1.7;\n            color: #1a1a1a;\n        }\n\n        .article-content ul,\n        .article-content ol {\n            padding-left: 20px;\n            margin: 5px 0;\n        }\n\n        .article-content ul li,\n        .article-content ol li {\n            margin-bottom: 3px;\n        }\n\n        .article-content .highlight {\n            background: #fff3cd;\n            padding: 0 4px;\n            border-radius: 2px;\n            font-weight: 600;\n        }\n\n        /* ============================================================\n           RÈGLEMENT INTÉRIEUR\n           ============================================================ */\n        .rules-grid {\n            display: table;\n            width: 100%;\n            margin-top: 8px;\n            border-collapse: collapse;\n        }\n\n        .rules-grid .rule-row {\n            display: table-row;\n        }\n\n        .rules-grid .rule-cell {\n            display: table-cell;\n            padding: 6px 12px;\n            border-bottom: 1px solid #f0f0f0;\n            vertical-align: top;\n        }\n\n        .rules-grid .rule-label {\n            font-weight: 600;\n            font-size: 11px;\n            color: #1a237e;\n            width: 30%;\n        }\n\n        .rules-grid .rule-value {\n            font-size: 11px;\n            color: #1a1a1a;\n            width: 70%;\n        }\n\n        .rule-subsection {\n            margin-top: 10px;\n            padding-left: 10px;\n            border-left: 2px solid #e8eaf6;\n        }\n\n        .rule-subsection-title {\n            font-weight: 600;\n            font-size: 12px;\n            color: #1a237e;\n            margin-bottom: 4px;\n        }\n\n        .rule-list {\n            padding-left: 20px;\n            margin: 4px 0 8px 0;\n        }\n\n        .rule-list li {\n            margin-bottom: 3px;\n            font-size: 11px;\n        }\n\n        /* ============================================================\n           SIGNATURES\n           ============================================================ */\n        .signature-section {\n            margin-top: 30px;\n            padding-top: 20px;\n            border-top: 2px solid #e8eaf6;\n        }\n\n        .signature-grid {\n            display: table;\n            width: 100%;\n            margin-top: 20px;\n        }\n\n        .signature-box {\n            display: table-cell;\n            width: 50%;\n            padding: 0 20px;\n            text-align: center;\n            vertical-align: bottom;\n        }\n\n        .signature-box .label {\n            font-weight: 700;\n            font-size: 12px;\n            color: #1a237e;\n            margin-bottom: 5px;\n        }\n\n        .signature-box .line {\n            border-bottom: 1px solid #1a1a1a;\n            width: 80%;\n            margin: 10px auto 5px auto;\n            height: 35px;\n        }\n\n        .signature-box .note {\n            font-size: 9px;\n            color: #777;\n            margin-top: 4px;\n        }\n\n        .signature-box .date-line {\n            margin-top: 15px;\n            font-size: 10px;\n            color: #555;\n        }\n\n        /* ============================================================\n           PIED DE PAGE\n           ============================================================ */\n        .footer {\n            margin-top: 30px;\n            padding-top: 15px;\n            border-top: 1px solid #ddd;\n            text-align: center;\n            font-size: 9px;\n            color: #888;\n        }\n\n        /* ============================================================\n           UTILITAIRES\n           ============================================================ */\n        .text-center { text-align: center; }\n        .mt-10 { margin-top: 10px; }\n        .mt-15 { margin-top: 15px; }\n        .mt-20 { margin-top: 20px; }\n        .mb-10 { margin-bottom: 10px; }\n        .mb-15 { margin-bottom: 15px; }\n        .page-break { page-break-before: always; }\n\n        .field-placeholder {\n            color: #1a237e;\n            background: #f0f4ff;\n            padding: 0 4px;\n            border-radius: 2px;\n            font-weight: 600;\n        }\n\n        .empty-field {\n            color: #c62828;\n            font-style: italic;\n            font-size: 10px;\n        }\n\n        /* ============================================================\n           IMPRESSION\n           ============================================================ */\n        @media print {\n            body {\n                padding: 20px 30px;\n            }\n            .no-print {\n                display: none !important;\n            }\n            .page-break {\n                page-break-before: always;\n            }\n        }\n\n        /* ============================================================\n           RESPONSIVE POUR L\'ÉCRAN (optionnel)\n           ============================================================ */\n        @media (max-width: 768px) {\n            body {\n                padding: 15px;\n            }\n            .summary-grid {\n                padding: 10px 15px;\n            }\n            .signature-box {\n                display: block;\n                width: 100%;\n                margin-bottom: 25px;\n            }\n            .signature-grid {\n                display: block;\n            }\n            .rules-grid .rule-cell {\n                display: block;\n                width: 100%;\n            }\n            .rules-grid .rule-row {\n                display: block;\n                margin-bottom: 8px;\n            }\n            .header-left {\n                flex-direction: column;\n                justify-content: center;\n            }\n        }\n        .header-left {\n            flex: 1;\n            text-align: left;\n            display: flex;\n            align-items: center;\n            gap: 15px;\n        }\n        .logo-img {\n            max-height: 70px;\n            max-width: 120px;\n            height: auto;\n            width: auto;\n            display: block;\n        }\n    </style>\n</head>\n<body>\n<div class=\"page\">\n\n    <!-- ============================================================\n    EN-TÊTE\n    ============================================================ -->\n    <div class=\"header-left\">\n                    <img src=\"https://croudedaloa.soumafe.ci//admin/assets/images/logos/6a4278c138bee2.69473646.jpg\" alt=\"Logo\" class=\"logo-img\">\n        \n    </div>\n    <div class=\"header\">\n        <div class=\"logo-text\">\n            Crou de daloa\n            <small>Services Universitaires</small>\n        </div>\n        <h1>Contrat de location de chambre</h1>\n        <div class=\"subtitle\">Modèle standard — À compléter avant signature</div>\n\n    </div>\n\n    <!-- ============================================================\n    PRÉAMBULE\n    ============================================================ -->\n    <p style=\"margin-bottom: 1px;\">\n        Entre les soussignés :\n    </p>\n\n    <p style=\"margin-bottom: 1px;\">\n        <strong>Le Bailleur</strong>,\n        <span class=\"field-placeholder\">Crou de daloa</span>,\n        représenté par <span class=\"field-placeholder\">Le Directeur Général</span>,\n        demeurant dans la région de\n        <span class=\"field-placeholder\">Région du Haut-Sassandra</span>,\n        précisément dans la ville de\n        <span class=\"field-placeholder\">Daloa</span>,\n        ci-après dénommé(e) <strong>« le Bailleur »</strong>,\n    </p>\n\n    <p style=\"margin-bottom: 10px;\">\n        <strong>Et</strong>\n    </p>\n\n    <p style=\"margin-bottom: 15px;\">\n        <strong>L\'etudiant</strong>,\n        <span class=\"field-placeholder\">John Doe</span>,\n        né(e) le <span class=\"field-placeholder\">29-07-2026</span>,\n        matricule <span class=\"field-placeholder\">23000000761</span>,\n        demeurant actuellement a la <span class=\"field-placeholder\">cite kalilou</span>\n        dans le batiment <span class=\"field-placeholder\">BATIMENT-A</span>,\n        ci-après dénommé(e) <strong>« le Locataire »</strong>,\n    </p>\n\n    <p style=\"margin-top: 15px; font-weight: 600;\">\n        Il a été convenu ce qui suit :\n    </p>\n\n    <!-- ============================================================\n    RÉCAPITULATIF DES CONDITIONS\n    ============================================================ -->\n    <div class=\"section-title\">\n        Récapitulatif des conditions\n        <span class=\"badge\">RÉSUMÉ</span>\n    </div>\n\n    <div class=\"summary-grid\">\n        <div class=\"summary-row\">\n            <span class=\"label\">Locataire</span>\n            <span class=\"value highlight\">John Doe</span>\n        </div>\n        <div class=\"summary-row\">\n            <span class=\"label\">Chambre louée</span>\n            <span class=\"value highlight\">CH-04</span>\n        </div>\n        <div class=\"summary-row\">\n            <span class=\"label\">Surface</span>\n            <span class=\"value\">0.00</span>\n        </div>\n        <div class=\"summary-row\">\n            <span class=\"label\">Adresse du logement</span>\n            <span class=\"value\">cite kalilou</span>\n        </div>\n        <div class=\"summary-row\">\n            <span class=\"label\">Date d\'entrée</span>\n            <span class=\"value\">03/08/2026</span>\n        </div>\n        <div class=\"summary-row\">\n            <span class=\"label\">Durée du contrat</span>\n            <span class=\"value\">12 mois renouvelable</span>\n        </div>\n        <div class=\"summary-row\">\n            <span class=\"label\">Loyer mensuel</span>\n            <span class=\"value highlight\">0 FCFA</span>\n        </div>\n        <div class=\"summary-row\">\n            <span class=\"label\">Charges comprises</span>\n            <span class=\"value\">Eau, Électricité, Internet, Entretien</span>\n        </div>\n        <div class=\"summary-row\">\n            <span class=\"label\">Dépôt de garantie</span>\n            <span class=\"value highlight\">50 000 FCFA</span>\n        </div>\n        <div class=\"summary-row\">\n            <span class=\"label\">Jour de paiement</span>\n            <span class=\"value\">Le 5 de chaque mois</span>\n        </div>\n    </div>\n\n    <!-- ============================================================\n    ARTICLE 1\n    ============================================================ -->\n    <div class=\"article\">\n        <div class=\"article-header\">\n            <span class=\"article-number\">1</span>\n            <span class=\"article-title\">Objet du contrat</span>\n        </div>\n        <div class=\"article-content\">\n            <p>\n                Le Bailleur loue au Locataire, qui accepte, une chambre\n                <span class=\"highlight\">Double</span>\n                désignée <span class=\"highlight\">CH-04</span>,\n                d\'une surface d\'environ <span class=\"highlight\">0.00</span> m²,\n                située à l\'adresse <span class=\"highlight\">cite kalilou</span>,\n                ainsi que l\'usage des parties communes (cuisine, salle de bain, salon) partagées avec les autres occupants.\n            </p>\n        </div>\n    </div>\n\n    <!-- ============================================================\n    ARTICLE 2\n    ============================================================ -->\n    <div class=\"article\">\n        <div class=\"article-header\">\n            <span class=\"article-number\">2</span>\n            <span class=\"article-title\">Durée du contrat</span>\n        </div>\n        <div class=\"article-content\">\n            <p>\n                Le présent contrat est conclu pour une durée de\n                <span class=\"highlight\">12 mois renouvelable</span>,\n                à compter du <span class=\"highlight\">03/08/2026</span>.\n                Il pourra être renouvelé par accord exprès des deux parties ou résilié dans les conditions prévues à l\'article 8.\n            </p>\n        </div>\n    </div>\n\n    <!-- ============================================================\n    ARTICLE 3\n    ============================================================ -->\n    <div class=\"article\">\n        <div class=\"article-header\">\n            <span class=\"article-number\">3</span>\n            <span class=\"article-title\">Loyer et charges</span>\n        </div>\n        <div class=\"article-content\">\n            <ol>\n                <li>\n                    Le Locataire s\'engage à verser un loyer mensuel de\n                    <span class=\"highlight\">0 FCFA</span>,\n                    payable d\'avance le <span class=\"highlight\">Le 5 de chaque mois</span>\n                    de chaque mois, par <span class=\"highlight\">Virement bancaire / Mobile Money</span>.\n                </li>\n                <li>\n                    Les charges suivantes sont <span class=\"highlight\">incluses</span>\n                    dans le loyer : <span class=\"highlight\">[DÉTAIL DES CHARGES]</span>.\n                </li>\n                <li>\n                    Tout retard de paiement supérieur à <span class=\"highlight\">15</span> jours\n                    pourra entraîner l\'application de pénalités ou la résiliation du contrat.\n                </li>\n            </ol>\n        </div>\n    </div>\n\n    <!-- ============================================================\n    ARTICLE 4\n    ============================================================ -->\n    <div class=\"article\">\n        <div class=\"article-header\">\n            <span class=\"article-number\">4</span>\n            <span class=\"article-title\">Dépôt de garantie</span>\n        </div>\n        <div class=\"article-content\">\n            <p>\n                À la signature du présent contrat, le Locataire verse un dépôt de garantie d\'un montant de\n                <span class=\"highlight\">50 000 FCFA</span>,\n                destiné à couvrir d\'éventuels manquements aux obligations locatives.\n                Ce dépôt sera restitué dans un délai de <span class=\"highlight\">30</span> jours\n                après la restitution des lieux, déduction faite des sommes dues le cas échéant.\n            </p>\n        </div>\n    </div>\n\n    <!-- ============================================================\n    ARTICLE 5\n    ============================================================ -->\n    <div class=\"article\">\n        <div class=\"article-header\">\n            <span class=\"article-number\">5</span>\n            <span class=\"article-title\">Obligations du Locataire</span>\n        </div>\n        <div class=\"article-content\">\n            <ul>\n                <li>Occuper les lieux paisiblement et y habiter personnellement ;</li>\n                <li>Payer le loyer et les charges aux échéances convenues ;</li>\n                <li>Entretenir la chambre et signaler sans délai toute dégradation ou panne ;</li>\n                <li>Respecter le règlement intérieur annexé au présent contrat (Article 7) ;</li>\n                <li>Ne pas sous-louer ni céder le contrat sans accord écrit du Bailleur.</li>\n            </ul>\n        </div>\n    </div>\n\n    <!-- ============================================================\n    ARTICLE 6\n    ============================================================ -->\n    <div class=\"article\">\n        <div class=\"article-header\">\n            <span class=\"article-number\">6</span>\n            <span class=\"article-title\">Obligations du Bailleur</span>\n        </div>\n        <div class=\"article-content\">\n            <ul>\n                <li>Délivrer une chambre décente, en bon état d\'usage et de réparation ;</li>\n                <li>Assurer la jouissance paisible des lieux loués ;</li>\n                <li>Effectuer les réparations autres que locatives ;</li>\n                <li>Restituer le dépôt de garantie dans les conditions prévues à l\'article 4.</li>\n            </ul>\n        </div>\n    </div>\n\n    <!-- ============================================================\n    ARTICLE 7 - RÈGLEMENT INTÉRIEUR\n    ============================================================ -->\n    <div class=\"article\">\n        <div class=\"article-header\">\n            <span class=\"article-number\">7</span>\n            <span class=\"article-title\">Règlement intérieur (annexe)</span>\n        </div>\n        <div class=\"article-content\">\n            <p style=\"margin-bottom: 8px;\">\n                Le présent règlement fait partie intégrante du contrat. Le non-respect de ses dispositions peut constituer un motif de résiliation.\n            </p>\n\n            <!-- 7.1 Vie collective -->\n            <div class=\"rule-subsection\">\n                <div class=\"rule-subsection-title\">7.1 Vie collective et nuisances</div>\n                <ul class=\"rule-list\">\n                    <li>Respecter le calme entre <span class=\"highlight\">22h</span>\n                        et <span class=\"highlight\">7h</span> ;</li>\n                    <li>Limiter le bruit (musique, appareils, visites) en toutes circonstances ;</li>\n                    <li>Maintenir la propreté des parties communes après chaque usage.</li>\n                </ul>\n            </div>\n\n            <!-- 7.2 Visites et invités -->\n            <div class=\"rule-subsection\">\n                <div class=\"rule-subsection-title\">7.2 Visites et invités</div>\n                <ul class=\"rule-list\">\n                    <li>Les visites sont autorisées de <span class=\"highlight\">8h à 22h</span>\n                        sous réserve d\'en informer le Bailleur ;</li>\n                    <li>Les nuitées d\'invités sont limitées à <span class=\"highlight\">3</span>\n                        par mois, sauf accord contraire.</li>\n                </ul>\n            </div>\n\n            <!-- 7.3 Usage des équipements -->\n            <div class=\"rule-subsection\">\n                <div class=\"rule-subsection-title\">7.3 Usage des équipements</div>\n                <ul class=\"rule-list\">\n                    <li>Usage de la cuisine, du réfrigérateur et des équipements communs :\n                        <span class=\"highlight\">Usage partagé, nettoyage après usage</span> ;</li>\n                    <li>Connexion internet/Wi-Fi :\n                        <span class=\"highlight\">Wi-Fi gratuit disponible 24h/24</span> ;</li>\n                    <li>Interdiction de fumer dans les lieux :\n                        <span class=\"highlight\">NON, sauf zones extérieures autorisées</span>.</li>\n                </ul>\n            </div>\n\n            <!-- 7.4 Sécurité -->\n            <div class=\"rule-subsection\">\n                <div class=\"rule-subsection-title\">7.4 Sécurité</div>\n                <ul class=\"rule-list\">\n                    <li>Ne pas dupliquer les clés sans autorisation du Bailleur ;</li>\n                    <li>Signaler immédiatement toute anomalie (fuite, panne électrique, intrusion) ;</li>\n                    <li>Respecter les consignes de sécurité incendie affichées dans le logement.</li>\n                </ul>\n            </div>\n\n            <!-- 7.5 Animaux -->\n            <div class=\"rule-subsection\">\n                <div class=\"rule-subsection-title\">7.5 Animaux</div>\n                <p style=\"font-size: 11px;\">\n                    Les animaux domestiques sont <span class=\"highlight\">NON AUTORISÉS</span>,\n                    sous réserve, le cas échéant, des conditions suivantes :\n                    <span class=\"highlight\">Aucune dérogation possible</span>.\n                </p>\n            </div>\n\n            <!-- 7.6 Dispositions diverses -->\n            <div class=\"rule-subsection\">\n                <div class=\"rule-subsection-title\">7.6 Dispositions diverses</div>\n                <p style=\"font-size: 11px;\">\n                    <span class=\"highlight\">Parking à disposition sur demande / Buanderie au sous-sol / Poubelles à sortir les mardis et vendredis</span>\n                </p>\n            </div>\n        </div>\n    </div>\n\n    <!-- ============================================================\n    ARTICLE 8\n    ============================================================ -->\n    <div class=\"article\">\n        <div class=\"article-header\">\n            <span class=\"article-number\">8</span>\n            <span class=\"article-title\">Résiliation</span>\n        </div>\n        <div class=\"article-content\">\n            <ol>\n                <li>\n                    Le Locataire peut résilier le contrat à tout moment moyennant un préavis de\n                    <span class=\"highlight\">1 mois</span>,\n                    notifié par <span class=\"highlight\">Lettre recommandée avec accusé de réception</span>.\n                </li>\n                <li>\n                    Le Bailleur peut résilier le contrat en cas de manquement grave (impayés, troubles de voisinage,\n                    non-respect du règlement intérieur) après mise en demeure restée sans effet pendant\n                    <span class=\"highlight\">15 jours</span>.\n                </li>\n            </ol>\n        </div>\n    </div>\n\n    <!-- ============================================================\n    ARTICLE 9\n    ============================================================ -->\n    <div class=\"article\">\n        <div class=\"article-header\">\n            <span class=\"article-number\">9</span>\n            <span class=\"article-title\">Litiges</span>\n        </div>\n        <div class=\"article-content\">\n            <p>\n                En cas de différend, les parties s\'efforceront de trouver une solution amiable.\n                À défaut, le litige sera porté devant\n                <span class=\"highlight\">le tribunal de première instance de </span>.\n            </p>\n        </div>\n    </div>\n\n    <!-- ============================================================\n    ARTICLE 10\n    ============================================================ -->\n    <div class=\"article\">\n        <div class=\"article-header\">\n            <span class=\"article-number\">10</span>\n            <span class=\"article-title\">Dispositions complémentaires</span>\n        </div>\n        <div class=\"article-content\">\n            <p>\n                <span class=\"highlight\">Le locataire s&#039;engage à respecter les règles de vie collective et à entretenir les espaces communs.</span>\n            </p>\n        </div>\n    </div>\n\n    <!-- ============================================================\n    SIGNATURES\n    ============================================================ -->\n    <div class=\"signature-section\">\n        <p style=\"text-align: center; margin-bottom: 15px;\">\n            Fait à <span class=\"highlight\">Abidjan</span>,\n            le <span class=\"highlight\">23/09/2026</span>,\n            en deux exemplaires originaux.\n        </p>\n\n        <div class=\"signature-grid\">\n            <div class=\"signature-box\">\n                <div class=\"label\">Le Crou</div>\n                <div class=\"line\">&nbsp;</div>\n                <div class=\"note\">(nom, signature et cachet)</div>\n                <div class=\"date-line\">Lu et approuvé</div>\n            </div>\n            <div class=\"signature-box\">\n                <div class=\"label\">L\'étudiant</div>\n                <div class=\"line\">&nbsp;</div>\n                <div class=\"note\">(nom et signature)</div>\n                <div class=\"date-line\">Lu et approuvé, précédé de la mention « Bon pour accord »</div>\n            </div>\n        </div>\n    </div>\n\n    <!-- ============================================================\n    PIED DE PAGE\n    ============================================================ -->\n    <div class=\"footer\">\n        Contrat de location de chambre — Crou de daloa —\n        Généré le 23/09/2026 à 16:12\n        <br>\n        <span style=\"font-size: 8px; color: #aaa;\">\n            Document confidentiel — Ne pas diffuser\n        </span>\n    </div>\n\n</div>\n</body>\n</html>\n', '7c95beae66244ff656f40ea9677b6d0e834dd103c70bbbd2e76927312a6b8e42', 'Je confirme avoir lu le contrat de logement présenté et en approuver les conditions.', '2026-09-23 16:12:58', '2026-09-23 11:49:46', '2026-09-23 16:12:58');

-- --------------------------------------------------------

--
-- Structure de la table `type_chambre`
--

CREATE TABLE `type_chambre` (
  `id_type_chambre` char(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `montant` double DEFAULT 0,
  `caution` int(11) NOT NULL DEFAULT 0,
  `avance` int(11) NOT NULL DEFAULT 0,
  `superficie` int(11) NOT NULL DEFAULT 0,
  `equipement` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `type_chambre`
--

INSERT INTO `type_chambre` (`id_type_chambre`, `name`, `description`, `montant`, `caution`, `avance`, `superficie`, `equipement`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
('8fce98d7-fde1-11f0-82f8-fa163e85dd73', 'Individuelle', '—', 12000, 0, 6, 0, '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Climatiseur\":\"Climatiseur\"}', 1, NULL, NULL, '2026-01-19 19:33:27', '2026-07-21 14:50:09'),
('bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'Double', 'Pour deux personnes', 6000, 0, 6, 0, '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 1, NULL, NULL, '2026-01-30 14:15:50', '2026-07-21 14:49:57'),
('3a1a5998-d79e-44ba-82dc-bc4f9ae0823a', 'Triple', 'Pour trois personnes, il y aura trois lits', 4000, 0, 6, 0, '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Climatiseur\":\"Climatiseur\",\"Ventilateur\":\"Ventilateur\"}', 1, NULL, NULL, '2026-01-30 14:16:14', '2026-07-21 14:50:20');

-- --------------------------------------------------------

--
-- Structure de la table `type_cite`
--

CREATE TABLE `type_cite` (
  `id_type_cite` char(36) NOT NULL,
  `code_type` varchar(50) DEFAULT NULL,
  `code` varchar(150) DEFAULT NULL,
  `name` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `categorie` enum('universitaire','privee','mixte','residence') NOT NULL,
  `public_cible` enum('etudiant','enseignant','personnel','tous') DEFAULT 'etudiant',
  `standard_equipements` text DEFAULT NULL,
  `services_inclus` text DEFAULT NULL,
  `conditions_acces` text DEFAULT NULL,
  `duree_min_sejour` int(11) DEFAULT NULL,
  `duree_max_sejour` int(11) DEFAULT NULL,
  `age_min` int(11) DEFAULT 0,
  `age_max` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `type_cite`
--

INSERT INTO `type_cite` (`id_type_cite`, `code_type`, `code`, `name`, `description`, `categorie`, `public_cible`, `standard_equipements`, `services_inclus`, `conditions_acces`, `duree_min_sejour`, `duree_max_sejour`, `age_min`, `age_max`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
('db724325-fde0-11f0-82f8-fa163e85dd73', NULL, NULL, 'Mixte', NULL, 'universitaire', 'etudiant', NULL, NULL, NULL, NULL, NULL, 0, 0, 1, NULL, NULL, '2026-01-30 09:57:42', '2026-01-30 13:37:44'),
('db72456e-fde0-11f0-82f8-fa163e85dd73', NULL, NULL, 'Uniquement Homme', NULL, 'universitaire', 'etudiant', NULL, NULL, NULL, NULL, NULL, 0, 0, 1, NULL, NULL, '2026-01-30 09:57:52', '2026-01-30 13:37:44'),
('db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, NULL, 'Uniquement Femme', NULL, 'universitaire', 'etudiant', NULL, NULL, NULL, NULL, NULL, 0, 0, 1, NULL, NULL, '2026-01-30 09:57:59', '2026-01-30 13:37:44');

-- --------------------------------------------------------

--
-- Structure de la table `type_equipement`
--

CREATE TABLE `type_equipement` (
  `id_type_equipement` char(36) NOT NULL,
  `code_type` varchar(150) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `categorie` enum('mobilier','electronique','cuisine','sdb','autre') DEFAULT NULL,
  `description` text DEFAULT NULL,
  `marque` varchar(100) DEFAULT NULL,
  `modele` varchar(100) DEFAULT NULL,
  `duree_vie_estimee` int(11) DEFAULT NULL,
  `cout_moyen` decimal(10,2) DEFAULT 0.00,
  `cout_maintenance` decimal(10,2) DEFAULT 0.00,
  `est_consommable` tinyint(1) DEFAULT 0,
  `unite_mesure` varchar(20) DEFAULT NULL,
  `specifications_techniques` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `type_equipement`
--

INSERT INTO `type_equipement` (`id_type_equipement`, `code_type`, `name`, `categorie`, `description`, `marque`, `modele`, `duree_vie_estimee`, `cout_moyen`, `cout_maintenance`, `est_consommable`, `unite_mesure`, `specifications_techniques`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
('78345357-fdce-11f0-82f8-fa163e85dd73', NULL, 'Lit', NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0, NULL, NULL, 1, NULL, NULL, '2026-01-30 11:15:03', '2026-01-30 11:26:07'),
('783454c5-fdce-11f0-82f8-fa163e85dd73', NULL, 'Table', NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0, NULL, NULL, 1, NULL, NULL, '2026-01-30 11:14:54', '2026-01-30 11:26:07'),
('78345544-fdce-11f0-82f8-fa163e85dd73', NULL, 'Chaise', NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0, NULL, NULL, 1, NULL, NULL, '2026-01-30 11:15:12', '2026-01-30 11:26:07'),
('8b004b87-0a23-4cd5-93e3-2297c3ecdf0b', NULL, 'Armoire', NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0, NULL, NULL, 1, NULL, NULL, '2026-01-30 11:36:48', '2026-01-30 11:36:48'),
('c83f684e-370f-465b-b0d1-1e2f91fae2a2', NULL, 'Commode', NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0, NULL, NULL, 1, NULL, NULL, '2026-01-30 11:37:48', '2026-01-30 11:37:48'),
('d3ec21d1-1fb2-405e-b849-5144baf69c93', NULL, 'Climatiseur', NULL, 'climatiseur', NULL, NULL, NULL, 0.00, 0.00, 0, NULL, NULL, 1, NULL, NULL, '2026-02-06 16:36:05', '2026-02-06 16:36:05'),
('0fa67b04-7f52-45e9-882a-d0915bf1f607', NULL, 'Lavabo', NULL, 'Juste au cas où', NULL, NULL, NULL, 0.00, 0.00, 0, NULL, NULL, 1, NULL, NULL, '2026-03-11 15:33:17', '2026-03-11 15:33:17'),
('a62e4005-91fe-4f37-bd88-28fb1abf81fc', NULL, 'Climatiseur', NULL, 'Climatiseur', NULL, NULL, NULL, 0.00, 0.00, 0, NULL, NULL, 1, NULL, NULL, '2026-05-19 17:34:15', '2026-05-19 17:34:15');

-- --------------------------------------------------------

--
-- Structure de la table `type_piece`
--

CREATE TABLE `type_piece` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `type_piece`
--

INSERT INTO `type_piece` (`id`, `name`, `description`) VALUES
(1, 'CNI', 'Carte Nationale d’Identité'),
(2, 'PASSPORT', 'Passeport ordinaire'),
(3, 'PERMIS', 'Permis de conduire'),
(4, 'ATTESTATION', 'Attestation d’identité'),
(5, 'CARTE SCOLAIRE', 'Carte scolaire'),
(6, 'CARTE CONSULAIRE', 'Carte consulaire');

-- --------------------------------------------------------

--
-- Structure de la table `type_utilisateur`
--

CREATE TABLE `type_utilisateur` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `type_utilisateur`
--

INSERT INTO `type_utilisateur` (`id`, `name`, `created_at`, `updated_at`) VALUES
(7, 'Enseignant', '2026-03-11 16:29:46', '2026-03-11 16:29:46'),
(2, 'Administratif', '2025-12-29 21:42:13', '2026-03-11 16:28:47'),
(3, 'Technique', '2025-12-29 21:42:13', '2026-03-11 16:29:04'),
(4, 'Sécurité', '2025-12-29 21:42:13', '2026-03-11 16:28:33'),
(6, 'Directrice de logements', '2026-01-16 09:10:07', '2026-03-12 10:44:52');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id_user` char(36) NOT NULL,
  `matricule` varchar(50) DEFAULT NULL,
  `campus_id` varchar(150) DEFAULT NULL,
  `code` varchar(150) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `genre_id` int(11) NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `lieu_naissance` varchar(255) DEFAULT NULL,
  `nationalite` varchar(100) DEFAULT 'Ivoirienne',
  `cin` varchar(50) DEFAULT NULL,
  `date_delivrance_cin` date DEFAULT NULL,
  `telephone_principal` varchar(20) NOT NULL,
  `telephone_secondaire` varchar(20) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_secondaire` varchar(255) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `ville_id` int(11) DEFAULT NULL,
  `region_id` int(11) DEFAULT NULL,
  `photo` varchar(500) DEFAULT NULL,
  `type_utilisateur_id` int(11) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `campus_code` varchar(150) DEFAULT NULL,
  `date_inscription` date NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `statut` enum('active','inactive','suspendu','banni','en_attente') DEFAULT 'en_attente',
  `raison_suspension` text DEFAULT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `updated_by` varchar(150) DEFAULT NULL,
  `deleted_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id_user`, `matricule`, `campus_id`, `code`, `name`, `genre_id`, `date_naissance`, `lieu_naissance`, `nationalite`, `cin`, `date_delivrance_cin`, `telephone_principal`, `telephone_secondaire`, `email`, `email_secondaire`, `adresse`, `ville_id`, `region_id`, `photo`, `type_utilisateur_id`, `role_id`, `campus_code`, `date_inscription`, `password`, `remember_token`, `statut`, `raison_suspension`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('d896afa9-bd56-4a22-8d1c-f25e1de5bdf8', NULL, '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'Koffi Jephte', 1, NULL, NULL, 'IVOIRIENNE', '123456', NULL, '+2250545254655', NULL, 'jephte.koffi@soumafe.ci', NULL, 'Abidjan', 9, 1, NULL, 2, 1, NULL, '2026-06-25', '$2y$12$1gAky.FRbnDnEvmwyDaOruiuh78K4272DgJ5ZRtvKma8lZIcqR.CS', 'Ueq0wXtB4njD9tMQugtACNP1V1pc6aTnBNcSt4eIeePTMMeNaPWAS6zlUUvv', 'active', NULL, NULL, NULL, NULL, '2026-06-25 10:51:28', '2026-06-25 10:51:28', NULL),
('cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'Yapo Rodrigue', 1, NULL, NULL, 'Ivoirienne', NULL, NULL, '', NULL, 'rodrigue.yapo@soumafe.ci', NULL, NULL, 1, 1, NULL, 1, 1, NULL, '2026-06-17', '$2y$12$AVLFXIQ7HXGshGXhPBpl2.nPQVq34bq.Tg1ciFz4cRST01H.kuSk2', NULL, 'en_attente', NULL, NULL, NULL, NULL, '2026-06-17 14:06:53', '2026-06-17 14:08:09', NULL),
('a96eda67-0609-41e2-a074-3cf21042cec7', NULL, '3c11a70c-a236-4177-a98c-a77b5c45530c', NULL, 'test', 1, NULL, NULL, 'Ivoirienne', NULL, NULL, '0707902564', NULL, 'yaporodrigue1109@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-12', '$2y$12$BCaIbCrSXjzfKB4MFqBbjeQtIro5qFruozO8L6Ho41qLg01VktVNe', 'pQuzgcagO5TjiFLARnUJUCjfxCJYlBX3t2GhtRpNrg3hcZTyPRPq2RI6Aogv', 'active', NULL, NULL, NULL, NULL, '2026-10-12 21:21:10', '2026-10-12 21:21:10', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `villes`
--

CREATE TABLE `villes` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `region_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `villes`
--

INSERT INTO `villes` (`id`, `name`, `region_id`, `created_at`, `updated_at`) VALUES
(1, 'Abobo', 1, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(2, 'Anyama', 1, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(3, 'Attécoubé', 1, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(4, 'Cocody', 1, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(5, 'Koumassi', 1, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(6, 'Marcory', 1, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(7, 'Plateau', 1, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(8, 'Port-Bouët', 1, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(9, 'Bingerville', 1, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(10, 'Yopougon', 1, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(11, 'Songon', 1, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(12, 'Treichville', 1, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(13, 'Adjamé', 1, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(14, 'Dabou', 1, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(15, 'Agboville', 2, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(16, 'Sikensi', 2, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(17, 'Taabo', 2, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(18, 'Tiassalé', 2, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(19, 'Koro', 3, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(20, 'Ouaninou', 3, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(21, 'Touba', 3, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(22, 'Boundiali', 4, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(23, 'Kouto', 4, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(24, 'Tengréla', 4, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(25, 'Didiévi', 5, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(26, 'Djékanou', 5, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(27, 'Tiébissou', 5, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(28, 'Toumodi', 5, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(29, 'Dianra', 6, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(30, 'Kounahiri', 6, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(31, 'Mankono', 6, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(32, 'Bouna', 7, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(33, 'Doropo', 7, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(34, 'Nassian', 7, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(35, 'Téhini', 7, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(36, 'Bloléquin', 8, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(37, 'Guiglo', 8, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(38, 'Taï', 8, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(39, 'Toulepleu', 8, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(40, 'Kaniasso', 9, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(41, 'Minignan', 9, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(42, 'Béoumi', 10, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(43, 'Botro', 10, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(44, 'Bouaké', 10, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(45, 'Sakassou', 10, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(46, 'Fresco', 11, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(47, 'Sassandra', 11, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(48, 'Gagnoa', 12, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(49, 'Oumé', 12, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(50, 'Bondoukou', 13, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(51, 'Koun-Fao', 13, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(52, 'Sandégué', 13, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(53, 'Tanda', 13, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(54, 'Transua', 13, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(55, 'Dabou', 14, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(56, 'Grand-Lahou', 14, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(57, 'Jacqueville', 14, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(58, 'Bangolo', 15, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(59, 'Duékoué', 15, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(60, 'Facobly', 15, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(61, 'Kouibly', 15, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(62, 'Daloa', 16, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(63, 'Issia', 16, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(64, 'Vavoua', 16, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(65, 'Zoukougbeu', 16, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(66, 'Daoukro', 17, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(67, 'M’Bahiakro', 17, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(68, 'Ouellé', 17, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(69, 'Prikro', 17, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(70, 'Abengourou', 18, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(71, 'Agnibilékrou', 18, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(72, 'Bettié', 18, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(73, 'Gbéléban', 19, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(74, 'Madinani', 19, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(75, 'Odienné', 19, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(76, 'Samatiguila', 19, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(77, 'Séguélon', 19, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(78, 'Adzopé', 20, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(79, 'Akoupé', 20, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(80, 'Alépé', 20, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(81, 'Yakassé-Attobrou', 20, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(82, 'Divo', 21, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(83, 'Guitry', 21, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(84, 'Lakota', 21, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(85, 'Bonon', 22, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(86, 'Bouaflé', 22, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(87, 'Gohitafla', 22, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(88, 'Sinfra', 22, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(89, 'Zuénoula', 22, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(90, 'Dabakala', 23, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(91, 'Katiola', 23, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(92, 'Niakaramadougou', 23, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(93, 'Arrah', 24, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(94, 'Bongouanou', 24, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(95, 'M’Batto', 24, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(96, 'Buyo', 25, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(97, 'Guéyo', 25, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(98, 'Méagui', 25, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(99, 'Soubré', 25, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(100, 'Bocanda', 26, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(101, 'Dimbokro', 26, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(102, 'Kouassi-Kouassikro', 26, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(103, 'Dikodougou', 27, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(104, 'Korhogo', 27, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(105, 'M’Bengué', 27, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(106, 'Sinématiali', 27, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(107, 'San-Pédro', 28, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(108, 'Tabou', 28, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(109, 'Aboisso', 29, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(110, 'Adiaké', 29, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(111, 'Grand-Bassam', 29, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(112, 'Tiapoum', 29, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(113, 'Ferkessédougou', 30, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(114, 'Kong', 30, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(115, 'Ouangolodougou', 30, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(116, 'Biankouma', 31, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(117, 'Danané', 31, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(118, 'Man', 31, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(119, 'Sipilou', 31, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(120, 'Zouan-Hounien', 31, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(121, 'Kani', 32, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(122, 'Séguéla', 32, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(123, 'Attiégouakro', 33, '2024-12-24 07:05:30', '2024-12-24 07:05:30'),
(124, 'Yamoussoukro', 33, '2024-12-24 07:05:30', '2024-12-24 07:05:30');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_logs_created_at_action_index` (`created_at`,`action`),
  ADD KEY `activity_logs_actor_type_index` (`actor_type`),
  ADD KEY `activity_logs_actor_id_index` (`actor_id`),
  ADD KEY `activity_logs_route_name_index` (`route_name`);

--
-- Index pour la table `admission_derogations`
--
ALTER TABLE `admission_derogations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admission_derogations_etudiant_id_index` (`etudiant_id`),
  ADD KEY `admission_derogations_demande_id_index` (`demande_id`),
  ADD KEY `admission_derogations_campagne_id_index` (`campagne_id`);

--
-- Index pour la table `affectation_equipement_type_equipement`
--
ALTER TABLE `affectation_equipement_type_equipement`
  ADD PRIMARY KEY (`id_affectation`);

--
-- Index pour la table `aile_batiment`
--
ALTER TABLE `aile_batiment`
  ADD PRIMARY KEY (`id_aile_batiment`),
  ADD UNIQUE KEY `code_aile` (`code_aile`);

--
-- Index pour la table `annonces`
--
ALTER TABLE `annonces`
  ADD PRIMARY KEY (`id_annonce`);

--
-- Index pour la table `annonce_deliveries`
--
ALTER TABLE `annonce_deliveries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `annonce_recipient_channel_unique` (`annonce_id`,`etudiant_id`,`channel`),
  ADD KEY `annonce_deliveries_status_index` (`status`);

--
-- Index pour la table `assignation_chambres`
--
ALTER TABLE `assignation_chambres`
  ADD PRIMARY KEY (`id_assignation`),
  ADD KEY `assignation_chambres_lit_id_foreign` (`lit_id`);

--
-- Index pour la table `batiment`
--
ALTER TABLE `batiment`
  ADD PRIMARY KEY (`id_batiment`);

--
-- Index pour la table `campagne`
--
ALTER TABLE `campagne`
  ADD PRIMARY KEY (`id_campagne`);

--
-- Index pour la table `campaign_scorings`
--
ALTER TABLE `campaign_scorings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_scorings_campagne_id_index` (`campagne_id`);

--
-- Index pour la table `campus`
--
ALTER TABLE `campus`
  ADD PRIMARY KEY (`id_campus`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `code_campus` (`code_campus`);

--
-- Index pour la table `cautions`
--
ALTER TABLE `cautions`
  ADD PRIMARY KEY (`id_caution`);

--
-- Index pour la table `chambre`
--
ALTER TABLE `chambre`
  ADD PRIMARY KEY (`id_chambre`);

--
-- Index pour la table `cite`
--
ALTER TABLE `cite`
  ADD PRIMARY KEY (`id_cite`);

--
-- Index pour la table `config_app`
--
ALTER TABLE `config_app`
  ADD PRIMARY KEY (`config_id`);

--
-- Index pour la table `countrie`
--
ALTER TABLE `countrie`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `demande`
--
ALTER TABLE `demande`
  ADD PRIMARY KEY (`id_demande`),
  ADD KEY `demande_etat_dossier_index` (`etat_dossier`),
  ADD KEY `demande_assignation_id_index` (`assignation_id`);

--
-- Index pour la table `departement`
--
ALTER TABLE `departement`
  ADD PRIMARY KEY (`id_departement`),
  ADD UNIQUE KEY `code_departement` (`code_departement`);

--
-- Index pour la table `departure_requests`
--
ALTER TABLE `departure_requests`
  ADD PRIMARY KEY (`id_demande_sortie`),
  ADD KEY `departure_requests_etudiant_id_index` (`etudiant_id`),
  ADD KEY `departure_requests_assignation_id_index` (`assignation_id`),
  ADD KEY `departure_requests_chambre_id_index` (`chambre_id`),
  ADD KEY `departure_requests_sanction_id_index` (`sanction_id`);

--
-- Index pour la table `departure_settings`
--
ALTER TABLE `departure_settings`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `departure_settlements`
--
ALTER TABLE `departure_settlements`
  ADD PRIMARY KEY (`id_decompte`),
  ADD KEY `departure_settlements_demande_sortie_id_index` (`demande_sortie_id`),
  ADD KEY `departure_settlements_etat_des_lieux_id_index` (`etat_des_lieux_id`),
  ADD KEY `departure_settlements_etudiant_id_index` (`etudiant_id`),
  ADD KEY `departure_settlements_chambre_id_index` (`chambre_id`);

--
-- Index pour la table `disciplinary_reports`
--
ALTER TABLE `disciplinary_reports`
  ADD PRIMARY KEY (`id_rapport`),
  ADD UNIQUE KEY `disciplinary_reports_numero_rapport_unique` (`numero_rapport`),
  ADD KEY `disciplinary_reports_etudiant_id_index` (`etudiant_id`),
  ADD KEY `disciplinary_reports_cite_id_index` (`cite_id`),
  ADD KEY `disciplinary_reports_batiment_id_index` (`batiment_id`),
  ADD KEY `disciplinary_reports_chambre_id_index` (`chambre_id`),
  ADD KEY `disciplinary_reports_declarant_id_index` (`declarant_id`);

--
-- Index pour la table `disciplinary_report_histories`
--
ALTER TABLE `disciplinary_report_histories`
  ADD PRIMARY KEY (`id_historique`),
  ADD KEY `disciplinary_report_histories_rapport_id_index` (`rapport_id`);

--
-- Index pour la table `document`
--
ALTER TABLE `document`
  ADD PRIMARY KEY (`id_document`);

--
-- Index pour la table `document_campagne`
--
ALTER TABLE `document_campagne`
  ADD PRIMARY KEY (`id_document_campagne`);

--
-- Index pour la table `document_etudiant`
--
ALTER TABLE `document_etudiant`
  ADD PRIMARY KEY (`id_document`);

--
-- Index pour la table `dossier_transitions`
--
ALTER TABLE `dossier_transitions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dossier_transitions_demande_id_index` (`demande_id`);

--
-- Index pour la table `echeances`
--
ALTER TABLE `echeances`
  ADD PRIMARY KEY (`id_echeance`);

--
-- Index pour la table `echeanciers`
--
ALTER TABLE `echeanciers`
  ADD PRIMARY KEY (`id_echeancier`);

--
-- Index pour la table `edl_element`
--
ALTER TABLE `edl_element`
  ADD PRIMARY KEY (`edl_element_id`);

--
-- Index pour la table `edl_image`
--
ALTER TABLE `edl_image`
  ADD PRIMARY KEY (`edl_image_id`);

--
-- Index pour la table `edl_piece`
--
ALTER TABLE `edl_piece`
  ADD PRIMARY KEY (`edl_piece_id`);

--
-- Index pour la table `equipement`
--
ALTER TABLE `equipement`
  ADD PRIMARY KEY (`equipement_id`);

--
-- Index pour la table `equipement_affectations`
--
ALTER TABLE `equipement_affectations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipement_affectations_equipement_id_index` (`equipement_id`),
  ADD KEY `equipement_affectations_cite_id_index` (`cite_id`),
  ADD KEY `equipement_affectations_batiment_id_index` (`batiment_id`),
  ADD KEY `equipement_affectations_chambre_id_index` (`chambre_id`);

--
-- Index pour la table `equipement_mouvement`
--
ALTER TABLE `equipement_mouvement`
  ADD PRIMARY KEY (`equipement_mouvement_id`);

--
-- Index pour la table `etat_des_lieux`
--
ALTER TABLE `etat_des_lieux`
  ADD PRIMARY KEY (`etat_des_lieux_id`);

--
-- Index pour la table `etudiants`
--
ALTER TABLE `etudiants`
  ADD PRIMARY KEY (`id_etudiant`),
  ADD UNIQUE KEY `email1` (`email1`),
  ADD UNIQUE KEY `email2` (`email2`),
  ADD UNIQUE KEY `numero_etudiant` (`numero_etudiant`);

--
-- Index pour la table `filiere`
--
ALTER TABLE `filiere`
  ADD PRIMARY KEY (`id_filiere`),
  ADD UNIQUE KEY `code_filiere` (`code_filiere`);

--
-- Index pour la table `genre`
--
ALTER TABLE `genre`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `groupe_sanguins`
--
ALTER TABLE `groupe_sanguins`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `housing_campaign_announcements`
--
ALTER TABLE `housing_campaign_announcements`
  ADD PRIMARY KEY (`campaign_id`);

--
-- Index pour la table `housing_entry_appointments`
--
ALTER TABLE `housing_entry_appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `housing_entry_appointments_assignment_id_unique` (`assignment_id`),
  ADD KEY `housing_entry_appointments_student_id_index` (`student_id`),
  ADD KEY `housing_entry_appointments_starts_at_index` (`starts_at`);

--
-- Index pour la table `housing_entry_messages`
--
ALTER TABLE `housing_entry_messages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `housing_entry_messages_event_key_unique` (`event_key`),
  ADD KEY `housing_entry_messages_student_id_index` (`student_id`),
  ADD KEY `housing_entry_messages_assignment_id_index` (`assignment_id`),
  ADD KEY `housing_entry_messages_status_index` (`status`);

--
-- Index pour la table `housing_entry_settings`
--
ALTER TABLE `housing_entry_settings`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `interventions`
--
ALTER TABLE `interventions`
  ADD PRIMARY KEY (`id_intervention`);

--
-- Index pour la table `legal_documents`
--
ALTER TABLE `legal_documents`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `legal_document_versions`
--
ALTER TABLE `legal_document_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `legal_document_versions_legal_document_id_revision_unique` (`legal_document_id`,`revision`);

--
-- Index pour la table `legal_signatories`
--
ALTER TABLE `legal_signatories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `legal_signatories_role_code_unique` (`role_code`);

--
-- Index pour la table `lit`
--
ALTER TABLE `lit`
  ADD PRIMARY KEY (`id_lit`),
  ADD UNIQUE KEY `code_lit` (`code_lit`);

--
-- Index pour la table `lits`
--
ALTER TABLE `lits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lits_chambre_id_numero_unique` (`chambre_id`,`numero`);

--
-- Index pour la table `loyers`
--
ALTER TABLE `loyers`
  ADD PRIMARY KEY (`id_loyer`),
  ADD KEY `loyers_etudiant_id_foreign` (`etudiant_id`),
  ADD KEY `loyers_chambre_id_foreign` (`chambre_id`),
  ADD KEY `loyers_cite_id_foreign` (`cite_id`),
  ADD KEY `loyers_assignation_id_foreign` (`assignation_id`),
  ADD KEY `loyers_created_by_foreign` (`created_by`),
  ADD KEY `loyers_updated_by_foreign` (`updated_by`),
  ADD KEY `loyers_etudiant_annee_index` (`etudiant_id`,`annee_universitaire`),
  ADD KEY `loyers_statut_date_index` (`statut`,`mois_facturation`),
  ADD KEY `loyers_cite_annee_index` (`cite_id`,`annee_universitaire`),
  ADD KEY `loyers_solde_index` (`solde_restant`),
  ADD KEY `loyers_date_fin_index` (`date_limit_paiement`);

--
-- Index pour la table `maintenanciers`
--
ALTER TABLE `maintenanciers`
  ADD PRIMARY KEY (`id_maintenancier`);

--
-- Index pour la table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `modules_slug_unique` (`slug`),
  ADD KEY `modules_parent_id_index` (`parent_id`);

--
-- Index pour la table `niveau`
--
ALTER TABLE `niveau`
  ADD PRIMARY KEY (`id_niveau`);

--
-- Index pour la table `niveau_aile_batiment`
--
ALTER TABLE `niveau_aile_batiment`
  ADD PRIMARY KEY (`id_niveau_aile`);

--
-- Index pour la table `niveau_etude`
--
ALTER TABLE `niveau_etude`
  ADD PRIMARY KEY (`id_niveau_etude`);

--
-- Index pour la table `online_payments`
--
ALTER TABLE `online_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `online_payments_reference_unique` (`reference`),
  ADD UNIQUE KEY `online_payments_provider_id_provider_reference_unique` (`provider_id`,`provider_reference`),
  ADD UNIQUE KEY `online_payments_paiement_id_unique` (`paiement_id`),
  ADD KEY `online_payments_provider_id_index` (`provider_id`),
  ADD KEY `online_payments_student_id_index` (`student_id`),
  ADD KEY `online_payments_loyer_id_index` (`loyer_id`),
  ADD KEY `online_payments_status_index` (`status`),
  ADD KEY `online_payments_next_check_at_index` (`next_check_at`);

--
-- Index pour la table `ordres_travail`
--
ALTER TABLE `ordres_travail`
  ADD PRIMARY KEY (`id_ordres_travail`),
  ADD UNIQUE KEY `numero_ot` (`numero_ot`);

--
-- Index pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD PRIMARY KEY (`id_paiement`),
  ADD UNIQUE KEY `numero_paiement` (`numero_paiement`);

--
-- Index pour la table `paiement_repartitions`
--
ALTER TABLE `paiement_repartitions`
  ADD PRIMARY KEY (`id_repartition`),
  ADD KEY `idx_paiement_repartitions_paiement_id` (`paiement_id`),
  ADD KEY `idx_paiement_repartitions_type` (`type`),
  ADD KEY `idx_paiement_repartitions_mois_annee` (`mois`,`annee`);

--
-- Index pour la table `parametres_historique`
--
ALTER TABLE `parametres_historique`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_table_record` (`table_name`,`record_id`);

--
-- Index pour la table `parametres_logs`
--
ALTER TABLE `parametres_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_level` (`level`);

--
-- Index pour la table `payment_providers`
--
ALTER TABLE `payment_providers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_providers_code_unique` (`code`);

--
-- Index pour la table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_slug_unique` (`slug`),
  ADD KEY `permissions_module_index` (`module`),
  ADD KEY `permissions_module_id_index` (`module_id`);

--
-- Index pour la table `regions`
--
ALTER TABLE `regions`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `reglement_interieur`
--
ALTER TABLE `reglement_interieur`
  ADD PRIMARY KEY (`id_reglement`);

--
-- Index pour la table `residence_campagne`
--
ALTER TABLE `residence_campagne`
  ADD PRIMARY KEY (`id_residence_campagne`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `role_permission`
--
ALTER TABLE `role_permission`
  ADD PRIMARY KEY (`role_id`,`permission_id`);

--
-- Index pour la table `sanctions`
--
ALTER TABLE `sanctions`
  ADD PRIMARY KEY (`id_sanction`),
  ADD UNIQUE KEY `sanctions_numero_sanction_unique` (`numero_sanction`),
  ADD UNIQUE KEY `sanctions_rapport_id_unique` (`rapport_id`),
  ADD KEY `sanctions_etudiant_id_index` (`etudiant_id`),
  ADD KEY `sanctions_cite_id_index` (`cite_id`),
  ADD KEY `sanctions_batiment_id_index` (`batiment_id`),
  ADD KEY `sanctions_chambre_id_index` (`chambre_id`);

--
-- Index pour la table `sanction_histories`
--
ALTER TABLE `sanction_histories`
  ADD PRIMARY KEY (`id_historique`),
  ADD KEY `sanction_histories_sanction_id_index` (`sanction_id`);

--
-- Index pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `sms_pros`
--
ALTER TABLE `sms_pros`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `student_api_tokens`
--
ALTER TABLE `student_api_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_api_tokens_token_hash_unique` (`token_hash`),
  ADD KEY `student_api_tokens_etudiant_id_index` (`etudiant_id`);

--
-- Index pour la table `student_contract_approvals`
--
ALTER TABLE `student_contract_approvals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_contract_approvals_assignment_id_unique` (`assignment_id`),
  ADD KEY `student_contract_approvals_student_id_index` (`student_id`);

--
-- Index pour la table `type_chambre`
--
ALTER TABLE `type_chambre`
  ADD PRIMARY KEY (`id_type_chambre`);

--
-- Index pour la table `type_cite`
--
ALTER TABLE `type_cite`
  ADD PRIMARY KEY (`id_type_cite`),
  ADD UNIQUE KEY `code_type` (`code_type`);

--
-- Index pour la table `type_equipement`
--
ALTER TABLE `type_equipement`
  ADD PRIMARY KEY (`id_type_equipement`),
  ADD UNIQUE KEY `code_type` (`code_type`);

--
-- Index pour la table `type_piece`
--
ALTER TABLE `type_piece`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `type_utilisateur`
--
ALTER TABLE `type_utilisateur`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `matricule` (`matricule`),
  ADD UNIQUE KEY `cin` (`cin`);

--
-- Index pour la table `villes`
--
ALTER TABLE `villes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `affectation_equipement_type_equipement`
--
ALTER TABLE `affectation_equipement_type_equipement`
  MODIFY `id_affectation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `aile_batiment`
--
ALTER TABLE `aile_batiment`
  MODIFY `id_aile_batiment` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT pour la table `annonce_deliveries`
--
ALTER TABLE `annonce_deliveries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `campaign_scorings`
--
ALTER TABLE `campaign_scorings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `countrie`
--
ALTER TABLE `countrie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `departement`
--
ALTER TABLE `departement`
  MODIFY `id_departement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `departure_settings`
--
ALTER TABLE `departure_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `dossier_transitions`
--
ALTER TABLE `dossier_transitions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `filiere`
--
ALTER TABLE `filiere`
  MODIFY `id_filiere` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `genre`
--
ALTER TABLE `genre`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `groupe_sanguins`
--
ALTER TABLE `groupe_sanguins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `housing_entry_appointments`
--
ALTER TABLE `housing_entry_appointments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `housing_entry_messages`
--
ALTER TABLE `housing_entry_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `housing_entry_settings`
--
ALTER TABLE `housing_entry_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `legal_documents`
--
ALTER TABLE `legal_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `legal_document_versions`
--
ALTER TABLE `legal_document_versions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `legal_signatories`
--
ALTER TABLE `legal_signatories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lit`
--
ALTER TABLE `lit`
  MODIFY `id_lit` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lits`
--
ALTER TABLE `lits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT pour la table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT pour la table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT pour la table `niveau`
--
ALTER TABLE `niveau`
  MODIFY `id_niveau` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `niveau_aile_batiment`
--
ALTER TABLE `niveau_aile_batiment`
  MODIFY `id_niveau_aile` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT pour la table `parametres_historique`
--
ALTER TABLE `parametres_historique`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `parametres_logs`
--
ALTER TABLE `parametres_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `payment_providers`
--
ALTER TABLE `payment_providers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT pour la table `regions`
--
ALTER TABLE `regions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT pour la table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sms_pros`
--
ALTER TABLE `sms_pros`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `type_piece`
--
ALTER TABLE `type_piece`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `type_utilisateur`
--
ALTER TABLE `type_utilisateur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `villes`
--
ALTER TABLE `villes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `legal_document_versions`
--
ALTER TABLE `legal_document_versions`
  ADD CONSTRAINT `legal_document_versions_legal_document_id_foreign` FOREIGN KEY (`legal_document_id`) REFERENCES `legal_documents` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

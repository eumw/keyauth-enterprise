SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `keyauth`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_tokens`
--

CREATE TABLE `access_tokens` (
  `token` varchar(255) NOT NULL,
  `client_id` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `app` varchar(255) DEFAULT NULL,
  `scopes` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acclogs`
--

CREATE TABLE `acclogs` (
  `id` int NOT NULL,
  `username` varchar(65) DEFAULT NULL,
  `date` varchar(10) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `useragent` varchar(400) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `username` varchar(65) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email` varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `password` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `ownerid` varchar(65) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `role` varchar(65) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `app` varchar(65) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `owner` varchar(49) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `banned` varchar(99) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `locked` int NOT NULL DEFAULT '0',
  `warning` varchar(999) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `admin` int NOT NULL DEFAULT '0',
  `img` varchar(2048) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'https://cdn.keyauth.cc/assets/img/favicon.png',
  `balance` varchar(49) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `keylevels` varchar(49) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'N/A',
  `expires` varchar(49) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `registrationip` varchar(49) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `lastip` varchar(49) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `region` varchar(99) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `asNum` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `twofactor` int NOT NULL DEFAULT '0',
  `googleAuthCode` varchar(59) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `darkmode` int NOT NULL DEFAULT '0',
  `acclogs` int NOT NULL DEFAULT '1',
  `lastreset` int DEFAULT NULL,
  `emailVerify` int NOT NULL DEFAULT '1',
  `permissions` bit(64) NOT NULL DEFAULT b'11111111111',
  `securityKey` int NOT NULL DEFAULT '0',
  `staff` int DEFAULT '0',
  `formBanned` int DEFAULT '0',
  `connection` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `alert` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `secWords` varchar(375) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `apps`
--

CREATE TABLE `apps` (
  `id` int NOT NULL,
  `owner` varchar(65) NOT NULL,
  `name` varchar(191) NOT NULL,
  `secret` varchar(64) NOT NULL,
  `ownerid` varchar(39) NOT NULL,
  `enabled` int NOT NULL,
  `banned` int NOT NULL DEFAULT '0',
  `paused` int NOT NULL DEFAULT '0',
  `hwidcheck` int NOT NULL,
  `vpnblock` int NOT NULL DEFAULT '0',
  `sellerkey` varchar(32) NOT NULL,
  `ver` varchar(5) NOT NULL DEFAULT '1.0',
  `download` varchar(120) DEFAULT NULL,
  `hash` varchar(2000) DEFAULT NULL,
  `webhook` varchar(2048) DEFAULT NULL,
  `ipLogging` int NOT NULL DEFAULT '1',
  `auditLogWebhook` varchar(130) DEFAULT NULL,
  `resellerstore` varchar(69) DEFAULT NULL,
  `appdisabled` varchar(100) NOT NULL DEFAULT 'This application is disabled',
  `usernametaken` varchar(100) NOT NULL DEFAULT 'Username already taken, choose a different one',
  `keynotfound` varchar(100) NOT NULL DEFAULT 'Invalid license key',
  `keyused` varchar(100) NOT NULL DEFAULT 'License key has already been used',
  `nosublevel` varchar(100) DEFAULT 'There is no subscription created for your key level. Contact application developer.',
  `usernamenotfound` varchar(100) NOT NULL DEFAULT 'Invalid username',
  `passmismatch` varchar(100) NOT NULL DEFAULT 'Password does not match.',
  `hwidmismatch` varchar(100) NOT NULL DEFAULT 'HWID doesn''t match. Ask for a HWID reset',
  `noactivesubs` varchar(100) NOT NULL DEFAULT 'No active subscription(s) found',
  `hwidblacked` varchar(100) NOT NULL DEFAULT 'You''ve been blacklisted from our application',
  `pausedsub` varchar(100) NOT NULL DEFAULT 'Your subscription is paused and can''t be used right now',
  `vpnblocked` varchar(100) NOT NULL DEFAULT 'VPNs are blocked on this application',
  `keybanned` varchar(100) NOT NULL DEFAULT 'Your license is banned',
  `userbanned` varchar(100) NOT NULL DEFAULT 'The user is banned',
  `sessionunauthed` varchar(100) NOT NULL DEFAULT 'Session is not validated',
  `hashcheckfail` varchar(100) NOT NULL DEFAULT 'This program hash does not match, make sure you''re using latest version',
  `loggedInMsg` varchar(99) NOT NULL DEFAULT 'Logged in!',
  `pausedApp` varchar(99) NOT NULL DEFAULT 'Application is currently paused, please wait for the developer to say otherwise.',
  `unTooShort` varchar(99) NOT NULL DEFAULT 'Username too short, try longer one.',
  `pwLeaked` varchar(99) NOT NULL DEFAULT 'This password has been leaked in a data breach (not from us), please use a different one.',
  `chatHitDelay` varchar(99) NOT NULL DEFAULT 'Chat slower, you''ve hit the delay limit',
  `sellixsecret` varchar(32) DEFAULT NULL,
  `sellixdayproduct` varchar(13) DEFAULT NULL,
  `sellixweekproduct` varchar(13) DEFAULT NULL,
  `sellixmonthproduct` varchar(13) DEFAULT NULL,
  `sellixlifetimeproduct` varchar(13) DEFAULT NULL,
  `shoppysecret` varchar(16) DEFAULT NULL,
  `shoppydayproduct` varchar(7) DEFAULT NULL,
  `shoppyweekproduct` varchar(7) DEFAULT NULL,
  `shoppymonthproduct` varchar(7) DEFAULT NULL,
  `shoppylifetimeproduct` varchar(7) DEFAULT NULL,
  `sellappsecret` varchar(64) DEFAULT NULL,
  `sellappdayproduct` varchar(199) DEFAULT NULL,
  `sellappweekproduct` varchar(199) DEFAULT NULL,
  `sellappmonthproduct` varchar(199) DEFAULT NULL,
  `sellapplifetimeproduct` varchar(199) DEFAULT NULL,
  `cooldown` int NOT NULL DEFAULT '604800',
  `panelstatus` int NOT NULL DEFAULT '1',
  `session` int NOT NULL DEFAULT '21600',
  `hashcheck` int NOT NULL DEFAULT '0',
  `webdownload` varchar(191) DEFAULT NULL,
  `customDomain` varchar(253) DEFAULT NULL,
  `format` varchar(99) NOT NULL DEFAULT '******-******-******-******-******-******',
  `amount` int DEFAULT NULL,
  `lvl` int DEFAULT NULL,
  `note` varchar(69) DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `unit` int DEFAULT NULL,
  `killOtherSessions` int NOT NULL DEFAULT '0',
  `cooldownUnit` int NOT NULL DEFAULT '86400',
  `sessionUnit` int NOT NULL DEFAULT '3600',
  `minUsernameLength` int NOT NULL DEFAULT '1',
  `blockLeakedPasswords` int NOT NULL DEFAULT '0',
  `forceEncryption` int NOT NULL DEFAULT '0',
  `customDomainAPI` varchar(253) DEFAULT NULL,
  `customerPanelIcon` varchar(200) NOT NULL DEFAULT 'https://cdn.keyauth.cc/front/assets/img/favicon.png',
  `forceHwid` int DEFAULT '1',
  `minHwid` int DEFAULT '20',
  `sellerLogs` int DEFAULT '0',
  `sellerApiWhitelist` varchar(49) DEFAULT NULL,
  `tokensystem` int DEFAULT '0',
  `tokeninvalid` varchar(255) DEFAULT 'Please provide a valid token for you to proceed',
  `enabledFunctions` varchar(172) NOT NULL DEFAULT '524287',
  `apiCustomDomainId` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditLog`
--

CREATE TABLE `auditLog` (
  `id` int NOT NULL,
  `user` varchar(65) NOT NULL,
  `event` varchar(999) NOT NULL,
  `time` int NOT NULL,
  `app` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `authorization_codes`
--

CREATE TABLE `authorization_codes` (
  `code` varchar(255) NOT NULL,
  `client_id` varchar(255) NOT NULL,
  `ownerid` varchar(39) NOT NULL,
  `app` varchar(64) DEFAULT NULL,
  `scopes` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bans`
--

CREATE TABLE `bans` (
  `id` int NOT NULL,
  `hwid` varchar(500) DEFAULT NULL,
  `ip` varchar(49) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `country` varchar(60) DEFAULT NULL,
  `asn` varchar(60) DEFAULT NULL,
  `type` varchar(5) DEFAULT NULL,
  `app` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buttons`
--

CREATE TABLE `buttons` (
  `id` int NOT NULL,
  `text` varchar(99) NOT NULL,
  `value` varchar(99) NOT NULL,
  `app` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chatmsgs`
--

CREATE TABLE `chatmsgs` (
  `id` int NOT NULL,
  `author` varchar(70) NOT NULL,
  `message` varchar(2000) NOT NULL,
  `timestamp` int NOT NULL,
  `channel` varchar(50) NOT NULL,
  `app` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chatmutes`
--

CREATE TABLE `chatmutes` (
  `id` int NOT NULL,
  `user` varchar(70) NOT NULL,
  `time` int NOT NULL,
  `app` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chats`
--

CREATE TABLE `chats` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `delay` int NOT NULL,
  `app` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customBots`
--

CREATE TABLE `customBots` (
  `id` int NOT NULL,
  `clientId` bigint NOT NULL,
  `token` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `pubKey` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `app` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` int DEFAULT (unix_timestamp())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emailverify`
--

CREATE TABLE `emailverify` (
  `id` int NOT NULL,
  `secret` varchar(32) NOT NULL,
  `email` varchar(40) NOT NULL,
  `time` int NOT NULL,
  `region` varchar(99) DEFAULT NULL,
  `asNum` varchar(20) DEFAULT NULL,
  `newEmail` varchar(40) DEFAULT NULL,
  `newUsername` varchar(99) DEFAULT NULL,
  `oldUsername` varchar(99) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `pk` int NOT NULL,
  `name` varchar(49) NOT NULL,
  `id` varchar(49) NOT NULL,
  `url` varchar(2048) DEFAULT NULL,
  `size` varchar(49) NOT NULL,
  `uploaddate` varchar(49) NOT NULL,
  `app` varchar(64) NOT NULL,
  `authed` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `keys`
--

CREATE TABLE `keys` (
  `id` int NOT NULL,
  `key` varchar(70) NOT NULL,
  `note` varchar(191) DEFAULT NULL,
  `expires` varchar(49) NOT NULL,
  `status` varchar(49) NOT NULL,
  `level` varchar(12) NOT NULL DEFAULT '',
  `genby` varchar(65) DEFAULT NULL,
  `gendate` varchar(49) NOT NULL,
  `usedon` int DEFAULT NULL,
  `usedby` varchar(70) DEFAULT NULL,
  `app` varchar(64) NOT NULL,
  `banned` varchar(99) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int NOT NULL,
  `logdate` varchar(49) NOT NULL,
  `logdata` varchar(275) NOT NULL,
  `credential` varchar(70) DEFAULT NULL,
  `pcuser` varchar(32) DEFAULT NULL,
  `logapp` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauthApps`
--

CREATE TABLE `oauthApps` (
  `client_id` varchar(255) NOT NULL,
  `client_secret` varchar(255) NOT NULL,
  `app_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `orderID` varchar(36) NOT NULL,
  `username` varchar(65) NOT NULL,
  `date` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resets`
--

CREATE TABLE `resets` (
  `id` int NOT NULL,
  `secret` char(32) NOT NULL,
  `email` varchar(40) NOT NULL,
  `time` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resetUsers`
--

CREATE TABLE `resetUsers` (
  `id` int NOT NULL,
  `secret` varchar(32) NOT NULL,
  `email` varchar(40) NOT NULL,
  `username` varchar(70) NOT NULL,
  `app` varchar(64) NOT NULL,
  `time` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `securityKeys`
--

CREATE TABLE `securityKeys` (
  `id` int NOT NULL,
  `username` varchar(65) DEFAULT NULL,
  `name` varchar(99) DEFAULT NULL,
  `credentialId` varchar(999) DEFAULT NULL,
  `credentialPublicKey` varchar(999) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sellerLogs`
--

CREATE TABLE `sellerLogs` (
  `id` int NOT NULL,
  `ip` varchar(45) NOT NULL,
  `path` varchar(999) NOT NULL,
  `date` int NOT NULL,
  `app` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(10) NOT NULL,
  `credential` varchar(70) DEFAULT NULL,
  `app` varchar(64) NOT NULL,
  `expiry` int NOT NULL,
  `created_at` int DEFAULT NULL,
  `enckey` varchar(100) DEFAULT NULL,
  `validated` int NOT NULL DEFAULT '0',
  `ip` varchar(45) DEFAULT NULL,
  `pk` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subs`
--

CREATE TABLE `subs` (
  `id` int NOT NULL,
  `user` varchar(70) NOT NULL,
  `subscription` varchar(49) NOT NULL,
  `expiry` varchar(49) NOT NULL,
  `app` varchar(64) NOT NULL,
  `key` varchar(70) DEFAULT NULL,
  `paused` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int NOT NULL,
  `name` varchar(49) NOT NULL,
  `level` varchar(12) NOT NULL,
  `app` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support`
--

CREATE TABLE `support` (
  `id` int NOT NULL,
  `username` varchar(65) NOT NULL,
  `time` int NOT NULL,
  `message` varchar(200) DEFAULT NULL,
  `staff` int NOT NULL DEFAULT '0',
  `ownerid` varchar(65) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tokens`
--

CREATE TABLE `tokens` (
  `id` int NOT NULL,
  `app` varchar(255) DEFAULT NULL,
  `token` varchar(32) DEFAULT NULL,
  `assigned` varchar(255) DEFAULT NULL,
  `banned` int DEFAULT '0',
  `reason` varchar(255) DEFAULT NULL,
  `hash` varchar(255) DEFAULT NULL,
  `type` varchar(45) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(70) NOT NULL,
  `email` varchar(40) DEFAULT NULL,
  `password` varchar(60) DEFAULT NULL,
  `hwid` varchar(2000) DEFAULT NULL,
  `app` varchar(64) NOT NULL,
  `owner` varchar(65) DEFAULT NULL,
  `createdate` int DEFAULT NULL,
  `lastlogin` int DEFAULT NULL,
  `banned` varchar(99) DEFAULT NULL,
  `ip` varchar(49) DEFAULT NULL,
  `cooldown` int DEFAULT NULL,
  `googleAuthCode` varchar(59) DEFAULT NULL,
  `2fa` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `uservars`
--

CREATE TABLE `uservars` (
  `id` int NOT NULL,
  `name` varchar(99) NOT NULL,
  `data` varchar(500) NOT NULL,
  `user` varchar(70) NOT NULL,
  `app` varchar(64) NOT NULL,
  `readOnly` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vars`
--

CREATE TABLE `vars` (
  `id` int NOT NULL,
  `varid` varchar(49) NOT NULL,
  `msg` varchar(1000) NOT NULL,
  `app` varchar(64) NOT NULL,
  `authed` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `webhooks`
--

CREATE TABLE `webhooks` (
  `id` int NOT NULL,
  `webid` varchar(60) NOT NULL,
  `baselink` varchar(200) NOT NULL,
  `useragent` varchar(49) NOT NULL DEFAULT 'KeyAuth',
  `app` varchar(64) NOT NULL,
  `authed` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `whitelist`
--

CREATE TABLE `whitelist` (
  `id` int NOT NULL,
  `ip` varchar(49) NOT NULL,
  `app` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_tokens`
--
ALTER TABLE `access_tokens`
  ADD PRIMARY KEY (`token`);

--
-- Indexes for table `acclogs`
--
ALTER TABLE `acclogs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username` (`username`);

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`username`),
  ADD KEY `idx_email_sha1` (`email`,`username`),
  ADD KEY `idx_accounts_owner` (`owner`);

--
-- Indexes for table `apps`
--
ALTER TABLE `apps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sellerkey_idx` (`sellerkey`),
  ADD KEY `name_owner_idx` (`name`,`owner`),
  ADD KEY `idx_apps_secret` (`secret`),
  ADD KEY `idx_apps_owner_ownerid` (`owner`,`ownerid`),
  ADD KEY `idx_apps_customdomain` (`customDomain`);

--
-- Indexes for table `auditLog`
--
ALTER TABLE `auditLog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app` (`app`);

--
-- Indexes for table `authorization_codes`
--
ALTER TABLE `authorization_codes`
  ADD PRIMARY KEY (`code`);

--
-- Indexes for table `bans`
--
ALTER TABLE `bans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bans_hwid_ip_app` (`hwid`,`ip`,`app`),
  ADD KEY `idx_bans_app_hwid_ip` (`app`,`hwid`,`ip`);

--
-- Indexes for table `buttons`
--
ALTER TABLE `buttons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `value` (`value`,`app`),
  ADD KEY `app_index` (`app`);

--
-- Indexes for table `chatmsgs`
--
ALTER TABLE `chatmsgs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_channel_app` (`channel`,`app`),
  ADD KEY `idx_app` (`app`);

--
-- Indexes for table `chatmutes`
--
ALTER TABLE `chatmutes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `app_index` (`app`);

--
-- Indexes for table `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `one name per app` (`name`,`app`),
  ADD KEY `app_index` (`app`);

--
-- Indexes for table `customBots`
--
ALTER TABLE `customBots`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emailverify`
--
ALTER TABLE `emailverify`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`pk`),
  ADD KEY `idx_app_id` (`app`,`id`);

--
-- Indexes for table `keys`
--
ALTER TABLE `keys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `app_index` (`app`),
  ADD KEY `idx_app_key` (`app`,`key`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_logapp_logdata_credential_pcuser` (`logapp`,`logdata`,`credential`,`pcuser`);

--
-- Indexes for table `oauthApps`
--
ALTER TABLE `oauthApps`
  ADD PRIMARY KEY (`client_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `resets`
--
ALTER TABLE `resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `resetUsers`
--
ALTER TABLE `resetUsers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `securityKeys`
--
ALTER TABLE `securityKeys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username_index` (`username`);

--
-- Indexes for table `sellerLogs`
--
ALTER TABLE `sellerLogs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `app_index` (`app`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`pk`),
  ADD UNIQUE KEY `SESSION` (`app`,`ip`),
  ADD KEY `session index` (`id`,`app`),
  ADD KEY `app_validated_expiry_index` (`app`,`validated`,`expiry`);

--
-- Indexes for table `subs`
--
ALTER TABLE `subs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_app_paused_idx` (`user`,`app`,`paused`),
  ADD KEY `idx_subs_user_app_expiry` (`user`,`app`,`expiry`),
  ADD KEY `app_subscription_expiry_idx` (`app`,`subscription`,`expiry`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `app_level_idx` (`app`,`level`);

--
-- Indexes for table `support`
--
ALTER TABLE `support`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ownerid_time` (`ownerid`,`time`);

--
-- Indexes for table `tokens`
--
ALTER TABLE `tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token_app` (`app`),
  ADD KEY `idx_token` (`token`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user index` (`username`,`app`),
  ADD KEY `app_index` (`app`);

--
-- Indexes for table `uservars`
--
ALTER TABLE `uservars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user vars` (`name`,`user`,`app`),
  ADD KEY `idx_uservars_app` (`app`);

--
-- Indexes for table `vars`
--
ALTER TABLE `vars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vars_varid_app` (`varid`,`app`,`msg`(50),`authed`),
  ADD KEY `index_app` (`app`),
  ADD KEY `idx_vars_app_varid_msg` (`app`,`varid`,`msg`(50));

--
-- Indexes for table `webhooks`
--
ALTER TABLE `webhooks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `webid_app_idx` (`webid`,`app`);

--
-- Indexes for table `whitelist`
--
ALTER TABLE `whitelist`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `acclogs`
--
ALTER TABLE `acclogs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `apps`
--
ALTER TABLE `apps`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditLog`
--
ALTER TABLE `auditLog`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bans`
--
ALTER TABLE `bans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buttons`
--
ALTER TABLE `buttons`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chatmsgs`
--
ALTER TABLE `chatmsgs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chatmutes`
--
ALTER TABLE `chatmutes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chats`
--
ALTER TABLE `chats`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customBots`
--
ALTER TABLE `customBots`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emailverify`
--
ALTER TABLE `emailverify`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `pk` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `keys`
--
ALTER TABLE `keys`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resets`
--
ALTER TABLE `resets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resetUsers`
--
ALTER TABLE `resetUsers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `securityKeys`
--
ALTER TABLE `securityKeys`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sellerLogs`
--
ALTER TABLE `sellerLogs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `pk` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subs`
--
ALTER TABLE `subs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support`
--
ALTER TABLE `support`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tokens`
--
ALTER TABLE `tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `uservars`
--
ALTER TABLE `uservars`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vars`
--
ALTER TABLE `vars`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `webhooks`
--
ALTER TABLE `webhooks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `whitelist`
--
ALTER TABLE `whitelist`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

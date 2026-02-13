<?php

namespace misc\upload;

use misc\etc;
use misc\cache;
use misc\mysql;

function add($url, $authed, $secret = null){
  $url = etc\sanitize($url);
  $authed = etc\sanitize($authed);

  if (!filter_var($url, FILTER_VALIDATE_URL)) {
    return 'invalid';
  }

  if (str_contains($url, "discord")) {
    return 'discord_cdn';
  }

  if (str_contains($url, "github.com")) {
    return 'github_no_raw';
  }

  if(str_contains($url, "localhost") || str_contains($url, "127.0.0.1") || str_contains($url, "file:/"))
    return 'no_local';

    //$requiredExtension = array(
    //  // Common file types
    //  ".zip", ".pdf", ".tiff", ".png", ".exe", ".psd", ".mp3", ".mp4",
    //  ".jar", ".xls", ".csv", ".bmp", ".txt", ".xml", ".rar", ".jpg", ".jpeg",
    //  ".doc", ".eps", ".avi", ".mov", ".apk", ".ios", ".sys", ".dll", ".js",
  //
    //  // Web development
    //  ".html", ".css", ".php", ".json", ".asp", ".aspx", ".jsp", ".htaccess",
  //
    //  // Programming languages
    //  ".c", ".cpp", ".h", ".java", ".py", ".php", ".js", ".html", ".css",
    //  ".rb", ".swift", ".pl", ".lua", ".sh", ".bat", ".ps1", ".ts",
  //
    //  // Database
    //  ".sql", ".db", ".mdb", ".accdb", ".sqlite", ".dbf",
  //
    //  // Markup and document formats
    //  ".markdown", ".md", ".rst", ".tex", ".latex", ".odt", ".docx", ".pptx",
  //
    //  // Configuration files
    //  ".ini", ".yaml", ".yml", ".json", ".xml", ".cfg", ".conf",
  //
    //  // Archive and compression
    //  ".7z", ".gz", ".tar", ".bz2", ".xz", ".z", ".rar",
  //
    //  // Data interchange formats
    //  ".json", ".xml", ".yaml", ".csv",
  //
    //  // Version control
    //  ".gitignore", ".gitattributes", ".gitkeep",
  //
    //  // Compiled files
    //  ".class", ".o", ".obj", ".lib", ".dll", ".so",
  //
    //  // Executable scripts
    //  ".bat", ".sh", ".ps1", ".bash",
  //
    //  // Virtualization
    //  ".vmdk", ".vdi", ".ova", ".ovf",
  //
    //  // Configuration and settings
    //  ".conf", ".config", ".settings",
  //
    //  // Configuration and log files
    //  ".log", ".log1", ".log2", ".bak", ".backup", ".swp", ".ttf",
//
    //  // binary, I (mak) am going to tell people rename their files to .bin so catbox.moe accepts it
    //  ".bin"
    //);

  //$linkExtension = strtolower(substr($url, strrpos($url, ".")));
//
  //if (!in_array($linkExtension, $requiredExtension)){
  //  return 'invalid_extension';
  //}

  // Initialize cURL session
  $ch = curl_init($url);

  // Set options
  curl_setopt($ch, CURLOPT_NOBODY, true); // Only fetch headers, no body
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the transfer as a string
  curl_setopt($ch, CURLOPT_HEADER, true); // Include the header in the output
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla KeyAuth');

  $response = curl_exec($ch);

  $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  if ($httpStatusCode == 403 || $httpStatusCode == 404) {
    return 'bad_response';
  }
  
  // Get content length
  $filesize = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
  $contType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

  if(str_contains($contType, "html")) {
    return 'invalid_extension';
  }

  if ($filesize > 10000000 && $_SESSION['role'] == "tester") {
    return 'tester_file_exceed';
  } else if ($filesize > 50000000 && ($_SESSION['role'] == "developer" || $_SESSION['role'] == "Manager")) {
    return 'dev_file_exceed';
  } else if ($filesize > 75000000) {
    return 'seller_file_exceed';
  }
  $id = etc\generateRandomNum();
  $fn = basename(parse_url($url, PHP_URL_PATH));
  $fs = etc\formatBytes($filesize);

  if (strlen($fn) > 49) {
    return 'name_too_large';
  }

  $query = mysql\query("INSERT INTO `files` (name, id, url, size, uploaddate, app, authed) VALUES (?, ?, ?, ?, ?, ?, ?)", [$fn, $id, $url, $fs, time(), $secret ?? $_SESSION['app'], $authed]);
  if ($query->affected_rows > 0) {
    if ($_SESSION['role'] == "seller" || !is_null($secret)) {
      cache\purge('KeyAuthFiles:' . ($secret ?? $_SESSION['app']));
    }
    return 'success';
  } else {
    return 'failure';
  }
}
function deleteAll($secret = null){
  $query = mysql\query("DELETE FROM `files` WHERE `app` = ?", [$secret ?? $_SESSION['app']]);

  if ($query->affected_rows > 0) {
    cache\purgePattern('KeyAuthFile:' . ($secret ?? $_SESSION['app']));
    if ($_SESSION['role'] == "seller" || !is_null($secret)) {
      cache\purge('KeyAuthFiles:' . ($secret ?? $_SESSION['app']));
    }
    return 'success';
  } else {
    return 'failure';
  }
}
function deleteSingular($file, $secret = null){
  $file = etc\sanitize($file);

  $query = mysql\query("DELETE FROM `files` WHERE `app` = ? AND `id` = ?", [$secret ?? $_SESSION['app'], $file]);

  if ($query->affected_rows > 0) {
    cache\purge('KeyAuthFile:' . ($secret ?? $_SESSION['app']) . ':' . $file);
    if ($_SESSION['role'] == "seller" || !is_null($secret)) {
      cache\purge('KeyAuthFiles:' . ($secret ?? $_SESSION['app']));
    }
    return 'success';
  } else {
    return 'failure';
  }
}

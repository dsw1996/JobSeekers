<?php

// Set page headers function
function setHeader($title)
{
    // Start the session for storing access tokens
    if (!headers_sent()) {
        session_start();
    }

    $header_data = "<!doctype html>
  <html>
  <head>
    <title>" . $title . "</title>
    <link href='styles/style.css' rel='stylesheet' type='text/css' />
  </head>
  <body>\n";
    $header_data .= "<a href='upload-file.php'>Back to Home</a>&nbsp;&nbsp;&nbsp;";
    if(isset($_SESSION['upload_token'])){
        $header_data .= "&nbsp;&nbsp;&nbsp;&nbsp;<a href='upload-file.php?logout=1'>Logout</a>";
    }
    $header_data .= "<header><h1>" . $title . "</h1></header>";
    return $header_data;
}

// Function to print error on missing credentials
function missingOAuth2CredentialsWarning()
{
    $response_data = "
    <h3 class='warn'>
      Warning: You need to set the location of your OAuth2 Client Credentials from the
      <a href='http://developers.google.com/console'>Google API console</a>.
    </h3>
    <p>
      Once downloaded, move them into the root directory of this repository and
      rename them 'oauth-credentials-file.json'.
    </p>";

    return $response_data;
}

// get the credentials from credentials file
function getOAuthCredentialsFile()
{
    $oauth_creds = 'oauth-credentials-file.json';
    if (file_exists($oauth_creds)) {
        return $oauth_creds;
    }
    return false;
}

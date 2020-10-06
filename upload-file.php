<?php

include_once 'vendor/autoload.php';
include_once "validation.php";

echo setHeader("Uploading a file on \"Google Drive\" using \"Google OAuth Token\" Using PHP");

// check for oauth credentials
if (!$oauth_credentials_file = getOAuthCredentialsFile()) {
    echo missingOAuth2CredentialsWarning();
    return;
}

// set redirect URI is to the current page
$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

// set config for google OAuth with google client library
$client = new Google_Client(); // create instant of google client
$client->setAuthConfig($oauth_credentials_file); // set credentials
$client->setRedirectUri($redirect_uri); // set redirect url
$client->addScope("https://www.googleapis.com/auth/drive"); // set scope of access
$service = new Google_Service_Drive($client); // set google client object with google service


// on logout remove a token from the session
if (isset($_REQUEST['logout'])) {
    unset($_SESSION['upload_token']);
    header('Location: upload-file.php');
}

/*
 - If we have a code back from the OAuth 2.0 flow, we need to exchange that with the
   Google_Client::fetchAccessTokenWithAuthCode() function. We store the result access token bundle in the session, and redirect to ourself.
 */
if (isset($_GET['code'])) {
    // get access token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // store in the session also
    $_SESSION['upload_token'] = $token;

    // redirect back to the example
    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}


// set the access token as part of the client
if (!empty($_SESSION['upload_token'])) {
    $client->setAccessToken($_SESSION['upload_token']);
    if ($client->isAccessTokenExpired()) {
        unset($_SESSION['upload_token']);
    }
} else {
    $authUrl = $client->createAuthUrl();
}

// If we're signed in then lets try to upload our file to local first then on drive.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $client->getAccessToken()) {

    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $errorMsg = '';

    // get extension of file
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Check if file is attached and submit request
    if(isset($_POST["submit"]) && !(empty($_FILES['fileToUpload']))) {
        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
            && $imageFileType != "gif" && $imageFileType != "JPG" && $imageFileType != "pdf" && $imageFileType != "doc" && $imageFileType != "docx") {
            $errorMsg = "Sorry, only JPG, JPEG, PNG, GIF, PDF & DOC files are allowed.";
            $uploadOk = 0;
        }
        // check for file size limit
        else if ($_FILES["fileToUpload"]["size"] > 500000) {
            $errorMsg = "Sorry, your file is too large.";
            $uploadOk = 0;
        }
        // move file to our directory
        else if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            DEFINE("DEMOFILE", $target_file);
            DEFINE("DEMOFILENAME", $_FILES["fileToUpload"]["name"]);
        } else {
            $uploadOk = 0;
            $errorMsg = "Sorry, there was an error uploading your file.";
        }
    }
    else{
        $errorMsg = "Sorry, there was an error";
        $uploadOk = 0;
    }

    // Now lets try and send the metadata as well using multipart! if file uploaded on local successfully
    if($uploadOk == 1){
        $file = new Google_Service_Drive_DriveFile();
        $file->setName(DEMOFILENAME);
        $result2 = $service->files->create(
            $file,
            array(
                'data' => file_get_contents(DEMOFILE),
                'mimeType' => mime_content_type(DEMOFILE), //'image/jpeg',
                'uploadType' => 'multipart'
            )
        );
    }

}

?>


<div class="box">
    <?php if (isset($authUrl)): ?>
        <div class="">
            <center><span class="warn">Warrning!</span> : <strong>For upload a photo on google drive first you have to connect with drive. click below link to connect with drive using your gmail account.</strong></center><br>
            <a class='login' href='<?= $authUrl ?>'><img src="google.png" alt="Sign up with Google" title="Sign up with Google"></a>
        </div>
    <?php elseif($_SERVER['REQUEST_METHOD'] == 'POST'): ?>

        <?php if ($uploadOk == 0){ ?>
            <div>
                <p class="warn"><?= $errorMsg; ?></p>
                <a href='upload-file.php'>Try with diffrent file</a>
            </div>
        <?php }else{ ?>
            <div>
                <p>Your call was successful! Check your drive for the following files:</p>
                <ul>
                    <li><a href="https://drive.google.com/open?id=<?= $result2->id ?>" target="_blank"><?= $result2->name ?></a></li>
                </ul>
                <a href='upload-file.php'>Upload more files</a>
            </div>
        <?php } ?>
    <?php else: ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="fileToUpload" required="required">
            <input type="submit" name="submit" value="Click here to upload two small (1MB) test files" />
        </form>
    <?php endif ?>
</div>
<?php

require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../src/RaspAP/Auth/HTTPAuth.php';
require_once '../../includes/authenticate.php';

define("IMAGE_DIR", $_SERVER['DOCUMENT_ROOT'] . '/app/img/avatars/');
define("MAX_UPLOAD_MB", 2); // php.ini default MAX_FILE_SIZE = 2 MB
// map of permitted image types to the extension used for the stored file
$allowedTypes = [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_GIF  => 'gif',
    IMAGETYPE_PNG  => 'png',
];

if (!is_dir(IMAGE_DIR)) {
  mkdir(IMAGE_DIR, 0777, true);
}

// set default response status
$response = ['status' => 'failed'];

if (isset($_POST['csrf_token'])) {
    if (isset($_FILES['uploadAvatar'])) {
        $csrf_token = $_POST['csrf_token'];
        $fileUpload = $_FILES['uploadAvatar'];
        if ($fileUpload['error'] === UPLOAD_ERR_OK) {
            if ($fileUpload['size'] <= MAX_UPLOAD_MB * 1024 * 1024) {
                $imageType = exif_imagetype($fileUpload['tmp_name']);
                if ($imageType !== false && isset($allowedTypes[$imageType])) {
                    // derive the extension from the detected image type, never from the client-supplied filename
                    $fileName = hash("md5", uniqid()) . "." . $allowedTypes[$imageType];
                    $targetFile = rtrim(trim(IMAGE_DIR), '/') . '/' . $fileName;
                    if (move_uploaded_file($fileUpload['tmp_name'], $targetFile)) {
                        $response['status'] = 'ok';
                        $response['message'] = "User avatar uploaded successfully";
                        $response['uploaded'] = getAvatarUrl($fileName);
                        setcookie("avatar", getAvatarUrl($fileName), time()+60*60*24*30, '/');
                    } else {
                        $response['message'] = "Unable to save uploaded avatar, check permissions on " . IMAGE_DIR;
                    }
                } else {
                    $response['message'] = "Only files of type JPG, GIF and PNG are allowed";
                }
            } else {
                $response['message'] = "Uploaded file exceeds " .MAX_UPLOAD_MB. " MB";
            }
        } else {
            $response['message'] = "An error occured during upload";
        }
    } else {
        $response['message'] = "The uploaded file cannot be found";
    }
} else {
    handleInvalidCSRFToken();
}

header('Content-Type: application/json');
echo json_encode($response);

function getAvatarUrl($name) {
    return "app/img/avatars/$name";
}


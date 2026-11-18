<?php

function catchit_save_item_image_upload($file, &$errors)
{
    if ($file === null || !isset($file["error"])) {
        return null;
    }

    if ($file["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => "The image exceeds the server's upload limit.",
            UPLOAD_ERR_FORM_SIZE => "The image exceeds the form's upload limit.",
            UPLOAD_ERR_PARTIAL => "The image was only partially uploaded. Please try again.",
            UPLOAD_ERR_NO_TMP_DIR => "The server could not process the uploaded image.",
            UPLOAD_ERR_CANT_WRITE => "The server could not save the uploaded image.",
            UPLOAD_ERR_EXTENSION => "The server stopped the image upload."
        ];
        $errors[] = $upload_errors[$file["error"]]
            ?? "Image upload failed. Please try again.";
        return null;
    }

    if (
        !isset($file["tmp_name"], $file["size"]) ||
        !is_uploaded_file($file["tmp_name"])
    ) {
        $errors[] = "The uploaded image could not be verified.";
        return null;
    }

    $maximum_size = 2 * 1024 * 1024;
    $actual_size = filesize($file["tmp_name"]);

    if ($actual_size === false || $actual_size > $maximum_size) {
        $errors[] = "Image size must be 2 MB or less.";
        return null;
    }

    $allowed_types = [
        "image/jpeg" => ["jpg", IMAGETYPE_JPEG],
        "image/png" => ["png", IMAGETYPE_PNG],
        "image/webp" => ["webp", IMAGETYPE_WEBP]
    ];

    if (!class_exists("finfo")) {
        $errors[] = "Image validation is unavailable on the server.";
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file["tmp_name"]);
    $image_info = @getimagesize($file["tmp_name"]);

    if (
        !isset($allowed_types[$mime_type]) ||
        $image_info === false ||
        $image_info["mime"] !== $mime_type ||
        $image_info[2] !== $allowed_types[$mime_type][1]
    ) {
        $errors[] = "Only valid JPG, PNG and WEBP images are allowed.";
        return null;
    }

    $upload_directory = dirname(__DIR__)
        . DIRECTORY_SEPARATOR . "uploads"
        . DIRECTORY_SEPARATOR . "items"
        . DIRECTORY_SEPARATOR;

    if (
        !is_dir($upload_directory) &&
        !@mkdir($upload_directory, 0755, true) &&
        !is_dir($upload_directory)
    ) {
        $errors[] = "The server could not prepare image storage.";
        return null;
    }

    $extension = $allowed_types[$mime_type][0];
    $image_name = bin2hex(random_bytes(16)) . "." . $extension;
    $destination = $upload_directory . $image_name;

    if (file_exists($destination)) {
        $errors[] = "The image could not be stored. Please try again.";
        return null;
    }

    if (!@move_uploaded_file($file["tmp_name"], $destination)) {
        $errors[] = "Unable to save the uploaded image.";
        return null;
    }

    return $image_name;
}


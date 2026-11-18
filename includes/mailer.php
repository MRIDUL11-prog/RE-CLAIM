
<?php

// =============================================
// RE:CLAIM EMAIL SYSTEM
// =============================================

// Load Gmail SMTP configuration
require_once dirname(__DIR__) . "/config/mail.php";

// Load PHPMailer
$autoload = dirname(__DIR__) . "/vendor/autoload.php";

if (is_file($autoload)) {
    require_once $autoload;
}


// =============================================
// EMAIL LOGGING
// =============================================

function catchit_mail_log($event)
{
    error_log("Re:Claim email: " . $event);
}


// =============================================
// APPLICATION BASE URL
// =============================================

function catchit_base_url()
{
    $configured_url = trim((string) getenv("APP_BASE_URL"));

    if (
        getenv("CATCHIT_ENV") === "production" &&
        $configured_url === ""
    ) {
        catchit_mail_log(
            "APP_BASE_URL must be configured in production."
        );

        return null;
    }

    if ($configured_url !== "") {

        $url = rtrim($configured_url, "/");

    } else {

        $host = $_SERVER["HTTP_HOST"] ?? "";

        if (
            !preg_match(
                '/\A[a-zA-Z0-9.-]+(?::[0-9]{1,5})?\z/',
                $host
            )
        ) {
            catchit_mail_log("Invalid request host.");

            return null;
        }

        $script_directory = str_replace(
            "\\",
            "/",
            dirname($_SERVER["SCRIPT_NAME"] ?? "/")
        );

        $base_path = preg_replace(
            '~/(student|admin)$~',
            "",
            $script_directory
        );

        $scheme = (
            !empty($_SERVER["HTTPS"]) &&
            $_SERVER["HTTPS"] !== "off"
        ) ? "https" : "http";

        $url = $scheme . "://" . $host .
            rtrim($base_path, "/");
    }

    $url_parts = parse_url($url);

    if (
        !filter_var($url, FILTER_VALIDATE_URL) ||
        $url_parts === false ||
        empty($url_parts["host"]) ||
        isset($url_parts["user"]) ||
        isset($url_parts["pass"]) ||
        isset($url_parts["query"]) ||
        isset($url_parts["fragment"]) ||
        !in_array(
            strtolower($url_parts["scheme"] ?? ""),
            ["http", "https"],
            true
        )
    ) {
        catchit_mail_log("Invalid APP_BASE_URL.");

        return null;
    }

    if (
        getenv("CATCHIT_ENV") === "production" &&
        stripos($url, "https://") !== 0
    ) {
        catchit_mail_log(
            "HTTPS is required in production."
        );

        return null;
    }

    return $url;
}


// =============================================
// COMMON EMAIL DESIGN
// =============================================

function catchit_email_layout(
    $preheader,
    $heading,
    $intro,
    $content,
    $action_url = null,
    $action_label = null,
    $footer_message = "This is an automated email from Re:Claim."
) {

    $safe_preheader = htmlspecialchars(
        $preheader,
        ENT_QUOTES,
        "UTF-8"
    );

    $safe_heading = htmlspecialchars(
        $heading,
        ENT_QUOTES,
        "UTF-8"
    );

    $safe_intro = htmlspecialchars(
        $intro,
        ENT_QUOTES,
        "UTF-8"
    );

    $safe_footer = htmlspecialchars(
        $footer_message,
        ENT_QUOTES,
        "UTF-8"
    );

    $action = "";

    if (
        $action_url !== null &&
        $action_label !== null
    ) {

        $safe_url = htmlspecialchars(
            $action_url,
            ENT_QUOTES,
            "UTF-8"
        );

        $safe_label = htmlspecialchars(
            $action_label,
            ENT_QUOTES,
            "UTF-8"
        );

        $action =
            '<p style="margin:28px 0 8px;text-align:center">' .
            '<a href="' . $safe_url . '" ' .
            'style="display:inline-block;padding:14px 24px;' .
            'border-radius:8px;background:#2878c8;' .
            'color:#ffffff;font-weight:700;' .
            'text-decoration:none">' .
            $safe_label .
            '</a></p>';
    }

    return
        '<!doctype html>' .
        '<html lang="en">' .
        '<head>' .
        '<meta charset="utf-8">' .
        '<meta name="viewport" content="width=device-width,initial-scale=1">' .
        '<title>' . $safe_heading . '</title>' .
        '</head>' .

        '<body style="margin:0;padding:24px;background:#f3f5fc;' .
        'font-family:Arial,Helvetica,sans-serif;color:#17365d">' .

        '<span style="display:none!important;visibility:hidden;' .
        'opacity:0;height:0;width:0;overflow:hidden">' .
        $safe_preheader .
        '</span>' .

        '<table role="presentation" width="100%" cellspacing="0" cellpadding="0">' .
        '<tr><td align="center">' .

        '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" ' .
        'style="max-width:600px;background:#ffffff;' .
        'border-radius:16px;overflow:hidden">' .

        // Header
        '<tr><td style="padding:24px 32px;background:#17365d">' .

        '<div style="font-size:26px;font-weight:800;' .
        'letter-spacing:-1px;color:#ffffff">' .
        'RE:CLAIM' .
        '</div>' .

        '<div style="margin-top:5px;font-size:12px;' .
        'letter-spacing:1.5px;color:#d5e8ff">' .
        'CAMPUS LOST &amp; FOUND' .
        '</div>' .

        '</td></tr>' .

        // Main content
        '<tr><td style="padding:32px">' .

        '<h1 style="margin:0 0 14px;font-size:24px;color:#17365d">' .
        $safe_heading .
        '</h1>' .

        '<p style="margin:0 0 22px;line-height:1.65;color:#41445a">' .
        $safe_intro .
        '</p>' .

        $content .
        $action .

        '<p style="margin:28px 0 0;font-size:13px;' .
        'line-height:1.6;color:#65677a">' .
        $safe_footer .
        '</p>' .

        '</td></tr>' .

        // Footer
        '<tr><td style="padding:18px 32px;background:#f7f8ff;' .
        'color:#65677a;font-size:12px">' .
        'Sent by Re:Claim Campus Lost &amp; Found' .
        '</td></tr>' .

        '</table>' .
        '</td></tr>' .
        '</table>' .

        '</body></html>';
}


// =============================================
// EMAIL DETAILS TABLE
// =============================================

function catchit_email_details_table($details)
{
    $html =
        '<table role="presentation" width="100%" ' .
        'cellspacing="0" cellpadding="0" ' .
        'style="border-collapse:collapse;background:#f7f8ff;' .
        'border-radius:10px">';

    foreach ($details as $label => $value) {

        $html .=
            '<tr>' .

            '<td style="padding:11px 14px;' .
            'border-bottom:1px solid #e8e9f4;' .
            'font-size:13px;color:#65677a">' .

            htmlspecialchars(
                $label,
                ENT_QUOTES,
                "UTF-8"
            ) .

            '</td>' .

            '<td style="padding:11px 14px;' .
            'border-bottom:1px solid #e8e9f4;' .
            'font-size:14px;font-weight:600;color:#17365d">' .

            htmlspecialchars(
                (string) $value,
                ENT_QUOTES,
                "UTF-8"
            ) .

            '</td>' .
            '</tr>';
    }

    return $html . '</table>';
}


// =============================================
// COMMON SMTP EMAIL SENDER
// =============================================

function catchit_send_email(
    $recipient,
    $recipient_name,
    $subject,
    $html,
    $purpose
) {

    if (
        !class_exists(
            \PHPMailer\PHPMailer\PHPMailer::class
        )
    ) {
        catchit_mail_log("PHPMailer could not be loaded.");

        return false;
    }

    $host = trim((string) getenv("SMTP_HOST"));

    $sender = trim(
        (string) getenv("SMTP_FROM_EMAIL")
    );

    $sender_name = trim(
        (string) (
            getenv("SMTP_FROM_NAME") ?: "Re:Claim"
        )
    );

    if (
        $host === "" ||
        $sender === "" ||
        !filter_var($sender, FILTER_VALIDATE_EMAIL) ||
        !filter_var($recipient, FILTER_VALIDATE_EMAIL)
    ) {
        catchit_mail_log(
            "SMTP configuration or recipient address is invalid (" .
            $purpose . ")."
        );

        return false;
    }

    $port = filter_var(
        getenv("SMTP_PORT") ?: 587,
        FILTER_VALIDATE_INT,
        [
            "options" => [
                "min_range" => 1,
                "max_range" => 65535
            ]
        ]
    );

    $username = (string) (
        getenv("SMTP_USERNAME") ?: ""
    );

    $password = (string) (
        getenv("SMTP_PASSWORD") ?: ""
    );

    if (
        $port === false ||
        (($username === "") !== ($password === ""))
    ) {
        catchit_mail_log("Invalid SMTP authentication settings.");

        return false;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host = $host;
        $mail->Port = $port;
        $mail->Timeout = 15;

        $mail->CharSet = "UTF-8";

        $mail->SMTPAuth = ($username !== "");

        if ($mail->SMTPAuth) {

            $mail->Username = $username;
            $mail->Password = $password;
        }

        $encryption = strtolower(
            trim(
                (string) (
                    getenv("SMTP_ENCRYPTION") ?: "tls"
                )
            )
        );

        if ($encryption === "tls") {

            $mail->SMTPSecure =
                \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

        } elseif ($encryption === "ssl") {

            $mail->SMTPSecure =
                \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;

        } elseif ($encryption !== "none") {

            catchit_mail_log("Invalid SMTP encryption setting.");

            return false;

        } elseif (getenv("CATCHIT_ENV") === "production") {

            catchit_mail_log(
                "SMTP encryption is required in production."
            );

            return false;
        }

        $mail->setFrom($sender, $sender_name);

        $mail->addAddress(
            $recipient,
            $recipient_name
        );

        $mail->isHTML(true);

        $mail->Subject = $subject;
        $mail->Body = $html;

        $mail->AltBody = trim(
            preg_replace(
                '/\s+/',
                ' ',
                strip_tags($html)
            )
        );

        $mail->send();

        return true;

    } catch (\PHPMailer\PHPMailer\Exception $exception) {

        catchit_mail_log(
            "SMTP delivery failed (" . $purpose . "): " .
            $mail->ErrorInfo
        );

        return false;
    }
}


// =============================================
// ITEM REPORT EMAILS
// =============================================

function catchit_send_report_emails($report)
{
    $type = ucfirst($report["item_type"]);

    $details = [

        "Student" => $report["student_name"],

        "Item" => $report["item_name"],

        "Category" => $report["category_name"],

        "Location" => $report["location"],

        "Report type" => $type,

        "Submission date" => date("F j, Y")
    ];

    $details_html = catchit_email_details_table(
        $details
    );

    // Student confirmation email
    $student_html = catchit_email_layout(

        "Your " . strtolower($type) .
        " item report was submitted.",

        "Report submitted successfully",

        "Hi " . $report["student_name"] .
        ", your report has been saved and submitted for review.",

        $details_html
    );

    $student_sent = catchit_send_email(

        $report["student_email"],

        $report["student_name"],

        "Your Re:Claim " .
        strtolower($type) .
        " report was submitted",

        $student_html,

        "student report confirmation"
    );


    // Admin notification email
    $base_url = catchit_base_url();

    $admin_email = trim(
        (string) getenv("CATCHIT_ADMIN_EMAIL")
    );

    $admin_sent = false;

    if (
        $base_url !== null &&
        $admin_email !== ""
    ) {

        $admin_html = catchit_email_layout(

            "A new " . strtolower($type) .
            " item report needs review.",

            "New " . strtolower($type) .
            " item report",

            "A student has submitted a new report for review.",

            $details_html,

            $base_url . "/admin/manage_items.php",

            "Review item reports"
        );

        $admin_sent = catchit_send_email(

            $admin_email,

            "Re:Claim administrator",

            "New Re:Claim " .
            strtolower($type) .
            " item report",

            $admin_html,

            "admin report notification"
        );

    } else {

        catchit_mail_log(
            "Admin notification could not be sent because its email or APP_BASE_URL is missing."
        );
    }

    return [

        "student" => $student_sent,

        "admin" => $admin_sent
    ];
}


// =============================================
// PASSWORD RESET EMAIL
// =============================================

function catchit_send_password_reset_email(
    $email,
    $name,
    $reset_url
) {

    $link =
        '<p style="margin:0 0 12px;font-size:13px;' .
        'line-height:1.6;color:#65677a">' .

        'This link expires in one hour and can only be used once. ' .
        'If the button does not work, copy and paste this address into your browser:' .

        '<br>' .

        '<a style="color:#2878c8;word-break:break-all" href="' .

        htmlspecialchars(
            $reset_url,
            ENT_QUOTES,
            "UTF-8"
        ) .

        '">' .

        htmlspecialchars(
            $reset_url,
            ENT_QUOTES,
            "UTF-8"
        ) .

        '</a></p>';


    $html = catchit_email_layout(

        "Reset your Re:Claim password using a secure, single-use link.",

        "Re:Claim Password Reset",

        "Hello " . $name .
        ", we received a request to reset your Re:Claim account password. " .
        "Click the button below to create a new password.",

        $link,

        $reset_url,

        "Reset My Password",

        "If you did not request a password reset, please ignore this email."
    );


    return catchit_send_email(

        $email,

        $name,

        "Re:Claim - Password Reset Request",

        $html,

        "password reset"
    );
}

// =============================================
// PREMIUM CLAIM APPROVAL EMAIL
// RE:CLAIM - CAMPUS LOST & FOUND
// =============================================

function catchit_send_claim_approval_email(
    $email,
    $name,
    $item_name,
    $claim_id
) {

    // Secure dynamic values
    $safe_name = htmlspecialchars(
        $name,
        ENT_QUOTES,
        "UTF-8"
    );

    $safe_item = htmlspecialchars(
        $item_name,
        ENT_QUOTES,
        "UTF-8"
    );

    $safe_claim_id = htmlspecialchars(
        (string) $claim_id,
        ENT_QUOTES,
        "UTF-8"
    );


    // =============================================
    // PREMIUM EMAIL CONTENT
    // =============================================

    $html = '

    <!DOCTYPE html>
    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <meta name="viewport"
              content="width=device-width, initial-scale=1.0">

        <title>Claim Approved - Re:Claim</title>

    </head>


    <body style="
        margin:0;
        padding:0;
        background:#f1f4f9;
        font-family:Arial,Helvetica,sans-serif;
        color:#26344d;
    ">

    <!-- PREHEADER -->

    <div style="
        display:none;
        max-height:0;
        overflow:hidden;
        opacity:0;
    ">

        Great news! Your Re:Claim request has been approved.

    </div>


    <!-- MAIN WRAPPER -->

    <table
        width="100%"
        border="0"
        cellspacing="0"
        cellpadding="0"
        style="background:#f1f4f9;padding:35px 12px;"
    >

        <tr>

            <td align="center">


                <!-- EMAIL CARD -->

                <table
                    width="100%"
                    border="0"
                    cellspacing="0"
                    cellpadding="0"
                    style="
                        max-width:620px;
                        background:#ffffff;
                        border-radius:18px;
                        overflow:hidden;
                        box-shadow:0 8px 30px rgba(20,43,80,0.08);
                    "
                >


                    <!-- BRAND HEADER -->

                    <tr>

                        <td
                            align="center"
                            style="
                                background:#142b50;
                                padding:35px 25px;
                            "
                        >

                            <div style="
                                font-size:30px;
                                font-weight:800;
                                letter-spacing:2px;
                                color:#ffffff;
                            ">

                                RE:<span style="color:#e4b85f;">CLAIM</span>

                            </div>


                            <div style="
                                margin-top:8px;
                                font-size:11px;
                                letter-spacing:3px;
                                color:#cbd8eb;
                            ">

                                CAMPUS LOST &amp; FOUND

                            </div>


                            <div style="
                                width:55px;
                                height:3px;
                                background:#e4b85f;
                                margin:20px auto 0;
                                border-radius:5px;
                            "></div>

                        </td>

                    </tr>



                    <!-- SUCCESS SECTION -->

                    <tr>

                        <td
                            align="center"
                            style="padding:40px 30px 20px;"
                        >

                            <!-- SUCCESS ICON -->

                            <div style="
                                width:82px;
                                height:82px;
                                line-height:82px;
                                border-radius:50%;
                                background:#e5f7ed;
                                color:#20965b;
                                font-size:43px;
                                font-weight:bold;
                                text-align:center;
                                margin:0 auto 22px;
                            ">

                                &#10003;

                            </div>


                            <h1 style="
                                margin:0;
                                font-size:28px;
                                color:#17365d;
                                font-weight:800;
                            ">

                                Claim Approved!

                            </h1>


                            <p style="
                                margin:15px 0 0;
                                font-size:15px;
                                line-height:1.8;
                                color:#68758a;
                            ">

                                Congratulations! Your claim request
                                has been successfully approved by
                                the administrator.

                            </p>

                        </td>

                    </tr>



                    <!-- PERSONAL GREETING -->

                    <tr>

                        <td style="padding:15px 35px 25px;">

                            <p style="
                                margin:0;
                                font-size:16px;
                                color:#26344d;
                                line-height:1.7;
                            ">

                                Hello,

                                <strong style="color:#17365d;">

                                    ' . $safe_name . '

                                </strong>,

                            </p>


                            <p style="
                                margin:10px 0 0;
                                font-size:14px;
                                color:#68758a;
                                line-height:1.8;
                            ">

                                We are happy to inform you that
                                your claim has been reviewed and
                                approved. Here are your claim details:

                            </p>

                        </td>

                    </tr>



                    <!-- CLAIM DETAILS CARD -->

                    <tr>

                        <td style="padding:0 25px 28px;">

                            <table
                                width="100%"
                                border="0"
                                cellspacing="0"
                                cellpadding="0"
                                style="
                                    background:#f7f9fd;
                                    border:1px solid #e7ecf4;
                                    border-radius:12px;
                                    overflow:hidden;
                                "
                            >

                                <tr>

                                    <td
                                        colspan="2"
                                        style="
                                            padding:18px 20px;
                                            background:#eaf1fb;
                                            color:#17365d;
                                            font-size:13px;
                                            font-weight:bold;
                                            letter-spacing:1px;
                                        "
                                    >

                                        YOUR CLAIM DETAILS

                                    </td>

                                </tr>


                                <!-- ITEM NAME -->

                                <tr>

                                    <td style="
                                        padding:17px 20px;
                                        color:#718096;
                                        font-size:13px;
                                        border-bottom:1px solid #e7ecf4;
                                    ">

                                        Item Name

                                    </td>


                                    <td align="right" style="
                                        padding:17px 20px;
                                        color:#17365d;
                                        font-size:14px;
                                        font-weight:bold;
                                        border-bottom:1px solid #e7ecf4;
                                        word-break:break-word;
                                    ">

                                        ' . $safe_item . '

                                    </td>

                                </tr>



                                <!-- CLAIM ID -->

                                <tr>

                                    <td style="
                                        padding:17px 20px;
                                        color:#718096;
                                        font-size:13px;
                                        border-bottom:1px solid #e7ecf4;
                                    ">

                                        Claim ID

                                    </td>


                                    <td align="right" style="
                                        padding:17px 20px;
                                        color:#17365d;
                                        font-size:14px;
                                        font-weight:bold;
                                        border-bottom:1px solid #e7ecf4;
                                    ">

                                        #' . $safe_claim_id . '

                                    </td>

                                </tr>



                                <!-- STATUS -->

                                <tr>

                                    <td style="
                                        padding:18px 20px;
                                        color:#718096;
                                        font-size:13px;
                                    ">

                                        Approval Status

                                    </td>


                                    <td align="right" style="
                                        padding:18px 20px;
                                    ">

                                        <span style="
                                            display:inline-block;
                                            background:#dcf5e7;
                                            color:#16834c;
                                            padding:8px 15px;
                                            border-radius:20px;
                                            font-size:12px;
                                            font-weight:bold;
                                            letter-spacing:0.5px;
                                        ">

                                            &#10003; APPROVED

                                        </span>

                                    </td>

                                </tr>


                            </table>

                        </td>

                    </tr>



                    <!-- SUCCESS MESSAGE -->

                    <tr>

                        <td style="padding:0 25px 35px;">

                            <table
                                width="100%"
                                border="0"
                                cellspacing="0"
                                cellpadding="0"
                                style="
                                    background:#edf5ff;
                                    border-left:4px solid #2878c8;
                                    border-radius:8px;
                                "
                            >

                                <tr>

                                    <td style="
                                        padding:20px;
                                    ">

                                        <div style="
                                            font-size:15px;
                                            font-weight:bold;
                                            color:#17365d;
                                            margin-bottom:8px;
                                        ">

                                            Great News!

                                        </div>


                                        <div style="
                                            font-size:13px;
                                            line-height:1.8;
                                            color:#536782;
                                        ">

                                            Your claim has been approved.
                                            Please contact the administrator
                                            for any further assistance
                                            regarding the item collection.

                                        </div>

                                    </td>

                                </tr>

                            </table>

                        </td>

                    </tr>



                    <!-- CLOSING MESSAGE -->

                    <tr>

                        <td style="
                            padding:0 35px 35px;
                        ">

                            <p style="
                                margin:0;
                                font-size:14px;
                                line-height:1.8;
                                color:#68758a;
                            ">

                                Thank you for using

                                <strong style="color:#17365d;">
                                    Re:Claim
                                </strong>.

                                We hope you have a wonderful day!

                            </p>


                            <p style="
                                margin:18px 0 0;
                                font-size:14px;
                                color:#17365d;
                                font-weight:bold;
                            ">

                                Best Regards,<br>

                                Re:Claim Team

                            </p>

                        </td>

                    </tr>



                    <!-- FOOTER -->

                    <tr>

                        <td
                            align="center"
                            style="
                                background:#142b50;
                                padding:25px 20px;
                            "
                        >

                            <div style="
                                font-size:20px;
                                font-weight:800;
                                letter-spacing:1px;
                                color:#ffffff;
                            ">

                                RE:<span style="color:#e4b85f;">CLAIM</span>

                            </div>


                            <p style="
                                margin:9px 0;
                                font-size:12px;
                                color:#cbd8eb;
                                line-height:1.7;
                            ">

                                Making Lost Things Found Again.

                            </p>


                            <div style="
                                width:35px;
                                height:1px;
                                background:#e4b85f;
                                margin:15px auto;
                            "></div>


                            <p style="
                                margin:0;
                                font-size:11px;
                                color:#aebed5;
                                line-height:1.7;
                            ">

                                This is an automated email notification.
                                Please do not reply to this email.

                            </p>


                            <p style="
                                margin:12px 0 0;
                                font-size:11px;
                                color:#8297b6;
                            ">

                                &copy; ' . date("Y") . '
                                Re:Claim Campus Lost &amp; Found.

                            </p>

                        </td>

                    </tr>


                </table>


            </td>

        </tr>

    </table>


    </body>

    </html>

    ';


    // =============================================
    // SEND EMAIL USING EXISTING SMTP SYSTEM
    // =============================================

    return catchit_send_email(

        $email,

        $name,

        "Re:Claim - Congratulations! Your Claim Has Been Approved",

        $html,

        "claim approval notification"

    );

}

?>

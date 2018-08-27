<?php 


function sendEmail($to = '', $cc = '', $subject, $data)
{
    require_once('PHPMailer-master/PHPMailerAutoload.php'); //¡¡¡¡¡

    //Create a new PHPMailer instance
    $mail = new PHPMailer;
    //Tell PHPMailer to use SMTP
    $mail->IsSMTP();
    $mail->SMTPAuth = true;

    //Set the hostname of the mail server
    $mail->Host = "";
    // $mail->Host = "mail.condortravel.com";
    //Set the SMTP port number - likely to be 25, 465 or 587
    $mail->Port = 587;
    //Whether to use SMTP authentication
    $mail->CharSet = 'UTF-8';
    //Username to use for SMTP authentication
    $mail->Username = "";
    // $mail->Username = "condor-link";
    //Password to use for SMTP authentication
    $mail->Password = "";
    // $mail->Password = "con..,FTk2o18.";



    // die;

    try {
        
        $mail->setFrom('info@com', 'Test');
        //Set an alternative reply-to address
        //$mail->addReplyTo('info@confielms.com', 'Confie LMS');
        //Set who the message is to be sent to
        $mail->addAddress('test@', 'Test');
                
        //Set the subject line
        $mail->Subject = 'PHPMailer SMTP test';
        //Read an HTML message body from an external file, convert referenced images to embedded,
        //convert HTML into a basic plain-text alternative body
        //$mail->msgHTML($data   , dirname(__FILE__));
        $mail->msgHTML(loadHtmlContent($data));
        //Replace the plain text body with one created manually
        $mail->AltBody = 'This is a plain-text message body';
        //Attach an image file
        //$mail->addAttachment('images/phpmailer_mini.png');

        //send the message, check for errors
        $mail->send();
    } catch (phpmailerException $e) {
        if (!DOUBLE_CORE && DEPURACION) {
            echo $e->errorMessage();
        }
    } catch (Exception $e) {
        if (!DOUBLE_CORE && DEPURACION) {
            echo $e->getMessage();
        }
    }
}
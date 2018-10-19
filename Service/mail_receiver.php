<?php
require_once '../Swift/lib/swift_required.php';

try {
    $data = json_decode(urldecode($_REQUEST['data']), true);

    $message = new Swift_Message();

    if (isset($data['textHeaders'])) {
        foreach ($data['textHeaders'] as $h => $v) {
            $message->getHeaders()->addTextHeader($h, $v);
        }

        unset($data['textHeaders']);
    }

    if (isset($data['vars']) && is_array($data['vars'])) {
        $vars = $data['vars'];
        unset($data['vars']);
    }

    foreach ($data as $method => $value) {
        $method = str_replace('get', 'set', $method);
        $message->$method($value);
    }
    $message->setPriority(3);

    $transport = Swift_SmtpTransport::newInstance('localhost', 25);
    $mailer = Swift_Mailer::newInstance($transport);
    $failed = [];
    $log = '';

    if (isset($vars)) {
        $body = $message->getBody();
        foreach ($vars as $email => $rp) {
            $message->setTo($email);
            $message->setBody(strtr($body, $rp));
            if (isset($rp['%List-Unsubscribe%'])) {
                $message->getHeaders()->addTextHeader('List-Unsubscribe', "<{$rp['%List-Unsubscribe%']}>");
            }
            try {
                $mailer->send($message);
            } catch (Exception $e) {
                file_put_contents('log/exceptions', $e->getMessage()."\n");
                $failed[] = $email;
                $log .= $e->getMessage() . " \n";
            }
        }
    } else {
        try {
            $mailer->send($message);
        } catch (Exception $e) {
            file_put_contents('log/exceptions', $e->getMessage()."\n");
            // $failed = array_keys($message->getTo()); тут надо корректно извлечь массив email из заголовка письма
        }
    }
    $response = [
        'status' => 'success',
        'message' => $log,
    ];
    if (sizeof($failed)) {
        $response['failed_recipients'] = $failed;
    }
} catch (Exception $e) {
    file_put_contents('log/exceptions', $e->getMessage()."\n");
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
    ];
}

echo json_encode($response);

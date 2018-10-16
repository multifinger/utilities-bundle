<?php

//require_once '../lib/vendor/symfony/lib/vendor/swiftmailer/swift_required.php';
require_once '../Swift/lib/swift_required.php';

try
{
    $data = json_decode( urldecode( $_REQUEST['data'] ), true );

    $message = new Swift_Message();

    if ( isset( $data['textHeaders'] ) )
    {
        foreach( $data['textHeaders'] as $h => $v )
        {
            $message->getHeaders()->addTextHeader( $h, $v );
        }

        unset( $data['textHeaders'] );
    }

    // html workaround
    if ( 0 && $data['getContentType'] == 'text/html' )
    {
        $plain = strip_tags( $data['getBody'] );
        $html  = $data['getBody'];
        $message->addPart( $plain, 'text/plain' );
        $message->addPart( $html, 'text/html' );
        unset( $data['getBody'] );
        $data['getContentType'] = 'multipart/alternative';
    }

    foreach ( $data as $method => $value )
    {
        $method = str_replace( 'get', 'set', $method );
        $message->$method( $value );
    }

    $message->setPriority( 3 );
//  $message->setEncoder( new Swift_Mime_ContentEncoder_PlainContentEncoder() );

    $transport = Swift_SmtpTransport::newInstance('localhost', 25);
    $mailer = Swift_Mailer::newInstance($transport);
    $mailer->send( $message );
}
catch ( Exception $e )
{
    $data = $e->getMessage() . "\n";

    file_put_contents( 'log/exceptions', $data );
}

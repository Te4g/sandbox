<?php

use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\MessageComponentInterface;
use Ratchet\WebSocket\WsServer;

require_once 'vendor/autoload.php';

class WebsocketHandler implements MessageComponentInterface
{
    function onOpen(ConnectionInterface $conn)
    {
        $conn->send('Hello');
    }

    function onClose(ConnectionInterface $conn)
    {
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    public function onMessage(ConnectionInterface $conn, MessageInterface $msg)
    {
        echo "Message received: {$msg}\n";
        dump('isBinary', $msg->isBinary());

        // save binary data
        $file = fopen('test.mp4', 'a');
        fwrite($file, $msg->getPayload());
        fclose($file);

        $conn->send($msg);
        // ffmpeg read binary data to convert to mp4
        //shell_exec("ffmpeg -i {$msg->getPayload()} test.mp4");
    }
}

function saveImage($base64_image_string, $output_file_without_extention, $path_with_end_slash = ""): void
{
    $splited = explode(',', substr($base64_image_string, 5), 2);
    $mime = $splited[0];
    $data = $splited[1];

    $mime_split_without_base64 = explode(';', $mime, 2);
    $mime_split = explode('/', $mime_split_without_base64[0], 2);
    if (count($mime_split) === 2) {
        $extension = $mime_split[1];
        if ($extension === 'jpeg') $extension = 'jpg';
        $output_file_without_extention .= '.' . $extension;
    }
    file_put_contents($path_with_end_slash . $output_file_without_extention, base64_decode($data));
}

$server = IoServer::factory(new HttpServer(new WsServer(new WebsocketHandler())), 8087);

$server->run();

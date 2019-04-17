<?php
/**
 * Eric Draken
 * Date: 2016-09-02
 * Time: 4:44 PM
 * Desc: Callback for responding to Line messages
 *       Send 'whoami' to this endpoint to get a reply with your mid.
 */
 
// I put constants like 'LINE_CHANNEL_ID' here 
//require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . "/../includes/line-bot-sdk/vendor/autoload.php";
 
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\GuzzleHTTPClient;
 
// Set these values
$config = [
    'channelId' => '1565873395',
    'channelSecret' => 'bc0d721e1244a24924584e0d5b93fe9a',
    'channelMid' => '7Q6OsD1su6xYTjR5Y+8tCHBdPQZ7BiOJGAusDg2vkgq0nBfexHwmlm9c5Ae+hsplgUgC90YnXorSoLMlQGkarc8Ilkv1qCdc8elweM1oabnurSMeu6PNzXnHFrRggbBlkOnNV8fB9L85ssw8bRS/CQdB04t89/1O/w1cDnyilFU=',
];
$sdk = new LINEBot($config, new GuzzleHTTPClient($config));
 
$postdata = @file_get_contents("php://input");
$messages = $sdk->createReceivesFromJSON($postdata);
 
// Verify the signature
// REF: http://line.github.io/line-bot-api-doc/en/api/callback/post.html#signature-verification
// REF: http://stackoverflow.com/a/541450
$sigheader = 'X-LINE-ChannelSignature';
$signature = @$_SERVER[ 'HTTP_'.strtoupper(str_replace('-','_',$sigheader)) ];
if($signature && $sdk->validateSignature($postdata, $signature)) {
    // Next, extract the messages
    if(is_array($messages)) {
        foreach ($messages as $message) {
            if ($message instanceof LINEBot\Receive\Message\Text) {
                $text = $message->getText();
                if (strtolower(trim($text)) === "whoami") {
                    $fromMid = $message->getFromMid();
                    $user = $sdk->getUserProfile($fromMid);
                    $displayName = $user['contacts'][0]['displayName'];
 
                    $reply = "You are $displayName, and your mid is:\n\n$fromMid";
 
                    // Send the mid back to the sender and check if the message was delivered
                    $result = $sdk->sendText([$fromMid], $reply);
                    if(!$result instanceof LINE\LINEBot\Response\SucceededResponse) {
                        error_log('LINE error: ' . json_encode($result));
                    }
                } else {
                    // Process normally, or do nothing
                }
            } else {
                // Process other types of LINE messages like image, video, sticker, etc.
            }
        }
    } // Else, error
} else {
    error_log('LINE signatures didn\'t match: ' . $signature);
}

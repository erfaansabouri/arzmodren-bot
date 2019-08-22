<?php

include 'Telegram.php';

require 'vendor/autoload.php';

use Gregwar\Image\Image;

/* TELEGRAM VARIABLES */
$API_KEY = '708339626:AAEuT9rjD97XYvWFm5jMbgtq7Niy2XyPljc';
$telegram = new Telegram($API_KEY);
$result = $telegram->getData();
$text = $result['message']['text'];
$contact = $result['message']['contact'];
$chat_id = $result['message']['chat']['id'];
$user_id = $result['message']['from']['id'];
$first_name = $result['message']['chat']['first_name'];
$last_name = $result['message']['chat']['last_name'];
$username = $result['message']['chat']['username'];

/* TELEGRAM VARIABLES END*/

/* <---------------------------------------------------------> */

/* DATABASE CONNECTION */

$servernameDB = "localhost";
$usernameDB = "biaradio_doge";
$passwordDB = "mydoge";
$dbnameDB = "biaradio_doge";
$connection = new mysqli($servernameDB, $usernameDB, $passwordDB, $dbnameDB);

/* DATABASE CONNECTION END */

/* <---------------------------------------------------------> */

/* KEYBOARDS */

$main_menu_options = array( 
    //First row
    array($telegram->buildKeyboardButton("💳 حساب کاربری من")), 
    //Second row 
    array($telegram->buildKeyboardButton("💵 درخواست برداشت"), $telegram->buildKeyboardButton("🖇 لینک دعوت از دوستان"), $telegram->buildKeyboardButton("🎁 گردونه شانس")), 
    //Third row
    array($telegram->buildKeyboardButton("✅ تایید شماره تلفن همراه" , true))
    );
    
$main_menu_keyboard = $telegram->buildKeyBoard($main_menu_options, $onetime=false , true);


$validPhoneButtons = array( array($telegram->buildKeyboardButton("✅ تایید شماره تلفن همراه" , true)));
$validPhoneKeyboard = $telegram->buildKeyBoard($validPhoneButtons, $onetime=false , true);

$iAmJoinedButton = array( array($telegram->buildKeyboardButton("✅ عضو شدم")));
$iAmJoinedButtonKeyboard = $telegram->buildKeyBoard($iAmJoinedButton, $onetime=false , true);

$cancelButton = array( array($telegram->buildKeyboardButton("لغو عملیات")));
$cancelKeyboard = $telegram->buildKeyBoard($cancelButton, $onetime=false , true);


$hideKeyboard = $telegram->buildKeyBoardHide($selective = true);
/* KEYBOARDS END */

/* <---------------------------------------------------------> */

/*
$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "This is a Keyboard Test");
$telegram->sendMessage($content);

if($telegram->getUpdateType() == 'contact'){
    $content = array('chat_id' => $chat_id, 'text' => $contact['first_name']);
    $telegram->sendMessage($content);
}

*/


/* <---------------------------------------------------------> */
/* BOT FUNCTIONS */

function BotIsTyping($chat_id){
    global $telegram;
    $telegram->sendChatAction(['chat_id' => $chat_id,'action' => 'typing']);
}

function BotSendMessage($chat_id , $text , $parse_mode = null , $reply_to_message_id = null , $reply_markup = null){
    global $telegram;
    BotIsTyping($chat_id);
    $content = [    
                    'chat_id' => $chat_id,
                    'text' => $text,
                    'parse_mode' => $parse_mode,
                    'reply_to_message_id' => $reply_to_message_id,
                    'reply_markup' => $reply_markup
                ];
    $telegram->sendMessage($content);
}


/* BOT FUNCTIONS END */
/* <---------------------------------------------------------> */



/* <---------------------------------------------------------> */
/* FUNCTIONS */

function isStart($text){
    if (substr($text, 0, 6) == "/start") return true;
    else return false;
}

function isValidStart($text){
    global $chat_id;
    return substr($text, 7) != $chat_id ;
}

function isStartWithRefLink($text){
    return strlen($text) > 6;
}


function isUserRegistered($chat_id){
    global $connection;
    $user = "SELECT * FROM users WHERE chat_id = '$chat_id' LIMIT 1";
    $user_query = $connection->query($user);
    return $user_query->num_rows != 0;
}

function addNewUser($chat_id,$username,$first_name,$last_name,$ref_chat_id){
    global $connection;
    $add_new_user = "INSERT INTO users (chat_id,username,first_name,last_name,ref_chat_id) VALUES ('$chat_id' , '$username' , '$first_name' , '$last_name' ,  '$ref_chat_id')";
    $connection->query($add_new_user);
}

function updateRefInvitedCount($ref_chat_id){
    global $connection;
    $updateRefrence = "SELECT * FROM users WHERE chat_id = '$ref_chat_id' LIMIT 1";
    $updateRefrence_query = $connection->query($updateRefrence);
    if($updateRefrence_query->num_rows != 0){
        $update_user = "UPDATE users SET invited_count=invited_count+1 WHERE chat_id='$ref_chat_id'";
        $connection->query($update_user);
    }
}


function validateRefChatId($ref_chat_id){
    global $connection;
    global $chat_id;
    $ref = "SELECT * FROM users WHERE chat_id = '$ref_chat_id' LIMIT 1";
    $ref_query = $connection->query($ref);
    return $ref_query->num_rows != 0 && $ref_chat_id != $chat_id;
}


function isJoined($chat_id){
    global $API_KEY;
    $sponsored_channel = json_decode(file_get_contents("https://api.telegram.org/bot$API_KEY/getChatMember?chat_id=@thisdoge&user_id=".$chat_id));
    $status = $sponsored_channel->result->status;
    return $status == 'member' || $status == 'creator' || $status == 'administrator';
}


function validateContact($contact){
    global $chat_id;
    return $contact['user_id'] == $chat_id && (substr($contact['phone_number'], 0,2)== '98' || substr($contact['phone_number'], 0,3)== '+98');
}

function userHasPhone($chat_id){
   
    
    global $connection;
    
    $phone_number = "SELECT phone_number from users WHERE chat_id = '$chat_id' LIMIT 1";
    $phone_number_query = $connection->query($phone_number);
    $phone_number_row = $phone_number_query->fetch_assoc();
    $phone_number = $phone_number_row["phone_number"];
    
    return !is_null($phone_number) ;
}


function updateUserPhone($chat_id , $phone_number){
    global $connection;
    $update = "UPDATE users SET phone_number='$phone_number' WHERE chat_id='$chat_id'";
    $connection->query($update);
    $update = "UPDATE users SET is_phone_number_set='1' WHERE chat_id='$chat_id'";
    $connection->query($update);
}


function gotJoinedGift($chat_id){
    global $connection;
    
    $got_joined_gift = "SELECT got_joined_gift from users WHERE chat_id = '$chat_id' LIMIT 1";
    $got_joined_gift_query = $connection->query($got_joined_gift);
    $got_joined_gift_row = $got_joined_gift_query->fetch_assoc();
    $got_joined_gift = $got_joined_gift_row["got_joined_gift"];
    
    return $got_joined_gift == 1 ;
}

function sendGift($chat_id,$value){
    global $connection;
    $update = "UPDATE users SET doge_coin=doge_coin+'$value' WHERE chat_id='$chat_id'";
    $connection->query($update);
}

function updateJoinedGift($chat_id){
    global $connection;
    $update = "UPDATE users SET got_joined_gift =1 WHERE chat_id='$chat_id'";
    $connection->query($update);
}

function refGotGift($chat_id){
    global $connection;
    
    $ref_gift_received = "SELECT ref_gift_received from users WHERE chat_id = '$chat_id' LIMIT 1";
    $select_query = $connection->query($ref_gift_received);
    $select_row = $select_query->fetch_assoc();
    $ref_gift_received = $select_row["ref_gift_received"];
    
    return $ref_gift_received == 1 ;
}

function getRefChatId($chat_id){
    global $connection;
    $ref = "SELECT ref_chat_id from users WHERE chat_id = '$chat_id' LIMIT 1";
    $select_query = $connection->query($ref);
    $select_row = $select_query->fetch_assoc();
    return $select_row["ref_chat_id"];
    
}

function hasRef($chat_id){
    global $connection;
    $ref = "SELECT ref_chat_id from users WHERE chat_id = '$chat_id' LIMIT 1";
    $select_query = $connection->query($ref);
    $select_row = $select_query->fetch_assoc();
    return $select_row["ref_chat_id"] != 'nobody';
}


function updateRefGotGift($chat_id){
    global $connection;
    $update = "UPDATE users SET ref_gift_received=1 WHERE chat_id='$chat_id'";
    $connection->query($update);
}


function getBalance($chat_id){
    global $connection;
    $doge_coin = "SELECT doge_coin from users WHERE chat_id = '$chat_id' LIMIT 1";
    $select_query = $connection->query($doge_coin);
    $select_row = $select_query->fetch_assoc();
    return $select_row["doge_coin"];
}

function updateValidInvitedCount($chat_id){
    global $connection;
    $update = "UPDATE users SET valid_invited_count=valid_invited_count+1 WHERE chat_id='$chat_id'";
    $connection->query($update);
}

function getInvitedCount($chat_id){
    global $connection;
    $invitedCount = "SELECT invited_count from users WHERE chat_id = '$chat_id' LIMIT 1";
    $select_query = $connection->query($invitedCount);
    $select_row = $select_query->fetch_assoc();
    return $select_row["invited_count"];
}

function getValidInvitedCount($chat_id){
    global $connection;
    $invitedCount = "SELECT valid_invited_count from users WHERE chat_id = '$chat_id' LIMIT 1";
    $select_query = $connection->query($invitedCount);
    $select_row = $select_query->fetch_assoc();
    return $select_row["valid_invited_count"];
}

function hasValidWheel($chat_id){
    date_default_timezone_set('Iran/Tehran');
    global $connection;
    $lastTry = "SELECT last_wheel_try from users WHERE chat_id = '$chat_id' LIMIT 1";
    $select_query = $connection->query($lastTry);
    $select_row = $select_query->fetch_assoc();
    $lastTry = $select_row["last_wheel_try"];
    $now = time();
    $diff = $now - $lastTry;
    return $diff > 86400;
}

function updateLastTry($chat_id){
    global $connection;
    $now = time();
    $update = "UPDATE users SET last_wheel_try='$now' WHERE chat_id='$chat_id'";
    $connection->query($update);
}

function getWaitTime($chat_id){
    global $connection;
    $lastTry = "SELECT last_wheel_try from users WHERE chat_id = '$chat_id' LIMIT 1";
    $select_query = $connection->query($lastTry);
    $select_row = $select_query->fetch_assoc();
    $lastTry = $select_row["last_wheel_try"];
    $now = time();
    return $diff = floor((86400 - ($now - $lastTry))/3600);
}

function userHasEnoughBalance($chat_id){
    return getBalance($chat_id) >= 200;
}

function withDraw($chat_id , $value){
    global $connection;
    $update = "UPDATE users SET doge_coin=doge_coin-'$value' WHERE chat_id='$chat_id'";
    $connection->query($update);
}

function updateUserIsWithDrawing($chat_id,$status){
    global $connection;
    $update = "UPDATE users SET user_is_withdrawing=$status WHERE chat_id='$chat_id'";
    $connection->query($update);
}

function getUserIsWithDrawing($chat_id){
    global $connection;
    $user_is_withdrawing = "SELECT user_is_withdrawing from users WHERE chat_id = '$chat_id' LIMIT 1";
    $select_query = $connection->query($user_is_withdrawing);
    $select_row = $select_query->fetch_assoc();
    return $select_row["user_is_withdrawing"];
}


function validTransaction($text){
    global $chat_id;
    return preg_replace('/[0-9\.\-]/', '', $text) == "" && (float)$text >= 200 && getBalance($chat_id)  >= (float)$text;
}


function updateLastWithDraw($chat_id,$value){
    global $connection;
    $val = (float)$value;
    $update = "UPDATE users SET last_withdraw=$val WHERE chat_id='$chat_id'";
    $connection->query($update);
}

function getLastWithDraw($chat_id){
    global $connection;
    $user_is_withdrawing = "SELECT last_withdraw from users WHERE chat_id = '$chat_id' LIMIT 1";
    $select_query = $connection->query($user_is_withdrawing);
    $select_row = $select_query->fetch_assoc();
    return $select_row["last_withdraw"];
}

function writeNameOnImage($base_image , $chat_id){
    global $first_name;
    Image::open($base_image)
            ->write('CaviarDreams.ttf', $first_name, 1105, 465, 25, 0, 'white', 'center')
            ->save("images/withName$chat_id.jpg", 'jpg');
}

function saveUserImage($photo){
    global $chat_id;
    global $telegram;
    $file = $telegram->getFile($photo);
    $telegram->downloadFile($file['result']['file_path'], "images/raw$chat_id.png");
    
}

function mergeUserImage($raw_photo_path){
    global $first_name;
    global $chat_id;
    Image::open($raw_photo_path)->resize(470, 470)->save($raw_photo_path);
        $w = 470;  $h=470; // original size
        $original_path=$raw_photo_path;
        $dest_path="images/circule$chat_id.png";
        $src = imagecreatefromstring(file_get_contents($original_path));
        $newpic = imagecreatetruecolor($w,$h);
        imagealphablending($newpic,false);
        $transparent = imagecolorallocatealpha($newpic, 0, 0, 0, 127);
        $r=$w/2;
        for($x=0;$x<$w;$x++)
            for($y=0;$y<$h;$y++){
                $c = imagecolorat($src,$x,$y);
                $_x = $x - $w/2;
                $_y = $y - $h/2;
                if((($_x*$_x) + ($_y*$_y)) < ($r*$r)){
                    imagesetpixel($newpic,$x,$y,$c);
                }else{
                    imagesetpixel($newpic,$x,$y,$transparent);
                }
            }
        imagesavealpha($newpic, true);
        imagepng($newpic, $dest_path);
        imagedestroy($newpic);
        imagedestroy($src);
        Image::open('images/tron.jpg')
        ->merge(Image::open("images/circule$chat_id.png"), $x=384, $y=408, $width=470, $height=470)
        ->write('CaviarDreams.ttf', $first_name, 1105, 465, 25, 0, 'white', 'center')
        ->save("images/withNameImage$chat_id.jpg", 'jpg');

}
/* FUNCTIONS END */
/* <---------------------------------------------------------> */

if (userHasPhone($chat_id)){
    global $main_menu_options;
    $main_menu_options = array( 
    //First row
    array($telegram->buildKeyboardButton("💳 حساب کاربری من")), 
    //Second row 
    array( $telegram->buildKeyboardButton("🖇 لینک دعوت از دوستان"), $telegram->buildKeyboardButton("🎁 گردونه شانس")), 
    //Third row
    array($telegram->buildKeyboardButton("💵 درخواست برداشت")), );
    global $main_menu_keyboard;
    $main_menu_keyboard = $telegram->buildKeyBoard($main_menu_options, $onetime=false , true);

}




if(isStart($text)){
    updateUserIsWithDrawing($chat_id,0);
    if(isStartWithRefLink($text)){
        if(!isUserRegistered($chat_id)){
            //validate ref link (khodesh nabashe && id ref kharej az db nabashe)
            //register the user
            //update ref invited_count and send a msg to ref that a user is signed up with ur link
            //show current user the buttons
            $ref_chat_id = substr($text, 7);
            if(validateRefChatId($ref_chat_id)){
                addNewUser($chat_id,$username,$first_name,$last_name,$ref_chat_id);
                updateRefInvitedCount($ref_chat_id);
                BotIsTyping($ref_chat_id);
                $tg = '"'."tg://user?id=".$chat_id.'"';
                BotSendMessage($ref_chat_id , "یک "."<a href=>کاربر</a>". " جدید با لینک شما در ربات ثبت نام کرد." , null , null , $main_menu_keyboard);
                $replyText = "با موفقیت در سامانه ثبت نام شدید.";
                BotSendMessage($chat_id , $replyText , null , null , $validPhoneKeyboard);
            }
            
            elseif(!validateRefChatId($ref_chat_id)){
                addNewUser($chat_id,$username,$first_name,$last_name,'nobody');
                $replyText = "با موفقیت در سامانه ثبت نام شدید.";
                BotSendMessage($chat_id , $replyText , null , null , $validPhoneKeyboard);
            }
        }
        
        elseif(isUserRegistered($chat_id) && userHasPhone($chat_id)){
            BotSendMessage($chat_id , "چطوری میتونم کمکت کنم؟🤔👇🏻" , null , null , $main_menu_keyboard);
        }
    }
    
    elseif(!isStartWithRefLink($text)){
    
        
        if(!isUserRegistered($chat_id)){
            //register the user
            addNewUser($chat_id,$username,$first_name,$last_name,'nobody');
            //show current user the buttons
            $replyText = "با موفقیت در سامانه ثبت نام شدید.";
            BotSendMessage($chat_id , $replyText , null , null , $validPhoneKeyboard);
        }
        
        elseif(isUserRegistered($chat_id) && userHasPhone($chat_id)){
            BotIsTyping($chat_id);
            BotSendMessage($chat_id , "چطوری میتونم کمکت کنم؟🤔👇🏻" , null , null , $main_menu_keyboard);
        }
    }
    
}

if(!userHasPhone($chat_id)){
    if($telegram->getUpdateType() == 'contact' && validateContact($contact)){
        // update the phone number
        updateUserPhone($chat_id , $contact["phone_number"]);
        BotSendMessage($chat_id , "شماره همراه شما با موفقیت ثبت شد.\nلطفا در کانال زیر عضو شوید.".PHP_EOL."@ThisDoge" , null , null , $iAmJoinedButtonKeyboard);
    }
    
    elseif($telegram->getUpdateType() == 'contact' && !validateContact($contact)){
        $replyText = "شماره نا معتبر است! تنها شماره ایران پذیرفته میشود.".substr($contact['phone_number'], 0,3);
        BotSendMessage($chat_id , $replyText , null , null , $validPhoneKeyboard);
    }
    else{
        $replyText = "برای جلوگیری از تقلب و ایجاد فضای رقابتی سالم لازم است ابتدا شماره تلفن همراه خود را با استفاده از دکمه کیبورد زیر ارسال کنید.";
        BotSendMessage($chat_id , $replyText , null , null , $validPhoneKeyboard);
    }
}

elseif(!isJoined($chat_id)){
    BotSendMessage($chat_id , "برای استفاده از امکانات ربات باید ابتدا در کانال عضو شوید : \n @thisdoge",null,null,$iAmJoinedButtonKeyboard);
}


elseif(isJoined($chat_id)){
    
    if(userHasPhone($chat_id)){
        
        if(!gotJoinedGift($chat_id)){
            sendGift($chat_id,5);
            updateJoinedGift($chat_id);
            BotSendMessage($chat_id , "شما 5 واحد دوج به عنوان هدیه عضویت دریافت کردید." , 'html' , null , $main_menu_keyboard);
        }
        
        
        if(hasRef($chat_id) && userHasPhone($chat_id) && !refGotGift($chat_id)){
            sendGift(getRefChatId($chat_id) , 10);
            updateRefGotGift($chat_id);
            updateValidInvitedCount(getRefChatId($chat_id));
        }
        
        if($text == "لغو عملیات"){
            BotSendMessage($chat_id , "چطوری میتونم کمکت کنم؟🤔👇🏻" , null , null , $main_menu_keyboard);
            updateUserIsWithDrawing($chat_id,0);
        }
        
        
        if(getUserIsWithDrawing($chat_id) == 1){
            if(validTransaction($text)){
                BotSendMessage($chat_id ,"آدرس ولت دوج کوین مقصد را وارد نمایید :", null , null , $cancelKeyboard );
                updateLastWithDraw($chat_id,$text);
                updateUserIsWithDrawing($chat_id,2);
            }
            else{
                BotSendMessage($chat_id ,"ورودی نا معتبر.", null , null , $cancelKeyboard );
            }
            
        }
        
        elseif(getUserIsWithDrawing($chat_id) == 2){
            BotSendMessage($chat_id ,"درخواست شما ثبت شد." , null , null , $main_menu_keyboard );
            updateUserIsWithDrawing($chat_id,0);
            withDraw($chat_id , getLastWithDraw($chat_id));
            $tg = '"'."tg://user?id=".$chat_id.'"';

            BotSendMessage('-1001463804202' , "Withdraw Request from:\n"."<a href=$tg>[$first_name $last_name]</a>"."\n[@$username]\n[coins : ".getLastWithDraw($chat_id)."]\n[wallet address: $text]" , 'html');
        }
        
        
        
        if($text == "💳 حساب کاربری من"){
            $replyText =    "Your Balance :".PHP_EOL."<pre>".getBalance($chat_id)." DOGE</pre>".PHP_EOL.
                            "Pending Invites : ".PHP_EOL."<pre>".getInvitedCount($chat_id)." Person</pre>".PHP_EOL.
                            "Validated Invites : ".PHP_EOL."<pre>".getValidInvitedCount($chat_id)." Person</pre>".PHP_EOL;
            BotSendMessage($chat_id , $replyText , 'html' , null , $main_menu_keyboard);
        }
        
        elseif($text == "🎁 گردونه شانس"){
            if(hasValidWheel($chat_id)){
                BotSendMessage($chat_id , "در حال چرخیدن گردونه . . ." , 'html' , null , $main_menu_keyboard);
                BotIsTyping($chat_id);
                sleep(3);
                $randomGift = rand(1,4);
                sendGift($chat_id,$randomGift);
                BotSendMessage($chat_id , "شما $randomGift واحد دوج دریافت کردید." , 'html' , null , $main_menu_keyboard);
                updateLastTry($chat_id);
            }
            
            elseif(!hasValidWheel($chat_id)){
                BotSendMessage($chat_id , 'شما میتوانید  '.getWaitTime($chat_id).' ساعت دیگر گردنه را بچرخانید.' , 'html' , null , $main_menu_keyboard);
            }
           
        }
        
        elseif($text == "🖇 لینک دعوت از دوستان"){
            $userPhoto = $telegram->getUserProfilePhotos(['user_id' => $chat_id])["result"]["photos"][0][2]["file_id"];
            
            if(is_null($userPhoto)){
                writeNameOnImage("images/tron.jpg",$chat_id);
                $userPhoto = curl_file_create("images/withName$chat_id.jpg",'image/jpg'); 
            }
            
            elseif(!is_null($userPhoto)){
                saveUserImage($userPhoto);
                mergeUserImage("images/raw$chat_id.png");
                $userPhoto = curl_file_create("images/withNameImage$chat_id.jpg",'image/jpg'); 
            }
            $telegram->sendPhoto([
                    'chat_id' => $chat_id,
                    'photo' => $userPhoto,
                    'caption' => "متن تبلیغ".PHP_EOL."http://t.me/thisdogebot?start=".$chat_id,
                    'reply_markup' => $main_menu_keyboard,
                ]);
        }
        
        elseif($text == "💵 درخواست برداشت"){
            if(userHasEnoughBalance($chat_id)){
                updateUserIsWithDrawing($chat_id,1);
                $replyText = "حداقل میزان برداشت 200 دوج میباشد.\nموجودی شما ".getBalance($chat_id)." دوج میباشد.".PHP_EOL."رقم برداشتی خود را وارد نمایید :".PHP_EOL."یک عدد بزرگتر از 200 و با کیبورد انگلیسی وارد شود." ;
                BotSendMessage($chat_id ,$replyText, null , null , $cancelKeyboard );
                //withDraw($chat_id , 200.5);
                //BotSendMessage('-1001463804202' , "Withdraw Request from: $first_name $last_name [@$username]" , 'html');
                //-1001463804202
                //send a notif to admin
                //send a sucess notif to user
            }
            
            else{
                BotSendMessage($chat_id , "حداقل میزان برداشت 200 دوج میباشد.موجودی شما ".PHP_EOL.getBalance($chat_id)." دوج میباشد." , 'html' , null , $main_menu_keyboard);
            }
        }
        /*
        else{
            BotSendMessage($chat_id , "dastoor na shenakhte");
        }
        */
        
    }
    
    
    
    
}



$connection->close();

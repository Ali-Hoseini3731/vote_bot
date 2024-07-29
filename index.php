<?php
define("API_KEY", "7350804143:AAG8ixsfg0jPRe_8696wXOzsSrNa4YtlobE");
define("BASE_URL", "https://api.telegram.org/bot");


require "dbHandler.php";

function sendMessage($method, $data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, BASE_URL . API_KEY . "/" . $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    if (curl_error($ch)) {
        return curl_error($ch);
    } else {
        return json_decode($result);
    }
}

$response = file_get_contents("php://input");
$response = json_decode($response, true);


if (isset($response["message"])) {

    $new_chat_member = $response["message"]["new_chat_member"]["username"];
    if (isset($new_chat_member)) {

        if ($new_chat_member == "groupVote_bot") {
            $user_id = $response["message"]["from"]["id"];
            $first_name = $response["message"]["from"]["first_name"];
            $last_name = $response["message"]["from"]["last_name"];
        } else {
            $user_id = $response["message"]["new_chat_member"]["id"];
            $first_name = $response["message"]["new_chat_member"]["first_name"];
            $last_name = $response["message"]["new_chat_member"]["last_name"];
        }

    }
    $chat_id = $response["message"]["chat"]["id"];

    try {
        $sql = "SELECT * FROM vote WHERE user_id=$user_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $user_row_count = $stmt->rowCount();
    } catch (PDOException $e) {
        echo $sql . "<br>" . $e->getMessage();
    }


    if ($user_row_count == 0) {

        try {
            $sql = "INSERT INTO vote (user_id) VALUES ($user_id)";
            $conn->exec($sql);
        } catch (PDOException $e) {
            echo $sql . "<br>" . $e->getMessage();
        }
    }

    $total_vote = $bad = $medium = $good = $very_good = 0;
    try {
        $sql = "SELECT * FROM vote WHERE voted!=''";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_vote = $stmt->rowCount();
    } catch (PDOException $e) {
        echo $sql . "<br>" . $e->getMessage();
    }

    foreach ($result as $items) {
        foreach ($items as $key => $value) {
            if ($value == "bad") {
                $bad += 1;
            } elseif ($value == "medium") {
                $medium += 1;
            } elseif ($value == "good") {
                $good += 1;
            } elseif ($value == "very good") {
                $very_good += 1;
            }
        }
    }

    $msg = "سلام خوش آمدید آقای " . $first_name . $last_name . "\n\n";
    $msg .= "لطفا به این گروه رای دهید:\n\n";
    $msg .= "بد:  " . $bad . "\n";
    $msg .= "متوسط:  " . $medium . "\n";
    $msg .= "خوب:  " . $good . "\n";
    $msg .= "خیلی خوب:  " . $very_good . "\n\n";
    $msg .= "تعداد کل رای دهندگان: " . $total_vote;
    $data = array(
        "chat_id" => $chat_id,
        "text" => $msg,
        "reply_markup" => json_encode(
            [
                "inline_keyboard" => [[
                    ["text" => "بد", "callback_data" => "bad"],
                    ["text" => "متوسط", "callback_data" => "medium"],
                    ["text" => "خوب", "callback_data" => "good"],
                    ["text" => "خیلی خوب", "callback_data" => "very good"],
                ]],
            ]
        ),
    );

    if (isset($response["message"]["new_chat_member"]["username"])) {
        sendMessage("sendMessage", $data);
    }


}

if (isset($response["callback_query"])) {

    $callback_user_id = $response["callback_query"]["from"]["id"];
    $callback_voted = $response["callback_query"]["data"];
    $callback_chat_id = $response["callback_query"]["message"]["chat"]["id"];
    $callback_query_id = $response["callback_query"]["id"];
    $callback_query_message_id = $response["callback_query"]["message"]["message_id"];

    try {
        $sql = "SELECT * FROM vote WHERE user_id = $callback_user_id AND voted!=''";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $row_count = $stmt->rowCount();
    } catch (PDOException $e) {
        echo $sql . "<br>" . $e->getMessage();
    }

    if ($row_count == 0) {

        try {
            $sql = "UPDATE vote SET voted = '$callback_voted' WHERE user_id = $callback_user_id";
            $conn->exec($sql);
        } catch (PDOException $e) {
            echo $sql . "<br>" . $e->getMessage();
        }

        $msg1 = "از این که در نظرسنجی شرکت کردید سپاسگزاریم";
        $data = array(
            "callback_query_id" => $callback_query_id,
            "text" => $msg1,
        );
        sendMessage("answerCallbackQuery", $data);

    } else {

        $msg2 = "متاسفیم شما نظرسنجی را انجام داده اید";
        $data = array(
            "callback_query_id" => $callback_query_id,
            "text" => $msg2,
        );
        sendMessage("answerCallbackQuery", $data);
    }


    try {
        $sql = "SELECT * FROM vote";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_vote = $stmt->rowCount();
    } catch (PDOException $e) {
        echo $sql . "<br>" . $e->getMessage();
    }

    foreach ($result as $items) {
        foreach ($items as $key => $value) {
            if ($value == "bad") {
                $bad += 1;
            } elseif ($value == "medium") {
                $medium += 1;
            } elseif ($value == "good") {
                $good += 1;
            } elseif ($value == "very good") {
                $very_good += 1;
            }
        }
    }


    $msg = "سلام خوش آمدید آقای " . $first_name . $last_name . "\n\n";
    $msg .= "لطفا به این گروه رای دهید:\n\n";
    $msg .= "بد:  " . $bad . "\n";
    $msg .= "متوسط:  " . $medium . "\n";
    $msg .= "خوب:  " . $good . "\n";
    $msg .= "خیلی خوب:  " . $very_good . "\n\n";
    $msg .= "تعداد کل رای دهندگان: " . $total_vote;


    $data = array(
        "chat_id" => $callback_chat_id,
        "message_id" => $callback_query_message_id,
        "text" => $msg,
        "reply_markup" => json_encode(
            [
                "inline_keyboard" => [[
                    ["text" => "بد", "callback_data" => "bad"],
                    ["text" => "متوسط", "callback_data" => "medium"],
                    ["text" => "خوب", "callback_data" => "good"],
                    ["text" => "خیلی خوب", "callback_data" => "very good"],
                ]],
            ]
        ),
    );
    sendMessage("editMessageText", $data);
}







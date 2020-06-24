<?php

/*
 * Author: pligin
 * Site: psweb.ru
 * Telegram: t.me/pligin
 */

require_once(dirname(__FILE__).'/../../vibernotificationsdata.php');

class ViberNotificationsCallbackModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $viber = new ViberNotificationsData(Configuration::get('VIBER_TOKEN'),Configuration::get('VIBER_BOT_NAME'));
        $request = file_get_contents("php://input");
        $input = json_decode($request, true);
        $lts = json_decode(Configuration::get('VIBER_LAST_TS'),true);
        if(in_array($input['message_token'],$lts['messages'])){
            header("HTTP/1.1 200 OK");
            die();
        }else{
            if(empty($lts['lastdelete']) || $lts['lastdelete'] < time() - 60*10){
                unset($lts['messages']);
                $lts['lastdelete'] = time();
                $lts['messages'] = array();
            }
            $lts['messages'][] = $input['message_token'];
            Configuration::updateValue('VIBER_LAST_TS', json_encode($lts));
        }
        if($input['event'] == 'webhook') {
            $webhook_response['status'] = 0;
            $webhook_response['status_message'] = 'ok';
            $webhook_response['event_types'] = 'delivered';
            echo json_encode($webhook_response);
            die();
        }
        if($input['event'] == 'subscribed') {
            $isset = json_decode(Configuration::get('VIBER_RECEIVER_IDs'),true);
            if(!key_exists($input['user_id'],$isset)){
                $isset[$input['user_id']] = $sender_name;
                Configuration::updateValue('VIBER_RECEIVER_IDs', json_encode($isset));
                $viber -> sendMessage($input['user_id'], 'Тебе я буду отправлять все оповещения из магазина');
            }else{
                $viber -> sendMessage($input['user_id'], 'Ты уже есть в подписках');
            }
            die();
        }
        if($input['event'] == 'unsubscribed') {
            $isset = json_decode(Configuration::get('VIBER_RECEIVER_IDs'),true);
            if(key_exists($input['user_id'],$isset)){
                unset($isset[$input['user_id']]);
                Configuration::updateValue('VIBER_RECEIVER_IDs', json_encode($isset));
            }
            die();
        }
        if($input['event'] == 'message'){
            $type = $input['message']['type']; //type of message received (text/picture)
            $text = $input['message']['text']; //actual message the user has sent
            $sender_id = $input['sender']['id']; //unique viber id of user who sent the message
            $sender_name = $input['sender']['name']; //name of the user who sent the message
            if($input['event'] == 'message' && $type == 'text' && $text == 'send me'){
                $isset = json_decode(Configuration::get('VIBER_RECEIVER_IDs'),true);
                if(!key_exists($sender_id,$isset)){
                    $isset[$sender_id] = $sender_name;
                    Configuration::updateValue('VIBER_RECEIVER_IDs', json_encode($isset));
                    $viber -> sendMessage($sender_id, 'Я буду отправлять Вам все оповещения из магазина');
                }else{
                    $viber -> sendMessage($sender_id, 'Вы уже есть в подписках');
                }
            }elseif($input['event'] == 'message' && $type == 'text' && $text == 'list'){
                $isset = json_decode(Configuration::get('VIBER_RECEIVER_IDs'),true);
                if(empty($isset)){
                    $viber -> sendMessage($sender_id, 'Никто не подписан');
                }else{
                    $str = implode(', ',$isset);
                    $viber -> sendMessage($sender_id, 'Подписаны на оповещения магазина: '.$str);
                }

            }elseif($input['event'] == 'message' && $type == 'text' && $text == 'del me'){
                $isset = json_decode(Configuration::get('VIBER_RECEIVER_IDs'),true);
                unset($isset[$sender_id]);
                Configuration::updateValue('VIBER_RECEIVER_IDs', json_encode($isset));
                $viber -> sendMessage($sender_id, 'Вы удалены из списка');
            }elseif($input['event'] == 'message' && $type == 'text' && $text == 'del all'){
                Configuration::updateValue('VIBER_RECEIVER_IDs', json_encode(array()));
                $viber -> sendMessage($sender_id, 'Все удалены');
            }
            elseif($input['event'] == 'message' && $type == 'text' && $text == 'get data'){
                $viber -> sendMessage($sender_id, Configuration::get('VIBER_LAST_TS'));
            }
        }
    }
}
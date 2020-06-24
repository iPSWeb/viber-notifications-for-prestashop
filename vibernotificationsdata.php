<?php
/**
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2016 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/*
 * Author: pligin
 * Site: psweb.ru
 * Telegram: t.me/pligin
 */

class ViberNotificationsData
{
    private $auth_token = '';
    private $bot_name = '';

    public function __construct($auth_token,$bot_name='PSWeb')
    {
        $this->bot_name = $bot_name;
        $this->auth_token = $auth_token;
    }

    /*
     * Отправка запроса
     */
    private function sendAPIRequest($data,$method)
    {
        $request_data = json_encode($data);
	$ch = curl_init("https://chatapi.viber.com/pa/".$method);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	$err = curl_error($ch);
	curl_close($ch);
	if($err) {return Tools::jsonDecode($err, true);}
	else {return Tools::jsonDecode($response, true);}
    }
    
    /*
     * Установка веб хук
     */
    public function setWebHook($url){
        $jsonData = array(
            'auth_token' => $this->auth_token,
            'url' => $url,
            'event_types' => ["subscribed","unsubscribed","delivered","message","seen","conversation_started"]
            );
        return $this->sendAPIRequest($jsonData,'set_webhook');
    }
    
    /*
     * Отправка сообщения
     */
    public function sendMessage($sender_id, $text, $tracking_data = Null)
    {
	return $this->sendMsg($sender_id, $text, "text", $tracking_data);
    }
    
    /*
     * Отправка сообщения
     */
    public function sendMsg($sender_id, $text, $type, $tracking_data = Null, $arr_asoc = Null)
    {
	$data['auth_token'] = $this->auth_token;
	$data['receiver'] = $sender_id;
	if($text != Null) {$data['text'] = $text;}
	$data['type'] = $type;
	//$data['min_api_version'] = $input['sender']['api_version'];
	$data['sender']['name'] = $this->bot_name;
	//$data['sender']['avatar'] = $input['sender']['avatar'];
	if($tracking_data != Null) {$data['tracking_data'] = $tracking_data;}
	if($arr_asoc != Null){
            foreach($arr_asoc as $key => $val) {$data[$key] = $val;}
	}
	return $this->sendAPIRequest($data,'send_message');
    }
}

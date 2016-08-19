<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link http://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('Security');`
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();

        $this->loadComponent('RequestHandler');
        $this->loadComponent('Flash');
    }

    /**
     * Before render callback.
     *
     * @param \Cake\Event\Event $event The beforeRender event.
     * @return void
     */
    public function beforeRender(Event $event)
    {
        if (!array_key_exists('_serialize', $this->viewVars) &&
            in_array($this->response->type(), ['application/json', 'application/xml'])
        ) {
            $this->set('_serialize', true);
        }
    }

	/**
	 * Отправляет JSON ответ
	 *
	 * @param array $jsonArray
	 * @return NULL
	 */
	private function _sendJsonResponse($jsonArray) {
		$jsonArray['_serialize'] = array_keys($jsonArray);
		$jsonArray['_jsonOptions'] = JSON_UNESCAPED_UNICODE;

		$this->set($jsonArray);
		$this->viewBuilder()->className('Json');
		return null;
	}

	/**
	 * Возвращает ответ без ошибки и прерывает выполнение
	 *
	 * @param array $jsonData
	 * @return NULL
	 */

	protected function _sendJsonOk($jsonData) {
		return $this->_sendJsonResponse(['status' => 'ok'] + $jsonData);
	}

	/**
	 * Возвращает ответ с ошибкой, сообщением, и прерывает выполнение
	 *
	 * @param string $message
	 * @param array $jsonData дополнительные параметры если нужны
	 * @return NULL
	 */
	protected function _sendJsonError($message, $jsonData = []) {
		return $this->_sendJsonResponse(['status' => 'error', 'message' => $message] + $jsonData);
	}
}

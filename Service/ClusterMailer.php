<?php

namespace Multifinger\UtilitiesBundle\Service;

use Multifinger\AppSettingsBundle\Service\AppSettingsService;

/**
 * TODO похорошему надо унаследоваться от \Swift_Mailer
 * @author Maksim Borisov <maksim.i.borisov@gmail.com> 25.08.17 6:19
 * Class ClusterMailer
 * @package Multifinger\UtilitiesBundle\Service
 */
class ClusterMailer
{

    private const BLOCKED_SETTING_PREFIX = 'multi_mail_node_blocked_';
    private const BLOCK_TIMEOUT_SETTING  = 'multi_mail_block_timeout';
    private const DEFAULT_BLOCK_TIMEOUT  = 300;

    private $whitelist;

    private $blacklist;

    /** @var string[] массив всех используемых нод кластера (адреса скрипта-приемника) */
    private $nodes;

    /** @var string[] массив незаблокированных нод (адреса скрипта-приемника) */
    private $activeNodes = [];

    /** @var int - ключ текущей ноды в массиве нод */
    private $current;

    /** @var AppSettingsService */
    private $settings;

    //------------------------------------------------------------------------------------------------------------------

    public function __construct(AppSettingsService $settings, $nodes)
    {
        $this->settings = $settings;
        $this->nodes = $nodes;
    }

    public function setWhitelist(array $list = null)
    {
        $this->whitelist = $list;
    }

    public function setBlacklist(array $list = null)
    {
        $this->blacklist = $list;
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * @author Maksim Borisov <maksim.i.borisov@gmail.com> 25.08.17 8:19
     * @param \Swift_Message $message
     * @param null $failedRecipients
     */
    public function send(\Swift_Message $message, &$failedRecipients = null)
    {
        // Stop sending if email in blacklist
        if (is_array($this->blacklist) && sizeof($this->blackList)) {
            if (preg_match('#(:?'.implode('|', $this->blacklist).')#mui', key($message->getTo()))) {
                return;
            }
        }

        // Stop sending if whitelist defined and email not in whitelist
        if (is_array($this->whitelist)) {
            $to = $message->getTo();
            foreach ($to as $mail => $name) {
                if (!in_array($mail, $this->whitelist)) {
                    unset($to[$mail]);
                }
            }
            $message->setTo($to);
            // None recipients in whitelist
            if (!sizeof($message->getTo())) {
                return;
            }
        }

        $this->sendRecursive($message);
    }

    /**
     * Отправка письма непосредственно на сервер
     * Рекурсивно проверяются все ноды, если нода недоступна, она блокируется
     * Попытки заканчиваются, когда заканчиваются доступные незаблокированные ноды
     * @author Maksim Borisov <maksim.i.borisov@gmail.com> 25.08.17 6:25
     * @param \Swift_Message $message
     */
    private function sendRecursive(\Swift_Message $message)
    {
        $data = array();

        $node = $this->getNode();

        if (!$node) {
            // throw new \Exception('No available servers. Try later');
            return;
        }

        // подмена домена отправителя на домен текущего кластера для from
        $from = $this->fixFrom($message->getFrom(), $node);

        $data['getContentType']     = $message->getContentType();
        $data['getBody']            = $message->getBody();
        $data['getFrom']            = $from;
        $data['getReplyTo']         = $message->getReplyTo();
        $data['getReturnPath']      = $message->getReturnPath();
        $data['getTo']              = $message->getTo();
        $data['getCc']              = $message->getCc();
        $data['getBcc']             = $message->getBcc();
        $data['getSender']          = $message->getSender();
        $data['getSubject']         = $message->getSubject();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $node);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'data='.urlencode(json_encode($data)));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_exec($ch);

        $info = curl_getinfo($ch);
        curl_close($ch);

        if (200 != $info['http_code']) {
            // Block unresponding node by updating fail-timestamp
            $this->settings->set(self::BLOCKED_SETTING_PREFIX.$node, time());
            // TODO email error about node lock


            // отправка повторно через другую ноду
            $this->sendRecursive($message);
        }
    }

    /**
     * Выбор ноды, с проверкой блокировки
     * @author Maksim Borisov <maksim.i.borisov@gmail.com> 25.08.17 6:33
     * @return string|null
     */
    private function getNode()
    {
        if (!is_array($this->nodes)) {
            return null;
        }

        // Проверяем ноды на блокировку
        foreach ($this->nodes as $node) {
            $timeout = $this->settings->get(self::BLOCK_TIMEOUT_SETTING, self::DEFAULT_BLOCK_TIMEOUT);
            if ($this->settings->get(self::BLOCKED_SETTING_PREFIX.$node, 0) + $timeout > time()) {
                continue;
            }
            $this->activeNodes[] = $node;
        }

        // Выбираем текущую ноду случайно для первой попытки, далее перебираем по порядку
        if ($this->current === null) {
            $this->current = rand(0, count($this->activeNodes) - 1);
        } else {
            $this->current = (++$this->current < count($this->activeNodes)) ? $this->current : 0;
        }

        return $this->activeNodes[$this->current];
    }

    /**
     * Заменяет домен адресов отправителя $from на домен ноды $node
     * @author Maksim Borisov <maksim.i.borisov@gmail.com> 25.08.17 10:27
     * @param array $from
     * @param $node
     * @return array
     */
    private function fixFrom(array $from, $node)
    {
        $domain = parse_url($node, PHP_URL_HOST);

        $result = [];
        foreach ($from as $email => $name) {
            $result[explode('@', $email)[0].'@'.$domain] = $name;
        }

        return $result;
    }

}

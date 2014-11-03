<?php
/**
 * BoxBilling
 *
 * @copyright BoxBilling, Inc (http://www.boxbilling.com)
 * @license   Apache-2.0
 *
 * Copyright BoxBilling, Inc
 * This source file is subject to the Apache-2.0 License that is bundled
 * with this source code in the file LICENSE
 */


namespace Box\Mod\Hook;
use Box\InjectionAwareInterface;

class Service implements InjectionAwareInterface
{
    /**
     * @var \Box_Di
     */
    protected $di = null;

    /**
     * @param \Box_Di $di
     */
    public function setDi($di)
    {
        $this->di = $di;
    }

    /**
     * @return \Box_Di
     */
    public function getDi()
    {
        return $this->di;
    }

    public function getSearchQuery($filter)
    {
        $q="SELECT id, rel_type, rel_id, meta_value as event, created_at, updated_at
            FROM extension_meta 
            WHERE extension = 'mod_hook'
            AND rel_type = 'mod'
            AND meta_key = 'listener'
        ";
        return array($q, array());
    }
    
    public function toApiArray($row)
    {
        return $row;
    }
    
    public static function onAfterAdminActivateExtension(\Box_Event $event)
    {
        $params = $event->getParameters();
        if(!isset($params['id'])) {
            $event->setReturnValue(FALSE);
        } else {
            $di = $event->getDi();
            $ext = $di['db']->load('extension', $params['id']);
            if(is_object($ext) && $ext->type == 'mod') {
                $service = $di['mod_service']('hook');
                $service->batchConnect($ext->name);
            }
            $event->setReturnValue(TRUE);
        }
    }
    
    public static function onAfterAdminDeactivateExtension(\Box_Event $event)
    {
        $di = $event->getDi();
        $params = $event->getParameters();
        if($params['type'] == 'mod') {
            $q="DELETE FROM extension_meta 
                WHERE extension = 'mod_hook'
                AND rel_type = 'mod'
                AND rel_id = :mod
                AND meta_key = 'listener'";
            $di['db']->exec($q, array('mod'=>$params['id']));
        }
        
        $event->setReturnValue(TRUE);
    }

    /**
     * @param string $mod module name
     * @return bool
     */
    public function batchConnect($mod_name = null)
    {
        $mods = array();
        if(null !== $mod_name) {
            $mods[] = $mod_name;
        } else {
            $extensionService = $this->di['mod_service']('extension');
            $mods = $extensionService->getCoreAndActiveModules();
        }

        foreach($mods as $m) {
            $mod = $this->di['mod']($m);
            if($mod->hasService()) {
                $class = $mod->getService();
                $reflector = new \Reflectionclass($class);
                foreach($reflector->getMethods() as $method) {
                    $p = $method->getParameters();
                    if($method->isPublic()
                        && isset($p[0])
                        && $p[0]->getclass()
                        && in_array($p[0]->getclass()->getName(), array('Box_Event', '\Box_Event'))) {
                        $this->connect(array('event'=>$method->getName(), 'mod'=>$mod->getName()));
                    }
                }
            }
        }

        $this->_disconnectUnavailable();

        return true;
    }

    /**
     * Connect event for module
     *
     * @param string $event - event name, ie: onAfterAdminDeactivateExtension
     * @param string $mod - module name where hook is located
     *
     * @return boolean
     * @throws Box_Exception
     */
    private function connect($data)
    {
        if(!isset($data['event'])) {
            throw new \Box_Exception('Hook event not passed');
        }

        if(!isset($data['mod'])) {
            throw new \Box_Exception('Param mod not passed');
        }

        $event = $data['event'];
        $mod = $data['mod'];

        $q="SELECT id
            FROM extension_meta
            WHERE extension = 'mod_hook'
            AND rel_type = 'mod'
            AND rel_id = :mod
            AND meta_key = 'listener'
            AND meta_value = :event
        ";
        if($this->di['db']->getCell($q, array('mod'=>$mod, 'event'=>$event))) {
            // already connected
            return true;
        }

        $meta = $this->di['db']->dispense('extension_meta');
        $meta->extension    = 'mod_hook';
        $meta->rel_type     = 'mod';
        $meta->rel_id       = $mod;
        $meta->meta_key     = 'listener';
        $meta->meta_value   = $event;
        $meta->created_at   = date('c');
        $meta->updated_at   = date('c');
        $this->di['db']->store($meta);

        return true;
    }

    /**
     * Disconnect unavailable listeners
     */
    private function _disconnectUnavailable()
    {
        $rm_sql="DELETE FROM extension_meta WHERE id = :id";

        $sql="SELECT id, rel_id, meta_value
            FROM extension_meta
            WHERE extension = 'mod_hook'
            AND rel_type = 'mod'
            AND meta_key = 'listener'
        ";
        $list = $this->di['db']->getAll($sql);
        $extensionService = $this->di['mod_service']('extension');
        foreach($list as $listener) {
            try {
                $mod_name = $listener['rel_id'];
                $event = $listener['meta_value'];

                // do not disconnect core modules
                if($extensionService->isCoreModule($mod_name)) {
                    continue;
                }

                // disconect modules without service class
                $mod = $this->di['mod']($mod_name);
                if(!$mod->hasService()) {
                    $this->di['db']->exec($rm_sql, array('id'=>$listener['id']));
                    continue;
                }

                $ext = $this->di['db']->findOne('extension', "type = 'mod' AND name = :mod AND status = 'installed'", array('mod'=>$mod_name));
                if(!$ext) {
                    $this->di['db']->exec($rm_sql, array('id'=>$listener['id']));
                    continue;
                }

                $s = $mod->getService();
                if(!method_exists($s, $event)) {
                    $this->di['db']->exec($rm_sql, array('id'=>$listener['id']));
                    continue;
                }
            } catch(\Exception $e) {
                error_log($e->getMessage());
            }
        }
    }
}
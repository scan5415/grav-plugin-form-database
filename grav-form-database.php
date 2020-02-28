<?php

namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use PDO;
use Grav;

/**
 * Class FormDatabasePlugin
 * @package Grav\Plugin
 */
class GravFormDatabasePlugin extends Plugin {
    protected $db;
    protected $table;
    protected $config;
    protected $pname;//plugin's name

    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents() {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onFormProcessed' => ['onFormProcessed', 0]
        ];
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized() {

        // Don't proceed if we are in the admin plugin
        $this->pname = 'grav-form-database';
        if ($this->isAdmin()) {
            return;
        }
    }


    /**
     * Save Data in Database when processing the form
     *
     * @param Event $event
     */
    public function onFormProcessed(Event $event) {
        $action = $event['action'];
        switch ($action) {
            case 'database' :
                $this->grav['debugger']->addMessage('onFormProcessed - database');

                $params = $event['params'];
                $form = $event['form'];

                $this->prepareDB($params);
                
                $form_fields = $this->prepareFormFields($params['table_fields']?? $params['fields'], $form);
                $fields = array_keys($form_fields);
                
                $string = 'INSERT INTO ' . $this->table . ' ('. implode(', ', $fields).') VALUES (:'. implode(', :', $fields). ')';

                $this->db->insert($string, $form_fields);

                break;
        }
    }
    /**
     * Ensure Data is ready for PDO
     * @param type $formFields
     * @param type $form
     * @return type
     */
    private function prepareFormFields($formFields, $form) {
        $data = $form['data'];
        $this->grav['debugger']->addMessage($data);
        $twig = $this->grav['twig'];
        $vars = [
            'form' => $form
        ];
        $fields = $formFields;
        $separator = $this->config->get('plugins.'.$this->pname.'.array_separator')??';';//backwards compatible
        foreach ($fields as $field => $val) {
            $dataValue = $data[$val];
           
            if (strrpos($val, '{{')>=0 && strrpos($val, '}}')>0) {
                // Process with Twig
                $dataValue = $twig->processString($val, $vars);
            } else if(is_null($dataValue)){
                // if value hard coded
                $dataValue = $val;
            }
            if (gettype($dataValue) == 'array')
            {
                //stringify array
                $dataValue = implode($separator, $dataValue); //if form result = array expl. checkboxes or multiple selection 
            }
            $fields[$field] = $dataValue;
        }
        return $fields;
    }

    private function prepareDB($params) {
       
        //if db not passed with the form
        $db_name = $params['db']?? $this->config->get('plugins.'.$this->pname.'.db');
        if($db_name=='')
        {
             throw new \RuntimeException( "NO db SET. Set it in {$this->pname}.yaml of in your form's yaml");
        }
        $this->table = $params['table']?? $this->config->get('plugins.'.$this->pname.'.table');
        if ($this->table == '') {
             throw new \RuntimeException( "NO table SET. Set it in  {$this->pname}.yaml of in your form's yaml");
        }
        
        // backwards compatible config
        $engine = $this->config->get('plugins.'.$this->pname.'.engine')??'mysql';
        $user = $this->config->get('plugins.'.$this->pname.'.username')??$this->config->get('plugins.'.$this->pname.'.mysql_username')??'';
        $pwd = $this->config->get('plugins.'.$this->pname.'.password')??$this->config->get('plugins.'.$this->pname.'.mysql_password')??'';
        $server = $this->config->get('plugins.'.$this->pname.'.server')??$this->config->get('plugins.'.$this->pname.'.mysql_server')??'';
        $port = $this->config->get('plugins.'.$this->pname.'.port')??$this->config->get('plugins.'.$this->pname.'.mysql_port')??'';
        //
        $dsn = $engine . ':';
        switch ($engine) {
            case 'mysql':
                $dsn .= 'host=' . $server;
                $dsn .= ';dbname=' . $db_name;
                $dsn .= ';port=' .$port;
                $user = $user;
                $pwd = $pwd;
                break;
            case 'pgsql':
                $dsn .= 'host=' . $server;
                $dsn .= ' dbname=' . $db_name;
                $dsn .= ' port=' . $port;
                $dsn .= ' user=' . $user;
                $dsn .= ' password=' . $pwd;
                $user = '';
                $pwd = '';
                break;
            case 'sqlite':
                $dsn .= $server;
                $dsn .= '/';
                $dsn .= $db_name;
                break;
            default:
                $dsn .= 'host=' . $server;
                $dsn .= ';dbname=' . $db_name;
                $dsn .= ';port=' .$port;
                $user = $user;
                $pwd = $pwd;
                break;
                
        }
        try {
            $this->db = $this->grav['database']->connect( $dsn, $user, $pwd, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION] );
        } catch (Exception $e) {
            throw new \RuntimeException($user . ":" . $pwd . " | " . $dsn . " | " . $e->getMessage());
        }
        return $this->db;
    }
}

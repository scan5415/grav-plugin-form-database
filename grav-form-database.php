<?php

namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use PDO;

/**
 * Class FormDatabasePlugin
 * @package Grav\Plugin
 */
class GravFormDatabasePlugin extends Plugin {

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
        if ($this->isAdmin()) {
            return;
        }
    }

    /**
     * Do some work for this event, full details of events can be found
     * on the learn site: http://learn.getgrav.org/plugins/event-hooks
     *
     * @param Event $e
     */
    public function onPageContentRaw(Event $e) {
        // Get a variable from the plugin configuration
        $text = $this->grav['config']->get('plugins.grav-form-database.text_var');

        // Get the current raw content
        $content = $e['page']->getRawContent();

        // Prepend the output with the custom text and set back on the page
        $e['page']->setRawContent($text . "\n\n" . $content);
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

                $pdo = $this->prepareDB($params['db']);
                
                $form_fields = $this->prepareFormFields($params['table_fields'], $form);
                $fields = array_keys($form_fields);
                
                $string = 'INSERT INTO ' . $params['table'] . ' ('. implode(', ', $fields).') VALUES (:'. implode(', :', $fields). ')';
                $query = $pdo->prepare($string);
//                $this->grav['debugger']->addMessage($query);
//                $this->grav['debugger']->addMessage($form_fields);
                $query->execute($form_fields);
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
                $dataValue = implode('|', $dataValue); //if form result = array expl. checkboxes or multiple selection 
            }
            $fields[$field] = $dataValue;
        }
        return $fields;
    }

    private function prepareDB($db) {
        $engine = $this->config->get('plugins.grav-form-database.engine');
        $dsn = $engine . ':';
        $user = '';
        $pwd = '';

        switch ($engine) {
            case 'mysql':
                $dsn .= 'host=' . $this->config->get('plugins.grav-form-database.server');
                $dsn .= ';dbname=' . $db;
                $dsn .= ';port=' . $this->config->get('plugins.grav-form-database.port');
                $user = $this->config->get('plugins.grav-form-database.username');
                $pwd = $this->config->get('plugins.grav-form-database.password');
                break;
            case 'pgsql':
                $dsn .= 'host=' . $this->config->get('plugins.grav-form-database.server');
                $dsn .= ' dbname=' . $db;
                $dsn .= ' port=' . $this->config->get('plugins.grav-form-database.port');
                $dsn .= ' user=' . $this->config->get('plugins.grav-form-database.username');
                $dsn .= ' password=' . $this->config->get('plugins.grav-form-database.password');
                break;
            case 'sqlite':
                $dsn .= $this->config->get('plugins.grav-form-database.server');
                break;
        }
        //$this->grav['debugger']->addMessage($dsn);
        try {
            $pdo = new \PDO($dsn, $user, $pwd);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $e) {
            throw new \RuntimeException($user . ":" . $pwd . " | " . $dsn . " | " . $e->getMessage());
        }
        return $pdo;
    }

}

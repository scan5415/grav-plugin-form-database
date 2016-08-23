<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

/**
 * Class FormDatabasePlugin
 * @package Grav\Plugin
 */
class FormDatabasePlugin extends Plugin
{
	
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
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onFormProcessed' => ['onFormProcessed', 0]
        ];
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
		
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
    public function onPageContentRaw(Event $e)
    {
        // Get a variable from the plugin configuration
        $text = $this->grav['config']->get('plugins.form-database.text_var');

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
    public function onFormProcessed(Event $event)
    {
        $form = $event['form'];
        $action = $event['action'];
        $params = $event['params'];
		
		switch($action) {
			case 'database':

				//Connect to DB
				$server = $this->config->get('plugins.form-database.mysql_server');
				$port = $this->config->get('plugins.form-database.mysql_port');
				$user = $this->config->get('plugins.form-database.mysql_username');
				$pwd = $this->config->get('plugins.form-database.mysql_password');
				$db = $params['db'];
				$table = $params['table'];
				
				// Establish MySQL Connection
				$db_con = \mysqli_connect($server,$user,$pwd,$db,$port);
				if(!$db_con) {
					throw new \RuntimeException($user .":" . $pwd ."@" . $server .":" . $port ."/" . $db . " | " . mysqli_connect_error());
				}
				
				// Create SQL Statement from field matching in the page settings
				foreach($params['fields'] as $field => $val) {
					//Check DB Field Type
					$fieldSQL = "SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '" . $table . "' AND column_name = '" . $field . "'";
					if($fieldResult = \mysqli_query($db_con,$fieldSQL)) {
						$fieldRow = \mysqli_fetch_row($fieldResult);
						
						$fieldType = $fieldRow[0];
					} else {
						throw new \RuntimeException(mysqli_error($db_con));
					}
					
					if(strlen($fieldnames) === 0) {
						$fieldnames = "(" . $field ."";
						//Check if it an number value, if yes don't put in ''
						if(in_array($fieldType,array('smallint','tinyint','mediumint','int','bigint','decimal','float','double','read','bit','boolean','serial'),true)) {
							$fieldvalues = "(" . $_POST[$val];
						} else {
							$fieldvalues = "('" . $_POST[$val] ."'";
						}
						
					} else {
						
						$fieldnames .= "," . $field . "";
						//Check if it an number value, if yes don't put in ''
						if(in_array($fieldType,array('smallint','tinyint','mediumint','int','bigint','decimal','float','double','read','bit','boolean','serial'),true)) {
							$fieldvalues .= "," . $_POST[$val];
						} else {
							$fieldvalues .= ",'" . $_POST[$val] ."'";
						}
					}
					
				}
				$fieldnames .= ")";
				$fieldvalues .= ")";
				$sql = "INSERT INTO " . $table . " " . $fieldnames ." VALUES " . $fieldvalues;

				if(!(\mysqli_query($db_con,$sql))) {
					throw new \RuntimeException(mysqli_error($db_con));
				}
				
				mysqli_close($db_con);
			break;
		}
	}
}
